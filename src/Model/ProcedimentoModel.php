<?php
// src/Model/ProcedimentoModel.php

class ProcedimentoModel
{
    public function __construct(private PDO $pdo) {}

    public function listarTodos(): array
    {
        return $this->pdo->query("SELECT * FROM procedimentos ORDER BY nome ASC")->fetchAll();
    }

    /** Apenas procedimentos especializados — únicos elegíveis para Regras de Negócio (rateio) */
    public function listarEspecializados(): array
    {
        return $this->pdo->query(
            "SELECT * FROM procedimentos WHERE categoria = 'especializado' ORDER BY nome ASC"
        )->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM procedimentos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function inserir(string $nome, string $categoria, ?float $valorBase, ?string $tipo): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO procedimentos (nome, categoria, valor_base, tipo) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$nome, $categoria, $valorBase, $tipo]);
    }

    public function atualizar(int $id, string $nome, string $categoria, ?float $valorBase, ?string $tipo): void
    {
        // Registrar histórico de preço antes de atualizar
        $atual = $this->buscarPorId($id);
        if ($atual && (float)$atual['valor_base'] !== $valorBase) {
            $this->registrarHistoricoPreco($id, (float)$atual['valor_base'], (float)$valorBase);
        }

        $stmt = $this->pdo->prepare(
            "UPDATE procedimentos SET nome = ?, categoria = ?, valor_base = ?, tipo = ? WHERE id = ?"
        );
        $stmt->execute([$nome, $categoria, $valorBase, $tipo, $id]);
    }

    private function registrarHistoricoPreco(int $procedimentoId, float $valorAnterior, float $valorNovo): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO historico_precos (procedimento_id, valor_anterior, valor_novo) VALUES (?, ?, ?)"
        );
        $stmt->execute([$procedimentoId, $valorAnterior, $valorNovo]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM procedimentos WHERE id = ?");
        $stmt->execute([$id]);
    }

    /** Retorna o histórico de alterações de preço de um procedimento, mais recente primeiro */
    public function historicoPrecos(int $procedimentoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT valor_anterior, valor_novo, data_alteracao
            FROM historico_precos
            WHERE procedimento_id = ?
            ORDER BY data_alteracao DESC
            LIMIT 10
        ");
        $stmt->execute([$procedimentoId]);
        return $stmt->fetchAll();
    }

    /** Retorna todos os procedimentos com histórico de preços agrupado para exibição */
    public function listarComHistoricoPrecos(): array
    {
        // Busca todos os procedimentos
        $procs = $this->listarTodos();
        // Para cada um, adiciona o histórico
        foreach ($procs as &$p) {
            $p['historico_precos'] = $this->historicoPrecos((int)$p['id']);
        }
        return $procs;
    }

    public function estaEmUso(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM atendimento_procedimentos WHERE procedimento_id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }

    public function listarParaAtendimento(): array
    {
        return $this->pdo->query(
            "SELECT id, nome, categoria, valor_base, tipo FROM procedimentos ORDER BY nome ASC"
        )->fetchAll();
    }

    public function relatorio(string $dataInicio, string $dataFim): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.nome AS procedimento_nome,
                p.categoria,
                SUM(ap.quantidade) AS quantidade_executada,
                SUM(ap.valor_procedimento) AS valor_bruto_total,
                -- Valor pago ao executor (especialista ou clinico_geral)
                COALESCE(SUM(
                    CASE WHEN hr.tipo_participacao IN ('especialista','clinico_geral')
                         THEN hr.valor_recebido ELSE 0 END
                ), 0) AS valor_dentista,
                -- Valor retido pela clínica
                COALESCE(SUM(
                    CASE WHEN hr.tipo_participacao = 'clinica'
                         THEN hr.valor_recebido ELSE 0 END
                ), 0) AS valor_clinica,
                -- Comissão de indicação
                COALESCE(SUM(
                    CASE WHEN hr.tipo_participacao = 'indicador'
                         THEN hr.valor_recebido ELSE 0 END
                ), 0) AS valor_indicacao
            FROM atendimento_procedimentos ap
            JOIN atendimentos a  ON a.id  = ap.atendimento_id
            JOIN procedimentos p ON p.id  = ap.procedimento_id
            LEFT JOIN historico_rateio hr ON hr.atendimento_procedimento_id = ap.id
            WHERE a.data_atendimento BETWEEN ? AND ?
              AND ap.status_execucao = 'concluido'
              AND a.status_pagamento  = 'pago'
            GROUP BY p.id, p.nome, p.categoria
            ORDER BY quantidade_executada DESC, valor_bruto_total DESC
        ");
        $stmt->execute([$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
        return $stmt->fetchAll();
    }

    public function totalProcedimentosExecutados(string $dataInicio, string $dataFim): int
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(ap.quantidade)
            FROM atendimento_procedimentos ap
            JOIN atendimentos a ON a.id = ap.atendimento_id
            WHERE a.data_atendimento BETWEEN ? AND ?
              AND ap.status_execucao = 'concluido'
              AND a.status_pagamento  = 'pago'
        ");
        $stmt->execute([$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
        return (int) ($stmt->fetchColumn() ?? 0);
    }
}
