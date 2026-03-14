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

if (!isset($_SESSION['parceiro_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Captura os dados
$deseja_publicar = $_POST['deseja_publicar'] ?? [];
$rede_impacto = $_POST['rede_impacto'] ?? 'avaliar_depois'; // Padrão se nada vier

// Codifica os arrays simples para JSON
$publicar_json = json_encode($deseja_publicar, JSON_UNESCAPED_UNICODE);

try {
    // Atualiza a tabela parceiro_contrato
    $sql_contrato = "UPDATE parceiro_contrato SET 
                     deseja_publicar = ?, 
                     rede_impacto = ?
                     WHERE parceiro_id = ?";
                     
    $stmt = $pdo->prepare($sql_contrato);
    $stmt->execute([$publicar_json, $rede_impacto, $parceiro_id]);

    // Atualiza o progresso (Vai para a Etapa 6 que é o Upload Jurídico)
    $sql_progresso = "UPDATE parceiros SET etapa_atual = GREATEST(etapa_atual, 6) WHERE id = ?";
    $pdo->prepare($sql_progresso)->execute([$parceiro_id]);

    // Redireciona para a Etapa 6 (Área Jurídica e Uploads)
    $from = $_POST['from'] ?? '';
    $destino = ($from === 'confirmacao') ? 'confirmacao.php' : 'etapa6_juridico.php';
    header("Location: " . $destino);
    exit;

} catch (PDOException $e) {
    error_log("Erro ao salvar Etapa 5 do Parceiro: " . $e->getMessage());
    $_SESSION['erro_etapa5'] = "Erro ao salvar preferências de uso. Tente novamente.";
    header("Location: etapa5_plataforma.php");
    exit;
}
