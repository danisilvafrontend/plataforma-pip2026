<?php
// /public_html/admin/reset_email.php
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

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
} catch (PDOException $e) {
    die('Erro na conexão com o banco: ' . $e->getMessage());
}

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
    $novoEmail = trim($_POST['email'] ?? '');
    if ($novoEmail === '') {
        $erro = "O campo e-mail não pode estar vazio.";
    } else {
        // Gera senha temporária
        $senhaTemp = gerarSenhaTemporaria();
        $hash = password_hash($senhaTemp, PASSWORD_DEFAULT);

        // Atualiza no banco
        $stmt = $pdo->prepare("UPDATE empreendedores SET email = ?, senha_hash = ? WHERE id = ?");
        $stmt->execute([$novoEmail, $hash, $id]);

        // Envia e-mail
        $assunto = "Plataforma Impactos Positivos - Sua nova senha temporária";
        $mensagem = "Olá {$usuario['nome']},\n\n".
                    "Seu e-mail foi atualizado para {$novoEmail}.\n".
                    "Sua senha temporária é: {$senhaTemp}\n".
                    "Acesse o sistema em: https://pip2026.dscriacaoweb.com.br/login.php\n\n".
                    "Recomendamos alterar a senha após o primeiro login.";

        // Ajuste o cabeçalho conforme seu servidor
        $headers = "From: suporte@impactospositivos.com\r\n".
                   "Reply-To: suporte@impactospositivos.com\r\n".
                   "X-Mailer: PHP/" . phpversion();

        mail($novoEmail, $assunto, $mensagem, $headers);

        $_SESSION['flash_message'] = "E-mail atualizado e senha temporária enviada para {$novoEmail}.";
        header("Location: /admin/empreendedores.php");
        exit;
    }
}
?>

<?php include __DIR__ . '/../app/views/admin/header.php'; ?>

<div class="container mt-4">
  <h4>Editar e-mail do usuário #<?= htmlspecialchars((string)$usuario['id']) ?></h4>

  <?php if (!empty($erro)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label for="email" class="form-label">Novo e-mail</label>
      <input type="email" name="email" id="email" class="form-control"
             value="<?= htmlspecialchars($usuario['email']) ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Salvar e enviar senha temporária</button>
    <a href="/admin/empreendedores.php" class="btn btn-secondary">Cancelar</a>
  </form>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>