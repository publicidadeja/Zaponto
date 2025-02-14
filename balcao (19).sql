-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 14/02/2025 às 18:21
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
-- Banco de dados: `balcao`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `administradores`
--

CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_acesso` enum('admin','super_admin') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `administradores`
--

INSERT INTO `administradores` (`id`, `nome`, `email`, `senha`, `nivel_acesso`, `created_at`) VALUES
(2, 'Administrador', 'admin@exemplo.com', '$2y$10$elO.32JoFmpVrzhmz2W5aeUyElvMSAW7AigOOmzQg8PR2JH8qim9G', 'super_admin', '2025-02-07 19:37:33');

-- --------------------------------------------------------

--
-- Estrutura para tabela `assinaturas`
--

CREATE TABLE `assinaturas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `plano_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `data_inicio` datetime DEFAULT NULL,
  `data_fim` datetime DEFAULT NULL,
  `is_trial` tinyint(1) DEFAULT 0,
  `limite_leads` int(11) DEFAULT 0,
  `limite_mensagens` int(11) DEFAULT 0,
  `tem_ia` tinyint(1) DEFAULT 0,
  `proximo_pagamento` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `assinaturas`
--

INSERT INTO `assinaturas` (`id`, `usuario_id`, `plano_id`, `status`, `stripe_subscription_id`, `data_inicio`, `data_fim`, `is_trial`, `limite_leads`, `limite_mensagens`, `tem_ia`, `proximo_pagamento`) VALUES
(4, 7, 4, 'ativo', NULL, '2025-02-11 20:41:18', '2025-02-18 20:41:18', 1, 100, 100, 1, NULL),
(5, 8, 4, 'ativo', NULL, '2025-02-12 15:51:31', '2025-02-19 15:51:31', 1, 100, 100, 1, NULL),
(6, 9, 4, 'ativo', NULL, '2025-02-12 15:57:42', '2025-02-19 15:57:42', 1, 100, 100, 1, NULL),
(7, 10, 4, 'ativo', NULL, '2025-02-12 16:08:06', '2025-02-19 16:08:06', 1, 100, 100, 1, NULL),
(8, 11, 4, 'ativo', NULL, '2025-02-12 18:23:35', '2025-02-19 18:23:35', 1, 100, 100, 1, NULL),
(9, 12, 4, 'ativo', NULL, '2025-02-12 20:12:41', '2025-02-19 20:12:41', 1, 100, 100, 1, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `nome_site` varchar(100) NOT NULL,
  `email_suporte` varchar(100) NOT NULL,
  `whatsapp_suporte` varchar(20) NOT NULL,
  `tempo_entre_envios` int(11) NOT NULL DEFAULT 30,
  `max_leads_dia` int(11) NOT NULL DEFAULT 1000,
  `max_mensagens_dia` int(11) NOT NULL DEFAULT 1000,
  `termos_uso` text DEFAULT NULL,
  `politica_privacidade` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `nome_site`, `email_suporte`, `whatsapp_suporte`, `tempo_entre_envios`, `max_leads_dia`, `max_mensagens_dia`, `termos_uso`, `politica_privacidade`, `created_at`, `updated_at`) VALUES
(1, 'Zaponto', 'suporte@xzappro.com', '(55) 11999-9999', 30, 1000, 1000, '', '', '2025-02-07 17:30:17', '2025-02-11 15:34:29');

-- --------------------------------------------------------

--
-- Estrutura para tabela `dispositivos`
--

CREATE TABLE `dispositivos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `device_id` varchar(255) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'DISCONNECTED',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `qr_code` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `dispositivos`
--

INSERT INTO `dispositivos` (`id`, `usuario_id`, `device_id`, `nome`, `status`, `created_at`, `qr_code`) VALUES
(2, 1, 'device_67a5dabeaf84a', 'PJ', 'CONNECTED', '2025-02-07 10:04:46', NULL),
(8, 2, 'device_67aab7f01d178', 'Leonardo', 'WAITING_QR', '2025-02-11 02:37:36', NULL),
(11, 2, 'device_67aab930f3341', 'Aline', 'CONNECTED', '2025-02-11 02:42:56', NULL),
(14, 7, 'device_67aba8ea9793a', 'Padaria Avenida', 'WAITING_QR', '2025-02-11 19:45:46', '2@3lbmae7Y6lm7SP0NOCyjk77aXFITcbidn4Eofv/9FxXXfgJReWZTW8scTkwEzEMT39DOoao94UjJ2WZ5Bg0yStVXxbaxlToatUk=,SrVvRgdGkB4iI0ZuU/2PgAX2amIOMr/nK0jUQNs5Ilg=,8qjfmaVz701E4Ax30ah++2wdF53VLzwjYMoNe/3D7zY=,t7Iy9AHuQ0a1C7K0M5+LoaK69kZcuz97Szo24ugralc=,1');

-- --------------------------------------------------------

--
-- Estrutura para tabela `envios_em_massa`
--

CREATE TABLE `envios_em_massa` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `mensagem` text NOT NULL,
  `arquivo` varchar(255) DEFAULT NULL,
  `total_enviados` int(11) DEFAULT 0,
  `status` varchar(50) DEFAULT 'PENDENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fila_mensagens`
--

