<?php
// ajax/preview_rateio.php — calcula preview do split financeiro antes de confirmar pagamento
header('Content-Type: application/json');

define('ROOT', dirname(__DIR__, 2));
require_once ROOT . '/config/database.php';
require_once ROOT . '/config/session.php';

// Deve estar logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['erro' => 'Sessão expirada.']);
    exit;
}

$atendimentoId = (int)($_GET['atendimento_id'] ?? 0);
if (!$atendimentoId) {
    echo json_encode(['erro' => 'ID do atendimento inválido.']);
    exit;
}

try {
    // Buscar atendimento
    $stmt = $pdo->prepare("SELECT * FROM atendimentos WHERE id = ?");
    $stmt->execute([$atendimentoId]);
    $atendimento = $stmt->fetch();
    if (!$atendimento) {
        echo json_encode(['erro' => 'Atendimento não encontrado.']);
        exit;
    }

    // Buscar procedimentos com categoria e dentista indicador
    $stmt = $pdo->prepare("
        SELECT ap.id, ap.procedimento_id, ap.valor_procedimento,
               ap.dentista_indicador_id, p.categoria, p.nome AS proc_nome,
               u.nome AS executor_nome,
               ui.nome AS indicador_nome
        FROM atendimento_procedimentos ap
        JOIN procedimentos p ON ap.procedimento_id = p.id
        LEFT JOIN usuarios u  ON ap.dentista_executor_id  = u.id
        LEFT JOIN usuarios ui ON ap.dentista_indicador_id = ui.id
        WHERE ap.atendimento_id = ?
          AND ap.status_execucao IN ('pendente','concluido')
    ");
    $stmt->execute([$atendimentoId]);
    $procs = $stmt->fetchAll();

    // Buscar regras de rateio ativas
    $stmt = $pdo->prepare("
        SELECT procedimento_id, percentual_especialista, percentual_indicador, percentual_clinica
        FROM regras_rateio
        WHERE ativo = 1
    ");
    $stmt->execute();
    $regrasMap = [];
    foreach ($stmt->fetchAll() as $r) {
        $regrasMap[(int)$r['procedimento_id']] = $r;
    }

    // Buscar regras de comissão
    $stmtCom = $pdo->prepare("SELECT * FROM comissao WHERE ativo = 1 ORDER BY tipo_regra ASC");
    $stmtCom->execute();
    $comissoes = $stmtCom->fetchAll();

    // Faturamento bruto do mês atual (para meta de comissão)
    $inicioMes = date('Y-m-01 00:00:00');
    $fimMes    = date('Y-m-t 23:59:59');
    $stmtFat = $pdo->prepare("
        SELECT COALESCE(SUM(ap.valor_procedimento), 0)
        FROM atendimento_procedimentos ap
        JOIN atendimentos a ON ap.atendimento_id = a.id
        WHERE a.data_atendimento BETWEEN ? AND ?
    ");
    $stmtFat->execute([$inicioMes, $fimMes]);
    $fatMensal = (float)$stmtFat->fetchColumn();

    $dentistaExecutorId = (int)$atendimento['dentista_executor_id'];

    // Encontrar comissão aplicável ao executor
    function buscarComissao(array $comissoes, int $dentistaId): ?array {
        // Prioridade: individual > geral
        foreach ($comissoes as $c) {
            if ($c['tipo_regra'] === 'individual' && (int)$c['dentista_id'] === $dentistaId) return $c;
        }
        foreach ($comissoes as $c) {
            if ($c['tipo_regra'] === 'geral') return $c;
        }
        return null;
    }

    $comissaoRegra = buscarComissao($comissoes, $dentistaExecutorId);

    // Buscar nome do executor
    $stmtNome = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmtNome->execute([$dentistaExecutorId]);
    $nomeExecutor = (string)($stmtNome->fetchColumn() ?: 'Dentista');

    // Calcular split por procedimento
    $splits = [];
    $totais = ['dentista' => 0.0, 'indicador' => 0.0, 'clinica' => 0.0, 'clinico_geral' => 0.0];

    foreach ($procs as $proc) {
        $valor     = (float)$proc['valor_procedimento'];
        $procId    = (int)$proc['procedimento_id'];
        $categoria = $proc['categoria'];
        $regra     = $regrasMap[$procId] ?? null;
        $split     = ['proc_nome' => $proc['proc_nome'], 'valor' => $valor];

        if ($regra && $categoria === 'especializado') {
            $pEsp = (float)$regra['percentual_especialista'] / 100;
            $pInd = (float)$regra['percentual_indicador']    / 100;
            $pCli = (float)$regra['percentual_clinica']      / 100;

            $split['tipo']              = 'especializado';
            $split['especialista']      = round($valor * $pEsp, 2);
            $split['pct_especialista']  = (float)$regra['percentual_especialista'];
            $split['indicador']         = $proc['dentista_indicador_id'] ? round($valor * $pInd, 2) : 0.0;
            $split['pct_indicador']     = $proc['dentista_indicador_id'] ? (float)$regra['percentual_indicador'] : 0.0;
            $split['clinica']           = round($valor * $pCli, 2);
            $split['pct_clinica']       = (float)$regra['percentual_clinica'];
            $split['executor_nome']     = $proc['executor_nome'] ?? $nomeExecutor;
            $split['indicador_nome']    = $proc['indicador_nome'] ?? null;

            $totais['dentista']  += $split['especialista'];
            $totais['indicador'] += $split['indicador'];
            $totais['clinica']   += $split['clinica'];

        } else {
            // Clínico geral: comissão baseada em meta
            $percent = 20.0; // fallback
            if ($comissaoRegra) {
                $percent = ($fatMensal >= (float)$comissaoRegra['teto_meta'])
                    ? (float)$comissaoRegra['percentual_acima']
                    : (float)$comissaoRegra['percentual_abaixo'];
            }
            $vDentista = round($valor * $percent / 100, 2);
            $vClinica  = round($valor - $vDentista, 2);

            $split['tipo']           = 'geral';
            $split['clinico_geral']  = $vDentista;
            $split['pct_dentista']   = $percent;
            $split['clinica']        = $vClinica;
            $split['executor_nome']  = $nomeExecutor;

            $totais['clinico_geral'] += $vDentista;
            $totais['clinica']       += $vClinica;
        }

        $splits[] = $split;
    }

    $totalDentistas = $totais['dentista'] + $totais['clinico_geral'];

    echo json_encode([
        'splits'            => $splits,
        'totais'            => $totais,
        'total_dentistas'   => $totalDentistas,
        'fat_mensal'        => $fatMensal,
        'comissao_regra'    => $comissaoRegra,
        'executor_nome'     => $nomeExecutor,
    ]);

} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
