<?php
// src/View/layout/header.php

function isActive(array|string $rotas): bool {
    $rota_atual = $_GET['rota'] ?? 'painel';
    if (!is_array($rotas)) $rotas = [$rotas];
    foreach ($rotas as $r) {
        if (str_starts_with($rota_atual, $r)) return true;
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clínica Prev Dentistas</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <!-- Font Awesome 6 Free -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .nav-icon { margin-right: 6px; width: 16px; text-align: center; }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <a href="<?= BASE_URL ?>?rota=painel" style="text-decoration:none; color:inherit;">
                <i class="fa-solid fa-tooth"></i> Prev Dentistas
            </a>
        </div>

        <?php if (isset($_SESSION['usuario_id'])): ?>
        <div class="menu-toggle" id="mobile-menu">
            <span></span><span></span><span></span>
        </div>
        <?php endif; ?>

        <nav class="menu" id="navbar-menu">
            <?php if (isset($_SESSION['usuario_id'])): ?>

                <!-- Painel -->
                <a href="<?= BASE_URL ?>?rota=painel" class="<?= isActive('painel') ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-line nav-icon"></i> Painel
                </a>

                <!-- Atendimentos -->
                <div class="dropdown">
                    <a href="javascript:void(0)" class="<?= isActive('atendimentos') ? 'active' : '' ?>">
                        <i class="fa-solid fa-calendar-check nav-icon"></i> Atendimentos <small>▾</small>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?= BASE_URL ?>?rota=atendimentos.novo">
                            <i class="fa-solid fa-file-medical nav-icon"></i> Lançar Procedimento
                        </a>
                        <a href="<?= BASE_URL ?>?rota=atendimentos.pagamento">
                            <i class="fa-solid fa-money-bill-wave nav-icon"></i> Confirmar Pagamento
                        </a>
                    </div>
                </div>

                <!-- Cadastros -->
                <?php if (is_admin() || is_dentista() || is_recepcionista()): ?>
                <div class="dropdown">
                    <a href="javascript:void(0)" class="<?= isActive(['procedimentos','despesas','usuarios','pacientes']) ? 'active' : '' ?>">
                        <i class="fa-solid fa-folder-open nav-icon"></i> Cadastros <small>▾</small>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?= BASE_URL ?>?rota=pacientes">
                            <i class="fa-solid fa-user-injured nav-icon"></i> Pacientes
                        </a>
                        <?php if (is_admin()): ?>
                            <a href="<?= BASE_URL ?>?rota=procedimentos">
                                <i class="fa-solid fa-syringe nav-icon"></i> Procedimentos
                            </a>
                            <a href="<?= BASE_URL ?>?rota=despesas">
                                <i class="fa-solid fa-receipt nav-icon"></i> Despesas
                            </a>
                            <a href="<?= BASE_URL ?>?rota=usuarios">
                                <i class="fa-solid fa-user-doctor nav-icon"></i> Usuários
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Relatórios -->
                <div class="dropdown">
                    <a href="javascript:void(0)" class="<?= isActive('relatorios') ? 'active' : '' ?>">
                        <i class="fa-solid fa-chart-bar nav-icon"></i> Relatórios <small>▾</small>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?= BASE_URL ?>?rota=relatorios.diario">
                            <i class="fa-solid fa-calendar-day nav-icon"></i> Diário
                        </a>
                        <?php if (is_admin() || is_dentista()): ?>
                            <a href="<?= BASE_URL ?>?rota=relatorios.dentistas">
                                <i class="fa-solid fa-stethoscope nav-icon"></i> Por Dentista
                            </a>
                            <a href="<?= BASE_URL ?>?rota=relatorios.paciente">
                                <i class="fa-solid fa-notes-medical nav-icon"></i> Por Paciente
                            </a>
                        <?php endif; ?>
                        <?php if (is_admin()): ?>
                            <a href="<?= BASE_URL ?>?rota=relatorios">
                                <i class="fa-solid fa-coins nav-icon"></i> Financeiro Geral
                            </a>
                            <a href="<?= BASE_URL ?>?rota=relatorios.procedimentos">
                                <i class="fa-solid fa-microscope nav-icon"></i> Por Procedimentos
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Configuração -->
                <a href="<?= BASE_URL ?>?rota=configuracoes" class="<?= isActive('configuracoes') ? 'active' : '' ?>">
                    <i class="fa-solid fa-gear nav-icon"></i> Configuração
                </a>

                <!-- Painel Admin — só proprietário -->
                <?php if (is_admin()): ?>
                <div class="dropdown">
                    <a href="javascript:void(0)" class="<?= isActive('admin') ? 'active' : '' ?>">
                        <i class="fa-solid fa-sliders nav-icon"></i> Painel Admin <small>▾</small>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?= BASE_URL ?>?rota=admin.taxas">
                            <i class="fa-solid fa-credit-card nav-icon"></i> Taxas de Cartão
                        </a>
                        <a href="<?= BASE_URL ?>?rota=admin.comissoes">
                            <i class="fa-solid fa-hand-holding-dollar nav-icon"></i> Comissões
                        </a>
                        <a href="<?= BASE_URL ?>?rota=admin.rateio">
                            <i class="fa-solid fa-scale-balanced nav-icon"></i> Regras de Negócio
                        </a>
                    </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </nav>

        <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="user-menu">
                <span><i class="fa-solid fa-circle-user" style="margin-right:5px;"></i><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
                <a href="<?= BASE_URL ?>?rota=logout" class="btn btn-secondary">
                    <i class="fa-solid fa-right-from-bracket" style="margin-right:4px;"></i>Sair
                </a>
            </div>
        <?php endif; ?>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.getElementById('mobile-menu');
            const navMenu    = document.getElementById('navbar-menu');

            if (menuToggle && navMenu) {
                menuToggle.addEventListener('click', function () {
                    navMenu.classList.toggle('active');
                });
            }

            document.querySelectorAll('.dropdown').forEach(function (dropdown) {
                dropdown.querySelector('a').addEventListener('click', function (e) {
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        const content   = dropdown.querySelector('.dropdown-content');
                        const isVisible = content.style.display === 'block';
                        document.querySelectorAll('.dropdown-content').forEach(c => c.style.display = 'none');
                        content.style.display = isVisible ? 'none' : 'block';
                    }
                });
            });
        });
    </script>

    <main class="container">
    <script>
        window.__BASE_URL = '<?= BASE_URL ?>';
    </script>

