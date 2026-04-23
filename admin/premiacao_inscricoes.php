<?php
declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

$appBase = dirname(__DIR__) . '/app';
$config  = require $appBase . '/config/db.php';

$dsn  = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s',
    $config['host'], $config['dbname'], $config['port'], $config['charset']);
$opts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $opts);
} catch (PDOException $e) {
    die('Erro na conexão com o banco: ' . $e->getMessage());
}

$pageTitle = 'Premiação — Inscrições';

function h(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $dt): string
{
    if (empty($dt) || str_starts_with($dt, '0000')) return '—';
    return date('d/m/Y H:i', strtotime($dt));
}

function badgeStatus(string $status): string
{
    $map = [
        'rascunho'            => ['#e2e3e5', '#41464b', 'Rascunho'],
        'enviada'             => ['#cfe2ff', '#084298', 'Enviada'],
        'em_triagem'          => ['#fff3cd', '#856404', 'Em Triagem'],
        'elegivel'            => ['#d1e7dd', '#0f5132', 'Elegível'],
        'inelegivel'          => ['#fde8ea', '#842029', 'Inelegível'],
        'classificada_fase_1' => ['#CDDE00', '#1E3425', 'Class. Fase 1'],
        'classificada_fase_2' => ['#a8f0c6', '#145a32', 'Class. Fase 2'],
        'finalista'           => ['#ffd6a5', '#7d3c00', 'Finalista'],
        'vencedora'           => ['#ffe066', '#5a3e00', 'Vencedora'],
        'eliminada'           => ['#f5c6cb', '#721c24', 'Eliminada'],
    ];
    [$bg, $color, $label] = $map[$status] ?? ['#e2e3e5', '#41464b', ucfirst($status)];
    return '<span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;
        border-radius:999px;background:' . $bg . ';color:' . $color . ';
        font-size:11px;font-weight:700;white-space:nowrap;">'
        . h($label) . '</span>';
}

// ── Filtros ───────────────────────────────────────────────────────────────────
$filtroAno         = (int)   ($_GET['ano']          ?? 0);
$filtroPremiacao   = (int)   ($_GET['premiacao_id'] ?? 0);
$filtroStatus      =         trim($_GET['status']   ?? '');
$filtroBusca       =         trim($_GET['busca']    ?? '');
$filtroCategoria   =         trim($_GET['categoria'] ?? '');

// ── Anos disponíveis para o select ───────────────────────────────────────────
$anos = $pdo->query("SELECT DISTINCT ano FROM premiacoes ORDER BY ano DESC")->fetchAll(PDO::FETCH_COLUMN);

// ── Premiações filtradas pelo ano selecionado ─────────────────────────────────
$stmtPrem = $filtroAno > 0
    ? $pdo->prepare("SELECT id, nome FROM premiacoes WHERE ano = ? ORDER BY id DESC")
    : $pdo->prepare("SELECT id, nome FROM premiacoes ORDER BY ano DESC, id DESC");
if ($filtroAno > 0) $stmtPrem->execute([$filtroAno]);
else                $stmtPrem->execute();
$premiacoes = $stmtPrem->fetchAll();

// ── Totais para os cards de KPI ───────────────────────────────────────────────
// Total de negócios publicados na plataforma
$totalNegocios = (int) $pdo->query("SELECT COUNT(*) FROM negocios WHERE publicado_vitrine = 1")->fetchColumn();

// Total de inscrições (com filtro de ano/premiação se aplicado)
$whereKpi  = [];
$paramsKpi = [];
if ($filtroAno > 0) {
    $whereKpi[]  = 'p.ano = ?';
    $paramsKpi[] = $filtroAno;
}
if ($filtroPremiacao > 0) {
    $whereKpi[]  = 'pi.premiacao_id = ?';
    $paramsKpi[] = $filtroPremiacao;
}
$whereKpiSql = $whereKpi ? 'WHERE ' . implode(' AND ', $whereKpi) : '';

$sqlKpi = "SELECT
    COUNT(*)                                               AS total_inscritos,
    SUM(pi.status = 'elegivel')                            AS total_elegiveis,
    SUM(pi.status IN ('classificada_fase_1','classificada_fase_2','finalista','vencedora')) AS total_classificados,
    SUM(pi.status = 'vencedora')                           AS total_vencedores
FROM premiacao_inscricoes pi
INNER JOIN premiacoes p ON p.id = pi.premiacao_id
$whereKpiSql";
$stmtKpi = $pdo->prepare($sqlKpi);
$stmtKpi->execute($paramsKpi);
$kpi = $stmtKpi->fetch();

