<?php
// /public_html/login.php
declare(strict_types=1);
session_start();


// Ajuste os requires conforme sua estrutura:
// Se app está DENTRO de public_html use as linhas abaixo (como seu arquivo atual):
require_once __DIR__ . '/app/services/Database.php';
require_once __DIR__ . '/app/models/UserModel.php';

// Se app estiver FÓRA da pasta public_html, substitua por:
// require_once __DIR__ . '/../app/services/Database.php';
// require_once __DIR__ . '/../app/models/UserModel.php';

$errors = [];
$email = null;

// Gera token CSRF simples
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // valida CSRF
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($token, (string)$postedToken)) {
        $errors[] = 'Requisição inválida. Atualize a página e tente novamente.';
    }

    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if (!$email) {
        $errors[] = 'Informe um e-mail válido.';
    }
    if (empty($senha)) {
        $errors[] = 'Informe a senha.';
    }

    if (empty($errors)) {
        try {
            $userModel = new UserModel();
            $user = $userModel->findByEmail($email);

            if ($user && password_verify($senha, $user['senha_hash'])) {
                if ($user['status'] !== 'ativo') {
                    $errors[] = 'Conta não está ativa.';
                } else {
                    // autentica
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_name'] = $user['nome'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['logged_at'] = time();

                    // atualiza last login (isolado em try/catch)
                    try {
                        $userModel->updateLastLogin((int)$user['id']);
                    } catch (Throwable $e) {
                        error_log('updateLastLogin error: ' . $e->getMessage());
                    }

                    // redireciona conforme role
                    $role = $user['role'];
                    if (in_array($role, ['juri', 'tecnica'], true)) {
                        header('Location: /admin/negocios.php');
                    } else {
                        header('Location: /admin/dashboard.php');
                    }
                    exit;
                }
            } else {
                $errors[] = 'Credenciais incorretas.';
            }
        } catch (Throwable $e) {
            // cria pasta de logs se necessário e grava stacktrace
            $logDir = __DIR__ . '/../storage/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            @file_put_contents($logDir . '/login_error.log',
                date('c') . ' | ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL
                . $e->getTraceAsString() . PHP_EOL . PHP_EOL, FILE_APPEND);

            $errors[] = 'Erro interno. Tente novamente mais tarde.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}

// Função auxiliar: tenta incluir header/footer públicos, retorna true se incluido
function try_include_public_header(): bool {
    $path = __DIR__ . '/../app/views/public/header_public.php';
    if (is_file($path)) {
        include $path;
        return true;
    }
    return false;
}
function try_include_public_footer(): bool {
    $path = __DIR__ . '/../app/views/public/footer_public.php';
    if (is_file($path)) {
        include $path;
        return true;
    }
    return false;
}

// Se header público existir, ele já deve imprimir <head> e abrir <body>.
// Caso contrário, usaremos o markup inline abaixo como fallback.
$header_included = try_include_public_header();
?>

<?php if (!$header_included): ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login Admin — Impactos Positivos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f5f7fb; }
    .card-login { max-width: 420px; margin: 6vh auto; }
    .brand { font-weight:700; letter-spacing: .5px; }
  </style>
</head>
<body>
<?php endif; ?>

  <div class="container">
    <div class="card card-login shadow-sm">
      <div class="card-body">
        <h3 class="card-title mb-3 text-center brand">Impactos Positivos</h3>
        <p class="text-center text-muted small">Área administrativa</p>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e, ENT_QUOTES) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" novalidate>
          <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($token, ENT_QUOTES)?>">
          <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input name="email" type="email" class="form-control" required value="<?=isset($email) ? htmlspecialchars($email, ENT_QUOTES) : ''?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Senha</label>
            <input name="senha" type="password" class="form-control" required>
          </div>
          <div class="d-grid">
            <button class="btn btn-primary" type="submit">Entrar</button>
            <a class="forgot-link" href="/auth/forgot_password_form.php">Esqueci minha senha</a>
          </div>
        </form>

      </div>
    </div>
  </div>

<?php
// inclui footer público se existir, caso contrário insere o fallback/footer básico
$footer_included = try_include_public_footer();
if (!$footer_included): ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>