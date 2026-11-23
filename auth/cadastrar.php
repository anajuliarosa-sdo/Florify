<?php
require_once __DIR__.'/../config/db.php'; header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['sucesso'=>false,'mensagem'=>'Método não permitido.']); exit; }
$nome=trim($_POST['nome']??''); $email=trim($_POST['email']??''); $senha=$_POST['senha']??'';
if (strlen($nome)<3 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($senha)<8) { echo json_encode(['sucesso'=>false,'mensagem'=>'Confira nome, e-mail e senha (mínimo de 8 caracteres).']); exit; }
$st=$pdo->prepare('SELECT id_usuario FROM usuario WHERE email=?'); $st->execute([$email]);
if ($st->fetch()) { echo json_encode(['sucesso'=>false,'mensagem'=>'Este e-mail já está cadastrado.']); exit; }
$hash=password_hash($senha,PASSWORD_DEFAULT);
$st=$pdo->prepare("INSERT INTO usuario (nome,email,senha,tipo_perfil,telefone,endereco) VALUES (?,?,?,'CLIENTE','','')");
try { $st->execute([$nome,$email,$hash]); echo json_encode(['sucesso'=>true,'mensagem'=>'Cadastro realizado! Agora entre com seu e-mail e senha.']); }
catch(PDOException $e) { http_response_code(500); echo json_encode(['sucesso'=>false,'mensagem'=>'Não foi possível concluir o cadastro.']); }
