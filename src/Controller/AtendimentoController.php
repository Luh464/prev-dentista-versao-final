<?php
// src/Controller/AtendimentoController.php

class AtendimentoController
{
    private AtendimentoModel  $model;
    private PacienteModel     $pacienteModel;
    private ProcedimentoModel $procModel;

    public function __construct(private PDO $pdo)
    {
        $this->model         = new AtendimentoModel($pdo);
        $this->pacienteModel = new PacienteModel($pdo);
        $this->procModel     = new ProcedimentoModel($pdo);
    }

    /** Exibe o formulário de novo atendimento com odontograma */
    public function novo(): void
    {
        $dentistas     = (new UsuarioModel($this->pdo))->listarDentistas();
        $procedimentos = $this->procModel->listarParaAtendimento();

        require ROOT . '/src/View/layout/header.php';
        require ROOT . '/src/View/atendimentos/novo.php';
        require ROOT . '/src/View/layout/footer.php';
    }

    /** Salva o atendimento (POST do formulário do odontograma) */
    public function salvar(): void
    {
        ini_set('display_errors', 0);
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $sendError = function (string $msg, int $code = 400): void {
            http_response_code($code);
            echo json_encode(['sucesso' => false, 'erro' => $msg]);
            exit;
        };

        date_default_timezone_set('America/Sao_Paulo');
        $this->pdo->beginTransaction();

        try {
            if (!empty($_POST['procedimentos_a_deletar'])) {
                $this->model->deletarProcedimentos($_POST['procedimentos_a_deletar']);
            }

            $pacienteId   = !empty($_POST['paciente_id'])   ? (int)trim($_POST['paciente_id']) : null;
            $pacienteNome = trim($_POST['paciente_nome']    ?? '');
            $idDentista   = $_POST['id_dentista']           ?? null;
            $procsInput   = $_POST['procedimentos']         ?? [];

            if ((!$pacienteId && empty($pacienteNome)) || empty($idDentista) || empty($procsInput['id'] ?? [])) {
                $sendError("Paciente, dentista e pelo menos um procedimento são obrigatórios.");
            }

            if ($pacienteId) {
                $pacienteNome = $this->pacienteModel->buscarNomePorId($pacienteId);
                if (!$pacienteNome) {
                    $sendError("Paciente ID $pacienteId não encontrado no banco.");
                }
            } else {
                $existente = $this->pdo->prepare("SELECT id FROM pacientes WHERE nome = ? LIMIT 1");
                $existente->execute([$pacienteNome]);
                $row = $existente->fetch();
                if ($row) {
                    $pacienteId = $row['id'];
                } else {
                    $pacienteId = $this->pacienteModel->inserirSimplesOuObterPorNome($pacienteNome);
                }
            }

            // Upload de arquivo
            $urlArquivo = null;
            if (isset($_FILES['raio_x_file']) && $_FILES['raio_x_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = ROOT . '/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $finfo   = new finfo(FILEINFO_MIME_TYPE);
                $mime    = $finfo->file($_FILES['raio_x_file']['tmp_name']);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'application/pdf' => 'pdf'];

                if (!array_key_exists($mime, $allowed)) {
                    throw new Exception("Formato de arquivo não permitido.");
                }

                $ext      = $allowed[$mime];
                $safe     = preg_replace('/[^a-zA-Z0-9-_]/', '', str_replace(' ', '_', $pacienteNome));
                $fileName = uniqid() . '_' . $safe . '.' . $ext;
                if (!move_uploaded_file($_FILES['raio_x_file']['tmp_name'], $uploadDir . $fileName)) {
                    throw new Exception("Falha ao mover o arquivo.");
                }
                $urlArquivo = 'uploads/' . $fileName;
            }

            // Calcular totais
            $ids          = $procsInput['id']              ?? [];
            $valores      = $procsInput['valor']           ?? [];
            $qtds         = $procsInput['quantidade']      ?? [];
            $locais       = $procsInput['local']           ?? [];
            $naturezas    = $procsInput['natureza']        ?? [];
            $custosAux    = $procsInput['custo_auxiliar']  ?? [];
            $descricoes   = $procsInput['descricao']       ?? [];
            $statusExec   = $procsInput['status_execucao'] ?? [];

            $valorTotal       = 0;
            $totalCustoAux    = 0;
            $procsParaInserir = [];

            foreach ($ids as $i => $procId) {
                $valor  = (float) str_replace(',', '.', $valores[$i] ?? 0);
                $qtd    = max(1, (int)($qtds[$i] ?? 1));
                $custo  = (float) str_replace(',', '.', $custosAux[$i] ?? 0);
                $status = $statusExec[$i] ?? 'pendente';

                $valorTotal    += $valor;
                $totalCustoAux += $custo;

                $procsParaInserir[] = [
                    ':procedimento_id'    => (int)$procId,
                    ':quantidade'         => $qtd,
                    ':valor_procedimento' => $valor,
                    ':local'              => $locais[$i]    ?? null,
                    ':natureza'           => $naturezas[$i] ?? 'consulta',
                    ':custo_auxiliar'     => $custo,
                    ':descricao'          => $descricoes[$i] ?? null,
                    ':status_execucao'    => $status,
                    ':url_arquivo'        => null,
                ];
            }

            // Inserir atendimento (sem comissao_dentista — calculada pelo novo módulo)
            $atendimentoId = $this->model->inserir([
                ':paciente_id'           => (int)$pacienteId,
                ':dentista_executor_id'  => (int)$idDentista,
                ':data_atendimento'      => date('Y-m-d H:i:s'),
                ':status_pagamento'      => 'pendente',
                ':valor_total'           => $valorTotal,
                ':custo_auxiliar'        => $totalCustoAux,
                ':taxa_cartao'           => 0,
                ':valor_liquido_clinica' => 0,
                ':url_arquivo'           => $urlArquivo,
            ]);

            foreach ($procsParaInserir as $proc) {
                $proc[':atendimento_id'] = $atendimentoId;
                $this->model->inserirProcedimento($proc);
            }

            $this->pdo->commit();
            echo json_encode([
                'sucesso'        => true,
                'atendimento_id' => $atendimentoId,
                'mensagem'       => 'Atendimento lançado com sucesso!',
                'redirectUrl'    => BASE_URL . '?rota=painel',
            ]);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $sendError($e->getMessage());
        }
        exit;
    }