$totalInscritos     = (int) ($kpi['total_inscritos']    ?? 0);
$totalElegiveis     = (int) ($kpi['total_elegiveis']    ?? 0);
$totalClassificados = (int) ($kpi['total_classificados'] ?? 0);
$totalVencedores    = (int) ($kpi['total_vencedores']   ?? 0);
$pctInscritos       = $totalNegocios > 0 ? round(($totalInscritos / $totalNegocios) * 100, 1) : 0;
$pctElegiveis       = $totalInscritos > 0 ? round(($totalElegiveis / $totalInscritos) * 100, 1) : 0;

// ── Categorias disponíveis para filtro ────────────────────────────────────────
$categoriasDisponiveis = $pdo->query(
    "SELECT DISTINCT categoria FROM premiacao_inscricoes ORDER BY categoria"
)->fetchAll(PDO::FETCH_COLUMN);

// ── Query principal de inscrições ─────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($filtroAno > 0) {
    $where[]  = 'p.ano = ?';
    $params[] = $filtroAno;
}
if ($filtroPremiacao > 0) {
    $where[]  = 'pi.premiacao_id = ?';
    $params[] = $filtroPremiacao;
}
if ($filtroStatus !== '') {
    $where[]  = 'pi.status = ?';
    $params[] = $filtroStatus;
}
if ($filtroCategoria !== '') {
    $where[]  = 'pi.categoria = ?';
    $params[] = $filtroCategoria;
}
if ($filtroBusca !== '') {
    $where[]  = 'n.nome_fantasia LIKE ?';
    $params[] = '%' . $filtroBusca . '%';
}

$whereSql = implode(' AND ', $where);

$sql = "
    SELECT
        pi.id,
        pi.premiacao_id,
        pi.negocio_id,
        pi.empreendedor_id,
        pi.categoria,
        pi.status,
        pi.aceite_regulamento,
        pi.aceite_veracidade,
        pi.deseja_participar,
        pi.observacoes_admin,
        pi.enviado_em,
        pi.created_at,
        pi.updated_at,
        n.nome_fantasia,
        n.municipio,
        n.estado,
        p.nome  AS premiacao_nome,
        p.ano   AS premiacao_ano,
        CONCAT(e.nome, ' ', e.sobrenome) AS empreendedor_nome
    FROM premiacao_inscricoes pi
    INNER JOIN premiacoes p   ON p.id  = pi.premiacao_id
    INNER JOIN negocios   n   ON n.id  = pi.negocio_id
    INNER JOIN empreendedores e ON e.id = pi.empreendedor_id
    WHERE $whereSql
    ORDER BY pi.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inscricoes = $stmt->fetchAll();

// ── Atualização de status via POST ────────────────────────────────────────────
$mensagem = '';
$erro      = '';

