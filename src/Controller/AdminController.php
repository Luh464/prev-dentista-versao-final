<?php
// src/Controller/AdminController.php
// Painel Administrativo: Taxas de Cartão, Comissões e Regras de Rateio

class AdminController
{
    private TaxaCartaoModel  $taxaModel;
    private ComissaoModel    $comissaoModel;
    private RateioModel      $rateioModel;

    public function __construct(private PDO $pdo)
    {
        $this->taxaModel     = new TaxaCartaoModel($pdo);
        $this->comissaoModel = new ComissaoModel($pdo);
        $this->rateioModel   = new RateioModel($pdo);
    }

    // ─── TAXAS DE CARTÃO ──────────────────────────────────────────────────────

    public function taxas(): void
    {
        $taxas    = $this->taxaModel->listarAtivas();
        $bandeiras = $this->taxaModel->bandeirasDisponiveis();
        $msg  = $_GET['msg']  ?? null;
        $erro = $_GET['erro'] ?? null;

        require ROOT . '/src/View/layout/header.php';
        require ROOT . '/src/View/admin/taxas.php';
        require ROOT . '/src/View/layout/footer.php';
    }

    public function salvarTaxa(): void
    {
        $bandeira   = trim($_POST['bandeira']       ?? '');
        $tipo       = $_POST['tipo']                ?? '';
        $modo       = $_POST['modo']                ?? 'unica';
        $percentual = (float)str_replace(',', '.', $_POST['percentual_taxa'] ?? '0');

        if (empty($bandeira) || !in_array($tipo, ['debito','credito']) || $percentual <= 0) {
            header("Location: " . BASE_URL . "?rota=admin.taxas&erro=campos_invalidos");
            exit;
        }

        if ($modo === 'intervalo') {
            // Aplicar a mesma taxa para um intervalo de parcelas
            $de  = max(1, (int)($_POST['parcelas_de']  ?? 1));
            $ate = min(12, (int)($_POST['parcelas_ate'] ?? 1));

            if ($de > $ate) {
                header("Location: " . BASE_URL . "?rota=admin.taxas&erro=intervalo_invalido");
                exit;
            }

            for ($p = $de; $p <= $ate; $p++) {
                $this->taxaModel->inserir($bandeira, $tipo, $p, $percentual);
            }
        } else {
            // Parcela específica
            $parcelas = max(1, min(12, (int)($_POST['parcelas'] ?? 1)));
            $this->taxaModel->inserir($bandeira, $tipo, $parcelas, $percentual);
        }

        header("Location: " . BASE_URL . "?rota=admin.taxas&msg=sucesso");
        exit;
    }

    public function excluirTaxa(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $this->taxaModel->desativar($id);
        header("Location: " . BASE_URL . "?rota=admin.taxas&msg=excluido");
        exit;
    }

    /** Edição rápida via AJAX: altera só o percentual de uma taxa */
    public function editarTaxa(): void
    {
        header('Content-Type: application/json');
        $id         = (int)($_POST['id'] ?? 0);
        $percentual = (float)str_replace(',', '.', $_POST['percentual_taxa'] ?? '0');

        if (!$id || $percentual <= 0) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos.']);
            exit;
        }

