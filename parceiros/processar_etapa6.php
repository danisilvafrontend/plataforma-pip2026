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

// Busca os arquivos já existentes
$stmt = $pdo->prepare("SELECT logo_url, manual_marca_url FROM parceiro_contrato WHERE parceiro_id = ?");
$stmt->execute([$parceiro_id]);
$contrato_atual = $stmt->fetch(PDO::FETCH_ASSOC);

$logo_url = $contrato_atual['logo_url'] ?? null;
$manual_url = $contrato_atual['manual_marca_url'] ?? null;

// Função simples para upload - CORRIGIDA
function processarUpload($file, $prefixo, $diretorio_destino, $parceiro_id) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nome_novo = $prefixo . '_' . $parceiro_id . '_' . time() . '.' . $extensao;
        $caminho_absoluto = $diretorio_destino . $nome_novo;
        
        // AQUI ESTAVA O ERRO DE DIGITAÇÃO:
        if (move_uploaded_file($file['tmp_name'], $caminho_absoluto)) {
            return '/uploads/parceiros/' . $nome_novo;
        }
    }
    return false;
}

// Processa Logomarca
if (!empty($_FILES['logo']['name'])) {
    $nova_logo = processarUpload($_FILES['logo'], 'logo', $diretorio_destino, $parceiro_id);
    if ($nova_logo) $logo_url = $nova_logo;
}

// Processa Manual da Marca
if (!empty($_FILES['manual_marca']['name'])) {
    $novo_manual = processarUpload($_FILES['manual_marca'], 'manual', $diretorio_destino, $parceiro_id);
    if ($novo_manual) $manual_url = $novo_manual;
}

try {
    $pdo->beginTransaction();

    // 1. Atualiza as URLs dos arquivos e aceitação dos termos na tabela de contratos
    $sql_contrato = "UPDATE parceiro_contrato SET 
                     logo_url = ?, 
                     manual_marca_url = ?, 
                     termos_aceitos = ?
                     WHERE parceiro_id = ?";
                     
    $stmt_contrato = $pdo->prepare($sql_contrato);
    $stmt_contrato->execute([$logo_url, $manual_url, $termos_aceitos, $parceiro_id]);

    // 2. Atualiza o status do parceiro na tabela principal para 'analise' e finaliza o cadastro
    $sql_parceiro = "UPDATE parceiros SET 
                     etapa_atual = 7, 
                     status = 'analise' 
                     WHERE id = ?";
    $pdo->prepare($sql_parceiro)->execute([$parceiro_id]);

    $pdo->commit();

    // Tudo certo! Redireciona para o painel / dashboard final
    header("Location: confirmacao.php");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao salvar Etapa 6 do Parceiro: " . $e->getMessage());
    $_SESSION['erro_etapa6'] = "Erro ao finalizar o cadastro. Tente novamente.";
    header("Location: etapa6_juridico.php");
    exit;
}