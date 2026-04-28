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

// Empreendedor responsável (dono da conta vinculada ao negócio)
$empreendedorResponsavel = pdo_fetch_one($pdo, "
    SELECT e.*
    FROM empreendedores e
    INNER JOIN negocios n ON n.empreendedor_id = e.id
    WHERE n.id = ?
    LIMIT 1
", [$negocio_id]) ?: [];
/* -------------------------
   Partials (8 blocos)
   ------------------------- */

$base_partials = __DIR__ . '/blocos-cadastros';
$partials = [
    'empreendedor' => $base_partials . '/bloco_empreendedor.php',
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

    <nav class="admin-negocio-nav"> 
        <a href="#empreendedor">Responsável</a>
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

    <div class="admin-negocio-content mt-4">
        <section id="empreendedor" class="admin-etapa-wrap"><?php include __DIR__ . '/blocos-cadastros/bloco-empreendedor.php'; ?>
        </section>
        <section id="etapa-1" class="admin-etapa-wrap"><?php include __DIR__ . '/blocos-cadastros/bloco_etapa1.php'; ?></section>
        <section id="etapa-2" class="admin-etapa-wrap"><?php include __DIR__ . '/blocos-cadastros/bloco_etapa2.php'; ?></section>
        <section id="etapa-3" class="admin-etapa-wrap"><?php include __DIR__ . '/blocos-cadastros/bloco_etapa3.php'; ?></section>
        <section id="etapa-4" class="admin-etapa-wrap"><?php include __DIR__ . '/blocos-cadastros/bloco_etapa4.php'; ?></section>
        <section id="etapa-5" class="admin-etapa-wrap"><?php include __DIR__ . '/blocos-cadastros/bloco_etapa5.php'; ?></section>
        <section id="etapa-6" class="admin-etapa-wrap"><?php include __DIR__ . '/blocos-cadastros/bloco_etapa6.php'; ?></section>
        <section id="etapa-7" class="admin-etapa-wrap"><?php include __DIR__ . '/blocos-cadastros/bloco_etapa7.php'; ?></section>
        <section id="etapa-8" class="admin-etapa-wrap"><?php include __DIR__ . '/blocos-cadastros/bloco_etapa8.php'; ?></section>
        <section id="etapa-9" class="admin-etapa-wrap"><?php include __DIR__ . '/blocos-cadastros/bloco_etapa9.php'; ?></section>
    </div>

    <!-- Ações da Revisão -->
    <div class="form-section mb-4">

    <div class="form-section-title">
        <i class="bi bi-send-check"></i> Publicar na Vitrine
    </div>

    <!-- 3 cards informativos -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
        <div class="emp-card h-100" style="padding:1rem;">
            <div class="d-flex align-items-start gap-3">
            <div class="emp-stat-icon" style="background:#f0f4ed; color:#1E3425;">
                <i class="bi bi-globe2"></i>
            </div>
            <div>
                <div class="fw-bold small mb-1" style="color:#1E3425;">Visibilidade pública</div>
                <div class="small text-muted">Seu negócio aparece na Vitrine Nacional e pode ser encontrado por parceiros, investidores e pela comunidade.</div>
            </div>
            </div>
        </div>
        </div>
        <div class="col-12 col-md-4">
        <div class="emp-card h-100" style="padding:1rem;">
            <div class="d-flex align-items-start gap-3">
            <div class="emp-stat-icon" style="background:#f0f4ed; color:#1E3425;">
                <i class="bi bi-diagram-3"></i>
            </div>
            <div>
                <div class="fw-bold small mb-1" style="color:#1E3425;">Parte do ecossistema</div>
                <div class="small text-muted">Você passa a integrar o mapa de negócios de impacto do Brasil, conectado a outros empreendedores e organizações.</div>
            </div>
            </div>
        </div>
        </div>
        <div class="col-12 col-md-4">
        <div class="emp-card h-100" style="padding:1rem;">
            <div class="d-flex align-items-start gap-3">
            <div class="emp-stat-icon" style="background:#f0f4ed; color:#CDDE00;">
                <i class="bi bi-trophy" style="color:#1E3425;"></i>
            </div>
            <div>
                <div class="fw-bold small mb-1" style="color:#1E3425;">Elegível à Premiação</div>
                <div class="small text-muted">Negócios publicados ficam aptos a participar da Premiação Impactos Positivos — você decide quando quiser se inscrever.</div>
            </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Ações -->
    <?php if ((int)($negocio['publicado_vitrine'] ?? 0) === 1): ?>

        <!-- Já publicado -->
        <div class="d-flex align-items-center gap-3 p-3 rounded mb-3"
            style="background:#e8f5e9; border:1px solid #a5d6a7;">
        <i class="bi bi-check-circle-fill" style="color:#2e7d32; font-size:1.3rem;"></i>
        <div>
            <div class="fw-semibold small" style="color:#2e7d32;">Negócio publicado na vitrine</div>
            <div class="small text-muted">Seu negócio já está visível publicamente.</div>
        </div>
        <a href="/negocio.php?id=<?= $negocio_id ?>"
            target="_blank"
            class="btn-emp-outline ms-auto flex-shrink-0">
            <i class="bi bi-box-arrow-up-right me-1"></i> Ver na vitrine
        </a>
        </div>

    <?php else: ?>

        <!-- Botões de ação -->
        <div class="d-flex flex-wrap align-items-center gap-2">

        <!-- 1. Pré-visualizar -->
        <button type="button" class="btn-emp-outline" data-bs-toggle="modal" data-bs-target="#previewModal">
            <i class="bi bi-display me-1"></i> Pré-visualizar
        </button>

        <!-- 2. Enviar para revisão (só publica, sem premiação) -->
        <form action="/negocios/publicar.php" method="post" class="d-inline">
            <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
            <input type="hidden" name="acao" value="publicar">
            <button type="submit" class="btn-emp-outline">
            <i class="bi bi-send me-1"></i> Enviar para Avaliação
            </button>
        </form>

        <!-- 3. Enviar para revisão + inscrever na premiação (abre modal) -->
        <?php if ($premiacaoVigente): ?>
            <button type="button"
                    class="btn-emp-primary"
                    onclick="abrirModalRevisaoPremiacao(<?= $negocio_id ?>, <?= (int)($inscricaoPremiacao['aceite_regulamento'] ?? 0) ?>, <?= (int)($inscricaoPremiacao['aceite_veracidade'] ?? 0) ?>)">
            <i class="bi bi-trophy me-1"></i> Enviar para Avaliação e Inscrever na Premiação
            </button>
        <?php endif; ?>

        </div>

    <?php endif; ?>

    </div>


    <!-- Modal — Revisão + Premiação -->
    <?php if ($premiacaoVigente): ?>
    <div class="modal fade ip-modal" id="modalRevisaoPremiacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none;">
        <form action="/negocios/publicar.php" method="post" id="formRevisaoPremiacao">
            <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
            <input type="hidden" name="acao" value="publicar_com_premiacao">

            <div class="modal-header">
            <h5 class="modal-title">
                <i class="bi bi-trophy me-2" style="color:#CDDE00;"></i>
                Inscrição na Premiação
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

            <p class="small text-muted mb-1">
                <strong style="color:#1E3425;"><?= htmlspecialchars($premiacaoVigente['nome']) ?></strong>
            </p>
            <p class="small text-muted mb-4">
                A inscrição é gratuita e opcional. Negócios publicados ficam aptos a participar da votação pública e ao reconhecimento da comunidade. Você pode alterar sua participação a qualquer momento em "Meus Negócios".
            </p>

            <div class="form-check p-3 mb-2 rounded" style="background:#f5f7f2; border:1px solid #e8ede5;">
                <input class="form-check-input" type="checkbox"
                    name="aceite_regulamento"
                    id="modal_rev_aceite_regulamento" value="1">
                <label class="form-check-label small" for="modal_rev_aceite_regulamento">
                Li e aceito o
                <a href="https://impactospositivos.com/regulamento-do-premio/"
                    target="_blank" rel="noopener noreferrer"
                    style="color:#1E3425; font-weight:700;">
                    regulamento da Premiação
                </a>
                </label>
            </div>

            <div class="form-check p-3 rounded" style="background:#f5f7f2; border:1px solid #e8ede5;">
                <input class="form-check-input" type="checkbox"
                    name="aceite_veracidade"
                    id="modal_rev_aceite_veracidade" value="1">
                <label class="form-check-label small" for="modal_rev_aceite_veracidade">
                Declaro que todas as informações publicadas sobre este negócio são verdadeiras
                e de minha responsabilidade
                </label>
            </div>

            </div>

            <div class="modal-footer">
            <button type="button" class="btn-modal-fechar" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn-emp-primary">
                <i class="bi bi-send me-1"></i> Enviar e me inscrever
            </button>
            </div>

        </form>
        </div>
    </div>
    </div>

    <script>
        function abrirModalRevisaoPremiacao(negocioId, aceiteReg, aceiteVer) {
        document.getElementById('modal_rev_aceite_regulamento').checked = aceiteReg === 1;
        document.getElementById('modal_rev_aceite_veracidade').checked  = aceiteVer === 1;
        new bootstrap.Modal(document.getElementById('modalRevisaoPremiacao')).show();
    }

    // Validação antes de submeter
    document.getElementById('formRevisaoPremiacao').addEventListener('submit', function(e) {
        const regulamento = document.getElementById('modal_rev_aceite_regulamento');
        const veracidade  = document.getElementById('modal_rev_aceite_veracidade');

        if (!regulamento.checked || !veracidade.checked) {
            e.preventDefault();

            // Remove alerta anterior se existir
            const anterior = document.getElementById('alerta-modal-premiacao');
            if (anterior) anterior.remove();

            const alerta = document.createElement('div');
            alerta.id = 'alerta-modal-premiacao';
            alerta.className = 'alert alert-warning py-2 mt-3 mb-0 small';
            alerta.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Para se inscrever na premiação, você precisa aceitar o regulamento e confirmar a veracidade das informações.';

            // Insere o alerta após os checkboxes (dentro do modal-body)
            veracidade.closest('.modal-body').appendChild(alerta);

            // Destaca os checkboxes não marcados
            [regulamento, veracidade].forEach(function(el) {
                if (!el.checked) {
                    el.closest('.form-check').style.border = '1.5px solid #dc3545';
                    el.closest('.form-check').style.background = '#fff5f5';
                } else {
                    el.closest('.form-check').style.border = '1px solid #e8ede5';
                    el.closest('.form-check').style.background = '#f5f7f2';
                }
            });

            return false;
        }
    });

    // Limpa visual de erro ao marcar
    ['modal_rev_aceite_regulamento', 'modal_rev_aceite_veracidade'].forEach(function(id) {
        document.getElementById(id).addEventListener('change', function() {
            this.closest('.form-check').style.border = '1px solid #e8ede5';
            this.closest('.form-check').style.background = '#f5f7f2';
            const alerta = document.getElementById('alerta-modal-premiacao');
            if (alerta) alerta.remove();
        });
    });
    </script>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/modais/modal_vitrine.php'; ?>
<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>
