<?php
// /auth/forgot_password.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carrega configuração do banco
$configPath = __DIR__ . '/../app/config/db.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    die("Arquivo de configuração do banco não encontrado.");
}

$config = require $configPath;
if (!is_array($config)) {
    http_response_code(500);
    die("Configuração do banco inválida.");
}

require_once __DIR__ . '/../app/helpers/mail.php';
require_once __DIR__ . '/../app/helpers/render.php';

// Captura e valida o e-mail
$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "E-mail inválido.";
    exit;
}

// Conexão com o banco
try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die("Erro na conexão com o banco: " . $e->getMessage());
}
$user = null;
$tipo = null;
$tabelaEncontrada = null;

// 1) users
$stmt = $pdo->prepare("SELECT id, nome, role FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $user = $row;
    $tipo = $row['role'];
    $tabelaEncontrada = 'users';
}

// 2) empreendedores
if (!$user) {
    $stmt = $pdo->prepare("SELECT id, nome FROM empreendedores WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user = $row;
        $tipo = 'empreendedor';
        $tabelaEncontrada = 'empreendedores';
    }
}

// 3) parceiros
if (!$user) {
    $stmt = $pdo->prepare("SELECT id, nome_fantasia AS nome FROM parceiros WHERE email_login = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user = $row;
        $tipo = 'parceiro';
        $tabelaEncontrada = 'parceiros';
    }
}

// 4) sociedade_civil
if (!$user) {
    $stmt = $pdo->prepare("SELECT id, nome FROM sociedade_civil WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user = $row;
        $tipo = 'eleitor';
        $tabelaEncontrada = 'sociedade_civil';
    }
}

if ($user && $tipo && $tabelaEncontrada) {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = (new DateTime())->add(new DateInterval('PT1H'))->format('Y-m-d H:i:s');

    $update = $pdo->prepare("UPDATE {$tabelaEncontrada} SET password_reset_token = ?, password_reset_expires_at = ? WHERE id = ?");
    $update->execute([$tokenHash, $expiresAt, $user['id']]);

    $resetUrl = sprintf(
        "https://%s/auth/reset_password_form.php?email=%s&token=%s&tipo=%s",
        $_SERVER['HTTP_HOST'],
        urlencode($email),
        urlencode($token),
        urlencode($tipo)
    );

    $subject = "Redefinição de senha — Plataforma Impactos Positivos";
    $body = render_email_template(__DIR__ . '/../app/views/emails/reset_password.php', [
        'nome' => $user['nome'],
        'resetUrl' => $resetUrl,
        'expiresHours' => 1
    ]);

    send_mail($email, $user['nome'], $subject, $body);
}

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container d-flex flex-column min-vh-100">
  <div class="row justify-content-center flex-grow-1">
    <div class="col-md-6">
      <div class="alert alert-info mt-5">
        Se o e‑mail estiver cadastrado, você receberá um link para redefinir sua senha.
      </div>
      <div class="d-flex gap-3 mt-3">
        <a href="/login.php" class="btn btn-primary">Ir para Login</a>
        <a href="/" class="btn btn-secondary">Fechar</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>