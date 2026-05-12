<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: etapa_extra_patrocinadores.php');
    exit;
}

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

if (!isset($_SESSION['parceiro_id'])) {
    header('Location: /login.php?msg=login_necessario');
    exit;
}

$parceiro_id = (int) $_SESSION['parceiro_id'];

// Coleta e sanitiza
$objetivo_parceria   = $_POST['objetivo_parceria']   ?? [];
$objetivo_outro      = trim($_POST['objetivo_outro']      ?? '');
$modalidade          = $_POST['modalidade']          ?? [];
$modalidade_outro    = trim($_POST['modalidade_outro']    ?? '');
$faixa_apoio         = trim($_POST['faixa_apoio']         ?? '');
$interesse_proposta  = $_POST['interesse_proposta']  ?? [];
$observacoes         = trim($_POST['observacoes']         ?? '');
$declara_interesse   = isset($_POST['declara_interesse']) ? 1 : 0;

// Validação
if (empty($objetivo_parceria) && empty($objetivo_outro)) {
    $_SESSION['erro_etapa_extra'] = 'Selecione ao menos um objetivo da parceria.';
    header('Location: etapa_extra_patrocinadores.php');
    exit;
}

if (empty($faixa_apoio)) {
    $_SESSION['erro_etapa_extra'] = 'Selecione a faixa estimada de apoio ou investimento.';
    header('Location: etapa_extra_patrocinadores.php');
    exit;
}

if (!$declara_interesse) {
    $_SESSION['erro_etapa_extra'] = 'É necessário declarar o interesse em avançar para concluir esta etapa.';
    header('Location: etapa_extra_patrocinadores.php');
    exit;
}

$objetivo_json  = json_encode($objetivo_parceria,  JSON_UNESCAPED_UNICODE);
$modalidade_json = json_encode($modalidade,         JSON_UNESCAPED_UNICODE);
$proposta_json  = json_encode($interesse_proposta,  JSON_UNESCAPED_UNICODE);

try {
    $pdo->beginTransaction();

    $pdo->prepare("
        INSERT INTO parceiro_etapa_extra (
            parceiro_id, objetivo_parceria, objetivo_outro,
            modalidade, modalidade_outro, faixa_apoio,
            interesse_proposta, observacoes, declara_interesse, atualizado_em
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            objetivo_parceria  = VALUES(objetivo_parceria),
            objetivo_outro     = VALUES(objetivo_outro),
            modalidade         = VALUES(modalidade),
            modalidade_outro   = VALUES(modalidade_outro),
            faixa_apoio        = VALUES(faixa_apoio),
            interesse_proposta = VALUES(interesse_proposta),
            observacoes        = VALUES(observacoes),
            declara_interesse  = VALUES(declara_interesse),
            atualizado_em      = NOW()
    ")->execute([
        $parceiro_id, $objetivo_json, $objetivo_outro,
        $modalidade_json, $modalidade_outro, $faixa_apoio,
        $proposta_json, $observacoes, $declara_interesse,
    ]);

    // Marca etapa extra como concluída e avança progresso para etapa 6
    $pdo->prepare("
        UPDATE parceiros
        SET etapa_extra_concluida = 1,
            etapa_atual = GREATEST(etapa_atual, 6)
        WHERE id = ?
    ")->execute([$parceiro_id]);

    $pdo->commit();

    // Avança para etapa6_juridico.php
    header('Location: etapa6_juridico.php');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Erro ao salvar etapa extra: ' . $e->getMessage());
    $_SESSION['erro_etapa_extra'] = 'Erro ao salvar as informações. Tente novamente mais tarde.';
    header('Location: etapa_extra_patrocinadores.php');
    exit;
}
