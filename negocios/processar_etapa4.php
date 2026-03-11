<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id = (int)($_POST['negocio_id'] ?? 0);
$ods_prioritaria = (int)($_POST['ods_prioritaria'] ?? 0);
$ods_relacionadas = $_POST['ods_relacionadas'] ?? [];

$errors = [];

if ($negocio_id === 0) {
    $errors[] = "Negócio inválido.";
}
if ($ods_prioritaria === 0) {
    $errors[] = "Selecione a ODS prioritária.";
}

if (!empty($errors)) {
    $_SESSION['errors_etapa4'] = $errors;
    header("Location: /negocios/etapa4_ods.php?id=" . $negocio_id);
    exit;
}

// Atualiza ODS prioritária na tabela negocios
$stmt = $pdo->prepare("UPDATE negocios SET ods_prioritaria_id = ?, etapa_atual = 5, updated_at = NOW() WHERE id = ? AND empreendedor_id = ?");
$stmt->execute([$ods_prioritaria, $negocio_id, $_SESSION['user_id']]);

// Remove ODS relacionadas antigas
$stmt = $pdo->prepare("DELETE FROM negocio_ods WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);

// Insere novas ODS relacionadas
$stmt = $pdo->prepare("INSERT INTO negocio_ods (negocio_id, ods_id) VALUES (?, ?)");
foreach ($ods_relacionadas as $ods_id) {
    $stmt->execute([$negocio_id, (int)$ods_id]);
}

$modo = $_POST['modo'] ?? 'cadastro';

if ($modo === 'cadastro') {
    // Atualiza etapa e vai para etapa 8
    $stmt = $pdo->prepare("UPDATE negocios 
        SET etapa_atual = 5, updated_at = NOW() 
        WHERE id = ? AND empreendedor_id = ?");
    $stmt->execute([$negocio_id, $_SESSION['user_id']]);

    header("Location: /negocios/etapa5_apresentacao.php?id=" . $negocio_id);
    exit;
} else {
    // Edição: volta para Meus Negócios
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}