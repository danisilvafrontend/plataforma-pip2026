<?php
// /public_html/admin/create_empreendedor.php
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
$config = require $appBase . '/config/db.php';

// Conexão direta com PDO para manipular a tabela empreendedores
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

// CSRF token (gera se não existe)
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];

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

// PROCESSAMENTO DO POST antes de qualquer saída
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, (string)$posted)) {
        $errors[] = 'Requisição inválida.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $sobrenome = trim($_POST['sobrenome'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $senhaInput = $_POST['senha'] ?? '';
        
        if (!$nome || !$sobrenome || !$email) {
            $errors[] = 'Preencha nome, sobrenome e e-mail válidos.';
        }

        // Verifica se o e-mail já existe
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM empreendedores WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Este e-mail já está cadastrado para outro empreendedor.";
            }
        }

        // se senha não veio no POST, gerar temporária; se veio, usar ela
        $tempPassword = '';
        if (trim($senhaInput) === '') {
            $tempPassword = generate_temp_password(10);
            $passwordToStore = password_hash($tempPassword, PASSWORD_DEFAULT);
        } else {
            $tempPassword = $senhaInput;
            $passwordToStore = password_hash($senhaInput, PASSWORD_DEFAULT);
        }

        if (empty($errors)) {
            try {
                // Inserir empreendedor
                $stmt = $pdo->prepare("INSERT INTO empreendedores (nome, sobrenome, email, senha_hash, status, criado_em) VALUES (?, ?, ?, ?, 'ativo', NOW())");
                $stmt->execute([$nome, $sobrenome, $email, $passwordToStore]);
                $createdId = $pdo->lastInsertId();

                if ($createdId) {
                    // Renderizar templates de e-mail
                    require_once $appBase . '/helpers/render.php';
                    require_once $appBase . '/helpers/mail.php';

                    $appName = 'Plataforma Impactos Positivos';
                    // Link para a página de login dos empreendedores
                    $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'seusite') . '/login.php';

                    $vars = [
                        'nome' => $nome . ' ' . $sobrenome,
                        'email' => $email,
                        'tempPassword' => $tempPassword,
                        'role' => 'Empreendedor',
                        'appName' => $appName,
                        'loginUrl' => $loginUrl
                    ];

                    $tplHtml = $appBase . '/views/emails/novo_empreendedor.php';
                    $tplTxt  = $appBase . '/views/emails/new_empreendedor.txt.php';


                    try {
                        $bodyHtml = render_email_template($tplHtml, $vars);
                    } catch (Throwable $e) {
                        $bodyHtml = null;
                    }
                    try {
                        $bodyPlain = render_email_template($tplTxt, $vars);
                    } catch (Throwable $e) {
                        $bodyPlain = "Olá {$nome},\n\nSua conta de Empreendedor foi criada em {$appName}.\n\nE-mail: {$email}\nSenha provisória: {$tempPassword}\n\nAcesse: {$loginUrl}\n\nPor favor, complete seu cadastro e altere sua senha no primeiro acesso.\n\nAtenciosamente,\n{$appName}";
                    }

                    $subject = "Sua conta de Empreendedor foi criada!";
                    $mailOk = send_mail($email, $nome . ' ' . $sobrenome, $subject, $bodyHtml, $bodyPlain);


                    if ($mailOk) {
                        $messages[] = "Empreendedor criado com sucesso! Credenciais enviadas para {$email}.";
                    } else {
                        $messages[] = "Empreendedor criado, mas falhou ao enviar o e-mail.";
                    }
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Criar Empreendedor</h2>
    <a href="/admin/empreendedores.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<?php if(!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if(!empty($messages)): ?>
    <div class="alert alert-success">
        <ul class="mb-0">
            <?php foreach($messages as $msg): ?>
                <li><?= htmlspecialchars($msg) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nome" id="nome" required>
                </div>
                <div class="col-md-6">
                    <label for="sobrenome" class="form-label">Sobrenome <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="sobrenome" id="sobrenome" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" id="email" required>
                <div class="form-text">As credenciais de acesso serão enviadas para este e-mail.</div>
            </div>

            <div class="mb-4">
                <label for="senha" class="form-label">Senha Provisória (opcional)</label>
                <input type="text" class="form-control" name="senha" id="senha" placeholder="Deixe em branco para gerar automaticamente">
                <div class="form-text">
                    Se você não preencher, o sistema criará uma senha forte aleatória e enviará no e-mail do empreendedor.
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Criar Empreendedor e Enviar Acesso
            </button>
        </form>
    </div>
</div>

<?php require_once $appBase . '/views/admin/footer.php'; ?>
