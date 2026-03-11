<?php
// /public_html/admin/excluir_empreendedor.php
declare(strict_types=1);
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/helpers/auth.php';

// Apenas superadmin
if (!is_superadmin()) {
    http_response_code(403);
    die("Acesso negado. Apenas superadmin pode excluir empreendedor.");
}

$config = require __DIR__ . '/../app/config/db.php';

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
} catch (PDOException $e) {
    die('Erro de conexão: ' . $e->getMessage());
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID inválido.");
}

// Confirma se existe
$stmt = $pdo->prepare("SELECT id, nome FROM empreendedores WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("Usuário não encontrado.");
}

// === INÍCIO DA EXCLUSÃO EM CASCATA ===
try {
    $pdo->beginTransaction();

    // 1. Encontra todos os negócios desse empreendedor
    $stmtNegocios = $pdo->prepare("SELECT id FROM negocios WHERE empreendedor_id = ?");
    $stmtNegocios->execute([$id]);
    $negociosIds = $stmtNegocios->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($negociosIds)) {
        // Cria os '?' dinâmicos para a query (ex: "?, ?, ?")
        $inQuery = implode(',', array_fill(0, count($negociosIds), '?'));
        
        // 2. Exclui os dependentes dos negócios (Todas as etapas e scores)
        
        // Etapa 8 / Geral: Scores
        $pdo->prepare("DELETE FROM scores_negocios WHERE negocio_id IN ($inQuery)")->execute($negociosIds);
        
        // Etapa 5: Apresentação e Galeria
        $pdo->prepare("DELETE FROM negocio_apresentacao WHERE negocio_id IN ($inQuery)")->execute($negociosIds);
        
        // Etapa 4: ODS
        $pdo->prepare("DELETE FROM negocio_ods WHERE negocio_id IN ($inQuery)")->execute($negociosIds);
        
        // Etapa 3: Eixos e Subáreas
        $pdo->prepare("DELETE FROM negocio_subareas WHERE negocio_id IN ($inQuery)")->execute($negociosIds);
        
        // Tabelas Adicionais (Caso existam na base)
        $pdo->prepare("DELETE FROM negocio_impacto WHERE negocio_id IN ($inQuery)")->execute($negociosIds);
        $pdo->prepare("DELETE FROM negocio_visao WHERE negocio_id IN ($inQuery)")->execute($negociosIds);
        $pdo->prepare("DELETE FROM negocio_mercado WHERE negocio_id IN ($inQuery)")->execute($negociosIds);
        $pdo->prepare("DELETE FROM negocio_financeiro WHERE negocio_id IN ($inQuery)")->execute($negociosIds);
        $pdo->prepare("DELETE FROM negocio_sustentabilidade WHERE negocio_id IN ($inQuery)")->execute($negociosIds);
        $pdo->prepare("DELETE FROM negocio_documentos WHERE negocio_id IN ($inQuery)")->execute($negociosIds);

        // Etapa 2: Fundadores
        $pdo->prepare("DELETE FROM negocio_fundadores WHERE negocio_id IN ($inQuery)")->execute($negociosIds);
        
        // 3. Finalmente, Exclui os negócios
        $stmtDelNegocios = $pdo->prepare("DELETE FROM negocios WHERE empreendedor_id = ?");
        $stmtDelNegocios->execute([$id]);
    }

    // 4. Exclui registros de fundadores que possam estar atrelados pelo ID do empreendedor (caso tenha fugido do IN acima)
    $stmtDelFundadoresEmp = $pdo->prepare("DELETE FROM negocio_fundadores WHERE empreendedor_id = ?");
    $stmtDelFundadoresEmp->execute([$id]);

    // 5. Por último, exclui o empreendedor principal
    $stmt = $pdo->prepare("DELETE FROM empreendedores WHERE id = ?");
    $stmt->execute([$id]);

    $pdo->commit();
    // === FIM DA EXCLUSÃO ===

    $_SESSION['flash_message'] = "Usuário #{$id} ({$usuario['nome']}) e todos os seus negócios foram excluídos com sucesso!";
    header("Location: /admin/empreendedores.php");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack(); // Desfaz qualquer alteração se deu erro
    die("Erro ao excluir usuário e seus dados relacionados: " . $e->getMessage());
}
