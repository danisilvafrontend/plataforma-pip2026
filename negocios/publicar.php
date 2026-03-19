<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
            <h2>Novo Negócio Enviado para Aprovação</h2>
            <p><strong>Negócio:</strong> {$negocio['nome_fantasia']}</p>
            <p><strong>Categoria:</strong> {$negocio['categoria']}</p>
            <p><strong>ID:</strong> {$negocioId}</p>
            <p><a href='{$linkAdmin}' class='btn btn-primary'>Verificar agora no Painel</a></p>
            <p>AINDA É SÓ UM TESTE</P>
            <hr>
            <p><small>Enviado em: " . date('d/m/Y H:i') . "</small></p>
        ";

        // Prepara os headers para o mail() nativo
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: Plataforma Impactos Positivos <nao-responda@dscriacaoweb.com.br>\r\n";

        // Loop sobre os emails diretamente
        foreach ($admins as $emailAdmin) {
            // $emailAdmin já é a string do email (ex: "admin@pip.com")
            @mail($emailAdmin, $assunto, $mensagem, $headers);
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
