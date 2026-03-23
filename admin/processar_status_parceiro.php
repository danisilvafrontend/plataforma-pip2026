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
        $subject = 'Sua parceria com a Plataforma Impactos Positivos foi Aprovada';
        
            $bodyHtml = '
            <div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #eaeaea; border-radius: 8px; padding: 30px; background-color: #ffffff;">
                
                <div style="text-align: center; margin-bottom: 25px;">
                    <h2 style="color: #0d6efd; margin: 10px 0 0 0;">Parceria Ativada com Sucesso!</h2>
                </div>

                <p style="font-size: 16px;">Olá, <strong>{{nome}}</strong>!</p>
                
                <p>Temos uma ótima notícia: a parceria da organização <strong>{{organizacao}}</strong> foi oficializada e ativada em nossa plataforma.</p>
                
                <div style="background-color: #e9f2ff; border-left: 4px solid #0d6efd; padding: 15px; margin: 25px 0; border-radius: 4px;">
                    <p style="margin: 0; font-size: 15px; color: #084298;">
                        A partir de agora, vocês integram oficialmente a rede da <strong>Plataforma Impactos Positivos</strong>. Juntos, vamos fortalecer e impulsionar negócios que transformam a sociedade e o meio ambiente.
                    </p>
                </div>

                <p style="text-align: center; margin: 35px 0;">
                    <a href="{{link_painel}}" style="background-color: #0d6efd; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block; box-shadow: 0 4px 6px rgba(13,110,253,0.2);">
                        Acessar Meu Painel de Parceiro
                    </a>
                </p>

                <h4 style="color: #444; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-top: 30px;">O que acontece agora?</h4>
                <ul style="color: #555; padding-left: 20px;">
                    <li style="margin-bottom: 10px;">Através do seu painel, você terá acesso a orientações, andamento da parceria e próximos passos.</li>
                    <li style="margin-bottom: 10px;">Em breve, nossa equipe entrará em contato para alinhar as primeiras ações conjuntas e oportunidades mapeadas.</li>
                </ul>

                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                
                <p style="color: #666; font-size: 14px; margin-bottom: 5px;">Se precisar de apoio ou quiser tirar alguma dúvida, nossa equipe está à total disposição.</p>
                <p style="color: #666; font-size: 14px; margin-top: 0;">Um grande abraço,<br><strong>Equipe Impactos Positivos</strong></p>
            </div>
        ';


        $rendered = render_email_from_db($subject, $bodyHtml, [
            'nome' => $parceiro['rep_nome'] ?: $parceiro['nome_fantasia'],
            'organizacao' => $parceiro['nome_fantasia'] ?: $parceiro['razao_social'],
            'email' => $parceiro['rep_email'],
            'link_painel' => get_base_url() . '/parceiros/dashboard.php',
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
