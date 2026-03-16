<?php
session_start();
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$parceiro_id = $_SESSION['parceiro_id'];
$termos_aceitos = isset($_POST['termos_aceitos']) ? 1 : 0;

if (!$termos_aceitos) {
    $_SESSION['erro_etapa6'] = "Você precisa aceitar os termos de parceria para finalizar o cadastro.";
    header("Location: etapa6_juridico.php");
    exit;
}

// Configuração do diretório de uploads
$diretorio_destino = __DIR__ . '/../uploads/parceiros/';

// Cria a pasta se não existir
if (!is_dir($diretorio_destino)) {
    mkdir($diretorio_destino, 0777, true);
}

// Busca os arquivos já existentes + TODOS OS NOVOS CAMPOS
$stmt = $pdo->prepare("
    SELECT logo_url, manual_marca_url, termos_aceitos,
           facebook_url, instagram_url, linkedin_url, youtube_url, autoriza_marca
    FROM parceiro_contrato 
    WHERE parceiro_id = ?
");
$stmt->execute([$parceiro_id]);
$contrato_atual = $stmt->fetch(PDO::FETCH_ASSOC);

$logo_url = $contrato_atual['logo_url'] ?? null;
$manual_url = $contrato_atual['manual_marca_url'] ?? null;

// Captura NOVOS campos do POST com fallback para valores antigos
$facebook_url  = trim($_POST['facebook_url']  ?? ($contrato_atual['facebook_url']  ?? ''));
$instagram_url = trim($_POST['instagram_url'] ?? ($contrato_atual['instagram_url'] ?? ''));
$linkedin_url  = trim($_POST['linkedin_url']  ?? ($contrato_atual['linkedin_url']  ?? ''));
$youtube_url   = trim($_POST['youtube_url']   ?? ($contrato_atual['youtube_url']   ?? ''));
$autoriza_marca = !empty($_POST['autoriza_marca']) ? 1 : (int)($contrato_atual['autoriza_marca'] ?? 0);

// Função de upload (mantida igual)
function processarUpload($file, $prefixo, $diretorio_destino, $parceiro_id) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nome_novo = $prefixo . '_' . $parceiro_id . '_' . time() . '.' . $extensao;
        $caminho_absoluto = $diretorio_destino . $nome_novo;
        
        if (move_uploaded_file($file['tmp_name'], $caminho_absoluto)) {
            return '/uploads/parceiros/' . $nome_novo;
        }
    }
    return false;
}

// Processa Logomarca (se novo arquivo)
if (!empty($_FILES['logo']['name'])) {
    $nova_logo = processarUpload($_FILES['logo'], 'logo', $diretorio_destino, $parceiro_id);
    if ($nova_logo) $logo_url = $nova_logo;
}

// Processa Manual da Marca (se novo arquivo)
if (!empty($_FILES['manual_marca']['name'])) {
    $novo_manual = processarUpload($_FILES['manual_marca'], 'manual', $diretorio_destino, $parceiro_id);
    if ($novo_manual) $manual_url = $novo_manual;
}

try {
    $pdo->beginTransaction();

    // 1. Atualiza TODOS os campos na tabela de contratos
    $sql_contrato = "
        UPDATE parceiro_contrato SET 
            logo_url = ?, 
            manual_marca_url = ?, 
            termos_aceitos = ?,
            facebook_url = ?,
            instagram_url = ?,
            linkedin_url = ?,
            youtube_url = ?,
            autoriza_marca = ?
        WHERE parceiro_id = ?
    ";
    
    $stmt_contrato = $pdo->prepare($sql_contrato);
    $stmt_contrato->execute([
        $logo_url,
        $manual_url,
        $termos_aceitos,
        $facebook_url,
        $instagram_url,
        $linkedin_url,
        $youtube_url,
        $autoriza_marca,
        $parceiro_id
    ]);

    // 2. Finaliza cadastro na tabela principal
    $sql_parceiro = "UPDATE parceiros SET 
                        etapa_atual = 7, 
                        status = 'analise' 
                     WHERE id = ?";
    $pdo->prepare($sql_parceiro)->execute([$parceiro_id]);

    $pdo->commit();

    header("Location: confirmacao.php");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao salvar Etapa 6 do Parceiro: " . $e->getMessage());
    $_SESSION['erro_etapa6'] = "Erro ao finalizar o cadastro. Tente novamente.";
    header("Location: etapa6_juridico.php");
    exit;
}
?>
