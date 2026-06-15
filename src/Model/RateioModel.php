<?php
// src/Model/RateioModel.php

class RateioModel
{
    public function __construct(private PDO $pdo) {}

    // ─── Regras de Rateio ─────────────────────────────────────────────────────

    public function listarRegrasAtivas(): array
    {
        return $this->pdo->query("
            SELECT rr.*, p.nome AS procedimento_nome
            FROM regras_rateio rr
            JOIN procedimentos p ON rr.procedimento_id = p.id
            WHERE rr.ativo = 1
            ORDER BY p.nome ASC
        ")->fetchAll();
    }

    public function buscarRegraPorProcedimento(int $procedimentoId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM regras_rateio
            WHERE procedimento_id = ? AND ativo = 1
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$procedimentoId]);
        return $stmt->fetch();
    }

    public function inserirRegra(int $procedimentoId, float $pEspecialista, float $pIndicador, float $pClinica): void
    {
        // Desativar regra anterior do mesmo procedimento (lógica no PHP, não trigger —
        // MariaDB 10.4 não permite trigger fazer UPDATE na mesma tabela que disparou o INSERT)
        $stmt = $this->pdo->prepare("
            UPDATE regras_rateio
               SET ativo = 0, data_fim = CURDATE()
             WHERE procedimento_id = ? AND ativo = 1
        ");
        $stmt->execute([$procedimentoId]);

        $stmt = $this->pdo->prepare("
            INSERT INTO regras_rateio
                (procedimento_id, percentual_especialista, percentual_indicador, percentual_clinica, ativo, data_inicio)
            VALUES (?, ?, ?, ?, 1, CURDATE())
        ");
        $stmt->execute([$procedimentoId, $pEspecialista, $pIndicador, $pClinica]);
    }

    /** Edição rápida: altera os percentuais de uma regra existente */
    public function atualizarRegra(int $id, float $pEspecialista, float $pIndicador, float $pClinica): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE regras_rateio
               SET percentual_especialista = ?, percentual_indicador = ?, percentual_clinica = ?
             WHERE id = ?
        ");
        $stmt->execute([$pEspecialista, $pIndicador, $pClinica, $id]);
    }

    public function desativarRegra(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE regras_rateio SET ativo = 0, data_fim = CURDATE() WHERE id = ?");
        $stmt->execute([$id]);
    }

    // ─── Histórico de Rateio ──────────────────────────────────────────────────

    public function gravarRateio(
        int     $atendimentoProcedimentoId,
        ?int    $dentistaId,
        string  $tipoParticipacao,
        string  $descricao,
        float   $percentual,
        float   $valorProcedimento,
        float   $valorRecebido
    ): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO historico_rateio
                (atendimento_procedimento_id, dentista_id, tipo_participacao,
                 contexto_descricao, percentual_aplicado, valor_procedimento, valor_recebido)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $atendimentoProcedimentoId,
            $dentistaId,
            $tipoParticipacao,
            $descricao,
            $percentual,
            $valorProcedimento,
            $valorRecebido,
        ]);
    }

    /** Calcula e grava o rateio completo de um procedimento */
    public function calcularEGravarRateio(
        int    $apId,
        int    $procedimentoId,
        float  $valor,
        int    $dentistaExecutorId,
        ?int   $dentistaIndicadorId,
        string $categoriaProcedimento,
        int    $dentistaAtendimentoId,
        float  $fatMensal,
        ComissaoModel $comissaoModel
    ): array {
        $regra = $this->buscarRegraPorProcedimento($procedimentoId);

        // Buscar nomes dos dentistas para o contexto
        $nomeExecutor  = $this->buscarNomeDentista($dentistaExecutorId);
        $nomeIndicador = $dentistaIndicadorId ? $this->buscarNomeDentista($dentistaIndicadorId) : null;

        $resultado = [];

        if ($regra && $categoriaProcedimento === 'especializado') {
            // ── Rateio especializado: regra configurável ───────────────────
            $pEsp  = (float)$regra['percentual_especialista'] / 100;
            $pInd  = (float)$regra['percentual_indicador']    / 100;
            $pCli  = (float)$regra['percentual_clinica']      / 100;

            $vEsp  = round($valor * $pEsp, 2);
            $vInd  = round($valor * $pInd, 2);
            $vCli  = round($valor * $pCli, 2);

            // Especialista
            $this->gravarRateio($apId, $dentistaExecutorId, 'especialista',
                "$nomeExecutor – Especialista", $regra['percentual_especialista'], $valor, $vEsp);
            $resultado[] = ['tipo' => 'especialista', 'dentista' => $nomeExecutor, 'valor' => $vEsp];

            // Indicador (clínico geral que captou)
            if ($dentistaIndicadorId) {
                $this->gravarRateio($apId, $dentistaIndicadorId, 'indicador',
                    "$nomeIndicador – Indicação", $regra['percentual_indicador'], $valor, $vInd);
                $resultado[] = ['tipo' => 'indicador', 'dentista' => $nomeIndicador, 'valor' => $vInd];
            }

            // Clínica
            $this->gravarRateio($apId, null, 'clinica',
                "Clínica – Retenção", $regra['percentual_clinica'], $valor, $vCli);
            $resultado[] = ['tipo' => 'clinica', 'dentista' => 'Clínica', 'valor' => $vCli];

        } else {
            // ── Rateio clínico geral: usa tabela comissao ──────────────────
            $comissao = $comissaoModel->calcularComissao($dentistaAtendimentoId, $valor, $fatMensal);
            $vClinica = round($valor - $comissao, 2);

            $this->gravarRateio($apId, $dentistaAtendimentoId, 'clinico_geral',
                "$nomeExecutor – Clínico Geral", 0, $valor, $comissao);
            $resultado[] = ['tipo' => 'clinico_geral', 'dentista' => $nomeExecutor, 'valor' => $comissao];

            $this->gravarRateio($apId, null, 'clinica',
                "Clínica – Retenção", 0, $valor, $vClinica);
            $resultado[] = ['tipo' => 'clinica', 'dentista' => 'Clínica', 'valor' => $vClinica];
        }

        return $resultado;
    }

    private function buscarNomeDentista(int $id): string
    {
        $stmt = $this->pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return (string)($stmt->fetchColumn() ?: 'Desconhecido');
    }

    public function listarHistoricoPorAtendimento(int $atendimentoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT hr.*, u.nome AS dentista_nome, p.nome AS procedimento_nome
            FROM historico_rateio hr
            JOIN atendimento_procedimentos ap ON hr.atendimento_procedimento_id = ap.id
            JOIN procedimentos p ON ap.procedimento_id = p.id
            LEFT JOIN usuarios u ON hr.dentista_id = u.id
            WHERE ap.atendimento_id = ?
            ORDER BY hr.id ASC
        ");
        $stmt->execute([$atendimentoId]);
        return $stmt->fetchAll();
    }
}
