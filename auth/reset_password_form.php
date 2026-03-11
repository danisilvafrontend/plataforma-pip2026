<?php
// /auth/reset_password_form.php
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$tipo = $_GET['tipo'] ?? '';

$tabela = match ($tipo) {
    'admin' => 'users',
    'empreendedor' => 'empreendedores',
    'parceiro' => 'parceiros',
    'eleitor' => 'comunidade_civil',
    default => null
};

if (!$email || !$token || !$tabela) {
    die("Link inválido.");
}

$tokenHash = hash('sha256', $token);
$stmt = $pdo->prepare("SELECT id FROM {$tabela} WHERE email = ? AND password_reset_token = ? AND password_reset_expires_at > NOW()");
$stmt->execute([$email, $tokenHash]);
$user = $stmt->fetch();

if (!$user) {
    die("Link expirado ou inválido.");
}
include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container d-flex flex-column min-vh-100">
  <div class="row justify-content-center flex-grow-1">
    <div class="col-md-6">
      <div class="card shadow-sm mt-5">
        <div class="card-header bg-primary text-white">
          <h2 class="h5 mb-0">Redefinir senha</h2>
        </div>
        <div class="card-body">
          <form method="post" action="/auth/reset_password.php">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">

            <div class="mb-3">
              <label class="form-label">Nova senha</label>
              <input type="password" name="senha" class="form-control" required minlength="8">
            </div>

            <div class="mb-3">
              <label class="form-label">Confirmar nova senha</label>
              <input type="password" name="senha_confirm" class="form-control" required minlength="8">
            </div>

            <div class="d-flex gap-3">
              <button type="submit" class="btn btn-success">Salvar nova senha</button>
              <a href="/login.php" class="btn btn-secondary">Voltar ao login</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
