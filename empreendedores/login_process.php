<?php
// /empreendedores/login_process.php
declare(strict_types=1);
session_start();

// Exibir erros em desenvolvimento (remover em produção)
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Helpers
require_once __DIR__ . '/../app/helpers/functions.php';

// Configuração do banco
$config = require __DIR__ . '/../app/config/db.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'],
        $config['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    die("Erro na conexão com o banco: " . $e->getMessage());
}

// Captura dados do formulário
$email = sanitize_email($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

$errors = [];

// Validações básicas
if (!$email) {
    $errors[] = "Informe um e-mail válido.";
}
if (empty($senha)) {
    $errors[] = "Informe a senha.";
}

if (!empty($errors)) {
    http_response_code(422);
    foreach ($errors as $e) {
        echo "- " . htmlspecialchars($e, ENT_QUOTES) . "<br>";
    }
    exit;
}

// Busca empreendedor pelo e-mail
$stmt = $pdo->prepare("SELECT id, nome, email, senha_hash 
                       FROM empreendedores 
                       WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$empreendedor = $stmt->fetch(PDO::FETCH_ASSOC);

if ($empreendedor && password_verify($senha, $empreendedor['senha_hash'])) {
    session_regenerate_id(true);
    $_SESSION['empreendedor_id'] = (int)$empreendedor['id'];
    $_SESSION['empreendedor_nome'] = $empreendedor['nome']; // só nome
    $_SESSION['empreendedor_email'] = $empreendedor['email'];
    $_SESSION['logged_at'] = time();

    // Atualiza o último login
    $update = $pdo->prepare("UPDATE empreendedores SET ultimo_login = NOW() WHERE id = ?");
    $update->execute([$empreendedor['id']]);

    session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$empreendedor['id'];   // ID genérico usado pelo auth.php
        $_SESSION['user_role'] = 'empreendedor';           // role reconhecida pelo helper

        // Mantém também os campos específicos que você já usa
        $_SESSION['empreendedor_id'] = (int)$empreendedor['id'];
        $_SESSION['empreendedor_nome'] = $empreendedor['nome'];
        $_SESSION['empreendedor_email'] = $empreendedor['email'];
        $_SESSION['logged_at'] = time();

    header("Location: /empreendedores/dashboard.php");
    exit;
} else {
    // Salva mensagem de erro na sessão
    $_SESSION['login_error'] = "E-mail ou senha inválidos.";
    header("Location: /login.php");
    exit;
}