    /** Tela de confirmação / pagamento */
    public function confirmarPagamento(): void
    {
        $paciente_id   = $_GET['paciente_id'] ?? null;
        $paciente_nome = '';
        $atendimentos  = [];
        $valor_total   = 0;
        $ultimo_atendimento_id = null;

        if ($paciente_id) {
            $paciente_nome = $this->pacienteModel->buscarNomePorId((int)$paciente_id);
            $ultimo_atendimento_id = $this->model->ultimoPendenteDoPaciente((int)$paciente_id);

            if ($ultimo_atendimento_id) {
                $atendimentos = $this->model->procedimentosFinalizadosDoAtendimento((int)$ultimo_atendimento_id);
                foreach ($atendimentos as $at) {
                    $valor_total += $at['valor_procedimento'];
                }
            }
        }

        // Lista de todos os atendimentos pendentes — exibida quando nenhum paciente foi buscado
        $pendentes = empty($paciente_id) ? $this->model->listarTodosPendentes() : [];

        require ROOT . '/src/View/layout/header.php';
        require ROOT . '/src/View/atendimentos/odontograma.php';
        require ROOT . '/src/View/layout/footer.php';
    }

    /** Processa o POST do pagamento */
    public function processarPagamento(): void
    {
        header('Content-Type: application/json');
        if (ob_get_level()) ob_clean();

        $atendimentoId = $_POST['atendimento_id'] ?? null;
        $pacienteId    = $_POST['paciente_id']    ?? null;
        $pagamentos    = $_POST['pagamentos']     ?? [];

        if (!$atendimentoId) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'erro' => 'ID do atendimento não informado.']);
            exit;
        }
        if (!$pacienteId) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'erro' => 'ID do paciente não informado.']);
            exit;
        }
        if (empty($pagamentos['valor'])) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'erro' => 'Nenhum pagamento informado.']);
            exit;
        }

        try {
            $this->pdo->beginTransaction();

            $atendimento = $this->model->buscarPorId((int)$atendimentoId);
            if (!$atendimento) throw new Exception("Atendimento não encontrado.");

            // Proteção contra duplo lançamento: se já estiver pago, não reprocessa
            if ($atendimento['status_pagamento'] === 'pago') {
                throw new Exception("Este atendimento já está pago. Pagamento não lançado novamente.");
            }

            $totalTaxaCartao = 0.0;
            $totalPago       = 0.0;
            $int_id          = (int)$atendimentoId;

            $parcelasMap = [];
            if (!empty($pagamentos['parcelas'])) {
                foreach ($pagamentos['parcelas'] as $idx => $parc) {
                    $parcelasMap[$idx] = (int)$parc;
                }
            }

            $bandeirasArr = $pagamentos['bandeira'] ?? [];

            foreach ($pagamentos['valor'] as $i => $valor) {
                $valorStr  = str_replace([',', ' ', 'R$'], ['.', '', ''], trim($valor));
                $valorPago = (float)$valorStr;
                if ($valorPago <= 0) continue;

                $forma    = $pagamentos['forma'][$i] ?? 'dinheiro';
                $bandeira = $bandeirasArr[$i] ?? 'Outras';
                $parcelas = 1;
                if ($forma === 'credito') {
                    $parcelas = !empty($parcelasMap) ? (int)(reset($parcelasMap)) : 1;
                    array_shift($parcelasMap);
                }
                $parcelas = max(1, min(12, $parcelas));

                $this->model->inserirPagamento($int_id, $forma, $valorPago, $parcelas);

                $taxaPerc  = $this->buscarTaxaCartao($forma, $parcelas, $bandeira);
                $valorTaxa = round($valorPago * $taxaPerc, 2);
                $totalTaxaCartao += $valorTaxa;
                $totalPago       += $valorPago;
            }

            $valorEsperado = (float)$atendimento['valor_total'];
            if ($valorEsperado > 0 && abs($totalPago - $valorEsperado) > 0.05) {
                throw new Exception(
                    "Soma dos pagamentos (R$ " . number_format($totalPago, 2, ',', '.') .
                    ") não corresponde ao total (R$ " . number_format($valorEsperado, 2, ',', '.') . ")."
                );
            }

            $custoAux     = (float)$atendimento['custo_auxiliar'];
            $valorLiquido = $totalPago - $totalTaxaCartao - $custoAux;

            $this->model->atualizarPagamento($int_id, $totalTaxaCartao, $custoAux, $valorLiquido);
            $this->model->marcarProcedimentosComoConcluido($int_id);

            // ── Gravar histórico de rateio por procedimento ───────────────
            $rateioModel   = new RateioModel($this->pdo);
            $comissaoModel = new ComissaoModel($this->pdo);
            $inicioMes     = date('Y-m-01 00:00:00');
            $fimMes        = date('Y-m-t 23:59:59');
            $fatMensal     = $this->model->faturamentoBrutoMensal($inicioMes, $fimMes);

            $procs = $this->model->procedimentosFinalizadosDoAtendimento($int_id);
            foreach ($procs as $proc) {
                $apFull = $this->model->buscarProcedimentoCompleto((int)$proc['id']);
                if (!$apFull) continue;

                $rateioModel->calcularEGravarRateio(
                    (int)$proc['id'],
                    (int)$apFull['procedimento_id'],
                    (float)$proc['valor_procedimento'],
                    (int)$atendimento['dentista_executor_id'],
                    !empty($apFull['dentista_indicador_id']) ? (int)$apFull['dentista_indicador_id'] : null,
                    $apFull['categoria'],
                    (int)$atendimento['dentista_executor_id'],
                    $fatMensal,
                    $comissaoModel
                );
            }

            $this->pdo->commit();
            echo json_encode(['sucesso' => true, 'mensagem' => 'Pagamento confirmado com sucesso!', 'redirectUrl' => BASE_URL . '?rota=painel']);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    }

    /** Busca taxa de cartão no banco — retorna percentual decimal (ex: 0.03 para 3%) */
    private function buscarTaxaCartao(string $forma, int $parcelas, string $bandeira = 'Outras'): float
    {
        if ($forma === 'dinheiro' || $forma === 'pix') return 0.0;

        $tipo = ($forma === 'debito') ? 'debito' : 'credito';

        // Tentar com bandeira específica primeiro
        $stmt = $this->pdo->prepare("
            SELECT percentual_taxa FROM taxa_cartao
            WHERE bandeira = ? AND tipo = ? AND parcelas = ? AND ativo = 1
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$bandeira, $tipo, $parcelas]);
        $taxa = $stmt->fetchColumn();

        // Fallback: buscar qualquer taxa ativa para o tipo/parcelas
        if ($taxa === false) {
            $stmt = $this->pdo->prepare("
                SELECT percentual_taxa FROM taxa_cartao
                WHERE tipo = ? AND parcelas = ? AND ativo = 1
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$tipo, $parcelas]);
            $taxa = $stmt->fetchColumn();
        }

        return $taxa !== false ? (float)$taxa / 100 : 0.0;
    }

    /** AJAX – histórico do paciente */
    public function historicoAjax(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['erro' => 'Sessão expirada.']);
            exit;
        }

        $pacienteId = (int)($_GET['paciente_id'] ?? 0);
        if (!$pacienteId) {
            echo json_encode(['erro' => 'ID do paciente não fornecido.']);
            exit;
        }

        $todos      = $this->model->historicoCompletoPaciente($pacienteId);
        $realizados = array_filter($todos, fn($p) => $p['status_execucao'] === 'concluido');
        $pendentes  = array_filter($todos, fn($p) => $p['status_execucao'] !== 'concluido');

        echo json_encode([
            'realizados' => array_values($realizados),
            'pendentes'  => array_values($pendentes),
        ]);
        exit;
    }

    /** AJAX – procedimentos pendentes do paciente */
    public function procedimentosPendentes(): void
    {
        header('Content-Type: application/json');
        $pacienteId = (int)($_GET['paciente_id'] ?? 0);
        echo json_encode($this->model->procedimentosPendentesDoPaciente($pacienteId));
        exit;
    }

    /** AJAX – verificar se existe pagamento pendente */
    public function verificarPagamentoPendente(): void
    {
        header('Content-Type: application/json');
        $pacienteId = (int)($_GET['paciente_id'] ?? 0);
        echo json_encode(['pendente' => $this->model->temPagamentoPendente($pacienteId)]);
        exit;
    }

    /** AJAX – remover procedimento pendente do odontograma */
    public function removerProcedimento(): void
    {
        header('Content-Type: application/json');

        $id = (int)($_POST['id_procedimento'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID não fornecido.']);
            exit;
        }

        $proc = $this->model->buscarProcedimentoParaRemocao($id);
        if (!$proc) {
            echo json_encode(['status' => 'error', 'message' => 'Procedimento não encontrado.']);
            exit;
        }

        if ($proc['status_execucao'] !== 'pendente' || $proc['status_pagamento'] === 'pago') {
            echo json_encode(['status' => 'error', 'message' => 'Não é possível remover um procedimento já pago ou finalizado.']);
            exit;
        }

        try {
            $this->pdo->beginTransaction();
            if (!empty($proc['url_arquivo'])) {
                $abs = realpath(ROOT . '/' . $proc['url_arquivo']);
                if ($abs && file_exists($abs)) @unlink($abs);
            }
            $this->model->excluirProcedimentoUnitario($id);
            $this->pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Procedimento removido.']);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Erro interno.']);
        }
        exit;
    }

    /** POST – remover anexo de um procedimento */
    public function removerAnexo(): void
    {
        header('Content-Type: application/json');
        $id = (int)($_POST['id_procedimento'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID não fornecido.']);
            exit;
        }

        $this->pdo->beginTransaction();
        try {
            $caminho = $this->model->removerAnexoProcedimento($id);
            if (!$caminho) {
                $this->pdo->rollBack();
                echo json_encode(['status' => 'success', 'message' => 'Nenhum anexo para remover.']);
                exit;
            }
            $abs = realpath(ROOT . '/' . $caminho);
            if ($abs && file_exists($abs) && !@unlink($abs)) {
                throw new Exception("Falha ao apagar arquivo físico.");
            }
            $this->pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Arquivo removido.']);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Erro interno.']);
        }
        exit;
    }

    /** POST – upload de arquivo para procedimento existente */
    public function salvarArquivo(): void
    {
        $apId        = $_POST['atendimento_procedimento_id'] ?? null;
        $pacNome     = $_POST['paciente_nome_redirect']      ?? '';
        $redirectUrl = BASE_URL . "?rota=relatorios.paciente&paciente_nome=" . urlencode($pacNome);

        if (!$apId || !isset($_FILES['arquivo_procedimento']) || $_FILES['arquivo_procedimento']['error'] !== UPLOAD_ERR_OK) {
            header("Location: $redirectUrl&erro=" . urlencode("Dados inválidos para o upload."));
            exit;
        }

        $uploadDir = ROOT . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $finfo   = new finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($_FILES['arquivo_procedimento']['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];

        if (!array_key_exists($mime, $allowed)) {
            header("Location: $redirectUrl&erro=" . urlencode("Formato não permitido."));
            exit;
        }

        $ext      = $allowed[$mime];
        $fileName = 'proc_' . $apId . '_' . uniqid() . '.' . $ext;

        if (!move_uploaded_file($_FILES['arquivo_procedimento']['tmp_name'], $uploadDir . $fileName)) {
            header("Location: $redirectUrl&erro=" . urlencode("Falha ao mover arquivo."));
            exit;
        }

        $this->model->atualizarUrlArquivoProcedimento((int)$apId, 'uploads/' . $fileName);
        header("Location: $redirectUrl&msg=arquivo_salvo");
        exit;
    }

    /** AJAX – detalhes de um atendimento para modal */
    public function detalhes(): void
    {
        header('Content-Type: application/json');
        $id = (int)($_GET['id'] ?? 0);
        $at = $this->model->buscarPorId($id);
        echo json_encode($at ?: ['erro' => 'Não encontrado']);
        exit;
    }
}
