<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$identificador = trim($_POST['usuario'] ?? '');
$senha = $_POST['senha'] ?? '';
$modo = ($_POST['modo'] ?? 'cliente') === 'admin' ? 'admin' : 'cliente';

if ($identificador === '' || $senha === '') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Informe seu e-mail e sua senha.']);
    exit;
}

// A tabela usuario deste projeto possui e-mail, não possui um campo separado de nome de usuário.
$stmt = $pdo->prepare('SELECT id_usuario, nome, email, senha, tipo_perfil FROM usuario WHERE email = ? LIMIT 1');
$stmt->execute([$identificador]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($senha, $usuario['senha'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail ou senha incorretos.']);
    exit;
}

$perfilAdmin = strtoupper(trim((string) $usuario['tipo_perfil'])) === 'ADMIN';
if ($modo === 'admin' && !$perfilAdmin) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Esta conta ainda não tem perfil de administrador. Entre com uma conta ADMIN ou altere o perfil dessa conta no banco de dados.'
    ]);
    exit;
}

session_regenerate_id(true);
$_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
$_SESSION['nome'] = $usuario['nome'];
$_SESSION['tipo_perfil'] = $perfilAdmin ? 'ADMIN' : 'CLIENTE';

echo json_encode([
    'sucesso' => true,
    'mensagem' => 'Login realizado!',
    'redirecionar' => $perfilAdmin ? 'admin.php' : 'itens.php'
]);
