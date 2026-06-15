<div class="card">
    <h2><i class="fa-solid fa-scale-balanced" style="margin-right:8px;color:var(--primary-color);"></i>Regras de Negócio</h2>
    <p style="color:var(--secondary-color);">
        Configure como o valor de cada procedimento especializado é dividido entre especialista, indicador e clínica. A soma deve ser 100%.<br>
        <i class="fa-solid fa-circle-info" style="color:var(--primary-color);margin-right:4px;"></i>
        Apenas procedimentos da categoria <strong>Especializado</strong> aparecem aqui. Procedimentos gerais (Consulta, Limpeza, etc.) usam as regras de Comissão.
    </p>

    <?php if ($msg === 'sucesso'): ?>
        <p style="color:#28a745;background:#e8f5e9;padding:1rem;border-radius:6px;">Regra de negócio salva com sucesso!</p>
    <?php elseif ($msg === 'excluido'): ?>
        <p style="color:#856404;background:#fff3cd;padding:1rem;border-radius:6px;">Regra de negócio removida.</p>
    <?php elseif ($erro === 'campos_invalidos'): ?>
        <p style="color:#dc3545;background:#fdecea;padding:1rem;border-radius:6px;">Preencha todos os campos.</p>
    <?php elseif ($erro === 'soma_invalida'): ?>
        <p style="color:#dc3545;background:#fdecea;padding:1rem;border-radius:6px;">A soma dos percentuais deve ser exatamente 100%.</p>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="card" style="margin-top:1.5rem;">
        <h3>Nova Regra de Negócio</h3>
        <form action="<?= BASE_URL ?>?rota=admin.rateio.salvar" method="POST">
            <div class="form-group" style="margin-top:1rem;">
                <label>Procedimento</label>
                <select name="procedimento_id" required>
                    <option value="">Selecione um procedimento</option>
                    <?php foreach ($procedimentos as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-top:1rem;">
                <div class="form-group">
                    <label>% Especialista</label>
                    <input type="number" name="percentual_especialista" id="p_esp" step="0.01" min="0" max="100"
                           placeholder="Ex: 50" oninput="calcResto()" required>
                </div>
                <div class="form-group">
                    <label>% Indicador (clínico geral)</label>
                    <input type="number" name="percentual_indicador" id="p_ind" step="0.01" min="0" max="100"
                           placeholder="Ex: 10" oninput="calcResto()">
                </div>
                <div class="form-group">
                    <label>% Clínica</label>
                    <input type="number" name="percentual_clinica" id="p_cli" step="0.01" min="0" max="100"
                           placeholder="Ex: 40" oninput="calcResto()">
                </div>
            </div>
            <p id="soma_info" style="font-size:.9rem;margin:.5rem 0;color:var(--secondary-color);">Soma: 0%</p>
            <p style="font-size:.85rem;color:var(--secondary-color);">
                Exemplo padrão: Especialista 50% + Indicador 10% + Clínica 40% = 100%.
            </p>
            <button type="submit" class="btn btn-success">Salvar Regra</button>
        </form>
    </div>

    <!-- Cards -->
    <h3 style="margin-top:2rem;">Regras Ativas</h3>
    <?php if (empty($regras)): ?>
        <p style="color:var(--secondary-color);">Nenhuma regra cadastrada.</p>
    <?php else: ?>
        <div class="rn-grid">
            <?php foreach ($regras as $r): ?>
                <div class="rn-card" data-id="<?= $r['id'] ?>">
                    <div class="rn-card-header">
                        <div class="rn-card-icon"><i class="fa-solid fa-tooth"></i></div>
                        <div class="rn-card-title">
                            <strong><?= htmlspecialchars($r['procedimento_nome']) ?></strong>
                            <span class="rn-card-since">desde <?= date('d/m/Y', strtotime($r['criado_em'])) ?></span>
                        </div>
                        <div class="rn-card-actions">
                            <button type="button" class="rn-btn-edit" title="Editar regra"
                                    onclick="abrirEdicaoRateio(<?= $r['id'] ?>, '<?= htmlspecialchars($r['procedimento_nome']) ?>', <?= $r['percentual_especialista'] ?>, <?= $r['percentual_indicador'] ?>, <?= $r['percentual_clinica'] ?>)">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <a href="<?= BASE_URL ?>?rota=admin.rateio.excluir&id=<?= $r['id'] ?>"
                               class="rn-btn-delete" title="Remover regra"
                               onclick="return confirm('Remover esta regra de negócio?')">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <div class="rn-card-body">
                        <div class="rn-stat">
                            <span class="rn-stat-label">Especialista</span>
                            <span class="rn-stat-value rn-esp"><?= number_format($r['percentual_especialista'], 0, ',', '.') ?>%</span>
                        </div>
                        <div class="rn-stat">
                            <span class="rn-stat-label">Indicador</span>
                            <span class="rn-stat-value rn-ind"><?= number_format($r['percentual_indicador'], 0, ',', '.') ?>%</span>
                        </div>
                        <div class="rn-stat">
                            <span class="rn-stat-label">Clínica</span>
                            <span class="rn-stat-value rn-cli"><?= number_format($r['percentual_clinica'], 0, ',', '.') ?>%</span>
                        </div>
                    </div>
                    <div class="rn-bar">
                        <span class="rn-bar-esp" style="width:<?= $r['percentual_especialista'] ?>%"></span>
                        <span class="rn-bar-ind" style="width:<?= $r['percentual_indicador'] ?>%"></span>
                        <span class="rn-bar-cli" style="width:<?= $r['percentual_clinica'] ?>%"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ===== MODAL DE EDIÇÃO RÁPIDA DE REGRA DE NEGÓCIO ===== -->
<div id="modalEditarRateio" class="rn-edit-overlay" onclick="if(event.target===this) fecharEdicaoRateio()">
    <div class="rn-edit-box">
        <button type="button" class="rn-edit-close" onclick="fecharEdicaoRateio()"><i class="fa-solid fa-xmark"></i></button>
        <h3><i class="fa-solid fa-pen" style="margin-right:8px;color:var(--primary-color);"></i>Editar Regra</h3>
        <p id="rn_edit_desc" style="color:var(--secondary-color);font-size:.9rem;margin-bottom:1rem;"></p>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
            <div class="form-group">
                <label>Especialista</label>
                <input type="number" id="rn_edit_esp" step="0.01" min="0" max="100" oninput="calcRestoEdicao()" style="width:100%;">
            </div>
            <div class="form-group">
                <label>Indicador</label>
                <input type="number" id="rn_edit_ind" step="0.01" min="0" max="100" oninput="calcRestoEdicao()" style="width:100%;">
            </div>
            <div class="form-group">
                <label>Clínica</label>
                <input type="number" id="rn_edit_cli" step="0.01" min="0" max="100" oninput="calcRestoEdicao()" style="width:100%;">
            </div>
        </div>
        <p id="rn_edit_soma" style="font-size:.85rem;margin:.4rem 0;color:var(--secondary-color);">Soma: 100%</p>
        <div id="rn_edit_msg" style="display:none;font-size:.85rem;margin-bottom:.5rem;"></div>
        <button type="button" class="btn btn-success" style="width:100%;" onclick="salvarEdicaoRateio()">
            <i class="fa-solid fa-floppy-disk" style="margin-right:6px;"></i>Salvar
        </button>
    </div>
</div>

<style>
.rn-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-top: 1rem;
}
@media (max-width: 700px) { .rn-grid { grid-template-columns: 1fr; } }