        $this->taxaModel->atualizarPercentual($id, $percentual);
        echo json_encode(['sucesso' => true, 'percentual' => $percentual]);
        exit;
    }

    // ─── COMISSÕES ────────────────────────────────────────────────────────────

    public function comissoes(): void
    {
        $comissoes = $this->comissaoModel->listarAtivas();
        $dentistas = (new UsuarioModel($this->pdo))->listarDentistas();
        $msg  = $_GET['msg']  ?? null;
        $erro = $_GET['erro'] ?? null;

        require ROOT . '/src/View/layout/header.php';
        require ROOT . '/src/View/admin/comissoes.php';
        require ROOT . '/src/View/layout/footer.php';
    }

    public function salvarComissao(): void
    {
        $tipo         = $_POST['tipo_regra']         ?? '';
        $dentistaId   = !empty($_POST['dentista_id']) ? (int)$_POST['dentista_id'] : null;
        $teto         = (float)str_replace(',', '.', $_POST['teto_meta']          ?? '0');
        $abaixo       = (float)str_replace(',', '.', $_POST['percentual_abaixo']  ?? '0');
        $acima        = (float)str_replace(',', '.', $_POST['percentual_acima']   ?? '0');

        if (!in_array($tipo, ['geral','individual']) || $teto <= 0 || $abaixo <= 0 || $acima <= 0) {
            header("Location: " . BASE_URL . "?rota=admin.comissoes&erro=campos_invalidos");
            exit;
        }

        if ($tipo === 'geral') {
            $this->comissaoModel->inserirGeral($teto, $abaixo, $acima);
        } else {
            if (!$dentistaId) {
                header("Location: " . BASE_URL . "?rota=admin.comissoes&erro=dentista_obrigatorio");
                exit;
            }
            $this->comissaoModel->inserirIndividual($dentistaId, $teto, $abaixo, $acima);
        }

        header("Location: " . BASE_URL . "?rota=admin.comissoes&msg=sucesso");
        exit;
    }

    public function excluirComissao(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $this->comissaoModel->desativar($id);
        header("Location: " . BASE_URL . "?rota=admin.comissoes&msg=excluido");
        exit;
    }

    /** Edição rápida via AJAX: altera meta e percentuais de uma regra de comissão */
    public function editarComissao(): void
    {
        header('Content-Type: application/json');
        $id     = (int)($_POST['id'] ?? 0);
        $teto   = (float)str_replace(',', '.', $_POST['teto_meta']         ?? '0');
        $abaixo = (float)str_replace(',', '.', $_POST['percentual_abaixo'] ?? '0');
        $acima  = (float)str_replace(',', '.', $_POST['percentual_acima']  ?? '0');

        if (!$id || $teto <= 0 || $abaixo <= 0 || $acima <= 0) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos.']);
            exit;
        }

        $this->comissaoModel->atualizarRegra($id, $teto, $abaixo, $acima);
        echo json_encode(['sucesso' => true, 'teto' => $teto, 'abaixo' => $abaixo, 'acima' => $acima]);
        exit;
    }

    // ─── REGRAS DE RATEIO ─────────────────────────────────────────────────────

    public function rateio(): void
    {
        $regras       = $this->rateioModel->listarRegrasAtivas();
        $procedimentos = (new ProcedimentoModel($this->pdo))->listarEspecializados();
        $msg  = $_GET['msg']  ?? null;
        $erro = $_GET['erro'] ?? null;

        require ROOT . '/src/View/layout/header.php';
        require ROOT . '/src/View/admin/rateio.php';
        require ROOT . '/src/View/layout/footer.php';
    }

    public function salvarRateio(): void
    {
        $procedimentoId = (int)($_POST['procedimento_id']       ?? 0);
        $pEsp           = (float)str_replace(',', '.', $_POST['percentual_especialista'] ?? '0');
        $pInd           = (float)str_replace(',', '.', $_POST['percentual_indicador']    ?? '0');
        $pCli           = (float)str_replace(',', '.', $_POST['percentual_clinica']      ?? '0');

        if (!$procedimentoId || $pEsp <= 0) {
            header("Location: " . BASE_URL . "?rota=admin.rateio&erro=campos_invalidos");
            exit;
        }

        $total = $pEsp + $pInd + $pCli;
        if (abs($total - 100) > 0.01) {
            header("Location: " . BASE_URL . "?rota=admin.rateio&erro=soma_invalida");
            exit;
        }

        $this->rateioModel->inserirRegra($procedimentoId, $pEsp, $pInd, $pCli);
        header("Location: " . BASE_URL . "?rota=admin.rateio&msg=sucesso");
        exit;
    }

    public function excluirRateio(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $this->rateioModel->desativarRegra($id);
        header("Location: " . BASE_URL . "?rota=admin.rateio&msg=excluido");
        exit;
    }

    /** Edição rápida via AJAX: altera os percentuais de uma regra de negócio */
    public function editarRateio(): void
    {
        header('Content-Type: application/json');
        $id   = (int)($_POST['id'] ?? 0);
        $pEsp = (float)str_replace(',', '.', $_POST['percentual_especialista'] ?? '0');
        $pInd = (float)str_replace(',', '.', $_POST['percentual_indicador']    ?? '0');
        $pCli = (float)str_replace(',', '.', $_POST['percentual_clinica']      ?? '0');

        $soma = $pEsp + $pInd + $pCli;

        if (!$id || $pEsp <= 0 || abs($soma - 100) > 0.01) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'erro' => 'A soma dos percentuais deve ser 100%.']);
            exit;
        }

        $this->rateioModel->atualizarRegra($id, $pEsp, $pInd, $pCli);
        echo json_encode(['sucesso' => true, 'esp' => $pEsp, 'ind' => $pInd, 'cli' => $pCli]);
        exit;
    }
}
