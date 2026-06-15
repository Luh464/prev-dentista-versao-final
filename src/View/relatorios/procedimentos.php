<?php
$totalBruto = array_sum(array_column($procedimentos_relatorio, 'valor_bruto_total'));
?>
<div class="card">
    <h2>Relatório por Procedimentos</h2>

    <form method="GET" action="<?= BASE_URL ?>index.php" class="card" style="margin-top:1rem;">
        <input type="hidden" name="rota" value="relatorios.procedimentos">
        <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
            <div class="form-group">
                <label for="inicio">Data Início</label>
                <input type="date" name="inicio" id="inicio" value="<?= htmlspecialchars($data_inicio) ?>">
            </div>
            <div class="form-group">
                <label for="fim">Data Fim</label>
                <input type="date" name="fim" id="fim" value="<?= htmlspecialchars($data_fim) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <?php if (count($procedimentos_relatorio) > 0): ?>

    <!-- Cards de resumo -->
    <div class="dashboard-grid" style="margin-top:1.5rem;">
        <div class="stat-card">
            <h3>Total Executados</h3>
            <div class="stat-value"><?= $totalProcedimentos ?></div>
            <small style="color:#888;">procedimentos</small>
        </div>
        <div class="stat-card" style="border-left-color:#005b96;">
            <h3>Valor Bruto Gerado</h3>
            <div class="stat-value" style="color:#005b96;">R$ <?= number_format($totalBruto, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color:#27ae60;">
            <h3>Pago a Dentistas</h3>
            <div class="stat-value" style="color:#27ae60;">
                R$ <?= number_format(array_sum(array_column($procedimentos_relatorio, 'valor_dentista')), 2, ',', '.') ?>
            </div>
            <small style="color:#888;">especialistas + clínicos</small>
        </div>
        <div class="stat-card" style="border-left-color:#e67e22;">
            <h3>Retido pela Clínica</h3>
            <div class="stat-value" style="color:#e67e22;">
                R$ <?= number_format(array_sum(array_column($procedimentos_relatorio, 'valor_clinica')), 2, ',', '.') ?>
            </div>
        </div>
    </div>

    <!-- Gráfico de barras empilhadas -->
    <div class="card" style="margin-top:1.5rem;">
        <h3 style="margin-bottom:1rem;"><i class="fa-solid fa-chart-bar" style="margin-right:8px;color:#005b96;"></i>Distribuição de Receita por Procedimento</h3>
        <p style="font-size:13px;color:#888;margin-bottom:1rem;">
            Cada barra mostra o valor bruto gerado, dividido entre o que vai para o dentista executor,
            para indicação e para a clínica.
        </p>
        <canvas id="procChart" style="max-height:420px;"></canvas>
        <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:12px;justify-content:center;font-size:12px;">
            <span><span style="display:inline-block;width:12px;height:12px;background:#005b96;border-radius:2px;margin-right:5px;"></span>Dentista executor</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:#17a2b8;border-radius:2px;margin-right:5px;"></span>Indicação</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:#e67e22;border-radius:2px;margin-right:5px;"></span>Clínica</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:#e0e0e0;border-radius:2px;margin-right:5px;"></span>Não discriminado</span>
        </div>
    </div>

    <!-- Tabela detalhada -->
    <div style="margin-top:2rem;">
        <h3 style="margin-bottom:.5rem;">Detalhamento por Procedimento</h3>
        <table class="mobile-card-table" style="margin-top:1rem;">
            <thead>
                <tr>
                    <th>Procedimento</th>
                    <th>Categoria</th>
                    <th style="text-align:center;">Qtd</th>
                    <th style="text-align:right;">Valor Bruto</th>
                    <th style="text-align:right;">Dentista</th>
                    <th style="text-align:right;">Indicação</th>
                    <th style="text-align:right;">Clínica</th>
                    <th style="text-align:right;">% do Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($procedimentos_relatorio as $proc):
                    $pct = $totalProcedimentos > 0 ? ($proc['quantidade_executada'] / $totalProcedimentos) * 100 : 0;
                    $semRateio = (float)$proc['valor_dentista'] + (float)$proc['valor_clinica'] + (float)$proc['valor_indicacao'] < 0.01;
                ?>
                <tr>
                    <td data-label="Procedimento"><?= htmlspecialchars($proc['procedimento_nome']) ?></td>
                    <td data-label="Categoria">
                        <span style="font-size:11px;padding:2px 8px;border-radius:10px;
                            background:<?= $proc['categoria']==='especializado'?'#e3f2fd':'#e8f5e9' ?>;
                            color:<?= $proc['categoria']==='especializado'?'#005b96':'#2e7d32' ?>;">
                            <?= ucfirst($proc['categoria']) ?>
                        </span>
                    </td>
                    <td data-label="Qtd" style="text-align:center;font-weight:600;"><?= (int)$proc['quantidade_executada'] ?></td>
                    <td data-label="Valor Bruto" style="text-align:right;font-weight:700;color:#005b96;">
                        R$ <?= number_format((float)$proc['valor_bruto_total'], 2, ',', '.') ?>
                    </td>
                    <td data-label="Dentista" style="text-align:right;color:#005b96;">
                        <?php if ($semRateio): ?>
                            <span style="color:#ccc;font-size:11px;">—</span>
                        <?php else: ?>
                            R$ <?= number_format((float)$proc['valor_dentista'], 2, ',', '.') ?>
                        <?php endif; ?>
                    </td>
                    <td data-label="Indicação" style="text-align:right;color:#17a2b8;">
                        <?php if ($semRateio || (float)$proc['valor_indicacao'] < 0.01): ?>
                            <span style="color:#ccc;font-size:11px;">—</span>
                        <?php else: ?>
                            R$ <?= number_format((float)$proc['valor_indicacao'], 2, ',', '.') ?>
                        <?php endif; ?>
                    </td>
                    <td data-label="Clínica" style="text-align:right;color:#e67e22;">
                        <?php if ($semRateio): ?>
                            <span style="color:#ccc;font-size:11px;">—</span>
                        <?php else: ?>
                            R$ <?= number_format((float)$proc['valor_clinica'], 2, ',', '.') ?>
                        <?php endif; ?>
                    </td>
                    <td data-label="% do Total" style="text-align:right;">
                        <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;">
                            <div style="width:60px;height:6px;background:#eee;border-radius:3px;overflow:hidden;">
                                <div style="width:<?= min(100, $pct) ?>%;height:100%;background:#005b96;border-radius:3px;"></div>
                            </div>
                            <?= number_format($pct, 1, ',', '.') ?>%
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:bold;background:#f8f9fa;">
                    <td colspan="2">Total</td>
                    <td style="text-align:center;"><?= $totalProcedimentos ?></td>
                    <td style="text-align:right;color:#005b96;">R$ <?= number_format($totalBruto, 2, ',', '.') ?></td>
                    <td style="text-align:right;color:#005b96;">R$ <?= number_format(array_sum(array_column($procedimentos_relatorio, 'valor_dentista')), 2, ',', '.') ?></td>
                    <td style="text-align:right;color:#17a2b8;">R$ <?= number_format(array_sum(array_column($procedimentos_relatorio, 'valor_indicacao')), 2, ',', '.') ?></td>
                    <td style="text-align:right;color:#e67e22;">R$ <?= number_format(array_sum(array_column($procedimentos_relatorio, 'valor_clinica')), 2, ',', '.') ?></td>
                    <td style="text-align:right;">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script>
    (function() {
        var data = <?= json_encode(array_map(function($p) {
            return [
                'nome'      => mb_strlen($p['procedimento_nome']) > 25
                    ? mb_substr($p['procedimento_nome'], 0, 23) . '…'
                    : $p['procedimento_nome'],
                'bruto'     => (float)$p['valor_bruto_total'],
                'dentista'  => (float)$p['valor_dentista'],
                'indicacao' => (float)$p['valor_indicacao'],
                'clinica'   => (float)$p['valor_clinica'],
            ];
        }, $procedimentos_relatorio)) ?>;

        var labels = data.map(function(d){ return d.nome; });

        new Chart(document.getElementById('procChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Dentista executor',
                        data: data.map(function(d){ return d.dentista; }),
                        backgroundColor: '#005b96',
                        borderRadius: 3
                    },
                    {
                        label: 'Indicação',
                        data: data.map(function(d){ return d.indicacao; }),
                        backgroundColor: '#17a2b8',
                        borderRadius: 3
                    },
                    {
                        label: 'Clínica',
                        data: data.map(function(d){ return d.clinica; }),
                        backgroundColor: '#e67e22',
                        borderRadius: 3
                    },
                    {
                        label: 'Não discriminado',
                        data: data.map(function(d){
                            var alocado = d.dentista + d.indicacao + d.clinica;
                            return alocado < 0.01 ? d.bruto : 0;
                        }),
                        backgroundColor: '#e0e0e0',
                        borderRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': R$ ' + ctx.parsed.y.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                },
                scales: {
                    x: { stacked: true, ticks: { font: { size: 11 } } },
                    y: {
                        stacked: true,
                        ticks: {
                            callback: function(v) { return 'R$ ' + v.toFixed(0); }
                        }
                    }
                }
            }
        });
    })();
    </script>

    <?php else: ?>
        <div style="text-align:center;padding:3rem;color:#aaa;">
            <i class="fa-solid fa-chart-bar" style="font-size:2.5rem;margin-bottom:1rem;display:block;"></i>
            Nenhum procedimento encontrado para o período selecionado.
        </div>
    <?php endif; ?>
</div>
