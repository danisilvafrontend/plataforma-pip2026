<?php
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$email        = $_POST['email']         ?? '';
$token        = $_POST['token']         ?? '';
$tipo         = $_POST['tipo']          ?? '';
$senha        = $_POST['senha']         ?? '';
$senhaConfirm = $_POST['senha_confirm'] ?? '';

$tabela = match ($tipo) {
    'admin'        => 'users',
    'empreendedor' => 'empreendedores',
    'parceiro'     => 'parceiros',
    'eleitor'      => 'sociedade_civil',
    default        => null
};

$colunaEmail = ($tabela === 'parceiros') ? 'email_login' : 'email';

if (!$email || !$token || !$senha || !$senhaConfirm || !$tabela) {
    die("Dados incompletos.");
}

if ($senha !== $senhaConfirm) {
    die("As senhas não coincidem.");
}

if (strlen($senha) < 8) {
    die("A senha deve ter pelo menos 8 caracteres.");
}

$tokenHash = hash('sha256', $token);
$stmt = $pdo->prepare("SELECT id FROM {$tabela} WHERE {$colunaEmail} = ? AND password_reset_token = ? AND password_reset_expires_at > NOW()");
$stmt->execute([$email, $tokenHash]);
$user = $stmt->fetch();

if (!$user) {
    die("Token inválido ou expirado.");
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);
$update = $pdo->prepare("UPDATE {$tabela} SET senha_hash = ?, password_reset_token = NULL, password_reset_expires_at = NULL WHERE id = ?");
$update->execute([$senhaHash, $user['id']]);

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container d-flex flex-column min-vh-100">
  <div class="row justify-content-center flex-grow-1">
    <div class="col-md-6">
      <div class="alert alert-success mt-5">
        Senha redefinida com sucesso. Você já pode fazer login.
      </div>
      <div class="d-flex gap-3 mt-3">
        <a href="/login.php" class="btn btn-primary">Ir para Login</a>
        <a href="/" class="btn btn-secondary">Fechar</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>