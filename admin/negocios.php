<?php
// /public_html/admin/negocios.php
declare(strict_types=1);
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

$config = require __DIR__ . '/../app/config/db.php';
$dsn    = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}";
$opts   = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $opts);

    $where  = [];
    $params = [];

    $filtro_nome = $_GET['nome'] ?? '';
    if (!empty($filtro_nome)) { $where[] = "n.nome_fantasia LIKE ?"; $params[] = "%" . $filtro_nome . "%"; }

    $filtro_categoria = $_GET['categoria'] ?? '';
    if (!empty($filtro_categoria)) { $where[] = "n.categoria = ?"; $params[] = $filtro_categoria; }

    $filtro_status = $_GET['status'] ?? '';
    if ($filtro_status === 'encerrado') {
        $where[] = "n.status_operacional = 'encerrado'";
    } elseif ($filtro_status === 'concluido') {
        $where[] = "n.inscricao_completa = 1 AND (n.status_operacional != 'encerrado' OR n.status_operacional IS NULL)";
    } elseif ($filtro_status === 'andamento') {
        $where[] = "(n.inscricao_completa IS NULL OR n.inscricao_completa = 0) AND (n.status_operacional != 'encerrado' OR n.status_operacional IS NULL)";
    } elseif ($filtro_status === 'analise') {
        $where[] = "n.status_vitrine = 'em_analise'";
    } elseif ($filtro_status === 'aprovado') {
    $where[] = "n.status_vitrine = 'aprovado'";
    } elseif ($filtro_status === 'indeferido') {
        $where[] = "n.status_vitrine = 'indeferido'";
    }

    $sql = "SELECT n.id, n.nome_fantasia, n.categoria, n.etapa_atual, n.inscricao_completa,
                   n.status_operacional, n.status_vitrine, n.publicado_vitrine,
                   CONCAT(TRIM(e.nome), ' ', TRIM(e.sobrenome)) AS empreendedor,
                   s.score_impacto, s.score_investimento, s.score_escala, s.score_geral
            FROM negocios n
            JOIN empreendedores e ON n.empreendedor_id = e.id
            LEFT JOIN scores_negocios s ON n.id = s.negocio_id ";

    if (!empty($where)) { $sql .= " WHERE " . implode(" AND ", $where); }

    $colunas_permitidas  = ['created_at' => 'n.created_at', 'escala' => 's.score_escala', 'investimento' => 's.score_investimento', 'impacto' => 's.score_impacto', 'geral' => 's.score_geral'];
    $direcoes_permitidas = ['ASC', 'DESC'];
    $coluna_ordem  = $_GET['ordem'] ?? 'created_at';
    $direcao_ordem = $_GET['dir']   ?? 'DESC';
    $campo_sql = $colunas_permitidas[$coluna_ordem] ?? 'n.created_at';
    $dir_sql   = in_array(strtoupper($direcao_ordem), $direcoes_permitidas) ? strtoupper($direcao_ordem) : 'DESC';
    $sql .= " ORDER BY {$campo_sql} {$dir_sql}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $negocios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    function linkOrdenacao($coluna) {
        $get = $_GET;
        $dir_atual = $get['dir'] ?? 'DESC';
        $col_atual = $get['ordem'] ?? 'created_at';
        $get['dir']   = ($col_atual === $coluna && $dir_atual === 'DESC') ? 'ASC' : 'DESC';
        $get['ordem'] = $coluna;
        return '?' . http_build_query($get);
    }

    function iconeOrdenacao($coluna) {
        $dir_atual = $_GET['dir'] ?? 'DESC';
        $col_atual = $_GET['ordem'] ?? 'created_at';
        if ($col_atual === $coluna) {
            return $dir_atual === 'ASC' ? '▲' : '▼';
        }
        return '';
    }

    $stmtCat = $pdo->query("SELECT DISTINCT categoria FROM negocios WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");
    $categorias_disponiveis = $stmtCat->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}

$sqlTotais = "SELECT COUNT(*) as total,
    SUM(CASE WHEN status_operacional = 'encerrado' THEN 1 ELSE 0 END) as encerrados,
    SUM(CASE WHEN inscricao_completa = 1 AND (status_operacional != 'encerrado' OR status_operacional IS NULL) THEN 1 ELSE 0 END) as concluidos,
    SUM(CASE WHEN (inscricao_completa = 0 OR inscricao_completa IS NULL) AND (status_operacional != 'encerrado' OR status_operacional IS NULL) THEN 1 ELSE 0 END) as andamento
    FROM negocios";
