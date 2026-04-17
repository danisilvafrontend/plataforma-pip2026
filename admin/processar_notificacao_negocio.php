<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/mail.php';
require_once __DIR__ . '/../app/helpers/render.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: negocios.php'); exit;
}

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'], $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id     = (int)($_POST['negocio_id'] ?? 0);
$mensagem_extra = trim($_POST['mensagem_extra'] ?? '');

$etapas_nomes = [
    1 => 'Dados do Negócio',  2 => 'Fundadores',
    3 => 'Eixo Temático',     4 => 'ODS',
    5 => 'Financeiro',        6 => 'Impacto',
    7 => 'Visão de Futuro',   8 => 'Apresentação',
    9 => 'Documentação',      10 => 'Revisão Final',
];

try {
    $stmt = $pdo->prepare("
        SELECT n.id, n.nome_fantasia, n.etapa_atual, n.inscricao_completa,
               e.nome, e.email
        FROM negocios n
        JOIN empreendedores e ON n.empreendedor_id = e.id
        WHERE n.id = ?
        LIMIT 1
    ");
    $stmt->execute([$negocio_id]);
    $negocio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$negocio) {
        $_SESSION['erro'] = 'Negócio não encontrado.';
        header('Location: negocios.php'); exit;
    }

    // Busca o mesmo template de pendentes
    $stmtTpl = $pdo->prepare("SELECT subject, body_html FROM email_templates WHERE slug = 'negocios_pendentes'");
    $stmtTpl->execute();
    $template = $stmtTpl->fetch();

    if (!$template) {
        $_SESSION['erro'] = 'Template de e-mail não encontrado.';
        header('Location: negocios.php'); exit;
    }

    $etapa_nome = $etapas_nomes[(int)$negocio['etapa_atual']] ?? "Etapa {$negocio['etapa_atual']}";

    $bloco_extra = '';
    if ($mensagem_extra !== '') {
        $bloco_extra = '
            <div style="background:#f0f4ed; border-left:4px solid #CDDE00;
                        padding:14px 18px; margin:20px 0; border-radius:4px;">
                <p style="margin:0; font-size:14px; color:#1E3425;">
                    <strong>Mensagem da equipe:</strong><br>
                    ' . nl2br(htmlspecialchars($mensagem_extra)) . '
                </p>
            </div>';
    }

    // Injeta o bloco extra no body antes do fechamento da div
    $bodyHtml = $bloco_extra !== ''
    ? substr_replace($template['body_html'], $bloco_extra, strpos($template['body_html'], '</div>'), 0)
    : $template['body_html'];

    $rendered = render_email_from_db($template['subject'], $bodyHtml, [
        'nome'          => $negocio['nome'],
        'nome_fantasia' => $negocio['nome_fantasia'],
        'etapa_atual'   => $negocio['etapa_atual'],
        'etapa_nome'    => $etapa_nome,
        'link_cadastro' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/empreendedores/meus-negocios.php',
        'ano'           => date('Y'),
    ]);

    send_mail(
        $negocio['email'],
        $negocio['nome'],
        $rendered['subject'],
        $rendered['bodyHtml'],
        strip_tags($rendered['bodyHtml'])
    );

    $_SESSION['sucesso'] = "Notificação enviada para {$negocio['nome']} ({$negocio['email']}).";
    header('Location: negocios.php'); exit;

} catch (Throwable $e) {
    error_log('Erro notificacao negocio: ' . $e->getMessage());
    $_SESSION['erro'] = 'Erro ao enviar a notificação.';
    header('Location: negocios.php'); exit;
}