-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 04/06/2026 às 16:26
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `clinica_prev_dentistas`
-- (nome mantido do projeto original)
--
CREATE DATABASE IF NOT EXISTS `clinica_prev_dentistas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `clinica_prev_dentistas`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `atendimentos`
--

CREATE TABLE `atendimentos` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `data_atendimento` datetime NOT NULL,
  `valor_total` decimal(10,2) DEFAULT 0.00,
  `taxa_cartao` decimal(10,2) DEFAULT 0.00,
  `custo_auxiliar` decimal(10,2) DEFAULT 0.00,
  `valor_liquido_clinica` decimal(10,2) DEFAULT 0.00,
  `status_pagamento` enum('pendente','parcial','pago') DEFAULT 'pendente',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `dentista_indicador_id` int(11) DEFAULT NULL,
  `dentista_executor_id` int(11) DEFAULT NULL,
  `url_arquivo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `atendimento_pagamentos`
--

CREATE TABLE `atendimento_pagamentos` (
  `id` int(11) NOT NULL,
  `atendimento_id` int(11) NOT NULL,
  `forma_pagamento` enum('dinheiro','pix','debito','credito') NOT NULL,
  `valor_recebido` decimal(10,2) NOT NULL,
  `qtd_parcelas` int(11) DEFAULT 1,
  `bandeira` varchar(45) DEFAULT NULL,
  `taxa_aplicada` decimal(10,2) DEFAULT NULL,
  `taxa_cartao_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `atendimento_procedimentos`
--

CREATE TABLE `atendimento_procedimentos` (
  `id` int(11) NOT NULL,
  `atendimento_id` int(11) NOT NULL,
  `procedimento_id` int(11) NOT NULL,
  `quantidade` int(11) DEFAULT 1,
  `valor_procedimento` decimal(10,2) NOT NULL,
  `custo_auxiliar` decimal(10,2) DEFAULT 0.00,
  `natureza` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `dentista_executor_id` int(11) DEFAULT NULL COMMENT 'Dentista que executou este procedimento específico (recebe % especialista)',
  `dentista_indicador_id` int(11) DEFAULT NULL COMMENT 'Dentista clínico geral que captou/indicou este procedimento (recebe 10%)',
  `local` varchar(100) DEFAULT NULL,
  `url_arquivo` text DEFAULT NULL,
  `status_execucao` enum('pendente','em_andamento','concluido','cancelado') DEFAULT 'pendente',
  `data_execucao` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `comissao`
-- CORREÇÃO 1: dentista_id agora é NULL para suportar regra geral (vale para todos)
-- Quando tipo_regra = 'geral'      → dentista_id deve ser NULL
-- Quando tipo_regra = 'individual' → dentista_id deve ser preenchido
--

CREATE TABLE `comissao` (
  `id` int(11) NOT NULL,
  `dentista_id` int(11) DEFAULT NULL COMMENT 'NULL = regra geral (todos); preenchido = regra individual por dentista',
  `tipo_regra` enum('geral','individual') NOT NULL,
  `teto_meta` decimal(10,2) NOT NULL,
  `percentual_abaixo` decimal(10,2) NOT NULL,
  `percentual_acima` decimal(10,2) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `despesas`
--

CREATE TABLE `despesas` (
  `id` int(11) NOT NULL,
  `descricao` varchar(150) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `tipo` enum('fixa','variavel') NOT NULL,
  `data_despesa` date NOT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_precos`
--

CREATE TABLE `historico_precos` (
  `id` int(11) NOT NULL,
  `procedimento_id` int(11) NOT NULL,
  `valor_anterior` decimal(10,2) NOT NULL,
  `valor_novo` decimal(10,2) NOT NULL,
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_rateio`
--

CREATE TABLE `historico_rateio` (
  `id` int(11) NOT NULL,
  `atendimento_procedimento_id` int(11) NOT NULL,
  `dentista_id` int(11) DEFAULT NULL,
  `tipo_participacao` enum('especialista','indicador','clinica','clinico_geral') NOT NULL COMMENT 'Papel exercido: especialista = executor especializado, clinico_geral = clínico que indicou, indicador = dentista externo que captou, clinica = parte retida pela clínica',
  `contexto_descricao` varchar(150) DEFAULT NULL COMMENT 'Descrição legível para relatório. Ex: "Endodontista – Canal Radicular", "Clínico Geral – Indicação"',
  `percentual_aplicado` decimal(10,2) NOT NULL,
  `valor_procedimento` decimal(10,2) NOT NULL,
  `valor_recebido` decimal(10,2) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `foto_paciente` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `procedimentos`
--

CREATE TABLE `procedimentos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `categoria` enum('geral','especializado','protese') NOT NULL,
  `tipo` text DEFAULT NULL,
  `valor_base` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `regras_rateio`
--

CREATE TABLE `regras_rateio` (
  `id` int(11) NOT NULL,
  `procedimento_id` int(11) NOT NULL,
  `percentual_especialista` decimal(10,2) NOT NULL,
  `percentual_indicador` decimal(10,2) NOT NULL,
  `percentual_clinica` decimal(10,2) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data de criação do registro para auditoria',
  `alterado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Última modificação'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger trg_desativa_regra_anterior removido: lógica de desativação
-- movida para RateioModel::inserirRegra() no PHP (MariaDB 10.4 não
-- permite trigger fazer UPDATE na própria tabela do INSERT)

-- --------------------------------------------------------

--
-- Estrutura para tabela `taxa_cartao`
-- CORREÇÃO 2: adicionada coluna data_fim para rastrear vigência histórica das taxas
-- CORREÇÃO 3: trigger que desativa taxa anterior ao cadastrar nova (mesmo padrão de regras_rateio)
--

CREATE TABLE `taxa_cartao` (
  `id` int(11) NOT NULL,
  `bandeira` varchar(50) NOT NULL,
  `tipo` enum('debito','credito') NOT NULL,
  `parcelas` int(11) NOT NULL,
  `percentual_taxa` decimal(5,2) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_fim` date DEFAULT NULL COMMENT 'Preenchida automaticamente pelo trigger ao ser substituída por nova taxa',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger trg_desativa_taxa_anterior removido: lógica de desativação
-- movida para TaxaCartaoModel::inserir() no PHP (mesmo motivo acima)

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `login` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('proprietario','recepcionista','dentista') NOT NULL,
  `especialidade` varchar(100) DEFAULT NULL COMMENT 'Ex: clinico_geral, endodontia, ortodontia, implantodontia, cirurgia...',
  `tipo_profissional` enum('clinico_geral','especialista') DEFAULT NULL COMMENT 'Distingue clínico geral de especialista para cálculo de comissão e rateio',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `atendimentos`
--
ALTER TABLE `atendimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_atendimento_paciente` (`paciente_id`),
  ADD KEY `dentista_indicador_id` (`dentista_indicador_id`),
  ADD KEY `dentista_executor_id` (`dentista_executor_id`);

--
-- Índices de tabela `atendimento_pagamentos`
--
ALTER TABLE `atendimento_pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pagamento_atendimento` (`atendimento_id`),
  ADD KEY `fk_pagamento_taxa` (`taxa_cartao_id`);

--
-- Índices de tabela `atendimento_procedimentos`
--
ALTER TABLE `atendimento_procedimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_atendimento_procedimento` (`atendimento_id`),
  ADD KEY `fk_procedimento` (`procedimento_id`),
  ADD KEY `fk_proc_executor` (`dentista_executor_id`),
  ADD KEY `fk_proc_indicador` (`dentista_indicador_id`);

--
-- Índices de tabela `comissao`
--
ALTER TABLE `comissao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comissao_dentista` (`dentista_id`);

--
-- Índices de tabela `despesas`
--
ALTER TABLE `despesas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `historico_precos`
--
ALTER TABLE `historico_precos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_historico_procedimento` (`procedimento_id`);

--
-- Índices de tabela `historico_rateio`
--
ALTER TABLE `historico_rateio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rateio_procedimento` (`atendimento_procedimento_id`),
  ADD KEY `fk_rateio_dentista` (`dentista_id`);

--
-- Índices de tabela `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `procedimentos`
--
ALTER TABLE `procedimentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `regras_rateio`
--
ALTER TABLE `regras_rateio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_regra_ativa_por_procedimento` (`procedimento_id`,`ativo`) COMMENT 'Garante no máximo 1 regra ativa por procedimento. Remover ativo=1 ao criar nova.',
  ADD KEY `fk_regra_procedimento` (`procedimento_id`);

--
-- Índices de tabela `taxa_cartao`
--
ALTER TABLE `taxa_cartao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

ALTER TABLE `atendimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `atendimento_pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `atendimento_procedimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `comissao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `despesas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `historico_precos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `historico_rateio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pacientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `procedimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `regras_rateio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `taxa_cartao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

ALTER TABLE `atendimentos`
  ADD CONSTRAINT `fk_atendimento_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE;

ALTER TABLE `atendimento_pagamentos`
  ADD CONSTRAINT `fk_pagamento_atendimento` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pagamento_taxa` FOREIGN KEY (`taxa_cartao_id`) REFERENCES `taxa_cartao` (`id`) ON DELETE SET NULL;

ALTER TABLE `atendimento_procedimentos`
  ADD CONSTRAINT `fk_atendimento_procedimento` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_proc_executor` FOREIGN KEY (`dentista_executor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_proc_indicador` FOREIGN KEY (`dentista_indicador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_procedimento` FOREIGN KEY (`procedimento_id`) REFERENCES `procedimentos` (`id`);

--
-- Restrições para tabela `comissao`
-- NOTA: FK mantida mas aceita NULL (regra geral não tem dentista específico)
--
ALTER TABLE `comissao`
  ADD CONSTRAINT `fk_comissao_dentista` FOREIGN KEY (`dentista_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `historico_precos`
  ADD CONSTRAINT `fk_historico_procedimento` FOREIGN KEY (`procedimento_id`) REFERENCES `procedimentos` (`id`) ON DELETE CASCADE;

ALTER TABLE `historico_rateio`
  ADD CONSTRAINT `fk_rateio_dentista` FOREIGN KEY (`dentista_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rateio_procedimento` FOREIGN KEY (`atendimento_procedimento_id`) REFERENCES `atendimento_procedimentos` (`id`) ON DELETE CASCADE;

ALTER TABLE `regras_rateio`
  ADD CONSTRAINT `fk_regra_procedimento` FOREIGN KEY (`procedimento_id`) REFERENCES `procedimentos` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
