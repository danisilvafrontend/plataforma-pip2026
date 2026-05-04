<?php
// /public_html/admin/visualizar_negocio.php
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

// Empreendedor responsável pelo negócio
$empreendedorResponsavel = pdo_fetch_one($pdo, "
    SELECT e.*
    FROM empreendedores e
    INNER JOIN negocios n ON n.empreendedor_id = e.id
    WHERE n.id = ?
    LIMIT 1
", [$negocio_id]) ?: [];

// ── Role do usuário logado ───────────────────────────────────────────────────
$_role          = $_SESSION['user_role'] ?? '';
$_userId        = (int)($_SESSION['user_id'] ?? 0);
$_isJuri        = ($_role === 'juri');
$_isTecnica     = ($_role === 'tecnica');
$_isJuriOuTecnica = $_isJuri || $_isTecnica;

// ── Verifica se já votou neste negócio ──────────────────────────────────────
$_jaVotou = false;
$urlVotar = null;

if ($_isJuriOuTecnica && $_userId > 0) {

    if ($_isJuri) {
        // Tabela: premiacao_votos_juri  → ligação: inscricao_id → premiacao_inscricoes.negocio_id
        $stmtVoto = $pdo->prepare("
            SELECT COUNT(*) FROM premiacao_votos_juri vj
            INNER JOIN premiacao_inscricoes pi ON pi.id = vj.inscricao_id
            WHERE vj.user_id = ? AND pi.negocio_id = ?
        ");
        $stmtVoto->execute([$_userId, $negocio_id]);
        $_jaVotou = (int)$stmtVoto->fetchColumn() > 0;
        $urlVotar = '/admin/premiacao_juri.php?negocio_id=' . $negocio_id;
    }

    if ($_isTecnica) {
        // Tabela: premiacao_votos_tecnicos → mesma lógica
        $stmtVoto = $pdo->prepare("
            SELECT COUNT(*) FROM premiacao_votos_tecnicos vt
            INNER JOIN premiacao_inscricoes pi ON pi.id = vt.inscricao_id
            WHERE vt.user_id = ? AND pi.negocio_id = ?
        ");
        $stmtVoto->execute([$_userId, $negocio_id]);
        $_jaVotou = (int)$stmtVoto->fetchColumn() > 0;
        $urlVotar = '/admin/premiacao_voto_tecnico.php?negocio_id=' . $negocio_id;
    }
}

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

            <?php if (!empty($negocio['publicado_vitrine'])): ?>
            <div class="admin-summary-item">
                <a href="/negocio.php?id=<?= $negocio_id ?>" target="_blank" rel="noopener noreferrer"
                   class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-up-right"></i> Ver na Vitrine
                </a>
            </div>
            <?php endif; ?>

            <?php if ($_isJuriOuTecnica && $urlVotar): ?>
            <div class="admin-summary-item">
                <?php if ($_jaVotou): ?>
                    <span class="btn btn-sm btn-success disabled d-inline-flex align-items-center gap-2"
                          title="Você já registrou seu voto neste negócio">
                        <i class="bi bi-check2-circle"></i>
                        <?= $_isJuri ? 'Já votei (Júri)' : 'Já votei (Técnica)' ?>
                    </span>
                <?php else: ?>
                    <a href="<?= $urlVotar ?>" class="btn btn-sm btn-success d-inline-flex align-items-center gap-2">
                        <i class="bi bi-check2-circle"></i>
                        <?= $_isJuri ? 'Votar como Júri' : 'Votar como Técnica' ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <nav class="admin-negocio-nav">
        <?php if (!is_juri_ou_tecnica()): ?>
            <a href="#empreendedor">Responsável</a>
        <?php endif; ?>

        <a href="#etapa-1">Etapa 1</a>

        <?php if (!is_juri_ou_tecnica()): ?>
            <a href="#etapa-2">Etapa 2</a>
        <?php endif; ?>

        <a href="#etapa-3">Etapa 3</a>
        <a href="#etapa-4">Etapa 4</a>
        <a href="#etapa-5">Etapa 5</a>
        <a href="#etapa-6">Etapa 6</a>
        <a href="#etapa-7">Etapa 7</a>
        <a href="#etapa-8">Etapa 8</a>

        <?php if (!is_juri_ou_tecnica()): ?>
        <a href="#etapa-9">Etapa 9</a>
        <?php endif; ?>
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
        <?php if (!is_juri_ou_tecnica()): ?>
            <section id="empreendedor" class="admin-etapa-wrap">
                <?php include __DIR__ . '/../negocios/blocos-cadastros/bloco-empreendedor.php'; ?>
            </section>
        <?php endif; ?>

        <section id="etapa-1" class="admin-etapa-wrap">
            <?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa1.php'; ?>
        </section>

        <?php if (!is_juri_ou_tecnica()): ?>
            <section id="etapa-2" class="admin-etapa-wrap">
                <?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa2.php'; ?>
            </section>
        <?php endif; ?>

        <section id="etapa-3" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa3.php'; ?></section>
        <section id="etapa-4" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa4.php'; ?></section>
        <section id="etapa-5" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa5.php'; ?></section>
        <section id="etapa-6" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa6.php'; ?></section>
        <section id="etapa-7" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa7.php'; ?></section>
        <section id="etapa-8" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa8.php'; ?></section>

        <?php if (!is_juri_ou_tecnica()): ?>
        <section id="etapa-9" class="admin-etapa-wrap"><?php include __DIR__ . '/../negocios/blocos-cadastros/bloco_etapa9.php'; ?></section>
        <?php endif; ?>
    </div>

    <?php if (($negocio['status_vitrine'] ?? '') === 'em_analise'): ?>
        <div class="admin-decisao-box">
            <div class="admin-decisao-texto">
                <span class="admin-page-kicker">Decisão administrativa</span>
                <h2 class="admin-decisao-title">Aguardando aprovação de vitrine</h2>
                <p class="mb-0">
                    Revise as informações e os documentos antes de aprovar a publicação ou indeferir o cadastro.
                </p>
            </div>

            <div class="admin-decisao-acoes">
                <a href="/admin/aprovar_negocio.php?id=<?= $negocio_id ?>" class="btn btn-success btn-lg">
                    <i class="bi bi-check-circle me-2"></i>Aprovar e publicar
                </a>

                <button type="button" class="btn btn-outline-danger btn-lg"
                        data-bs-toggle="modal" data-bs-target="#modalIndeferir">
                    <i class="bi bi-x-circle me-2"></i>Indeferir cadastro
                </button>
            </div>
        </div>

        <!-- ===== MODAL DE INDEFERIMENTO ===== -->
        <div class="modal fade" id="modalIndeferir" tabindex="-1" aria-labelledby="modalIndeferirLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="/admin/notificar_negocio.php">
                        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">

                        <div class="modal-header border-danger">
                            <h5 class="modal-title text-danger" id="modalIndeferirLabel">
                                <i class="bi bi-exclamation-triangle me-2"></i>Indeferir cadastro
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <p class="text-muted mb-3">
                                Selecione os itens com pendências. O empreendedor receberá um e-mail automático listando cada item que precisa ser corrigido, e o negócio ficará com status <strong>indeferido</strong> até o reenvio.
                            </p>

                            <p class="fw-semibold mb-2">Itens com pendência:</p>
                            <div class="row g-2 mb-4">
                                <?php
                                $opcoes = [
                                    'dados_responsável'   => 'Dados básicos (nome, CPF, data nascimento)',
                                    'dados_basicos'   => 'Dados básicos (nome, CNPJ, endereço) (Etapa 1 Dados do Negócio)',
                                    'fundadores'      => 'Dados dos fundadores (Etapa 2 Fundadores)',
                                    'eixo_tematico'   => 'Eixo temático / subáreas (Etapa 3 Eixo Temático)',
                                    'ods'             => 'ODS selecionadas (Etapa 4 ODS)',
                                    'financeiro'      => 'Informações financeiras (Etapa 5 Dados Financeiros)',
                                    'impacto'         => 'Dados de impacto social (Etapa 6 Avaliação de Impacto)',
                                    'visao'           => 'Visão de futuro e mercado (Etapa 7 Visão de Futuro)',
                                    'logotipo'        => 'Logotipo do negócio (Etapa 8 Apresentação)',
                                    'galeria_imagens' => 'Imagens da galeria (Etapa 8 Apresentação)',
                                    'video'           => 'Link de vídeo de apresentação (Etapa 8 Apresentação)',
                                    'descricao'       => 'Descrição / pitch do negócio (Etapa 8 Apresentação)',
                                    'documentos'      => 'Documentação Legal Etapa 9 (CNDT / Ambiental)',
                                ];
                                foreach ($opcoes as $val => $label): ?>
                                    <div class="col-12 col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                name="pendencias[]" value="<?= $val ?>"
                                                id="pend_<?= $val ?>">
                                            <label class="form-check-label" for="pend_<?= $val ?>">
                                                <?= $label ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mb-3">
                                <label for="observacao_livre" class="form-label fw-semibold">
                                    Observação adicional <span class="text-muted fw-normal">(opcional)</span>
                                </label>
                                <textarea class="form-control" id="observacao_livre"
                                        name="observacao_livre" rows="3"
                                        placeholder="Ex: O logotipo enviado está com fundo transparente, por favor envie em formato PNG com fundo branco."></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-send me-1"></i>Indeferir e notificar empreendedor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- ===== FIM DO MODAL ===== -->

    <?php endif; ?>

    <div class="text-center mt-4 d-flex justify-content-center align-items-center gap-3 flex-wrap">
        <a href="/admin/negocios.php" class="btn btn-outline-secondary px-4">
            <i class="bi bi-arrow-left me-1"></i>Voltar
        </a>

        <?php if (!empty($negocio['publicado_vitrine'])): ?>
        <a href="/negocio.php?id=<?= $negocio_id ?>" target="_blank" rel="noopener noreferrer"
           class="btn btn-outline-primary px-4 d-inline-flex align-items-center gap-2">
            <i class="bi bi-box-arrow-up-right"></i> Ver na Vitrine
        </a>
        <?php endif; ?>

        <?php if ($_isJuriOuTecnica && $urlVotar): ?>
            <?php if ($_jaVotou): ?>
                <span class="btn btn-success px-4 disabled d-inline-flex align-items-center gap-2"
                      title="Você já registrou seu voto neste negócio">
                    <i class="bi bi-check2-circle"></i>
                    <?= $_isJuri ? 'Já votei (Júri)' : 'Já votei (Técnica)' ?>
                </span>
            <?php else: ?>
                <a href="<?= $urlVotar ?>" class="btn btn-success px-4 d-inline-flex align-items-center gap-2">
                    <i class="bi bi-check2-circle"></i>
                    <?= $_isJuri ? 'Votar como Júri' : 'Votar como Técnica' ?>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>