CREATE TABLE `fila_mensagens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `dispositivo_id` varchar(255) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `mensagem` text NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `status` enum('PENDENTE','ENVIADO','ERRO') DEFAULT 'PENDENTE',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `fila_mensagens`
--

INSERT INTO `fila_mensagens` (`id`, `usuario_id`, `dispositivo_id`, `numero`, `mensagem`, `nome`, `arquivo_path`, `status`, `error_message`, `created_at`, `updated_at`) VALUES
(1, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acaaf71b939_1739369207_Captura de tela 2024-09-26 173202.png', 'ENVIADO', NULL, '2025-02-12 14:06:47', '2025-02-12 14:06:51'),
(2, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acaaf71b939_1739369207_Captura de tela 2024-09-26 173202.png', 'ENVIADO', NULL, '2025-02-12 14:06:47', '2025-02-12 14:06:59'),
(3, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acaaf71b939_1739369207_Captura de tela 2024-09-26 173202.png', 'ENVIADO', NULL, '2025-02-12 14:06:47', '2025-02-12 14:07:09'),
(4, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acab504e67b_1739369296_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 14:08:16', '2025-02-12 14:08:19'),
(5, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acab504e67b_1739369296_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 14:08:16', '2025-02-12 14:08:29'),
(6, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acab504e67b_1739369296_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 14:08:16', '2025-02-12 14:08:39'),
(7, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acacb56a5fb_1739369653_tim.png', 'ENVIADO', NULL, '2025-02-12 14:14:13', '2025-02-12 14:14:16'),
(8, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acacb56a5fb_1739369653_tim.png', 'ENVIADO', NULL, '2025-02-12 14:14:13', '2025-02-12 14:14:25'),
(9, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acacb56a5fb_1739369653_tim.png', 'ENVIADO', NULL, '2025-02-12 14:14:13', '2025-02-12 14:14:31'),
(10, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acacb56a5fb_1739369653_tim.png', 'ENVIADO', NULL, '2025-02-12 14:14:13', '2025-02-12 14:14:38'),
(11, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acad0e1b3f6_1739369742_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 14:15:42', '2025-02-12 14:15:44'),
(12, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acad0e1b3f6_1739369742_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 14:15:42', '2025-02-12 14:15:53'),
(13, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acad0e1b3f6_1739369742_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 14:15:42', '2025-02-12 14:16:04'),
(14, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acaeb9b8f3a_1739370169_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 14:22:49', '2025-02-12 14:22:52'),
(15, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acaeb9b8f3a_1739370169_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 14:22:49', '2025-02-12 14:23:02'),
(16, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acaeb9b8f3a_1739370169_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 14:22:49', '2025-02-12 14:23:12'),
(17, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acaeb9b8f3a_1739370169_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 14:22:49', '2025-02-12 14:23:19'),
(18, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acaeb9b8f3a_1739370169_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 14:22:49', '2025-02-12 14:23:30'),
(19, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acaf41bae50_1739370305_tim.png', 'ENVIADO', NULL, '2025-02-12 14:25:05', '2025-02-12 14:25:08'),
(20, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acaf41bae50_1739370305_tim.png', 'ENVIADO', NULL, '2025-02-12 14:25:05', '2025-02-12 14:25:15'),
(21, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '', 'ENVIADO', NULL, '2025-02-12 14:27:10', '2025-02-12 14:27:11'),
(22, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb09bbdac3_1739370651_logo.png', 'ENVIADO', NULL, '2025-02-12 14:30:51', '2025-02-12 14:30:58'),
(23, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb09bbdac3_1739370651_logo.png', 'ENVIADO', NULL, '2025-02-12 14:30:51', '2025-02-12 14:31:08'),
(24, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb09bbdac3_1739370651_logo.png', 'ENVIADO', NULL, '2025-02-12 14:30:51', '2025-02-12 14:31:18'),
(25, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb09bbdac3_1739370651_logo.png', 'ENVIADO', NULL, '2025-02-12 14:30:51', '2025-02-12 14:31:26'),
(26, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb09bbdac3_1739370651_logo.png', 'ENVIADO', NULL, '2025-02-12 14:30:51', '2025-02-12 14:31:36'),
(27, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb09cc6cee_1739370652_logo.png', 'ENVIADO', NULL, '2025-02-12 14:30:52', '2025-02-12 14:31:46'),
(28, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb09cc6cee_1739370652_logo.png', 'ENVIADO', NULL, '2025-02-12 14:30:52', '2025-02-12 14:31:57'),
(29, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb09cc6cee_1739370652_logo.png', 'ENVIADO', NULL, '2025-02-12 14:30:52', '2025-02-12 14:32:07'),
(30, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb09cc6cee_1739370652_logo.png', 'ENVIADO', NULL, '2025-02-12 14:30:52', '2025-02-12 14:32:14'),
(31, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb09cc6cee_1739370652_logo.png', 'ENVIADO', NULL, '2025-02-12 14:30:52', '2025-02-12 14:32:20'),
(32, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '', 'ENVIADO', NULL, '2025-02-12 14:33:53', '2025-02-12 14:33:54'),
(33, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '', 'ENVIADO', NULL, '2025-02-12 14:33:53', '2025-02-12 14:33:59'),
(34, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '', 'ENVIADO', NULL, '2025-02-12 14:33:54', '2025-02-12 14:33:59'),
(35, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '', 'ENVIADO', NULL, '2025-02-12 14:33:54', '2025-02-12 14:34:07'),
(36, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb2170a2e9_1739371031_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 14:37:11', '2025-02-12 14:37:13'),
(37, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb2170a2e9_1739371031_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 14:37:11', '2025-02-12 14:37:21'),
(38, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb2170a2e9_1739371031_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 14:37:11', '2025-02-12 14:37:29'),
(39, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb2170a2e9_1739371031_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 14:37:11', '2025-02-12 14:37:37'),
(40, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acb2170a2e9_1739371031_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 14:37:11', '2025-02-12 14:37:46'),
(41, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67aceae19d124_1739385569_acervo de livros.png', 'ERRO', 'Cannot read properties of undefined (reading \'isRegisteredUser\')', '2025-02-12 18:39:29', '2025-02-12 18:39:29'),
(42, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67aceae19d124_1739385569_acervo de livros.png', 'ERRO', 'Cannot read properties of undefined (reading \'isRegisteredUser\')', '2025-02-12 18:39:29', '2025-02-12 18:39:29'),
(43, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67aceae19d124_1739385569_acervo de livros.png', 'ERRO', 'Cannot read properties of undefined (reading \'isRegisteredUser\')', '2025-02-12 18:39:29', '2025-02-12 18:39:29'),
(44, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67aceae19d124_1739385569_acervo de livros.png', 'ERRO', 'Cannot read properties of undefined (reading \'isRegisteredUser\')', '2025-02-12 18:39:29', '2025-02-12 18:39:29'),
(45, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67aceae19d124_1739385569_acervo de livros.png', 'ERRO', 'Cannot read properties of undefined (reading \'isRegisteredUser\')', '2025-02-12 18:39:29', '2025-02-12 18:39:29'),
(46, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67aceb5452fe9_1739385684_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 18:41:24', '2025-02-12 18:41:26'),
(47, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67aceb5452fe9_1739385684_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 18:41:24', '2025-02-12 18:41:26'),
(48, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67aceb5452fe9_1739385684_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 18:41:24', '2025-02-12 18:41:26'),
(49, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67aceb5452fe9_1739385684_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 18:41:24', '2025-02-12 18:41:26'),
(50, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67aceb5452fe9_1739385684_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 18:41:24', '2025-02-12 18:41:26'),
(51, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf3024974e_1739387650_logo.png', 'ENVIADO', NULL, '2025-02-12 19:14:10', '2025-02-12 19:14:11'),
(52, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf3024974e_1739387650_logo.png', 'ENVIADO', NULL, '2025-02-12 19:14:10', '2025-02-12 19:14:11'),
(53, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf3024974e_1739387650_logo.png', 'ENVIADO', NULL, '2025-02-12 19:14:10', '2025-02-12 19:14:11'),
(54, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf3024974e_1739387650_logo.png', 'ENVIADO', NULL, '2025-02-12 19:14:10', '2025-02-12 19:14:11'),
(55, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf3024974e_1739387650_logo.png', 'ENVIADO', NULL, '2025-02-12 19:14:10', '2025-02-12 19:14:11'),
(56, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf5363fa1b_1739388214_tim.png', 'ENVIADO', NULL, '2025-02-12 19:23:34', '2025-02-12 19:23:35'),
(57, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf5363fa1b_1739388214_tim.png', 'ENVIADO', NULL, '2025-02-12 19:23:34', '2025-02-12 19:23:35'),
(58, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf5fc462fd_1739388412_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:26:52', '2025-02-12 19:26:53'),
(59, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf5fc462fd_1739388412_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:26:52', '2025-02-12 19:26:54'),
(60, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf5fc462fd_1739388412_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:26:52', '2025-02-12 19:26:54'),
(61, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf5fc462fd_1739388412_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:26:52', '2025-02-12 19:26:54'),
(62, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf6d1253c7_1739388625_WhatsApp Image 2024-12-23 at 15.02.38 (2).jpeg', 'ENVIADO', NULL, '2025-02-12 19:30:25', '2025-02-12 19:30:28'),
(63, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf6d1253c7_1739388625_WhatsApp Image 2024-12-23 at 15.02.38 (2).jpeg', 'ENVIADO', NULL, '2025-02-12 19:30:25', '2025-02-12 19:30:28'),
(64, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf6d1253c7_1739388625_WhatsApp Image 2024-12-23 at 15.02.38 (2).jpeg', 'ENVIADO', NULL, '2025-02-12 19:30:25', '2025-02-12 19:30:28'),
(65, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf6d1253c7_1739388625_WhatsApp Image 2024-12-23 at 15.02.38 (2).jpeg', 'ENVIADO', NULL, '2025-02-12 19:30:25', '2025-02-12 19:30:28'),
(66, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf6d1253c7_1739388625_WhatsApp Image 2024-12-23 at 15.02.38 (2).jpeg', 'ENVIADO', NULL, '2025-02-12 19:30:25', '2025-02-12 19:30:28'),
(67, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf6eeb5dd2_1739388654_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:30:54', '2025-02-12 19:30:56'),
(68, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf6eeb5dd2_1739388654_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:30:54', '2025-02-12 19:30:56'),
(69, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf6eeb5dd2_1739388654_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:30:54', '2025-02-12 19:30:56'),
(70, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf6eeb5dd2_1739388654_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:30:54', '2025-02-12 19:30:57'),
(71, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf6eeb5dd2_1739388654_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:30:54', '2025-02-12 19:30:57'),
(72, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf7f9e56dc_1739388921_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:35:21', '2025-02-12 19:35:23'),
(73, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf7f9e56dc_1739388921_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:35:21', '2025-02-12 19:35:23'),
(74, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf7f9e56dc_1739388921_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:35:21', '2025-02-12 19:35:23'),
(75, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf7f9e56dc_1739388921_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:35:21', '2025-02-12 19:35:23'),
(76, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf7f9e56dc_1739388921_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:35:21', '2025-02-12 19:35:23'),
(77, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf8794624c_1739389049_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:37:29', '2025-02-12 19:37:31'),
(78, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf8794624c_1739389049_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:37:29', '2025-02-12 19:37:36'),
(79, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf8794624c_1739389049_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:37:29', '2025-02-12 19:37:42'),
(80, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf8794624c_1739389049_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:37:29', '2025-02-12 19:37:47'),
(81, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf8794624c_1739389049_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:37:29', '2025-02-12 19:37:53'),
(82, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf8794624c_1739389049_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:37:29', '2025-02-12 19:37:57'),
(83, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf8794624c_1739389049_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:37:29', '2025-02-12 19:38:03'),
(84, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf8794624c_1739389049_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:37:29', '2025-02-12 19:38:10'),
(85, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf8794624c_1739389049_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:37:29', '2025-02-12 19:38:16'),
(86, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acf8794624c_1739389049_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 19:37:29', '2025-02-12 19:38:22'),
(87, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acfa4028058_1739389504_Captura de tela 2025-01-21 113003.png', 'ENVIADO', NULL, '2025-02-12 19:45:04', '2025-02-12 19:45:07'),
(88, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acfa4028058_1739389504_Captura de tela 2025-01-21 113003.png', 'ENVIADO', NULL, '2025-02-12 19:45:04', '2025-02-12 19:45:12'),
(89, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acfa4028058_1739389504_Captura de tela 2025-01-21 113003.png', 'ENVIADO', NULL, '2025-02-12 19:45:04', '2025-02-12 19:45:18'),
(90, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acfa4028058_1739389504_Captura de tela 2025-01-21 113003.png', 'ENVIADO', NULL, '2025-02-12 19:45:04', '2025-02-12 19:45:25'),
(91, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acfa4028058_1739389504_Captura de tela 2025-01-21 113003.png', 'ENVIADO', NULL, '2025-02-12 19:45:04', '2025-02-12 19:45:29'),
(92, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acfcc74515b_1739390151_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:55:51', '2025-02-12 19:55:53'),
(93, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acfcc74515b_1739390151_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:55:51', '2025-02-12 19:55:59'),
(94, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acfcc74515b_1739390151_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:55:51', '2025-02-12 19:56:04'),
(95, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acfcc74515b_1739390151_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:55:51', '2025-02-12 19:56:09'),
(96, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', NULL, '../uploads/file_67acfcc74515b_1739390151_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 19:55:51', '2025-02-12 19:56:15'),
(97, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad004357381_1739391043_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 20:10:43', '2025-02-12 20:10:45'),
(98, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad004357381_1739391043_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 20:10:43', '2025-02-12 20:10:51'),
(99, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad004357381_1739391043_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 20:10:43', '2025-02-12 20:10:56'),
(100, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad004357381_1739391043_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 20:10:43', '2025-02-12 20:11:01'),
(101, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad004357381_1739391043_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 20:10:43', '2025-02-12 20:11:07'),
(102, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad04a4c04d4_1739392164_Captura de tela 2024-09-26 173202.png', 'ENVIADO', NULL, '2025-02-12 20:29:24', '2025-02-12 20:29:30'),
(103, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad04a4c04d4_1739392164_Captura de tela 2024-09-26 173202.png', 'ENVIADO', NULL, '2025-02-12 20:29:24', '2025-02-12 20:29:35'),
(104, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad04a4c04d4_1739392164_Captura de tela 2024-09-26 173202.png', 'ENVIADO', NULL, '2025-02-12 20:29:24', '2025-02-12 20:29:40'),
(105, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad239331515_1739400083_Captura de tela 2024-09-26 173202.png', 'ENVIADO', NULL, '2025-02-12 22:41:23', '2025-02-12 22:41:26'),
(106, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 22:47:33', '2025-02-12 22:47:34'),
(107, 7, 'device_67aba8ea9793a', '553591944831', '<div id=\"loading\" style=\"display: none;\" class=\"text-center mt-3\">\r\n    <div class=\"spinner-border text-primary\" role=\"status\">\r\n        <span class=\"visually-hidden\">Carregando...</span>\r\n    </div>\r\n    <p>Iniciando envio em massa...</p>\r\n</div>', NULL, '../uploads/file_67ad25272a7c1_1739400487_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 22:48:07', '2025-02-12 22:48:09'),
(108, 7, 'device_67aba8ea9793a', '553591944831', '<div id=\"loading\" style=\"display: none;\" class=\"text-center mt-3\">\r\n    <div class=\"spinner-border text-primary\" role=\"status\">\r\n        <span class=\"visually-hidden\">Carregando...</span>\r\n    </div>\r\n    <p>Iniciando envio em massa...</p>\r\n</div>', NULL, '../uploads/file_67ad25272a7c1_1739400487_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 22:48:07', '2025-02-12 22:48:14'),
(109, 7, 'device_67aba8ea9793a', '553591944831', '<div id=\"loading\" style=\"display: none;\" class=\"text-center mt-3\">\r\n    <div class=\"spinner-border text-primary\" role=\"status\">\r\n        <span class=\"visually-hidden\">Carregando...</span>\r\n    </div>\r\n    <p>Iniciando envio em massa...</p>\r\n</div>', NULL, '../uploads/file_67ad25272a7c1_1739400487_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 22:48:07', '2025-02-12 22:48:20'),
(110, 7, 'device_67aba8ea9793a', '5535991944831', '<div id=\"loading\" style=\"display: none;\" class=\"text-center mt-3\">\r\n    <div class=\"spinner-border text-primary\" role=\"status\">\r\n        <span class=\"visually-hidden\">Carregando...</span>\r\n    </div>\r\n    <p>Iniciando envio em massa...</p>\r\n</div>', NULL, '../uploads/file_67ad25272a7c1_1739400487_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 22:48:07', '2025-02-12 22:48:25'),
(111, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad25fde0638_1739400701_CASH BT TOUR2.png', 'ENVIADO', NULL, '2025-02-12 22:51:41', '2025-02-12 22:51:45'),
(112, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad25fde0638_1739400701_CASH BT TOUR2.png', 'ENVIADO', NULL, '2025-02-12 22:51:41', '2025-02-12 22:51:49'),
(113, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad25fde0638_1739400701_CASH BT TOUR2.png', 'ENVIADO', NULL, '2025-02-12 22:51:41', '2025-02-12 22:51:54'),
(114, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad25fde0638_1739400701_CASH BT TOUR2.png', 'ENVIADO', NULL, '2025-02-12 22:51:41', '2025-02-12 22:51:59'),
(115, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad25fde0638_1739400701_CASH BT TOUR2.png', 'ENVIADO', NULL, '2025-02-12 22:51:41', '2025-02-12 22:52:05'),
(116, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2602e92d3_1739400706_CASH BT TOUR2.png', 'ENVIADO', NULL, '2025-02-12 22:51:46', '2025-02-12 22:52:09'),
(117, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2602e92d3_1739400706_CASH BT TOUR2.png', 'ENVIADO', NULL, '2025-02-12 22:51:46', '2025-02-12 22:52:15'),
(118, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2602e92d3_1739400706_CASH BT TOUR2.png', 'ENVIADO', NULL, '2025-02-12 22:51:46', '2025-02-12 22:52:20'),
(119, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2602e92d3_1739400706_CASH BT TOUR2.png', 'ENVIADO', NULL, '2025-02-12 22:51:46', '2025-02-12 22:52:26'),
(120, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2602e92d3_1739400706_CASH BT TOUR2.png', 'ENVIADO', NULL, '2025-02-12 22:51:46', '2025-02-12 22:52:31'),
(121, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad272c28e79_1739401004_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:56:44', '2025-02-12 22:56:47'),
(122, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad272c28e79_1739401004_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:56:44', '2025-02-12 22:56:54'),
(123, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad272c28e79_1739401004_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:56:44', '2025-02-12 22:56:58'),
(124, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad272c28e79_1739401004_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:56:44', '2025-02-12 22:57:00'),
(125, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad272c28e79_1739401004_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:56:44', '2025-02-12 22:57:05'),
(126, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad273130d44_1739401009_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:56:49', '2025-02-12 22:57:08'),
(127, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad273130d44_1739401009_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:56:49', '2025-02-12 22:57:15'),
(128, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad273130d44_1739401009_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:56:49', '2025-02-12 22:57:21'),
(129, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad273130d44_1739401009_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:56:49', '2025-02-12 22:57:27'),
(130, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad273130d44_1739401009_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:56:49', '2025-02-12 22:57:33'),
(131, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad279cab76a_1739401116_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:58:36', '2025-02-12 22:58:40'),
(132, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad279cab76a_1739401116_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:58:36', '2025-02-12 22:58:46'),
(133, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad279cab76a_1739401116_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:58:36', '2025-02-12 22:58:51'),
(134, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad279cab76a_1739401116_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:58:36', '2025-02-12 22:58:56'),
(135, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad279cab76a_1739401116_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:58:36', '2025-02-12 22:59:04'),
(136, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad27a1b2f5d_1739401121_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:58:41', '2025-02-12 22:59:10'),
(137, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad27a1b2f5d_1739401121_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:58:41', '2025-02-12 22:59:12'),
(138, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad27a1b2f5d_1739401121_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:58:41', '2025-02-12 22:59:18'),
(139, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad27a1b2f5d_1739401121_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:58:41', '2025-02-12 22:59:23'),
(140, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad27a1b2f5d_1739401121_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 22:58:41', '2025-02-12 22:59:30'),
(141, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:01:22', '2025-02-12 23:01:23'),
(142, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:01:22', '2025-02-12 23:01:27'),
(143, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:01:27', '2025-02-12 23:01:28'),
(144, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:01:27', '2025-02-12 23:01:31'),
(145, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:01:54', '2025-02-12 23:01:55'),
(146, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:01:58', '2025-02-12 23:01:59'),
(147, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:05:21', '2025-02-12 23:05:21'),
(148, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:05:21', '2025-02-12 23:05:25'),
(149, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:05:26', '2025-02-12 23:05:27'),
(150, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:05:26', '2025-02-12 23:05:29'),
(151, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad29752e17f_1739401589_tim.png', 'ENVIADO', NULL, '2025-02-12 23:06:29', '2025-02-12 23:06:33'),
(152, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad29752e17f_1739401589_tim.png', 'ENVIADO', NULL, '2025-02-12 23:06:29', '2025-02-12 23:06:39'),
(153, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad29752e17f_1739401589_tim.png', 'ENVIADO', NULL, '2025-02-12 23:06:29', '2025-02-12 23:06:45'),
(154, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad29752e17f_1739401589_tim.png', 'ENVIADO', NULL, '2025-02-12 23:06:29', '2025-02-12 23:06:51'),
(155, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad29752e17f_1739401589_tim.png', 'ENVIADO', NULL, '2025-02-12 23:06:29', '2025-02-12 23:06:57'),
(156, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2a746bdf7_1739401844_logo.png', 'ENVIADO', NULL, '2025-02-12 23:10:44', '2025-02-12 23:10:48'),
(157, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2a746bdf7_1739401844_logo.png', 'ENVIADO', NULL, '2025-02-12 23:10:44', '2025-02-12 23:10:53'),
(158, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2a746bdf7_1739401844_logo.png', 'ENVIADO', NULL, '2025-02-12 23:10:44', '2025-02-12 23:10:58'),
(159, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2a746bdf7_1739401844_logo.png', 'ENVIADO', NULL, '2025-02-12 23:10:44', '2025-02-12 23:11:03'),
(160, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2a746bdf7_1739401844_logo.png', 'ENVIADO', NULL, '2025-02-12 23:10:44', '2025-02-12 23:11:08'),
(161, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2b02c0c98_1739401986_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 23:13:06', '2025-02-12 23:13:09'),
(162, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2b02c0c98_1739401986_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 23:13:06', '2025-02-12 23:13:14'),
(163, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2b02c0c98_1739401986_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 23:13:06', '2025-02-12 23:13:19'),
(164, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2b02c0c98_1739401986_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 23:13:06', '2025-02-12 23:13:25'),
(165, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2b02c0c98_1739401986_acervo de livros.png', 'ENVIADO', NULL, '2025-02-12 23:13:06', '2025-02-12 23:13:30'),
(166, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2ba7403a8_1739402151_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 23:15:51', '2025-02-12 23:15:54'),
(167, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2ba7403a8_1739402151_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 23:15:51', '2025-02-12 23:16:00'),
(168, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2ba7403a8_1739402151_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 23:15:51', '2025-02-12 23:16:04'),
(169, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2ba7403a8_1739402151_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 23:15:51', '2025-02-12 23:16:10'),
(170, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67ad2ba7403a8_1739402151_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-12 23:15:51', '2025-02-12 23:16:15'),
(171, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:17:41', '2025-02-12 23:17:41'),
(172, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:21:36', '2025-02-12 23:21:36'),
(173, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:21:36', '2025-02-12 23:21:41');
INSERT INTO `fila_mensagens` (`id`, `usuario_id`, `dispositivo_id`, `numero`, `mensagem`, `nome`, `arquivo_path`, `status`, `error_message`, `created_at`, `updated_at`) VALUES
(174, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:21:36', '2025-02-12 23:21:44'),
(175, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:21:36', '2025-02-12 23:21:48'),
(176, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-12 23:21:36', '2025-02-12 23:21:52'),
(177, 7, 'device_67aba8ea9793a', '553591944831', 'Use Leonardo para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67ad3234f07af_1739403828_WhatsApp Image 2024-12-23 at 15.02.38 (2).jpeg', 'ENVIADO', NULL, '2025-02-12 23:43:48', '2025-02-12 23:43:52'),
(178, 7, 'device_67aba8ea9793a', '553591944831', 'Use Aline para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67ad3234f07af_1739403828_WhatsApp Image 2024-12-23 at 15.02.38 (2).jpeg', 'ENVIADO', NULL, '2025-02-12 23:43:48', '2025-02-12 23:43:58'),
(179, 7, 'device_67aba8ea9793a', '553591944831', 'Use VIVIAN MACIENTE DO NASCIMENTO para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67ad3234f07af_1739403828_WhatsApp Image 2024-12-23 at 15.02.38 (2).jpeg', 'ENVIADO', NULL, '2025-02-12 23:43:48', '2025-02-12 23:44:03'),
(180, 7, 'device_67aba8ea9793a', '5535991944831', 'Use Leonardo Nascimento para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67ad3234f07af_1739403828_WhatsApp Image 2024-12-23 at 15.02.38 (2).jpeg', 'ENVIADO', NULL, '2025-02-12 23:43:48', '2025-02-12 23:44:08'),
(181, 7, 'device_67aba8ea9793a', '553591944831', 'Use Barbara para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67ad3234f07af_1739403828_WhatsApp Image 2024-12-23 at 15.02.38 (2).jpeg', 'ENVIADO', NULL, '2025-02-12 23:43:48', '2025-02-12 23:44:13'),
(182, 7, 'device_67aba8ea9793a', '553591944831', 'Use Leonardo para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67ad3ae73ab70_1739406055_CASH BT TOUR.png', 'ENVIADO', NULL, '2025-02-13 00:20:55', '2025-02-13 00:20:58'),
(183, 7, 'device_67aba8ea9793a', '553591944831', 'Use Barbara para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67ad3ae73ab70_1739406055_CASH BT TOUR.png', 'ENVIADO', NULL, '2025-02-13 00:20:55', '2025-02-13 00:21:03'),
(184, 7, 'device_67aba8ea9793a', '553591944831', 'Use Leonardo para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67ad3b9fdc992_1739406239_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-13 00:23:59', '2025-02-13 00:58:13'),
(185, 7, 'device_67aba8ea9793a', '553591944831', 'Use Aline para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67ad3b9fdc992_1739406239_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-13 00:23:59', '2025-02-13 00:58:18'),
(186, 7, 'device_67aba8ea9793a', '553591944831', 'Use VIVIAN MACIENTE DO NASCIMENTO para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67ad3b9fdc992_1739406239_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-13 00:23:59', '2025-02-13 00:58:22'),
(187, 7, 'device_67aba8ea9793a', '5535991944831', 'Use Leonardo Nascimento para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67ad3b9fdc992_1739406239_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-13 00:23:59', '2025-02-13 00:58:29'),
(188, 7, 'device_67aba8ea9793a', '553591944831', 'Use Barbara para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67ad3b9fdc992_1739406239_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-13 00:23:59', '2025-02-13 00:58:35'),
(189, 7, 'device_67aba8ea9793a', '553591944831', 'Use Leonardo para incluir o nome do lead na mensagem.\r\n2222', NULL, '../uploads/file_67ad3bd7e3702_1739406295_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-13 00:24:55', '2025-02-13 00:58:40'),
(190, 7, 'device_67aba8ea9793a', '553591944831', 'Use Aline para incluir o nome do lead na mensagem.\r\n2222', NULL, '../uploads/file_67ad3bd7e3702_1739406295_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-13 00:24:55', '2025-02-13 00:58:47'),
(191, 7, 'device_67aba8ea9793a', '553591944831', 'Use VIVIAN MACIENTE DO NASCIMENTO para incluir o nome do lead na mensagem.\r\n2222', NULL, '../uploads/file_67ad3bd7e3702_1739406295_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-13 00:24:55', '2025-02-13 00:58:53'),
(192, 7, 'device_67aba8ea9793a', '5535991944831', 'Use Leonardo Nascimento para incluir o nome do lead na mensagem.\r\n2222', NULL, '../uploads/file_67ad3bd7e3702_1739406295_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-13 00:24:55', '2025-02-13 00:58:57'),
(193, 7, 'device_67aba8ea9793a', '553591944831', 'Use Barbara para incluir o nome do lead na mensagem.\r\n2222', NULL, '../uploads/file_67ad3bd7e3702_1739406295_Captura de tela 2025-01-21 112831.png', 'ENVIADO', NULL, '2025-02-13 00:24:55', '2025-02-13 00:59:02'),
(194, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:25:33', '2025-02-13 00:59:06'),
(195, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:25:33', '2025-02-13 00:59:09'),
(196, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:25:33', '2025-02-13 00:59:13'),
(197, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:25:33', '2025-02-13 00:59:16'),
(198, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:25:33', '2025-02-13 00:59:18'),
(199, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:41:05', '2025-02-13 00:59:21'),
(200, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:41:05', '2025-02-13 00:59:25'),
(201, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:41:07', '2025-02-13 00:59:28'),
(202, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:41:07', '2025-02-13 00:59:31'),
(203, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:41:07', '2025-02-13 00:59:35'),
(204, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:42:05', '2025-02-13 00:59:38'),
(205, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:42:05', '2025-02-13 00:59:41'),
(206, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:42:05', '2025-02-13 00:59:44'),
(207, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:42:05', '2025-02-13 00:59:47'),
(208, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:42:05', '2025-02-13 00:59:49'),
(209, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:58:10', '2025-02-13 00:59:52'),
(210, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:58:10', '2025-02-13 00:59:55'),
(211, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:58:10', '2025-02-13 00:59:58'),
(212, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:58:10', '2025-02-13 01:00:00'),
(213, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 00:58:10', '2025-02-13 01:00:04'),
(214, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 01:00:45', '2025-02-13 01:00:45'),
(215, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 01:00:45', '2025-02-13 01:00:47'),
(216, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 01:00:45', '2025-02-13 01:00:52'),
(217, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 01:00:45', '2025-02-13 01:00:55'),
(218, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 01:00:45', '2025-02-13 01:00:58'),
(219, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ERRO', 'Protocol error (Runtime.callFunctionOn): Session closed. Most likely the page has been closed.', '2025-02-13 11:06:23', '2025-02-13 11:06:23'),
(220, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ERRO', 'Protocol error (Runtime.callFunctionOn): Session closed. Most likely the page has been closed.', '2025-02-13 11:06:23', '2025-02-13 11:06:24'),
(221, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ERRO', 'Protocol error (Runtime.callFunctionOn): Session closed. Most likely the page has been closed.', '2025-02-13 11:06:23', '2025-02-13 11:06:25'),
(222, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '', 'ERRO', 'Protocol error (Runtime.callFunctionOn): Session closed. Most likely the page has been closed.', '2025-02-13 11:06:23', '2025-02-13 11:06:26'),
(223, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ERRO', 'Protocol error (Runtime.callFunctionOn): Session closed. Most likely the page has been closed.', '2025-02-13 11:06:23', '2025-02-13 11:06:28'),
(224, 7, 'device_67aba8ea9793a', '553591944831', 'Use Leonardo para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add3ef7d1e8_1739445231_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:13:51', '2025-02-13 11:13:54'),
(225, 7, 'device_67aba8ea9793a', '553591944831', 'Use Aline para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add3ef7d1e8_1739445231_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:13:51', '2025-02-13 11:14:00'),
(226, 7, 'device_67aba8ea9793a', '553591944831', 'Use VIVIAN MACIENTE DO NASCIMENTO para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add3ef7d1e8_1739445231_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:13:51', '2025-02-13 11:14:05'),
(227, 7, 'device_67aba8ea9793a', '5535991944831', 'Use Leonardo Nascimento para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add3ef7d1e8_1739445231_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:13:51', '2025-02-13 11:14:10'),
(228, 7, 'device_67aba8ea9793a', '553591944831', 'Use Barbara para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add3ef7d1e8_1739445231_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:13:51', '2025-02-13 11:14:16'),
(229, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add6d7c1c63_1739445975_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:26:15', '2025-02-13 11:26:19'),
(230, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add6d7c1c63_1739445975_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:26:15', '2025-02-13 11:26:24'),
(231, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add6d7c1c63_1739445975_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:26:15', '2025-02-13 11:26:29'),
(232, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add6d7c1c63_1739445975_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:26:15', '2025-02-13 11:26:33'),
(233, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add6d7c1c63_1739445975_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:26:15', '2025-02-13 11:26:39'),
(234, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add81eba208_1739446302_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:31:42', '2025-02-13 11:31:46'),
(235, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add81eba208_1739446302_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:31:42', '2025-02-13 11:31:52'),
(236, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add81eba208_1739446302_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:31:42', '2025-02-13 11:31:56'),
(237, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add81eba208_1739446302_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:31:42', '2025-02-13 11:32:02'),
(238, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add81eba208_1739446302_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:31:42', '2025-02-13 11:32:07'),
(239, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add820730d4_1739446304_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:31:44', '2025-02-13 11:32:13'),
(240, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add820730d4_1739446304_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:31:44', '2025-02-13 11:32:18'),
(241, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add820730d4_1739446304_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:31:44', '2025-02-13 11:32:23'),
(242, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add820730d4_1739446304_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:31:44', '2025-02-13 11:32:28'),
(243, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '../uploads/file_67add820730d4_1739446304_473406168_477716408498685_171445.jpg', 'ENVIADO', NULL, '2025-02-13 11:31:44', '2025-02-13 11:32:31'),
(244, 7, 'device_67aba8ea9793a', '553591944831', 'Use Leonardo para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add86b9ef07_1739446379_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:32:59', '2025-02-13 11:33:02'),
(245, 7, 'device_67aba8ea9793a', '553591944831', 'Use Aline para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add86b9ef07_1739446379_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:32:59', '2025-02-13 11:33:04'),
(246, 7, 'device_67aba8ea9793a', '553591944831', 'Use VIVIAN MACIENTE DO NASCIMENTO para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add86b9ef07_1739446379_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:32:59', '2025-02-13 11:33:07'),
(247, 7, 'device_67aba8ea9793a', '5535991944831', 'Use Leonardo Nascimento para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add86b9ef07_1739446379_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:32:59', '2025-02-13 11:33:12'),
(248, 7, 'device_67aba8ea9793a', '553591944831', 'Use Barbara para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add86b9ef07_1739446379_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:32:59', '2025-02-13 11:33:15'),
(249, 7, 'device_67aba8ea9793a', '553591944831', 'Use Leonardo para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add86e26583_1739446382_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:33:02', '2025-02-13 11:33:20'),
(250, 7, 'device_67aba8ea9793a', '553591944831', 'Use Aline para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add86e26583_1739446382_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:33:02', '2025-02-13 11:33:25'),
(251, 7, 'device_67aba8ea9793a', '553591944831', 'Use VIVIAN MACIENTE DO NASCIMENTO para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add86e26583_1739446382_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:33:02', '2025-02-13 11:33:30'),
(252, 7, 'device_67aba8ea9793a', '5535991944831', 'Use Leonardo Nascimento para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add86e26583_1739446382_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:33:02', '2025-02-13 11:33:35'),
(253, 7, 'device_67aba8ea9793a', '553591944831', 'Use Barbara para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67add86e26583_1739446382_login-image.png', 'ENVIADO', NULL, '2025-02-13 11:33:02', '2025-02-13 11:33:42'),
(254, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:40:09', '2025-02-13 11:40:10'),
(255, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:40:09', '2025-02-13 11:40:11'),
(256, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:40:09', '2025-02-13 11:40:12'),
(257, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:40:09', '2025-02-13 11:40:13'),
(258, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:40:09', '2025-02-13 11:40:15'),
(259, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:40:10', '2025-02-13 11:40:17'),
(260, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:40:10', '2025-02-13 11:40:18'),
(261, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:40:10', '2025-02-13 11:40:20'),
(262, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:40:10', '2025-02-13 11:40:22'),
(263, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:40:10', '2025-02-13 11:40:23'),
(264, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:42:17', '2025-02-13 11:42:17'),
(265, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:42:17', '2025-02-13 11:42:18'),
(266, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:42:17', '2025-02-13 11:42:20'),
(267, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:42:17', '2025-02-13 11:42:22'),
(268, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:42:17', '2025-02-13 11:42:24'),
(269, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:42:18', '2025-02-13 11:42:26'),
(270, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:42:18', '2025-02-13 11:42:27'),
(271, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:42:18', '2025-02-13 11:42:29'),
(272, 7, 'device_67aba8ea9793a', '5535991944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:42:18', '2025-02-13 11:42:31'),
(273, 7, 'device_67aba8ea9793a', '553591944831', 'Preencha aqui com o seu texto...', NULL, '', 'ENVIADO', NULL, '2025-02-13 11:42:18', '2025-02-13 11:42:31'),
(274, 7, 'device_67aba8ea9793a', '553591944831', 'Use Leonardo para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67addc1265b3a_1739447314_473406168_477716408498685_171445.jpg', 'PENDENTE', NULL, '2025-02-13 11:48:34', '2025-02-13 11:48:34'),
(275, 7, 'device_67aba8ea9793a', '553591944831', 'Use Aline para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67addc1265b3a_1739447314_473406168_477716408498685_171445.jpg', 'PENDENTE', NULL, '2025-02-13 11:48:34', '2025-02-13 11:48:34'),
(276, 7, 'device_67aba8ea9793a', '553591944831', 'Use VIVIAN MACIENTE DO NASCIMENTO para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67addc1265b3a_1739447314_473406168_477716408498685_171445.jpg', 'PENDENTE', NULL, '2025-02-13 11:48:34', '2025-02-13 11:48:34'),
(277, 7, 'device_67aba8ea9793a', '5535991944831', 'Use Leonardo Nascimento para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67addc1265b3a_1739447314_473406168_477716408498685_171445.jpg', 'PENDENTE', NULL, '2025-02-13 11:48:34', '2025-02-13 11:48:34'),
(278, 7, 'device_67aba8ea9793a', '553591944831', 'Use Barbara para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67addc1265b3a_1739447314_473406168_477716408498685_171445.jpg', 'PENDENTE', NULL, '2025-02-13 11:48:34', '2025-02-13 11:48:34'),
(279, 7, 'device_67aba8ea9793a', '553591944831', 'Use Leonardo para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67addc1375d03_1739447315_473406168_477716408498685_171445.jpg', 'PENDENTE', NULL, '2025-02-13 11:48:35', '2025-02-13 11:48:35'),
(280, 7, 'device_67aba8ea9793a', '553591944831', 'Use Aline para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67addc1375d03_1739447315_473406168_477716408498685_171445.jpg', 'PENDENTE', NULL, '2025-02-13 11:48:35', '2025-02-13 11:48:35'),
(281, 7, 'device_67aba8ea9793a', '553591944831', 'Use VIVIAN MACIENTE DO NASCIMENTO para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67addc1375d03_1739447315_473406168_477716408498685_171445.jpg', 'PENDENTE', NULL, '2025-02-13 11:48:35', '2025-02-13 11:48:35'),
(282, 7, 'device_67aba8ea9793a', '5535991944831', 'Use Leonardo Nascimento para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67addc1375d03_1739447315_473406168_477716408498685_171445.jpg', 'PENDENTE', NULL, '2025-02-13 11:48:35', '2025-02-13 11:48:35'),
(283, 7, 'device_67aba8ea9793a', '553591944831', 'Use Barbara para incluir o nome do lead na mensagem.\r\n', NULL, '../uploads/file_67addc1375d03_1739447315_473406168_477716408498685_171445.jpg', 'PENDENTE', NULL, '2025-02-13 11:48:35', '2025-02-13 11:48:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ia_interacoes`
--

