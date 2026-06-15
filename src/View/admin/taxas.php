<?php
// Mapa de logos por bandeira
$logosBandeiras = [
    'Visa'             => 'https://raw.githubusercontent.com/aaronfagan/svg-credit-card-payment-icons/main/flat/visa.svg',
    'Mastercard'       => 'https://raw.githubusercontent.com/aaronfagan/svg-credit-card-payment-icons/main/flat/mastercard.svg',
    'Elo'              => 'https://raw.githubusercontent.com/aaronfagan/svg-credit-card-payment-icons/main/flat/elo.svg',
    'Hipercard'        => 'https://raw.githubusercontent.com/aaronfagan/svg-credit-card-payment-icons/main/flat/hipercard.svg',
    'American Express' => 'https://raw.githubusercontent.com/aaronfagan/svg-credit-card-payment-icons/main/flat/amex.svg',
    'Outras'           => null,
];
?>
<div class="card">
    <h2><i class="fa-solid fa-credit-card" style="margin-right:8px;color:var(--primary-color);"></i>Taxas de Cartão</h2>
    <p style="color: var(--secondary-color);">
        Configure as taxas por bandeira, tipo e número de parcelas.<br>
        <i class="fa-solid fa-circle-info" style="color:var(--primary-color);margin-right:4px;"></i>
        <strong>Cada linha cadastrada = uma parcela permitida.</strong>
        Se você cadastrar Visa Crédito 1x, 2x, 3x e 5x — o sistema só vai oferecer essas opções de parcelamento para o Visa.
        Se não houver nenhuma taxa cadastrada para uma bandeira, o sistema libera todas as parcelas sem restrição.
    </p>

    <?php if ($msg === 'sucesso'): ?>
        <p style="color:#28a745;background:#e8f5e9;padding:1rem;border-radius:6px;"><i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>Taxa salva com sucesso!</p>
    <?php elseif ($msg === 'excluido'): ?>
        <p style="color:#856404;background:#fff3cd;padding:1rem;border-radius:6px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i>Taxa desativada.</p>
    <?php elseif ($erro === 'campos_invalidos'): ?>
        <p style="color:#dc3545;background:#fdecea;padding:1rem;border-radius:6px;"><i class="fa-solid fa-xmark" style="margin-right:6px;"></i>Preencha todos os campos corretamente.</p>
    <?php elseif ($erro === 'intervalo_invalido'): ?>
        <p style="color:#dc3545;background:#fdecea;padding:1rem;border-radius:6px;"><i class="fa-solid fa-xmark" style="margin-right:6px;"></i>O valor "De" não pode ser maior que "Até".</p>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="card" style="margin-top:1.5rem;">
        <h3>Nova Taxa</h3>

        <!-- Toggle de modo -->
        <div class="modo-toggle" style="margin-bottom:1.2rem;">
            <label class="modo-option">
                <input type="radio" name="modo_radio" value="unica" checked onchange="alternarModo('unica')">
                <span><i class="fa-solid fa-tag"></i> Parcela específica</span>
            </label>
            <label class="modo-option">
                <input type="radio" name="modo_radio" value="intervalo" onchange="alternarModo('intervalo')">
                <span><i class="fa-solid fa-layer-group"></i> Mesma taxa para um intervalo</span>
            </label>
        </div>

        <form action="<?= BASE_URL ?>?rota=admin.taxas.salvar" method="POST">
            <input type="hidden" name="modo" id="modo_input" value="unica">

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-top:1rem;">

                <!-- Bandeira com select customizado com logos -->
                <div class="form-group">
                    <label>Bandeira</label>
                    <input type="hidden" name="bandeira" id="bandeira_value" required>
                    <div class="bandeira-select" id="bandeira_select">
                        <div class="bandeira-selected" id="bandeira_display" onclick="toggleBandeiraDropdown()">
                            <span id="bandeira_placeholder" style="color:#999;">Selecione a bandeira</span>
                        </div>
                        <div class="bandeira-options" id="bandeira_options">
                            <?php foreach ($bandeiras as $b): ?>
                            <div class="bandeira-option" onclick="selecionarBandeira('<?= htmlspecialchars($b) ?>', '<?= htmlspecialchars($logosBandeiras[$b] ?? '') ?>')" data-value="<?= htmlspecialchars($b) ?>">
                                <?php if (!empty($logosBandeiras[$b])): ?>
                                    <img src="<?= $logosBandeiras[$b] ?>" alt="<?= htmlspecialchars($b) ?>" style="width:46px;height:28px;object-fit:contain;">
                                <?php else: ?>
                                    <i class="fa-solid fa-credit-card" style="font-size:1.5rem;color:#aaa;width:46px;text-align:center;"></i>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($b) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo" id="tipo_taxa" required onchange="toggleParcelas()">
                        <option value="debito">Débito</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>

                <!-- Modo: Parcela específica -->
                <div class="form-group modo-campo modo-unica" id="grupo_parcelas">
                    <label>Parcelas</label>
                    <select name="parcelas" id="sel_parcela_unica">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?>x</option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Modo: Intervalo -->
                <div class="form-group modo-campo modo-intervalo" style="display:none;">
                    <label>De</label>
                    <select name="parcelas_de" id="sel_parcela_de">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?>x</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group modo-campo modo-intervalo" style="display:none;">
                    <label>Até</label>
                    <select name="parcelas_ate" id="sel_parcela_ate">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= $i == 12 ? 'selected' : '' ?>><?= $i ?>x</option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Taxa (%)</label>
                    <input type="number" name="percentual_taxa" step="0.01" min="0.01" placeholder="Ex: 1.50" required>
                    <small id="hint_taxa" style="color:#888;font-size:11px;">
                        Para crédito parcelado: taxa mensal repassada ao cliente (juros compostos).<br>
                        Para débito/crédito à vista: taxa da operadora descontada da clínica.
                    </small>
                </div>
            </div>

            <p id="resumo_intervalo" style="display:none;font-size:.9rem;color:var(--primary-color);background:#eef4fb;padding:.6rem 1rem;border-radius:6px;margin-top:1rem;">
                <i class="fa-solid fa-circle-info"></i> <span id="resumo_texto"></span>
            </p>

            <button type="submit" class="btn btn-success" style="margin-top:1rem;">
                <i class="fa-solid fa-floppy-disk" style="margin-right:6px;"></i>Salvar Taxa
            </button>
        </form>
    </div>

    <!-- Tabela -->
    <h3 style="margin-top:2rem;">Taxas Ativas</h3>
    <?php
    $grupos = [];
    foreach ($taxas as $t) $grupos[$t['bandeira']][$t['tipo']][] = $t;
    ?>
    <?php if (empty($taxas)): ?>
        <p style="color:var(--secondary-color);">Nenhuma taxa cadastrada. Cadastre acima para começar.</p>
    <?php else: ?>
        <div class="taxas-grid">
            <?php foreach ($grupos as $bandeira => $tipos): ?>
                <div class="taxa-card">
                    <div class="taxa-card-header">
                        <?php if (!empty($logosBandeiras[$bandeira])): ?>
                            <img src="<?= $logosBandeiras[$bandeira] ?>" alt="<?= htmlspecialchars($bandeira) ?>">
                        <?php else: ?>
                            <i class="fa-solid fa-credit-card" style="font-size:1.6rem;color:#aaa;width:48px;text-align:center;"></i>
                        <?php endif; ?>
                        <h4><?= htmlspecialchars($bandeira) ?></h4>
                    </div>
                    <div class="taxa-card-body">
                        <?php foreach ($tipos as $tipo => $rows): ?>
                            <?php foreach ($rows as $t): ?>
                                <div class="taxa-row" data-id="<?= $t['id'] ?>">
                                    <div class="taxa-row-info">
                                        <span class="taxa-tipo"><?= ucfirst($t['tipo']) ?></span>
                                        <span class="taxa-parcelas"><?= $t['parcelas'] ?>x</span>
                                    </div>
                                    <span class="taxa-valor" data-valor="<?= $t['percentual_taxa'] ?>">
                                        <?= number_format($t['percentual_taxa'], 2, ',', '.') ?>%
                                    </span>
                                    <div class="taxa-row-actions">
                                        <button type="button" class="taxa-btn-edit" title="Editar taxa"
                                                onclick="abrirEdicaoTaxa(<?= $t['id'] ?>, '<?= htmlspecialchars($bandeira) ?>', '<?= ucfirst($t['tipo']) ?>', <?= $t['parcelas'] ?>, <?= $t['percentual_taxa'] ?>)">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <a href="<?= BASE_URL ?>?rota=admin.taxas.excluir&id=<?= $t['id'] ?>"
                                           class="taxa-btn-delete" title="Desativar taxa"
                                           onclick="return confirm('Desativar esta taxa?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ===== MODAL DE EDIÇÃO RÁPIDA DE TAXA ===== -->
