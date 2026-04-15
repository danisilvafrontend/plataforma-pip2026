<?php
// /public_html/admin/edit_user.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

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

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];

$um     = new UserModel();
$errors = [];
$messages = [];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /admin/administradores.php'); exit; }

$user = $um->getById($id);
if (!$user) { header('Location: /admin/administradores.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, (string)$posted)) {
        $errors[] = 'Requisição inválida.';
    } else {
        $nome            = trim($_POST['nome'] ?? '');
        $email           = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $senha           = $_POST['senha'] ?? '';
        $role            = $_POST['role'] ?? 'user';
        $requestedStatus = $_POST['status'] ?? $user['status'];

        if ($requestedStatus === 'inativo' && !is_superadmin()) {
            $errors[] = 'Apenas superadmins podem marcar contas como inativas.';
            $requestedStatus = $user['status'];
        }
        if (!$nome || !$email) $errors[] = 'Nome e e-mail são obrigatórios.';

        if (empty($errors)) {
            try {
                $um->update($id, [
                    'nome'   => $nome,
                    'email'  => $email,
                    'role'   => $role,
                    'status' => in_array($requestedStatus, ['ativo','pendente','suspenso','inativo','excluido'], true) ? $requestedStatus : $user['status'],
                    'senha'  => $senha !== '' ? $senha : null
                ]);
                $_SESSION['flash_message'] = 'Usuário atualizado com sucesso.';
                header('Location: /admin/administradores.php');
                exit;
            } catch (Throwable $e) {
                $errors[] = 'Erro ao atualizar usuário.';
                error_log('User update error: ' . $e->getMessage());
            }
        }
    }
}

$header = $appBase . '/views/admin/header.php';
$footer = $appBase . '/views/admin/footer.php';
if (is_file($header)) include $header; else echo "<h2>Editar Usuário</h2>";

// valores do formulário: POST tem prioridade, depois dados do banco
$fNome   = $_POST['nome']   ?? $user['nome'];
$fEmail  = $_POST['email']  ?? $user['email'];
$fRole   = $_POST['role']   ?? $user['role'];
$fStatus = $_POST['status'] ?? $user['status'];
?>

<!-- ── Alertas ───────────────────────────────────────── -->
<?php if (!empty($messages)): ?>
  <div class="alert d-flex align-items-center gap-2 mb-3" style="background:rgba(205,222,0,.15);border:1px solid rgba(205,222,0,.4);color:#1E3425;border-radius:10px;">
    <i class="bi bi-check-circle-fill" style="color:#7a8500;"></i>
    <?php foreach ($messages as $m) echo htmlspecialchars($m) . ' '; ?>
  </div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert d-flex align-items-center gap-2 mb-3" style="background:#fde8ea;border:1px solid #f5c2c7;color:#842029;border-radius:10px;">
    <i class="bi bi-exclamation-circle-fill"></i>
    <?php foreach ($errors as $e) echo htmlspecialchars($e) . ' '; ?>
  </div>
<?php endif; ?>

<!-- ── Cabeçalho ─────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0" style="color:#1E3425;">Editar Usuário</h4>
    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
      <span class="user-meta-badge"><i class="bi bi-hash"></i><?= $id ?></span>
      <span class="user-meta-badge"><i class="bi bi-person"></i><?= htmlspecialchars($user['nome'], ENT_QUOTES) ?></span>
    </div>
  </div>
  <a href="/admin/administradores.php" class="btn-cancel">
    <i class="bi bi-arrow-left me-1"></i> Voltar
  </a>
</div>

<!-- ── Formulário ────────────────────────────────────── -->
<div class="form-card">
  <div class="form-card-header">
    <div class="header-icon"><i class="bi bi-pencil-fill"></i></div>
    <h5>Dados do Usuário</h5>
  </div>
  <div class="form-card-body">
    <form method="post" class="row g-3" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">

      <!-- Nome -->
      <div class="col-md-6">
        <label class="form-label">Nome completo <span style="color:#dc3545;">*</span></label>
        <input name="nome" class="form-control" required
               value="<?= htmlspecialchars($fNome, ENT_QUOTES) ?>">
      </div>

      <!-- E-mail -->
      <div class="col-md-6">
        <label class="form-label">E-mail <span style="color:#dc3545;">*</span></label>
        <input name="email" type="email" class="form-control" required
               value="<?= htmlspecialchars($fEmail, ENT_QUOTES) ?>">
      </div>

      <!-- Senha -->
      <div class="col-md-4">
        <label class="form-label">Nova senha</label>
        <input name="senha" type="password" class="form-control" placeholder="••••••••••">
        <p class="form-hint"><i class="bi bi-info-circle me-1"></i>Deixe em branco para manter a senha atual.</p>
      </div>

      <!-- Role -->
      <div class="col-md-4">
        <label class="form-label">Perfil (Role)</label>
        <select name="role" class="form-select">
          <option value="user"  <?= $fRole === 'user'  ? 'selected' : '' ?>>User</option>
          <option value="admin" <?= $fRole === 'admin' ? 'selected' : '' ?>>Admin</option>
          <?php if (is_superadmin()): ?>
            <option value="superadmin" <?= $fRole === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
          <?php endif; ?>
        </select>
      </div>

      <!-- Status -->
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="ativo"    <?= $fStatus === 'ativo'    ? 'selected' : '' ?>>Ativo</option>
          <option value="pendente" <?= $fStatus === 'pendente' ? 'selected' : '' ?>>Pendente</option>
          <option value="suspenso" <?= $fStatus === 'suspenso' ? 'selected' : '' ?>>Suspenso</option>
          <option value="excluido" <?= $fStatus === 'excluido' ? 'selected' : '' ?>>Excluído</option>
          <?php if (is_superadmin()): ?>
            <option value="inativo" <?= $fStatus === 'inativo' ? 'selected' : '' ?>>Inativo</option>
          <?php endif; ?>
        </select>
      </div>

      <!-- Ações -->
      <div class="col-12">
        <hr style="border-color:rgba(30,52,37,.08);">
        <div class="d-flex justify-content-end gap-2">
          <a href="/admin/administradores.php" class="btn-cancel">Cancelar</a>
          <button class="btn-submit" type="submit">
            <i class="bi bi-floppy me-1"></i> Salvar alterações
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (is_file($footer)) include $footer; ?>