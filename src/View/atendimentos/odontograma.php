<style>
.bandeira-custom {
    position: relative;
    display: inline-block;
    min-width: 190px;
}
.bandeira-custom-selected {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 6px 10px;
    background: #fff;
    cursor: pointer;
    min-height: 40px;
    font-size: 14px;
}
.bandeira-custom-selected:hover { border-color: var(--primary-color, #005b96); }
.bandeira-custom-selected::after { content: "\25BE"; margin-left:auto; color:#999; }
.bandeira-custom-dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    min-width: 200px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    z-index: 99999;
    overflow: hidden;
}
.bandeira-custom-dropdown.open { display: block; }
.bandeira-custom-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    color: #333;
}
.bandeira-custom-item:last-child { border-bottom: none; }
.bandeira-custom-item:hover { background: #f0f4ff; }
.bandeira-custom-item.selected { background: #e8f0fe; font-weight: 600; }
.bandeira-custom-item img { width: 44px; height: 27px; object-fit: contain; }

/* ===== Lista de Pagamentos Pendentes ===== */
.pendentes-lista {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.pendente-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #fff;
    border: 1px solid #eee;
    border-radius: 10px;
    padding: 12px 16px;
    text-decoration: none;
    color: inherit;
    transition: all .15s ease;
}
.pendente-item:hover {
    border-color: #e67e22;
    background: #fff8f0;
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(230,126,34,0.12);
}
.pendente-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.pendente-info strong {
    font-size: 0.95rem;
    color: #333;
}
.pendente-meta {
    font-size: 0.78rem;
    color: #999;
}
.pendente-valor {
    display: flex;
    align-items: center;
    font-weight: 700;
    font-size: 1rem;
    color: #e67e22;
    white-space: nowrap;
}
</style>

<div id="toast-notification" class="toast"></div>
<style>
    .toast { position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 5px; color: white; font-size: 16px; z-index: 9999; opacity: 0; visibility: hidden; transition: opacity 0.5s, visibility 0.5s, transform 0.5s; transform: translateX(100%); }
    .toast.show { opacity: 1; visibility: visible; transform: translateX(0); }
    .toast.error { background-color: #dc3545; } /* red */
    .toast.success { background-color: #28a745; } /* green */
</style>
<div class="card">
    <h2>Confirmar Pagamento</h2>

    <fieldset style="border:1px solid #ddd; border-radius:8px; padding:1rem; margin-bottom:1.5rem;">
        <legend style="font-weight:bold; padding:0 8px;">Buscar Paciente</legend>
        <div class="form-group busca-wrapper" style="margin-bottom:0;">
            <label for="paciente_busca_odonto">Paciente</label>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                <input type="text" id="paciente_busca_odonto"
                       placeholder="Digite o nome para buscar..."
                       autocomplete="off" style="flex-grow:1;"
                       value="<?= htmlspecialchars($paciente_nome ?? '') ?>"
                       oninput="buscaOdonto(this.value)">
                <?php if (!empty($paciente_nome)): ?>
                <a href="<?= BASE_URL ?>?rota=atendimentos.pagamento" class="btn btn-danger"><i class="fa-solid fa-xmark"></i> Limpar</a>
                <?php endif; ?>
            </div>
            <ul id="drop_odonto" class="busca-dropdown"></ul>
            <span id="status_odonto" class="busca-status">
                <?= !empty($paciente_nome) ? ('<i class="fa-solid fa-circle-check" style="color:#28a745;margin-right:4px;"></i>Paciente: <strong>'.htmlspecialchars($paciente_nome).'</strong>') : 'Digite o nome para buscar o paciente.' ?>
            </span>
        </div>
    </fieldset>


    <?php if (empty($paciente_id) && !empty($pendentes)): ?>
        <div class="card" style="margin-top:1.2rem; border-left: 4px solid #e67e22;">
            <h3 style="margin-bottom:.8rem;">
                <i class="fa-solid fa-clock" style="margin-right:8px;color:#e67e22;"></i>
                Pagamentos Pendentes
                <span style="background:#e67e22;color:#fff;font-size:.75rem;padding:2px 9px;border-radius:12px;margin-left:6px;vertical-align:middle;">
                    <?= count($pendentes) ?>
                </span>
            </h3>
            <p style="color:var(--secondary-color);font-size:.9rem;margin-bottom:.8rem;">
                Clique em um paciente para confirmar o pagamento.
            </p>
            <div class="pendentes-lista">
                <?php foreach ($pendentes as $p): ?>
                    <a href="<?= BASE_URL ?>?rota=atendimentos.pagamento&paciente_id=<?= $p['paciente_id'] ?>" class="pendente-item">
                        <div class="pendente-info">
                            <strong><?= htmlspecialchars($p['paciente_nome']) ?></strong>
                            <span class="pendente-meta">
                                <?= date('d/m/Y', strtotime($p['data_atendimento'])) ?>
                                · <?= (int)$p['qtd_procedimentos'] ?> procedimento(s)
                            </span>
                        </div>
                        <div class="pendente-valor">
                            R$ <?= number_format($p['valor_total'], 2, ',', '.') ?>
                            <i class="fa-solid fa-chevron-right" style="margin-left:8px;color:#bbb;"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($paciente_id && $paciente_nome): ?>

        <?php if (!$ultimo_atendimento_id): ?>
            <div class="card" style="border-left: 4px solid #e67e22; padding: 1rem; margin-top:1rem;">
                <strong style="color:#e67e22;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i>Nenhum atendimento encontrado</strong>
                <p>O paciente <strong><?= htmlspecialchars($paciente_nome) ?></strong> não possui atendimentos registrados. Faça um lançamento primeiro.</p>
                <a href="<?= BASE_URL ?>?rota=atendimentos.novo" class="btn btn-primary" style="margin-top:0.5rem;">Lançar Atendimento</a>
            </div>
        <?php else: ?>

        <form id="form-pagamento" action="<?= BASE_URL ?>?rota=atendimentos.pagar" method="POST">
            <input type="hidden" name="paciente_id"    value="<?= (int)($paciente_id ?? 0) ?>">
            <input type="hidden" name="atendimento_id" value="<?= (int)($ultimo_atendimento_id ?? 0) ?>">

            <div class="card" style="margin-top:1rem;">
                <h3 style="margin-bottom:0.8rem;"><i class="fa-solid fa-file-medical" style="margin-right:8px;color:var(--primary-color);"></i>Procedimentos do Atendimento</h3>

                <?php if (!empty($atendimentos)): ?>
                <table class="mobile-card-table">
                    <thead><tr><th>Procedimento</th><th>Qtd</th><th style="text-align:right;">Valor</th></tr></thead>
                    <tbody>
                        <?php foreach ($atendimentos as $at): ?>
                        <tr>
                            <td><?= htmlspecialchars($at['nome']) ?></td>
                            <td><?= (int)$at['quantidade'] ?>x</td>
                            <td style="text-align:right; font-weight:600;">R$ <?= number_format($at['valor_procedimento'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color:#888; padding:1rem 0;">Nenhum procedimento listado. O valor total será informado manualmente.</p>
                <?php endif; ?>

                <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                    <strong>Valor Total do Atendimento:</strong>
                    <span style="font-size:1.3rem; font-weight:700; color:#005b96;">
                        R$ <?= number_format($valor_total, 2, ',', '.') ?>
                    </span>
                </div>

                <!-- ── Preview de rateio ─────────────────────────────────── -->
                <div id="rateio-preview-box" style="margin-top:1.2rem;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:.6rem;">
                        <i class="fa-solid fa-coins" style="color:#e67e22;"></i>
                        <strong style="font-size:.95rem;">Divisão financeira (preview)</strong>
                        <span id="rateio-loading" style="font-size:12px;color:#999;display:none;">
                            <i class="fa-solid fa-spinner fa-spin"></i> calculando...
                        </span>
                    </div>
                    <div id="rateio-content">
                        <!-- preenchido via JS -->
                        <p style="color:#aaa;font-size:13px;">Carregando divisão...</p>
                    </div>
                </div>
                <!-- ──────────────────────────────────────────────────────── -->                </div>

                <input type="hidden" id="valor_total_hidden" value="<?= number_format($valor_total, 2, '.', '') ?>">
            </div>

            <div class="card" style="margin-top:1rem;">
                <h3 style="margin-bottom:0.8rem;"><i class="fa-solid fa-credit-card" style="margin-right:8px;color:var(--primary-color);"></i>Formas de Pagamento</h3>
                <p style="color:#888; font-size:13px; margin-bottom:1rem;">
                    Distribua o valor entre uma ou mais formas de pagamento. A soma deve ser igual ao total.
                </p>

                <div id="pagamentos_container"></div>

                <button type="button" id="add_pagamento" class="btn btn-secondary" style="margin-top:0.5rem;">
                    + Adicionar Forma de Pagamento
                </button>

                <div style="margin-top:1rem; padding:0.8rem; background:#f8fafc; border-radius:6px; display:flex; gap:2rem; flex-wrap:wrap;">
                    <span>Total informado: <strong id="total_pago" style="color:#005b96;">R$ 0,00</strong></span>
                    <span>Restante: <strong id="restante_pagar" style="color:#e74c3c;">R$ 0,00</strong></span>
                </div>
            </div>

            <button type="submit" class="btn btn-success" style="width:100%; margin-top:1rem; padding:14px; font-size:16px;">
                <i class="fa-solid fa-check" style="margin-right:6px;"></i>Confirmar Pagamento
            </button>
        </form>

        <?php endif; ?>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
$(document).ready(function() {
    // Autocomplete for patient search remains the same
    // busca de paciente via dropdown nativo (buscaOdonto)

    // ── Sistema de pagamento ─────────────────────────────────────────────────
    var container    = document.getElementById('pagamentos_container');
    var addBtn       = document.getElementById('add_pagamento');
    var totalSpan    = document.getElementById('total_pago');
    var restanteSpan = document.getElementById('restante_pagar');
    var valorTotal   = parseFloat((document.getElementById('valor_total_hidden') || {}).value || 0);

    if (!container) return;

    var formasLabel = { dinheiro:'Dinheiro', pix:'PIX', debito:'Débito', credito:'Crédito' };

    var bandeiras = [
        { value:'Visa',             logo:'https://raw.githubusercontent.com/aaronfagan/svg-credit-card-payment-icons/main/flat/visa.svg' },
        { value:'Mastercard',       logo:'https://raw.githubusercontent.com/aaronfagan/svg-credit-card-payment-icons/main/flat/mastercard.svg' },
        { value:'Elo',              logo:'https://raw.githubusercontent.com/aaronfagan/svg-credit-card-payment-icons/main/flat/elo.svg' },
        { value:'Hipercard',        logo:'https://raw.githubusercontent.com/aaronfagan/svg-credit-card-payment-icons/main/flat/hipercard.svg' },
        { value:'American Express', logo:'https://raw.githubusercontent.com/aaronfagan/svg-credit-card-payment-icons/main/flat/amex.svg' },
        { value:'Outras',           logo:null }
    ];

    function buscarParcelas(bandeira, tipo, callback) {
        try {
            var url = window.__BASE_URL + 'ajax/buscar_parcelas.php'
                + '?bandeira=' + encodeURIComponent(bandeira)
                + '&tipo='     + encodeURIComponent(tipo);
            fetch(url)
                .then(function(r) { return r.ok ? r.json() : {parcelas: [1,2,3,4,5,6,7,8,9,10,11,12], limitado: false}; })
                .then(function(d) { callback(d.parcelas || [], d.limitado || false); })
                .catch(function() { callback([1,2,3,4,5,6,7,8,9,10,11,12], false); });
        } catch(e) { callback([1,2,3,4,5,6,7,8,9,10,11,12], false); }
    }

    var linhasPagamento = []; // referências diretas: {row, inp, sel, parcSel, bandHidden}

    // Calcula parcela com juros compostos (Tabela Price)
    // Parcela = PV * [i*(1+i)^n] / [(1+i)^n - 1]
    function calcularParcelaPrice(valor, taxaMensal, parcelas) {
        if (parcelas <= 1 || taxaMensal === 0) return valor / parcelas;
        var i = taxaMensal / 100;
        var fator = Math.pow(1 + i, parcelas);
        return valor * (i * fator) / (fator - 1);
    }

    function buscarTaxa(bandeira, tipo, parcelas, callback) {
        try {
            var url = window.__BASE_URL + 'ajax/buscar_taxa.php'
                + '?bandeira=' + encodeURIComponent(bandeira)
                + '&tipo='     + encodeURIComponent(tipo)
                + '&parcelas=' + encodeURIComponent(parcelas);
            fetch(url)
                .then(function(r) { return r.ok ? r.json() : {taxa:0}; })
                .then(function(d) { callback((d && d.taxa) ? parseFloat(d.taxa) : 0); })
                .catch(function() { callback(0); });
        } catch(e) { callback(0); }
    }

    function criarLinhaPagamento(valorInicial) {
        var row = document.createElement('div');
        row.setAttribute('data-pagamento-row', '1');
        row.style.cssText = 'margin-bottom:12px;';

        var linha = document.createElement('div');
        linha.style.cssText = 'display:flex; gap:8px; align-items:center; flex-wrap:wrap;';

        // Forma
        var sel = document.createElement('select');
        sel.name = 'pagamentos[forma][]';
        sel.style.cssText = 'flex:0 0 150px; padding:8px; border:1px solid #ccc; border-radius:6px;';
        Object.entries(formasLabel).forEach(function(pair) {
            var o = document.createElement('option');
            o.value = pair[0]; o.textContent = pair[1];
            sel.appendChild(o);
        });

        // Valor
        var inp = document.createElement('input');
        inp.type = 'text';
        inp.name = 'pagamentos[valor][]';
        inp.placeholder = 'Valor (R$)';
        inp.style.cssText = 'flex:1; min-width:100px; padding:8px; border:1px solid #ccc; border-radius:6px;';
        if (valorInicial) inp.value = valorInicial.toFixed(2).replace('.', ',');

        // Bandeira hidden
        var bandHidden = document.createElement('input');
        bandHidden.type = 'hidden';
        bandHidden.name = 'pagamentos[bandeira][]';
        bandHidden.value = bandeiras[0].value;

        // Wrapper bandeira
        var bandWrapper = document.createElement('div');
        bandWrapper.className = 'bandeira-custom';
        bandWrapper.style.cssText = 'display:none; flex-shrink:0;';

        var bandDisplay = document.createElement('div');
        bandDisplay.className = 'bandeira-custom-selected';

        var bandDropdown = document.createElement('div');
        bandDropdown.className = 'bandeira-custom-dropdown';

        // Parcelas
        var parcSel = document.createElement('select');
        parcSel.style.cssText = 'display:none; padding:8px; border:1px solid #ccc; border-radius:6px; width:75px; flex-shrink:0;';
        for (var p = 1; p <= 12; p++) {
            var op = document.createElement('option');
            op.value = p; op.textContent = p + 'x';
            parcSel.appendChild(op);
        }

        // Info taxa
        var infoDiv = document.createElement('div');
        infoDiv.style.cssText = 'display:none; font-size:13px; color:#555; background:#f0f4ff; border-radius:6px; padding:6px 12px; margin-top:4px; border-left:3px solid var(--primary-color, #005b96);';

        // ── Funções locais (definidas antes de serem usadas) ──────────────────

        function mostrarInfoTaxa() {
            var forma    = sel.value;
            var valor    = parseFloat(inp.value.replace(',', '.')) || 0;
            var parcelas = parseInt(parcSel.value) || 1;
            var band     = bandHidden.value;

            if ((forma === 'debito' || forma === 'credito') && valor > 0) {
                buscarTaxa(band, forma, parcelas, function(taxa) {
                    var html = '<i class="fa-solid fa-circle-info" style="margin-right:5px;color:#005b96;"></i>';

                    if (forma === 'debito') {
                        // Débito: taxa única, clínica absorve
                        var desconto = valor * (taxa / 100);
                        var liquido  = valor - desconto;
                        if (taxa > 0) {
                            html += 'Taxa ' + band + ' Débito: <strong>' + taxa.toFixed(2).replace('.',',') + '%</strong>';
                            html += ' → clínica recebe <strong style="color:#27ae60;">R$ ' + liquido.toFixed(2).replace('.',',') + '</strong>';
                        } else {
                            html += 'Sem taxa cadastrada para débito ' + band + '.';
                        }

                    } else {
                        // Crédito: juros compostos repassados ao cliente
                        if (parcelas === 1) {
                            // À vista no crédito: taxa única sobre total
                            var desconto1 = valor * (taxa / 100);
                            var liquido1  = valor - desconto1;
                            if (taxa > 0) {
                                html += 'Crédito à vista ' + band + ': taxa <strong>' + taxa.toFixed(2).replace('.',',') + '%</strong>';
                                html += ' → clínica recebe <strong style="color:#27ae60;">R$ ' + liquido1.toFixed(2).replace('.',',') + '</strong>';
                            } else {
                                html += 'Sem taxa cadastrada para crédito à vista ' + band + '.';
                            }
                        } else {
                            // Parcelado: juros compostos — cliente paga mais
                            var parcela      = calcularParcelaPrice(valor, taxa, parcelas);
                            var totalCliente = parcela * parcelas;
                            var jurosTotal   = totalCliente - valor;
                            var taxaClinica  = totalCliente * 0.015; // taxa operadora sobre total recebido (aprox)
                            var liquidoCli   = totalCliente - taxaClinica;

                            html += band + ' <strong>' + parcelas + 'x</strong> com juros de <strong>' + taxa.toFixed(2).replace('.',',') + '% a.m.</strong><br>';
                            html += '&nbsp;&nbsp;→ Parcela: <strong style="color:#e67e22;">R$ ' + parcela.toFixed(2).replace('.',',') + '/mês</strong>';
                            html += ' | Total cliente: <strong style="color:#e67e22;">R$ ' + totalCliente.toFixed(2).replace('.',',') + '</strong>';
                            html += ' | Juros: <strong>R$ ' + jurosTotal.toFixed(2).replace('.',',') + '</strong>';
                        }
                    }

                    infoDiv.innerHTML = html;
                    infoDiv.style.display = 'block';
                    atualizarTotalComJuros();
                });
            } else {
                infoDiv.style.display = 'none';
                atualizarTotalComJuros();
            }
        }

        function renderizarBandDisplay(b) {
            bandDisplay.innerHTML = '';
            var el;
            if (b.logo) {
                el = document.createElement('img');
                el.src = b.logo; el.alt = b.value;
                el.style.cssText = 'width:44px;height:27px;object-fit:contain;';
            } else {
                el = document.createElement('i');
                el.className = 'fa-solid fa-credit-card';
                el.style.cssText = 'font-size:1.3rem;color:#aaa;width:44px;text-align:center;';
            }
            var sp = document.createElement('span');
            sp.textContent = b.value;
            sp.style.marginLeft = '6px';
            bandDisplay.appendChild(el);
            bandDisplay.appendChild(sp);
        }

        // ── Montar dropdown de bandeiras ──────────────────────────────────────
        bandeiras.forEach(function(b, idx) {
            var item = document.createElement('div');
            item.className = 'bandeira-custom-item' + (idx === 0 ? ' selected' : '');
            item.dataset.value = b.value;

            var el2;
            if (b.logo) {
                el2 = document.createElement('img');
                el2.src = b.logo; el2.alt = b.value;
            } else {
                el2 = document.createElement('i');
                el2.className = 'fa-solid fa-credit-card';
                el2.style.cssText = 'font-size:1.4rem;color:#aaa;width:44px;text-align:center;';
            }
            var sp2 = document.createElement('span');
            sp2.textContent = b.value;
            item.appendChild(el2);
            item.appendChild(sp2);

            item.addEventListener('mousedown', function(e) {
                e.preventDefault();
                bandHidden.value = b.value;
                bandDropdown.querySelectorAll('.bandeira-custom-item').forEach(function(el) {
                    el.classList.remove('selected');
                });
                item.classList.add('selected');
                renderizarBandDisplay(b);
                bandDropdown.classList.remove('open');
                // Atualizar parcelas disponíveis para esta bandeira
                if (sel.value === 'credito' || sel.value === 'debito') {
                    atualizarParcelas(b.value, sel.value);
                }
                mostrarInfoTaxa();
            });
            bandDropdown.appendChild(item);
        });

        // Inicializar com Visa
        renderizarBandDisplay(bandeiras[0]);

        bandDisplay.addEventListener('click', function(e) {
            e.stopPropagation();
            bandDropdown.classList.toggle('open');
        });
        document.addEventListener('click', function() {
            bandDropdown.classList.remove('open');
        });

        bandWrapper.appendChild(bandHidden);
        bandWrapper.appendChild(bandDisplay);
        bandWrapper.appendChild(bandDropdown);

        function atualizarParcelas(bandeira, tipo) {
            buscarParcelas(bandeira, tipo, function(parcelas, limitado) {
                var valorAtual = parseInt(parcSel.value) || 1;
                parcSel.innerHTML = '';
                parcelas.forEach(function(p) {
                    var op = document.createElement('option');
                    op.value = p;
                    op.textContent = p + 'x' + (p === 1 ? ' (à vista)' : '');
                    parcSel.appendChild(op);
                });
                // Manter seleção se ainda disponível
                var existe = parcelas.indexOf(valorAtual) !== -1;
                parcSel.value = existe ? valorAtual : parcelas[parcelas.length - 1];

                if (limitado) {
                    parcSel.title = 'Parcelamento limitado pelo cadastro de taxas desta bandeira';
                } else {
                    parcSel.title = '';
                }
                mostrarInfoTaxa();
            });
        }

        // ── Eventos ──────────────────────────────────────────────────────────
        sel.addEventListener('change', function() {
            var f = sel.value;
            if (f === 'debito' || f === 'credito') {
                bandWrapper.style.display = 'flex';
                atualizarParcelas(bandHidden.value, f);
            } else {
                bandWrapper.style.display = 'none';
            }
            if (f === 'credito') {
                parcSel.style.display = 'inline-block';
                parcSel.name = 'pagamentos[parcelas][]';
            } else {
                parcSel.style.display = 'none';
                parcSel.removeAttribute('name');
            }
            mostrarInfoTaxa();
        });

        inp.addEventListener('input', function() {
            recalcular();
            mostrarInfoTaxa();
            atualizarTotalComJuros();
        });

        parcSel.addEventListener('change', mostrarInfoTaxa);

        // Botão remover
        var rem = document.createElement('button');
        rem.type = 'button';
        rem.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        rem.className = 'btn btn-danger';
        rem.style.cssText = 'padding:8px 12px; flex-shrink:0;';
        // (listener de remoção configurado abaixo, após registro no array)

        linha.appendChild(sel);
        linha.appendChild(inp);
        linha.appendChild(bandWrapper);
        linha.appendChild(parcSel);
        linha.appendChild(rem);
        row.appendChild(linha);
        row.appendChild(infoDiv);

        // Registrar referências diretas para cálculo de juros
        var ref = { row: row, inp: inp, sel: sel, parcSel: parcSel, bandHidden: bandHidden };
        linhasPagamento.push(ref);

        rem.addEventListener('click', function() {
            var idx = linhasPagamento.indexOf(ref);
            if (idx > -1) linhasPagamento.splice(idx, 1);
            row.remove();
            recalcular();
            atualizarTotalComJuros();
        });

        return row;
    }

        function recalcular() {
        var total = 0;
        container.querySelectorAll('input[name="pagamentos[valor][]"]').forEach(function(i) {
            var v = parseFloat(i.value.replace(',', '.'));
            if (!isNaN(v)) total += v;
        });
        var restante = valorTotal - total;
        if (totalSpan)    totalSpan.innerHTML = 'R$ ' + total.toFixed(2).replace('.', ',');
        if (restanteSpan) {
            restanteSpan.textContent = 'R$ ' + Math.abs(restante).toFixed(2).replace('.', ',');
            restanteSpan.style.color = Math.abs(restante) < 0.01 ? '#27ae60' : '#e74c3c';
        }
    }

    // Atualiza o totalSpan com valor final incluindo juros de todas as linhas
    function atualizarTotalComJuros() {
        var linhas = [];

        linhasPagamento.forEach(function(ref) {
            var v = parseFloat(ref.inp.value.replace(',', '.')) || 0;
            if (v <= 0) return;
            var f = ref.sel.value;
            var p = parseInt(ref.parcSel.value) || 1;
            var b = ref.bandHidden.value;
            linhas.push({ valor: v, forma: f, parcelas: p, bandeira: b });
        });

        if (linhas.length === 0) {
            if (totalSpan) totalSpan.innerHTML = 'R$ 0,00';
            return;
        }

        var totalBase  = linhas.reduce(function(s, l) { return s + l.valor; }, 0);
        var pendentes  = linhas.length;
        var totalFinal = 0;

        linhas.forEach(function(l) {
            if (l.forma === 'credito' && l.parcelas > 1) {
                buscarTaxa(l.bandeira, 'credito', l.parcelas, function(taxa) {
                    if (taxa > 0) {
                        totalFinal += calcularParcelaPrice(l.valor, taxa, l.parcelas) * l.parcelas;
                    } else {
                        totalFinal += l.valor;
                    }
                    pendentes--;
                    if (pendentes === 0) renderizarTotal(totalBase, totalFinal);
                });
            } else {
                totalFinal += l.valor;
                pendentes--;
                if (pendentes === 0) renderizarTotal(totalBase, totalFinal);
            }
        });
    }

    function renderizarTotal(base, final) {
        if (!totalSpan) return;
        var diff = final - base;
        if (diff > 0.01) {
            totalSpan.innerHTML =
                '<span style="color:#005b96;">R$ ' + base.toFixed(2).replace('.', ',') + '</span>'
                + ' <i class="fa-solid fa-arrow-right" style="font-size:11px;color:#aaa;"></i>'
                + ' <strong style="color:#e67e22;">R$ ' + final.toFixed(2).replace('.', ',') + '</strong>'
                + ' <small style="color:#e67e22;">(com juros)</small>';
        } else {
            totalSpan.innerHTML = '<span style="color:#005b96;">R$ ' + base.toFixed(2).replace('.', ',') + '</span>';
        }
    }

    // ── Preview de Rateio ──────────────────────────────────────────────────
    (function() {
        var atId    = <?= (int)($ultimo_atendimento_id ?? 0) ?>;
        var box     = document.getElementById('rateio-content');
        var loading = document.getElementById('rateio-loading');
        if (!atId || !box) return;

        if (loading) loading.style.display = 'inline';

        fetch(window.__BASE_URL + 'ajax/preview_rateio.php?atendimento_id=' + atId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (loading) loading.style.display = 'none';
                if (data.erro) {
                    box.innerHTML = '<p style="color:#dc3545;font-size:13px;">' + data.erro + '</p>';
                    return;
                }

                var html = '<div style="display:flex;flex-direction:column;gap:8px;">';

                // Linha por procedimento
                data.splits.forEach(function(s) {
                    html += '<div style="background:#f8fafc;border-radius:8px;padding:10px 14px;border:1px solid #e8eef5;">';
                    html += '<div style="font-size:13px;font-weight:600;color:#333;margin-bottom:6px;">'
                          + esc(s.proc_nome) + ' — <span style="color:#005b96;">R$ ' + fmtR(s.valor) + '</span></div>';

                    html += '<div style="display:flex;flex-wrap:wrap;gap:6px;">';

                    if (s.tipo === 'especializado') {
                        html += pill('#005b96', esc(s.executor_nome || 'Especialista') + ' (executor ' + fmtPct(s.pct_especialista) + ')', 'R$ ' + fmtR(s.especialista));
                        if (s.indicador > 0) {
                            html += pill('#17a2b8', esc(s.indicador_nome || 'Indicador') + ' (' + fmtPct(s.pct_indicador) + ')', 'R$ ' + fmtR(s.indicador));
                        }
                        html += pill('#e67e22', 'Clínica (' + fmtPct(s.pct_clinica) + ')', 'R$ ' + fmtR(s.clinica));
                    } else {
                        html += pill('#27ae60', esc(s.executor_nome || 'Clínico Geral') + ' (' + fmtPct(s.pct_dentista) + ')', 'R$ ' + fmtR(s.clinico_geral));
                        html += pill('#e67e22', 'Clínica (' + fmtPct(100 - s.pct_dentista) + ')', 'R$ ' + fmtR(s.clinica));
                    }

                    html += '</div></div>';
                });

                html += '</div>';

                // Barra de resumo geral
                var totalGeral = data.totais.dentista + data.totais.clinico_geral + data.totais.indicador + data.totais.clinica;
                html += '<div style="margin-top:10px;padding:12px 16px;background:#fff;border:1px solid #dee2e6;border-radius:8px;">';
                html += '<div style="font-size:12px;font-weight:700;color:#555;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px;">Resumo do atendimento</div>';
                html += '<div style="display:flex;gap:16px;flex-wrap:wrap;">';
                if (data.totais.dentista > 0) {
                    html += resumoItem('#005b96', 'Especialista(s)', data.totais.dentista);
                }
                if (data.totais.clinico_geral > 0) {
                    html += resumoItem('#27ae60', 'Clínico Geral', data.totais.clinico_geral);
                }
                if (data.totais.indicador > 0) {
                    html += resumoItem('#17a2b8', 'Indicação', data.totais.indicador);
                }
                html += resumoItem('#e67e22', 'Clínica', data.totais.clinica);
                html += '</div>';

                // Barra visual proporcional
                if (totalGeral > 0) {
                    html += '<div style="display:flex;height:8px;border-radius:4px;overflow:hidden;margin-top:10px;gap:1px;">';
                    var segmentos = [
                        { v: data.totais.dentista,     c: '#005b96' },
                        { v: data.totais.clinico_geral, c: '#27ae60' },
                        { v: data.totais.indicador,    c: '#17a2b8' },
                        { v: data.totais.clinica,      c: '#e67e22' },
                    ];
                    segmentos.forEach(function(seg) {
                        if (seg.v <= 0) return;
                        var pct = (seg.v / totalGeral * 100).toFixed(1);
                        html += '<div style="flex:' + pct + ';background:' + seg.c + ';min-width:3px;"></div>';
                    });
                    html += '</div>';
                    html += '<div style="font-size:11px;color:#999;margin-top:4px;">Os valores acima são um preview calculado antes da confirmação do pagamento.</div>';
                }

                html += '</div>';
                box.innerHTML = html;
            })
            .catch(function(e) {
                if (loading) loading.style.display = 'none';
                if (box) box.innerHTML = '<p style="color:#aaa;font-size:12px;">Não foi possível carregar o preview de rateio.</p>';
            });

        function pill(cor, label, valor) {
            return '<span style="display:inline-flex;align-items:center;gap:4px;background:' + cor + '18;'
                 + 'border:1px solid ' + cor + '55;border-radius:14px;padding:3px 10px;font-size:12px;">'
                 + '<span style="color:' + cor + ';font-weight:700;">' + valor + '</span>'
                 + ' <span style="color:#555;">' + label + '</span>'
                 + '</span>';
        }

        function resumoItem(cor, label, valor) {
            return '<div style="display:flex;flex-direction:column;gap:2px;">'
                 + '<span style="font-size:11px;color:#999;">' + label + '</span>'
                 + '<span style="font-size:1rem;font-weight:700;color:' + cor + ';">R$ ' + fmtR(valor) + '</span>'
                 + '</div>';
        }

        function fmtR(v) { return parseFloat(v||0).toFixed(2).replace('.', ','); }
        function fmtPct(v) { return parseFloat(v||0).toFixed(0) + '%'; }
        function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    })();
    // ── Fim Preview de Rateio ──────────────────────────────────────────────

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            // Sugerir o restante automaticamente
            var total = 0;
            document.querySelectorAll('input[name="pagamentos[valor][]"]').forEach(function(i) {
                var v = parseFloat(i.value.replace(',', '.'));
                if (!isNaN(v)) total += v;
            });
            var restante = Math.max(0, valorTotal - total);
            container.appendChild(criarLinhaPagamento(restante > 0 ? restante : null));
            recalcular();
        });
    }

    // Criar primeira linha automaticamente com o valor total pré-preenchido
    if (container) {
        container.appendChild(criarLinhaPagamento(valorTotal > 0 ? valorTotal : null));
        recalcular();
        atualizarTotalComJuros();
    }

    // Submit do form
    var formPag = document.getElementById('form-pagamento');
    if (formPag) {
        formPag.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = formPag.querySelector('button[type="submit"]');

            // Validar soma
            var totalPago = 0;
            document.querySelectorAll('input[name="pagamentos[valor][]"]').forEach(function(i) {
                var v = parseFloat(i.value.replace(',', '.'));
                if (!isNaN(v)) totalPago += v;
            });

            if (valorTotal > 0 && Math.abs(totalPago - valorTotal) > 0.01) {
                showToast('A soma dos pagamentos (R$ ' + totalPago.toFixed(2).replace('.',',') + ') deve ser igual ao total (R$ ' + valorTotal.toFixed(2).replace('.',',') + ').', 'error');
                return;
            }
            if (totalPago <= 0) {
                showToast('Informe pelo menos um valor de pagamento.', 'error');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="margin-right:6px;"></i>Processando...';

            // Normalizar vírgulas para pontos — remover tudo que não seja dígito ou ponto
            document.querySelectorAll('input[name="pagamentos[valor][]"]').forEach(function(i) {
                var v = i.value.trim().replace(',', '.');
                // Garantir que só tem um ponto decimal
                var partes = v.split('.');
                if (partes.length > 2) v = partes[0] + '.' + partes.slice(1).join('');
                i.value = parseFloat(v).toFixed(2);
            });

            var fd = new FormData(formPag);

            fetch(formPag.getAttribute('action'), { method: 'POST', body: fd })
                .then(function(r) {
                    return r.text();
                })
                .then(function(txt) {
                    var data;
                    try { data = JSON.parse(txt); }
                    catch(ex) { throw new Error('Resposta inválida do servidor: ' + txt.substring(0, 200)); }

                    if (data.sucesso) {
                        showToast(data.mensagem || 'Pagamento confirmado!', 'success');
                        setTimeout(function() {
                            window.location.href = window.__BASE_URL + '?rota=painel';
                        }, 1500);
                    } else {
                        showToast(data.erro || 'Erro desconhecido.', 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-check" style="margin-right:6px;"></i>Confirmar Pagamento';
                    }
                })
                .catch(function(err) {
                    console.error('Pagamento erro:', err);
                    showToast('Erro de comunicação: ' + err.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check" style="margin-right:6px;"></i>Confirmar Pagamento';
                });
        });
    }

    function showToast(message, type = 'success') {
        const toast = $('#toast-notification');
        
        toast.text(message).removeClass('success error').addClass(type).addClass('show');
        
        setTimeout(() => {
            toast.removeClass('show');
        }, 5000);
    }
});
</script>

<style>
.busca-wrapper { position: relative; }
.busca-dropdown {
    display:none; position:absolute; top:100%; left:0; right:0;
    background:#fff; border:1px solid #ccc; border-radius:4px;
    max-height:240px; overflow-y:auto; margin:2px 0 0; padding:0;
    list-style:none; z-index:99999; box-shadow:0 4px 12px rgba(0,0,0,.15);
}
.busca-status { font-size:12px; color:#888; margin-top:3px; display:block; min-height:16px; }
</style>

<script>
(function() {
    // ── buscaLiveTable: filtra uma <table> nos dados já renderizados ──────────
    window.buscaLiveTable = function(cfg) {
        // cfg: { inputId, tableId, colIndex, rota, mes }
        var input   = document.getElementById(cfg.inputId);
        var table   = document.getElementById(cfg.tableId);
        if (!input || !table) return;

        // Previne submit do form pai ao pressionar Enter
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') e.preventDefault();
        });

        var _t = null;
        input.addEventListener('input', function() {
            clearTimeout(_t);
            var term = this.value.toLowerCase().trim();
            _t = setTimeout(function() {
                var rows = table.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    if (cfg.colIndex !== undefined) {
                        var cell = row.cells[cfg.colIndex];
                        var txt  = cell ? cell.textContent.toLowerCase() : '';
                        row.style.display = (!term || txt.includes(term)) ? '' : 'none';
                    } else {
                        // Busca em todas as colunas
                        var txt = row.textContent.toLowerCase();
                        row.style.display = (!term || txt.includes(term)) ? '' : 'none';
                    }
                });
            }, 150);
        });
    };

    // ── buscaLiveDropdown: dropdown de pacientes via AJAX ────────────────────
    window.buscaLiveDropdown = function(cfg) {
        // cfg: { inputId, listId, statusId, onSelect }
        var input  = document.getElementById(cfg.inputId);
        var lista  = document.getElementById(cfg.listId);
        var status = cfg.statusId ? document.getElementById(cfg.statusId) : null;
        if (!input || !lista) return;

        // Previne submit do form pai ao pressionar Enter
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); lista.style.display='none'; }
        });

        var _t = null;
        input.addEventListener('input', function() {
            var term = this.value;
            clearTimeout(_t);
            lista.innerHTML = ''; lista.style.display = 'none';
            if (!term) { if(status) status.textContent = ''; return; }
            if(status) status.textContent = 'Buscando...';
            _t = setTimeout(function() {
                fetch(window.__BASE_URL + 'ajax/buscar_paciente.php?term=' + encodeURIComponent(term))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        lista.innerHTML = '';
                        if (!data.length) {
                            if(status) status.textContent = 'Nenhum paciente encontrado.';
                            lista.style.display = 'none';
                            if (cfg.onNone) cfg.onNone(term);
                            return;
                        }
                        data.forEach(function(p) {
                            var li = document.createElement('li');
                            li.style.cssText = 'padding:10px 14px;cursor:pointer;border-bottom:1px solid #eee;font-size:14px;';
                            li.innerHTML = '<strong>' + esc(p.nome) + '</strong>'
                                + (p.cpf      ? ' <span style="color:#999;font-size:12px;"> · CPF: '  + esc(p.cpf)      + '</span>' : '')
                                + (p.telefone ? ' <span style="color:#999;font-size:12px;"> · '        + esc(p.telefone) + '</span>' : '');
                            li.onmouseover = function(){ this.style.background='#f0f4ff'; };
                            li.onmouseout  = function(){ this.style.background=''; };
                            li.onmousedown = function(e) {
                                e.preventDefault();
                                lista.style.display = 'none';
                                if (cfg.onSelect) cfg.onSelect(p);
                            };
                            lista.appendChild(li);
                        });
                        lista.style.display = 'block';
                        if(status) status.textContent = data.length + ' encontrado(s).';
                    })
                    .catch(function(err){ if(status) status.textContent = 'Sem conexão com o servidor.'; console.error('Busca AJAX erro:', err); });
            }, 220);
        });

        // Fechar ao clicar fora
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !lista.contains(e.target))
                lista.style.display = 'none';
        });
    };

    function esc(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
})();
</script>

