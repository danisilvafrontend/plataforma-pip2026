<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Autenticação
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Config DB
$config = require __DIR__ . '/../app/config/db.php';
try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Erro ao conectar ao banco de dados.");
}

// Helpers compartilhados
require_once __DIR__ . '/blocos-cadastros/_shared.php';

if (file_exists('./blocos_cadastros/bloco_etapa1.php')) {
    echo '<!-- bloco_etapa1 OK -->';
} else {
    echo '<!-- ERRO: bloco_etapa1 NÃO encontrado -->';
}


// Recebe id do negócio e valida
$negocio_id = (int)($_GET['id'] ?? 0);
if ($negocio_id <= 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}

// Busca negócio e valida permissão do empreendedor
$stmt = $pdo->prepare("SELECT * FROM negocios WHERE id = ? AND empreendedor_id = ?");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$negocio) {
    die("Negócio não encontrado ou você não tem permissão.");
}

/* -------------------------
   Carregamento de dados
   ------------------------- */

// Etapa 1: dados gerais já em $negocio

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
    SELECT icone_url, n_ods, nome 
    FROM ods 
    WHERE id = (SELECT ods_prioritaria_id FROM negocios WHERE id = ?)
");
$stmt->execute([$negocio_id]);
$ods_prioritaria = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT o.icone_url, n_ods, nome
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

/* -------------------------
   Partials (8 blocos)
   ------------------------- */

$base_partials = __DIR__ . '/blocos-cadastros';
$partials = [
    'etapa1' => $base_partials . '/bloco_etapa1.php',
    'etapa2' => $base_partials . '/bloco_etapa2.php',
    'etapa3' => $base_partials . '/bloco_etapa3.php',
    'etapa4' => $base_partials . '/bloco_etapa4.php',
    'etapa5' => $base_partials . '/bloco_etapa5.php',
    'etapa6' => $base_partials . '/bloco_etapa6.php',
    'etapa7' => $base_partials . '/bloco_etapa7.php',
    'etapa8' => $base_partials . '/bloco_etapa8.php',
    'etapa9' => $base_partials . '/bloco_etapa9.php'
];

// Inclui header do layout do empreendedor
include __DIR__ . '/../app/views/empreendedor/header.php';

?>


<!-- SUBSTITUA esta seção no visualizar.php (após carregar TODOS os dados: $negocio, $financeiro, etc.) -->

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            
            <?php include __DIR__ . '/blocos-cadastros/bloco_etapa1.php'; ?>
            <?php include __DIR__ . '/blocos-cadastros/bloco_etapa2.php'; ?>
            <?php include __DIR__ . '/blocos-cadastros/bloco_etapa3.php'; ?>
            <?php include __DIR__ . '/blocos-cadastros/bloco_etapa4.php'; ?>
            <?php include __DIR__ . '/blocos-cadastros/bloco_etapa5.php'; ?>
            <?php include __DIR__ . '/blocos-cadastros/bloco_etapa6.php'; ?>
            <?php include __DIR__ . '/blocos-cadastros/bloco_etapa7.php'; ?>
            <?php include __DIR__ . '/blocos-cadastros/bloco_etapa8.php'; ?>
            <?php include __DIR__ . '/blocos-cadastros/bloco_etapa9.php'; ?>

        </div>
    </div>
</div>


<!-- Botões de ação -->
<div class="container my-4 text-center">
    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#previewModal">
        Pré-visualizar na Vitrine
    </button>
    
    <?php if (($negocio['publicado_vitrine'] ?? 0) == 1): ?>
        <form action="/negocios/publicar.php" method="post" class="d-inline-block ms-2" onsubmit="return confirm('Deseja ocultar o negócio da vitrine?');">
            <input type="hidden" name="negocio_id" value="<?= e($negocio_id) ?>">
            <input type="hidden" name="acao" value="remover">
            <button type="submit" class="btn btn-outline-danger">Remover da Vitrine</button>
        </form>
        <a href="/negocio.php?id=<?= e($negocio_id) ?>" target="_blank" class="btn btn-success ms-2">Acessar Link Público</a>
    <?php else: ?>
        <form action="/negocios/publicar.php" method="post" class="d-inline-block ms-2">
            <input type="hidden" name="negocio_id" value="<?= e($negocio_id) ?>">
            <input type="hidden" name="acao" value="publicar">
            <button type="submit" class="btn btn-success">Enviar Cadastro para Avaliação</button>
        </form>
    <?php endif; ?>
</div>


<?php include __DIR__ . '/modais/modal_vitrine.php'; ?>

<!-- Footer normal -->
<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>
