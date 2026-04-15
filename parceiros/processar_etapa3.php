<?php
session_start();

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

// Captura os dados (ANTIGOS + NOVOS)
$nivel_engajamento = $_POST['nivel_engajamento'] ?? '';
$escopo_atuacao = $_POST['escopo_atuacao'] ?? [];
$escopo_outro = trim($_POST['escopo_outro'] ?? '');
$oferece_premiacao = !empty($_POST['oferece_premiacao']) ? 1 : 0;
$premio_descricao = trim($_POST['premio_descricao'] ?? '');

// Validações básicas
if (empty($nivel_engajamento) || (empty($escopo_atuacao) && empty($escopo_outro))) {
    $_SESSION['erro_etapa3'] = "Por favor, preencha o Nível de Engajamento e ao menos um Escopo de Atuação.";
    header("Location: etapa3_combinado.php");
    exit;
}

// Se marcou prêmio mas não descreveu, avisa
if ($oferece_premiacao && empty($premio_descricao)) {
    $_SESSION['erro_etapa3'] = "Se deseja oferecer premiação, descreva qual prêmio e seu valor.";
    header("Location: etapa3_combinado.php");
    exit;
}
// Codifica array para JSON
$escopo_json = json_encode($escopo_atuacao, JSON_UNESCAPED_UNICODE);

try {
    // INSERT/UPDATE com os NOVOS CAMPOS
        $sql_contrato = "INSERT INTO parceiro_contrato 
        (parceiro_id, nivel_engajamento, escopo_atuacao, escopo_outro, oferece_premiacao, premio_descricao) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        nivel_engajamento = VALUES(nivel_engajamento),
        escopo_atuacao = VALUES(escopo_atuacao),
        escopo_outro = VALUES(escopo_outro),
        oferece_premiacao = VALUES(oferece_premiacao),
        premio_descricao = VALUES(premio_descricao)";
    
    $stmt = $pdo->prepare($sql_contrato);
    $stmt->execute([
        $parceiro_id, 
        $nivel_engajamento, 
        $escopo_json, 
        $escopo_outro,
        $oferece_premiacao,
        $premio_descricao
    ]);

    // Atualiza progresso
    $sql_progresso = "UPDATE parceiros SET etapa_atual = GREATEST(etapa_atual, 4) WHERE id = ?";
    $stmt = $pdo->prepare($sql_progresso);
    $stmt->execute([$parceiro_id]);

    // Redireciona para a Etapa 3 (Definição do Combinado)
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
?>
