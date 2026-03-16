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

if (!isset($_SESSION['parceiro_id'])) {
    header("Location: login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Captura as seleções (Arrays e as strings únicas)
$ods = $_POST['ods'] ?? [];
$eixos = $_POST['eixos'] ?? [];
$maturidade = $_POST['maturidade'] ?? [];
$setores = $_POST['setores'] ?? [];
$perfil = $_POST['perfil'] ?? [];
$alcance = $_POST['alcance'] ?? '';

// Novas variáveis do Matchmaking
$orcamento_anual = $_POST['orcamento_anual'] ?? '';
$tipo_relacionamento = $_POST['tipo_relacionamento'] ?? '';
$horizonte_engajamento = $_POST['horizonte_engajamento'] ?? '';

// Codifica os arrays simples para JSON
$eixos_json = json_encode($eixos, JSON_UNESCAPED_UNICODE);
$maturidade_json = json_encode($maturidade, JSON_UNESCAPED_UNICODE);
$setores_json = json_encode($setores, JSON_UNESCAPED_UNICODE);
$perfil_json = json_encode($perfil, JSON_UNESCAPED_UNICODE);

try {
    $pdo->beginTransaction();

    // 1. Processa a tabela auxiliar parceiro_interesses (AGORA COM AS 3 NOVAS COLUNAS)
    $sql_int = "
        INSERT INTO parceiro_interesses 
        (parceiro_id, eixos_interesse, maturidade_negocios, setores_interesse, perfil_impacto, alcance_impacto, orcamento_anual, tipo_relacionamento, horizonte_engajamento) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        eixos_interesse = VALUES(eixos_interesse), 
        maturidade_negocios = VALUES(maturidade_negocios), 
        setores_interesse = VALUES(setores_interesse), 
        perfil_impacto = VALUES(perfil_impacto), 
        alcance_impacto = VALUES(alcance_impacto),
        orcamento_anual = VALUES(orcamento_anual),
        tipo_relacionamento = VALUES(tipo_relacionamento),
        horizonte_engajamento = VALUES(horizonte_engajamento)
    ";
    $stmt = $pdo->prepare($sql_int);
    $stmt->execute([
        $parceiro_id, 
        $eixos_json, 
        $maturidade_json, 
        $setores_json, 
        $perfil_json, 
        $alcance,
        $orcamento_anual,
        $tipo_relacionamento,
        $horizonte_engajamento
    ]);

    // 2. Processa as ODS (Remove as antigas e insere as novas)
    $pdo->prepare("DELETE FROM parceiro_ods WHERE parceiro_id = ?")->execute([$parceiro_id]);
    
    if (!empty($ods)) {
        $sql_ods = "INSERT INTO parceiro_ods (parceiro_id, ods_id) VALUES (?, ?)";
        $stmt_ods = $pdo->prepare($sql_ods);
        foreach ($ods as $ods_id) {
            $stmt_ods->execute([$parceiro_id, $ods_id]);
        }
    }

    // 3. Atualiza progresso da etapa
    $sql_progresso = "UPDATE parceiros SET etapa_atual = GREATEST(etapa_atual, 5) WHERE id = ?";
    $pdo->prepare($sql_progresso)->execute([$parceiro_id]);

    $pdo->commit();

     // Redireciona para a Etapa 3 (Definição do Combinado)
    $from = $_POST['from'] ?? '';
    $destino = ($from === 'confirmacao') ? 'confirmacao.php' : 'etapa5_plataforma.php';
    header("Location: " . $destino);
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao salvar Etapa 4 do Parceiro: " . $e->getMessage());
    $_SESSION['erro_etapa4'] = "Erro ao salvar os interesses. Tente novamente.";
    header("Location: etapa4_interesses.php");
    exit;
}
