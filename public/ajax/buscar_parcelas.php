<?php
// ajax/buscar_parcelas.php — retorna as parcelas disponíveis para bandeira/tipo
header('Content-Type: application/json');

define('ROOT', dirname(__DIR__, 2));
require_once ROOT . '/config/database.php';

$bandeira = trim($_GET['bandeira'] ?? '');
$tipo     = trim($_GET['tipo']     ?? '');

if (empty($bandeira) || !in_array($tipo, ['debito','credito'])) {
    // Sem restrição cadastrada: retornar 1 a 12
    $parcelas = range(1, 12);
    echo json_encode(['parcelas' => $parcelas, 'limitado' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT parcelas, percentual_taxa
        FROM taxa_cartao
        WHERE bandeira = ? AND tipo = ? AND ativo = 1
        ORDER BY parcelas ASC
    ");
    $stmt->execute([$bandeira, $tipo]);
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        // Sem taxa cadastrada para esta bandeira: sem restrição
        echo json_encode(['parcelas' => range(1, 12), 'limitado' => false]);
    } else {
        $parcelas = array_column($rows, 'parcelas');
        echo json_encode(['parcelas' => $parcelas, 'limitado' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['parcelas' => range(1, 12), 'limitado' => false]);
}
