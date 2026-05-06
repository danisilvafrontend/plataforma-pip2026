<?php
// /premiacao.php — Página pública de votação da premiação ativa
session_start();
ini_set('display_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/app/helpers/premiacao_auth.php';

$config  = require __DIR__ . '/app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'], $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ── URL atual com filtros preservados (usada no redirect pós-voto) ────────────
$filtrosAtivos = array_filter([
    'categoria' => $_GET['categoria'] ?? '',
    'estado'    => $_GET['estado']    ?? '',
    'municipio' => $_GET['municipio'] ?? '',
    'eixo'      => $_GET['eixo']      ?? '',
    'ods'       => $_GET['ods']       ?? '',
]);
$redirectComFiltros = '/premiacao.php' . (!empty($filtrosAtivos) ? '?' . http_build_query($filtrosAtivos) : '');

// ── 1. Premiação ativa ────────────────────────────────────────────────────────
$premiacao = $pdo->query("
    SELECT id, nome, slug, ano, status,
           regulamento_url
    FROM premiacoes
    WHERE status IN ('ativa','planejada')
    ORDER BY CASE WHEN status = 'ativa' THEN 0 ELSE 1 END, ano DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$premiacaoId = (int)($premiacao['id'] ?? 0);

// ── 2. Fase com voto popular em andamento ────────────────────────────────────
$faseAtiva = null;
if ($premiacaoId > 0) {
    $stmtFase = $pdo->prepare("
        SELECT id, nome, tipo_fase, rodada
        FROM premiacao_fases
        WHERE premiacao_id = ?
          AND permite_voto_popular = 1
          AND status = 'em_andamento'
        ORDER BY data_inicio ASC
        LIMIT 1
    ");
    $stmtFase->execute([$premiacaoId]);
    $faseAtiva = $stmtFase->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Validação via datas da fase ativa
$votacaoAbertaPorData = false;
if ($faseAtiva) {
    $stmtFaseDatas = $pdo->prepare("SELECT data_inicio, data_fim FROM premiacao_fases WHERE id = ? LIMIT 1");
    $stmtFaseDatas->execute([$faseAtiva['id']]);
    $faseDatas = $stmtFaseDatas->fetch(PDO::FETCH_ASSOC);
    if ($faseDatas) {
        $agora = time();
        $ini   = $faseDatas['data_inicio'] ? strtotime($faseDatas['data_inicio']) : 0;
        $fim   = $faseDatas['data_fim']    ? strtotime($faseDatas['data_fim'])    : 0;
        $votacaoAbertaPorData = ($ini && $fim && $agora >= $ini && $agora <= $fim);
    }
}
$votacaoAberta = $faseAtiva && $votacaoAbertaPorData;

// ── Determina o pool de status elegíveis para a fase ativa ───────────────────
function buildStatusPoolPublic(?array $fase): string
{
    if (!$fase) return "'elegivel'";
    $tipo   = $fase['tipo_fase'] ?? 'classificatoria';
    $rodada = (int)($fase['rodada'] ?? 1);
    if ($tipo === 'final') {
        return "'finalista'";
    }
    if ($rodada <= 1) {
        return "'elegivel','classificada_fase_1'";
    }
    $anterior = 'classificada_fase_' . ($rodada - 1);
    return "'elegivel','{$anterior}','{$anterior}'";
}

$statusPool = buildStatusPoolPublic($faseAtiva);
$statusPoolArr = array_unique(array_map('trim', explode(',', str_replace("'", '', $statusPool))));
$statusPoolIn  = implode(',', array_map(fn($s) => "'$s'", $statusPoolArr));

// ── Actor unificado (empreendedor, parceiro, sociedade_civil) ─────────────────
$actor         = premiacao_current_actor();
$usuarioLogado = $actor !== null && $actor['contexto'] === 'frontend';
$usuarioId     = $actor['id']   ?? null;
$tipoEleitor   = $actor['tipo'] ?? 'empreendedor';

// ── 3. Votos já dados pelo usuário nesta fase ────────────────────────────────
$votosDoUsuario = [];
if ($usuarioLogado && $faseAtiva && $usuarioId) {
    $stmtVotos = $pdo->prepare("
        SELECT pi.negocio_id
        FROM premiacao_votos_populares pvp
        INNER JOIN premiacao_inscricoes pi ON pi.id = pvp.inscricao_id
        WHERE pvp.fase_id      = ?
          AND pvp.eleitor_id   = ?
          AND pvp.tipo_eleitor = ?
    ");
    $stmtVotos->execute([$faseAtiva['id'], $usuarioId, $tipoEleitor]);
    $votosDoUsuario = $stmtVotos->fetchAll(PDO::FETCH_COLUMN);
}

// ── 4. Negócios inscritos e elegíveis para a fase atual ──────────────────────
$sql = "
    SELECT
        n.id, n.nome_fantasia, n.categoria, n.municipio, n.estado,
        a.frase_negocio, a.logo_negocio, a.imagem_destaque,
        o.icone_url,
        e.nome AS eixo_tematico_nome,
        pi.id  AS inscricao_id,
        (
            SELECT COUNT(*)
            FROM premiacao_votos_populares pvp2
            WHERE pvp2.inscricao_id = pi.id
              AND pvp2.fase_id = :fid_count
        ) AS total_votos
    FROM negocios n
    INNER JOIN premiacao_inscricoes pi
        ON pi.negocio_id   = n.id
       AND pi.premiacao_id = :pid
       AND pi.status       IN ($statusPoolIn)
    LEFT JOIN negocio_apresentacao a  ON a.negocio_id = n.id
    LEFT JOIN ods o                   ON o.id = n.ods_prioritaria_id
    LEFT JOIN eixos_tematicos e       ON e.id = n.eixo_principal_id
    WHERE n.publicado_vitrine = 1
";
$params = [
    ':pid'       => $premiacaoId,
    ':fid_count' => (int)($faseAtiva['id'] ?? 0),
];

if (!empty($_GET['categoria'])) { $sql .= " AND n.categoria = :categoria";       $params[':categoria'] = $_GET['categoria']; }
if (!empty($_GET['estado']))    { $sql .= " AND n.estado = :estado";             $params[':estado']    = $_GET['estado'];    }
if (!empty($_GET['municipio'])) { $sql .= " AND n.municipio = :municipio";       $params[':municipio'] = $_GET['municipio']; }
if (!empty($_GET['eixo']))      { $sql .= " AND n.eixo_principal_id = :eixo";    $params[':eixo']      = $_GET['eixo'];      }
if (!empty($_GET['ods']))       { $sql .= " AND n.ods_prioritaria_id = :ods";    $params[':ods']       = $_GET['ods'];       }

$sql .= " ORDER BY n.nome_fantasia";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$negocios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 5. Filtros para os selects ───────────────────────────────────────────────
$qBase = "
    FROM negocios n
    INNER JOIN premiacao_inscricoes pi
        ON pi.negocio_id = n.id AND pi.premiacao_id = ? AND pi.status IN ($statusPoolIn)
    WHERE n.publicado_vitrine = 1
";

$categorias = $pdo->prepare("SELECT DISTINCT n.categoria $qBase ORDER BY n.categoria");
$categorias->execute([$premiacaoId]);
$categorias = $categorias->fetchAll(PDO::FETCH_COLUMN);

$estados = $pdo->prepare("SELECT DISTINCT n.estado $qBase ORDER BY n.estado");
$estados->execute([$premiacaoId]);
$estados = $estados->fetchAll(PDO::FETCH_COLUMN);

$municipios = $pdo->prepare("SELECT DISTINCT n.municipio $qBase ORDER BY n.municipio");
$municipios->execute([$premiacaoId]);
$municipios = $municipios->fetchAll(PDO::FETCH_COLUMN);

$ods = $pdo->prepare("
    SELECT DISTINCT o.id, o.nome, o.icone_url
    FROM negocios n
    INNER JOIN premiacao_inscricoes pi
        ON pi.negocio_id = n.id AND pi.premiacao_id = ? AND pi.status IN ($statusPoolIn)
    INNER JOIN ods o ON o.id = n.ods_prioritaria_id
    WHERE n.publicado_vitrine = 1
    ORDER BY o.id
");
$ods->execute([$premiacaoId]);
$ods = $ods->fetchAll(PDO::FETCH_ASSOC);

$eixos = $pdo->prepare("
    SELECT DISTINCT et.id, et.nome
    FROM negocios n
    INNER JOIN premiacao_inscricoes pi
        ON pi.negocio_id = n.id AND pi.premiacao_id = ? AND pi.status IN ($statusPoolIn)
    INNER JOIN eixos_tematicos et ON et.id = n.eixo_principal_id
    WHERE n.publicado_vitrine = 1
    ORDER BY et.nome
");
$eixos->execute([$premiacaoId]);
$eixos = $eixos->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/app/views/public/header_public.php';
?>

<div class="container vitrine-nacional-page">

    <!-- Hero -->
    <div class="vitrine-nacional-hero mb-4">
        <div class="vitrine-nacional-hero-content">
            <span class="vitrine-kicker">Premiação</span>
            <h1 class="vitrine-title mb-2">
                <?= htmlspecialchars($premiacao['nome'] ?? 'Premiação Impactos Positivos') ?>
            </h1>
            <p class="vitrine-subtitle mb-0">
                <?php if ($votacaoAberta): ?>
                    Fase de votação aberta: <strong><?= htmlspecialchars($faseAtiva['nome']) ?></strong>.
                    Conheça os negócios inscritos e vote no seu favorito.
                    <?php if (!$usuarioLogado): ?>
                        <a href="/login.php?redirect=<?= urlencode('/premiacao.php') ?>"
                           class="fw-bold" style="color:#CDDE00;">Faça login para votar.</a>
                    <?php endif; ?>
                <?php elseif ($faseAtiva): ?>
                    Fase <strong><?= htmlspecialchars($faseAtiva['nome']) ?></strong> ativa.
                    A votação abrirá em breve.
                <?php elseif ($premiacao): ?>
                    Conheça os negócios inscritos nesta edição.
                <?php else: ?>
                    Nenhuma edição ativa no momento.
                <?php endif; ?>
                <?php if (!empty($premiacao['regulamento_url'])): ?>
                    <a href="<?= htmlspecialchars($premiacao['regulamento_url']) ?>"
                       target="_blank" rel="noopener" class="ms-2 small">Ver regulamento</a>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if (!$premiacao): ?>
        <div class="vitrine-empty">
            <h3>Premiação não disponível</h3>
            <p>Nenhuma edição da premiação está ativa no momento. Volte em breve!</p>
        </div>
        <?php include __DIR__ . '/app/views/public/footer_public.php'; exit; ?>
    <?php endif; ?>

    <!-- Banner: votação fechada -->
    <?php if (!$votacaoAberta): ?>
        <div class="alert d-flex align-items-center gap-3 mb-4 rounded-3"
             style="background:#fff8e1;border:1px solid #ffe082;color:#795548;">
            <i class="bi bi-hourglass-split fs-4 flex-shrink-0"></i>
            <div>
                <strong>Votação ainda não iniciada.</strong>
                Os negócios elegíveis já estão visíveis. A votação popular será aberta em breve.
            </div>
        </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="vitrine-toolbar mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <span class="badge text-bg-light px-3 py-2 rounded-pill border">
                    <?= count($negocios) ?> negócio(s) inscrito(s)
                </span>
                <?php if ($votacaoAberta): ?>
                    <span class="badge px-3 py-2 rounded-pill ms-2"
                          style="background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;">
                        <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i> Votação aberta
                    </span>
                <?php endif; ?>
                <?php if (!empty($filtrosAtivos)): ?>
                    <span class="badge text-bg-warning px-3 py-2 rounded-pill ms-2">
                        <i class="bi bi-funnel-fill me-1"></i> <?= count($filtrosAtivos) ?> filtro(s) ativo(s)
                    </span>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-primary vitrine-filtros-toggle"
                        type="button" data-bs-toggle="collapse"
                        data-bs-target="#painelFiltros">
                    <i class="bi bi-sliders me-2"></i> Filtros
                </button>
                <a href="/premiacao.php" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </div>

    <!-- Painel de filtros -->
    <div class="collapse<?= !empty($filtrosAtivos) ? ' show' : '' ?> mb-4" id="painelFiltros">
        <form method="GET" class="vitrine-filtros-collapse">
            <div class="row g-3">
                <div class="col-md-6 col-xl-2">
                    <label class="vitrine-filtro-label">Categoria</label>
                    <select name="categoria" class="form-select vitrine-select">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>"
                                <?= ($_GET['categoria'] ?? '') === $c ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="vitrine-filtro-label">Estado</label>
                    <select name="estado" class="form-select vitrine-select">
                        <option value="">Todos</option>
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= htmlspecialchars($e) ?>"
                                <?= ($_GET['estado'] ?? '') === $e ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="vitrine-filtro-label">Município</label>
                    <select name="municipio" class="form-select vitrine-select">
                        <option value="">Todos</option>
                        <?php foreach ($municipios as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>"
                                <?= ($_GET['municipio'] ?? '') === $m ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="vitrine-filtro-label">Eixo temático</label>
                    <select name="eixo" class="form-select vitrine-select">
                        <option value="">Todos</option>
                        <?php foreach ($eixos as $e): ?>
                            <option value="<?= $e['id'] ?>"
                                <?= ($_GET['eixo'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label class="vitrine-filtro-label">ODS prioritária</label>
                    <select name="ods" class="form-select vitrine-select">
                        <option value="">Todas</option>
                        <?php foreach ($ods as $o): ?>
                            <option value="<?= $o['id'] ?>"
                                <?= ($_GET['ods'] ?? '') == $o['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($o['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12 col-xl-2 d-flex flex-column justify-content-end">
                    <div class="vitrine-filtro-acoes">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        <a href="/premiacao.php" class="btn btn-outline-secondary w-100">Limpar</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Grid -->
    <?php if (!empty($negocios)): ?>
        <div class="row g-4">
            <?php foreach ($negocios as $n):
                $cores   = ['Ideação'=>'#f59e0b','Operação'=>'#3b82f6','Tração/Escala'=>'#16a34a','Dinamizador'=>'#9333ea'];
                $corCat  = $cores[$n['categoria']] ?? '#1E3425';
                $jaVotou = in_array($n['id'], $votosDoUsuario);
            ?>
            <div class="col-md-6 col-xl-4">
                <article class="vitrine-card h-100">

                    <a href="/negocio.php?id=<?= $n['id'] ?>" class="vitrine-card-link-area">
                        <div class="vitrine-card-media <?= empty($n['imagem_destaque']) ? 'sem-capa' : '' ?>">
                            <?php if (!empty($n['imagem_destaque'])): ?>
                                <img src="<?= htmlspecialchars($n['imagem_destaque']) ?>"
                                     alt="<?= htmlspecialchars($n['nome_fantasia']) ?>"
                                     class="vitrine-card-cover">
                            <?php elseif (!empty($n['logo_negocio'])): ?>
                                <div class="vitrine-card-logo-wrap">
                                    <img src="<?= htmlspecialchars($n['logo_negocio']) ?>"
                                         alt="<?= htmlspecialchars($n['nome_fantasia']) ?>"
                                         class="vitrine-card-logo">
                                </div>
                            <?php else: ?>
                                <div class="vitrine-card-fallback">
                                    <span><?= mb_strtoupper(mb_substr($n['nome_fantasia'], 0, 1)) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($n['categoria'])): ?>
                                <span class="vitrine-card-categoria"
                                      style="--categoria-cor:<?= $corCat ?>;">
                                    <?= htmlspecialchars($n['categoria']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($votacaoAberta || (int)$n['total_votos'] > 0): ?>
                                <span class="vitrine-card-votos-badge">
                                    <i class="bi bi-trophy-fill me-1"></i>
                                    <?= (int)$n['total_votos'] ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="vitrine-card-body">
                            <div class="vitrine-card-top">
                                <h3 class="vitrine-card-title"><?= htmlspecialchars($n['nome_fantasia']) ?></h3>
                                <?php if (!empty($n['municipio']) || !empty($n['estado'])): ?>
                                    <p class="vitrine-card-local">
                                        <i class="bi bi-geo-alt"></i>
                                        <?= htmlspecialchars(trim(($n['municipio'] ?? '') . ' / ' . ($n['estado'] ?? ''), ' /')) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="vitrine-card-meta">
                                <?php if (!empty($n['eixo_tematico_nome'])): ?>
                                    <span class="vitrine-chip vitrine-chip-eixo">
                                        <?= htmlspecialchars($n['eixo_tematico_nome']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($n['icone_url'])): ?>
                                    <span class="vitrine-ods">
                                        <img src="<?= htmlspecialchars($n['icone_url']) ?>" alt="ODS">
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($n['frase_negocio'])): ?>
                                <blockquote class="vitrine-card-frase">
                                    <?= htmlspecialchars($n['frase_negocio']) ?>
                                </blockquote>
                            <?php endif; ?>
                        </div>
                    </a>

                    <!-- Ações do card -->
                    <div class="vitrine-card-actions">
                        <a href="/negocio.php?id=<?= $n['id'] ?>" class="btn btn-outline-primary">
                            Ver negócio
                        </a>

                        <?php if (!$votacaoAberta): ?>
                            <button class="btn btn-secondary" disabled title="Votação não iniciada">
                                <i class="bi bi-trophy me-1"></i> Votar
                            </button>

                        <?php elseif (!$usuarioLogado): ?>
                            <a href="/login.php?redirect=<?= urlencode($redirectComFiltros) ?>"
                               class="btn btn-primary">
                                <i class="bi bi-trophy me-1"></i> Votar
                            </a>

                        <?php elseif ($jaVotou): ?>
                            <button class="btn btn-success" disabled>
                                <i class="bi bi-check-lg me-1"></i> Votado
                            </button>

                        <?php else: ?>
                            <form method="POST" action="/premiacao/votar.php" class="d-inline">
                                <input type="hidden" name="inscricao_id" value="<?= (int)$n['inscricao_id'] ?>">
                                <input type="hidden" name="fase_id"      value="<?= (int)$faseAtiva['id'] ?>">
                                <input type="hidden" name="redirect"     value="<?= htmlspecialchars($redirectComFiltros) ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-trophy me-1"></i> Votar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                </article>
            </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="vitrine-empty">
            <h3>Nenhum negócio encontrado</h3>
            <p>Tente ajustar ou limpar os filtros.</p>
            <a href="/premiacao.php" class="btn btn-outline-primary mt-2">Limpar filtros</a>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>