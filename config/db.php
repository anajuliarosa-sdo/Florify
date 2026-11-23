<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$DB_HOST = getenv('FLORIFY_DB_HOST') ?: 'localhost';
$DB_NAME = getenv('FLORIFY_DB_NAME') ?: 'formulario';
$DB_USER = getenv('FLORIFY_DB_USER') ?: 'root';
$DB_PASS = getenv('FLORIFY_DB_PASS') ?: '';
try {
    $pdo = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados. Confira as configurações em config/db.php.');
}
function florify_e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function exigir_admin(): void {
    if (empty($_SESSION['id_usuario']) || ($_SESSION['tipo_perfil'] ?? '') !== 'ADMIN') { header('Location: login.php'); exit; }
}
function exigir_cliente(): void {
    if (empty($_SESSION['id_usuario']) || ($_SESSION['tipo_perfil'] ?? '') !== 'CLIENTE') { header('Location: login.php'); exit; }
}
