<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

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

$negocio_id = (int)($_POST['negocio_id'] ?? 0);
$eixo_principal_id = (int)($_POST['eixo_principal'] ?? 0);
$subareas = $_POST['subareas'] ?? [];

$errors = [];

if ($negocio_id === 0) {
    $errors[] = "Negócio inválido.";
}
if ($eixo_principal_id === 0) {
    $errors[] = "Selecione um eixo temático.";
}
if (empty($subareas)) {
    $errors[] = "Selecione pelo menos uma subárea.";
}

if (!empty($errors)) {
    $_SESSION['errors_etapa3'] = $errors;
    header("Location: /negocios/etapa3_eixo_tematico.php?id=" . $negocio_id);
    exit;
}

// Atualiza eixo principal e updated_at
$stmt = $pdo->prepare("UPDATE negocios SET eixo_principal_id = ?, etapa_atual = 4, updated_at = NOW() WHERE id = ? AND empreendedor_id = ?");
$stmt->execute([$eixo_principal_id, $negocio_id, $_SESSION['user_id']]);

// Remove subáreas antigas
$stmt = $pdo->prepare("DELETE FROM negocio_subareas WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);

// Insere novas subáreas (IDs vindos direto do formulário)
$stmt = $pdo->prepare("INSERT INTO negocio_subareas (negocio_id, subarea_id) VALUES (?, ?)");
foreach ($subareas as $subarea_id) {
    $stmt->execute([$negocio_id, (int)$subarea_id]);
}



$modo = $_POST['modo'] ?? 'cadastro';

if ($modo === 'cadastro') {
    // Atualiza etapa e vai para etapa 8
    $stmt = $pdo->prepare("UPDATE negocios 
        SET etapa_atual = 4, updated_at = NOW() 
        WHERE id = ? AND empreendedor_id = ?");
    $stmt->execute([$negocio_id, $_SESSION['user_id']]);

    header("Location: /negocios/etapa4_ods.php?id=" . $negocio_id);
    exit;
} else {
    // Edição: volta para Meus Negócios
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}