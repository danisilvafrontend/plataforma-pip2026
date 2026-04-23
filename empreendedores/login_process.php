<?php
// /empreendedores/login_process.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../app/helpers/functions.php';

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

$email    = sanitize_email($_POST['email'] ?? '');
$senha    = $_POST['senha'] ?? '';
$redirect = trim($_POST['redirect'] ?? '');

$errors = [];

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

$stmt = $pdo->prepare("SELECT id, nome, email, senha_hash 
                       FROM empreendedores 
                       WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$empreendedor = $stmt->fetch(PDO::FETCH_ASSOC);

if ($empreendedor && password_verify($senha, $empreendedor['senha_hash'])) {
    session_regenerate_id(true);

    $_SESSION['user_id']           = (int)$empreendedor['id'];
    $_SESSION['user_role']         = 'empreendedor';
    $_SESSION['empreendedor_id']   = (int)$empreendedor['id'];
    $_SESSION['empreendedor_nome'] = $empreendedor['nome'];
    $_SESSION['empreendedor_email'] = $empreendedor['email'];
    $_SESSION['logged_at']         = time();

    $update = $pdo->prepare("
        UPDATE empreendedores
        SET status = CASE
                        WHEN primeiro_acesso_pendente = 1 THEN 'ativo'
                        ELSE status
                    END,
            primeiro_acesso_pendente = 0,
            ultimo_login = NOW()
        WHERE id = ?
    ");
    $update->execute([$empreendedor['id']]);

    // Redireciona para a URL de origem ou para o dashboard
    $destino = ($redirect && str_starts_with($redirect, '/')) ? $redirect : '/empreendedores/dashboard.php';
    header("Location: " . $destino);
    exit;
} else {
    $_SESSION['login_error'] = "E-mail ou senha inválidos.";
    header("Location: /login.php" . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit;
}