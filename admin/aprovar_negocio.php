<?php
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

$negocio_id = (int)($_GET['id'] ?? 0);

if ($negocio_id <= 0) {
    $_SESSION['erro'] = 'ID do negócio inválido.';
    header("Location: /admin/negocios.php");
    exit;
}
try {
    // Busca dados do negócio e do empreendedor para o e-mail
    $stmt = $pdo->prepare("
        SELECT n.nome_fantasia, n.status_vitrine, n.email_comercial, e.nome as empreendedor_nome, e.email as empreendedor_email 
        FROM negocios n
        JOIN empreendedores e ON n.empreendedor_id = e.id
        WHERE n.id = ?
        LIMIT 1
    ");
    $stmt->execute([$negocio_id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        $_SESSION['erro'] = "Negócio não encontrado.";
        header("Location: /admin/negocios.php");
        exit;
    }

    if ($dados['status_vitrine'] === 'aprovado') {
        $_SESSION['sucesso'] = "Este negócio já estava aprovado e publicado.";
        header("Location: /admin/visualizar_negocio.php?id=" . $negocio_id);
        exit;
    }

    $pdo->beginTransaction();

    // Aprova o negócio e publica na vitrine
    $stmtUpdate = $pdo->prepare("
        UPDATE negocios 
        SET status_vitrine = 'aprovado', 
            publicado_vitrine = 1,
            publicado_em = NOW(),
            etapa_atual = 'publicado'
        WHERE id = ?
    ");
    $stmtUpdate->execute([$negocio_id]);

    // PREPARA E ENVIA O E-MAIL (Mesmo formato dos parceiros)
    $emailDestino = !empty($dados['empreendedor_email']) ? $dados['empreendedor_email'] : $dados['email_comercial'];

    if (!empty($emailDestino)) {
        $subject = 'Seu negócio foi aprovado na Vitrine Impactos Positivos!';
        
        $bodyHtml = '
            <p>Olá, {{nome}}!</p>

            <p>Temos uma ótima notícia: o cadastro do seu negócio <strong>{{negocio}}</strong> foi analisado e <strong>aprovado</strong> por nossa equipe.</p>

            <p>Ele já está publicado e visível na vitrine da Plataforma Impactos Positivos.</p>

            <p style="text-align: center; margin: 30px 0;">
                <a href="{{link_vitrine}}" style="background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Ver meu negócio na vitrine</a>
            </p>

            <p>Continue mantendo seus dados atualizados para atrair mais parceiros e oportunidades.</p>

            <p>Se precisar de apoio, nossa equipe está à disposição.</p>

            <p>Abraços,<br>Equipe Impactos Positivos</p>
        ';

        // Renderiza o email substituindo as variáveis {{...}}
                $rendered = render_email_from_db($subject, $bodyHtml, [
            'nome' => $dados['empreendedor_nome'] ?: 'Empreendedor',
            'negocio' => $dados['nome_fantasia'],
            'email' => $emailDestino,
            'link_vitrine' => get_base_url() . '/negocio.php?id=' . $negocio_id, // DINÂMICO ✅
            'ano' => date('Y')
        ]);


        $bodyAlt = strip_tags($rendered['bodyHtml']);

        // Função de envio oficial do sistema
        send_mail(
            $emailDestino,
            $dados['empreendedor_nome'] ?: 'Empreendedor',
            $rendered['subject'],
            $rendered['bodyHtml'],
            $bodyAlt
        );
    }

    $pdo->commit();

    $_SESSION['sucesso'] = "Negócio aprovado e publicado com sucesso! O empreendedor foi notificado.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao aprovar negócio ID $negocio_id: " . $e->getMessage());
    $_SESSION['erro'] = "Erro interno ao tentar aprovar o negócio.";
}

header("Location: /admin/visualizar_negocio.php?id=" . $negocio_id);
exit;
?>
