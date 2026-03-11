<?php
// /cron/update_status.php
declare(strict_types=1);

require_once __DIR__ . '/../app/services/Database.php';

try {
    $pdo = Database::getInstance();

    // Ativo
    $pdo->exec("UPDATE empreendedores
                SET status = 'ativo'
                WHERE ultimo_login >= (NOW() - INTERVAL 90 DAY)");

    // Inativo
    $pdo->exec("UPDATE empreendedores
                SET status = 'inativo'
                WHERE ultimo_login < (NOW() - INTERVAL 90 DAY)
                  AND ultimo_login >= (NOW() - INTERVAL 350 DAY)");

    // Dormant
    $pdo->exec("UPDATE empreendedores
                SET status = 'dormant'
                WHERE ultimo_login < (NOW() - INTERVAL 350 DAY)");

    echo "Status atualizado com sucesso.\n";
} catch (Throwable $e) {
    error_log("Erro ao atualizar status: " . $e->getMessage());
    echo "Erro ao atualizar status.\n";
}