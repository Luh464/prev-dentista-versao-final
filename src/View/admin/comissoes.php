<div class="card">
    <h2><i class="fa-solid fa-hand-holding-dollar" style="margin-right:8px;color:var(--primary-color);"></i>Comissões dos Dentistas</h2>
    <p style="color:var(--secondary-color);">Configure regras de comissão. Regra <strong>individual</strong> tem prioridade sobre a <strong>geral</strong>. Mudanças só valem para novos atendimentos.</p>

    <?php if ($msg === 'sucesso'): ?>
        <p style="color:#28a745;background:#e8f5e9;padding:1rem;border-radius:6px;">Regra salva com sucesso!</p>
    <?php elseif ($msg === 'excluido'): ?>
        <p style="color:#856404;background:#fff3cd;padding:1rem;border-radius:6px;">Regra desativada.</p>
    <?php elseif ($erro === 'campos_invalidos'): ?>
        <p style="color:#dc3545;background:#fdecea;padding:1rem;border-radius:6px;">Preencha todos os campos.</p>
    <?php elseif ($erro === 'dentista_obrigatorio'): ?>
        <p style="color:#dc3545;background:#fdecea;padding:1rem;border-radius:6px;">Selecione um dentista para regra individual.</p>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="card" style="margin-top:1.5rem;">
        <h3>Nova Regra de Comissão</h3>
        <form action="<?= BASE_URL ?>?rota=admin.comissoes.salvar" method="POST">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-top:1rem;">
                <div class="form-group">
                    <label>Tipo de Regra</label>
                    <select name="tipo_regra" id="tipo_regra" required onchange="toggleDentista()">
                        <option value="geral">Geral (todos os dentistas)</option>
                        <option value="individual">Individual (por dentista)</option>
                    </select>
                </div>
                <div class="form-group" id="grupo_dentista" style="display:none;">
                    <label>Dentista</label>
                    <select name="dentista_id">
                        <option value="">Selecione</option>
                        <?php foreach ($dentistas as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Meta Mensal (R$)</label>
                    <input type="number" name="teto_meta" step="0.01" placeholder="Ex: 10000.00" required>
                </div>
                <div class="form-group">
                    <label>% Abaixo da Meta</label>
                    <input type="number" name="percentual_abaixo" step="0.01" placeholder="Ex: 20" required>
                </div>
                <div class="form-group">
                    <label>% Acima da Meta</label>
                    <input type="number" name="percentual_acima" step="0.01" placeholder="Ex: 30" required>
                </div>
            </div>
            <p style="font-size:.85rem;color:var(--secondary-color);margin:.5rem 0;">
                Exemplo: Meta R$ 10.000 → abaixo da meta recebe 20%, acima recebe 30%.
            </p>
            <button type="submit" class="btn btn-success">Salvar Regra</button>
        </form>
    </div>

    <!-- Cards -->
    <h3 style="margin-top:2rem;">Regras Ativas</h3>
    <?php if (empty($comissoes)): ?>
        <p style="color:var(--secondary-color);">Nenhuma regra cadastrada.</p>
    <?php else: ?>
        <div class="cm-grid">
            <?php foreach ($comissoes as $c): ?>
                <div class="cm-card" data-id="<?= $c['id'] ?>">
                    <div class="cm-card-header">
                        <div class="cm-card-icon <?= $c['tipo_regra']==='geral' ? 'cm-icon-geral' : 'cm-icon-individual' ?>">
                            <i class="fa-solid <?= $c['tipo_regra']==='geral' ? 'fa-users' : 'fa-user' ?>"></i>
                        </div>
                        <div class="cm-card-title">
                            <strong><?= $c['tipo_regra'] === 'geral' ? 'Geral (todos)' : htmlspecialchars($c['dentista_nome'] ?? '—') ?></strong>
                            <span class="cm-card-since">desde <?= date('d/m/Y', strtotime($c['criado_em'])) ?></span>
                        </div>
                        <div class="cm-card-actions">
                            <button type="button" class="cm-btn-edit" title="Editar regra"
                                    onclick="abrirEdicaoComissao(<?= $c['id'] ?>, '<?= $c['tipo_regra']==='geral' ? 'Geral (todos)' : htmlspecialchars($c['dentista_nome'] ?? '') ?>', <?= $c['teto_meta'] ?>, <?= $c['percentual_abaixo'] ?>, <?= $c['percentual_acima'] ?>)">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <a href="<?= BASE_URL ?>?rota=admin.comissoes.excluir&id=<?= $c['id'] ?>"
                               class="cm-btn-delete" title="Desativar regra"
                               onclick="return confirm('Desativar esta regra?')">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <div class="cm-card-body">
                        <div class="cm-stat">
                            <span class="cm-stat-label">Meta Mensal</span>
                            <span class="cm-stat-value cm-meta">R$ <?= number_format($c['teto_meta'], 2, ',', '.') ?></span>
                        </div>
                        <div class="cm-stat">
                            <span class="cm-stat-label">Abaixo da meta</span>
                            <span class="cm-stat-value cm-abaixo"><?= number_format($c['percentual_abaixo'], 2, ',', '.') ?>%</span>
                        </div>
                        <div class="cm-stat">
                            <span class="cm-stat-label">Acima da meta</span>
                            <span class="cm-stat-value cm-acima"><?= number_format($c['percentual_acima'], 2, ',', '.') ?>%</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ===== MODAL DE EDIÇÃO RÁPIDA DE COMISSÃO ===== -->
<div id="modalEditarComissao" class="cm-edit-overlay" onclick="if(event.target===this) fecharEdicaoComissao()">
    <div class="cm-edit-box">
        <button type="button" class="cm-edit-close" onclick="fecharEdicaoComissao()"><i class="fa-solid fa-xmark"></i></button>
        <h3><i class="fa-solid fa-pen" style="margin-right:8px;color:var(--primary-color);"></i>Editar Regra</h3>
        <p id="cm_edit_desc" style="color:var(--secondary-color);font-size:.9rem;margin-bottom:1rem;"></p>
        <div class="form-group">
            <label>Meta Mensal (R$)</label>
            <input type="number" id="cm_edit_teto" step="0.01" style="width:100%;">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="form-group">
                <label>% Abaixo</label>
                <input type="number" id="cm_edit_abaixo" step="0.01" style="width:100%;">
            </div>
            <div class="form-group">
                <label>% Acima</label>
                <input type="number" id="cm_edit_acima" step="0.01" style="width:100%;">
            </div>
        </div>
        <div id="cm_edit_msg" style="display:none;font-size:.85rem;margin:.5rem 0;"></div>
        <button type="button" class="btn btn-success" style="width:100%;margin-top:.5rem;" onclick="salvarEdicaoComissao()">
            <i class="fa-solid fa-floppy-disk" style="margin-right:6px;"></i>Salvar
        </button>
    </div>
</div>

<style>
.cm-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-top: 1rem;
}
@media (max-width: 700px) { .cm-grid { grid-template-columns: 1fr; } }

