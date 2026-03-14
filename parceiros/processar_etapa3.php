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
$duracao_meses = $_POST['duracao_meses'] ?? '';
$nivel_engajamento = $_POST['nivel_engajamento'] ?? '';
$escopo_atuacao = $_POST['escopo_atuacao'] ?? [];
$escopo_outro = trim($_POST['escopo_outro'] ?? '');

// Validações básicas
if (empty($duracao_meses) || empty($nivel_engajamento) || (empty($escopo_atuacao) && empty($escopo_outro))) {
    $_SESSION['erro_etapa3'] = "Por favor, preencha a Duração, o Nível de Engajamento e ao menos um Escopo de Atuação.";
    header("Location: etapa3_combinado.php");
    exit;
}

// Codifica array para JSON
$escopo_json = json_encode($escopo_atuacao, JSON_UNESCAPED_UNICODE);

try {
    // Como a tabela parceiro_contrato já foi criada na Etapa 2, usamos UPDATE direto
    // Mas pra garantir, usamos ON DUPLICATE KEY UPDATE caso o usuário tenha pulado rotas
    $sql_contrato = "INSERT INTO parceiro_contrato (parceiro_id, duracao_meses, nivel_engajamento, escopo_atuacao, escopo_outro) 
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                     duracao_meses = VALUES(duracao_meses),
                     nivel_engajamento = VALUES(nivel_engajamento),
                     escopo_atuacao = VALUES(escopo_atuacao),
                     escopo_outro = VALUES(escopo_outro)";
                     
    $stmt = $pdo->prepare($sql_contrato);
    $stmt->execute([$parceiro_id, $duracao_meses, $nivel_engajamento, $escopo_json, $escopo_outro]);

    // Atualiza progresso
    $sql_progresso = "UPDATE parceiros SET etapa_atual = GREATEST(etapa_atual, 4) WHERE id = ?";
    $stmt = $pdo->prepare($sql_progresso);
    $stmt->execute([$parceiro_id]);

    // Redireciona para a Etapa 4 (Mapeamento de Interesses e Perfil de Impacto)
    $from = $_POST['from'] ?? '';
    $destino = ($from === 'confirmacao') ? 'confirmacao.php' : 'etapa4_interesses.php';
    header("Location: " . $destino);
    exit;

} catch (PDOException $e) {
    error_log("Erro ao salvar Etapa 3 do Parceiro: " . $e->getMessage());
    $_SESSION['erro_etapa3'] = "Erro ao salvar os dados. Tente novamente.";
    header("Location: etapa3_combinado.php");
    exit;
}