<div id="modalEditarTaxa" class="taxa-edit-overlay" onclick="if(event.target===this) fecharEdicaoTaxa()">
    <div class="taxa-edit-box">
        <button type="button" class="taxa-edit-close" onclick="fecharEdicaoTaxa()"><i class="fa-solid fa-xmark"></i></button>
        <h3><i class="fa-solid fa-pen" style="margin-right:8px;color:var(--primary-color);"></i>Editar Taxa</h3>
        <p id="taxa_edit_desc" style="color:var(--secondary-color);font-size:.9rem;margin-bottom:1rem;"></p>
        <div class="form-group">
            <label>Nova Taxa (%)</label>
            <input type="number" id="taxa_edit_valor" step="0.01" min="0.01" style="width:100%;">
        </div>
        <div id="taxa_edit_msg" style="display:none;font-size:.85rem;margin-bottom:.8rem;"></div>
        <button type="button" class="btn btn-success" style="width:100%;" onclick="salvarEdicaoTaxa()">
            <i class="fa-solid fa-floppy-disk" style="margin-right:6px;"></i>Salvar
        </button>
    </div>
</div>

<style>
.bandeira-select {
    position: relative;
    user-select: none;
}
.bandeira-selected {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 6px 12px;
    background: #fff;
    cursor: pointer;
    min-height: 42px;
    transition: border-color .2s;
}
.bandeira-selected:hover { border-color: var(--primary-color); }
.bandeira-selected::after {
    content: "\25BE";
    margin-left: auto;
    color: #999;
    font-size: 12px;
}
.bandeira-options {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    z-index: 9999;
    overflow: hidden;
}
.bandeira-options.open { display: block; }
.bandeira-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background .15s;
}
.bandeira-option:last-child { border-bottom: none; }
.bandeira-option:hover { background: #f0f4ff; }
.bandeira-option.selected { background: #e8f0fe; font-weight: 600; }
.bandeira-option span { font-size: 14px; color: #333; }
.modo-toggle {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.modo-option {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 10px 16px;
    cursor: pointer;
    transition: all .15s;
    font-size: 14px;
    flex: 1;
    min-width: 200px;
}
.modo-option:hover { border-color: var(--primary-color); background: #f8fafc; }
.modo-option input[type="radio"] { accent-color: var(--primary-color); width: 16px; height: 16px; }
.modo-option:has(input:checked) {
    border-color: var(--primary-color);
    background: #eef4fb;
    font-weight: 600;
}
.modo-option i { color: var(--primary-color); margin-right: 4px; }

/* ===== Grid de Taxas em Cards ===== */
.taxas-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-top: 1rem;
}
@media (max-width: 700px) {
    .taxas-grid { grid-template-columns: 1fr; }
}
.taxa-card {
    border: 1px solid #eee;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
}
.taxa-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #eee;
}
.taxa-card-header img { width: 48px; height: 30px; object-fit: contain; }
.taxa-card-header h4 { margin: 0; color: var(--primary-color); font-size: 1.05rem; }
.taxa-card-body { padding: 6px 0; }
.taxa-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    border-bottom: 1px solid #f5f5f5;
}
.taxa-row:last-child { border-bottom: none; }
.taxa-row-info {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}
.taxa-tipo { font-size: 0.85rem; color: #555; }
.taxa-parcelas {
    font-size: 0.75rem;
    background: #eef4fb;
    color: var(--primary-color);
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 600;
}
.taxa-valor {
    font-weight: 700;
    font-size: 0.95rem;
    color: #333;
    min-width: 60px;
    text-align: right;
}
.taxa-row-actions {
    display: flex;
    gap: 6px;
}
.taxa-btn-edit, .taxa-btn-delete {
    width: 30px;
    height: 30px;
    min-width: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    font-size: 0.8rem;
    text-decoration: none;
    transition: all .15s;
}
.taxa-btn-edit { background: #eef4fb; color: var(--primary-color); }
.taxa-btn-edit:hover { background: var(--primary-color); color: #fff; }
.taxa-btn-delete { background: #fdecea; color: #dc3545; }
.taxa-btn-delete:hover { background: #dc3545; color: #fff; }

/* ===== Modal de Edição Rápida ===== */
.taxa-edit-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(20,30,40,0.55);
    backdrop-filter: blur(3px);
    z-index: 9500;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.taxa-edit-overlay.open { display: flex; }
.taxa-edit-box {
    background: #fff;
    width: 100%;
    max-width: 360px;
    border-radius: 14px;
    padding: 24px;
    position: relative;
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
}
.taxa-edit-close {
    position: absolute;
    top: 12px; right: 12px;
    width: 30px; height: 30px;
    border-radius: 50%;
    background: #f0f2f5;
    border: none;
    cursor: pointer;
    color: #555;
}
.taxa-edit-close:hover { background: #e2e6ea; }
</style>

<script>
function toggleBandeiraDropdown() {
    document.getElementById('bandeira_options').classList.toggle('open');
}

function selecionarBandeira(nome, logo) {
    document.getElementById('bandeira_value').value = nome;
    document.getElementById('bandeira_options').classList.remove('open');

    // Atualizar display
    var display = document.getElementById('bandeira_display');
    var html = '';
    if (logo) {
        html += '<img src="' + logo + '" alt="' + nome + '" style="width:46px;height:28px;object-fit:contain;">';
    } else {
        html += '<i class="fa-solid fa-credit-card" style="font-size:1.3rem;color:#aaa;width:46px;text-align:center;"></i>';
    }
    html += '<span style="font-size:14px;color:#333;">' + nome + '</span>';
    display.innerHTML = html + '<span style="margin-left:auto;color:#999;font-size:12px;">&#9662;</span>';

    // Marcar selected
    document.querySelectorAll('.bandeira-option').forEach(function(el) {
        el.classList.toggle('selected', el.dataset.value === nome);
    });
}

// Fechar ao clicar fora
document.addEventListener('click', function(e) {
    var sel = document.getElementById('bandeira_select');
    if (sel && !sel.contains(e.target)) {
        document.getElementById('bandeira_options').classList.remove('open');
    }
});

function alternarModo(modo) {
    document.getElementById('modo_input').value = modo;

    var camposUnica     = document.querySelectorAll('.modo-unica');
    var camposIntervalo = document.querySelectorAll('.modo-intervalo');

    if (modo === 'intervalo') {
        camposUnica.forEach(function(el) { el.style.display = 'none'; });
        camposIntervalo.forEach(function(el) { el.style.display = 'block'; });
    } else {
        camposUnica.forEach(function(el) { el.style.display = 'block'; });
        camposIntervalo.forEach(function(el) { el.style.display = 'none'; });
    }
    atualizarResumoIntervalo();
}

function atualizarResumoIntervalo() {
    var modo = document.getElementById('modo_input').value;
    var resumo = document.getElementById('resumo_intervalo');
    var texto  = document.getElementById('resumo_texto');

    if (modo !== 'intervalo') {
        resumo.style.display = 'none';
        return;
    }

    var de  = parseInt(document.getElementById('sel_parcela_de').value);
    var ate = parseInt(document.getElementById('sel_parcela_ate').value);
    var taxaInput = document.querySelector('input[name="percentual_taxa"]');
    var taxa = parseFloat((taxaInput.value || '0').replace(',', '.')) || 0;

    if (de > ate) {
        texto.innerHTML = '<strong style="color:#dc3545;">"De" não pode ser maior que "Até".</strong>';
        resumo.style.display = 'block';
        return;
    }

    var qtd = ate - de + 1;
    texto.innerHTML = 'Será cadastrada a taxa de <strong>' + (taxa || 0).toFixed(2).replace('.',',') + '%</strong> '
        + 'para <strong>' + qtd + ' parcela(s)</strong>: de <strong>' + de + 'x</strong> até <strong>' + ate + 'x</strong>.';
    resumo.style.display = 'block';
}

function toggleParcelas() {
    const tipo = document.getElementById('tipo_taxa').value;
    const modo = document.getElementById('modo_input').value;

    if (modo === 'unica') {
        const g = document.getElementById('grupo_parcelas');
        g.style.opacity = tipo === 'debito' ? '0.5' : '1';
        if (tipo === 'debito') document.getElementById('sel_parcela_unica').value = '1';
    } else {
        // Intervalo: débito força 1x a 1x
        if (tipo === 'debito') {
            document.getElementById('sel_parcela_de').value  = '1';
            document.getElementById('sel_parcela_ate').value = '1';
            document.getElementById('sel_parcela_de').disabled  = true;
            document.getElementById('sel_parcela_ate').disabled = true;
        } else {
            document.getElementById('sel_parcela_de').disabled  = false;
            document.getElementById('sel_parcela_ate').disabled = false;
        }
        atualizarResumoIntervalo();
    }
}

// Listeners para atualizar o resumo em tempo real
document.getElementById('sel_parcela_de').addEventListener('change', atualizarResumoIntervalo);
document.getElementById('sel_parcela_ate').addEventListener('change', atualizarResumoIntervalo);
document.querySelector('input[name="percentual_taxa"]').addEventListener('input', atualizarResumoIntervalo);

toggleParcelas();

// ===== Edição rápida de taxa (modal) =====
var taxaEditId = null;

function abrirEdicaoTaxa(id, bandeira, tipo, parcelas, valorAtual) {
    taxaEditId = id;
    document.getElementById('taxa_edit_desc').textContent = bandeira + ' · ' + tipo + ' · ' + parcelas + 'x';
    document.getElementById('taxa_edit_valor').value = valorAtual;
    document.getElementById('taxa_edit_msg').style.display = 'none';
    document.getElementById('modalEditarTaxa').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(function() { document.getElementById('taxa_edit_valor').focus(); }, 100);
}

function fecharEdicaoTaxa() {
    document.getElementById('modalEditarTaxa').classList.remove('open');
    document.body.style.overflow = '';
    taxaEditId = null;
}

function salvarEdicaoTaxa() {
    var valor = parseFloat((document.getElementById('taxa_edit_valor').value || '0').replace(',', '.'));
    var msg = document.getElementById('taxa_edit_msg');

    if (!taxaEditId || isNaN(valor) || valor <= 0) {
        msg.textContent = 'Informe uma taxa válida.';
        msg.style.color = '#dc3545';
        msg.style.display = 'block';
        return;
    }

    var fd = new FormData();
    fd.append('id', taxaEditId);
    fd.append('percentual_taxa', valor);

    fetch(window.__BASE_URL + '?rota=admin.taxas.editar', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.sucesso) {
                // Atualizar o valor na tela sem recarregar
                var row = document.querySelector('.taxa-row[data-id="' + taxaEditId + '"] .taxa-valor');
                if (row) row.textContent = data.percentual.toFixed(2).replace('.', ',') + '%';
                fecharEdicaoTaxa();
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
    if (e.key === 'Escape') fecharEdicaoTaxa();
});
</script>
