CREATE DATABASE IF NOT EXISTS formulario
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE formulario;

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

CREATE TABLE usuario (
  id_usuario int(11) NOT NULL AUTO_INCREMENT,
  nome varchar(100) NOT NULL,
  email varchar(100) NOT NULL,
  senha varchar(100) NOT NULL,
  tipo_perfil enum('ADMIN','CLIENTE') NOT NULL DEFAULT 'CLIENTE',
  telefone varchar(20) NOT NULL,
  endereco varchar(100) NOT NULL,
  PRIMARY KEY (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE produtos (
  id_produto int(11) NOT NULL AUTO_INCREMENT,
  nome_produto varchar(100) NOT NULL,
  descricao varchar(100) NOT NULL,
  preco decimal(10,2) NOT NULL,
  estoque int(11) NOT NULL,
  imagem varchar(255) NOT NULL,
  categoria varchar(100) NOT NULL,
  PRIMARY KEY (id_produto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE pedido (
  id_pedido int(11) NOT NULL AUTO_INCREMENT,
  quantidade int(11) NOT NULL,
  valor_total decimal(10,2) NOT NULL,
  endereco_entrega varchar(255) NOT NULL,
  data_entrega date NOT NULL,
  mensagem_cartao varchar(255) NOT NULL,
  forma_pagamento varchar(100) NOT NULL,
  status_pedido varchar(100) NOT NULL,
  data_pedido timestamp NOT NULL,
  id_cliente int(11) NOT NULL,
  id_produto int(11) NOT NULL,
  PRIMARY KEY (id_pedido),
  KEY fk_cliente (id_cliente),
  KEY fk_produto (id_produto),
  CONSTRAINT fk_cliente FOREIGN KEY (id_cliente) REFERENCES usuario (id_usuario),
  CONSTRAINT fk_produto FOREIGN KEY (id_produto) REFERENCES produtos (id_produto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
