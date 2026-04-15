<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

require_once __DIR__ . '/../negocios/blocos-cadastros/_shared.php';

$negocio_id = (int)($_GET['id'] ?? 0);
if ($negocio_id <= 0) {
    header("Location: /admin/negocios.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM negocios WHERE id = ?");
$stmt->execute([$negocio_id]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$negocio) {
    die("Negócio não encontrado.");
}

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

$stmt = $pdo->prepare("SELECT * FROM negocio_apresentacao WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$apresentacao = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$galeria = gallery_from_apresentacao($apresentacao);
$links = links_from_apresentacao($apresentacao);

$impacto = $pdo->query("SELECT * FROM negocio_impacto WHERE negocio_id = $negocio_id")->fetch(PDO::FETCH_ASSOC);
$visao   = $pdo->query("SELECT * FROM negocio_visao WHERE negocio_id = $negocio_id")->fetch(PDO::FETCH_ASSOC);

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

$stmt = $pdo->prepare("
    SELECT * FROM negocios_documentos nd
    WHERE nd.negocio_id = ?
");
$stmt->execute([$negocio_id]);
$docs = $stmt->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="container admin-negocio-page my-4 my-lg-5">
    <div class="admin-negocio-hero">
        <div class="admin-negocio-hero-main">
            <span class="admin-page-kicker">Painel administrativo</span>
            <h1 class="admin-negocio-title mb-2">Revisão do negócio</h1>
            <p class="admin-negocio-subtitle mb-0">
                Analise os dados cadastrados, revise documentos e decida sobre a publicação na vitrine.
            </p>
        </div>

        <div class="admin-negocio-summary">
            <div class="admin-summary-item">
                <span class="admin-summary-label">Negócio</span>
                <strong><?= htmlspecialchars($negocio['nome_fantasia'] ?? 'Não informado') ?></strong>
            </div>

            <div class="admin-summary-item">
                <span class="admin-summary-label">Status da vitrine</span>
                <strong><?= htmlspecialchars($negocio['status_vitrine'] ?? 'Não informado') ?></strong>
            </div>

            <div class="admin-summary-item">
                <span class="admin-summary-label">Publicação</span>
                <strong><?= !empty($negocio['publicado_vitrine']) ? 'Publicado' : 'Não publicado' ?></strong>
            </div>
        </div>
    </div>

    <nav class="admin-negocio-nav">
        <a href="#etapa-1">Etapa 1</a>
        <a href="#etapa-2">Etapa 2</a>
        <a href="#etapa-3">Etapa 3</a>
        <a href="#etapa-4">Etapa 4</a>
        <a href="#etapa-5">Etapa 5</a>
        <a href="#etapa-6">Etapa 6</a>
        <a href="#etapa-7">Etapa 7</a>
        <a href="#etapa-8">Etapa 8</a>
        <a href="#etapa-9">Etapa 9</a>
    </nav>

    <?php if (isset($_SESSION['erro'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">
            <?= htmlspecialchars($_SESSION['erro']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['erro']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
            <?= htmlspecialchars($_SESSION['sucesso']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['sucesso']); ?>
    <?php endif; ?>

    <div class="admin-negocio-content mt-4">
        <section id="etapa-1" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa1.php'; ?></section>
        <section id="etapa-2" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa2.php'; ?></section>
        <section id="etapa-3" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa3.php'; ?></section>
        <section id="etapa-4" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa4.php'; ?></section>
        <section id="etapa-5" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa5.php'; ?></section>
        <section id="etapa-6" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa6.php'; ?></section>
        <section id="etapa-7" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa7.php'; ?></section>
        <section id="etapa-8" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa8.php'; ?></section>
        <section id="etapa-9" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa9.php'; ?></section>
    </div>

    <?php if (($negocio['status_vitrine'] ?? '') === 'em_analise'): ?>
        <div class="admin-decisao-box">
            <div class="admin-decisao-texto">
                <span class="admin-page-kicker">Decisão administrativa</span>
                <h2 class="admin-decisao-title">Aguardando aprovação de vitrine</h2>
                <p class="mb-0">
                    Este negócio foi enviado para análise. Revise as informações e os documentos antes de aprovar a publicação ou rejeitar o cadastro.
                </p>
            </div>

            <div class="admin-decisao-acoes">
                <a href="/admin/aprovar_negocio.php?id=<?= $negocio_id ?>" class="btn btn-success btn-lg">
                    <i class="bi bi-check-circle me-2"></i>
                    Aprovar e publicar
                </a>

                <a href="/admin/rejeitar_negocio.php?id=<?= $negocio_id ?>" class="btn btn-outline-danger btn-lg">
                    <i class="bi bi-x-circle me-2"></i>
                    Rejeitar cadastro
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="/admin/negocios.php" class="btn btn-outline-secondary px-4">Voltar</a>
    </div>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>