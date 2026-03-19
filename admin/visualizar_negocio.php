<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Autenticação admin
require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

// Config DB
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Helpers compartilhados
require_once __DIR__ . '/../negocios/blocos-cadastros/_shared.php';

// Recebe id do negócio
$negocio_id = (int)($_GET['id'] ?? 0);
if ($negocio_id <= 0) {
    header("Location: /admin/negocios.php");
    exit;
}

// Busca negócio (sem restrição de empreendedor)
$stmt = $pdo->prepare("SELECT * FROM negocios WHERE id = ?");
$stmt->execute([$negocio_id]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$negocio) {
    die("Negócio não encontrado.");
}


// Etapa 2: fundadores
$stmt = $pdo->prepare("
    SELECT * FROM negocio_fundadores 
    WHERE negocio_id = ? 
    ORDER BY tipo = 'principal' DESC, id ASC
");
$stmt->execute([$negocio_id]);
$fundadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
$fundador_principal = null;
$cofundadores = [];
foreach ($fundadores as $f) {
    if (($f['tipo'] ?? '') === 'principal') $fundador_principal = $f;
    else $cofundadores[] = $f;
}

// Etapa 3: eixo e subáreas
$stmt = $pdo->prepare("
    SELECT et.nome as eixo_nome 
    FROM eixos_tematicos et 
    WHERE et.id = (SELECT eixo_principal_id FROM negocios WHERE id = ?)
");
$stmt->execute([$negocio_id]);
$eixo_principal = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT s.nome 
    FROM subareas s 
    INNER JOIN negocio_subareas ns ON s.id = ns.subarea_id 
    WHERE ns.negocio_id = ? 
    ORDER BY s.nome
");
$stmt->execute([$negocio_id]);
$subareas_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Etapa 4: ODS
$stmt = $pdo->prepare("
    SELECT icone_url 
    FROM ods 
    WHERE id = (SELECT ods_prioritaria_id FROM negocios WHERE id = ?)
");
$stmt->execute([$negocio_id]);
$ods_prioritaria = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT o.icone_url 
    FROM ods o 
    INNER JOIN negocio_ods no ON o.id = no.ods_id 
    WHERE no.negocio_id = ? 
    ORDER BY o.id
");
$stmt->execute([$negocio_id]);
$ods_relacionadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Etapa 5: apresentação (galeria, vídeos, textos)
$stmt = $pdo->prepare("SELECT * FROM negocio_apresentacao WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$apresentacao = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$galeria = gallery_from_apresentacao($apresentacao);
$links = links_from_apresentacao($apresentacao);

// Etapa 6: impacto / visão (exemplo de tabelas já existentes)
$impacto = $pdo->query("SELECT * FROM negocio_impacto WHERE negocio_id = $negocio_id")->fetch(PDO::FETCH_ASSOC);
$visao   = $pdo->query("SELECT * FROM negocio_visao WHERE negocio_id = $negocio_id")->fetch(PDO::FETCH_ASSOC);

// Etapa 7 e 8: dados adicionais — carregamento seguro com fallback
// Ajuste os nomes das tabelas/colunas conforme seu schema real
try {
    $mercado = pdo_fetch_one($pdo, "SELECT * FROM negocio_mercado WHERE negocio_id = ?", [$negocio_id]) ?: [];
} catch (Throwable $e) {
    $mercado = [];
}

try {
    $financeiro = pdo_fetch_one($pdo, "SELECT * FROM negocio_financeiro WHERE negocio_id = ?", [$negocio_id]) ?: [];
} catch (Throwable $e) {
    $financeiro = [];
}

try {
    $sustentabilidade = pdo_fetch_one($pdo, "SELECT * FROM negocio_sustentabilidade WHERE negocio_id = ?", [$negocio_id]) ?: [];
} catch (Throwable $e) {
    $sustentabilidade = [];
}

try {
    $documentos = pdo_fetch_all($pdo, "SELECT * FROM negocio_documentos WHERE negocio_id = ? ORDER BY id DESC", [$negocio_id]) ?: [];
} catch (Throwable $e) {
    $documentos = [];
}

// Busca docs da etapa 9
$stmt = $pdo->prepare("
    SELECT * FROM negocios_documentos nd
    WHERE nd.negocio_id = ?
");
$stmt->execute([$negocio_id]);
$docs = $stmt->fetch(PDO::FETCH_ASSOC);

// Inclui header do admin
include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa1.php'; ?>
            <?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa2.php'; ?>
            <?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa3.php'; ?>
            <?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa4.php'; ?>
            <?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa5.php'; ?>
            <?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa6.php'; ?>
            <?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa7.php'; ?>
            <?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa8.php'; ?>
            <?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa9.php'; ?>
        </div>
    </div>
</div>

<!-- Botões de ação para admin -->
 <!-- NO FINAL do bloco_etapa9.php OU após bloco_etapa9 na visualizar_negocio.php -->
<?php if ($negocio['status_vitrine'] === 'em_analise'): ?>
<div class="alert alert-warning">
    <h6 class="alert-heading mb-2">
        <i class="bi bi-hourglass-split text-warning me-2"></i>
        Aguardando Aprovação de Vitrine
    </h6>
    <p class="mb-3">
        Este negócio foi enviado para análise. Verifique as documentações e aprove se tudo estiver OK.
    </p>
    <div class="d-flex gap-2">
        <a href="/admin/aprovar_negocio.php?id=<?= $negocio_id ?>" 
           class="btn btn-success btn-lg">
            <i class="bi bi-check-circle me-2"></i>
            APROVAR e Publicar na Vitrine
        </a>
        <a href="/admin/rejeitar_negocio.php?id=<?= $negocio_id ?>" 
           class="btn btn-outline-danger btn-lg">
            <i class="bi bi-x-circle me-2"></i>
            Rejeitar Cadastro
        </a>
    </div>
</div>
<?php endif; ?>

<div class="container my-4 text-center">
    <a href="/admin/negocios.php" class="btn btn-secondary">Voltar</a>
</div>


<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>