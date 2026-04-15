<?php
// /public_html/admin/create_empreendedor.php
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
$config = require $appBase . '/config/db.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados.");
}

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];

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
        $nome      = trim($_POST['nome'] ?? '');
        $sobrenome = trim($_POST['sobrenome'] ?? '');
        $email     = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $senhaInput = $_POST['senha'] ?? '';

        if (!$nome || !$sobrenome || !$email)
            $errors[] = 'Preencha nome, sobrenome e e-mail válidos.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM empreendedores WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) $errors[] = "Este e-mail já está cadastrado para outro empreendedor.";
        }

        $tempPassword = '';
        if (trim($senhaInput) === '') {
            $tempPassword    = generate_temp_password(10);
            $passwordToStore = password_hash($tempPassword, PASSWORD_DEFAULT);
        } else {
            $tempPassword    = $senhaInput;
            $passwordToStore = password_hash($senhaInput, PASSWORD_DEFAULT);
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO empreendedores (nome, sobrenome, email, senha_hash, status, criado_em) VALUES (?, ?, ?, ?, 'ativo', NOW())");
                $stmt->execute([$nome, $sobrenome, $email, $passwordToStore]);
                $createdId = $pdo->lastInsertId();

                if ($createdId) {
                    require_once $appBase . '/helpers/render.php';
                    require_once $appBase . '/helpers/mail.php';

                    $appName  = 'Plataforma Impactos Positivos';
                    $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
                              . ($_SERVER['HTTP_HOST'] ?? 'seusite') . '/login.php';

                    $vars = [
                        'nome'          => $nome . ' ' . $sobrenome,
                        'email'         => $email,
                        'tempPassword'  => $tempPassword,
                        'role'          => 'Empreendedor',
                        'appName'       => $appName,
                        'loginUrl'      => $loginUrl,
                    ];

                    $tplHtml = $appBase . '/views/emails/novo_empreendedor.php';
                    $tplTxt  = $appBase . '/views/emails/new_empreendedor.txt.php';

                    try { $bodyHtml  = render_email_template($tplHtml, $vars); }
                    catch (Throwable $e) { $bodyHtml = null; }

                    try { $bodyPlain = render_email_template($tplTxt, $vars); }
                    catch (Throwable $e) {
                        $bodyPlain = "Olá {$nome},\n\nSua conta de Empreendedor foi criada em {$appName}.\n\nE-mail: {$email}\nSenha provisória: {$tempPassword}\n\nAcesse: {$loginUrl}\n\nAtenciosamente,\n{$appName}";
                    }

                    $mailOk = send_mail($email, $nome . ' ' . $sobrenome, "Sua conta de Empreendedor foi criada!", $bodyHtml, $bodyPlain);

                    $messages[] = $mailOk
                        ? "Empreendedor criado com sucesso! Credenciais enviadas para {$email}."
                        : "Empreendedor criado, mas falhou ao enviar o e-mail.";
                }
            } catch (Throwable $t) {
                error_log("Erro em create_empreendedor: " . $t->getMessage());
                $errors[] = "Erro no Banco: " . $t->getMessage();
            }
        }
    }
}

$pageTitle = "Novo Empreendedor";
require_once $appBase . '/views/admin/header.php';
?>

<!-- ══════════════════════════════════
     Cabeçalho da página
═══════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0" style="color:#1E3425;">Novo Empreendedor</h4>
    <small style="color:#6c8070; font-size:.82rem;">Preencha os dados para criar o acesso</small>
  </div>
  <a href="/admin/empreendedores.php" class="hd-btn outline">
    <i class="bi bi-arrow-left"></i> Voltar
  </a>
</div>

<!-- Alertas -->
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger d-flex gap-2 align-items-start mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill mt-1"></i>
    <ul class="mb-0 ps-2">
      <?php foreach ($errors as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if (!empty($messages)): ?>
  <div class="alert alert-success d-flex gap-2 align-items-start mb-4" role="alert">
    <i class="bi bi-check-circle-fill mt-1"></i>
    <ul class="mb-0 ps-2">
      <?php foreach ($messages as $msg): ?>
        <li><?= htmlspecialchars($msg) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- ══════════════════════════════════
     Formulário
═══════════════════════════════════ -->
<div class="form-card" style="max-width: 680px;">
  <div class="form-card-header">
    <div class="header-icon"><i class="bi bi-person-plus-fill"></i></div>
    <h5>Dados do Empreendedor</h5>
  </div>
  <div class="form-card-body">
    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

      <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
          <label for="nome" class="form-label">
            Nome <span class="text-danger">*</span>
          </label>
          <input type="text" class="form-control" name="nome" id="nome"
                 placeholder="Ex: Ana" required>
        </div>
        <div class="col-12 col-md-6">
          <label for="sobrenome" class="form-label">
            Sobrenome <span class="text-danger">*</span>
          </label>
          <input type="text" class="form-control" name="sobrenome" id="sobrenome"
                 placeholder="Ex: Silva" required>
        </div>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">
          E-mail <span class="text-danger">*</span>
        </label>
        <input type="email" class="form-control" name="email" id="email"
               placeholder="empreendedor@email.com" required>
        <div class="form-hint">As credenciais de acesso serão enviadas para este e-mail.</div>
      </div>

      <div class="mb-4">
        <label for="senha" class="form-label">Senha Provisória
          <span style="font-weight:400; color:#9aab9d;">(opcional)</span>
        </label>
        <input type="text" class="form-control" name="senha" id="senha"
               placeholder="Deixe em branco para gerar automaticamente">
        <div class="form-hint">
          Se não preencher, o sistema criará uma senha forte aleatória e enviará por e-mail.
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn-submit">
          <i class="bi bi-person-plus me-1"></i> Criar e Enviar Acesso
        </button>
        <a href="/admin/empreendedores.php" class="btn-cancel">Cancelar</a>
      </div>

    </form>
  </div>
</div>

<?php require_once $appBase . '/views/admin/footer.php'; ?>