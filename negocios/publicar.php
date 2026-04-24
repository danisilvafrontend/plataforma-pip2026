<?php
// /public_html/negocios/publicar.php
session_start();

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Helpers de e-mail 
require_once __DIR__ . '/../app/helpers/mail.php';
require_once __DIR__ . '/../app/helpers/email_template.php';

$negocioId = (int)($_GET['negocio_id'] ?? $_POST['negocio_id'] ?? $_GET['id'] ?? $_POST['id'] ?? 0);

$empreendedorId = $_SESSION['user_id'];

if ($negocioId === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}

$colDono = 'empreendedor_id';

// Verifica se negócio existe e pertence ao user
$stmt = $pdo->prepare("
    SELECT id, nome_fantasia, categoria, status_vitrine, etapa_atual, inscricao_completa 
    FROM negocios 
    WHERE id = ? AND {$colDono} = ? LIMIT 1
");
$stmt->execute([$negocioId, $empreendedorId]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$negocio) {
    die('Negócio não encontrado ou sem permissão.');
}

// Verifica se docs foram enviadas (obrigatório para aprovação)
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM negocios_documentos 
    WHERE negocio_id = ? 
    AND certidao_trabalhista_path IS NOT NULL 
    AND certidao_ambiental_path IS NOT NULL
");
$stmt->execute([$negocioId]);
$docsOk = $stmt->fetchColumn() > 0;

if (!$docsOk) {
    $_SESSION['erro'] = 'Envie primeiro as certidões trabalhista e ambiental (Etapa 9).';
    header("Location: /negocios/confirmacao.php?id=" . $negocioId);
    exit;
}
$acao = $_POST['acao'] ?? '';

$premiacaoDesejaParticipar  = ($acao === 'publicar_com_premiacao') ? 1 : 0;
$premiacaoAceiteRegulamento = isset($_POST['aceite_regulamento']) ? 1 : 0;
$premiacaoAceiteVeracidade  = isset($_POST['aceite_veracidade'])  ? 1 : 0;

if ($acao === 'remover') {
    $stmt = $pdo->prepare("UPDATE negocios SET publicado_vitrine = 0, status_operacional = 'encerrado' WHERE id = ?");
    $stmt->execute([$negocioId]);
    header('Location: /empreendedores/meus-negocios.php?ok=ocultado');
    exit;
} elseif ($acao === 'republicar') {
    $stmt = $pdo->prepare("UPDATE negocios SET publicado_vitrine = 1, status_operacional = 'ativo' WHERE id = ?");
    $stmt->execute([$negocioId]);
    header('Location: /empreendedores/meus-negocios.php?ok=republicado');
    exit;
}

// Envia para aprovação
try {
    $pdo->beginTransaction();

    // 1) Marca como enviado para análise
       $stmt = $pdo->prepare("
        UPDATE negocios 
        SET status_vitrine = 'em_analise',
            etapa_atual = 11,
            inscricao_completa = 1,
            updated_at = NOW()
        WHERE id = ? AND {$colDono} = ?
    ");

    $stmt->execute([$negocioId, $empreendedorId]);

    // 1.1) Se houver premiação vigente e o empreendedor desejar participar,
    // salva ou atualiza a inscrição do negócio na premiação
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

    if (!empty($premiacaoVigente['id']) && $premiacaoDesejaParticipar === 1) {
        if ($premiacaoAceiteRegulamento !== 1 || $premiacaoAceiteVeracidade !== 1) {
            throw new Exception('Para participar da premiação, é obrigatório aceitar o regulamento e declarar a veracidade das informações.');
        }

        $stmtPremiacaoUpsert = $pdo->prepare("
            INSERT INTO premiacao_inscricoes (
                premiacao_id,
                negocio_id,
                empreendedor_id,
                categoria,
                aceite_regulamento,
                aceite_veracidade,
                deseja_participar,
                status,
                enviado_em,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'enviada', NOW(), NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                empreendedor_id = VALUES(empreendedor_id),
                categoria = VALUES(categoria),
                aceite_regulamento = VALUES(aceite_regulamento),
                aceite_veracidade = VALUES(aceite_veracidade),
                deseja_participar = VALUES(deseja_participar),
                status = 'enviada',
                enviado_em = NOW(),
                updated_at = NOW()
        ");

        $stmtPremiacaoUpsert->execute([
            (int)$premiacaoVigente['id'],
            $negocioId,
            $empreendedorId,
            $negocio['categoria'],
            $premiacaoAceiteRegulamento,
            $premiacaoAceiteVeracidade,
            $premiacaoDesejaParticipar
        ]);
    }

    // 2) Envia email para admins
    $stmt = $pdo->prepare("
        SELECT email FROM users 
        WHERE role IN ('admin', 'superadmin') AND status = 'ativo'
    ");
    $stmt->execute();

    // Como usamos FETCH_COLUMN, $admins é um array direto de emails: ['a@b.com', 'c@d.com']
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($admins)) {
        $assunto = "Novo negócio aguardando aprovação: " . $negocio['nome_fantasia'];
        $linkAdmin = get_base_url() . "/admin/visualizar_negocio.php?id=" . $negocioId;
        $mensagem = "
            <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; padding: 20px;'>
                <h2 style='color: #d63384; border-bottom: 2px solid #f8d7da; padding-bottom: 10px;'>Novo Negócio Aguardando Avaliação</h2>
                
                <p>Olá, Equipe Impactos Positivos,</p>
                <p>O empreendedor concluiu todas as etapas e enviou o negócio abaixo para publicação na vitrine:</p>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #d63384; margin: 20px 0; border-radius: 4px;'>
                    <p style='margin: 0 0 5px 0;'><strong>Nome Fantasia:</strong> {$negocio['nome_fantasia']}</p>
                    <p style='margin: 0 0 5px 0;'><strong>Categoria:</strong> {$negocio['categoria']}</p>
                    <p style='margin: 0;'><strong>ID do Sistema:</strong> {$negocioId}</p>
                </div>

                <div style='background-color: #fff3cd; color: #842029; padding: 15px; border: 1px solid #f5c2c7; border-radius: 5px; margin-bottom: 25px;'>
                    <strong><span style='font-size: 16px;'>⚠️ Atenção Necessária:</span></strong><br>
                    Antes de aprovar e publicar o negócio, é obrigatório verificar a autenticidade e validade dos documentos legais enviados na Etapa 9:
                    <ul style='margin-top: 8px; margin-bottom: 0;'>
                        <li>Certidão Negativa de Débitos Trabalhistas (CNDT)</li>
                        <li>Certidão Negativa de Multas Ambientais (CNMA)</li>
                    </ul>
                </div>

                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$linkAdmin}' style='background-color: #d63384; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Acessar Perfil e Analisar Documentos</a>
                </p>
                
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 12px; color: #777; text-align: center;'>Enviado em: " . date('d/m/Y \à\s H:i') . "</p>
            </div>
        ";

        // Prepara os headers para o mail() nativo
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: Plataforma Impactos Positivos <nao-responda@dscriacaoweb.com.br>\r\n";

        // Loop sobre os emails diretamente
        foreach ($admins as $emailAdmin) {
            // Usa a sua função de e-mail centralizada do mail.php
            send_mail($emailAdmin, 'Administrador PIP', $assunto, $mensagem, $headers);
        }
    }

    $pdo->commit();
    
    $_SESSION['sucesso'] = "Negócio '{$negocio['nome_fantasia']}' enviado para aprovação! Recebemos sua solicitação e em breve analisaremos as documentações.";
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erro ao enviar negócio $negocioId para aprovação: " . $e->getMessage());
    $_SESSION['erro'] = "Erro ao enviar para aprovação: " . $e->getMessage();
}

// Redireciona para meus-negócios com status EM ANALISE
header("Location: /empreendedores/meus-negocios.php");
exit;
?>
