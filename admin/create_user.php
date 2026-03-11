<?php
// /public_html/admin/create_user.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

// localizar app
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

// CSRF token (gera se não existe)
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];

$um = new UserModel();
$errors = [];
$messages = [];

// helper: gerar senha temporária
if (!function_exists('generate_temp_password')) {
    function generate_temp_password(int $length = 10): string {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%&*?';
        $max = strlen($chars) - 1;
        $pw = '';
        for ($i = 0; $i < $length; $i++) {
            $pw .= $chars[random_int(0, $max)];
        }
        return $pw;
    }
}

// PROCESSAMENTO DO POST antes de qualquer saída (incluindo header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, (string)$posted)) {
        $errors[] = 'Requisição inválida.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $senhaInput = $_POST['senha'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $requestedStatus = $_POST['status'] ?? 'ativo';

        // apenas superadmin pode definir inativo
        if ($requestedStatus === 'inativo' && !is_superadmin()) {
            $errors[] = 'Apenas superadmins podem marcar contas como inativas.';
            $requestedStatus = 'ativo';
        }

        if (!$nome || !$email) {
            $errors[] = 'Preencha nome e e-mail.';
        }

        // se senha não veio no POST, gerar temporária; se veio, usar ela
        $tempPassword = '';
        if (trim($senhaInput) === '') {
            $tempPassword = generate_temp_password(10);
            $passwordToStore = $tempPassword;
        } else {
            $passwordToStore = $senhaInput;
            $tempPassword = $senhaInput; // enviar a senha informada no e-mail
        }

        if (empty($errors)) {
            try {
                // criar usuário (UserModel->create deve hash automaticamente)
                $createdId = $um->create([
                    'nome' => $nome,
                    'email' => $email,
                    'senha' => $passwordToStore,
                    'role' => $role,
                    'status' => in_array($requestedStatus, ['ativo','pendente','suspenso','inativo','excluido'], true) ? $requestedStatus : 'ativo'
                ]);

                if ($createdId) {
                    // Renderizar templates de e-mail
                    // carrega helpers de template e mail
                    require_once $appBase . '/helpers/render.php';
                    require_once $appBase . '/helpers/mail.php';

                    $appName = 'Plataforma Impactos Positivos';
                    $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'seusite') . '/login.php';

                    $vars = [
                        'nome' => $nome,
                        'email' => $email,
                        'tempPassword' => $tempPassword,
                        'role' => $role,
                        'appName' => $appName,
                        'loginUrl' => $loginUrl
                    ];

                    // paths dos templates
                    $tplHtml = $appBase . '/views/emails/new_user.php';
                    $tplTxt  = $appBase . '/views/emails/new_user.txt.php';

                    // gerar corpos (fallback simples se templates ausentes)
                    try {
                        $bodyHtml = render_email_template($tplHtml, $vars);
                    } catch (Throwable $e) {
                        $bodyHtml = null;
                    }
                    try {
                        $bodyPlain = render_email_template($tplTxt, $vars);
                    } catch (Throwable $e) {
                        // fallback plain
                        $bodyPlain = "Olá {$nome}\n\nSua conta foi criada em {$appName}.\n\nE-mail: {$email}\nSenha temporária: {$tempPassword}\nRole: {$role}\n\nAcesse: {$loginUrl}\n\nPor favor, troque sua senha no primeiro acesso.\n\nAtenciosamente,\n{$appName}";
                    }

                    $subject = "Conta criada na {$appName}";

                    // enviar email (não interromper fluxo em caso de falha de envio)
                    $sent = send_mail($email, $nome, $subject, $bodyHtml, $bodyPlain);
                    if (!$sent) {
                        error_log("Falha ao enviar e-mail para {$email}");
                    }

                    // flash message e redirect
                    $_SESSION['flash_message'] = 'Usuário criado com sucesso. E-mail de acesso enviado.';
                    header('Location: /admin/administradores.php');
                    exit;
                } else {
                    $errors[] = 'Erro ao criar usuário.';
                }
            } catch (Throwable $e) {
                $errors[] = 'Erro ao criar usuário.';
                error_log('User create error: ' . $e->getMessage());
            }
        }
    }
}

// incluir header e renderizar formulário
$header = $appBase . '/views/admin/header.php';
$footer = $appBase . '/views/admin/footer.php';
if (is_file($header)) include $header; else echo "<h2>Novo Usuário</h2>";
?>

<div class="row mb-3">
  <div class="col-8"><h4>Novo Usuário</h4></div>
  <div class="col-4 text-end"><a href="/admin/administradores.php" class="btn btn-sm btn-secondary">Voltar</a></div>
</div>

<?php if (!empty($messages)): ?><div class="alert alert-success"><?php foreach($messages as $m) echo htmlspecialchars($m).' ';?></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo htmlspecialchars($e).' ';?></div><?php endif; ?>

<form method="post" class="row g-3" novalidate>
  <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf, ENT_QUOTES)?>">
  <div class="col-md-6">
    <label class="form-label">Nome</label>
    <input name="nome" class="form-control" required value="<?=htmlspecialchars($_POST['nome'] ?? '')?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">E-mail</label>
    <input name="email" type="email" class="form-control" required value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">Senha (opcional — se deixado em branco será gerada automaticamente)</label>
    <input name="senha" type="password" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Role</label>
    <select name="role" class="form-select">
      <option value="user" <?= (($_POST['role'] ?? '') === 'user') ? 'selected' : '' ?>>User</option>
      <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
      <?php if (is_superadmin()): ?>
        <option value="superadmin" <?= (($_POST['role'] ?? '') === 'superadmin') ? 'selected' : '' ?>>Superadmin</option>
      <?php endif; ?>
    </select>
  </div>

  <div class="col-md-4">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <option value="ativo" <?= (($_POST['status'] ?? '') === 'ativo') ? 'selected' : '' ?>>Ativo</option>
      <option value="pendente" <?= (($_POST['status'] ?? '') === 'pendente') ? 'selected' : '' ?>>Pendente</option>
      <option value="suspenso" <?= (($_POST['status'] ?? '') === 'suspenso') ? 'selected' : '' ?>>Suspenso</option>
      <option value="inativo" <?= (($_POST['status'] ?? '') === 'inativo') ? 'selected' : '' ?>>Inativo</option>
      <?php if (is_superadmin()): ?>
        <option value="excluido" <?= (($_POST['status'] ?? '') === 'excluido') ? 'selected' : '' ?>>Excluido</option>
      <?php endif; ?>
    </select>
  </div>

  <div class="col-12 text-end">
    <a class="btn btn-secondary" href="/admin/usuarios.php">Cancelar</a>
    <button class="btn btn-primary" type="submit">Criar usuário</button>
  </div>
</form>

<?php if (is_file($footer)) include $footer; ?>