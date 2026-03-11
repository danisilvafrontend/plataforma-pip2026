<?php
// /public_html/admin/email_templates.php
session_start();
require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$msg = null;

// Atualização de template ou teste
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && isset($_POST['action']) && $_POST['action'] === 'save') {
        $stmt = $pdo->prepare("UPDATE email_templates SET subject = ?, body_html = ? WHERE id = ?");
        $stmt->execute([$_POST['subject'], $_POST['body_html'], $_POST['id']]);
        $msg = "Template atualizado com sucesso!";
    }

    if (isset($_POST['id']) && isset($_POST['action']) && $_POST['action'] === 'test') {
        $stmt = $pdo->prepare("SELECT subject, body_html FROM email_templates WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $tpl = $stmt->fetch();

        if ($tpl) {
            // substitui variáveis por dados fictícios
            $subject = str_replace(
                ['{{nome}}','{{email}}','{{dias}}','{{nome_fantasia}}','{{etapa_atual}}','{{ano}}'],
                ['Maria Silva','maria@teste.com','120','Loja Teste','2','2026'],
                $tpl['subject']
            );
            $bodyHtml = str_replace(
                ['{{nome}}','{{email}}','{{dias}}','{{nome_fantasia}}','{{etapa_atual}}','{{ano}}'],
                ['Maria Silva','maria@teste.com','120','Loja Teste','2','2026'],
                $tpl['body_html']
            );

            // aplica header/footer
            $header = '<div style="background:#0066cc;color:#fff;padding:20px;text-align:center"><h1>Impactos Positivos</h1></div>';
            $footer = '<div style="background:#f0f0f0;padding:15px;text-align:center;font-size:12px;color:#666">© '.date('Y').' Impactos Positivos</div>';
            $bodyHtml = $header . '<div style="padding:20px">'.$bodyHtml.'</div>' . $footer;

            $bodyAlt = strip_tags($bodyHtml);

            // envia para o admin logado
            $adminEmail = $_SESSION['user_email'] ?? null;
            $adminNome  = $_SESSION['user_name'] ?? 'Admin';

            if ($adminEmail && send_mail($adminEmail, $adminNome, $subject, $bodyHtml, $bodyAlt)) {
                $msg = "E-mail de teste enviado para {$adminEmail}.";
            } else {
                $msg = "Falha ao enviar e-mail de teste. Verifique se o admin logado possui e-mail cadastrado.";
            }
        }
    }
}
// Atualização de template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $stmt = $pdo->prepare("UPDATE email_templates SET subject = ?, body_html = ? WHERE id = ?");
    $stmt->execute([$_POST['subject'], $_POST['body_html'], $_POST['id']]);
    $msg = "Template atualizado com sucesso!";
}

// Consulta todos os templates
$stmt = $pdo->query("SELECT id, slug, subject, body_html FROM email_templates ORDER BY slug ASC");
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="container my-5">
    <h1 class="mb-4">Gerenciar Templates de E-mail</h1>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Variáveis disponíveis nos templates</h5>
            <p class="text-muted">Use estas variáveis no corpo ou no assunto do e-mail. Elas serão substituídas automaticamente pelos valores reais no momento do envio.</p>
            <ul class="list-group list-group-flush">
            <li class="list-group-item"><code>{{nome}}</code> → Nome do empreendedor</li>
            <li class="list-group-item"><code>{{email}}</code> → E-mail do empreendedor</li>
            <li class="list-group-item"><code>{{dias}}</code> → Quantidade de dias desde o último login</li>
            <li class="list-group-item"><code>{{nome_fantasia}}</code> → Nome fantasia do negócio</li>
            <li class="list-group-item"><code>{{etapa_atual}}</code> → Etapa atual do cadastro do negócio</li>
            <li class="list-group-item"><code>{{ano}}</code> → Ano atual (ex.: <?= date('Y') ?>)</li>
            </ul>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php foreach ($templates as $tpl): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Template: <?= htmlspecialchars($tpl['slug']) ?></h5>
                <div class="row">
                    <div class="col-lg-6">
                        <form method="post">
                            <input type="hidden" name="id" value="<?= $tpl['id'] ?>">

                            <div class="mb-3">
                                <label for="subject-<?= $tpl['id'] ?>" class="form-label">Assunto</label>
                                <input type="text" name="subject" id="subject-<?= $tpl['id'] ?>" 
                                    class="form-control" value="<?= htmlspecialchars($tpl['subject']) ?>">
                            </div>

                            <div class="mb-3">
                                <label for="body-<?= $tpl['id'] ?>" class="form-label">Corpo do e-mail</label>
                                <textarea name="body_html" id="body-<?= $tpl['id'] ?>" class="form-control wysiwyg" rows="8">
                                <?= htmlspecialchars($tpl['body_html']) ?>
                                </textarea>
                            </div>

                            <div class="mb-3">
                                <button type="submit" name="action" value="save" class="btn btn-primary">Salvar alterações</button>
                                <button type="submit" name="action" value="test" class="btn btn-secondary">Testar envio</button>
                            </div>
                        </form>
                    </div>

                <div class="col-lg-6">
                    <h6>Preview (com dados de exemplo)</h6>
                    <?php
                    // substitui variáveis por valores fictícios
                    $previewSubject = str_replace(
                        ['{{nome}}','{{email}}','{{dias}}','{{nome_fantasia}}','{{etapa_atual}}','{{ano}}'],
                        ['Maria Silva','maria@teste.com','120','Loja Teste','2','2026'],
                        $tpl['subject']
                    );
                    $previewBody = str_replace(
                        ['{{nome}}','{{email}}','{{dias}}','{{nome_fantasia}}','{{etapa_atual}}','{{ano}}'],
                        ['Maria Silva','maria@teste.com','120','Loja Teste','2','2026'],
                        $tpl['body_html']
                    );
                    ?>
                    <div class="border p-3 bg-light">
                    <strong>Assunto:</strong> <?= htmlspecialchars($previewSubject) ?><br><br>
                    <?= $previewBody ?>
                    </div>
                </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>



<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>
