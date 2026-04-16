<?php
// /public_html/admin/rejeitar_negocio.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/mail.php';
require_once __DIR__ . '/../app/helpers/render.php';

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /admin/negocios.php");
    exit;
}

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id      = (int)($_POST['negocio_id'] ?? 0);
$pendencias      = $_POST['pendencias'] ?? [];       // array de strings
$observacao_livre = trim($_POST['observacao_livre'] ?? '');

if ($negocio_id <= 0) {
    $_SESSION['erro'] = 'ID do negócio inválido.';
    header("Location: /admin/negocios.php");
    exit;
}

// Labels legíveis para o e-mail
$labels_pendencias = [
    'dados_basicos'   => 'Dados básicos (nome, CNPJ, endereço) (Etapa 1 Dados do Negócio)',
    'fundadores'      => 'Dados dos fundadores (Etapa 2 Fundadores)',
    'eixo_tematico'   => 'Eixo temático / subáreas (Etapa 3 Eixo Temático)',
    'ods'             => 'ODS selecionadas (Etapa 4 ODS)',
    'financeiro'      => 'Informações financeiras (Etapa 5 Dados Financeiros)',
    'impacto'         => 'Dados de impacto social (Etapa 6 Avaliação de Impacto)',
    'visao'           => 'Visão de futuro e mercado (Etapa 7 Visão de Futuro)',
    'logotipo'        => 'Logotipo do negócio (Etapa 8 Apresentação)',
    'galeria_imagens' => 'Imagens da galeria (Etapa 8 Apresentação)',
    'video'           => 'Link de vídeo de apresentação (Etapa 8 Apresentação)',
    'descricao'       => 'Descrição / pitch do negócio (Etapa 8 Apresentação)',
    'documentos'      => 'Documentação Legal Etapa 9 (CNDT / Ambiental)',
];

try {
    // Busca dados do negócio + empreendedor
    $stmt = $pdo->prepare("
        SELECT n.nome_fantasia, n.status_vitrine, n.email_comercial,
               e.nome AS empreendedor_nome, e.email AS empreendedor_email
        FROM negocios n
        JOIN empreendedores e ON n.empreendedor_id = e.id
        WHERE n.id = ?
        LIMIT 1
    ");
    $stmt->execute([$negocio_id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) {
        $_SESSION['erro'] = 'Negócio não encontrado.';
        header("Location: /admin/negocios.php");
        exit;
    }

    $pdo->beginTransaction();

    // Atualiza status para indeferido
    $stmtUpdate = $pdo->prepare("
        UPDATE negocios
        SET status_vitrine   = 'indeferido',
            publicado_vitrine = 0,
            updated_at        = NOW()
        WHERE id = ?
    ");
    $stmtUpdate->execute([$negocio_id]);

    // Monta a lista de pendências em HTML
    $itens_pendencias_html = '';
    foreach ($pendencias as $chave) {
        $label = $labels_pendencias[$chave] ?? htmlspecialchars($chave);
        $itens_pendencias_html .= "<li style='margin-bottom:8px;'>⚠️ <strong>{$label}</strong></li>";
    }
    if (empty($itens_pendencias_html)) {
        $itens_pendencias_html = "<li>Revise o cadastro completo e corrija as informações necessárias.</li>";
    }

    // Observação extra do admin
    $bloco_obs = '';
    if (!empty($observacao_livre)) {
        $obs_escaped = nl2br(htmlspecialchars($observacao_livre));
        $bloco_obs = "
            <div style='background-color:#fff8e1;border-left:4px solid #ffc107;padding:15px;margin:20px 0;border-radius:4px;'>
                <strong>Observação da equipe:</strong><br>
                <span style='color:#555;'>{$obs_escaped}</span>
            </div>
        ";
    }

    // Link para o painel do empreendedor
    $link_painel = get_base_url() . '/empreendedores/meus-negocios.php';
    $nome_empreendedor = $dados['empreendedor_nome'] ?: 'Empreendedor';
    $nome_negocio      = $dados['nome_fantasia'];

    $emailDestino = !empty($dados['empreendedor_email'])
        ? $dados['empreendedor_email']
        : $dados['email_comercial'];

    if (!empty($emailDestino)) {
        $subject = 'Seu cadastro precisa de correções – Plataforma Impactos Positivos';

        $bodyHtml = "
            <div style='font-family:Arial,sans-serif;color:#333;line-height:1.6;max-width:600px;margin:0 auto;border:1px solid #eaeaea;border-radius:8px;padding:30px;background-color:#ffffff;'>

                <div style='text-align:center;margin-bottom:25px;'>
                    <h2 style='color:#dc3545;margin:10px 0 0 0;'>Cadastro com pendências</h2>
                </div>

                <p style='font-size:16px;'>Olá, <strong>{$nome_empreendedor}</strong>!</p>

                <p>Analisamos o cadastro do negócio <strong>{$nome_negocio}</strong> e identificamos alguns pontos que precisam ser revisados antes da publicação na vitrine.</p>

                <div style='background-color:#fff3f3;border-left:4px solid #dc3545;padding:15px;margin:25px 0;border-radius:4px;'>
                    <p style='margin:0 0 10px 0;'><strong>Itens que precisam de atenção:</strong></p>
                    <ul style='margin:0;padding-left:18px;color:#555;'>
                        {$itens_pendencias_html}
                    </ul>
                </div>

                {$bloco_obs}

                <p>Acesse seu painel, faça as correções necessárias e reenvie o cadastro para análise. Nossa equipe fará uma nova revisão assim que você reenviar.</p>

                <p style='text-align:center;margin:35px 0;'>
                    <a href='{$link_painel}' style='background-color:#0d6efd;color:#ffffff;padding:14px 30px;text-decoration:none;border-radius:5px;font-weight:bold;font-size:16px;display:inline-block;'>
                        Acessar meu painel e corrigir
                    </a>
                </p>

                <hr style='border:none;border-top:1px solid #eee;margin:30px 0;'>
                <p style='color:#666;font-size:14px;margin-bottom:5px;'>Qualquer dúvida, entre em contato com nossa equipe.</p>
                <p style='color:#666;font-size:14px;margin-top:0;'>Um abraço,<br><strong>Equipe Impactos Positivos</strong></p>
            </div>
        ";

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: Plataforma Impactos Positivos <nao-responda@dscriacaoweb.com.br>\r\n";

        send_mail($emailDestino, $nome_empreendedor, $subject, $bodyHtml, $headers);
    }

    $pdo->commit();

    $_SESSION['sucesso'] = "Cadastro indeferido. O empreendedor foi notificado por e-mail com as pendências.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['erro'] = "Erro: " . $e->getMessage();
}

header("Location: /admin/visualizar_negocio.php?id=" . $negocio_id);
exit;
?>