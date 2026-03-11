<?php
session_start();
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/mail.php';
require_once __DIR__ . '/../app/helpers/render.php';

require_admin_login();

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Busca empreendedores com status
$stmt = $pdo->query("SELECT id, nome, email, status, ultimo_login FROM empreendedores");
$empreendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mensagem de feedback
$msg = $_GET['msg'] ?? null;

// Carrega templates do banco
$templates = [];
foreach (['ausente','desengajado','inativo'] as $slug) {
    $stmtTpl = $pdo->prepare("SELECT subject, body_html FROM email_templates WHERE slug = :slug");
    $stmtTpl->execute(['slug' => $slug]);
    $templates[$slug] = $stmtTpl->fetch();
}

// Contadores por status
$statusCounts = [
    'ausente' => 0,
    'desengajado' => 0,
    'inativo' => 0
];

foreach ($empreendedores as $e) {
    if (isset($statusCounts[$e['status']])) {
        $statusCounts[$e['status']]++;
    }
}

// Processamento do envio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $subject = $_POST['subject'] ?? '';
    $bodyHtml = $_POST['body_html'] ?? '';
    $enviados = 0;

    // Atualiza o template no banco
    $stmtUpdate = $pdo->prepare("UPDATE email_templates SET subject = :subject, body_html = :body WHERE slug = :slug");
    $stmtUpdate->execute([
        'subject' => $subject,
        'body'    => $bodyHtml,
        'slug'    => $action
    ]);

    // Envia os e-mails
    foreach ($empreendedores as $e) {
        if ($e['status'] === $action) {
            $dias = $e['ultimo_login'] ? (int)((time() - strtotime($e['ultimo_login'])) / 86400) : '';

            $rendered = render_email_from_db($subject, $bodyHtml, [
                'nome' => $e['nome'],
                'email' => $e['email'],
                'dias' => $dias,
                'ano' => date('Y')
            ]);

            $bodyAlt = strip_tags($rendered['bodyHtml']);

            if (send_mail($e['email'], $e['nome'], $rendered['subject'], $rendered['bodyHtml'], $bodyAlt)) {
                $enviados++;
            }
        }
    }

    // Redireciona para evitar reenvio e recarregar templates atualizados
    header("Location: enviar_email_status.php?msg=Foram enviados {$enviados} e-mails para empreendedores com status {$action}.");
    exit;
}

include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-6">
            <h1 class="mb-4 mt-4">Enviar e-mails por status de engajamento</h1>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4 mt-4">
                <div class="card-body">
                    <h5 class="card-title">Total de empreendedores: <strong><?= count($empreendedores) ?></strong></h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Ausentes: <strong><?= $statusCounts['ausente'] ?></strong></li>
                        <li class="list-group-item">Desengajados: <strong><?= $statusCounts['desengajado'] ?></strong></li>
                        <li class="list-group-item">Inativos: <strong><?= $statusCounts['inativo'] ?></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-7">
            <!-- Form Ausente -->
            <h4>Enviar para Empreendedores Ausentes</h4>
            <form method="post" class="mb-4">
                <input type="hidden" name="action" value="ausente">
                <div class="mb-3">
                    <label class="form-label">Assunto</label>
                    <input type="text" name="subject" class="form-control"
                        value="<?= htmlspecialchars($templates['ausente']['subject'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensagem</label>
                    <textarea name="body_html" class="form-control wysiwyg" rows="8"><?= htmlspecialchars($templates['ausente']['body_html'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-warning">Enviar para Ausentes</button>
            </form>

            <!-- Form Desengajado -->
            <h4>Enviar para Empreendedores Desengajados</h4>
            <form method="post" class="mb-4">
                <input type="hidden" name="action" value="desengajado">
                <div class="mb-3">
                    <label class="form-label">Assunto</label>
                    <input type="text" name="subject" class="form-control"
                        value="<?= htmlspecialchars($templates['desengajado']['subject'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensagem</label>
                    <textarea name="body_html" class="form-control wysiwyg" rows="8"><?= htmlspecialchars($templates['desengajado']['body_html'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-info">Enviar para Desengajados</button>
            </form>

            <!-- Form Inativo -->
            <h4>Enviar para Empreendedores Inativos</h4>
            <form method="post" class="mb-4">
                <input type="hidden" name="action" value="inativo">
                <div class="mb-3">
                    <label class="form-label">Assunto</label>
                    <input type="text" name="subject" class="form-control"
                        value="<?= htmlspecialchars($templates['inativo']['subject'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensagem</label>
                    <textarea name="body_html" class="form-control wysiwyg" rows="8"><?= htmlspecialchars($templates['inativo']['body_html'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-danger">Enviar para Inativos</button>
            </form>
        </div>

        <div class="col-md-5">
            <div class="card mb-4 mt-4">
                <div class="card-body">
                    <h5 class="card-title">Variáveis disponíveis nos templates</h5>
                    <p class="text-muted">Use estas variáveis no corpo ou no assunto do e-mail. Elas serão substituídas automaticamente pelos valores reais no momento do envio.</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><code>{{nome}}</code> → Nome do empreendedor</li>
                        <li class="list-group-item"><code>{{email}}</code> → E-mail do empreendedor</li>
                        <li class="list-group-item"><code>{{dias}}</code> → Dias desde o último login</li>
                        <li class="list-group-item"><code>{{ano}}</code> → Ano atual (<?= date('Y') ?>)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>