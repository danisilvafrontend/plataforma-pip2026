<?php
session_start();


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();


require_once __DIR__ . '/../app/helpers/scores.php';
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Busca todos os negócios
$stmt = $pdo->query("SELECT id FROM negocios");
$negocios = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($negocios as $negocio_id) {
    $scoreImpacto = calcularScore($pdo, $negocio_id, 'IMPACTO');
    $scoreInvestimento = calcularScore($pdo, $negocio_id, 'INVESTIMENTO');
    $scoreEscala = calcularScore($pdo, $negocio_id, 'ESCALA');

    $scoreGeral = round(0.40 * $scoreImpacto + 0.30 * $scoreInvestimento + 0.30 * $scoreEscala);

    $stmtUpdate = $pdo->prepare("
        INSERT INTO scores_negocios (negocio_id, score_impacto, score_investimento, score_escala, score_geral, atualizado_em)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            score_impacto = VALUES(score_impacto),
            score_investimento = VALUES(score_investimento),
            score_escala = VALUES(score_escala),
            score_geral = VALUES(score_geral),
            atualizado_em = NOW()
    ");
    $stmtUpdate->execute([$negocio_id, $scoreImpacto, $scoreInvestimento, $scoreEscala, $scoreGeral]);
}

header("Location: negocios.php?msg=scores_recalculados");
exit;