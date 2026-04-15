<?php
session_start();
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Verifica se está logado e se é POST
if (!isset($_SESSION['parceiro_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Captura os arrays de múltipla escolha e garante que não injetem dados vazios
$tipos_parceria = $_POST['tipos_parceria'] ?? [];
$natureza_parceria = $_POST['natureza_parceria'] ?? [];

// Validação: Pelo menos 1 tipo de parceria deve ser escolhido
if (empty($tipos_parceria)) {
    $_SESSION['erro_etapa2'] = "Por favor, selecione pelo menos um Tipo de Parceria.";
    header("Location: etapa2_tipo.php");
    exit;
}

// Codifica para JSON
$tipos_json = json_encode($tipos_parceria, JSON_UNESCAPED_UNICODE);
$natureza_json = json_encode($natureza_parceria, JSON_UNESCAPED_UNICODE);

try {
    // Insere ou Atualiza a tabela parceiro_contrato
    $sql_contrato = "INSERT INTO parceiro_contrato (parceiro_id, tipos_parceria, natureza_parceria) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE 
                     tipos_parceria = VALUES(tipos_parceria), 
                     natureza_parceria = VALUES(natureza_parceria)";
                     
    $stmt = $pdo->prepare($sql_contrato);
    $stmt->execute([$parceiro_id, $tipos_json, $natureza_json]);

    // Atualiza o progresso na tabela principal parceiros
    $sql_progresso = "UPDATE parceiros SET etapa_atual = GREATEST(etapa_atual, 3) WHERE id = ?";
    $stmt = $pdo->prepare($sql_progresso);
    $stmt->execute([$parceiro_id]);

    // Redireciona para a Etapa 3 (Definição do Combinado)
    $from = $_POST['from'] ?? '';
    $destino = ($from === 'confirmacao') ? 'confirmacao.php' : 'etapa3_combinado.php';
    header("Location: " . $destino);
    exit;

} catch (PDOException $e) {
    error_log("Erro ao salvar Etapa 2 do Parceiro: " . $e->getMessage());
    $_SESSION['erro_etapa2'] = "Erro ao salvar os dados. Tente novamente.";
    header("Location: etapa2_tipo.php");
    exit;
}
