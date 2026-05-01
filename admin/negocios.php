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
    if (!empty($filtro_nome)) {
        $where[] = "n.nome_fantasia LIKE ?";
        $params[] = "%" . $filtro_nome . "%";
    }

    $filtro_empreendedor = $_GET['empreendedor'] ?? '';
    if (!empty($filtro_empreendedor)) {
        $where[] = "CONCAT(TRIM(e.nome), ' ', TRIM(e.sobrenome)) LIKE ?";
        $params[] = "%" . $filtro_empreendedor . "%";
    }

    $filtro_categoria = $_GET['categoria'] ?? '';
    if (!empty($filtro_categoria)) {
        $where[] = "n.categoria = ?";
        $params[] = $filtro_categoria;
    }

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

    // Premiação ativa
    $stmtPremAtiva = $pdo->query("
        SELECT id
        FROM premiacoes
        WHERE status IN ('ativa', 'planejada')
        ORDER BY
            CASE WHEN status = 'ativa' THEN 0 ELSE 1 END,
            ano DESC,
            id DESC
        LIMIT 1
    ");
    $premiacaoAtualId = (int) ($stmtPremAtiva->fetchColumn() ?: 0);

    if (is_juri_ou_tecnica() && $premiacaoAtualId > 0) {
        $where[] = "EXISTS (
            SELECT 1
            FROM premiacao_inscricoes pi
            WHERE pi.negocio_id = n.id
              AND pi.premiacao_id = ?
        )";
        $params[] = $premiacaoAtualId;
    }

    $sql = "SELECT n.id, n.nome_fantasia, n.categoria, n.etapa_atual, n.inscricao_completa,
                   n.status_operacional, n.status_vitrine, n.publicado_vitrine,
                   CONCAT(TRIM(e.nome), ' ', TRIM(e.sobrenome)) AS empreendedor,
                   s.score_impacto, s.score_investimento, s.score_escala, s.score_geral
            FROM negocios n
            JOIN empreendedores e ON n.empreendedor_id = e.id
            LEFT JOIN scores_negocios s ON n.id = s.negocio_id ";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $colunas_permitidas = [
        'created_at'    => 'n.created_at',
        'etapa'         => 'n.etapa_atual',
        'escala'        => 's.score_escala',
        'investimento'  => 's.score_investimento',
        'impacto'       => 's.score_impacto',
        'geral'         => 's.score_geral'
    ];
    $direcoes_permitidas = ['ASC', 'DESC'];
    $coluna_ordem  = $_GET['ordem'] ?? 'created_at';
    $direcao_ordem = $_GET['dir']   ?? 'DESC';
    $campo_sql = $colunas_permitidas[$coluna_ordem] ?? 'n.created_at';
    $dir_sql   = in_array(strtoupper($direcao_ordem), $direcoes_permitidas) ? strtoupper($direcao_ordem) : 'DESC';
    $sql .= " ORDER BY {$campo_sql} {$dir_sql}";

    $por_pagina   = 50;
    $pagina_atual = max(1, (int)($_GET['pagina'] ?? 1));
    $offset       = ($pagina_atual - 1) * $por_pagina;

    $sqlCount = "SELECT COUNT(*) FROM negocios n JOIN empreendedores e ON n.empreendedor_id = e.id LEFT JOIN scores_negocios s ON n.id = s.negocio_id";
    if (!empty($where)) {
        $sqlCount .= " WHERE " . implode(" AND ", $where);
    }

    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $total_registros = (int)$stmtCount->fetchColumn();
    $total_paginas   = (int)ceil($total_registros / $por_pagina);

    $sql .= " LIMIT {$por_pagina} OFFSET {$offset}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $negocios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Inscrições da premiação ativa: mapa negocio_id => inscricao_id ────
    $inscricoesPorNegocio = [];
    if ($premiacaoAtualId > 0 && is_juri_ou_tecnica()) {
        $negIds = array_column($negocios, 'id');
        if (!empty($negIds)) {
            $placeholders = implode(',', array_fill(0, count($negIds), '?'));
            $stmtInsc = $pdo->prepare("
                SELECT negocio_id, id AS inscricao_id
                FROM premiacao_inscricoes
                WHERE premiacao_id = ?
                  AND negocio_id IN ({$placeholders})
                  AND status = 'elegivel'
            ");
            $stmtInsc->execute(array_merge([$premiacaoAtualId], $negIds));
            foreach ($stmtInsc->fetchAll() as $row) {
                $inscricoesPorNegocio[(int)$row['negocio_id']] = (int)$row['inscricao_id'];
            }
        }
    }

    // ── Fase ativa por role ──────────────────────────────────────
    $faseAtiva = null;
    if ($premiacaoAtualId > 0 && is_juri_ou_tecnica()) {
        if (is_tecnica()) {
            $colFase = 'permite_avaliacao_tecnica';
        } else {
            $colFase = 'permite_juri_final';
        }
        $stmtFaseRole = $pdo->prepare("
            SELECT id FROM premiacao_fases
            WHERE premiacao_id = ?
              AND status = 'em_andamento'
              AND {$colFase} = 1
            ORDER BY rodada ASC LIMIT 1
        ");
        $stmtFaseRole->execute([$premiacaoAtualId]);
        $faseAtiva = $stmtFaseRole->fetchColumn() ?: null;
    }

    // ── Votos já dados pelo usuário logado nesta fase ────────────────
    $votosJaDados = [];
    if ($faseAtiva) {
        require_once __DIR__ . '/../app/helpers/premiacao_auth.php';
        $actor = premiacao_current_actor();
        if ($actor) {
            $currentUserId = (int)$actor['id'];
            $inscIds = array_values($inscricoesPorNegocio);

            if (!empty($inscIds)) {
                $phVotos     = implode(',', array_fill(0, count($inscIds), '?'));
                $tabelaVotos = is_tecnica()
                    ? 'premiacao_votos_tecnicos'
                    : 'premiacao_votos_juri';

                $stmtVotos = $pdo->prepare("
                    SELECT inscricao_id
                    FROM {$tabelaVotos}
                    WHERE fase_id      = ?
                      AND user_id      = ?
                      AND inscricao_id IN ({$phVotos})
                ");
                $stmtVotos->execute(array_merge(
                    [(int)$faseAtiva, $currentUserId],
                    $inscIds
                ));
                foreach ($stmtVotos->fetchAll(PDO::FETCH_COLUMN) as $vid) {
                    $votosJaDados[(int)$vid] = true;
                }
            }
        }
    }

    function linkOrdenacao($coluna) {
        $get = $_GET;
        $dir_atual = $get['dir'] ?? 'DESC';
        $col_atual = $get['ordem'] ?? 'created_at';
        $get['dir']   = ($col_atual === $coluna && $dir_atual === 'DESC') ? 'ASC' : 'DESC';
        $get['ordem'] = $coluna;
        return '?' . http_build_query($get);
    }

    function iconeOrdenacao($coluna) {
        $dir_atual = $_GET['dir']   ?? 'DESC';
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
    'dados_gerais' => 'Dados Gerais', 'contatos'  => 'Contatos',
    'endereco'     => 'Endereço',     'midias'    => 'Mídias',
    'pitch'        => 'Pitch',        'impacto'   => 'Impacto',
    'demografia'   => 'Demografia',   'finalizado' => 'Finalizado'
];

include __DIR__ . '/../app/views/admin/header.php';
?>

<?php if (!empty($_SESSION['sucesso'])): ?>
  <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    <?= htmlspecialchars($_SESSION['sucesso']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['sucesso']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['erro'])): ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?= htmlspecialchars($_SESSION['erro']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['erro']); ?>
<?php endif; ?>

<!-- Toast de feedback (voto técnico AJAX) -->
<div id="voto-toast-wrap" aria-live="polite" style="
  position:fixed; top:1.25rem; right:1.25rem; z-index:9999;
  display:flex; flex-direction:column; gap:.5rem; pointer-events:none;
"></div>

<!-- Cabeçalho -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0" style="color:#1E3425;">Negócios Cadastrados</h4>
    <small style="color:#6c8070; font-size:.82rem;">Acompanhe o andamento de todos os negócios inscritos na plataforma</small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if (can_see_admin_shortcuts()): ?>
    <a href="/admin/recalcular_scores.php" class="hd-btn outline">
      <i class="bi bi-arrow-repeat"></i> Recalcular Scores
    </a>
    <a href="/admin/enviar_email_negocios_pendentes.php" class="hd-btn outline">
      <i class="bi bi-envelope-exclamation"></i> Notificar Pendentes
    </a>
    <?php endif; ?>
    <a href="relatorios_negocios.php" class="hd-btn primary">
      <i class="bi bi-bar-chart-fill"></i> Ver Relatórios
    </a>
    <a href="/admin/dashboard.php" class="hd-btn outline">
      <i class="bi bi-arrow-left"></i> Voltar
    </a>
  </div>
</div>

<!-- Mini KPIs -->
<?php if (can_see_admin_shortcuts()): ?>
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
<?php endif; ?>

<!-- Filtros -->
<div class="filter-card card p-3 mb-4">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-sm-6 col-lg-3">
      <label class="form-label">Nome Fantasia</label>
      <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" name="nome" class="form-control"
               placeholder="Buscar negócio…" value="<?= htmlspecialchars($filtro_nome) ?>">
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <label class="form-label">Empreendedor</label>
      <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" name="empreendedor" class="form-control"
              placeholder="Buscar por nome…" value="<?= htmlspecialchars($filtro_empreendedor) ?>">
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-2">
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
        <option value="concluido"  <?= $filtro_status === 'concluido'  ? 'selected' : '' ?>>Concluído</option>
        <option value="andamento"  <?= $filtro_status === 'andamento'  ? 'selected' : '' ?>>Em Andamento</option>
        <option value="encerrado"  <?= $filtro_status === 'encerrado'  ? 'selected' : '' ?>>Encerrado</option>
        <option value="analise"    <?= $filtro_status === 'analise'    ? 'selected' : '' ?>>Em Análise</option>
        <option value="aprovado"   <?= $filtro_status === 'aprovado'   ? 'selected' : '' ?>>Aprovado</option>
        <option value="indeferido" <?= $filtro_status === 'indeferido' ? 'selected' : '' ?>>Indeferido</option>
      </select>
    </div>
    <div class="col-12 col-sm-6 col-lg-2 d-flex gap-2">
      <button type="submit" class="hd-btn primary w-100">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
      <a href="/admin/negocios.php" class="hd-btn outline">
        <i class="bi bi-x-lg"></i>
      </a>
    </div>
  </form>
</div>

<!-- Tabela -->
<div class="card section-card mb-4">
  <div class="table-responsive">
    <table class="emp-table neg-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome Fantasia</th>
          <th>Categoria</th>
          <th>Empreendedor</th>
          <th><a href="<?= linkOrdenacao('etapa') ?>" class="neg-sort-link">Etapa <?= iconeOrdenacao('etapa') ?></a></th>
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
            <?php
              $nid         = (int)$n['id'];
              $inscricaoId = $inscricoesPorNegocio[$nid] ?? null;
              $jaVotou     = $inscricaoId && isset($votosJaDados[$inscricaoId]);
              $redirectUrl = urlencode('/admin/negocios.php?' . http_build_query($_GET));
            ?>
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

              <!-- Ações -->
              <td class="text-center">
                <div class="d-flex gap-1 justify-content-center">

                  <!-- Ver detalhes -->
                  <a href="/admin/visualizar_negocio.php?id=<?= $nid ?>" class="act-btn edit" title="Ver detalhes">
                    <i class="bi bi-eye"></i>
                  </a>

                  <?php if (is_tecnica() && $inscricaoId && $faseAtiva): ?>
                    <?php if ($jaVotou): ?>
                      <button type="button" class="act-btn" title="Voto já registrado" disabled
                              style="background:rgba(108,117,125,.10);color:#adb5bd;cursor:not-allowed;opacity:.65;">
                        <i class="bi bi-check-circle-fill"></i>
                      </button>
                    <?php else: ?>
                      <!-- botão AJAX — data-inscricao / data-fase passados via atributos -->
                      <button type="button"
                              class="act-btn btn-votar-tecnico"
                              title="Votar como Técnico"
                              data-inscricao="<?= $inscricaoId ?>"
                              data-fase="<?= (int)$faseAtiva ?>"
                              style="background:rgba(25,135,84,.12);color:#198754;">
                        <i class="bi bi-clipboard2-check"></i>
                      </button>
                    <?php endif; ?>

                  <?php elseif (is_juri() && $inscricaoId && $faseAtiva): ?>
                    <?php if ($jaVotou): ?>
                      <button type="button" class="act-btn" title="Voto já registrado" disabled
                              style="background:rgba(108,117,125,.10);color:#adb5bd;cursor:not-allowed;opacity:.65;">
                        <i class="bi bi-award-fill"></i>
                      </button>
                    <?php else: ?>
                      <a href="/admin/premiacao_juri.php?inscricao_id=<?= $inscricaoId ?>&fase_id=<?= (int)$faseAtiva ?>&redirect=<?= $redirectUrl ?>"
                         class="act-btn"
                         title="Votar como Júri"
                         style="background:rgba(102,51,153,.12);color:#6633cc;">
                        <i class="bi bi-star-half"></i>
                      </a>
                    <?php endif; ?>
                  <?php endif; ?>

                  <?php if (can_see_admin_shortcuts()): ?>
                    <a href="/admin/recalcular_score.php?id=<?= $nid ?>" class="act-btn" title="Recalcular Score"
                      style="background:rgba(151,163,39,.12);color:#5c6318;">
                      <i class="bi bi-arrow-repeat"></i>
                    </a>

                    <button type="button" class="act-btn" title="Enviar Notificação"
                            style="background:rgba(149,188,204,.18);color:#3a6f82;"
                            onclick="abrirModalNotificacao(<?= $nid ?>, '<?= htmlspecialchars(addslashes((string)$n['nome_fantasia'])) ?>')">
                      <i class="bi bi-bell"></i>
                    </button>
                  <?php endif; ?>

                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($total_paginas > 1): ?>
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-3 mb-4">
    <div class="text-muted small">
        Exibindo <strong><?= number_format(min($offset + 1, $total_registros)) ?></strong>
        a <strong><?= number_format(min($offset + $por_pagina, $total_registros)) ?></strong>
        de <strong><?= number_format($total_registros) ?></strong> negócios
    </div>
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <?php
            $get_base = $_GET;
            unset($get_base['pagina']);
            $qs     = http_build_query($get_base);
            $qs_sep = $qs ? $qs . '&' : '';
            ?>
            <li class="page-item <?= $pagina_atual <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= $qs_sep ?>pagina=<?= $pagina_atual - 1 ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php
            $inicio = max(1, $pagina_atual - 3);
            $fim    = min($total_paginas, $pagina_atual + 3);
            if ($inicio > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= $qs_sep ?>pagina=1">1</a>
                </li>
                <?php if ($inicio > 2): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
            <?php endif; ?>
            <?php for ($p = $inicio; $p <= $fim; $p++): ?>
                <li class="page-item <?= $p === $pagina_atual ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= $qs_sep ?>pagina=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($fim < $total_paginas): ?>
                <?php if ($fim < $total_paginas - 1): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= $qs_sep ?>pagina=<?= $total_paginas ?>"><?= $total_paginas ?></a>
                </li>
            <?php endif; ?>
            <li class="page-item <?= $pagina_atual >= $total_paginas ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= $qs_sep ?>pagina=<?= $pagina_atual + 1 ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
  </div>
<?php endif; ?>

<!-- MODAL NOTIFICAÇÃO INDIVIDUAL -->
<div class="modal fade" id="modalNotificacao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px; border:none;">
      <form action="/admin/processar_notificacao_negocio.php" method="POST">
        <input type="hidden" name="negocio_id" id="notif_negocio_id">
        <div class="modal-header" style="border-bottom:1px solid #f0f4ed;">
          <h5 class="modal-title" style="color:#1E3425;">
            <i class="bi bi-bell me-2" style="color:#CDDE00;"></i>
            Notificar Empreendedor
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="p-3 rounded mb-3" style="background:#f7f9f5; border:1px solid #e6ece1;">
            <div class="small fw-semibold mb-1" style="color:#1E3425;">
              <i class="bi bi-briefcase me-1"></i> Negócio
            </div>
            <div class="fw-bold" id="notif_nome_negocio" style="color:#1E3425;"></div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.88rem;">Mensagem adicional
              <span class="text-muted fw-normal">(opcional)</span>
            </label>
            <textarea name="mensagem_extra" class="form-control" rows="3" maxlength="500"
                      placeholder="Ex: Notamos que seu cadastro está na Etapa 5. Podemos ajudar?"></textarea>
            <div class="form-text">Será inserida no corpo do e-mail padrão.</div>
          </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid #f0f4ed;">
          <button type="button" class="btn-emp-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="hd-btn primary">
            <i class="bi bi-send me-1"></i> Enviar notificação
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
/* Toast de voto */
.voto-toast {
  pointer-events:auto;
  min-width:280px; max-width:380px;
  padding:.75rem 1rem;
  border-radius:10px;
  font-size:.875rem;
  font-weight:500;
  line-height:1.4;
  display:flex;
  align-items:flex-start;
  gap:.6rem;
  box-shadow:0 4px 18px rgba(0,0,0,.13);
  animation: toastIn .22s cubic-bezier(.16,1,.3,1) both;
}
.voto-toast.saindo {
  animation: toastOut .2s ease-in forwards;
}
.voto-toast i { flex-shrink:0; font-size:1rem; margin-top:.05rem; }
.voto-toast.ok  { background:#f0faf3; color:#1a6b38; border:1px solid #b6e0c4; }
.voto-toast.err { background:#fff4f4; color:#9b1c2a; border:1px solid #f5c0c5; }
@keyframes toastIn  { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:none} }
@keyframes toastOut { from{opacity:1;transform:none} to{opacity:0;transform:translateY(-6px)} }
</style>

<script>
function abrirModalNotificacao(id, nome) {
    document.getElementById('notif_negocio_id').value = id;
    document.getElementById('notif_nome_negocio').textContent = nome;
    new bootstrap.Modal(document.getElementById('modalNotificacao')).show();
}

// ── Toast helper ─────────────────────────────────────────
function mostrarToastVoto(tipo, msg) {
    const wrap = document.getElementById('voto-toast-wrap');
    const t = document.createElement('div');
    t.className = 'voto-toast ' + tipo;
    const icone = tipo === 'ok'
        ? '<i class="bi bi-check-circle-fill"></i>'
        : '<i class="bi bi-exclamation-circle-fill"></i>';
    t.innerHTML = icone + '<span>' + msg + '</span>';
    wrap.appendChild(t);
    const dur = tipo === 'err' ? 7000 : 4000;   // erros ficam mais tempo
    setTimeout(() => {
        t.classList.add('saindo');
        t.addEventListener('animationend', () => t.remove(), { once: true });
    }, dur);
}

// ── Voto técnico via AJAX (delegation) ──────────────────────
document.addEventListener('click', async function(e) {
    const btn = e.target.closest('.btn-votar-tecnico');
    if (!btn) return;

    e.preventDefault();
    btn.disabled = true;
    btn.style.opacity = '.55';
    btn.querySelector('i').className = 'bi bi-hourglass-split';

    const inscricaoId = btn.dataset.inscricao;
    const faseId      = btn.dataset.fase;

    const body = new URLSearchParams();
    body.append('inscricao_id', inscricaoId);
    body.append('fase_id',      faseId);

    try {
        const res  = await fetch('/premiacao/votar_tecnico.php', {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body:    body,
        });
        const data = await res.json();

        if (data.ok) {
            // Sucesso: transforma o botão no estado "já votou"
            btn.classList.remove('btn-votar-tecnico');
            btn.removeAttribute('data-inscricao');
            btn.removeAttribute('data-fase');
            btn.title = 'Voto já registrado';
            btn.style.cssText = 'background:rgba(108,117,125,.10);color:#adb5bd;cursor:not-allowed;opacity:.65;';
            btn.querySelector('i').className = 'bi bi-check-circle-fill';
            mostrarToastVoto('ok', 'Voto registrado com sucesso!');
        } else {
            // Erro do servidor (limite, duplicado, fase encerrada etc.)
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.querySelector('i').className = 'bi bi-clipboard2-check';
            mostrarToastVoto('err', data.erro || 'Não foi possível registrar o voto.');
        }
    } catch {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.querySelector('i').className = 'bi bi-clipboard2-check';
        mostrarToastVoto('err', 'Erro de conexão. Tente novamente.');
    }
});
</script>
<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>
