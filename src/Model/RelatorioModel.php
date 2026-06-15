<?php
// src/Model/RelatorioModel.php

class RelatorioModel
{
    public function __construct(private PDO $pdo) {}

    public function faturamentoBruto(string $inicio, string $fim): float
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(ap.valor_procedimento)
            FROM atendimentos a
            JOIN atendimento_procedimentos ap ON a.id = ap.atendimento_id
            WHERE a.data_atendimento BETWEEN ? AND ?
              AND a.status_pagamento IN ('pago','pendente')
              AND ap.status_execucao  IN ('concluido','pendente')
        ");
        $stmt->execute([$inicio . ' 00:00:00', $fim . ' 23:59:59']);
        return (float) ($stmt->fetchColumn() ?? 0);
    }

    public function liquidoClinica(string $inicio, string $fim): float
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(valor_liquido_clinica)
            FROM atendimentos
            WHERE data_atendimento BETWEEN ? AND ?
              AND status_pagamento = 'pago'
        ");
        $stmt->execute([$inicio . ' 00:00:00', $fim . ' 23:59:59']);
        return (float) ($stmt->fetchColumn() ?? 0);
    }

    public function totalDespesas(string $inicio, string $fim): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT SUM(valor) FROM despesas WHERE data_despesa BETWEEN ? AND ?"
        );
        $stmt->execute([$inicio, $fim]);
        return (float) ($stmt->fetchColumn() ?? 0);
    }

    public function listarDespesasNoPeriodo(string $inicio, string $fim, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM despesas
            WHERE data_despesa BETWEEN ? AND ?
            ORDER BY data_despesa DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $inicio, PDO::PARAM_STR);
        $stmt->bindValue(2, $fim,    PDO::PARAM_STR);
        $stmt->bindValue(3, $limit,  PDO::PARAM_INT);
        $stmt->bindValue(4, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contarDespesasNoPeriodo(string $inicio, string $fim): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM despesas WHERE data_despesa BETWEEN ? AND ?"
        );
        $stmt->execute([$inicio, $fim]);
        return (int) $stmt->fetchColumn();
    }

    public function pagamentosPorForma(string $inicio, string $fim): array
    {
        $stmt = $this->pdo->prepare("
            SELECT ap.forma_pagamento, SUM(ap.valor_recebido) AS total
            FROM atendimento_pagamentos ap
            JOIN atendimentos a ON ap.atendimento_id = a.id
            WHERE DATE(a.data_atendimento) BETWEEN ? AND ?
              AND a.status_pagamento = 'pago'
            GROUP BY ap.forma_pagamento
        ");
        $stmt->execute([$inicio, $fim]);
        return $stmt->fetchAll();
    }

    /**
     * Retorna o total recebido por cada participante no período,
     * agrupado por tipo_participacao e dentista.
     * Usada para discriminar na tela financeira quem recebeu o quê.
     */
    public function rateiosPorParticipante(string $inicio, string $fim): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                hr.tipo_participacao,
                hr.dentista_id,
                u.nome AS dentista_nome,
                hr.contexto_descricao,
                SUM(hr.valor_recebido) AS total_recebido
            FROM historico_rateio hr
            JOIN atendimento_procedimentos ap ON hr.atendimento_procedimento_id = ap.id
            JOIN atendimentos a ON ap.atendimento_id = a.id
            LEFT JOIN usuarios u ON hr.dentista_id = u.id
            WHERE a.data_atendimento BETWEEN ? AND ?
              AND a.status_pagamento = 'pago'
            GROUP BY hr.tipo_participacao, hr.dentista_id, u.nome, hr.contexto_descricao
            ORDER BY hr.tipo_participacao, u.nome
        ");
        $stmt->execute([$inicio . ' 00:00:00', $fim . ' 23:59:59']);
        return $stmt->fetchAll();
    }

    /**
     * Versão resumida: total por tipo (especialista, indicador, clinico_geral, clinica)
     * para os cards de resumo.
     */
    public function rateioTotalPorTipo(string $inicio, string $fim): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                hr.tipo_participacao,
                SUM(hr.valor_recebido) AS total
            FROM historico_rateio hr
            JOIN atendimento_procedimentos ap ON hr.atendimento_procedimento_id = ap.id
            JOIN atendimentos a ON ap.atendimento_id = a.id
            WHERE a.data_atendimento BETWEEN ? AND ?
              AND a.status_pagamento = 'pago'
            GROUP BY hr.tipo_participacao
        ");
        $stmt->execute([$inicio . ' 00:00:00', $fim . ' 23:59:59']);
        $rows = $stmt->fetchAll();
        $map  = [];
        foreach ($rows as $r) {
            $map[$r['tipo_participacao']] = (float)$r['total'];
        }
        return $map;
    }

    /**
     * Retorna o rateio por procedimento + dentista para a tabela detalhada do relatório
     */
    public function rateioDetalhadoPorAtendimento(string $inicio, string $fim, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                a.id AS atendimento_id,
                DATE(a.data_atendimento) AS data_at,
                pac.nome AS paciente_nome,
                p.nome   AS procedimento_nome,
                ap.valor_procedimento,
                MAX(CASE WHEN hr.tipo_participacao IN ('especialista','clinico_geral','indicador') THEN u.nome END) AS dentista_nome,
                MAX(CASE WHEN hr.tipo_participacao = 'especialista'   THEN hr.valor_recebido END) AS v_especialista,
                MAX(CASE WHEN hr.tipo_participacao = 'clinico_geral'  THEN hr.valor_recebido END) AS v_clinico_geral,
                MAX(CASE WHEN hr.tipo_participacao = 'indicador'      THEN hr.valor_recebido END) AS v_indicador,
                MAX(CASE WHEN hr.tipo_participacao = 'clinica'        THEN hr.valor_recebido END) AS v_clinica
            FROM atendimentos a
            JOIN pacientes pac ON a.paciente_id = pac.id
            JOIN atendimento_procedimentos ap ON ap.atendimento_id = a.id
            JOIN procedimentos p ON ap.procedimento_id = p.id
            LEFT JOIN historico_rateio hr ON hr.atendimento_procedimento_id = ap.id
            LEFT JOIN usuarios u ON hr.dentista_id = u.id AND hr.tipo_participacao IN ('especialista','clinico_geral','indicador')
            WHERE a.data_atendimento BETWEEN ? AND ?
              AND a.status_pagamento = 'pago'
              AND ap.status_execucao = 'concluido'
            GROUP BY a.id, ap.id, pac.nome, p.nome, ap.valor_procedimento, a.data_atendimento
            ORDER BY a.data_atendimento DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $inicio . ' 00:00:00', PDO::PARAM_STR);
        $stmt->bindValue(2, $fim    . ' 23:59:59', PDO::PARAM_STR);
        $stmt->bindValue(3, $limit,                PDO::PARAM_INT);
        $stmt->bindValue(4, $offset,               PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contarProcedimentosPagos(string $inicio, string $fim): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(ap.id)
            FROM atendimentos a
            JOIN atendimento_procedimentos ap ON ap.atendimento_id = a.id
            WHERE a.data_atendimento BETWEEN ? AND ?
              AND a.status_pagamento = 'pago'
              AND ap.status_execucao = 'concluido'
        ");
        $stmt->execute([$inicio . ' 00:00:00', $fim . ' 23:59:59']);
        return (int) $stmt->fetchColumn();
    }
}
