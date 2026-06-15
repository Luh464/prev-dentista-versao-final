<div class="card">
    <h2>Relatório de Desempenho por Dentista</h2>

    <form method="GET" action="<?= BASE_URL ?>index.php" class="card" style="margin-top: 1rem;">
        <input type="hidden" name="rota" value="relatorios.dentistas">
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <div class="form-group">
                <label for="inicio">Data Início</label>
                <input type="date" name="inicio" id="inicio" value="<?= $data_inicio ?>">
            </div>
            <div class="form-group">
                <label for="fim">Data Fim</label>
                <input type="date" name="fim" id="fim" value="<?= $data_fim ?>">
            </div>
            <?php if (is_admin()): ?>
            <div class="form-group">
                <label for="dentista_id">Dentista</label>
                <select name="dentista_id" id="dentista_id">
                    <option value="todos">Todos</option>
                    <?php foreach ($dentistas as $dentista): ?>
                        <option value="<?= $dentista['id'] ?>" <?= $dentista_id == $dentista['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dentista['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div style="margin-top: 2rem;">
        <table class="mobile-card-table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Dentista</th>
                    <th>Nº de Atendimentos</th>
                    <th>Faturamento Bruto</th>
                    <th>Valor p/ Dentista</th>
                    <th>Valor p/ Clínica</th>
                    <th style="text-align:center;">Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($relatorio_dentistas) > 0): ?>
                    <?php foreach($relatorio_dentistas as $rel): ?>
                    <tr>
                        <td data-label = "Dentista"><?= htmlspecialchars($rel['dentista_nome']) ?></td>
                        <td data-label = "Atendimentos"><?= $rel['total_atendimentos'] ?></td>
                        <td data-label = "Faturamento Bruto">R$ <?= number_format($rel['faturamento_bruto'], 2, ',', '.') ?></td>
                        <td data-label = "Faturamento Dentista" style="color: var(--success-color); font-weight: bold;">
                            <?php if ((float)$rel['valor_para_dentista'] > 0): ?>
                                R$ <?= number_format($rel['valor_para_dentista'], 2, ',', '.') ?>
                            <?php else: ?>
                                <span style="color:#999;font-weight:normal;font-size:.85rem;" title="Rateio calculado apenas após confirmação do pagamento">— <i class="fa-solid fa-circle-info"></i></span>
                            <?php endif; ?>
                        </td>
                        <td data-label = "Faturamento Clínica" style="color: var(--success-color); font-weight: bold;">
                            <?php if ((float)$rel['valor_para_clinica'] > 0): ?>
                                R$ <?= number_format($rel['valor_para_clinica'], 2, ',', '.') ?>
                            <?php else: ?>
                                <span style="color:#999;font-weight:normal;font-size:.85rem;" title="Rateio calculado apenas após confirmação do pagamento">— <i class="fa-solid fa-circle-info"></i></span>
                            <?php endif; ?>
                        </td>
                        <td data-label = "Detalhes" style="text-align:center;">
                            <button type="button" class="btn-ver-detalhe"
                                    data-dentista-id="<?= $rel['dentista_id'] ?>"
                                    data-dentista-nome="<?= htmlspecialchars($rel['dentista_nome']) ?>"
                                    title="Ver detalhamento do período">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px;">Nenhum dado encontrado para os filtros selecionados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== MODAL DE DETALHAMENTO POR DENTISTA ===== -->