CREATE TABLE `ia_interacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `prompt` text NOT NULL,
  `resposta` text NOT NULL,
  `data_criacao` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `info_negocios`
--

CREATE TABLE `info_negocios` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome_negocio` varchar(255) NOT NULL,
  `segmento` varchar(100) NOT NULL,
  `tamanho_empresa` varchar(50) NOT NULL,
  `objetivo` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `leads_enviados`
--

CREATE TABLE `leads_enviados` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `dispositivo_id` varchar(255) DEFAULT NULL,
  `numero` varchar(20) NOT NULL,
  `mensagem` text NOT NULL,
  `arquivo` varchar(255) DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'ENVIADO',
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_id` int(11) DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `leads_enviados`
--

INSERT INTO `leads_enviados` (`id`, `usuario_id`, `dispositivo_id`, `numero`, `mensagem`, `arquivo`, `nome`, `status`, `data_envio`, `status_id`, `observacoes`) VALUES
(40, 1, 'device_67a5dabeaf84a', '553591944831', 'Olá Aline Token do Dispositivo: 19203333', '1500 LIVROS.png', 'Aline', 'ENVIADO', '2025-02-09 19:05:19', NULL, 'oi'),
(51, 1, 'device_67a5dabeaf84a', '553591944831', 'Olá Leonardo Nascimento ????Esperamos que esteja tendo um ótimo dia. Gostaríamos de informar que seu Token do Dispositivo é: 19203333.Esse código é importante para garantir a segurança de suas informações. Caso tenha alguma dúvida, fique à vontade para entrar em contato conosco.Contamos com sua confiança e ficamos felizes em poder ajudá-lo(a)!Atenciosamente,Equipe de Suporte', '1500 LIVROS.png', 'Leonardo Nascimento', 'ENVIADO', '2025-02-09 19:39:46', NULL, NULL),
(52, 1, 'device_67a5dabeaf84a', '553591944831', 'Olá Aline ????Esperamos que esteja tendo um ótimo dia. Gostaríamos de informar que seu Token do Dispositivo é: 19203333.Esse código é importante para garantir a segurança de suas informações. Caso tenha alguma dúvida, fique à vontade para entrar em contato conosco.Contamos com sua confiança e ficamos felizes em poder ajudá-lo(a)!Atenciosamente,Equipe de Suporte', '1500 LIVROS.png', 'Aline', 'ENVIADO', '2025-02-09 19:39:38', NULL, NULL),
(53, 1, 'device_67a5dabeaf84a', '553591944831', 'Olá vivian ????Esperamos que esteja tendo um ótimo dia. Gostaríamos de informar que seu Token do Dispositivo é: 19203333.Esse código é importante para garantir a segurança de suas informações. Caso tenha alguma dúvida, fique à vontade para entrar em contato conosco.Contamos com sua confiança e ficamos felizes em poder ajudá-lo(a)!Atenciosamente,Equipe de Suporte', 'LOGOTIPO ZAOLOCAL.png', 'vivian', 'ENVIADO', '2025-02-09 19:05:28', NULL, NULL),
(54, 1, 'device_67a5dabeaf84a', '553591944831', 'Olá, Leonardo Nascimento 👋 Esperamos que seu dia esteja sendo ótimo! 🌞\r\n\r\nGostaríamos de informar que seu Token do Dispositivo é: 19203333. Esse código é essencial para a segurança de suas informações. Caso tenha qualquer dúvida, fique à vontade para entrar em contato - estaremos felizes em ajudá-lo(a)! 🤝', 'LOGOTIPO ZAOLOCAL.png', 'Leonardo Nascimento', 'ENVIADO', '2025-02-09 19:39:53', NULL, 'ok'),
(55, 1, 'device_67a5dabeaf84a', '553591944831', 'Olá, Teste de envio 👋 Esperamos que seu dia esteja sendo ótimo! 🌞\r\n\r\nGostaríamos de informar que seu Token do Dispositivo é: 19203333. Esse código é essencial para a segurança de suas informações. Caso tenha qualquer dúvida, fique à vontade para entrar em contato - estaremos felizes em ajudá-lo(a)! 🤝', 'LOGOTIPO ZAOLOCAL.png', 'Teste de envio', 'ENVIADO', '2025-02-09 19:39:33', NULL, NULL),
(56, 1, 'device_67a5dabeaf84a', '553591944831', 'Olá, Leonardo 👋 Esperamos que seu dia esteja sendo ótimo! 🌞\r\n\r\nGostaríamos de informar que seu Token do Dispositivo é: 19203333. Esse código é essencial para a segurança de suas informações. Caso tenha qualquer dúvida, fique à vontade para entrar em contato - estaremos felizes em ajudá-lo(a)! 🤝', 'LOGOTIPO ZAOLOCAL.png', 'Leonardo', 'ENVIADO', '2025-02-10 15:02:18', NULL, NULL),
(57, 1, 'device_67a5dabeaf84a', '553591944831', 'Olá, Leonardo Nascimento 👋 Esperamos que seu dia esteja sendo ótimo! 🌞\r\n\r\nGostaríamos de informar que seu Token do Dispositivo é: 19203333. Esse código é essencial para a segurança de suas informações. Caso tenha qualquer dúvida, fique à vontade para entrar em contato - estaremos felizes em ajudá-lo(a)! 🤝', 'LOGOTIPO ZAOLOCAL.png', 'Leonardo Nascimento', 'ENVIADO', '2025-02-10 23:27:30', NULL, NULL),
(58, 2, 'device_67aab930f3341', '553591944831', '', NULL, 'Leonardo Nascimento', 'ENVIADO', '2025-02-11 02:44:35', NULL, NULL),
(59, 2, 'device_67aab930f3341', '553591944831', 'Olá, Leonardo Nascimento 👋 Esperamos que seu dia esteja sendo ótimo! 🌞\r\n\r\nGostaríamos de informar que seu Token do Dispositivo é: 19203333. Esse código é essencial para a segurança de suas informações. Caso tenha qualquer dúvida, fique à vontade para entrar em contato - estaremos felizes em ajudá-lo(a)! 🤝', '473406168_477716408498685_171445.jpg', 'Aline', 'ENVIADO', '2025-02-11 02:45:17', NULL, NULL),
(61, 7, 'device_67aba8ea9793a', '553591944831', 'Leonardo , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', 'LOGO ZAPONTO.png', 'Leonardo', 'ENVIADO', '2025-02-12 23:42:26', NULL, NULL),
(62, 7, 'device_67aba8ea9793a', '553591944831', 'Aline , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', 'LOGO ZAPONTO.png', 'Aline', 'ENVIADO', '2025-02-12 23:42:33', NULL, NULL),
(63, 7, 'device_67aba8ea9793a', '553591944831', 'VIVIAN MACIENTE DO NASCIMENTO , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', 'LOGO ZAPONTO.png', 'VIVIAN MACIENTE DO NASCIMENTO', 'ENVIADO', '2025-02-12 14:37:46', NULL, NULL),
(67, 7, 'device_67aba8ea9793a', '5535991944831', 'Leonardo Nascimento , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', 'LOGO ZAPONTO.png', 'Leonardo Nascimento', 'ENVIADO', '2025-02-12 14:37:37', NULL, NULL),
(68, 7, 'device_67aba8ea9793a', '553591944831', 'Barbara , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', 'LOGO ZAPONTO.png', 'Barbara', 'ENVIADO', '2025-02-12 14:37:46', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens_enviadas`
--

