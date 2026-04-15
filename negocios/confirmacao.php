<?php
// /public_html/negocios/confirmacao.php
session_start();

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
    SELECT 
        et.id,
        et.nome AS eixo_nome,
        et.icone_url
    FROM negocios n
    LEFT JOIN eixos_tematicos et ON et.id = n.eixo_principal_id
    WHERE n.id = ?
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

// Premiação vigente
$stmtPremiacao = $pdo->query("
    SELECT id, nome, ano, status
    FROM premiacoes
    WHERE status IN ('ativa', 'planejada')
    ORDER BY 
        CASE WHEN status = 'ativa' THEN 0 ELSE 1 END,
        ano DESC,
        id DESC
    LIMIT 1
");
$premiacaoVigente = $stmtPremiacao->fetch(PDO::FETCH_ASSOC);

// Inscrição já existente para este negócio na premiação vigente
$inscricaoPremiacao = null;
if (!empty($premiacaoVigente['id'])) {
    $stmtInscricaoPremiacao = $pdo->prepare("
        SELECT *
        FROM premiacao_inscricoes
        WHERE premiacao_id = ? AND negocio_id = ?
        LIMIT 1
    ");
    $stmtInscricaoPremiacao->execute([(int)$premiacaoVigente['id'], $negocio_id]);
    $inscricaoPremiacao = $stmtInscricaoPremiacao->fetch(PDO::FETCH_ASSOC) ?: null;
}

include __DIR__ . '/../app/views/empreendedor/header.php'; ?>

<div class="container py-4" style="max-width: 1200px;">

    <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="emp-page-title mb-1">
                Revisão Final: <?= htmlspecialchars($negocio['nome_fantasia'] ?? 'Negócio') ?>
            </h1>
            <p class="emp-page-subtitle mb-0">
                Confira todas as etapas do cadastro antes de enviar para avaliação ou publicar na vitrine.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline">
                <i class="bi bi-arrow-left me-1"></i> Meus Negócios
            </a>
        </div>
    </div>

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

    <div class="emp-confirm-actions-card mt-4">
        <div class="emp-confirm-actions-header">
            <div>
                <h2 class="emp-confirm-actions-title mb-1">Ações da Revisão</h2>
                <p class="emp-confirm-actions-subtitle mb-0">
                    Pré-visualize a vitrine, envie para avaliação ou gerencie a publicação do cadastro.
                </p>
            </div>
        </div>

        <div class="emp-confirm-actions-body">
            <div class="emp-confirm-actions-grid">

                <button type="button"
                        class="btn-emp-outline emp-confirm-action-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#previewModal">
                    <i class="bi bi-display me-2"></i> Pré-visualizar na Vitrine
                </button>

                <?php if (($negocio['publicado_vitrine'] ?? 0) == 1): ?>

                    <form action="/negocios/publicar.php"
                          method="post"
                          class="m-0"
                          onsubmit="return confirm('Deseja ocultar o negócio da vitrine?');">
                        <input type="hidden" name="negocio_id" value="<?= e($negocio_id) ?>">
                        <input type="hidden" name="acao" value="remover">

                        <button type="submit" class="btn-emp-outline emp-confirm-action-btn emp-confirm-action-danger w-100">
                            <i class="bi bi-eye-slash me-2"></i> Remover da Vitrine
                        </button>
                    </form>

                    <a href="/negocio.php?id=<?= e($negocio_id) ?>"
                       target="_blank"
                       class="btn-emp-primary emp-confirm-action-btn">
                        <i class="bi bi-box-arrow-up-right me-2"></i> Acessar Link Público
                    </a>

                <?php else: ?>

                    <form action="/negocios/publicar.php" method="post" class="m-0 w-100">
                        <input type="hidden" name="negocio_id" value="<?= e($negocio_id) ?>">
                        <input type="hidden" name="acao" value="publicar">

                        <?php if (!empty($premiacaoVigente['id'])): ?>
                            <div class="mt-3 p-3 rounded" style="background:#f7f9f5; border:1px solid #e6ece1;">
                                <div class="small fw-semibold mb-2" style="color:#1E3425;">
                                    <i class="bi bi-trophy me-1"></i>
                                    Participação na premiação vigente: <?= htmlspecialchars($premiacaoVigente['nome']) ?>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input"
                                        type="checkbox"
                                        name="premiacao_deseja_participar"
                                        id="premiacao_deseja_participar"
                                        value="1"
                                        <?= (int)($inscricaoPremiacao['deseja_participar'] ?? 0) === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="premiacao_deseja_participar">
                                        Desejo inscrever este negócio na premiação vigente
                                    </label>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input"
                                        type="checkbox"
                                        name="premiacao_aceite_regulamento"
                                        id="premiacao_aceite_regulamento"
                                        value="1"
                                        <?= (int)($inscricaoPremiacao['aceite_regulamento'] ?? 0) === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="premiacao_aceite_regulamento">
                                        Aceito o
                                        <a href="https://impactospositivos.com/regulamento-do-premio/" target="_blank" rel="noopener noreferrer">
                                            regulamento
                                        </a>
                                    </label>
                                </div>

                                <div class="form-check mb-0">
                                    <input class="form-check-input"
                                        type="checkbox"
                                        name="premiacao_aceite_veracidade"
                                        id="premiacao_aceite_veracidade"
                                        value="1"
                                        <?= (int)($inscricaoPremiacao['aceite_veracidade'] ?? 0) === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="premiacao_aceite_veracidade">
                                        Declaro a veracidade das informações para a premiação
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn-emp-primary emp-confirm-action-btn w-100 mt-3">
                            <i class="bi bi-send-check me-2"></i> Enviar Cadastro para Avaliação
                        </button>
                    </form>

                <?php endif; ?>

            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/modais/modal_vitrine.php'; ?>
<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>
