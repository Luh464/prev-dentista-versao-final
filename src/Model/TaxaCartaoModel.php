<?php
// src/Model/TaxaCartaoModel.php

class TaxaCartaoModel
{
    public function __construct(private PDO $pdo) {}

    public function listarAtivas(): array
    {
        return $this->pdo->query("
            SELECT * FROM taxa_cartao
            WHERE ativo = 1
            ORDER BY bandeira, tipo, parcelas ASC
        ")->fetchAll();
    }

    public function listarTodas(): array
    {
        return $this->pdo->query("
            SELECT * FROM taxa_cartao
            ORDER BY bandeira, tipo, parcelas, id DESC
        ")->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM taxa_cartao WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function inserir(string $bandeira, string $tipo, int $parcelas, float $percentual): void
    {
        // Desativar taxa anterior com mesma bandeira/tipo/parcelas (lógica no PHP, não trigger)
        $stmt = $this->pdo->prepare("
            UPDATE taxa_cartao
               SET ativo = 0, data_fim = CURDATE()
             WHERE bandeira = ? AND tipo = ? AND parcelas = ? AND ativo = 1
        ");
        $stmt->execute([$bandeira, $tipo, $parcelas]);

        // Inserir nova taxa
        $stmt = $this->pdo->prepare("
            INSERT INTO taxa_cartao (bandeira, tipo, parcelas, percentual_taxa, ativo)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([$bandeira, $tipo, $parcelas, $percentual]);
    }

    /** Edição rápida: altera apenas o percentual de uma taxa existente */
    public function atualizarPercentual(int $id, float $percentual): void
    {
        $stmt = $this->pdo->prepare("UPDATE taxa_cartao SET percentual_taxa = ? WHERE id = ?");
        $stmt->execute([$percentual, $id]);
    }

    public function desativar(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE taxa_cartao SET ativo = 0, data_fim = CURDATE() WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function buscarTaxa(string $bandeira, string $tipo, int $parcelas): float
    {
        $stmt = $this->pdo->prepare("
            SELECT percentual_taxa FROM taxa_cartao
            WHERE bandeira = ? AND tipo = ? AND parcelas = ? AND ativo = 1
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$bandeira, $tipo, $parcelas]);
        return (float) ($stmt->fetchColumn() ?? 0);
    }

    public function bandeirasDisponiveis(): array
    {
        return ['Visa', 'Mastercard', 'Elo', 'Hipercard', 'American Express', 'Outras'];
    }
}
