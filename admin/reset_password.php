<?php
// /public_html/admin/reset_password.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../app/helpers/auth.php';

// Apenas admin ou superadmin podem acessar
require_admin_login();

$config = require __DIR__ . '/../app/config/db.php';

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO($dsn, $config['user'], $config['pass'], $options);

// Função para gerar senha temporária
function gerarSenhaTemporaria(int $length = 8): string {
    return substr(bin2hex(random_bytes($length)), 0, $length);
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID inválido.");
}

// Busca usuário
$stmt = $pdo->prepare("SELECT id, nome, email FROM empreendedores WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("Usuário não encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gera senha temporária
    $senhaTemp = gerarSenhaTemporaria();
    $hash = password_hash($senhaTemp, PASSWORD_DEFAULT);

    // Atualiza no banco
    $stmt = $pdo->prepare("UPDATE empreendedores SET senha_hash = ? WHERE id = ?");
    $stmt->execute([$hash, $id]);

    // Envia e-mail
    $assunto = "Plataforma Impactos Positivos - Sua nova senha temporária";
    $mensagem = "Olá {$usuario['nome']},\n\n".
                "Foi gerada uma nova senha temporária para sua conta.\n".
                "Sua senha temporária é: {$senhaTemp}\n".
                "Acesse o sistema em: https://pip2026.dscriacaoweb.com.br/login.php\n\n".
                "Recomendamos alterar a senha após o primeiro login.";

    $headers = "From: suporte@impactospositivos.com\r\n".
               "Reply-To: suporte@impactospositivos.com\r\n".
               "X-Mailer: PHP/" . phpversion();

    mail($usuario['email'], $assunto, $mensagem, $headers);

    $_SESSION['flash_message'] = "Senha temporária enviada para {$usuario['email']}.";
    header("Location: /admin/empreendedores.php");
    exit;
}
?>

<?php include __DIR__ . '/../app/views/admin/header.php'; ?>

<div class="container mt-4">
  <h4>Enviar senha temporária para usuário #<?= htmlspecialchars((string)$usuario['id']) ?></h4>

  <p>O e-mail atual do usuário é <strong><?= htmlspecialchars($usuario['email']) ?></strong>.</p>
  <form method="post">
    <button type="submit" class="btn btn-primary">Gerar e enviar senha temporária</button>
    <a href="/admin/empreendedores.php" class="btn btn-secondary">Cancelar</a>
  </form>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>