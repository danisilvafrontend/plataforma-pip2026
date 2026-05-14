<?php
declare(strict_types=1);
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../app/helpers/auth.php';

require_admin_login(['juri', 'tecnica']);
$role   = $_SESSION['user_role'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);

$config = require __DIR__ . '/../app/config/db.php';
$dsn    = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}";
$opts   = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $opts);

    // ── Fase ativa para este role ─────────────────────────────────────────
    $campoPerm = $role === 'juri' ? 'permite_juri_final' : 'permite_voto_tecnico';
    $stmtFase  = $pdo->prepare("
        SELECT pf.*
        FROM premiacao_fases pf
        WHERE pf.{$campoPerm} = 1
          AND pf.status IN ('em_andamento', 'agendada')
        ORDER BY
            CASE WHEN pf.status = 'em_andamento' THEN 0
                 WHEN pf.status = 'agendada'     THEN 1
                 ELSE 2 END,
            pf.data_inicio DESC
        LIMIT 1
    ");
    $stmtFase->execute();
    $fase = $stmtFase->fetch() ?: null;

    $agora         = time();
    $faseAtiva     = false;
    $faseEncerrada = false;
    $faseId        = 0;
    $premiacaoId   = 0;
    $isFaseFinal   = false;
    $limiteVotos   = 1;

    if ($fase) {
        $faseId      = (int)$fase['id'];
        $premiacaoId = (int)$fase['premiacao_id'];
        // isFaseFinal: fase onde o júri vota (permite_juri_final = 1)
        $isFaseFinal = ((int)($fase['permite_juri_final'] ?? 0) === 1);
        $limiteVotos = ($role === 'juri')
            ? 1
            : (int)($fase['qtd_classificados_tecnica'] ?: 5);
        $ini           = strtotime($fase['data_inicio']);
        $fim           = strtotime($fase['data_fim']);
        $faseAtiva     = ($agora >= $ini && $agora <= $fim);
        $faseEncerrada = ($agora > $fim);
    }

    // ── Filtros ────────────────────────────────────────────────────────────
    $filtro_nome      = trim($_GET['nome']      ?? '');
    $filtro_categoria = (string)($_GET['categoria'] ?? '');
    $filtro_ods       = (string)($_GET['ods']       ?? '');
    $filtro_eixo      = (string)($_GET['eixo']      ?? '');

    // ── Query base ───────────────────────────────────────────────────────────────
    //
    // Fase final: finalistas em premiacao_inscricoes (status classificada_fase2)
    //             A tabela premiacao_classificados NAO tem dados desta fase.
    // Fases classificatórias: usa premiacao_classificados com fase_id.
    //
    if ($faseId > 0 && $isFaseFinal) {

        $whereBase = "pi.premiacao_id = {$premiacaoId}
            AND pi.status IN ('classificada_fase2', 'finalista', 'vencedora')";

        $fromBase = "
            FROM premiacao_inscricoes pi
            INNER JOIN negocios n ON n.id = pi.negocio_id
            INNER JOIN premiacao_categorias pc
                ON pc.premiacao_id = pi.premiacao_id
               AND pc.nome COLLATE utf8mb4_unicode_ci = pi.categoria COLLATE utf8mb4_unicode_ci
            LEFT JOIN scores_negocios s  ON s.negocio_id = n.id
            LEFT JOIN ods o              ON o.id = n.ods_prioritaria_id
            LEFT JOIN eixos_tematicos et ON et.id = n.eixo_principal_id
        ";

        $colPosicao  = 'NULL';
        $colOrigem   = 'NULL';
        $ordemPadrao = 'pc.nome ASC, n.nome_fantasia ASC';

    } elseif ($faseId > 0) {

        $whereBase = "cl.fase_id = {$faseId}";

        $fromBase = "
            FROM premiacao_classificados cl
            INNER JOIN negocios n ON n.id = cl.negocio_id
            INNER JOIN premiacao_categorias pc ON pc.id = cl.categoria_id
            LEFT JOIN scores_negocios s  ON s.negocio_id = n.id
            LEFT JOIN ods o              ON o.id = n.ods_prioritaria_id
            LEFT JOIN eixos_tematicos et ON et.id = n.eixo_principal_id
        ";

        $colPosicao  = 'cl.posicao';
        $colOrigem   = 'cl.origem';
        $ordemPadrao = 'cl.posicao ASC';

    } else {

        $whereBase   = '1 = 0';
        $fromBase    = 'FROM negocios n
            LEFT JOIN premiacao_categorias pc ON 1 = 0
            LEFT JOIN scores_negocios s  ON 1 = 0
            LEFT JOIN ods o              ON 1 = 0
            LEFT JOIN eixos_tematicos et ON 1 = 0';
        $colPosicao  = 'NULL';
        $colOrigem   = 'NULL';
        $ordemPadrao = 'n.nome_fantasia ASC';
    }

    $where  = [$whereBase];
    $params = [];

    if ($filtro_nome !== '') {
        $where[]  = 'n.nome_fantasia LIKE ?';
        $params[] = "%{$filtro_nome}%";
    }
    if ($filtro_categoria !== '') {
        $where[]  = 'pc.nome = ?';
        $params[] = $filtro_categoria;
    }
    if ($filtro_ods !== '') {
        $where[]  = 'n.ods_prioritaria_id = ?';
        $params[] = (int)$filtro_ods;
    }
    if ($filtro_eixo !== '') {
        $where[]  = 'n.eixo_principal_id = ?';
        $params[] = (int)$filtro_eixo;
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    // Ordenação — compativel com PHP 7+
    $colunas_permitidas = ['nome' => 'n.nome_fantasia', 'categoria' => 'pc.nome'];
    if (!$isFaseFinal) {
        $colunas_permitidas['posicao'] = 'cl.posicao';
    }
    $direcoes_permitidas = ['ASC', 'DESC'];
    $dir_req = strtoupper((string)($_GET['dir'] ?? ''));
    $ord_req = (string)($_GET['ordem'] ?? '');

    if (isset($colunas_permitidas[$ord_req])) {
        $coluna_ordem  = $colunas_permitidas[$ord_req];
        $direcao_ordem = in_array($dir_req, $direcoes_permitidas, true) ? $dir_req : 'ASC';
        $orderSQL      = "{$coluna_ordem} {$direcao_ordem}";
    } else {
        $orderSQL = $ordemPadrao;
    }

    // Paginação
    $por_pagina   = 50;
    $pagina_atual = max(1, (int)($_GET['pagina'] ?? 1));
    $offset       = ($pagina_atual - 1) * $por_pagina;

    $sqlCount = "SELECT COUNT(*) {$fromBase} {$whereSQL}";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $total_registros = (int)$stmtCount->fetchColumn();
    $total_paginas   = (int)ceil($total_registros / $por_pagina);

    $sql = "
        SELECT
            n.id,
            n.nome_fantasia,
            pc.nome   AS categoria,
            pc.id     AS categoria_id,
            {$colPosicao} AS posicao_classificado,
            {$colOrigem}  AS origem_classificado,
            s.score_geral,
            o.id        AS ods_id,
            o.n_ods     AS ods_numero,
            o.nome      AS ods_nome,
            o.icone_url AS ods_icone,
            et.id       AS eixo_id,
            et.nome     AS eixo_nome
        {$fromBase}
        {$whereSQL}
        ORDER BY {$orderSQL}
        LIMIT {$por_pagina} OFFSET {$offset}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $negocios = $stmt->fetchAll();

    // ── Votos já feitos por este usuário ────────────────────────────────────────
    $votosPorCategoria = [];
    $negociosVotados   = [];
    if ($faseId && $userId) {
        $tabelaVotos = ($role === 'juri') ? 'premiacao_votos_juri' : 'premiacao_votos_tecnicos';
        $stmtVotos = $pdo->prepare("
            SELECT pc2.nome AS categoria_nome, COUNT(*) AS total
            FROM {$tabelaVotos} v
            JOIN premiacao_categorias pc2 ON pc2.id = v.categoria_id
            WHERE v.fase_id = ? AND v.user_id = ?
            GROUP BY pc2.nome
        ");
        $stmtVotos->execute([$faseId, $userId]);
        foreach ($stmtVotos->fetchAll() as $row) {
            $votosPorCategoria[$row['categoria_nome']] = (int)$row['total'];
        }

        $stmtVotadosIds = $pdo->prepare("
            SELECT pi2.negocio_id
            FROM {$tabelaVotos} v
            JOIN premiacao_inscricoes pi2 ON pi2.id = v.inscricao_id
            WHERE v.fase_id = ? AND v.user_id = ?
        ");
        $stmtVotadosIds->execute([$faseId, $userId]);
        $negociosVotados = array_flip($stmtVotadosIds->fetchAll(PDO::FETCH_COLUMN));
    }

    // ── Filtros select (dropdown) ──────────────────────────────────────────────
    $categorias_disponiveis = [];
    $ods_disponiveis        = [];
    $eixos_disponiveis      = [];

    if ($faseId > 0 && $isFaseFinal) {

        $stmtCats = $pdo->prepare("
            SELECT DISTINCT pc3.nome
            FROM premiacao_inscricoes pi3
            INNER JOIN premiacao_categorias pc3
                ON pc3.premiacao_id = pi3.premiacao_id
               AND pc3.nome COLLATE utf8mb4_unicode_ci = pi3.categoria COLLATE utf8mb4_unicode_ci
            WHERE pi3.premiacao_id = ?
              AND pi3.status IN ('classificada_fase2','finalista','vencedora')
            ORDER BY pc3.nome
        ");
        $stmtCats->execute([$premiacaoId]);
        $categorias_disponiveis = $stmtCats->fetchAll(PDO::FETCH_COLUMN);

        $stmtOds = $pdo->prepare("
            SELECT DISTINCT o2.id, o2.n_ods, o2.nome, o2.icone_url
            FROM premiacao_inscricoes pi4
            JOIN negocios n4 ON n4.id = pi4.negocio_id
            JOIN ods o2 ON o2.id = n4.ods_prioritaria_id
            WHERE pi4.premiacao_id = ?
              AND pi4.status IN ('classificada_fase2','finalista','vencedora')
            ORDER BY o2.n_ods ASC
        ");
        $stmtOds->execute([$premiacaoId]);
        $ods_disponiveis = $stmtOds->fetchAll();

        $stmtEixos = $pdo->prepare("
            SELECT DISTINCT et2.id, et2.nome
            FROM premiacao_inscricoes pi5
            JOIN negocios n5 ON n5.id = pi5.negocio_id
            JOIN eixos_tematicos et2 ON et2.id = n5.eixo_principal_id
            WHERE pi5.premiacao_id = ?
              AND pi5.status IN ('classificada_fase2','finalista','vencedora')
            ORDER BY et2.nome ASC
        ");
        $stmtEixos->execute([$premiacaoId]);
        $eixos_disponiveis = $stmtEixos->fetchAll();

    } elseif ($faseId > 0) {

        $stmtCats = $pdo->prepare("
            SELECT DISTINCT pc3.nome
            FROM premiacao_classificados cl2
            JOIN premiacao_categorias pc3 ON pc3.id = cl2.categoria_id
            WHERE cl2.fase_id = ?
            ORDER BY pc3.nome
        ");
        $stmtCats->execute([$faseId]);
        $categorias_disponiveis = $stmtCats->fetchAll(PDO::FETCH_COLUMN);

        $stmtOds = $pdo->prepare("
            SELECT DISTINCT o2.id, o2.n_ods, o2.nome, o2.icone_url
            FROM premiacao_classificados cl3
            JOIN negocios n2 ON n2.id = cl3.negocio_id
            JOIN ods o2 ON o2.id = n2.ods_prioritaria_id
            WHERE cl3.fase_id = ?
            ORDER BY o2.n_ods ASC
        ");
        $stmtOds->execute([$faseId]);
        $ods_disponiveis = $stmtOds->fetchAll();

        $stmtEixos = $pdo->prepare("
            SELECT DISTINCT et2.id, et2.nome
            FROM premiacao_classificados cl4
            JOIN negocios n3 ON n3.id = cl4.negocio_id
            JOIN eixos_tematicos et2 ON et2.id = n3.eixo_principal_id
            WHERE cl4.fase_id = ?
            ORDER BY et2.nome ASC
        ");
        $stmtEixos->execute([$faseId]);
        $eixos_disponiveis = $stmtEixos->fetchAll();
    }

    function linkOrd(string $col): string {
        $g = $_GET;
        $g['dir']   = (($g['ordem'] ?? '') === $col && strtoupper($g['dir'] ?? 'ASC') === 'ASC') ? 'DESC' : 'ASC';
        $g['ordem'] = $col;
        unset($g['pagina']);
        return '?' . http_build_query($g);
    }
    function iconOrd(string $col): string {
        if (($_GET['ordem'] ?? '') !== $col) return '';
        return strtoupper($_GET['dir'] ?? 'ASC') === 'ASC' ? ' ▲' : ' ▼';
    }

} catch (PDOException $e) {
    die('Erro no banco de dados: ' . $e->getMessage());
}

$isjuri     = ($role === 'juri');
$titulo     = $isjuri ? 'Votação — Bancada de Júri'    : 'Votação — Bancada Técnica';
$subtitulo  = $isjuri
    ? 'Avalie e vote nos negócios finalistas como membro do júri.'
    : 'Avalie e vote nos negócios classificados como membro da bancada técnica.';
$voto_url   = $isjuri ? '/premiacao/votar_juri.php' : '/premiacao/votar_tecnico.php';
$voto_icon  = $isjuri ? 'bi-star-fill'   : 'bi-clipboard2-check-fill';
$voto_label = $isjuri ? 'Votar (Júri)' : 'Votar (Técnica)';

include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0" style="color:#1E3425;"><?= $titulo ?></h4>
    <small style="color:#6c8070; font-size:.82rem;"><?= $subtitulo ?></small>
  </div>
  <a href="/admin/dashboard.php" class="hd-btn outline">
    <i class="bi bi-arrow-left"></i> Voltar
  </a>
</div>

<?php if ($fase): ?>
  <?php
    $ini_fmt = date('d/m/Y \u00e0\s H:i', strtotime($fase['data_inicio']));
    $fim_fmt = date('d/m/Y \u00e0\s H:i', strtotime($fase['data_fim']));
    $bannerClass = $faseAtiva ? 'border-success bg-success bg-opacity-10'
                : ($faseEncerrada ? 'border-danger bg-danger bg-opacity-10'
                : 'border-warning bg-warning bg-opacity-10');
    $bannerIcon  = $faseAtiva ? 'bi-unlock-fill text-success'
                : ($faseEncerrada ? 'bi-lock-fill text-danger' : 'bi-clock text-warning');
    $bannerStatus = $faseAtiva
        ? '<span class="badge bg-success">Aberta</span>'
        : ($faseEncerrada
            ? '<span class="badge bg-danger">Encerrada</span>'
            : '<span class="badge bg-warning text-dark">Aguardando início</span>');
  ?>
  <div class="card mb-4 border-2 <?= $bannerClass ?>">
    <div class="card-body py-3">
      <div class="d-flex align-items-start gap-3 flex-wrap">
        <i class="bi <?= $bannerIcon ?>" style="font-size:1.6rem;margin-top:.1rem;"></i>
        <div class="flex-grow-1">
          <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <strong style="font-size:1rem;"><?= htmlspecialchars($fase['nome']) ?></strong>
            <?= $bannerStatus ?>
          </div>
          <?php if (!empty($fase['descricao'])): ?>
            <p class="mb-2 text-muted" style="font-size:.85rem;"><?= nl2br(htmlspecialchars($fase['descricao'])) ?></p>
          <?php endif; ?>
          <div class="d-flex gap-4 flex-wrap" style="font-size:.82rem; color:#4a5e4f;">
            <span><i class="bi bi-calendar-event me-1"></i><strong>Início:</strong> <?= $ini_fmt ?></span>
            <span><i class="bi bi-calendar-check me-1"></i><strong>Encerramento:</strong> <?= $fim_fmt ?></span>
            <span><i class="bi bi-award me-1"></i><strong>Votos por categoria:</strong>
              <?= $limiteVotos ?> negócio<?= $limiteVotos > 1 ? 's' : '' ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($categorias_disponiveis)): ?>
  <div class="card mb-4" style="border:1px solid #dee2e6;">
    <div class="card-body py-3">
      <h6 class="mb-3" style="color:#1E3425; font-size:.88rem; font-weight:700;">
        <i class="bi bi-bar-chart-fill me-1"></i> Seus votos por categoria
      </h6>
      <div class="row g-2">
        <?php foreach ($categorias_disponiveis as $cat): ?>
          <?php
            $feitos = $votosPorCategoria[$cat] ?? 0;
            $pct    = $limiteVotos > 0 ? min(100, (int)round($feitos / $limiteVotos * 100)) : 0;
            $cor    = $feitos >= $limiteVotos ? 'bg-success' : ($feitos > 0 ? 'bg-primary' : 'bg-secondary');
            $txtCor = $feitos >= $limiteVotos ? 'text-success' : ($feitos > 0 ? 'text-primary' : 'text-muted');
          ?>
          <div class="col-12 col-sm-6 col-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span style="font-size:.78rem; color:#1E3425;"><?= htmlspecialchars($cat) ?></span>
              <span class="<?= $txtCor ?>" style="font-size:.78rem; font-weight:600;">
                <?= $feitos ?>/<?= $limiteVotos ?>
                <?php if ($feitos >= $limiteVotos): ?><i class="bi bi-check-circle-fill ms-1"></i><?php endif; ?>
              </span>
            </div>
            <div class="progress" style="height:5px;"><div class="progress-bar <?= $cor ?>" style="width:<?= $pct ?>%"></div></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

<?php else: ?>
  <div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Nenhuma fase de votação ativa no momento.
    <?= $isjuri ? 'A bancada de júri' : 'A bancada técnica' ?> ainda não tem uma fase habilitada.
  </div>
<?php endif; ?>

<!-- Filtros -->
<div class="filter-card card p-3 mb-4">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-sm-6 col-lg-3">
      <label class="form-label">Nome Fantasia</label>
      <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" name="nome" class="form-control" placeholder="Buscar negócio…" value="<?= htmlspecialchars($filtro_nome) ?>">
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
    <div class="col-12 col-sm-6 col-lg-3">
      <label class="form-label">ODS Prioritária</label>
      <select name="ods" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($ods_disponiveis as $ods): ?>
          <option value="<?= (int)$ods['id'] ?>" <?= $filtro_ods === (string)$ods['id'] ? 'selected' : '' ?>>
            ODS <?= htmlspecialchars((string)$ods['n_ods']) ?> — <?= htmlspecialchars($ods['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-sm-6 col-lg-2">
      <label class="form-label">Eixo Temático</label>
      <select name="eixo" class="form-select">
        <option value="">Todos</option>
        <?php foreach ($eixos_disponiveis as $eixo): ?>
          <option value="<?= (int)$eixo['id'] ?>" <?= $filtro_eixo === (string)$eixo['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($eixo['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-sm-6 col-lg-2 d-flex gap-2">
      <button type="submit" class="hd-btn primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button>
      <a href="/admin/votos_tecnicos.php" class="hd-btn outline" title="Limpar filtros"><i class="bi bi-x-lg"></i></a>
    </div>
  </form>
</div>

<?php if ($total_registros > 0): ?>
<p class="text-muted small mb-2">
  Exibindo <strong><?= number_format(min($offset + 1, $total_registros)) ?></strong>
  a <strong><?= number_format(min($offset + $por_pagina, $total_registros)) ?></strong>
  de <strong><?= number_format($total_registros) ?></strong> finalista(s).
</p>
<?php endif; ?>

<div class="card section-card mb-4">
  <div class="neg-table-wrap">
    <table class="neg-table">
      <thead>
        <tr>
          <th class="col-id">#</th>
          <th class="col-nome"><a href="<?= linkOrd('nome') ?>" class="neg-sort-link">Nome Fantasia<?= iconOrd('nome') ?></a></th>
          <th class="col-cat"><a href="<?= linkOrd('categoria') ?>" class="neg-sort-link">Categoria<?= iconOrd('categoria') ?></a></th>
          <th>ODS Prioritária</th>
          <th>Eixo Temático</th>
          <th class="col-score text-center">Score</th>
          <th class="col-acoes text-center">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($negocios)): ?>
          <tr>
            <td colspan="7" class="text-center py-5" style="color:#9aab9d;">
              <i class="bi bi-briefcase" style="font-size:2rem; opacity:.4; display:block; margin-bottom:.5rem;"></i>
              Nenhum negócio encontrado.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($negocios as $neg): ?>
            <?php
              $nid           = (int)$neg['id'];
              $catId         = (int)$neg['categoria_id'];
              $ods_numero    = trim((string)($neg['ods_numero'] ?? ''));
              $ods_nome_val  = trim((string)($neg['ods_nome']   ?? ''));
              $ods_icone_val = trim((string)($neg['ods_icone']  ?? ''));
              $tem_ods       = ($neg['ods_id'] ?? null) !== null;
              $categoria     = (string)($neg['categoria'] ?? '');

              $jaVotou        = isset($negociosVotados[$nid]);
              $votosNaCateg   = $votosPorCategoria[$categoria] ?? 0;
              $limiteAtingido = ($limiteVotos > 0 && $votosNaCateg >= $limiteVotos);
              $btnDesabilitado = (!$fase || !$faseAtiva || $jaVotou || $limiteAtingido);

              if (!$fase || !$faseAtiva) {
                  $tooltip = $faseEncerrada ? 'Fase de votação encerrada' : 'Nenhuma fase de votação ativa';
              } elseif ($jaVotou) {
                  $tooltip = 'Você já votou neste negócio';
              } elseif ($limiteAtingido) {
                  $tooltip = 'Você já votou nesta categoria';
              } else {
                  $tooltip = $voto_label;
              }

              $urlVoto = $voto_url
                  . '?negocio_id='    . $nid
                  . '&fase_id='       . $faseId
                  . '&categoria_id='  . $catId;
            ?>
            <tr>
              <td class="col-id" style="color:#9aab9d; font-size:.78rem; font-family:monospace;">#<?= $nid ?></td>
              <td class="col-nome"><strong><?= htmlspecialchars($neg['nome_fantasia']) ?></strong></td>
              <td class="col-cat">
                <span class="neg-cat-badge"><?= htmlspecialchars($categoria ?: '—') ?></span>
                <?php if ($fase && $faseAtiva && $limiteVotos > 0): ?>
                  <br><small class="<?= $votosNaCateg >= $limiteVotos ? 'text-success' : 'text-muted' ?>" style="font-size:.72rem;">
                    <?= $votosNaCateg ?>/<?= $limiteVotos ?> votos
                  </small>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($tem_ods && $ods_icone_val !== ''): ?>
                  <div class="d-flex align-items-center gap-2">
                    <img src="<?= htmlspecialchars($ods_icone_val) ?>" alt="ODS <?= htmlspecialchars($ods_numero) ?>"
                         title="ODS <?= htmlspecialchars($ods_numero) ?> — <?= htmlspecialchars($ods_nome_val) ?>"
                         style="width:36px;height:36px;object-fit:contain;border-radius:4px;flex-shrink:0;">
                    <span style="font-size:.82rem;color:#4a5e4f;line-height:1.2;">
                      <strong>ODS <?= htmlspecialchars($ods_numero) ?></strong><br>
                      <span style="font-size:.75rem;color:#6c8070;"><?= htmlspecialchars(mb_strimwidth($ods_nome_val, 0, 40, '…')) ?></span>
                    </span>
                  </div>
                <?php elseif ($tem_ods): ?>
                  <span class="neg-cat-badge">ODS <?= htmlspecialchars($ods_numero) ?></span>
                <?php else: ?>
                  <span style="color:#b0bdb3;">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($neg['eixo_nome'])): ?>
                  <span style="font-size:.84rem;color:#1E3425;"><?= htmlspecialchars($neg['eixo_nome']) ?></span>
                <?php else: ?>
                  <span style="color:#b0bdb3;">—</span>
                <?php endif; ?>
              </td>
              <td class="col-score text-center">
                <?php if ($neg['score_geral'] !== null): ?>
                  <span class="neg-score-geral"><?= number_format((float)$neg['score_geral'], 1, ',', '') ?></span>
                <?php else: ?>
                  <span style="color:#b0bdb3; font-size:.82rem;">—</span>
                <?php endif; ?>
              </td>
              <td class="col-acoes text-center">
                <div style="display:flex;flex-direction:column;align-items:center;gap:.4rem;">
                  <a href="/admin/visualizar_negocio.php?id=<?= $nid ?>" class="act-btn edit" title="Visualizar detalhes"
                     style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .7rem;font-size:.78rem;white-space:nowrap;width:100%;justify-content:center;">
                    <i class="bi bi-eye"></i><span>Ver Detalhes</span>
                  </a>
                  <?php if ($btnDesabilitado): ?>
                    <button type="button" class="act-btn" title="<?= htmlspecialchars($tooltip) ?>" disabled
                            style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .7rem;font-size:.78rem;white-space:nowrap;width:100%;justify-content:center;opacity:.45;cursor:not-allowed;
                            <?= $jaVotou ? 'background:rgba(25,135,84,.10);color:#198754;' : 'background:#f8f9fa;color:#adb5bd;' ?>">
                      <i class="bi <?= $jaVotou ? 'bi-check-circle-fill' : $voto_icon ?>"></i>
                      <span><?= $jaVotou ? 'Já votou' : ($faseEncerrada ? 'Encerrado' : $voto_label) ?></span>
                    </button>
                  <?php else: ?>
                    <a href="<?= $urlVoto ?>" class="act-btn" title="<?= htmlspecialchars($tooltip) ?>"
                       style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .7rem;font-size:.78rem;white-space:nowrap;width:100%;justify-content:center;
                       <?= $isjuri ? 'background:rgba(111,66,193,.12);color:#6f42c1;' : 'background:rgba(3,105,161,.12);color:#0369a1;' ?>">
                      <i class="bi <?= $voto_icon ?>"></i><span><?= htmlspecialchars($voto_label) ?></span>
                    </a>
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
      <ul class="pagination pagination-sm mb-0 ip-pagination">
        <?php $get_base = $_GET; unset($get_base['pagina']); $qs = http_build_query($get_base); $qs_sep = $qs ? $qs . '&' : ''; ?>
        <li class="page-item <?= $pagina_atual <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="?<?= $qs_sep ?>pagina=<?= $pagina_atual - 1 ?>"><i class="bi bi-chevron-left"></i></a>
        </li>
        <?php
          $ini_p = max(1, $pagina_atual - 3);
          $fim_p = min($total_paginas, $pagina_atual + 3);
          if ($ini_p > 1): ?><li class="page-item"><a class="page-link" href="?<?= $qs_sep ?>pagina=1">1</a></li><?php
          if ($ini_p > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; endif;
          for ($p = $ini_p; $p <= $fim_p; $p++): ?>
            <li class="page-item <?= $p === $pagina_atual ? 'active' : '' ?>">
              <a class="page-link" href="?<?= $qs_sep ?>pagina=<?= $p ?>"><?= $p ?></a>
            </li>
          <?php endfor;
          if ($fim_p < $total_paginas):
            if ($fim_p < $total_paginas - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="?<?= $qs_sep ?>pagina=<?= $total_paginas ?>"><?= $total_paginas ?></a></li>
          <?php endif; ?>
        <li class="page-item <?= $pagina_atual >= $total_paginas ? 'disabled' : '' ?>">
          <a class="page-link" href="?<?= $qs_sep ?>pagina=<?= $pagina_atual + 1 ?>"><i class="bi bi-chevron-right"></i></a>
        </li>
      </ul>
    </nav>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>
