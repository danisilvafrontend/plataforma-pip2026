<?php
session_start();

require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$pdo = getPDO();
$user_id = $_SESSION['user_id'];
$negocio_id = (int) ($_POST['negocio_id'] ?? 0);

if (!$negocio_id) {
    die('Negócio inválido.');
}

$stmt = $pdo->prepare("SELECT id FROM negocios WHERE id = :id AND user_id = :user_id LIMIT 1");
$stmt->execute([
    'id' => $negocio_id,
    'user_id' => $user_id,
]);

if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    die('Acesso negado.');
}

function textoValido($texto) {
    $texto = trim($texto);
    return preg_match_all('/[a-zA-ZÀ-ÿ]/', $texto) >= 5;
}

$_SESSION['errors_etapa8'] = [];

$visao_estrategica = $_POST['visao_estrategica'] ?? '';
$visao_outro = trim($_POST['visao_outro'] ?? '');
$sustentabilidade = $_POST['sustentabilidade'] ?? '';
$escala            = $_POST['escala'] ?? '';
$parcerias_ativas  = $_POST['parcerias_ativas'] ?? '';

$opcoes_parcerias = ['parcerias_nacionais_internacionais','parcerias_locais','parcerias_informais','sem_parcerias'];
$apoios = $_POST['apoios'] ?? [];
$apoio_outro = $_POST['apoio_outro'] ?? '';
$areas = $_POST['areas'] ?? [];
$area_outro = $_POST['area_outro'] ?? '';
$temas = $_POST['temas'] ?? [];
$tema_outro = $_POST['tema_outro'] ?? '';

if ($visao_outro && !textoValido($visao_outro)) {
    $_SESSION['errors_etapa8'][] = "O complemento de visão estratégica deve conter texto válido.";
}
if ($apoio_outro && !textoValido($apoio_outro)) {
    $_SESSION['errors_etapa8'][] = "O campo 'Outro' em apoio deve conter texto válido.";
}
if ($area_outro && !textoValido($area_outro)) {
    $_SESSION['errors_etapa8'][] = "O campo 'Outro' em áreas deve conter texto válido.";
}
if ($tema_outro && !textoValido($tema_outro)) {
    $_SESSION['errors_etapa8'][] = "O campo 'Outro' em temas deve conter texto válido.";
}

if (empty($visao_estrategica)) {
    $_SESSION['errors_etapa8'][] = "Informe a visão estratégica.";
}
if (empty($sustentabilidade)) {
    $_SESSION['errors_etapa8'][] = "Informe como pretende sustentar o crescimento ou consolidação.";
}
if (empty($escala)) {
    $_SESSION['errors_etapa8'][] = "Informe a escala pretendida.";
}
if (empty($parcerias_ativas) || !in_array($parcerias_ativas, $opcoes_parcerias)) {
    $_SESSION['errors_etapa8'][] = "Informe se o negócio possui parcerias estratégicas ativas.";
}
if (empty($apoios)) {
    $_SESSION['errors_etapa8'][] = "Selecione pelo menos um tipo de apoio financeiro ou estratégico.";
}
if (empty($areas)) {
    $_SESSION['errors_etapa8'][] = "Selecione pelo menos uma área a fortalecer.";
}
if (empty($temas)) {
    $_SESSION['errors_etapa8'][] = "Selecione pelo menos um tema de interesse.";
}

if (!empty($_SESSION['errors_etapa8'])) {
    header("Location: /negocios/etapa8_visao.php?id=" . $negocio_id);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM negocio_visao WHERE negocio_id = :negocio_id LIMIT 1");
$stmt->execute(['negocio_id' => $negocio_id]);
$exists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exists) {
    $stmt = $pdo->prepare("UPDATE negocio_visao SET 
        visao_estrategica = :visao_estrategica,
        visao_outro       = :visao_outro,
        sustentabilidade  = :sustentabilidade,
        escala            = :escala,
        parcerias_ativas  = :parcerias_ativas,
        apoios            = :apoios,
        apoio_outro       = :apoio_outro,
        areas             = :areas,
        area_outro        = :area_outro,
        temas             = :temas,
        tema_outro        = :tema_outro,
        atualizado_em     = NOW()
        WHERE negocio_id  = :negocio_id");
} else {
    $stmt = $pdo->prepare("INSERT INTO negocio_visao (
        negocio_id, visao_estrategica, visao_outro, sustentabilidade, escala, parcerias_ativas,
        apoios, apoio_outro, areas, area_outro, temas, tema_outro, criado_em, atualizado_em
    ) VALUES (
        :negocio_id, :visao_estrategica, :visao_outro, :sustentabilidade, :escala, :parcerias_ativas,
        :apoios, :apoio_outro, :areas, :area_outro, :temas, :tema_outro, NOW(), NOW()
    )");
}

$params = [
    'negocio_id'        => $negocio_id,
    'visao_estrategica' => $visao_estrategica,
    'visao_outro'       => $visao_outro ?: null,
    'sustentabilidade'  => $sustentabilidade,
    'escala'            => $escala,
    'parcerias_ativas'  => $parcerias_ativas,
    'apoios'            => json_encode($apoios),
    'apoio_outro'       => $apoio_outro ?: null,
    'areas'             => json_encode($areas),
    'area_outro'        => $area_outro ?: null,
    'temas'             => json_encode($temas),
    'tema_outro'        => $tema_outro ?: null,
];

try {
    $stmt->execute($params);

    try {
        $stmtScore = $pdo->prepare("
            INSERT INTO scores_negocios (negocio_id, score_escala, atualizado_em)
            VALUES (:negocio_id, 0, NOW())
            ON DUPLICATE KEY UPDATE score_escala=VALUES(score_escala), atualizado_em=NOW()
        ");
        $stmtScore->execute(['negocio_id' => $negocio_id]);
    } catch (Throwable $e) {
        // opcional
    }

    $stmt = $pdo->prepare("UPDATE negocios SET etapa_atual = 9, updated_at = NOW() WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        'id' => $negocio_id,
        'user_id' => $user_id,
    ]);

    header("Location: /negocios/confirmacao.php?id=" . $negocio_id);
    exit;
} catch (Throwable $e) {
    $_SESSION['errors_etapa8'][] = 'Erro ao salvar os dados da etapa 8.';
    header("Location: /negocios/etapa8_visao.php?id=" . $negocio_id);
    exit;
}
