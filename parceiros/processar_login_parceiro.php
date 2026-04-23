<?php
session_start();
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /login.php");
    exit;
}

$login    = trim($_POST['login'] ?? '');
$senha    = $_POST['senha'] ?? '';
$redirect = trim($_POST['redirect'] ?? '');

if ($login === '' || $senha === '') {
    $_SESSION['login_error'] = "Informe o e-mail/CNPJ e a senha.";
    header("Location: /login.php" . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit;
}

try {
    $cnpj_limpo = preg_replace('/[^0-9]/', '', $login);

    $stmt = $pdo->prepare("SELECT id, nome_fantasia, senha_hash, etapa_atual FROM parceiros WHERE email_login = ? OR cnpj = ?");
    $stmt->execute([$login, $cnpj_limpo]);
    $parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parceiro || !password_verify($senha, $parceiro['senha_hash'])) {
        $_SESSION['login_error'] = "E-mail/CNPJ ou senha inválidos.";
        header("Location: /login.php" . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
        exit;
    }

    $_SESSION['parceiro_id']   = $parceiro['id'];
    $_SESSION['parceiro_nome'] = $parceiro['nome_fantasia'];

    // Se veio redirect externo (ex: negocio.php ou premiacao.php), usa ele
    if ($redirect && str_starts_with($redirect, '/')) {
        header("Location: " . $redirect);
        exit;
    }

    // Caso contrário, redireciona pela etapa do cadastro
    $etapa = (int) $parceiro['etapa_atual'];
    if ($etapa === 1)      header("Location: /parceiros/etapa1_dados.php");
    elseif ($etapa === 2)  header("Location: /parceiros/etapa2_tipo.php");
    elseif ($etapa === 3)  header("Location: /parceiros/etapa3_combinado.php");
    elseif ($etapa === 4)  header("Location: /parceiros/etapa4_interesses.php");
    elseif ($etapa === 5)  header("Location: /parceiros/etapa5_plataforma.php");
    elseif ($etapa === 6)  header("Location: /parceiros/etapa6_juridico.php");
    else                   header("Location: /parceiros/dashboard.php");
    exit;

} catch (PDOException $e) {
    error_log("Erro no login do parceiro: " . $e->getMessage());
    $_SESSION['login_error'] = "Erro interno ao processar login. Tente novamente.";
    header("Location: /login.php" . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit;
}