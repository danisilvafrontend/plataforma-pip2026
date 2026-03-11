<?php
// /public_html/admin/usuarios.php
session_start();
require_once __DIR__ . '/../app/helpers/auth.php';

// só permite admin, superadmin ou juri (padrão do helper)
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

// Consulta empreendedores
$stmt = $pdo->query("SELECT id, nome, email, status FROM empreendedores ORDER BY nome ASC");
$usuarios = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../app/views/admin/header.php'; ?>

<div class="row mb-3">
  <div class="col-8"><h4>Usuários cadastrados</h4></div>
  <div class="col-4 text-end">
    <a href="/admin/create_user.php" class="btn btn-sm btn-primary">Novo usuário</a>
  </div>
</div>

<table class="table table-striped table-bordered">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nome</th>
      <th>Email</th>
      <th>Role</th>
      <th>Status</th>
      <th>Ações</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($usuarios)): ?>
      <tr><td colspan="6" class="text-center">Nenhum usuário encontrado.</td></tr>
    <?php else: ?>
      <?php foreach ($usuarios as $u): ?>
        <tr>
          <td><?= htmlspecialchars((string)$u['id']) ?></td>
          <td><?= htmlspecialchars($u['nome']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td>Empreendedor</td>
          <td><?= htmlspecialchars($u['status']) ?></td>
          <td>
            <?php if (is_superadmin()): ?>
              <a href="/admin/editar_empreendedor.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
              <a href="/admin/excluir_empreendedor.php?id=<?= $u['id'] ?>" 
                 class="btn btn-sm btn-danger"
                 onclick="return confirm('Tem certeza que deseja excluir este usuário?')">Excluir</a>
            <?php elseif (is_admin()): ?>
              <a href="/admin/reset_email.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-info">Editar Email</a>
              <a href="/admin/reset_password.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">Resetar Senha</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>