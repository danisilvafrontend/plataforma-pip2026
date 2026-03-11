<?php
// scripts/cleanup_password_resets.php
require __DIR__ . '/../app/config/db.php'; // ajusta caminho para seu $pdo

try {
    $sql = "UPDATE users
            SET password_reset_token = NULL, password_reset_expires_at = NULL
            WHERE password_reset_expires_at IS NOT NULL AND password_reset_expires_at < NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $count = $stmt->rowCount();
    echo date('c') . " - Tokens limpos: {$count}\n";
} catch (Exception $e) {
    echo date('c') . " - ERRO: " . $e->getMessage() . "\n";
    exit(1);
}