$stmtTotais      = $pdo->query($sqlTotais);
$totais          = $stmtTotais->fetch(PDO::FETCH_ASSOC);
$totalGeral      = $totais['total']      ?? 0;
$totalEncerrados = $totais['encerrados'] ?? 0;
$totalConcluidos = $totais['concluidos'] ?? 0;
$totalAndamento  = $totais['andamento']  ?? 0;

$etapas = [
    'dados_gerais' => 'Dados Gerais', 'contatos'   => 'Contatos',
    'endereco'     => 'Endereço',     'midias'      => 'Mídias',
    'pitch'        => 'Pitch',        'impacto'     => 'Impacto',
    'demografia'   => 'Demografia',   'finalizado'  => 'Finalizado'
];

include __DIR__ . '/../app/views/admin/header.php';
?>

<!-- ══════════════════════════════════
     Cabeçalho
═══════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0" style="color:#1E3425;">Negócios Cadastrados</h4>
    <small style="color:#6c8070; font-size:.82rem;">Acompanhe o andamento de todos os negócios inscritos na plataforma</small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="/admin/recalcular_scores.php" class="hd-btn outline">
      <i class="bi bi-arrow-repeat"></i> Recalcular Scores
    </a>
    <a href="/admin/enviar_email_negocios_pendentes.php" class="hd-btn outline">
      <i class="bi bi-envelope-exclamation"></i> Notificar Pendentes
    </a>
    <a href="relatorios_negocios.php" class="hd-btn primary">
      <i class="bi bi-bar-chart-fill"></i> Ver Relatórios
    </a>
    <a href="/admin/dashboard.php" class="hd-btn outline">
      <i class="bi bi-arrow-left"></i> Voltar
    </a>
  </div>
</div>

<!-- ══════════════════════════════════
     Mini KPIs
═══════════════════════════════════ -->
<!-- Mini KPIs -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="neg-kpi-card">
      <div class="neg-kpi-label">Total</div>
      <div class="neg-kpi-value"><?= $totalGeral ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="neg-kpi-card">
      <div class="neg-kpi-label">Concluídos</div>
      <div class="neg-kpi-value" style="color:#7a8500;"><?= $totalConcluidos ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="neg-kpi-card">
      <div class="neg-kpi-label">Em Andamento</div>
      <div class="neg-kpi-value" style="color:#97A327;"><?= $totalAndamento ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="neg-kpi-card">
      <div class="neg-kpi-label">Encerrados</div>
      <div class="neg-kpi-value" style="color:#842029;"><?= $totalEncerrados ?></div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════
     Filtros
