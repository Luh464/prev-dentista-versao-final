-- ============================================================
-- CORREÇÃO: remover triggers que causam erro 1442 no MariaDB 10.4
-- (MariaDB não permite trigger fazer UPDATE na mesma tabela que
--  disparou o INSERT). A lógica foi movida para o PHP nos Models
--  RateioModel e TaxaCartaoModel.
--
-- Execute este SQL no phpMyAdmin (banco clinica_prev_dentistas)
-- ============================================================

USE `clinica_prev_dentistas`;

DROP TRIGGER IF EXISTS `trg_desativa_regra_anterior`;
DROP TRIGGER IF EXISTS `trg_desativa_taxa_anterior`;
