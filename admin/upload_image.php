<?php
// /public_html/admin/upload_image.php
session_start();
require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

$targetDir = __DIR__ . '/../uploads/email_images/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if (isset($_FILES['file'])) {
    $fileName = uniqid() . '_' . basename($_FILES['file']['name']);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
        // Retorna JSON no formato esperado pelo TinyMCE
        echo json_encode(['location' => '/uploads/email_images/' . $fileName]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Falha ao enviar imagem']);
    }
}