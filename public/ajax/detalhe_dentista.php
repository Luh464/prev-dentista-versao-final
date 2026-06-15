<?php
// ajax/detalhe_dentista.php — detalhamento de desempenho de um dentista no período
header('Content-Type: application/json');

define('ROOT', dirname(__DIR__, 2));
require_once ROOT . '/config/database.php';
require_once ROOT . '/config/controle_acesso.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Sessão expirada.']);
    exit;
}

// autoload simples
spl_autoload_register(function (string $class): void {
    foreach ([ROOT . '/src/Model/', ROOT . '/src/Controller/'] as $dir) {
        $f = $dir . $class . '.php';
        if (file_exists($f)) { require_once $f; return; }
    }
});

$dentistaId = (int)($_GET['dentista_id'] ?? 0);
$inicio     = $_GET['inicio'] ?? date('Y-m-01');
$fim        = $_GET['fim']    ?? date('Y-m-t');

if (!$dentistaId) {
    echo json_encode(['erro' => 'Dentista não informado.']);
    exit;
}

// Apenas admin ou o próprio dentista podem ver
if (!is_admin() && (int)($_SESSION['usuario_id']) !== $dentistaId) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso não permitido.']);
    exit;
}

try {
    $atModel       = new AtendimentoModel($pdo);
    $comissaoModel = new ComissaoModel($pdo);
    $usuarioModel  = new UsuarioModel($pdo);

    $dentista = $usuarioModel->buscarPorId($dentistaId);
    if (!$dentista) {
        echo json_encode(['erro' => 'Dentista não encontrado.']);
        exit;
    }

    $resumo  = $atModel->resumoMetaDentista($dentistaId, $inicio . ' 00:00:00', $fim . ' 23:59:59');
    $detalhe = $atModel->detalhamentoAtendimentosDentista($dentistaId, $inicio . ' 00:00:00', $fim . ' 23:59:59');
    $serie   = $atModel->serieDiariaDentista($dentistaId, $inicio . ' 00:00:00', $fim . ' 23:59:59');

    // Buscar a regra de comissão aplicável (individual > geral) para mostrar a meta
    $regra = null;
    $stmt = $pdo->prepare("SELECT * FROM comissao WHERE tipo_regra='individual' AND dentista_id=? AND ativo=1 ORDER BY id DESC LIMIT 1");
    $stmt->execute([$dentistaId]);
    $regra = $stmt->fetch();
    if (!$regra) {
        $stmt = $pdo->prepare("SELECT * FROM comissao WHERE tipo_regra='geral' AND ativo=1 ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $regra = $stmt->fetch();
    }

    $faturamento = (float)$resumo['faturamento'];
    $totalGanho  = 0.0;
    foreach ($detalhe as $d) $totalGanho += (float)$d['valor_recebido'];

    $meta          = $regra ? (float)$regra['teto_meta'] : null;
    $bateuMeta     = $meta !== null ? $faturamento >= $meta : null;
    $percentualAtual = $regra
        ? ($bateuMeta ? (float)$regra['percentual_acima'] : (float)$regra['percentual_abaixo'])
        : null;
    $tipoRegra = $regra['tipo_regra'] ?? null;

    echo json_encode([
        'dentista_nome'    => $dentista['nome'],
        'periodo_inicio'   => $inicio,
        'periodo_fim'      => $fim,
        'faturamento'      => round($faturamento, 2),
        'total_ganho'      => round($totalGanho, 2),
        'total_clinica'    => round($faturamento - $totalGanho, 2),
        'meta'             => $meta,
        'bateu_meta'       => $bateuMeta,
        'percentual_atual' => $percentualAtual,
        'tipo_regra'       => $tipoRegra,
        'detalhe'          => $detalhe,
        'serie'            => $serie,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar detalhamento: ' . $e->getMessage()]);
}
