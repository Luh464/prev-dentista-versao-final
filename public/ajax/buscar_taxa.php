<?php
// ajax/buscar_taxa.php
header('Content-Type: application/json');

define('ROOT', dirname(__DIR__, 2)); // ajax -> public -> trabalhoihc
require_once ROOT . '/config/database.php';

$bandeira = trim($_GET['bandeira'] ?? '');
$tipo     = trim($_GET['tipo']     ?? '');
$parcelas = max(1, (int)($_GET['parcelas'] ?? 1));

if (empty($bandeira) || !in_array($tipo, ['debito','credito'])) {
    echo json_encode(['taxa' => 0]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT percentual_taxa FROM taxa_cartao
        WHERE bandeira = ? AND tipo = ? AND parcelas = ? AND ativo = 1
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$bandeira, $tipo, $parcelas]);
    $taxa = $stmt->fetchColumn();

    if ($taxa === false) {
        $stmt = $pdo->prepare("
            SELECT percentual_taxa FROM taxa_cartao
            WHERE tipo = ? AND parcelas = ? AND ativo = 1
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$tipo, $parcelas]);
        $taxa = $stmt->fetchColumn();
    }

    echo json_encode(['taxa' => $taxa !== false ? (float)$taxa : 0]);
} catch (Exception $e) {
    echo json_encode(['taxa' => 0, 'erro' => $e->getMessage()]);
}
