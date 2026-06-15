<?php
// src/Model/ComissaoModel.php

class ComissaoModel
{
    public function __construct(private PDO $pdo) {}

    public function listarAtivas(): array
    {
        return $this->pdo->query("
            SELECT c.*, u.nome AS dentista_nome
            FROM comissao c
            LEFT JOIN usuarios u ON c.dentista_id = u.id
            WHERE c.ativo = 1
            ORDER BY c.tipo_regra DESC, u.nome ASC
        ")->fetchAll();
    }

    public function buscarRegraGeral(): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM comissao
            WHERE tipo_regra = 'geral' AND ativo = 1
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch();
    }

    public function buscarRegraDentista(int $dentistaId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM comissao
            WHERE tipo_regra = 'individual' AND dentista_id = ? AND ativo = 1
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$dentistaId]);
        return $stmt->fetch();
    }

    public function inserirGeral(float $teto, float $percentAbaixo, float $percentAcima): void
    {
        // Desativar regra geral anterior
        $this->pdo->query("UPDATE comissao SET ativo = 0, data_fim = CURDATE() WHERE tipo_regra = 'geral' AND ativo = 1");

        $stmt = $this->pdo->prepare("
            INSERT INTO comissao (dentista_id, tipo_regra, teto_meta, percentual_abaixo, percentual_acima, ativo, data_inicio)
            VALUES (NULL, 'geral', ?, ?, ?, 1, CURDATE())
        ");
        $stmt->execute([$teto, $percentAbaixo, $percentAcima]);
    }

    public function inserirIndividual(int $dentistaId, float $teto, float $percentAbaixo, float $percentAcima): void
    {
        // Desativar regra individual anterior do mesmo dentista
        $stmt = $this->pdo->prepare("UPDATE comissao SET ativo = 0, data_fim = CURDATE() WHERE tipo_regra = 'individual' AND dentista_id = ? AND ativo = 1");
        $stmt->execute([$dentistaId]);

        $stmt = $this->pdo->prepare("
            INSERT INTO comissao (dentista_id, tipo_regra, teto_meta, percentual_abaixo, percentual_acima, ativo, data_inicio)
            VALUES (?, 'individual', ?, ?, ?, 1, CURDATE())
        ");
        $stmt->execute([$dentistaId, $teto, $percentAbaixo, $percentAcima]);
    }

    /** Edição rápida: altera meta e percentuais de uma regra existente */
    public function atualizarRegra(int $id, float $teto, float $percentAbaixo, float $percentAcima): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE comissao SET teto_meta = ?, percentual_abaixo = ?, percentual_acima = ?
            WHERE id = ?
        ");
        $stmt->execute([$teto, $percentAbaixo, $percentAcima, $id]);
    }

    public function desativar(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE comissao SET ativo = 0, data_fim = CURDATE() WHERE id = ?");
        $stmt->execute([$id]);
    }

    /** Calcula comissão de um dentista com base nas regras do banco */
    public function calcularComissao(int $dentistaId, float $valorProcedimento, float $fatMensal): float
    {
        // Prioridade: regra individual > regra geral
        $regra = $this->buscarRegraDentista($dentistaId) ?: $this->buscarRegraGeral();

        if (!$regra) {
            // Fallback: 20% fixo se não houver regra cadastrada
            return round($valorProcedimento * 0.20, 2);
        }

        $percent = ($fatMensal >= (float)$regra['teto_meta'])
            ? (float)$regra['percentual_acima']
            : (float)$regra['percentual_abaixo'];

        return round($valorProcedimento * ($percent / 100), 2);
    }
}
