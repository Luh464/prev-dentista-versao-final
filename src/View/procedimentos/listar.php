<style>
.historico-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: #fff3cd; color: #856404; border: 1px solid #ffc107;
    border-radius: 12px; padding: 2px 8px; font-size: 11px; font-weight: 600; cursor: pointer;
}
.historico-badge:hover { background: #ffc107; color: #333; }
.historico-dropdown {
    display: none; position: absolute; z-index: 9999; min-width: 320px;
    background: #fff; border: 1px solid #ddd; border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,.15); padding: 0; overflow: hidden;
}
.historico-dropdown.aberto { display: block; }
.hist-header {
    background: #fff8e1; padding: 10px 14px; font-size: 12px; font-weight: 700;
    color: #856404; border-bottom: 1px solid #ffc107;
    display: flex; align-items: center; gap: 6px;
}
.hist-row { display: flex; align-items: center; gap: 8px; padding: 8px 14px; border-bottom: 1px solid #f5f5f5; font-size: 12px; }
.hist-row:last-child { border-bottom: none; }
.hist-de { color: #dc3545; text-decoration: line-through; }
.hist-para { color: #28a745; font-weight: 700; }
.hist-data { color: #999; font-size: 11px; margin-left: auto; }
.hist-aviso {
    background: #e8f4fd; border: 1px solid #bee5eb; border-radius: 6px;
    padding: 10px 14px; font-size: 12px; color: #0c5460; display: flex; align-items: flex-start; gap: 8px;
}
.hist-aviso i { color: #17a2b8; margin-top: 1px; flex-shrink: 0; }

/* Modal de edição */
.modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 10000;
    align-items: center; justify-content: center;
}
.modal-overlay.aberto { display: flex; }
.modal-box {
    background: #fff; border-radius: 12px; padding: 28px; width: 100%; max-width: 480px;
    box-shadow: 0 12px 40px rgba(0,0,0,.25); position: relative;
}
.modal-box h3 { margin: 0 0 18px; font-size: 1.1rem; color: #333; }
.modal-close { position: absolute; top: 14px; right: 18px; font-size: 22px; cursor: pointer; color: #aaa; background: none; border: none; }
.modal-close:hover { color: #333; }
.aviso-preco {
    background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px;
    padding: 10px 14px; margin-bottom: 14px; font-size: 13px; color: #856404;
    display: flex; align-items: flex-start; gap: 8px;
}
.aviso-preco i { margin-top: 2px; flex-shrink: 0; }
</style>

<div class="card">
    <h2>Gestão de Procedimentos</h2>

    <?php if (isset($_GET['erro']) && $_GET['erro'] === 'conflito'): ?>
        <p class="error">Não é possível excluir o procedimento, pois ele já está vinculado a atendimentos.</p>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'sucesso'): ?>
        <p style="color:green;background:#e8f5e9;padding:1rem;border-radius:6px;">Procedimento criado com sucesso!</p>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'atualizado'): ?>
        <p style="color:#005b96;background:#e3f2fd;padding:1rem;border-radius:6px;">
            <i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>
            Procedimento atualizado. O novo valor valerá apenas para <strong>atendimentos futuros</strong>. Histórico e relatórios passados permanecem intactos.
        </p>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'excluido'): ?>
        <p style="color:#666;background:#f5f5f5;padding:1rem;border-radius:6px;">Procedimento removido.</p>
    <?php endif; ?>

    <!-- Formulário para Adicionar -->
    <div class="card" style="margin-top:2rem;">
        <h3>Novo Procedimento</h3>
        <form action="<?= BASE_URL ?>?rota=procedimentos.salvar" method="POST">
            <div class="form-group">
                <label for="nome">Nome do Procedimento</label>
                <input type="text" name="nome" id="nome" required>
            </div>
            <div class="form-group">
                <label for="categoria">Categoria</label>
                <select name="categoria" id="categoria" required>
                    <option value="geral">Geral</option>
                    <option value="especializado">Especializado</option>
                    <option value="protese">Prótese</option>
                </select>
            </div>
            <div class="form-group">
                <label for="tipo">Arquivo</label>
                <select name="tipo" id="tipo" required>
                    <option value="0">Sem Arquivo</option>
                    <option value="1">Com Arquivo</option>
                </select>
            </div>
            <div class="form-group">
                <label for="valor_base">Valor Base (R$)</label>
                <input type="number" step="0.01" name="valor_base" id="valor_base">
            </div>
            <button type="submit" class="btn btn-success">Salvar Procedimento</button>
        </form>
    </div>

    <!-- Tabela de Procedimentos -->
    <h3 style="margin-top:2rem;">Procedimentos Cadastrados</h3>
    <div class="hist-aviso" style="margin-bottom:1rem;">
        <i class="fa-solid fa-circle-info"></i>
        <div>
            <strong>Política de atualização de preços:</strong> ao editar o valor base de um procedimento,
            os atendimentos e relatórios <em>já registrados</em> não são alterados — cada procedimento
            gravado na base armazena o valor cobrado naquele momento. Apenas atendimentos <em>futuros</em>
            usarão o novo valor.
        </div>
    </div>

    <table class="mobile-card-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Valor Atual</th>
                <th>Histórico</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($procedimentos) > 0): ?>
                <?php foreach ($procedimentos as $procedimento): ?>
                    <tr>
                        <td data-label="Nome"><?= htmlspecialchars($procedimento['nome']) ?></td>
                        <td data-label="Categoria"><?= ucfirst($procedimento['categoria']) ?></td>
                        <td data-label="Valor Atual" style="font-weight:600;">
                            R$ <?= number_format((float)$procedimento['valor_base'], 2, ',', '.') ?>
                        </td>
                        <td data-label="Histórico">
                            <?php if (!empty($procedimento['historico_precos'])): ?>
                                <div style="position:relative; display:inline-block;">
                                    <span class="historico-badge"
                                          onclick="toggleHistorico(this, <?= (int)$procedimento['id'] ?>)">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                        <?= count($procedimento['historico_precos']) ?> alteração(ões)
                                    </span>
                                    <div class="historico-dropdown" id="hist-<?= (int)$procedimento['id'] ?>">
                                        <div class="hist-header">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                            Histórico de Preços — <?= htmlspecialchars($procedimento['nome']) ?>
                                        </div>
                                        <?php foreach ($procedimento['historico_precos'] as $h): ?>
                                            <div class="hist-row">
                                                <span class="hist-de">R$ <?= number_format((float)$h['valor_anterior'], 2, ',', '.') ?></span>
                                                <i class="fa-solid fa-arrow-right" style="color:#aaa;font-size:10px;"></i>
                                                <span class="hist-para">R$ <?= number_format((float)$h['valor_novo'], 2, ',', '.') ?></span>
                                                <span class="hist-data"><?= date('d/m/Y H:i', strtotime($h['data_alteracao'])) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                        <div style="padding:10px 14px; font-size:11px; color:#999; border-top:1px solid #f0f0f0;">
                                            <i class="fa-solid fa-shield-halved" style="color:#28a745;margin-right:4px;"></i>
                                            Atendimentos passados usaram o valor vigente em cada data.
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span style="color:#ccc;font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Ações" style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button class="btn btn-primary" style="font-size:13px;padding:5px 12px;"
                                    onclick="abrirEdicao(<?= (int)$procedimento['id'] ?>, '<?= htmlspecialchars(addslashes($procedimento['nome'])) ?>', '<?= $procedimento['categoria'] ?>', '<?= number_format((float)$procedimento['valor_base'], 2, '.', '') ?>', '<?= (int)($procedimento['tipo'] ?? 0) ?>')">
                                <i class="fa-solid fa-pencil"></i> Editar
                            </button>
                            <a href="<?= BASE_URL ?>?rota=procedimentos.excluir&id=<?= $procedimento['id'] ?>"
                               class="btn btn-danger" style="font-size:13px;padding:5px 12px;"
                               onclick="return confirm('Tem certeza que deseja remover este procedimento?');">
                                <i class="fa-solid fa-trash"></i> Remover
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">Nenhum procedimento registrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal de edição -->
<div class="modal-overlay" id="modal-edicao">
    <div class="modal-box">
        <button class="modal-close" onclick="fecharEdicao()" aria-label="Fechar">&times;</button>
        <h3><i class="fa-solid fa-pencil" style="margin-right:8px;color:#005b96;"></i>Editar Procedimento</h3>

        <div class="aviso-preco" id="aviso-mudanca-preco" style="display:none;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Atenção:</strong> você está alterando o valor base. Esta mudança só afeta
                <strong>atendimentos futuros</strong>. Os dados históricos e relatórios passados
                permanecerão com os valores originais registrados em cada atendimento.
            </div>
        </div>

        <form action="<?= BASE_URL ?>?rota=procedimentos.salvar" method="POST" id="form-edicao">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group">
                <label for="edit-nome">Nome do Procedimento</label>
                <input type="text" name="nome" id="edit-nome" required>
            </div>
            <div class="form-group">
                <label for="edit-categoria">Categoria</label>
                <select name="categoria" id="edit-categoria" required>
                    <option value="geral">Geral</option>
                    <option value="especializado">Especializado</option>
                    <option value="protese">Prótese</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit-tipo">Arquivo</label>
                <select name="tipo" id="edit-tipo">
                    <option value="0">Sem Arquivo</option>
                    <option value="1">Com Arquivo</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit-valor">
                    Valor Base (R$)
                    <span id="valor-atual-label" style="font-size:12px;color:#888;margin-left:8px;"></span>
                </label>
                <input type="number" step="0.01" name="valor_base" id="edit-valor"
                       oninput="verificarMudancaPreco(this)">
            </div>
            <div style="display:flex;gap:10px;margin-top:18px;">
                <button type="submit" class="btn btn-success" style="flex:1;">
                    <i class="fa-solid fa-check"></i> Salvar Alterações
                </button>
                <button type="button" class="btn btn-secondary" onclick="fecharEdicao()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
var _valorOriginalEdit = 0;

function toggleHistorico(badge, id) {
    var drop = document.getElementById('hist-' + id);
    if (!drop) return;
    // fechar todos outros
    document.querySelectorAll('.historico-dropdown.aberto').forEach(function(d) {
        if (d !== drop) d.classList.remove('aberto');
    });
    drop.classList.toggle('aberto');
    // posicionar
    var rect = badge.getBoundingClientRect();
    drop.style.top = '110%';
    drop.style.left = '0';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.historico-badge') && !e.target.closest('.historico-dropdown')) {
        document.querySelectorAll('.historico-dropdown.aberto').forEach(function(d) {
            d.classList.remove('aberto');
        });
    }
});

function abrirEdicao(id, nome, categoria, valor, tipo) {
    document.getElementById('edit-id').value      = id;
    document.getElementById('edit-nome').value    = nome;
    document.getElementById('edit-categoria').value = categoria;
    document.getElementById('edit-tipo').value    = tipo;
    document.getElementById('edit-valor').value   = valor;
    document.getElementById('valor-atual-label').textContent = '(atual: R$ ' + parseFloat(valor).toFixed(2).replace('.', ',') + ')';
    _valorOriginalEdit = parseFloat(valor);
    document.getElementById('aviso-mudanca-preco').style.display = 'none';
    document.getElementById('modal-edicao').classList.add('aberto');
}

function fecharEdicao() {
    document.getElementById('modal-edicao').classList.remove('aberto');
}

function verificarMudancaPreco(input) {
    var novo = parseFloat(input.value) || 0;
    var aviso = document.getElementById('aviso-mudanca-preco');
    aviso.style.display = (novo !== _valorOriginalEdit && novo > 0) ? 'flex' : 'none';
}

document.getElementById('modal-edicao').addEventListener('click', function(e) {
    if (e.target === this) fecharEdicao();
});
</script>