<div id="modalDetalheDentista" class="dd-modal-overlay" onclick="if(event.target===this) fecharModalDetalhe()">
    <div class="dd-modal-box">
        <button type="button" class="dd-modal-close" onclick="fecharModalDetalhe()" aria-label="Fechar">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div id="dd_loading" class="dd-loading">
            <i class="fa-solid fa-spinner fa-spin"></i> Carregando...
        </div>

        <div id="dd_conteudo" style="display:none;">
            <div class="dd-header">
                <i class="fa-solid fa-user-doctor dd-header-icon"></i>
                <div>
                    <h3 id="dd_nome"></h3>
                    <span id="dd_periodo" class="dd-periodo"></span>
                </div>
            </div>

            <!-- Cards resumo -->
            <div class="dd-cards">
                <div class="dd-card">
                    <span class="dd-card-label">Faturamento Bruto</span>
                    <span class="dd-card-value" id="dd_faturamento">R$ 0,00</span>
                </div>
                <div class="dd-card dd-card-ganho">
                    <span class="dd-card-label">Ganho do Dentista</span>
                    <span class="dd-card-value" id="dd_ganho">R$ 0,00</span>
                    <span class="dd-card-sub" id="dd_percentual"></span>
                </div>
                <div class="dd-card">
                    <span class="dd-card-label">Valor p/ Clínica</span>
                    <span class="dd-card-value" id="dd_clinica">R$ 0,00</span>
                </div>
            </div>

            <!-- Status da meta -->
            <div id="dd_meta_box" class="dd-meta-box">
                <i class="fa-solid fa-bullseye"></i>
                <span id="dd_meta_texto"></span>
            </div>

            <!-- Gráfico -->
            <h4 class="dd-subtitle">Como o faturamento se divide</h4>
            <div class="dd-chart-wrap">
                <canvas id="dd_chart" height="220" style="max-width:320px;"></canvas>
            </div>

            <!-- Tabela detalhada -->
            <h4 class="dd-subtitle">Atendimentos no período</h4>
            <div class="dd-table-wrap">
                <table class="dd-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Paciente</th>
                            <th>Procedimento</th>
                            <th>Valor</th>
                            <th>Recebido</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody id="dd_tbody"></tbody>
                </table>
            </div>
        </div>

        <div id="dd_erro" class="dd-erro" style="display:none;"></div>
    </div>
</div>

<style>
.btn-ver-detalhe {
    background: var(--primary-color, #005b96);
    color: #fff;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 0.95rem;
    transition: all .15s;
}
.btn-ver-detalhe:hover {
    background: #0370b5;
    transform: scale(1.08);
}

/* Modal overlay */
.dd-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(20,30,40,0.55);
    backdrop-filter: blur(3px);
    z-index: 9000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.dd-modal-overlay.open { display: flex; }

.dd-modal-box {
    background: #fff;
    width: 100%;
    max-width: 760px;
    max-height: 90vh;
    overflow-y: auto;
    border-radius: 16px;
    padding: 28px 28px 24px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.25);
    position: relative;
    animation: ddPopIn .25s ease;
}
@keyframes ddPopIn {
    from { opacity:0; transform: translateY(16px) scale(.97); }
    to   { opacity:1; transform: translateY(0) scale(1); }
}

