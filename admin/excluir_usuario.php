<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

if (!is_superadmin()) {
    http_response_code(403);
    exit('Acesso negado.');
}

$config = require __DIR__ . '/../app/config/db.php';
$dsn    = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}";
$opts   = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $opts);
} catch (PDOException $e) {
    die('Erro na conexão com o banco: ' . $e->getMessage());
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /admin/usuarios.php');
    exit;
}

$stmt = $pdo->prepare("DELETE FROM sociedade_civil WHERE id = ?");
$stmt->execute([$id]);

header('Location: /admin/usuarios.php?excluido=1');
exit;