<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$config = require __DIR__ . '/../app/config/db.php';

// 2. Cria a conexão PDO manualmente
try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro ao conectar no banco de dados: " . $e->getMessage());
}

$login = trim($_POST['login'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($login === '' || $senha === '') {
    $_SESSION['login_error'] = "Informe login e senha.";
    header("Location: /login.php");
    exit;
}

// tenta localizar por email ou CPF
$stmt = $pdo->prepare("SELECT * FROM sociedade_civil WHERE email = ? OR cpf = ?");
$stmt->execute([$login, preg_replace('/[^0-9]/', '', $login)]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
    $_SESSION['login_error'] = "Login ou senha inválidos.";
    header("Location: /login.php");
    exit;
}

// login ok
session_regenerate_id(false);

$_SESSION['logado']        = true;
$_SESSION['usuario_id']    = $usuario['id'];
$_SESSION['usuario_nome']  = $usuario['nome'];
$_SESSION['usuario_email'] = $usuario['email'];
$_SESSION['usuario_tipo']  = 'sociedade_civil';

header("Location: /sociedade_civil/minha_conta.php");
exit;