.cm-card {
    border: 1px solid #eee;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
}
.cm-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #eee;
}
.cm-card-icon {
    width: 40px; height: 40px; min-width:40px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
}
.cm-icon-geral { background: #eef4fb; color: var(--primary-color); }
.cm-icon-individual { background: #eafaf1; color: #27ae60; }
.cm-card-title { display: flex; flex-direction: column; flex: 1; min-width: 0; }
.cm-card-title strong { font-size: 0.95rem; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cm-card-since { font-size: 0.75rem; color: #999; }
.cm-card-actions { display: flex; gap: 6px; }
.cm-btn-edit, .cm-btn-delete {
    width: 30px; height: 30px; min-width: 30px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    border: none; cursor: pointer; font-size: 0.8rem;
    text-decoration: none; transition: all .15s;
}
.cm-btn-edit { background: #eef4fb; color: var(--primary-color); }
.cm-btn-edit:hover { background: var(--primary-color); color: #fff; }
.cm-btn-delete { background: #fdecea; color: #dc3545; }
.cm-btn-delete:hover { background: #dc3545; color: #fff; }

.cm-card-body {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 14px 16px;
}
.cm-stat { display: flex; flex-direction: column; gap: 2px; }
.cm-stat-label { font-size: 0.7rem; color: #999; text-transform: uppercase; letter-spacing: .03em; }
.cm-stat-value { font-size: 1rem; font-weight: 700; }
.cm-meta { color: #333; }
.cm-abaixo { color: #e67e22; }
.cm-acima { color: #27ae60; }

/* Modal */
.cm-edit-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(20,30,40,0.55);
    backdrop-filter: blur(3px);
    z-index: 9500;
    align-items: center; justify-content: center;
    padding: 20px;
}
.cm-edit-overlay.open { display: flex; }
.cm-edit-box {
    background: #fff; width: 100%; max-width: 380px;
    border-radius: 14px; padding: 24px; position: relative;
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
}
.cm-edit-close {
    position: absolute; top: 12px; right: 12px;
    width: 30px; height: 30px; border-radius: 50%;
    background: #f0f2f5; border: none; cursor: pointer; color: #555;
}
.cm-edit-close:hover { background: #e2e6ea; }
</style>

<script>
function toggleDentista() {
    const tipo = document.getElementById('tipo_regra').value;
    document.getElementById('grupo_dentista').style.display = tipo === 'individual' ? 'block' : 'none';
}

// ===== Edição rápida de comissão (modal) =====
var comissaoEditId = null;

function abrirEdicaoComissao(id, label, teto, abaixo, acima) {
    comissaoEditId = id;
    document.getElementById('cm_edit_desc').textContent = label;
    document.getElementById('cm_edit_teto').value   = teto;
    document.getElementById('cm_edit_abaixo').value = abaixo;
    document.getElementById('cm_edit_acima').value  = acima;
    document.getElementById('cm_edit_msg').style.display = 'none';
    document.getElementById('modalEditarComissao').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(function() { document.getElementById('cm_edit_teto').focus(); }, 100);
}

function fecharEdicaoComissao() {
    document.getElementById('modalEditarComissao').classList.remove('open');
    document.body.style.overflow = '';
    comissaoEditId = null;
}

function salvarEdicaoComissao() {
    var teto   = parseFloat((document.getElementById('cm_edit_teto').value   || '0').replace(',', '.'));
    var abaixo = parseFloat((document.getElementById('cm_edit_abaixo').value || '0').replace(',', '.'));
    var acima  = parseFloat((document.getElementById('cm_edit_acima').value  || '0').replace(',', '.'));
    var msg = document.getElementById('cm_edit_msg');

    if (!comissaoEditId || isNaN(teto) || teto <= 0 || isNaN(abaixo) || abaixo <= 0 || isNaN(acima) || acima <= 0) {
        msg.textContent = 'Preencha todos os campos com valores válidos.';
        msg.style.color = '#dc3545';
        msg.style.display = 'block';
        return;
    }

    var fd = new FormData();
    fd.append('id', comissaoEditId);
    fd.append('teto_meta', teto);
    fd.append('percentual_abaixo', abaixo);
    fd.append('percentual_acima', acima);

    fetch(window.__BASE_URL + '?rota=admin.comissoes.editar', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.sucesso) {
                var card = document.querySelector('.cm-card[data-id="' + comissaoEditId + '"]');
                if (card) {
                    card.querySelector('.cm-meta').textContent  = 'R$ ' + data.teto.toLocaleString('pt-BR', {minimumFractionDigits:2});
                    card.querySelector('.cm-abaixo').textContent = data.abaixo.toFixed(2).replace('.', ',') + '%';
                    card.querySelector('.cm-acima').textContent  = data.acima.toFixed(2).replace('.', ',') + '%';
                }
                fecharEdicaoComissao();
            } else {
                msg.textContent = data.erro || 'Erro ao salvar.';
                msg.style.color = '#dc3545';
                msg.style.display = 'block';
            }
        })
        .catch(function() {
            msg.textContent = 'Erro de conexão.';
            msg.style.color = '#dc3545';
            msg.style.display = 'block';
        });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fecharEdicaoComissao();
});
</script>