═══════════════════════════════════ -->
<div class="filter-card card p-3 mb-4">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-sm-6 col-lg-4">
      <label class="form-label">Nome Fantasia</label>
      <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" name="nome" class="form-control"
               placeholder="Buscar negócio…" value="<?= htmlspecialchars($filtro_nome) ?>">
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <label class="form-label">Categoria</label>
      <select name="categoria" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($categorias_disponiveis as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= $filtro_categoria === $cat ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-sm-6 col-lg-2">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="">Todos</option>
        <option value="concluido" <?= $filtro_status === 'concluido' ? 'selected' : '' ?>>Concluído</option>
        <option value="andamento" <?= $filtro_status === 'andamento' ? 'selected' : '' ?>>Em Andamento</option>
        <option value="encerrado" <?= $filtro_status === 'encerrado' ? 'selected' : '' ?>>Encerrado</option>
        <option value="analise"   <?= $filtro_status === 'analise'   ? 'selected' : '' ?>>Em Análise</option>
        <option value="aprovado"  <?= $filtro_status === 'aprovado'  ? 'selected' : '' ?>>Aprovado</option>
        <option value="indeferido" <?= $filtro_status === 'indeferido' ? 'selected' : '' ?>>Indeferido</option>
      </select>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 d-flex gap-2">
      <button type="submit" class="hd-btn primary w-100">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
      <a href="/admin/negocios.php" class="hd-btn outline">
        <i class="bi bi-x-lg"></i>
      </a>
    </div>
  </form>
</div>

<!-- ══════════════════════════════════
     Tabela
═══════════════════════════════════ -->
<div class="card section-card mb-4">
  <div class="table-responsive">
    <table class="emp-table neg-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome Fantasia</th>
          <th>Categoria</th>
          <th>Empreendedor</th>
          <th>Etapa Atual</th>
          <th class="text-center"><a href="<?= linkOrdenacao('escala') ?>" class="neg-sort-link">Escala <?= iconeOrdenacao('escala') ?></a></th>
          <th class="text-center"><a href="<?= linkOrdenacao('investimento') ?>" class="neg-sort-link">Invest. <?= iconeOrdenacao('investimento') ?></a></th>
          <th class="text-center"><a href="<?= linkOrdenacao('impacto') ?>" class="neg-sort-link">Impacto <?= iconeOrdenacao('impacto') ?></a></th>
          <th class="text-center"><a href="<?= linkOrdenacao('geral') ?>" class="neg-sort-link">Geral <?= iconeOrdenacao('geral') ?></a></th>
          <th>Status</th>
          <th class="text-center">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($negocios)): ?>
          <tr>
            <td colspan="11" class="text-center py-4" style="color:#9aab9d;">
              <i class="bi bi-briefcase" style="font-size:1.8rem; opacity:.4; display:block; margin-bottom:.5rem;"></i>
              Nenhum negócio encontrado com os filtros selecionados.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($negocios as $n): ?>
            <tr>
              <td style="color:#9aab9d; font-size:.78rem; font-family:monospace;">
                #<?= htmlspecialchars((string)$n['id']) ?>
              </td>
              <td style="font-weight:600; color:#1E3425; font-size:.88rem;">
                <?= htmlspecialchars($n['nome_fantasia']) ?>
              </td>
              <td>
                <span class="neg-cat-badge"><?= htmlspecialchars($n['categoria'] ?: '—') ?></span>
              </td>
              <td style="font-size:.85rem; color:#4a5e4f;">
                <?= htmlspecialchars($n['empreendedor']) ?>
              </td>
              <td>
                <?php if ($n['inscricao_completa']): ?>
                  <span class="emp-badge" style="background:rgba(205,222,0,.2);color:#7a8500;">
                    <i class="bi bi-check-circle-fill me-1"></i>Todas as etapas concluídas
                  </span>
                <?php else: ?>
                  <span style="font-size:.8rem; color:#6c8070;">
                    Etapa: <?= isset($etapas[$n['etapa_atual']]) ? $etapas[$n['etapa_atual']] : ($n['etapa_atual'] ?: 'Início') ?>
                  </span>
                <?php endif; ?>
              </td>
              <td class="text-center"><?= $n['score_escala']      ?? '-' ?></td>
              <td class="text-center"><?= $n['score_investimento'] ?? '-' ?></td>
              <td class="text-center"><?= $n['score_impacto']      ?? '-' ?></td>
              <td class="text-center"><?= $n['score_geral']        ?? '-' ?></td>
              <td>
                <?php if ($n['status_operacional'] === 'encerrado'): ?>
                  <span class="emp-badge" style="background:#fde8ea;color:#842029;">Encerrado</span>

                <?php elseif ($n['publicado_vitrine']): ?>
                  <span class="emp-badge" style="background:#CDDE00;color:#1E3425;">
                    <i class="bi bi-eye-fill me-1"></i>Aprovado e Publicado
                  </span>

                <?php elseif ($n['status_vitrine'] === 'em_analise'): ?>
                  <span class="emp-badge" style="background:rgba(149,188,204,.25);color:#3a6f82;">
                    <i class="bi bi-hourglass-split me-1"></i>Aguardando Aprovação
                  </span>

                <?php elseif ($n['status_vitrine'] === 'indeferido'): ?>
                  <span class="emp-badge" style="background:#fde8ea;color:#842029;">
                    <i class="bi bi-x-circle me-1"></i>Indeferido — Aguardando Revisão
                  </span>

                <?php elseif ($n['inscricao_completa']): ?>
                  <span class="emp-badge" style="background:rgba(151,163,39,.15);color:#5c6318;">Preenchimento Concluído</span>

                <?php else: ?>
                  <span class="emp-badge" style="background:#fff3cd;color:#856404;">Em andamento</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <div class="d-flex gap-1 justify-content-center">
                  <a href="/admin/visualizar_negocio.php?id=<?= $n['id'] ?>" class="act-btn edit" title="Ver detalhes">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="/admin/recalcular_score.php?id=<?= $n['id'] ?>" class="act-btn" title="Recalcular Score"
                     style="background:rgba(151,163,39,.12);color:#5c6318;">
                    <i class="bi bi-arrow-repeat"></i>
                  </a>
                  <button type="button" class="act-btn" title="Enviar Notificação"
                          style="background:rgba(149,188,204,.18);color:#3a6f82;"
                          onclick="abrirModalNotificacao(<?= (int)$n['id'] ?>, '<?= htmlspecialchars(addslashes((string)$n['nome_fantasia'])) ?>')">
                    <i class="bi bi-bell"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>