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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: parceiros.php');
    exit;
}

$parceiro_id = (int)($_POST['parceiro_id'] ?? 0);
// CORREÇÃO: O select no modal se chama 'novo_status', não 'status'
$novo_status = trim($_POST['novo_status'] ?? '');

if ($parceiro_id <= 0 || $novo_status === '') {
    $_SESSION['erro'] = 'Dados inválidos para atualizar o status do parceiro.';
    header('Location: parceiros.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, nome_fantasia, razao_social, rep_nome, rep_email 
        FROM parceiros 
        WHERE id = ? 
        LIMIT 1
    ");
    $stmt->execute([$parceiro_id]);
    $parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parceiro) {
        $_SESSION['erro'] = 'Parceiro não encontrado.';
        header('Location: parceiros.php');
        exit;
    }

    $stmtUpdate = $pdo->prepare("UPDATE parceiros SET status = ? WHERE id = ?");
    $stmtUpdate->execute([$novo_status, $parceiro_id]);

    // SE FOI APROVADO, DISPARA O E-MAIL COM HEADER E FOOTER
    if ($novo_status === 'ativo' && !empty($parceiro['rep_email'])) { // No form o option é value="ativo"
        $subject = 'Sua parceria com a Plataforma Impactos Positivos foi ativada';
        
        $bodyHtml = '
            <p>Olá, {{nome}}!</p>

            <p>Temos uma ótima notícia: a parceria da organização <strong>{{organizacao}}</strong> foi ativada em nossa plataforma.</p>

            <p>A partir de agora, sua organização passa a integrar oficialmente a rede da Plataforma Impactos Positivos.</p>

            <p>Em breve, vocês poderão acompanhar as próximas orientações, ativações e oportunidades da parceria.</p>

            <p>Se precisar de apoio, nossa equipe está à disposição.</p>

            <p>Abraços,<br>Equipe Impactos Positivos</p>
        ';

        $rendered = render_email_from_db($subject, $bodyHtml, [
            'nome' => $parceiro['rep_nome'] ?: $parceiro['nome_fantasia'],
            'organizacao' => $parceiro['nome_fantasia'] ?: $parceiro['razao_social'],
            'email' => $parceiro['rep_email'],
            'ano' => date('Y')
        ]);

        $bodyAlt = strip_tags($rendered['bodyHtml']);

        send_mail(
            $parceiro['rep_email'],
            $parceiro['rep_nome'] ?: $parceiro['nome_fantasia'],
            $rendered['subject'],
            $rendered['bodyHtml'],
            $bodyAlt
        );
    }

    $_SESSION['sucesso'] = 'Status do parceiro atualizado com sucesso.';
    header('Location: parceiros.php');
    exit;

} catch (PDOException $e) {
    error_log('Erro ao processar status do parceiro: ' . $e->getMessage());
    $_SESSION['erro'] = 'Erro ao atualizar o status do parceiro.';
    header('Location: parceiros.php');
    exit;
}
