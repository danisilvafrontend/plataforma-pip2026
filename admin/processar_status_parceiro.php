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
$novo_status = trim($_POST['novo_status'] ?? '');

$status_permitidos = ['analise', 'ativo', 'inativo'];

if ($parceiro_id <= 0 || $novo_status === '' || !in_array($novo_status, $status_permitidos, true)) {
    $_SESSION['erro'] = 'Dados inválidos para atualizar o status do parceiro.';
    header('Location: parceiros.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nome_fantasia,
            razao_social,
            rep_nome,
            rep_email,
            acordo_aceito
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

    // Só bloqueia ativação sem carta assinada
    if ($novo_status === 'ativo' && (int)($parceiro['acordo_aceito'] ?? 0) !== 1) {
        $_SESSION['erro'] = 'O status só pode ser alterado para Ativo após a assinatura da carta-acordo.';
        header('Location: visualizar_parceiro.php?id=' . $parceiro_id);
        exit;
    }

    $stmtUpdate = $pdo->prepare("UPDATE parceiros SET status = ? WHERE id = ?");
    $stmtUpdate->execute([$novo_status, $parceiro_id]);

    // Se foi aprovado, dispara o e-mail
    if ($novo_status === 'ativo' && !empty($parceiro['rep_email'])) {
        $subject = 'Parceria aprovada! Vamos gerar Impactos Positivos.';

        $bodyHtml = '
            <div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #eaeaea; border-radius: 8px; padding: 30px; background-color: #ffffff;">

                <div style="text-align: center; margin-bottom: 25px;">
                    <h2 style="color: #1D4F3A; margin: 10px 0 0 0;">Parceria ativa na plataforma!</h2>
                </div>

                <p style="font-size: 16px;">Olá, <strong>{{nome}}</strong>,</p>

                <p>Temos uma ótima notícia: a parceria com a <strong>{{organizacao}}</strong> está ativa na plataforma.</p>

                <div style="background-color: #f0f4ed; border-left: 4px solid #CDDE00; padding: 15px; margin: 25px 0; border-radius: 4px;">
                    <p style="margin: 0; font-size: 15px; color: #3a5a40;">
                        Agora vocês fazem parte de uma rede que conecta atores estratégicos para fortalecer negócios que estão transformando a economia.
                    </p>
                </div>

                <p style="text-align: center; margin: 35px 0;">
                    <a href="{{link_painel}}" style="background-color: #1D4F3A; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block; box-shadow: 0 4px 6px rgba(29,79,58,0.2);">
                        Acessar painel
                    </a>
                </p>

                <p style="font-size: 15px; color: #31443A;">
                    Nos próximos dias, entraremos em contato para alinhar as primeiras ações, cocriações e oportunidades dessa parceria.
                </p>

                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

                <p style="color: #666; font-size: 14px; margin-bottom: 5px;">Juntos, ampliamos o que o mundo tem de melhor.</p>
                <p style="color: #666; font-size: 14px; margin-top: 0;">Um abraço,<br><strong>Equipe Impactos Positivos</strong></p>

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