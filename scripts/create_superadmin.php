<?php
// create_superadmin.php
declare(strict_types=1);

$config = require __DIR__ . '/../app/config/db.php'; // ajuste o caminho se necessário

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'],
    $config['port'] ?? 3306,
    $config['dbname'],
    $config['charset'] ?? 'utf8mb4'
);

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Dados do superadmin — altere nome, email e senha antes de executar
$nome = 'Daniela';
$email = 'dani.dev@globalvisionaccess.com';
$plainPassword = 'D2ewb@0001';

// validações básicas
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "E-mail inválido\n";
    exit(1);
}
if (strlen($plainPassword) < 8) {
    echo "Senha muito curta (mínimo 8 caracteres)\n";
    exit(1);
}

// verifica se já existe
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
if ($stmt->fetch()) {
    echo "Já existe usuário com esse e-mail\n";
    exit(1);
}

$senhaHash = password_hash($plainPassword, PASSWORD_DEFAULT);

$insert = $pdo->prepare(
    'INSERT INTO users (nome, email, senha_hash, role, status, created_at, updated_at)
     VALUES (:nome, :email, :senha_hash, :role, :status, NOW(), NOW())'
);

$insert->execute([
    ':nome' => $nome,
    ':email' => $email,
    ':senha_hash' => $senhaHash,
    ':role' => 'superadmin',
    ':status' => 'ativo'
]);

echo "Superadmin criado com sucesso. ID: " . $pdo->lastInsertId() . PHP_EOL;