CREATE TABLE `mensagens_enviadas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `mensagem` text NOT NULL,
  `status` varchar(50) DEFAULT 'pendente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens_fila`
--

CREATE TABLE `mensagens_fila` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `dispositivo_id` varchar(255) DEFAULT NULL,
  `mensagem_base` text DEFAULT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notas_lead`
--

CREATE TABLE `notas_lead` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `texto` text NOT NULL,
  `data_criacao` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `data_criacao` datetime DEFAULT current_timestamp(),
  `data_leitura` datetime DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `is_admin_notification` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id`, `usuario_id`, `tipo`, `titulo`, `mensagem`, `lida`, `data_criacao`, `data_leitura`, `admin_id`, `is_admin_notification`) VALUES
(1, 1, 'aviso', 'Consórcio residencial', 'Aqui ficara a mensagem', 0, '2025-02-13 12:25:28', NULL, NULL, 0),
(2, 2, 'aviso', 'Consórcio residencial', 'Aqui ficara a mensagem', 0, '2025-02-13 12:25:28', NULL, NULL, 0),
(3, 7, 'aviso', 'Consórcio residencial', 'Aqui ficara a mensagem', 1, '2025-02-13 12:25:28', '2025-02-13 12:46:17', NULL, 0),
(4, 8, 'aviso', 'Consórcio residencial', 'Aqui ficara a mensagem', 0, '2025-02-13 12:25:28', NULL, NULL, 0),
(5, 9, 'aviso', 'Consórcio residencial', 'Aqui ficara a mensagem', 0, '2025-02-13 12:25:28', NULL, NULL, 0),
(6, 10, 'aviso', 'Consórcio residencial', 'Aqui ficara a mensagem', 0, '2025-02-13 12:25:28', NULL, NULL, 0),
(7, 11, 'aviso', 'Consórcio residencial', 'Aqui ficara a mensagem', 0, '2025-02-13 12:25:28', NULL, NULL, 0),
(8, 12, 'aviso', 'Consórcio residencial', 'Aqui ficara a mensagem', 0, '2025-02-13 12:25:28', NULL, NULL, 0),
(9, 1, 'sistema', '🔥Liquida showroom VESTA  - Estamos pertinho de você!', 'Mensagem', 0, '2025-02-13 12:27:13', NULL, NULL, 0),
(10, 2, 'sistema', '🔥Liquida showroom VESTA  - Estamos pertinho de você!', 'Mensagem', 0, '2025-02-13 12:27:13', NULL, NULL, 0),
(11, 7, 'sistema', '🔥Liquida showroom VESTA  - Estamos pertinho de você!', 'Mensagem', 1, '2025-02-13 12:27:13', '2025-02-13 12:46:16', NULL, 0),
(12, 8, 'sistema', '🔥Liquida showroom VESTA  - Estamos pertinho de você!', 'Mensagem', 0, '2025-02-13 12:27:13', NULL, NULL, 0),
(13, 9, 'sistema', '🔥Liquida showroom VESTA  - Estamos pertinho de você!', 'Mensagem', 1, '2025-02-13 12:27:13', '2025-02-13 14:59:02', NULL, 0),
(14, 10, 'sistema', '🔥Liquida showroom VESTA  - Estamos pertinho de você!', 'Mensagem', 0, '2025-02-13 12:27:13', NULL, NULL, 0),
(15, 11, 'sistema', '🔥Liquida showroom VESTA  - Estamos pertinho de você!', 'Mensagem', 0, '2025-02-13 12:27:13', NULL, NULL, 0),
(16, 12, 'sistema', '🔥Liquida showroom VESTA  - Estamos pertinho de você!', 'Mensagem', 0, '2025-02-13 12:27:13', NULL, NULL, 0),
(17, 1, 'sistema', '77777', '777777777', 0, '2025-02-14 14:08:54', NULL, NULL, 0),
(18, 2, 'sistema', '77777', '777777777', 0, '2025-02-14 14:08:54', NULL, NULL, 0),
(19, 7, 'sistema', '77777', '777777777', 1, '2025-02-14 14:08:54', '2025-02-14 14:10:11', NULL, 0),
(20, 8, 'sistema', '77777', '777777777', 0, '2025-02-14 14:08:54', NULL, NULL, 0),
(21, 9, 'sistema', '77777', '777777777', 0, '2025-02-14 14:08:54', NULL, NULL, 0),
(22, 10, 'sistema', '77777', '777777777', 0, '2025-02-14 14:08:54', NULL, NULL, 0),
(23, 11, 'sistema', '77777', '777777777', 0, '2025-02-14 14:08:54', NULL, NULL, 0),
(24, 12, 'sistema', '77777', '777777777', 0, '2025-02-14 14:08:54', NULL, NULL, 0),
(25, 1, 'sistema', 'eeee', 'eeee', 0, '2025-02-14 14:20:39', NULL, NULL, 0),
(26, 2, 'sistema', 'eeee', 'eeee', 0, '2025-02-14 14:20:39', NULL, NULL, 0),
(27, 7, 'sistema', 'eeee', 'eeee', 0, '2025-02-14 14:20:39', NULL, NULL, 0),
(28, 8, 'sistema', 'eeee', 'eeee', 0, '2025-02-14 14:20:39', NULL, NULL, 0),
(29, 9, 'sistema', 'eeee', 'eeee', 0, '2025-02-14 14:20:39', NULL, NULL, 0),
(30, 10, 'sistema', 'eeee', 'eeee', 0, '2025-02-14 14:20:39', NULL, NULL, 0),
(31, 11, 'sistema', 'eeee', 'eeee', 0, '2025-02-14 14:20:39', NULL, NULL, 0),
(32, 12, 'sistema', 'eeee', 'eeee', 0, '2025-02-14 14:20:39', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes_excluidas`
--

CREATE TABLE `notificacoes_excluidas` (
  `id` int(11) NOT NULL,
  `notificacao_id` int(11) NOT NULL,
  `data_exclusao` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `assinatura_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_pagamento` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `fatura_url` varchar(255) DEFAULT NULL,
  `stripe_payment_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos`
--

CREATE TABLE `planos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `limite_leads` int(11) NOT NULL,
  `limite_mensagens` int(11) NOT NULL,
  `recursos` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `descricao` text DEFAULT NULL,
  `tem_ia` tinyint(1) DEFAULT 0,
  `stripe_price_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `planos`
--

INSERT INTO `planos` (`id`, `nome`, `preco`, `limite_leads`, `limite_mensagens`, `recursos`, `ativo`, `created_at`, `descricao`, `tem_ia`, `stripe_price_id`) VALUES
(1, 'Início', 97.00, 200, 1000, '[]', 1, '2025-02-07 17:25:13', 'Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', 0, 'prod_Rkt6EjKnp0Dj3T'),
(2, 'Pro', 127.00, 1000, 5000, '[]', 1, '2025-02-10 23:54:44', 'Características:- 1000 leads/mês- 5.000 mensagens/mês- Todos os recursos básicos- Acesso à IA para automação- Suporte prioritário- Analytics básico', 1, 'prod_Rkt7sCWw1TX8tN'),
(3, 'Negócio', 237.00, -1, -1, '[]', 1, '2025-02-11 14:39:34', 'Características:- Ilimitado leads/mês- Ilimitado mensagens/mês- Todos os recursos Pro- IA avançada- Suporte VIP- Analytics completo', 1, 'prod_Rkt9uBs1ZihwcU'),
(4, 'Plano Teste', 0.00, 100, 100, '[]', 1, '2025-02-11 16:36:18', 'Plano para período de teste', 1, '0');

-- --------------------------------------------------------

--
-- Estrutura para tabela `status_lead`
--

CREATE TABLE `status_lead` (
  `id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `status_lead`
--

INSERT INTO `status_lead` (`id`, `status`) VALUES
(1, 'Novo'),
(2, 'Em Contato'),
(3, 'Convertido'),
(4, 'Perdido');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `mensagem_base` text DEFAULT NULL,
  `token_dispositivo` varchar(255) DEFAULT NULL,
  `arquivo_padrao` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `plano_id` int(11) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expira` datetime DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `empresa` varchar(255) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  `perfil_completo` tinyint(1) DEFAULT 0,
  `nome_negocio` varchar(255) DEFAULT NULL,
  `segmento` varchar(100) DEFAULT NULL,
  `publico_alvo` varchar(255) DEFAULT NULL,
  `objetivo_principal` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `mensagem_base`, `token_dispositivo`, `arquivo_padrao`, `created_at`, `plano_id`, `reset_token`, `reset_token_expira`, `telefone`, `status`, `empresa`, `site`, `foto_perfil`, `data_cadastro`, `perfil_completo`, `nome_negocio`, `segmento`, `publico_alvo`, `objetivo_principal`) VALUES
(1, 'Publicidade Brasil Já LTDA', 'admin@publicidadeja.com.br', '$2y$10$gmcEBHa5LTknlwOA8flP6OKqWB4UV3JGf/sK3HhIaDlacL/sBwUbS', 'Olá, {nome} 👋 Esperamos que seu dia esteja sendo ótimo! 🌞\r\n\r\nGostaríamos de informar que seu Token do Dispositivo é: 19203333. Esse código é essencial para a segurança de suas informações. Caso tenha qualquer dúvida, fique à vontade para entrar em contato - estaremos felizes em ajudá-lo(a)! 🤝', '', 'LOGOTIPO ZAOLOCAL.png', '2025-02-07 10:03:45', NULL, 'd89c11800d721194898f4723df5d244fd40e68cfc165973be3232cb9d15a0b84', '2025-02-13 14:13:47', NULL, 'ativo', NULL, NULL, NULL, '2025-02-11 16:33:16', 0, NULL, NULL, NULL, NULL),
(2, 'Leonardo Nascimento', 'leocorax@gmail.com', '$2y$10$K0NdzpScVc1YUYp7PACUn.13uel7uXEyE1MEjtF49SyYIgGKXH4my', 'Olá, Leonardo Nascimento 👋 Esperamos que seu dia esteja sendo ótimo! 🌞\r\n\r\nGostaríamos de informar que seu Token do Dispositivo é: 19203333. Esse código é essencial para a segurança de suas informações. Caso tenha qualquer dúvida, fique à vontade para entrar em contato - estaremos felizes em ajudá-lo(a)! 🤝', '', '473406168_477716408498685_171445.jpg', '2025-02-11 02:15:20', 1, NULL, NULL, '35991944831', 'ativo', '', '', '../uploads/perfil/profile_67aab4b60b0de.jpg', '2025-02-11 16:33:16', 0, NULL, NULL, NULL, NULL),
(7, 'Juliana Rodrigues', 'juliana@publicidadeja.com.br', '$2y$10$wDNGNeloYSzkfDKkqNZJBenSxmUiMUfSt9JG5.WSgPDubE2R7b8TG', '{nome} , esse é o plano: Características:- 200 leads/mês- 1.000 mensagens/mês- Recursos básicos de automação- Sem acesso à IA- Suporte por email.', '', 'LOGO ZAPONTO.png', '2025-02-11 19:41:18', NULL, NULL, NULL, '35991944832', 'ativo', '', '', '../uploads/perfil/profile_67acef9612e28.png', '2025-02-11 16:41:18', 1, 'Livraria da Ju', 'varejo', 'Leitores e degustaroes de café', 'atendimento'),
(8, 'VIVIAN MACIENTE DO NASCIMENTO', 'vivian@publicidadeja.com.br', '$2y$10$yKbBlAuguy.62oPvNkwCw.4.fS8V4XBQypAQfz7ZHeTctwc3f6Us.', NULL, NULL, NULL, '2025-02-12 14:51:31', NULL, NULL, NULL, '35991944831', 'ativo', NULL, NULL, NULL, '2025-02-12 11:51:31', 0, NULL, NULL, NULL, NULL),
(9, 'Gabriel Nascimento', 'gabriel2@gmail.com', '$2y$10$0Zo3uWDLrOQeVWCRB6eUcekTz.AM8wxaJ5LHOCYBu47UvBmdn8dAG', NULL, NULL, NULL, '2025-02-12 14:57:42', NULL, NULL, NULL, '35991944831', 'ativo', NULL, NULL, NULL, '2025-02-12 11:57:42', 1, 'Bar do Biel', 'alimentacao', 'Pessoas com fome', 'vendas'),
(10, 'Leonardo Nascimento', 'contato@publicidadeja.com.br', '$2y$10$zboTYYofVBbCUqtpIYNWOu5DsAHkJfMF6xZvU5moie0Won/N9rc6m', NULL, NULL, NULL, '2025-02-12 15:08:06', NULL, NULL, NULL, '35991944831', 'ativo', NULL, NULL, NULL, '2025-02-12 12:08:06', 0, NULL, NULL, NULL, NULL),
(11, 'Gabriel Nascimento', 'gabriel@gmail.com', '$2y$10$qyaV5e52AVAWwwsJNJsxw.7byzscEiKgCnZ8ocqJ7qogqNmfa2mOS', NULL, NULL, NULL, '2025-02-12 17:23:35', NULL, NULL, NULL, '35991944831', 'ativo', '', '', '../uploads/perfil/profile_67acee5c91a44.png', '2025-02-12 14:23:35', 1, 'Bar do Biel', 'varejo', 'Leitores e degustaroes de café', 'vendas'),
(12, 'Bago', 'bagu@gmail.com', '$2y$10$84LvFprDGbum90bzpRgI4.NbNnzi8pCnkUXxmaacgBBhuu7dYwaAm', NULL, NULL, NULL, '2025-02-12 19:12:41', NULL, NULL, NULL, '35991944831', 'ativo', NULL, NULL, NULL, '2025-02-12 16:12:41', 1, 'Bagu', 'alimentacao', 'Pessoas com fome', 'fidelizacao');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD UNIQUE KEY `email_3` (`email`);

--
-- Índices de tabela `assinaturas`
--
ALTER TABLE `assinaturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `plano_id` (`plano_id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `dispositivos`
--
ALTER TABLE `dispositivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_device_id` (`device_id`);

--
-- Índices de tabela `envios_em_massa`
--
ALTER TABLE `envios_em_massa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_envios_usuario` (`usuario_id`);

--
-- Índices de tabela `fila_mensagens`
--
ALTER TABLE `fila_mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_status` (`usuario_id`,`status`);

--
-- Índices de tabela `ia_interacoes`
--
ALTER TABLE `ia_interacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `info_negocios`
--
ALTER TABLE `info_negocios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `leads_enviados`
--
ALTER TABLE `leads_enviados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_leads_usuario` (`usuario_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Índices de tabela `mensagens_enviadas`
--
ALTER TABLE `mensagens_enviadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `mensagens_fila`
--
ALTER TABLE `mensagens_fila`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `notas_lead`
--
ALTER TABLE `notas_lead`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_data_criacao` (`data_criacao`);

--
-- Índices de tabela `notificacoes_excluidas`
--
ALTER TABLE `notificacoes_excluidas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notificacao_id` (`notificacao_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `assinatura_id` (`assinatura_id`);

--
-- Índices de tabela `planos`
--
ALTER TABLE `planos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `status_lead`
--
ALTER TABLE `status_lead`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `assinaturas`
--
ALTER TABLE `assinaturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `dispositivos`
--
ALTER TABLE `dispositivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `envios_em_massa`
--
ALTER TABLE `envios_em_massa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `fila_mensagens`
--
ALTER TABLE `fila_mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=284;

--
-- AUTO_INCREMENT de tabela `ia_interacoes`
--
ALTER TABLE `ia_interacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `info_negocios`
--
ALTER TABLE `info_negocios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `leads_enviados`
--
ALTER TABLE `leads_enviados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT de tabela `mensagens_enviadas`
--
ALTER TABLE `mensagens_enviadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `mensagens_fila`
--
ALTER TABLE `mensagens_fila`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notas_lead`
--
ALTER TABLE `notas_lead`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de tabela `notificacoes_excluidas`
--
ALTER TABLE `notificacoes_excluidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `planos`
--
ALTER TABLE `planos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `status_lead`
--
ALTER TABLE `status_lead`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `assinaturas`
--
ALTER TABLE `assinaturas`
  ADD CONSTRAINT `assinaturas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `assinaturas_ibfk_2` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`);

--
-- Restrições para tabelas `dispositivos`
--
ALTER TABLE `dispositivos`
  ADD CONSTRAINT `dispositivos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `envios_em_massa`
--
ALTER TABLE `envios_em_massa`
  ADD CONSTRAINT `envios_em_massa_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `ia_interacoes`
--
ALTER TABLE `ia_interacoes`
  ADD CONSTRAINT `ia_interacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `info_negocios`
--
ALTER TABLE `info_negocios`
  ADD CONSTRAINT `info_negocios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `leads_enviados`
--
ALTER TABLE `leads_enviados`
  ADD CONSTRAINT `leads_enviados_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `leads_enviados_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `status_lead` (`id`);

--
-- Restrições para tabelas `mensagens_enviadas`
--
ALTER TABLE `mensagens_enviadas`
  ADD CONSTRAINT `mensagens_enviadas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `notas_lead`
--
ALTER TABLE `notas_lead`
  ADD CONSTRAINT `notas_lead_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads_enviados` (`id`);

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `pagamentos_ibfk_2` FOREIGN KEY (`assinatura_id`) REFERENCES `assinaturas` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