if (isset($_GET['ok'])) $mensagem = trim($_GET['ok']);
if (isset($_GET['err'])) $erro    = trim($_GET['err']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    try {
        $inscricaoId = (int) ($_POST['inscricao_id'] ?? 0);
        $novoStatus  = trim($_POST['novo_status'] ?? '');
        $obs         = trim($_POST['observacoes_admin'] ?? '');

        $statusValidos = ['rascunho','enviada','em_triagem','elegivel','inelegivel',
            'classificada_fase_1','classificada_fase_2','finalista','vencedora','eliminada'];

        if ($inscricaoId <= 0 || !in_array($novoStatus, $statusValidos, true)) {
            throw new Exception('Dados inválidos.');
        }

        $stmtUpd = $pdo->prepare("
            UPDATE premiacao_inscricoes
            SET status = ?, observacoes_admin = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmtUpd->execute([$novoStatus, $obs !== '' ? $obs : null, $inscricaoId]);

        header('Location: premiacao_inscricoes.php?' . http_build_query(array_filter([
            'ano'          => $filtroAno ?: null,
            'premiacao_id' => $filtroPremiacao ?: null,
            'status'       => $filtroStatus,
            'busca'        => $filtroBusca,
            'categoria'    => $filtroCategoria,
            'ok'           => 'Status atualizado com sucesso.',
        ])));
        exit;
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}

require_once $appBase . '/views/admin/header.php';
?>

<style>
.kpi-card {
    border-radius: 12px;
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    box-shadow: 0 1px 4px rgba(30,52,37,.08);
    background: #fff;
    border: 1px solid #e8ede9;
    height: 100%;
}
.kpi-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; margin-bottom: 4px;
}
.kpi-valor { font-size: 28px; font-weight: 800; color: #1E3425; line-height: 1; }
.kpi-label { font-size: 12px; color: #6c7a6e; font-weight: 500; }
.kpi-pct   { font-size: 11px; color: #9aab9d; }
.progress-thin { height: 5px; border-radius: 99px; background: #e8ede9; overflow: hidden; }
.progress-thin .bar { height: 100%; border-radius: 99px; background: #CDDE00; transition: width .4s; }

.filtros-card {
    background: #fff;
    border: 1px solid #e8ede9;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 24px;
}

.inscricoes-table th { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6c7a6e; font-weight: 600; border-bottom: 2px solid #e8ede9; }
.inscricoes-table td { vertical-align: middle; font-size: 13px; border-color: #f0f4f1; }
.inscricoes-table tbody tr:hover { background: #f7faf7; }

.modal-header { background: #1E3425; color: #fff; }
.modal-header .btn-close { filter: invert(1); }
</style>

<div class="container-fluid py-4">

    <!-- Cabeçalho -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-1">Premiação — Inscrições</h1>
            <p class="text-muted mb-0">Gerencie os negócios inscritos e atualize seus status na premiação.</p>
        </div>
        <span class="badge bg-light text-dark border" style="font-size:12px;padding:8px 12px;">
            <i class="bi bi-list-check me-1"></i>
            <?= count($inscricoes) ?> inscrição<?= count($inscricoes) !== 1 ? 'ões' : '' ?> encontrada<?= count($inscricoes) !== 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= h($mensagem) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= h($erro) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#eaf7ef;">
                    <i class="bi bi-trophy-fill" style="color:#1E3425;"></i>
                </div>
                <div class="kpi-valor"><?= $totalInscritos ?></div>
                <div class="kpi-label">Total de Inscrições</div>
                <div class="progress-thin mt-1"><div class="bar" style="width:<?= min($pctInscritos,100) ?>%;"></div></div>
                <div class="kpi-pct"><?= $pctInscritos ?>% dos negócios publicados</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#e7f5ff;">
                    <i class="bi bi-check2-circle" style="color:#084298;"></i>
                </div>
                <div class="kpi-valor"><?= $totalElegiveis ?></div>
                <div class="kpi-label">Elegíveis</div>
                <div class="progress-thin mt-1"><div class="bar" style="width:<?= min($pctElegiveis,100) ?>%;background:#084298;"></div></div>
                <div class="kpi-pct"><?= $pctElegiveis ?>% das inscrições</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#fffbe6;">
                    <i class="bi bi-star-fill" style="color:#856404;"></i>
                </div>
                <div class="kpi-valor"><?= $totalClassificados ?></div>
                <div class="kpi-label">Classificados</div>
                <div class="progress-thin mt-1"><div class="bar" style="width:<?= $totalInscritos > 0 ? min(round($totalClassificados/$totalInscritos*100),100) : 0 ?>%;background:#856404;"></div></div>
                <div class="kpi-pct"><?= $totalInscritos > 0 ? round($totalClassificados/$totalInscritos*100,1) : 0 ?>% das inscrições</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#fff8e1;">
                    <i class="bi bi-award-fill" style="color:#5a3e00;"></i>
                </div>
                <div class="kpi-valor"><?= $totalVencedores ?></div>
                <div class="kpi-label">Vencedores</div>
                <div class="progress-thin mt-1"><div class="bar" style="width:<?= $totalInscritos > 0 ? min(round($totalVencedores/$totalInscritos*100),100) : 0 ?>%;background:#CDDE00;"></div></div>
                <div class="kpi-pct"><?= $totalNegocios ?> negócios publicados na plataforma</div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-card">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Ano</label>
                <select name="ano" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos os anos</option>
                    <?php foreach ($anos as $a): ?>
                        <option value="<?= (int)$a ?>" <?= $filtroAno === (int)$a ? 'selected' : '' ?>>
                            <?= (int)$a ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Premiação</label>
                <select name="premiacao_id" class="form-select form-select-sm">
                    <option value="">Todas as premiações</option>
                    <?php foreach ($premiacoes as $pr): ?>
                        <option value="<?= (int)$pr['id'] ?>" <?= $filtroPremiacao === (int)$pr['id'] ? 'selected' : '' ?>>
                            <?= h($pr['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Categoria</label>
                <select name="categoria" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($categoriasDisponiveis as $cat): ?>
                        <option value="<?= h($cat) ?>" <?= $filtroCategoria === $cat ? 'selected' : '' ?>>
                            <?= h($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php
                    $statusOpcoes = [
                        'rascunho'            => 'Rascunho',
                        'enviada'             => 'Enviada',
                        'em_triagem'          => 'Em Triagem',
                        'elegivel'            => 'Elegível',
                        'inelegivel'          => 'Inelegível',
                        'classificada_fase_1' => 'Class. Fase 1',
                        'classificada_fase_2' => 'Class. Fase 2',
                        'finalista'           => 'Finalista',
                        'vencedora'           => 'Vencedora',
                        'eliminada'           => 'Eliminada',
                    ];
                    foreach ($statusOpcoes as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $filtroStatus === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold mb-1" style="font-size:12px;">Buscar negócio</label>
                <input type="text" name="busca" class="form-control form-control-sm"
                    placeholder="Nome fantasia..." value="<?= h($filtroBusca) ?>">
            </div>

            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-success w-100">
                    <i class="bi bi-search"></i>
                </button>
                <a href="premiacao_inscricoes.php" class="btn btn-sm btn-outline-secondary w-100" title="Limpar filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <?php if (empty($inscricoes)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Nenhuma inscrição encontrada com os filtros aplicados.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table inscricoes-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Negócio</th>
                                <th>Empreendedor</th>
                                <th>Premiação</th>
                                <th>Categoria</th>
                                <th>Status</th>
                                <th>Inscrição</th>
                                <th class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscricoes as $insc): ?>
                                <tr>
                                    <td class="ps-3 text-muted" style="font-size:11px;"><?= (int)$insc['id'] ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= h($insc['nome_fantasia']) ?></div>
                                        <?php if ($insc['municipio'] || $insc['estado']): ?>
                                            <div class="text-muted" style="font-size:11px;">
                                                <i class="bi bi-geo-alt"></i>
                                                <?= h(trim(($insc['municipio'] ?? '') . ' / ' . ($insc['estado'] ?? ''), ' /')) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size:12px;"><?= h($insc['empreendedor_nome']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size:12px;font-weight:600;"><?= h($insc['premiacao_nome']) ?></div>
                                        <div class="text-muted" style="font-size:11px;">Ano <?= (int)$insc['premiacao_ano'] ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border" style="font-size:11px;">
                                            <?= h($insc['categoria']) ?>
                                        </span>
                                    </td>
                                    <td><?= badgeStatus($insc['status']) ?></td>
                                    <td style="font-size:11px;white-space:nowrap;">
                                        <?= dataBr($insc['created_at']) ?>
                                        <?php if ($insc['enviado_em']): ?>
                                            <div class="text-muted">Env: <?= dataBr($insc['enviado_em']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalStatus"
                                            data-id="<?= (int)$insc['id'] ?>"
                                            data-nome="<?= h($insc['nome_fantasia']) ?>"
                                            data-status="<?= h($insc['status']) ?>"
                                            data-obs="<?= h($insc['observacoes_admin'] ?? '') ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="/negocio.php?id=<?= (int)$insc['negocio_id'] ?>"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Ver negócio">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Modal: Alterar Status -->
<div class="modal fade" id="modalStatus" tabindex="-1" aria-labelledby="modalStatusLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="modalStatusLabel">
                    <i class="bi bi-pencil-square me-2"></i>Alterar Status da Inscrição
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="inscricao_id" id="modalInscricaoId">
                <div class="modal-body">
                    <p class="text-muted mb-3" id="modalNegocioNome" style="font-weight:600;font-size:15px;"></p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Novo Status</label>
                        <select name="novo_status" id="modalNovoStatus" class="form-select" required>
                            <?php foreach ($statusOpcoes as $val => $label): ?>
                                <option value="<?= $val ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-semibold">Observações do Admin <span class="text-muted fw-normal">(opcional)</span></label>
                        <textarea name="observacoes_admin" id="modalObs" class="form-control" rows="3"
                            placeholder="Justifique a decisão, se necessário..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar alteração</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Popula modal com dados da linha clicada
document.getElementById('modalStatus').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    document.getElementById('modalInscricaoId').value  = btn.dataset.id;
    document.getElementById('modalNegocioNome').textContent = btn.dataset.nome;
    document.getElementById('modalObs').value          = btn.dataset.obs;

    const sel = document.getElementById('modalNovoStatus');
    for (let opt of sel.options) {
        opt.selected = (opt.value === btn.dataset.status);
    }
});

// Submete filtro ao mudar o select de ano
document.querySelector('select[name="ano"]').addEventListener('change', function() {
    this.form.submit();
});
</script>

<?php require_once $appBase . '/views/admin/footer.php'; ?>