<script>
var _tOdonto = null;
function buscaOdonto(term) {
    var lista  = document.getElementById('drop_odonto');
    var status = document.getElementById('status_odonto');
    clearTimeout(_tOdonto);
    lista.innerHTML = ''; lista.style.display = 'none';
    if (!term) { status.innerHTML = 'Digite o nome para buscar o paciente.'; return; }
    status.innerHTML = 'Buscando...';
    _tOdonto = setTimeout(function() {
        fetch(window.__BASE_URL + 'ajax/buscar_paciente.php?term=' + encodeURIComponent(term))
            .then(function(r){ return r.json(); })
            .then(function(data) {
                lista.innerHTML = '';
                if (!data.length) { status.innerHTML = 'Nenhum paciente encontrado.'; return; }
                data.forEach(function(p) {
                    var li = document.createElement('li');
                    li.style.cssText = 'padding:10px 14px;cursor:pointer;border-bottom:1px solid #eee;font-size:14px;';
                    li.innerHTML = '<strong>' + (p.nome||'').replace(/</g,'&lt;') + '</strong>'
                        + (p.cpf ? ' <span style="color:#999;font-size:12px;"> · CPF: '+p.cpf+'</span>' : '');
                    li.onmouseover = function(){ this.style.background='#f0f4ff'; };
                    li.onmouseout  = function(){ this.style.background=''; };
                    li.onmousedown = function(e) {
                        e.preventDefault();
                        window.location.href = window.__BASE_URL + '?rota=atendimentos.pagamento&paciente_id=' + p.id + '&paciente_nome=' + encodeURIComponent(p.nome);
                    };
                    lista.appendChild(li);
                });
                lista.style.display = 'block';
                status.innerHTML = data.length + ' encontrado(s). Clique para confirmar pagamento.';
            })
            .catch(function(){ status.innerHTML = 'Erro na busca.'; });
    }, 220);
}
document.addEventListener('click', function(e) {
    var lista = document.getElementById('drop_odonto');
    var input = document.getElementById('paciente_busca_odonto');
    if (lista && input && !input.contains(e.target) && !lista.contains(e.target))
        lista.style.display = 'none';
});
// Remover o autocomplete jQuery UI herdado se ainda existir
if (typeof $ !== 'undefined' && $('#paciente_busca_odonto').autocomplete)
    try { $('#paciente_busca_odonto').autocomplete('destroy'); } catch(e) {}
</script>
