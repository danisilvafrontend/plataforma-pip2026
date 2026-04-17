<?php
// /public_html/admin/processar_lembrete_parceiro.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/mail.php';
require_once __DIR__ . '/../app/helpers/render.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: parceiros.php');
    exit;
}

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$parceiro_id    = (int)($_POST['parceiro_id'] ?? 0);
$mensagem_extra = trim($_POST['mensagem_extra'] ?? '');

if ($parceiro_id <= 0) {
    $_SESSION['erro'] = 'Parceiro inválido.';
    header('Location: parceiros.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, nome_fantasia, razao_social, rep_nome, rep_email, acordo_aceito
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

    if ((int)($parceiro['acordo_aceito'] ?? 0) === 1) {
        $_SESSION['erro'] = 'Este parceiro já assinou a carta-acordo.';
        header('Location: parceiros.php');
        exit;
    }

    if (empty($parceiro['rep_email'])) {
        $_SESSION['erro'] = 'Este parceiro não possui e-mail cadastrado.';
        header('Location: parceiros.php');
        exit;
    }

    $nome_organizacao = $parceiro['nome_fantasia'] ?: $parceiro['razao_social'] ?: 'sua organização';
    $nome_rep         = $parceiro['rep_nome'] ?: $nome_organizacao;
    $link_painel      = get_base_url() . '/parceiros/dashboard.php';

    // Bloco de mensagem extra (se preenchido)
    $bloco_extra = '';
    if ($mensagem_extra !== '') {
        $bloco_extra = '
            <div style="background:#f0f4ed; border-left:4px solid #CDDE00; padding:14px 18px;
                        margin:20px 0; border-radius:4px;">
                <p style="margin:0; font-size:14px; color:#1E3425;">
                    <strong>Mensagem da equipe:</strong><br>
                    ' . nl2br(htmlspecialchars($mensagem_extra)) . '
                </p>
            </div>';
    }

    $subject = 'Lembrete: Finalize sua parceria com a Plataforma Impactos Positivos';

    $bodyHtml = '
        <div style="font-family:Arial,sans-serif; color:#333; line-height:1.6;
                    max-width:600px; margin:0 auto; border:1px solid #eaeaea;
                    border-radius:8px; padding:30px; background:#ffffff;">

            <div style="text-align:center; margin-bottom:25px;">
                <h2 style="color:#1E3425; margin:10px 0 0 0;">
                    Não se esqueça de assinar a Carta-Acordo!
                </h2>
            </div>

            <p style="font-size:16px;">Olá, <strong>{{nome}}</strong>!</p>

            <p>
                Estamos animados com a possibilidade de ter a <strong>{{organizacao}}</strong>
                como parceira da <strong>Plataforma Impactos Positivos</strong>. 🎉
            </p>

            <p>
                Identificamos que a <strong>carta-acordo</strong> ainda não foi assinada,
                e esse é o único passo que falta para formalizar nossa parceria e darmos
                início juntos a todas as ações planejadas.
            </p>

            ' . $bloco_extra . '

            <div style="background:#fff8e1; border-left:4px solid #f59e0b;
                        padding:15px 18px; margin:25px 0; border-radius:4px;">
                <p style="margin:0; font-size:14px; color:#856404;">
                    <strong>⏳ O que fazer agora?</strong><br>
                    Acesse seu painel de parceiro, leia a carta-acordo e finalize sua assinatura.
                    É rápido e simples!
                </p>
            </div>

            <p style="text-align:center; margin:35px 0;">
                <a href="{{link_painel}}"
                   style="background:#CDDE00; color:#1E3425; padding:14px 30px;
                          text-decoration:none; border-radius:5px; font-weight:bold;
                          font-size:16px; display:inline-block;">
                    Acessar Meu Painel e Assinar
                </a>
            </p>

            <p>
                Se tiver qualquer dúvida ou dificuldade, é só responder este e-mail — nossa
                equipe está aqui para ajudar.
            </p>

            <hr style="border:none; border-top:1px solid #eee; margin:30px 0;">
            <p style="color:#666; font-size:14px; margin-bottom:5px;">
                Um grande abraço,<br>
                <strong>Equipe Impactos Positivos</strong>
            </p>
        </div>
    ';

    $rendered = render_email_from_db($subject, $bodyHtml, [
        'nome'        => $nome_rep,
        'organizacao' => $nome_organizacao,
        'email'       => $parceiro['rep_email'],
        'link_painel' => $link_painel,
        'ano'         => date('Y'),
    ]);

    $bodyAlt = strip_tags($rendered['bodyHtml']);

    send_mail(
        $parceiro['rep_email'],
        $nome_rep,
        $rendered['subject'],
        $rendered['bodyHtml'],
        $bodyAlt
    );

    $_SESSION['sucesso'] = 'Lembrete enviado com sucesso para ' . $parceiro['rep_email'] . '.';
    header('Location: parceiros.php');
    exit;

} catch (Throwable $e) {
    error_log('Erro ao enviar lembrete carta-acordo: ' . $e->getMessage());
    $_SESSION['erro'] = 'Erro ao enviar o lembrete. Tente novamente.';
    header('Location: parceiros.php');
    exit;
}