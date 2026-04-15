<?php
// /public_html/admin/create_user.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

$possibleAppPaths = [
    __DIR__ . '/../app',
    __DIR__ . '/../../app',
    __DIR__ . '/app',
    __DIR__ . '/../../../app'
];
$appBase = null;
foreach ($possibleAppPaths as $p) if (is_dir($p)) { $appBase = realpath($p); break; }
if ($appBase === null) { http_response_code(500); echo "Erro: pasta app não encontrada."; exit; }

require_once $appBase . '/helpers/auth.php';
require_admin_login();
require_once $appBase . '/services/Database.php';
require_once $appBase . '/models/UserModel.php';

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];

$um = new UserModel();
$errors   = [];
$messages = [];

if (!function_exists('generate_temp_password')) {
    function generate_temp_password(int $length = 10): string {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%&*?';
        $max = strlen($chars) - 1;
        $pw = '';
        for ($i = 0; $i < $length; $i++) $pw .= $chars[random_int(0, $max)];
        return $pw;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, (string)$posted)) {
        $errors[] = 'Requisição inválida.';
    } else {
        $nome            = trim($_POST['nome'] ?? '');
        $email           = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $senhaInput      = $_POST['senha'] ?? '';
        $role            = $_POST['role'] ?? 'user';
        $requestedStatus = $_POST['status'] ?? 'ativo';

        if ($requestedStatus === 'inativo' && !is_superadmin()) {
            $errors[] = 'Apenas superadmins podem marcar contas como inativas.';
            $requestedStatus = 'ativo';
        }
        if (!$nome || !$email) $errors[] = 'Preencha nome e e-mail.';

        $tempPassword = trim($senhaInput) === '' ? generate_temp_password(10) : $senhaInput;

        if (empty($errors)) {
            try {
                $createdId = $um->create([
                    'nome'   => $nome,
                    'email'  => $email,
                    'senha'  => $tempPassword,
                    'role'   => $role,
                    'status' => in_array($requestedStatus, ['ativo','pendente','suspenso','inativo','excluido'], true) ? $requestedStatus : 'ativo'
                ]);
                if ($createdId) {
                    require_once $appBase . '/helpers/render.php';
                    require_once $appBase . '/helpers/mail.php';
                    $appName  = 'Plataforma Impactos Positivos';
                    $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'seusite') . '/login.php';
                    $vars     = compact('nome','email','role','appName','loginUrl') + ['tempPassword' => $tempPassword];
                    $tplHtml  = $appBase . '/views/emails/new_user.php';
                    $tplTxt   = $appBase . '/views/emails/new_user.txt.php';
                    try { $bodyHtml = render_email_template($tplHtml, $vars); } catch (Throwable $e) { $bodyHtml = null; }
                    try { $bodyPlain = render_email_template($tplTxt, $vars); }
                    catch (Throwable $e) { $bodyPlain = "Olá {$nome}\n\nSua conta foi criada em {$appName}.\n\nE-mail: {$email}\nSenha: {$tempPassword}\n\nAcesse: {$loginUrl}\n\nAtenciosamente,\n{$appName}"; }
                    $sent = send_mail($email, $nome, "Conta criada na {$appName}", $bodyHtml, $bodyPlain);
                    if (!$sent) error_log("Falha ao enviar e-mail para {$email}");
                    $_SESSION['flash_message'] = 'Usuário criado com sucesso. E-mail de acesso enviado.';
                    header('Location: /admin/administradores.php');
                    exit;
                } else { $errors[] = 'Erro ao criar usuário.'; }
            } catch (Throwable $e) { $errors[] = 'Erro ao criar usuário.'; error_log('User create error: ' . $e->getMessage()); }
        }
    }
}

$header = $appBase . '/views/admin/header.php';
$footer = $appBase . '/views/admin/footer.php';
if (is_file($header)) include $header; else echo "<h2>Novo Usuário</h2>";
?>

<!-- ── Cabeçalho ─────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0" style="color:#1E3425;">Novo Administrador</h4>
    <small class="text-muted">Preencha os dados para criar a conta</small>
  </div>
  <a href="/admin/administradores.php" class="btn-cancel">
    <i class="bi bi-arrow-left me-1"></i> Voltar
  </a>
</div>

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

<!-- ── Formulário ────────────────────────────────────── -->
<div class="form-card">
  <div class="form-card-header">
    <div class="header-icon"><i class="bi bi-person-plus-fill"></i></div>
    <h5>Dados do Usuário</h5>
  </div>
  <div class="form-card-body">
    <form method="post" class="row g-3" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">

      <!-- Nome -->
      <div class="col-md-6">
        <label class="form-label">Nome completo <span style="color:#dc3545;">*</span></label>
        <input name="nome" class="form-control" required
               placeholder="Ex: João Silva"
               value="<?= htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES) ?>">
      </div>

      <!-- E-mail -->
      <div class="col-md-6">
        <label class="form-label">E-mail <span style="color:#dc3545;">*</span></label>
        <input name="email" type="email" class="form-control" required
               placeholder="usuario@email.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>">
      </div>

      <!-- Senha -->
      <div class="col-md-4">
        <label class="form-label">Senha</label>
        <input name="senha" type="password" class="form-control" placeholder="••••••••••">
        <p class="form-hint"><i class="bi bi-info-circle me-1"></i>Se deixado em branco, uma senha temporária será gerada e enviada por e-mail.</p>
      </div>

      <!-- Role -->
      <div class="col-md-4">
        <label class="form-label">Perfil (Role)</label>
        <select name="role" class="form-select">
          <option value="user"  <?= (($_POST['role'] ?? '') === 'user')  ? 'selected' : '' ?>>User</option>
          <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
          <?php if (is_superadmin()): ?>
            <option value="superadmin" <?= (($_POST['role'] ?? '') === 'superadmin') ? 'selected' : '' ?>>Superadmin</option>
          <?php endif; ?>
        </select>
      </div>

      <!-- Status -->
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="ativo"    <?= (($_POST['status'] ?? 'ativo') === 'ativo')    ? 'selected' : '' ?>>Ativo</option>
          <option value="pendente" <?= (($_POST['status'] ?? '') === 'pendente') ? 'selected' : '' ?>>Pendente</option>
          <option value="suspenso" <?= (($_POST['status'] ?? '') === 'suspenso') ? 'selected' : '' ?>>Suspenso</option>
          <option value="inativo"  <?= (($_POST['status'] ?? '') === 'inativo')  ? 'selected' : '' ?>>Inativo</option>
          <?php if (is_superadmin()): ?>
            <option value="excluido" <?= (($_POST['status'] ?? '') === 'excluido') ? 'selected' : '' ?>>Excluído</option>
          <?php endif; ?>
        </select>
      </div>

      <!-- Ações -->
      <div class="col-12">
        <hr style="border-color:rgba(30,52,37,.08);">
        <div class="d-flex justify-content-end gap-2">
          <a href="/admin/administradores.php" class="btn-cancel">Cancelar</a>
          <button class="btn-submit" type="submit">
            <i class="bi bi-person-check me-1"></i> Criar usuário
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (is_file($footer)) include $footer; ?>