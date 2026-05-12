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

// Captura os dados
$deseja_publicar = $_POST['deseja_publicar'] ?? [];
$rede_impacto    = $_POST['rede_impacto']    ?? 'avaliar_depois';

$publicar_json = json_encode($deseja_publicar, JSON_UNESCAPED_UNICODE);

try {
    // Atualiza a tabela parceiro_contrato
    $pdo->prepare("
        UPDATE parceiro_contrato
        SET deseja_publicar = ?, rede_impacto = ?
        WHERE parceiro_id = ?
    ")->execute([$publicar_json, $rede_impacto, $parceiro_id]);

    // Atualiza progresso
    $pdo->prepare("UPDATE parceiros SET etapa_atual = GREATEST(etapa_atual, 6) WHERE id = ?")
        ->execute([$parceiro_id]);

    // --- Define destino ---
    $from = $_POST['from'] ?? '';

    if ($from === 'confirmacao') {
        header("Location: confirmacao.php");
        exit;
    }

    // Verifica se o tipo de parceria exige etapa extra
    $stmt = $pdo->prepare("SELECT tipos_parceria FROM parceiro_contrato WHERE parceiro_id = ? LIMIT 1");
    $stmt->execute([$parceiro_id]);
    $contrato = $stmt->fetch(PDO::FETCH_ASSOC);

    $tipos = !empty($contrato['tipos_parceria']) ? json_decode($contrato['tipos_parceria'], true) : [];
    if (!is_array($tipos)) $tipos = [];

    $tipos_com_etapa_extra = [
        'Patrocinador Institucional',
        'Patrocinador Estratégico de Impacto',
        'Investidor de Ecossistema',
        'Doador de Impacto',
    ];

    $precisa_etapa_extra = count(array_intersect($tipos, $tipos_com_etapa_extra)) > 0;

    if ($precisa_etapa_extra) {
        header("Location: etapa_extra_patrocinadores.php");
    } else {
        header("Location: etapa6_juridico.php");
    }
    exit;

} catch (PDOException $e) {
    error_log("Erro ao salvar Etapa 5 do Parceiro: " . $e->getMessage());
    $_SESSION['erro_etapa5'] = "Erro ao salvar preferências de uso. Tente novamente.";
    header("Location: etapa5_plataforma.php");
    exit;
}
