<?php
// src/Controller/ProcedimentoController.php

class ProcedimentoController
{
    private ProcedimentoModel $model;

    public function __construct(private PDO $pdo)
    {
        $this->model = new ProcedimentoModel($pdo);
    }

    public function listar(): void
    {
        // Carrega procedimentos já com histórico de preços embutido
        $procedimentos = $this->model->listarComHistoricoPrecos();
        $mensagem      = $_GET['msg']  ?? null;
        $erro          = $_GET['erro'] ?? null;
        $msg           = $mensagem;

        require ROOT . '/src/View/layout/header.php';
        require ROOT . '/src/View/procedimentos/listar.php';
        require ROOT . '/src/View/layout/footer.php';
    }

    public function salvar(): void
    {
        $id        = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $nome      = trim($_POST['nome']      ?? '');
        $categoria = $_POST['categoria']      ?? '';
        $valorBase = !empty($_POST['valor_base']) ? (float)str_replace(',', '.', $_POST['valor_base']) : null;
        $tipo      = (int)($_POST['tipo']     ?? 0);

        if (empty($nome) || empty($categoria)) {
            header("Location: " . BASE_URL . "?rota=procedimentos&erro=campos_obrigatorios");
            exit;
        }

        if ($id) {
            // ATUALIZAÇÃO — preserva histórico automaticamente via Model::atualizar()
            $this->model->atualizar($id, $nome, $categoria, $valorBase, $tipo);
            header("Location: " . BASE_URL . "?rota=procedimentos&msg=atualizado");
        } else {
            // INSERÇÃO
            $this->model->inserir($nome, $categoria, $valorBase, $tipo);
            header("Location: " . BASE_URL . "?rota=procedimentos&msg=sucesso");
        }
        exit;
    }

    public function excluir(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if ($this->model->estaEmUso($id)) {
            header("Location: " . BASE_URL . "?rota=procedimentos&erro=conflito");
            exit;
        }

        $this->model->excluir($id);
        header("Location: " . BASE_URL . "?rota=procedimentos&msg=excluido");
        exit;
    }
}
