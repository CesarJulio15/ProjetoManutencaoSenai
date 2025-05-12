-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 09/05/2025 às 18:18
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
-- Banco de dados: `db_manutencao`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

-- Removendo a antiga tabela de usuários, se existir
DROP TABLE IF EXISTS `usuarios`;

-- Criando a nova tabela de docentes
CREATE TABLE IF NOT EXISTS `docentes` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    data_nascimento DATE NOT NULL,
    nif VARCHAR(40) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    docente_status ENUM('Ativo', 'Inativo') NOT NULL DEFAULT 'Ativo',
    tipo ENUM('Docente', 'Coordenador', 'Técnico') NOT NULL DEFAULT 'Docente',
    sessao_ativa VARCHAR(255) DEFAULT NULL,
    ultima_data_login DATETIME DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados adaptados dos antigos usuários para a nova tabela docentes
INSERT INTO `docentes` (
  id, nome, data_nascimento, nif, email, senha, docente_status, tipo,
  sessao_ativa, ultima_data_login, reset_token, reset_token_expiry
) VALUES
(4, 'Ariel', '1990-01-01', '123456789', 'ariel@example.com', '$2y$10$EeZyAcYRVmq59y0V8Ho1/O6z6oGZHK8BJPEx5QffjE73uU8EToipu', 'Ativo', 'Coordenador', NULL, NULL, NULL, NULL),
(15, 'Ariel ', '1990-01-01', 'ariel1357', 'ariel2@example.com', '$2y$10$pCiXumO3LJ.r/PrwELZlo.vTg4QA7JkR1Ry3DweXsj93hwy2QALQy', 'Ativo', 'Coordenador', NULL, NULL, NULL, NULL),
(16, 'Felipe Gomes', '1990-01-01', 'FelipeG04', 'felipe@example.com', '$2y$10$kCQXd0F.IhgqqlWE6xDqlOYCEcosOfKLFHZ4i9yO4BLmXSCsgtbrq', 'Ativo', 'Docente', NULL, NULL, NULL, NULL),
(17, 'Andre', '1990-01-01', 'AndreEM01', 'andre@example.com', '$2y$10$DZc3v5FFQ0HoR927F084yewqJZ0n9HjzKN5leDRy8pidZVyevOat.', 'Ativo', 'Técnico', NULL, NULL, NULL, NULL),
(18, 'Nilton Canossa', '1990-01-01', 'NiltonC04', 'nilton@example.com', '$2y$10$7EXBiGmyq6D.PmhyCZ39OuPzJScqQbz5.DfjvNVDHqmOPYrOt8rfm', 'Ativo', 'Docente', NULL, NULL, NULL, NULL),
(19, 'Rogério Andrade', '1990-01-01', 'Rogerio04', 'rogerio@example.com', '$2y$10$eDdZxfvGY6q5ZGip4o4IguEEuaqnqsrRYCRNa6r3tShcLyKBdYcqC', 'Ativo', 'Docente', NULL, NULL, NULL, NULL),
(20, 'Uriel Jesus', '1990-01-01', 'UrielEM02', 'uriel@example.com', '$2y$10$seMTU9l0qlhwcswj9LMJOeONRNx1Z47wvRAdhugZMzzKy0lVjspIi', 'Ativo', 'Técnico', NULL, NULL, NULL, NULL);


--
-- Estrutura para tabela `manutencao`
--

CREATE TABLE `manutencao` (
  `titulo` varchar(50) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `responsavel` INT NOT NULL,
  `foto` blob DEFAULT NULL,
  `status` ENUM('Pendente', 'Em Andamento', 'Rejeitados','Concluído') NOT NULL default 'Pendente',
  `data_abertura` DATE NOT NULL,
  `data_encerramento` DATE,
  `executante` INT DEFAULT NULL,
  `prioridade` enum('Baixa','Média','Alta','Urgente') NOT NULL DEFAULT 'Média',
  `bloco` enum('G0','D0 - Secretaria','D0 - Auditório','A0','A1','B0 - Quadra','B0','B1','C0','C1','E0','F0') NOT NULL,
  FOREIGN KEY (responsavel) REFERENCES docentes(id) ON DELETE CASCADE,
  FOREIGN KEY (executante) REFERENCES docentes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `rejeicoes`
--

CREATE TABLE `rejeicoes` (
  `id` int(11) NOT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `rejeicoes`
--

INSERT INTO `rejeicoes` (`id`, `usuario`, `titulo`) VALUES
(8, 'AndreEM01', 'Bebedouro Quebrado'),
(9, 'AndreEM01', 'aaaaaa');

-- --------------------------------------------------------

--
-- Índices para tabelas despejadas
--
--
-- Índices de tabela `rejeicoes`
--
ALTER TABLE `rejeicoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices anteriores removidos pois a tabela `usuarios` foi substituída por `docentes`
--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `rejeicoes`
--
ALTER TABLE `rejeicoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
-- ALTER TABLE `usuarios` removido pois a tabela foi substituída
--
-- Restrições para tabelas despejadas
--
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