.rn-card { border: 1px solid #eee; border-radius: 12px; overflow: hidden; background: #fff; }
.rn-card-header {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #eee;
}
.rn-card-icon {
    width: 40px; height: 40px; min-width: 40px;
    border-radius: 50%; background: #f3eafc; color: #8e44ad;
    display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
}
.rn-card-title { display: flex; flex-direction: column; flex: 1; min-width: 0; }
.rn-card-title strong { font-size: 0.95rem; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rn-card-since { font-size: 0.75rem; color: #999; }
.rn-card-actions { display: flex; gap: 6px; }
.rn-btn-edit, .rn-btn-delete {
    width: 30px; height: 30px; min-width: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    border: none; cursor: pointer; font-size: 0.8rem; text-decoration: none; transition: all .15s;
}
.rn-btn-edit { background: #eef4fb; color: var(--primary-color); }
.rn-btn-edit:hover { background: var(--primary-color); color: #fff; }
.rn-btn-delete { background: #fdecea; color: #dc3545; }
.rn-btn-delete:hover { background: #dc3545; color: #fff; }

.rn-card-body { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; padding: 14px 16px 8px; }
.rn-stat { display: flex; flex-direction: column; gap: 2px; }
.rn-stat-label { font-size: 0.7rem; color: #999; text-transform: uppercase; letter-spacing: .03em; }
.rn-stat-value { font-size: 1rem; font-weight: 700; }
.rn-esp { color: #8e44ad; }
.rn-ind { color: #e67e22; }
.rn-cli { color: var(--primary-color); }

.rn-bar { display: flex; height: 8px; margin: 0 16px 14px; border-radius: 6px; overflow: hidden; }
.rn-bar-esp { background: #8e44ad; }
.rn-bar-ind { background: #e67e22; }
.rn-bar-cli { background: var(--primary-color); }

/* Modal */
.rn-edit-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(20,30,40,0.55); backdrop-filter: blur(3px);
    z-index: 9500; align-items: center; justify-content: center; padding: 20px;
}
.rn-edit-overlay.open { display: flex; }
.rn-edit-box {
    background: #fff; width: 100%; max-width: 400px;
    border-radius: 14px; padding: 24px; position: relative;
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
}
.rn-edit-close {
    position: absolute; top: 12px; right: 12px;
    width: 30px; height: 30px; border-radius: 50%;
    background: #f0f2f5; border: none; cursor: pointer; color: #555;
}
.rn-edit-close:hover { background: #e2e6ea; }
</style>

<script>
function calcResto() {
    const esp = parseFloat(document.getElementById('p_esp').value) || 0;
    const ind = parseFloat(document.getElementById('p_ind').value) || 0;
    const cli = parseFloat(document.getElementById('p_cli').value) || 0;
    const soma = esp + ind + cli;
    const info = document.getElementById('soma_info');
    info.textContent = 'Soma: ' + soma.toFixed(2) + '%';
    info.style.color = Math.abs(soma - 100) < 0.01 ? '#28a745' : '#dc3545';
}

// ===== Edição rápida de regra de negócio (modal) =====
var rateioEditId = null;

function abrirEdicaoRateio(id, nome, esp, ind, cli) {
    rateioEditId = id;
    document.getElementById('rn_edit_desc').textContent = nome;
    document.getElementById('rn_edit_esp').value = esp;
    document.getElementById('rn_edit_ind').value = ind;
    document.getElementById('rn_edit_cli').value = cli;
    document.getElementById('rn_edit_msg').style.display = 'none';
    calcRestoEdicao();
    document.getElementById('modalEditarRateio').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(function() { document.getElementById('rn_edit_esp').focus(); }, 100);
}

function fecharEdicaoRateio() {
    document.getElementById('modalEditarRateio').classList.remove('open');
    document.body.style.overflow = '';
    rateioEditId = null;
}

function calcRestoEdicao() {
    const esp = parseFloat(document.getElementById('rn_edit_esp').value) || 0;
    const ind = parseFloat(document.getElementById('rn_edit_ind').value) || 0;
    const cli = parseFloat(document.getElementById('rn_edit_cli').value) || 0;
    const soma = esp + ind + cli;
    const info = document.getElementById('rn_edit_soma');
    info.textContent = 'Soma: ' + soma.toFixed(2) + '%';
    info.style.color = Math.abs(soma - 100) < 0.01 ? '#28a745' : '#dc3545';
}

function salvarEdicaoRateio() {
    var esp = parseFloat((document.getElementById('rn_edit_esp').value || '0').replace(',', '.'));
    var ind = parseFloat((document.getElementById('rn_edit_ind').value || '0').replace(',', '.'));
    var cli = parseFloat((document.getElementById('rn_edit_cli').value || '0').replace(',', '.'));
    var msg = document.getElementById('rn_edit_msg');
    var soma = esp + ind + cli;

    if (!rateioEditId || esp <= 0 || Math.abs(soma - 100) > 0.01) {
        msg.textContent = 'A soma dos percentuais deve ser exatamente 100%.';
        msg.style.color = '#dc3545';
        msg.style.display = 'block';
        return;
    }

    var fd = new FormData();
    fd.append('id', rateioEditId);
    fd.append('percentual_especialista', esp);
    fd.append('percentual_indicador', ind);
    fd.append('percentual_clinica', cli);

    fetch(window.__BASE_URL + '?rota=admin.rateio.editar', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.sucesso) {
                var card = document.querySelector('.rn-card[data-id="' + rateioEditId + '"]');
                if (card) {
                    card.querySelector('.rn-esp').textContent = data.esp.toFixed(0) + '%';
                    card.querySelector('.rn-ind').textContent = data.ind.toFixed(0) + '%';
                    card.querySelector('.rn-cli').textContent = data.cli.toFixed(0) + '%';
                    card.querySelector('.rn-bar-esp').style.width = data.esp + '%';
                    card.querySelector('.rn-bar-ind').style.width = data.ind + '%';
                    card.querySelector('.rn-bar-cli').style.width = data.cli + '%';
                }
                fecharEdicaoRateio();
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
    if (e.key === 'Escape') fecharEdicaoRateio();
});
</script>
