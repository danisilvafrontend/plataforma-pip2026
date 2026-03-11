<?php
// /public_html/admin/editar_empreendedor.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../app/helpers/auth.php';

// Apenas superadmin
if (!is_superadmin()) {
    http_response_code(403);
    die("Acesso negado. Apenas superadmin pode editar todos os dados.");
}

$config = require __DIR__ . '/../app/config/db.php';

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO($dsn, $config['user'], $config['pass'], $options);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID inválido.");
}

// Busca usuário
$stmt = $pdo->prepare("SELECT id, nome, email, status FROM empreendedores WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("Usuário não encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome   = trim($_POST['nome'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if ($nome === '' || $email === '') {
        $erro = "Nome e e-mail são obrigatórios.";
    } else {
        $stmt = $pdo->prepare("UPDATE empreendedores SET nome = ?, email = ?, status = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $status, $id]);

        $_SESSION['flash_message'] = "Usuário atualizado com sucesso!";
        header("Location: /admin/empreendedores.php");
        exit;
    }
}
?>

<?php include __DIR__ . '/../app/views/admin/header.php'; ?>

<div class="container mt-4">
  <h4>Editar usuário #<?= htmlspecialchars((string)$usuario['id']) ?></h4>

  <?php if (!empty($erro)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label for="nome" class="form-label">Nome</label>
      <input type="text" name="nome" id="nome" class="form-control"
             value="<?= htmlspecialchars($usuario['nome']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">E-mail</label>
      <input type="email" name="email" id="email" class="form-control"
             value="<?= htmlspecialchars($usuario['email']) ?>" required>
    </div>
    <div class="mb-3">
      <label for="status" class="form-label">Status</label>
      <select name="status" id="status" class="form-select">
        <option value="ativo" <?= $usuario['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
        <option value="inativo" <?= $usuario['status'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Salvar alterações</button>
    <a href="/admin/empreendedores.php" class="btn btn-secondary">Cancelar</a>
  </form>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>