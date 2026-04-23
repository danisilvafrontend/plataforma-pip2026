<?php
// /public_html/admin/aprovar_negocio.php
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
            etapa_atual = '12'
        WHERE id = ?
    ");
    $stmtUpdate->execute([$negocio_id]);

    // Garante inscrição do negócio na premiação vigente após aprovação/publicação
    $stmtPremiacao = $pdo->query("
        SELECT id, nome, ano, status
        FROM premiacoes
        WHERE status IN ('ativa', 'planejada')
        ORDER BY 
            CASE WHEN status = 'ativa' THEN 0 ELSE 1 END,
            ano DESC,
            id DESC
        LIMIT 1
    ");
    $premiacaoVigente = $stmtPremiacao->fetch(PDO::FETCH_ASSOC);

    if (!empty($premiacaoVigente['id'])) {
        $stmtBuscaInscricao = $pdo->prepare("
            SELECT id, deseja_participar, aceite_regulamento, aceite_veracidade
            FROM premiacao_inscricoes
            WHERE premiacao_id = ? AND negocio_id = ?
            LIMIT 1
        ");
        $stmtBuscaInscricao->execute([(int)$premiacaoVigente['id'], $negocio_id]);
        $inscricaoAtual = $stmtBuscaInscricao->fetch(PDO::FETCH_ASSOC);

        if ($inscricaoAtual) {
            if (
                (int)$inscricaoAtual['deseja_participar'] === 1 &&
                (int)$inscricaoAtual['aceite_regulamento'] === 1 &&
                (int)$inscricaoAtual['aceite_veracidade'] === 1
            ) {
                $stmtAtualizaInscricao = $pdo->prepare("
                    UPDATE premiacao_inscricoes
                    SET
                        status = CASE
                            WHEN status IN ('rascunho', 'enviada') THEN 'em_triagem'
                            ELSE status
                        END,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmtAtualizaInscricao->execute([$inscricaoAtual['id']]);
            }
        }
    }

        // PREPARA E ENVIA O E-MAIL (Mesmo formato dos parceiros)
    $emailDestino = !empty($dados['empreendedor_email']) ? $dados['empreendedor_email'] : $dados['email_comercial'];

    if (!empty($emailDestino)) {
        $subject = 'Seu negócio foi aprovado na Vitrine Impactos Positivos!';
        
        $link_vitrine = get_base_url() . '/negocio.php?id=' . $negocio_id;
        $nome_empreendedor = $dados['empreendedor_nome'] ?: 'Empreendedor';
        $nome_negocio = $dados['nome_fantasia'];
        
        $bodyHtml = "
           <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #eaeaea; border-radius: 8px; padding: 30px; background-color: #ffffff;'>

            <div style='text-align: center; margin-bottom: 25px;'>
                <h2 style='color: #28a745; margin: 10px 0 0 0;'>Seu negócio está ativo na plataforma!</h2>
            </div>

            <p style='font-size: 16px;'>Olá, <strong>{$nome_empreendedor}</strong>,</p>

            <p>Seu negócio <strong>{$nome_negocio}</strong> foi analisado e já está ativo na plataforma.</p>

            <div style='background-color: #f0f4ed; border-left: 4px solid #28a745; padding: 15px; margin: 25px 0; border-radius: 4px;'>
                <p style='margin: 0; font-size: 15px; color: #3a5a40;'>
                    Agora ele faz parte de um ambiente criado para ampliar a visibilidade, gerar conexões e abrir novas oportunidades para negócios que estão transformando a economia. Está pronto para ser descoberto por parceiros, investidores e por nossa comunidade?
                </p>
            </div>

            <p style='text-align: center; margin: 35px 0;'>
                <a href='{$link_vitrine}' style='background-color: #28a745; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block; box-shadow: 0 4px 6px rgba(40,167,69,0.2);'>
                    Ver meu negócio
                </a>
            </p>

            <h4 style='color: #444; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-top: 30px;'>Para aproveitar melhor essa etapa:</h4>
            <ul style='color: #555; padding-left: 20px;'>
                <li style='margin-bottom: 10px;'>Compartilhe a página do seu negócio nas redes</li>
                <li style='margin-bottom: 10px;'>Mantenha as informações atualizadas</li>
                <li style='margin-bottom: 10px;'>Acompanhe novas conexões e oportunidades</li>
                <li style='margin-bottom: 10px;'>Descubra outras histórias de Impacto</li>
            </ul>

            <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>

            <p style='color: #666; font-size: 14px; margin-bottom: 5px;'>
                Seguimos por aqui, conectando e fortalecendo negócios como o seu. Se precisar de apoio, nossa equipe está à disposição.
            </p>
            <p style='color: #666; font-size: 14px; margin-top: 0;'>
                Um abraço,<br><strong>Equipe Impactos Positivos</strong>
            </p>

        </div>
        ";

        // Headers padrão que você usa no resto do sistema
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: Plataforma Impactos Positivos <nao-responda@dscriacaoweb.com.br>\r\n";

        // Envio nativo padronizado
        send_mail(
            $emailDestino,
            $nome_empreendedor,
            $subject,
            $bodyHtml,
            $headers // O 5º parâmetro é o header, não o bodyAlt
        );
    }


    $pdo->commit();

    $_SESSION['sucesso'] = "Negócio aprovado e publicado com sucesso! O empreendedor foi notificado.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Salvamos a mensagem original do erro para você ver o que quebrou!
    $_SESSION['erro'] = "Erro Real: " . $e->getMessage();
}


header("Location: /admin/visualizar_negocio.php?id=" . $negocio_id);
exit;
?>
