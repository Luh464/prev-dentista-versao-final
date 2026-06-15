
<div class="card">
    <h2>Relatório Financeiro</h2>

    <form method="GET" action="<?= BASE_URL ?>index.php" class="card" style="margin-top: 1rem;">
        <input type="hidden" name="rota" value="relatorios">
        <div style="display: flex; gap: 1rem; align-items: center;">
            <div class="form-group">
                <label for="inicio">Data Início</label>
                <input type="date" name="inicio" id="inicio" value="<?= $data_inicio ?>">
            </div>
            <div class="form-group">
                <label for="fim">Data Fim</label>
                <input type="date" name="fim" id="fim" value="<?= $data_fim ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div class="dashboard-grid" style="margin-top: 2rem;">
        <div class="stat-card">
            <h3>Faturamento Bruto</h3>
            <div class="stat-value">R$ <?= number_format($financas['bruto'] ?? 0, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--danger-color);">
            <h3>Total Despesas</h3>
            <div class="stat-value">R$ <?= number_format($despesas ?? 0, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--success-color);">
            <h3>Lucro Líquido</h3>
            <div class="stat-value">R$ <?= number_format(($financas['liquido'] ?? 0) - ($despesas ?? 0), 2, ',', '.') ?></div>
        </div>
    </div>

    <!-- Discriminação de rateio por participante -->
    <?php
    $totalEspecialistas = ($rateioTipos['especialista']  ?? 0) + ($rateioTipos['clinico_geral'] ?? 0);
    $totalIndicacao     = $rateioTipos['indicador']      ?? 0;
    $totalClinica       = $rateioTipos['clinica']        ?? 0;
    ?>
    <div class="card" style="margin-top:1.5rem; border-left: 4px solid #005b96;">
        <h3 style="margin-bottom:1rem;">
            <i class="fa-solid fa-coins" style="margin-right:8px;color:#e67e22;"></i>
            Distribuição Financeira do Período
        </h3>
        <p style="font-size:13px;color:#888;margin-bottom:1rem;">
            Discriminação de quanto cada parte recebeu, calculada a partir do histórico de rateio gravado no momento de cada pagamento confirmado.
        </p>
        <div class="dashboard-grid">
            <div class="stat-card" style="border-left-color:#005b96;">
                <h3>Pago a Dentistas</h3>
                <div class="stat-value" style="color:#005b96;">R$ <?= number_format($totalEspecialistas, 2, ',', '.') ?></div>
                <small style="color:#888;">especialistas + clínicos</small>
            </div>
            <div class="stat-card" style="border-left-color:#17a2b8;">
                <h3>Comissão de Indicação</h3>
                <div class="stat-value" style="color:#17a2b8;">R$ <?= number_format($totalIndicacao, 2, ',', '.') ?></div>
                <small style="color:#888;">clínicos que captaram</small>
            </div>
            <div class="stat-card" style="border-left-color:#e67e22;">
                <h3>Retido pela Clínica</h3>
                <div class="stat-value" style="color:#e67e22;">R$ <?= number_format($totalClinica, 2, ',', '.') ?></div>
                <small style="color:#888;">antes de despesas</small>
            </div>
        </div>

        <!-- Tabela por dentista -->
        <?php
        // Agrupar participantes por dentista
        $porDentista = [];
        foreach ($rateioParticipantes as $r) {
            $key = $r['dentista_id'] ?? 'clinica';
            if (!isset($porDentista[$key])) {
                $porDentista[$key] = [
                    'nome'          => $r['dentista_nome'] ?? 'Clínica',
                    'especialista'  => 0,
                    'clinico_geral' => 0,
                    'indicador'     => 0,
                    'clinica'       => 0,
                ];
            }
            $tipo = $r['tipo_participacao'];
            if (in_array($tipo, ['especialista','clinico_geral','indicador','clinica'])) {
                $porDentista[$key][$tipo] += (float)$r['total_recebido'];
            }
        }
        ?>
        <?php if (!empty($porDentista)): ?>
        <table class="mobile-card-table" style="margin-top:1.5rem;">
            <thead>
                <tr>
                    <th>Participante</th>
                    <th style="text-align:right;">Especialista</th>
                    <th style="text-align:right;">Clínico Geral</th>
                    <th style="text-align:right;">Indicação</th>
                    <th style="text-align:right;color:#e67e22;">Total Recebido</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($porDentista as $key => $p):
                    if ($key === 'clinica') continue; // clínica vai no rodapé
                    $totalP = $p['especialista'] + $p['clinico_geral'] + $p['indicador'];
                ?>
                <tr>
                    <td data-label="Participante">
                        <i class="fa-solid fa-user-doctor" style="margin-right:6px;color:#005b96;"></i>
                        <?= htmlspecialchars($p['nome']) ?>
                    </td>
                    <td data-label="Especialista" style="text-align:right;">
                        <?= $p['especialista'] > 0 ? 'R$ ' . number_format($p['especialista'], 2, ',', '.') : '<span style="color:#ccc;">—</span>' ?>
                    </td>
                    <td data-label="Clínico Geral" style="text-align:right;">
                        <?= $p['clinico_geral'] > 0 ? 'R$ ' . number_format($p['clinico_geral'], 2, ',', '.') : '<span style="color:#ccc;">—</span>' ?>
                    </td>
                    <td data-label="Indicação" style="text-align:right;color:#17a2b8;">
                        <?= $p['indicador'] > 0 ? 'R$ ' . number_format($p['indicador'], 2, ',', '.') : '<span style="color:#ccc;">—</span>' ?>
                    </td>
                    <td data-label="Total" style="text-align:right;font-weight:700;color:#005b96;">
                        R$ <?= number_format($totalP, 2, ',', '.') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#fff8f0;font-weight:700;">
                    <td>
                        <i class="fa-solid fa-hospital" style="margin-right:6px;color:#e67e22;"></i>
                        Clínica (retenção)
                    </td>
                    <td colspan="3" style="text-align:right;color:#e67e22;">—</td>
                    <td style="text-align:right;color:#e67e22;">
                        R$ <?= number_format($totalClinica, 2, ',', '.') ?>
                    </td>
                </tr>
                <tr style="background:#f5f5f5;font-weight:800;">
                    <td>Total distribuído</td>
                    <td style="text-align:right;color:#005b96;">
                        R$ <?= number_format(array_sum(array_column($porDentista, 'especialista')), 2, ',', '.') ?>
                    </td>
                    <td style="text-align:right;color:#27ae60;">
                        R$ <?= number_format(array_sum(array_column($porDentista, 'clinico_geral')), 2, ',', '.') ?>
                    </td>
                    <td style="text-align:right;color:#17a2b8;">
                        R$ <?= number_format(array_sum(array_column($porDentista, 'indicador')), 2, ',', '.') ?>
                    </td>
                    <td style="text-align:right;color:#333;">
                        R$ <?= number_format($totalEspecialistas + $totalIndicacao + $totalClinica, 2, ',', '.') ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
            <p style="color:#aaa;font-size:13px;margin-top:1rem;padding:1rem;">
                Nenhum rateio registrado neste período. Os dados aparecem após pagamentos confirmados.
            </p>
        <?php endif; ?>
    </div>

    <div class="chart-buttons" style="margin-top: 2rem; text-align: center; margin-bottom: 1rem; display: flex; justify-content: center; gap: 10px;">
        <button id="btnEvolucao" class="btn btn-primary">Ver Evolução Financeira</button>
        <button id="btnPagamentos" class="btn btn-secondary">Ver Distribuição de Pagamentos</button>
    </div>

    <div id="chart-evolucao-container" style="margin-top: 1rem;">
        <h3>Evolução Financeira</h3>
        <canvas id="evolucaoFinanceiraChart" style="max-height: 400px;"></canvas>
    </div>

    <div id="chart-pagamentos-container" style="margin-top: 1rem; display: none;">
        <h3>Distribuição de Pagamentos</h3>
        <canvas id="pagamentosChart" style="max-height: 400px;"></canvas>
    </div>

    <div style="margin-top: 3rem;">
        <h3>Detalhes de Atendimentos</h3>
        <table class="mobile-card-table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Paciente</th>
                    <th>Procedimento</th>
                    <th>Valor Bruto</th>
                    <th>Valor Líquido</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($atendimentos as $at): ?>
                <tr>
                    <td data-label = "Data"><?= date('d/m/Y', strtotime($at['data_atendimento'])) ?></td>
                    <td data-label = "Paciente"><?= htmlspecialchars($at['paciente_nome']) ?></td>
                    <td data-label = "Procedimento"><?= htmlspecialchars($at['procedimento'] ?? '') ?></td>
                    <td data-label = "Valor Bruto">R$ <?= number_format($at['valor_bruto'], 2, ',', '.') ?></td>
                    <td data-label = "Lucro Líquido">R$ <?= number_format($at['valor_liquido_clinica'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginação Atendimentos -->
        <?php if ($paginasAt > 1): ?>
        <div style="display: flex; justify-content: flex-end; margin-top: 1rem; gap: 0.5rem;">
            <?php for ($i = 1; $i <= $paginasAt; $i++): ?>
                <?php 
                    $active = $i === $pagina_at ? 'background-color: var(--primary-color); color: white;' : 'background-color: #eee; color: #333;';
                    $queryParams = $_GET;
                    $queryParams['pagina_at'] = $i;
                    $url = '?' . http_build_query($queryParams);
                ?>
                <a href="<?= $url ?>" style="padding: 5px 10px; text-decoration: none; border-radius: 4px; <?= $active ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <div style="margin-top: 3rem;">
        <h3>Detalhes de Despesas</h3>
        <table class="mobile-card-table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($listaDespesas as $dp): ?>
                <tr>
                    <td data-label = "Data"><?= date('d/m/Y', strtotime($dp['data_despesa'])) ?></td>
                    <td data-label = "Descrição"><?= htmlspecialchars($dp['descricao']) ?></td>
                    <td data-label = "Tipo"><?= ucfirst($dp['tipo']) ?></td>
                    <td data-label = "Valor">R$ <?= number_format($dp['valor'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginação Despesas -->
        <?php if ($paginasDe > 1): ?>
        <div style="display: flex; justify-content: flex-end; margin-top: 1rem; gap: 0.5rem;">
            <?php for ($i = 1; $i <= $paginasDe; $i++): ?>
                <?php 
                    $active = $i === $pagina_de ? 'background-color: var(--primary-color); color: white;' : 'background-color: #eee; color: #333;';
                    $queryParams = $_GET;
                    $queryParams['pagina_de'] = $i;
                    $url = '?' . http_build_query($queryParams);
                ?>
                <a href="<?= $url ?>" style="padding: 5px 10px; text-decoration: none; border-radius: 4px; <?= $active ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnEvolucao = document.getElementById('btnEvolucao');
    const btnPagamentos = document.getElementById('btnPagamentos');
    const evolucaoContainer = document.getElementById('chart-evolucao-container');
    const pagamentosContainer = document.getElementById('chart-pagamentos-container');

    // Botão para mostrar o gráfico de evolução
    btnEvolucao.addEventListener('click', () => {
        evolucaoContainer.style.display = 'block';
        pagamentosContainer.style.display = 'none';
        
        btnEvolucao.classList.add('btn-primary');
        btnEvolucao.classList.remove('btn-secondary');
        
        btnPagamentos.classList.add('btn-secondary');
        btnPagamentos.classList.remove('btn-primary');
    });

    // Botão para mostrar o gráfico de pagamentos
    btnPagamentos.addEventListener('click', () => {
        evolucaoContainer.style.display = 'none';
        pagamentosContainer.style.display = 'block';

        btnPagamentos.classList.add('btn-primary');
        btnPagamentos.classList.remove('btn-secondary');

        btnEvolucao.classList.add('btn-secondary');
        btnEvolucao.classList.remove('btn-primary');
    });
    const ctx = document.getElementById('evolucaoFinanceiraChart').getContext('2d');
    const evolucaoChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                {
                    label: 'Faturamento Bruto',
                    data: <?= json_encode($faturamentoData) ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true,
                    tension: 0.1
                },
                {
                    label: 'Despesas',
                    data: <?= json_encode($despesaData) ?>,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: true,
                    tension: 0.1
                },
                {
                    label: 'Lucro Líquido',
                    data: <?= json_encode($lucroLiquidoData) ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });

    <?php if (!empty($pagamentoData)): ?>
    const ctxPagamentos = document.getElementById('pagamentosChart').getContext('2d');
    const pagamentosChart = new Chart(ctxPagamentos, {
        type: 'pie',
        data: {
            labels: <?= json_encode($pagamentoLabels) ?>,
            datasets: [{
                label: 'Total R$',
                data: <?= json_encode($pagamentoData) ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',  // Vermelho para Dinheiro/Pix
                    'rgba(54, 162, 235, 0.7)', // Azul para Débito
                    'rgba(255, 206, 86, 0.7)', // Amarelo para Crédito
                    'rgba(75, 192, 192, 0.7)', // Verde para outros
                    'rgba(153, 102, 255, 0.7)',// Roxo
                    'rgba(255, 159, 64, 0.7)'  // Laranja
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: false,
                    text: 'Formas de Pagamento no Período'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed);
                            }
                            return label;
                        },
                        footer: function(tooltipItems) {
                            let sum = tooltipItems[0].chart.getDatasetMeta(0).total;
                            let percentage = (tooltipItems[0].parsed * 100 / sum).toFixed(2) + '%';
                            return 'Porcentagem: ' + percentage;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php  ?>
