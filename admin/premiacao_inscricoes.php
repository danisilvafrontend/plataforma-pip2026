<?php
declare(strict_types=1);


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
$mensagem = '';
$erro = '';

function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $dt): string
{
    if (empty($dt) || str_starts_with((string)$dt, '0000')) {
        return '—';
    }
    return date('d/m/Y H:i', strtotime($dt));
}

function normalizarStatusPremiacao(?string $status): string
{
    $status = trim((string)$status);

    return match ($status) {
        'em_triagem' => 'emtriagem',
        'classificada_fase_1' => 'classificadafase1',
        'classificada_fase_2' => 'classificadafase2',
        default => $status,
    };
}

function labelStatus(string $status): string
{
    $status = normalizarStatusPremiacao($status);

    return match ($status) {
        'rascunho' => 'Rascunho',
        'enviada' => 'Enviada',
        'emtriagem' => 'Em triagem',
        'elegivel' => 'Elegível',
        'inelegivel' => 'Inelegível',
        'classificadafase1' => 'Class. Fase 1',
        'classificadafase2' => 'Class. Fase 2',
        'finalista' => 'Finalista',
        'vencedora' => 'Vencedora',
        'eliminada' => 'Eliminada',
        default => 'Não informado',
    };
}

function badgeStatus(string $status): string
{
    $status = normalizarStatusPremiacao($status);

    $map = [
        'rascunho' => ['background:rgba(108,128,112,.14);color:#4a5e4f;', 'Rascunho'],
        'enviada' => ['background:rgba(149,188,204,.20);color:#3a6f82;', 'Enviada'],
        'emtriagem' => ['background:rgba(255,243,205,1);color:#856404;', 'Em triagem'],
        'elegivel' => ['background:rgba(26,138,74,.14);color:#1a8a4a;', 'Elegível'],
        'inelegivel' => ['background:rgba(220,53,69,.12);color:#b02a37;', 'Inelegível'],
        'classificadafase1' => ['background:rgba(205,222,0,.24);color:#5f6b00;', 'Class. Fase 1'],
        'classificadafase2' => ['background:rgba(151,163,39,.18);color:#55610e;', 'Class. Fase 2'],
        'finalista' => ['background:rgba(255,214,165,1);color:#7d3c00;', 'Finalista'],
        'vencedora' => ['background:rgba(255,224,102,1);color:#5a3e00;', 'Vencedora'],
        'eliminada' => ['background:rgba(245,198,203,1);color:#721c24;', 'Eliminada'],
    ];

    [$style, $label] = $map[$status] ?? ['background:rgba(108,128,112,.14);color:#4a5e4f;', ucfirst($status)];

    return '<span class="emp-badge" style="' . h($style) . '">' . h($label) . '</span>';
}

function statusValidosAdmin(): array
{
    return [
        'rascunho',
        'enviada',
        'emtriagem',
        'elegivel',
        'inelegivel',
        'classificadafase1',
        'classificadafase2',
        'finalista',
        'vencedora',
        'eliminada',
    ];
}

if (isset($_GET['ok'])) {
    $mensagem = trim((string)$_GET['ok']);
}
if (isset($_GET['err'])) {
    $erro = trim((string)$_GET['err']);
}

