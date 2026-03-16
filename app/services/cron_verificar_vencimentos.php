<?php
// cron_verificar_vencimentos.php
// Esse arquivo não tem sessão, pois roda no servidor em background

ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$hoje = date('Y-m-d H:i:s');

try {
    // 1. Encontra todos os parceiros ATIVOS cujo contrato já VENCEU
    $sqlBusca = "
        SELECT p.id, p.rep_email, p.nome_fantasia
        FROM parceiros p
        INNER JOIN parceiro_contrato c ON p.id = c.parceiro_id
        WHERE p.status = 'ativo' 
        AND c.data_vencimento IS NOT NULL 
        AND c.data_vencimento < ?
    ";
    
    $stmtBusca = $pdo->prepare($sqlBusca);
    $stmtBusca->execute([$hoje]);
    $vencidos = $stmtBusca->fetchAll(PDO::FETCH_ASSOC);

    if (count($vencidos) > 0) {
        $ids = [];
        foreach ($vencidos as $v) {
            $ids[] = $v['id'];
        }

        // 2. Atualiza o status deles para 'vencido' (você pode adicionar esse status no select do seu admin)
        $in  = str_repeat('?,', count($ids) - 1) . '?';
        $sqlUpdate = "UPDATE parceiros SET status = 'vencido' WHERE id IN ($in)";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute($ids);

        echo "Foram inativados/vencidos " . count($ids) . " parceiros.<br>";
        
        // OPCIONAL: Aqui você pode colocar um loop para disparar um e-mail 
        // automático avisando o parceiro que a parceria expirou e convidando a renovar.

    } else {
        echo "Nenhum contrato vencido hoje.<br>";
    }

} catch (PDOException $e) {
    error_log("Erro no Cron de Parcerias: " . $e->getMessage());
    echo "Erro: " . $e->getMessage();
}
