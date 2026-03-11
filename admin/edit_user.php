<?php
// /public_html/admin/edit_user.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

// localizar app automaticamente
$possibleAppPaths = [
    __DIR__ . '/../app',
    __DIR__ . '/../../app',
    __DIR__ . '/app',
    __DIR__ . '/../../../app'
];
$appBase = null;
foreach ($possibleAppPaths as $p) {
    if (is_dir($p)) { $appBase = realpath($p); break; }
}
if ($appBase === null) { http_response_code(500); echo "Erro: pasta app não encontrada."; exit; }

require_once $appBase . '/helpers/auth.php';
require_admin_login();
require_once $appBase . '/services/Database.php';
require_once $appBase . '/models/UserModel.php';

// CSRF token (gera se não existe)
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];

$um = new UserModel();
$errors = [];
$messages = [];

// pegar id do GET (se inválido redireciona para lista)
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /admin/usuarios.php'); exit; }

$user = $um->getById($id);
if (!$user) { header('Location: /admin/usuarios.php'); exit; }

// PROCESSAMENTO DO POST antes de qualquer saída (incluindo header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, (string)$posted)) {
        $errors[] = 'Requisição inválida.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $senha = $_POST['senha'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $requestedStatus = $_POST['status'] ?? $user['status'];

        // Apenas superadmin pode definir 'inativo'
        if ($requestedStatus === 'inativo' && !is_superadmin()) {
            $errors[] = 'Apenas superadmins podem marcar contas como inativas.';
            $requestedStatus = $user['status'];
        }

        if (!$nome || !$email) $errors[] = 'Nome e e-mail são obrigatórios.';

        if (empty($errors)) {
            try {
                $um->update($id, [
                    'nome' => $nome,
                    'email' => $email,
                    'role' => $role,
                    'status' => in_array($requestedStatus, ['ativo','pendente','suspenso','inativo','excluido'], true) ? $requestedStatus : $user['status'],
                    'senha' => $senha !== '' ? $senha : null
                ]);

                // flash message e redirect (sem saída antes)
                $_SESSION['flash_message'] = 'Usuário atualizado com sucesso.';
                header('Location: /admin/usuarios.php');
                exit;
            } catch (Throwable $e) {
                $errors[] = 'Erro ao atualizar usuário.';
                error_log('User update error: ' . $e->getMessage());
            }
        }
    }
}

// include do header e renderização da página
$header = $appBase . '/views/admin/header.php';
$footer = $appBase . '/views/admin/footer.php';
if (is_file($header)) include $header; else echo "<h2>Editar Usuário</h2>";

// exibir mensagens locais (se existirem)
if (!empty($messages)): ?>
  <div class="alert alert-success"><?php foreach($messages as $m) echo htmlspecialchars($m).' '; ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><?php foreach($errors as $e) echo htmlspecialchars($e).' '; ?></div>
<?php endif; ?>

<div class="row mb-3">
  <div class="col-8"><h4>Editar Usuário</h4></div>
  <div class="col-4 text-end"><a href="/admin/usuarios.php" class="btn btn-sm btn-secondary">Voltar</a></div>
</div>

<form method="post" class="row g-3" novalidate>
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES) ?>">
  <div class="col-md-6">
    <label class="form-label">Nome</label>
    <input name="nome" class="form-control" required value="<?php echo htmlspecialchars($_POST['nome'] ?? $user['nome'], ENT_QUOTES) ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">E-mail</label>
    <input name="email" type="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email'], ENT_QUOTES) ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">Senha (deixe em branco para manter)</label>
    <input name="senha" type="password" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Role</label>
    <select name="role" class="form-select">
      <option value="user" <?php echo ((($user['role'] ?? '') === 'user' && ($_POST['role'] ?? '') === '') || ($_POST['role'] ?? '') === 'user') ? 'selected' : '' ?>>User</option>
      <option value="admin" <?php echo ((($user['role'] ?? '') === 'admin' && ($_POST['role'] ?? '') === '') || ($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
      <?php if (is_superadmin()): ?>
        <option value="superadmin" <?php echo ((($user['role'] ?? '') === 'superadmin' && ($_POST['role'] ?? '') === '') || ($_POST['role'] ?? '') === 'superadmin') ? 'selected' : '' ?>>Superadmin</option>
      <?php endif; ?>
    </select>
  </div>

  <div class="col-md-4">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <option value="ativo" <?php echo ((($user['status'] ?? '') === 'ativo' && ($_POST['status'] ?? '') === '') || ($_POST['status'] ?? '') === 'ativo') ? 'selected' : '' ?>>Ativo</option>
      <option value="pendente" <?php echo ((($user['status'] ?? '') === 'pendente' && ($_POST['status'] ?? '') === '') || ($_POST['status'] ?? '') === 'pendente') ? 'selected' : '' ?>>Pendente</option>
      <option value="suspenso" <?php echo ((($user['status'] ?? '') === 'suspenso' && ($_POST['status'] ?? '') === '') || ($_POST['status'] ?? '') === 'suspenso') ? 'selected' : '' ?>>Suspenso</option>
      <option value="excluido" <?php echo ((($user['status'] ?? '') === 'excluido' && ($_POST['status'] ?? '') === '') || ($_POST['status'] ?? '') === 'excluido') ? 'selected' : '' ?>>Excluido</option>
      <?php if (is_superadmin()): ?>
        <option value="inativo" <?php echo ((($user['status'] ?? '') === 'inativo' && ($_POST['status'] ?? '') === '') || ($_POST['status'] ?? '') === 'inativo') ? 'selected' : '' ?>>Inativo</option>
      <?php endif; ?>
    </select>
  </div>

  <div class="col-12 text-end">
    <a class="btn btn-secondary" href="/admin/usuarios.php">Cancelar</a>
    <button class="btn btn-primary" type="submit">Salvar</button>
  </div>
</form>

<?php
if (is_file($footer)) include $footer;
?>