.dd-modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #f0f2f5;
    border: none;
    cursor: pointer;
    color: #555;
    font-size: 1rem;
    transition: all .15s;
}
.dd-modal-close:hover { background: #e2e6ea; color: #222; }

.dd-loading {
    text-align: center;
    padding: 60px 0;
    color: var(--primary-color, #005b96);
    font-size: 1.1rem;
}

.dd-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
    padding-right: 30px;
}
.dd-header-icon {
    font-size: 2rem;
    color: var(--primary-color, #005b96);
    background: #eef4fb;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.dd-header h3 { margin: 0; font-size: 1.25rem; color: #222; }
.dd-periodo { font-size: 0.85rem; color: #888; }

.dd-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 18px;
}
.dd-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    border-left: 4px solid var(--primary-color, #005b96);
}
.dd-card-ganho { border-left-color: #27ae60; background: #eafaf1; }
.dd-card-label { font-size: 0.75rem; color: #777; text-transform: uppercase; letter-spacing: .03em; }
.dd-card-value { font-size: 1.25rem; font-weight: 700; color: #222; }
.dd-card-ganho .dd-card-value { color: #27ae60; }
.dd-card-sub { font-size: 0.75rem; color: #27ae60; font-weight: 600; }

.dd-meta-box {
    display: flex;
    align-items: center;
    gap: 10px;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 18px;
    font-size: 0.9rem;
}
.dd-meta-box.meta-batida { background: #eafaf1; color: #1e8449; }
.dd-meta-box.meta-nao-batida { background: #fdf3e8; color: #b9770e; }
.dd-meta-box.meta-sem-regra { background: #f0f2f5; color: #777; }
.dd-meta-box i { font-size: 1.1rem; }

.dd-chart-wrap { margin-bottom: 24px; height: 260px; position: relative; display:flex; justify-content:center; }

.dd-subtitle { font-size: 1rem; color: #333; margin-bottom: 10px; }

.dd-table-wrap { overflow-x: auto; }
.dd-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}
.dd-table th {
    text-align: left;
    background: #f8fafc;
    padding: 8px 10px;
    color: #777;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: .03em;
    white-space: nowrap;
}
.dd-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #f0f2f5;
    white-space: nowrap;
}
.dd-table td.dd-cat-especializado { color: #8e44ad; font-weight: 600; }
.dd-table td.dd-recebido { color: #27ae60; font-weight: 700; }

.dd-erro {
    text-align: center;
    padding: 40px 0;
    color: #c0392b;
}

@media (max-width: 600px) {
    .dd-cards { grid-template-columns: 1fr; }
    .dd-modal-box { padding: 20px 16px 18px; }
}
</style>

<script>
var ddChartInstance = null;

document.querySelectorAll('.btn-ver-detalhe').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var dentistaId = this.dataset.dentistaId;
        var nome       = this.dataset.dentistaNome;
        abrirModalDetalhe(dentistaId, nome);
    });
});

function abrirModalDetalhe(dentistaId, nome) {
    var overlay = document.getElementById('modalDetalheDentista');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';

    document.getElementById('dd_loading').style.display  = 'block';
    document.getElementById('dd_conteudo').style.display = 'none';
    document.getElementById('dd_erro').style.display     = 'none';

    var inicio = document.getElementById('inicio').value;
    var fim    = document.getElementById('fim').value;

    var url = window.__BASE_URL + 'ajax/detalhe_dentista.php'
        + '?dentista_id=' + encodeURIComponent(dentistaId)
        + '&inicio=' + encodeURIComponent(inicio)
        + '&fim=' + encodeURIComponent(fim);

    fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('dd_loading').style.display = 'none';

            if (data.erro) {
                document.getElementById('dd_erro').textContent = data.erro;
                document.getElementById('dd_erro').style.display = 'block';
                return;
            }

            renderizarDetalhe(data);
            document.getElementById('dd_conteudo').style.display = 'block';
        })
        .catch(function(err) {
            document.getElementById('dd_loading').style.display = 'none';
            document.getElementById('dd_erro').textContent = 'Erro ao carregar dados: ' + err.message;
            document.getElementById('dd_erro').style.display = 'block';
        });
}

function fecharModalDetalhe() {
    document.getElementById('modalDetalheDentista').classList.remove('open');
    document.body.style.overflow = '';
    if (ddChartInstance) { ddChartInstance.destroy(); ddChartInstance = null; }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fecharModalDetalhe();
});

function moeda(v) {
    return 'R$ ' + (parseFloat(v) || 0).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function renderizarDetalhe(data) {
    document.getElementById('dd_nome').textContent = data.dentista_nome;

    var di = data.periodo_inicio.split('-').reverse().join('/');
    var df = data.periodo_fim.split('-').reverse().join('/');
    document.getElementById('dd_periodo').textContent = di + ' a ' + df;

    document.getElementById('dd_faturamento').textContent = moeda(data.faturamento);
    document.getElementById('dd_ganho').textContent       = moeda(data.total_ganho);
    document.getElementById('dd_clinica').textContent     = moeda(data.total_clinica);

    if (data.percentual_atual !== null && data.faturamento > 0) {
        var pctReal = (data.total_ganho / data.faturamento * 100).toFixed(1);
        document.getElementById('dd_percentual').textContent = '≈ ' + pctReal + '% do faturamento';
    } else {
        document.getElementById('dd_percentual').textContent = '';
    }

    // Caixa de meta
    var metaBox   = document.getElementById('dd_meta_box');
    var metaTexto = document.getElementById('dd_meta_texto');
    metaBox.className = 'dd-meta-box';

    if (data.meta === null) {
        metaBox.classList.add('meta-sem-regra');
        metaTexto.innerHTML = '<i class="fa-solid fa-circle-info"></i> Nenhuma regra de comissão configurada — usando percentual padrão (20%).';
    } else if (data.bateu_meta) {
        metaBox.classList.add('meta-batida');
        metaTexto.innerHTML = '<i class="fa-solid fa-circle-check"></i> Meta de ' + moeda(data.meta) + ' <strong>atingida!</strong> '
            + 'Recebendo <strong>' + data.percentual_atual.toFixed(0) + '%</strong> em procedimentos gerais (percentual acima da meta).';
    } else {
        var faltam = data.meta - data.faturamento;
        metaBox.classList.add('meta-nao-batida');
        metaTexto.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Meta de ' + moeda(data.meta) + ' <strong>ainda não atingida</strong>. '
            + 'Faltam <strong>' + moeda(faltam) + '</strong> para o percentual subir. '
            + 'Atualmente recebendo <strong>' + (data.percentual_atual !== null ? data.percentual_atual.toFixed(0) : '20') + '%</strong> em procedimentos gerais.';
    }

    // Gráfico de pizza: como o faturamento bruto se divide
    var ctx = document.getElementById('dd_chart').getContext('2d');
    if (ddChartInstance) ddChartInstance.destroy();

    var ganho   = parseFloat(data.total_ganho)   || 0;
    var clinica = parseFloat(data.total_clinica) || 0;

    if (data.faturamento <= 0) {
        document.getElementById('dd_chart').parentElement.innerHTML = '<p style="text-align:center;color:#999;padding-top:80px;">Nenhum atendimento no período para exibir no gráfico.</p>';
    } else {
        ddChartInstance = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Ganho do Dentista', 'Valor p/ Clínica'],
                datasets: [{
                    data: [ganho, clinica],
                    backgroundColor: ['rgba(39,174,96,0.85)', 'rgba(0,91,150,0.75)'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 12 }, padding: 16 } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                var total = ctx.dataset.data.reduce(function(a,b){ return a+b; }, 0);
                                var pct = total > 0 ? (ctx.parsed / total * 100).toFixed(1) : 0;
                                return ctx.label + ': ' + moeda(ctx.parsed) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Tabela
    var tbody = document.getElementById('dd_tbody');
    tbody.innerHTML = '';

    if (data.detalhe.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#999;padding:20px;">Nenhum atendimento no período.</td></tr>';
    } else {
        data.detalhe.forEach(function(d) {
            var dataFmt = d.data_atendimento.split(' ')[0].split('-').reverse().join('/');
            var catClass = d.categoria === 'especializado' ? 'dd-cat-especializado' : '';
            var pct = parseFloat(d.percentual_aplicado);
            var pctTexto = (d.tipo_participacao === 'clinico_geral' || d.tipo_participacao === '')
                ? (data.percentual_atual !== null ? data.percentual_atual.toFixed(0) + '%' : '—')
                : (pct > 0 ? pct.toFixed(0) + '%' : '—');

            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + dataFmt + '</td>' +
                '<td>' + escapeHtml(d.paciente_nome) + '</td>' +
                '<td class="' + catClass + '">' + escapeHtml(d.procedimento_nome) + '</td>' +
                '<td>' + moeda(d.valor_procedimento) + '</td>' +
                '<td class="dd-recebido">' + moeda(d.valor_recebido) + '</td>' +
                '<td>' + pctTexto + '</td>';
            tbody.appendChild(tr);
        });
    }
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}
</script>
