<?php
// /public_html/admin/enviar_email_negocios_pendentes.php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


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

// Busca negócios com inscrição incompleta
$stmt = $pdo->query("
    SELECT e.nome, e.email, n.nome_fantasia, n.etapa_atual
    FROM negocios n
    JOIN empreendedores e ON n.empreendedor_id = e.id
    WHERE n.inscricao_completa = 0
");
$pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca template do banco
$stmtTpl = $pdo->prepare("SELECT subject, body_html FROM email_templates WHERE slug = 'negocios_pendentes'");
$stmtTpl->execute();
$template = $stmtTpl->fetch();

$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'] ?? $template['subject'];
    $bodyHtml = $_POST['body_html'] ?? $template['body_html'];

    $enviados = 0;

    foreach ($pendentes as $p) {
        $rendered = render_email_from_db($subject, $bodyHtml, [
            'nome' => $p['nome'],
            'nome_fantasia' => $p['nome_fantasia'],
            'etapa_atual' => $p['etapa_atual']
        ]);

        $bodyAlt = strip_tags($rendered['bodyHtml']);

        if (send_mail($p['email'], $p['nome'], $rendered['subject'], $rendered['bodyHtml'], $bodyAlt)) {
            $enviados++;
        }
    }

    $msg = "Foram enviados {$enviados} e-mails para empreendedores com inscrição incompleta.";
}

include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="container my-5">
    <h1 class="mb-4">Enviar e-mail para empreendedores com negócios pendentes</h1>


    <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <p>Existem <strong><?= count($pendentes) ?></strong> negócios com inscrição incompleta.</p>

    <?php if (count($pendentes) > 0 && $template): ?>
        <form method="post">
            <div class="mb-3">
                <label for="subject" class="form-label">Assunto</label>
                <input type="text" name="subject" id="subject"
                    class="form-control"
                    value="<?= htmlspecialchars($template['subject']) ?>">
            </div>

            <div class="mb-3">
                <label for="body_html" class="form-label">Mensagem</label>
                <textarea name="body_html" id="body_html"
                        class="form-control wysiwyg" rows="10"><?= htmlspecialchars($template['body_html']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Enviar e-mails agora</button>
        </form>
    <?php elseif (!$template): ?>
        <div class="alert alert-warning">Template "negocio_pendente" não encontrado no banco de dados.</div>
    <?php else: ?>
        <div class="alert alert-info">Nenhum negócio pendente encontrado.</div>
    <?php endif; ?>

    <div class="card mb-4 mt-4">
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
</div>


<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>