$filtroAno = (int)($_GET['ano'] ?? 0);
$filtroPremiacao = (int)($_GET['premiacao_id'] ?? 0);
$filtroStatus = normalizarStatusPremiacao($_GET['status'] ?? '');
$filtroBusca = trim((string)($_GET['busca'] ?? ''));
$filtroCategoria = trim((string)($_GET['categoria'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    try {
        $inscricaoId = (int)($_POST['inscricao_id'] ?? 0);
        $novoStatus = normalizarStatusPremiacao($_POST['novo_status'] ?? '');
        $obs = trim((string)($_POST['observacoes_admin'] ?? ''));

        if ($inscricaoId <= 0 || !in_array($novoStatus, statusValidosAdmin(), true)) {
            throw new Exception('Dados inválidos para atualização.');
        }

        $stmtUpd = $pdo->prepare("
            UPDATE premiacao_inscricoes
            SET status = ?, observacoes_admin = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmtUpd->execute([
            $novoStatus,
            $obs !== '' ? $obs : null,
            $inscricaoId
        ]);

        header('Location: premiacao_inscricoes.php?' . http_build_query(array_filter([
            'ano' => $filtroAno ?: null,
            'premiacao_id' => $filtroPremiacao ?: null,
            'status' => $filtroStatus ?: null,
            'busca' => $filtroBusca ?: null,
            'categoria' => $filtroCategoria ?: null,
            'ok' => 'Status atualizado com sucesso.',
        ])));
        exit;
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}

$anos = $pdo->query("SELECT DISTINCT ano FROM premiacoes ORDER BY ano DESC")->fetchAll(PDO::FETCH_COLUMN);

$stmtPrem = $filtroAno > 0
    ? $pdo->prepare("SELECT id, nome, ano FROM premiacoes WHERE ano = ? ORDER BY id DESC")
    : $pdo->prepare("SELECT id, nome, ano FROM premiacoes ORDER BY ano DESC, id DESC");

if ($filtroAno > 0) {
    $stmtPrem->execute([$filtroAno]);
} else {
    $stmtPrem->execute();
}
$premiacoes = $stmtPrem->fetchAll();

$categoriasDisponiveis = $pdo->query("
    SELECT DISTINCT categoria
    FROM premiacao_inscricoes
    WHERE categoria IS NOT NULL AND categoria <> ''
    ORDER BY categoria
")->fetchAll(PDO::FETCH_COLUMN);

$totalNegocios = (int)$pdo->query("
    SELECT COUNT(*)
    FROM negocios
    WHERE publicado_vitrine = 1
")->fetchColumn();

$whereKpi = [];
$paramsKpi = [];

if ($filtroAno > 0) {
    $whereKpi[] = 'p.ano = ?';
    $paramsKpi[] = $filtroAno;
}
if ($filtroPremiacao > 0) {
    $whereKpi[] = 'pi.premiacao_id = ?';
    $paramsKpi[] = $filtroPremiacao;
}

$whereKpiSql = $whereKpi ? 'WHERE ' . implode(' AND ', $whereKpi) : '';

$sqlKpi = "
    SELECT
        COUNT(*) AS total_inscritos,
        SUM(CASE WHEN pi.status = 'elegivel' THEN 1 ELSE 0 END) AS total_elegiveis,
        SUM(CASE WHEN pi.status IN ('classificadafase1','classificadafase2','finalista','vencedora') THEN 1 ELSE 0 END) AS total_classificados,
        SUM(CASE WHEN pi.status = 'vencedora' THEN 1 ELSE 0 END) AS total_vencedores
    FROM premiacao_inscricoes pi
    INNER JOIN premiacoes p ON p.id = pi.premiacao_id
    $whereKpiSql
";
$stmtKpi = $pdo->prepare($sqlKpi);
$stmtKpi->execute($paramsKpi);
$kpi = $stmtKpi->fetch() ?: [];

$totalInscritos = (int)($kpi['total_inscritos'] ?? 0);
$totalElegiveis = (int)($kpi['total_elegiveis'] ?? 0);
$totalClassificados = (int)($kpi['total_classificados'] ?? 0);
$totalVencedores = (int)($kpi['total_vencedores'] ?? 0);

$pctInscritos = $totalNegocios > 0 ? round(($totalInscritos / $totalNegocios) * 100, 1) : 0;
$pctElegiveis = $totalInscritos > 0 ? round(($totalElegiveis / $totalInscritos) * 100, 1) : 0;

$where = ['1=1'];
$params = [];

if ($filtroAno > 0) {
    $where[] = 'p.ano = ?';
    $params[] = $filtroAno;
}
if ($filtroPremiacao > 0) {
    $where[] = 'pi.premiacao_id = ?';
    $params[] = $filtroPremiacao;
}
if ($filtroStatus !== '') {
    $where[] = 'pi.status = ?';
    $params[] = $filtroStatus;
}
if ($filtroCategoria !== '') {
    $where[] = 'pi.categoria = ?';
    $params[] = $filtroCategoria;
}
if ($filtroBusca !== '') {
    $where[] = '(n.nome_fantasia LIKE ? OR CONCAT(e.nome, " ", e.sobrenome) LIKE ?)';
    $params[] = '%' . $filtroBusca . '%';
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
        p.nome AS premiacao_nome,
        p.ano AS premiacao_ano,
        CONCAT(e.nome, ' ', e.sobrenome) AS empreendedor_nome
    FROM premiacao_inscricoes pi
    INNER JOIN premiacoes p ON p.id = pi.premiacao_id
    INNER JOIN negocios n ON n.id = pi.negocio_id
    INNER JOIN empreendedores e ON e.id = pi.empreendedor_id
    WHERE $whereSql
    ORDER BY p.ano DESC, pi.created_at DESC, pi.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inscricoes = $stmt->fetchAll();

require_once $appBase . '/views/admin/header.php';
?>



<div class="container-fluid py-4">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h1 class="h3 mb-1">Inscrições na premiação</h1>
      <p class="text-muted mb-0">Gerencie os negócios inscritos e atualize seus status na premiação.</p>
    </div>
  </div>

  <?php if ($mensagem !== ''): ?>
    <div class="alert alert-success"><?= h($mensagem) ?></div>
  <?php endif; ?>

  <?php if ($erro !== ''): ?>
    <div class="alert alert-danger"><?= h($erro) ?></div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
  <div class="col-12 col-md-6 col-xl-3">
    <div class="kpi-card p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="kpi-icon primary"><i class="bi bi-shop"></i></div>
        <div>
          <div class="kpi-label">Negócios publicados</div>
          <div class="kpi-value"><?= (int)$totalNegocios ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-xl-3">
    <div class="kpi-card p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="kpi-icon secondary"><i class="bi bi-journal-check"></i></div>
        <div>
          <div class="kpi-label">Inscrições</div>
          <div class="kpi-value"><?= (int)$totalInscritos ?></div>
          <div class="text-muted small mt-1"><?= h((string)$pctInscritos) ?>% da base publicada</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-xl-3">
    <div class="kpi-card p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="kpi-icon success"><i class="bi bi-patch-check"></i></div>
        <div>
          <div class="kpi-label">Elegíveis</div>
          <div class="kpi-value"><?= (int)$totalElegiveis ?></div>
          <div class="text-muted small mt-1"><?= h((string)$pctElegiveis) ?>% das inscrições</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-xl-3">
    <div class="kpi-card p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="kpi-icon info"><i class="bi bi-trophy"></i></div>
        <div>
          <div class="kpi-label">Classificados</div>
          <div class="kpi-value"><?= (int)$totalClassificados ?></div>
          <div class="text-muted small mt-1"><?= (int)$totalVencedores ?> vencedores</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="filter-card card mb-4">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-12 col-md-2">
        <label class="form-label">Ano</label>
        <select name="ano" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($anos as $ano): ?>
            <option value="<?= (int)$ano ?>" <?= $filtroAno === (int)$ano ? 'selected' : '' ?>>
              <?= (int)$ano ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Premiação</label>
        <select name="premiacao_id" class="form-select">
          <option value="">Todas</option>
          <?php foreach ($premiacoes as $prem): ?>
            <option value="<?= (int)$prem['id'] ?>" <?= $filtroPremiacao === (int)$prem['id'] ? 'selected' : '' ?>>
              <?= h($prem['nome']) ?><?= !empty($prem['ano']) ? ' (' . (int)$prem['ano'] . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-2">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="">Todos</option>
          <?php foreach (statusValidosAdmin() as $statusOpt): ?>
            <option value="<?= h($statusOpt) ?>" <?= $filtroStatus === $statusOpt ? 'selected' : '' ?>>
              <?= h(labelStatus($statusOpt)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-2">
        <label class="form-label">Categoria</label>
        <select name="categoria" class="form-select">
          <option value="">Todas</option>
          <?php foreach ($categoriasDisponiveis as $categoria): ?>
            <option value="<?= h($categoria) ?>" <?= $filtroCategoria === $categoria ? 'selected' : '' ?>>
              <?= h($categoria) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Busca</label>
        <input
          type="text"
          name="busca"
          class="form-control"
          value="<?= h($filtroBusca) ?>"
          placeholder="Negócio ou empreendedor">
      </div>

      <div class="col-12 d-flex flex-wrap gap-2">
        <button type="submit" class="hd-btn primary">
          <i class="bi bi-funnel"></i> Filtrar
        </button>
        <a href="premiacao_inscricoes.php" class="hd-btn outline">
          Limpar
        </a>
      </div>
    </form>
  </div>
</div>

  <div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="emp-table premiacao-inscricoes-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Negócio</th>
            <th>Empreendedor</th>
            <th>Premiação</th>
            <th>Categoria</th>
            <th>Status</th>
            <th>Inscrição</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($inscricoes)): ?>
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                Nenhuma inscrição encontrada com os filtros selecionados.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($inscricoes as $insc): ?>
              <tr>
                <td><?= (int)$insc['id'] ?></td>

                <td>
                  <div class="fw-semibold"><?= h($insc['nome_fantasia']) ?></div>
                  <div class="small text-muted">
                    <?= h(trim((string)(($insc['municipio'] ?? '') . ' / ' . ($insc['estado'] ?? '')), ' /')) ?: 'Local não informado' ?>
                  </div>
                </td>

                <td>
                  <div><?= h($insc['empreendedor_nome']) ?></div>
                </td>

                <td>
                  <div><?= h($insc['premiacao_nome']) ?></div>
                  <div class="small text-muted">Ano <?= (int)$insc['premiacao_ano'] ?></div>
                </td>

                <td><?= h($insc['categoria']) ?></td>

                <td><?= badgeStatus((string)$insc['status']) ?></td>

                <td>
                  <div class="small">Criada: <?= h(dataBr($insc['created_at'])) ?></div>
                  <div class="small text-muted">Enviada: <?= h(dataBr($insc['enviado_em'])) ?></div>
                  <div class="small text-muted">Atualizada: <?= h(dataBr($insc['updated_at'])) ?></div>
                </td>

                <td style="min-width: 320px;">
                  <form method="post" class="d-grid gap-2">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="inscricao_id" value="<?= (int)$insc['id'] ?>">

                    <select name="novo_status" class="form-select form-select-sm">
                      <?php foreach (statusValidosAdmin() as $statusOpt): ?>
                        <option value="<?= h($statusOpt) ?>" <?= normalizarStatusPremiacao((string)$insc['status']) === $statusOpt ? 'selected' : '' ?>>
                          <?= h(labelStatus($statusOpt)) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>

                    <textarea
                      name="observacoes_admin"
                      class="form-control form-control-sm"
                      rows="3"
                      placeholder="Observações da equipe"><?= h($insc['observacoes_admin']) ?></textarea>

                    <button type="submit" class="hd-btn primary justify-content-center">
                      Salvar status
                    </button>

                    <div class="small text-muted">
                      Regulamento: <?= (int)$insc['aceite_regulamento'] === 1 ? 'aceito' : 'não aceito' ?> ·
                      Veracidade: <?= (int)$insc['aceite_veracidade'] === 1 ? 'confirmada' : 'não confirmada' ?> ·
                      Participação: <?= (int)$insc['deseja_participar'] === 1 ? 'confirmada' : 'rascunho' ?>
                    </div>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once $appBase . '/views/admin/footer.php'; ?>