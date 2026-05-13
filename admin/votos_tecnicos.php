<?php
// /public_html/admin/votos_tecnicos.php
declare(strict_types=1);
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../app/helpers/auth.php';

// Apenas juri e tecnica acessam esta página
require_admin_login(['juri', 'tecnica']);
$role = $_SESSION['user_role'] ?? '';

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

    $filtro_nome = trim($_GET['nome'] ?? '');
    if ($filtro_nome !== '') {
        $where[]  = "n.nome_fantasia LIKE ?";
        $params[] = "%{$filtro_nome}%";
    }

    $filtro_categoria = $_GET['categoria'] ?? '';
    if ($filtro_categoria !== '') {
        $where[]  = "n.categoria = ?";
        $params[] = $filtro_categoria;
    }

    $filtro_ods = $_GET['ods'] ?? '';
    if ($filtro_ods !== '') {
        $where[]  = "n.ods_prioritaria_id = ?";
        $params[] = (int)$filtro_ods;
    }

    $filtro_eixo = $_GET['eixo'] ?? '';
    if ($filtro_eixo !== '') {
        $where[]  = "n.eixo_principal_id = ?";
        $params[] = (int)$filtro_eixo;
    }

    // Apenas inscrições completas
    $where[] = "n.inscricao_completa = 1";

    $whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Ordenação
    $colunas_permitidas = [
        'nome'  => 'n.nome_fantasia',
        'geral' => 's.score_geral',
    ];
    $direcoes_permitidas = ['ASC', 'DESC'];
    $coluna_ordem  = $_GET['ordem'] ?? 'nome';
    $direcao_ordem = $_GET['dir']   ?? 'ASC';
    $campo_sql = $colunas_permitidas[$coluna_ordem] ?? 'n.nome_fantasia';
    $dir_sql   = in_array(strtoupper($direcao_ordem), $direcoes_permitidas) ? strtoupper($direcao_ordem) : 'ASC';

    // Paginação
    $por_pagina   = 50;
    $pagina_atual = max(1, (int)($_GET['pagina'] ?? 1));
    $offset       = ($pagina_atual - 1) * $por_pagina;

    $sqlCount = "
        SELECT COUNT(*)
        FROM negocios n
        LEFT JOIN scores_negocios s ON n.id = s.negocio_id
        LEFT JOIN ods o ON o.id = n.ods_prioritaria_id
        LEFT JOIN eixos_tematicos et ON et.id = n.eixo_principal_id
        {$whereSQL}
    ";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $total_registros = (int)$stmtCount->fetchColumn();
    $total_paginas   = (int)ceil($total_registros / $por_pagina);

    $sql = "
        SELECT
            n.id,
            n.nome_fantasia,
            n.categoria,
            s.score_geral,
            o.id        AS ods_id,
            o.n_ods     AS ods_numero,
            o.nome      AS ods_nome,
            o.icone_url AS ods_icone,
            et.id       AS eixo_id,
            et.nome     AS eixo_nome
        FROM negocios n
        LEFT JOIN scores_negocios s  ON n.id = s.negocio_id
        LEFT JOIN ods o              ON o.id = n.ods_prioritaria_id
        LEFT JOIN eixos_tematicos et ON et.id = n.eixo_principal_id
        {$whereSQL}
        ORDER BY {$campo_sql} {$dir_sql}
        LIMIT {$por_pagina} OFFSET {$offset}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $negocios = $stmt->fetchAll();

    $categorias_disponiveis = $pdo->query(
        "SELECT DISTINCT categoria FROM negocios
         WHERE categoria IS NOT NULL AND categoria != '' AND inscricao_completa = 1
         ORDER BY categoria"
    )->fetchAll(PDO::FETCH_COLUMN);

    $ods_disponiveis = $pdo->query(
        "SELECT o.id, o.n_ods, o.nome, o.icone_url
         FROM ods o
         INNER JOIN negocios n ON n.ods_prioritaria_id = o.id AND n.inscricao_completa = 1
         GROUP BY o.id, o.n_ods, o.nome, o.icone_url
         ORDER BY o.n_ods ASC"
    )->fetchAll();

    $eixos_disponiveis = $pdo->query(
        "SELECT et.id, et.nome
         FROM eixos_tematicos et
         INNER JOIN negocios n ON n.eixo_principal_id = et.id AND n.inscricao_completa = 1
         GROUP BY et.id, et.nome
         ORDER BY et.nome ASC"
    )->fetchAll();

    function linkOrdenacaoVotos(string $coluna): string {
        $get = $_GET;
        $dir_atual = $get['dir']   ?? 'ASC';
        $col_atual = $get['ordem'] ?? 'nome';
        $get['dir']   = ($col_atual === $coluna && $dir_atual === 'ASC') ? 'DESC' : 'ASC';
        $get['ordem'] = $coluna;
        unset($get['pagina']);
        return '?' . http_build_query($get);
    }

    function iconeOrdenacaoVotos(string $coluna): string {
        $dir_atual = $_GET['dir']   ?? 'ASC';
        $col_atual = $_GET['ordem'] ?? 'nome';
        if ($col_atual === $coluna) {
            return $dir_atual === 'ASC' ? ' ▲' : ' ▼';
        }
        return '';
    }

} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}

$voto_label = $role === 'juri' ? 'Votar (Júri)'    : 'Votar (Técnica)';
$voto_url   = $role === 'juri' ? '/premiacao/votar_juri.php' : '/premiacao/votar_tecnico.php';
$voto_icon  = $role === 'juri' ? 'bi-star-fill'     : 'bi-clipboard2-check-fill';
$voto_style = $role === 'juri'
    ? 'background:rgba(111,66,193,.12);color:#6f42c1;'
    : 'background:rgba(3,105,161,.12);color:#0369a1;';

include __DIR__ . '/../app/views/admin/header.php';
?>

<!-- Cabeçalho da página -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0" style="color:#1E3425;">
      <?= $role === 'juri' ? 'Votação — Bancada de Júri' : 'Votação — Bancada Técnica' ?>
    </h4>
    <small style="color:#6c8070; font-size:.82rem;">
      <?= $role === 'juri'
          ? 'Avalie e vote nos negócios inscritos como membro do júri.'
          : 'Avalie e vote nos negócios inscritos como membro da bancada técnica.'
      ?>
    </small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="/admin/dashboard.php" class="hd-btn outline">
      <i class="bi bi-arrow-left"></i> Voltar
    </a>
  </div>
</div>

<!-- Filtros -->
<div class="filter-card card p-3 mb-4">
  <form method="GET" class="row g-2 align-items-end">

    <div class="col-12 col-sm-6 col-lg-3">
      <label class="form-label">Nome Fantasia</label>
      <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" name="nome" class="form-control"
               placeholder="Buscar negócio…"
               value="<?= htmlspecialchars($filtro_nome) ?>">
      </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-2">
      <label class="form-label">Categoria</label>
      <select name="categoria" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($categorias_disponiveis as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>"
            <?= $filtro_categoria === $cat ? 'selected' : '' ?>>
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
          <option value="<?= (int)$ods['id'] ?>"
            <?= (string)$filtro_ods === (string)$ods['id'] ? 'selected' : '' ?>>
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
          <option value="<?= (int)$eixo['id'] ?>"
            <?= (string)$filtro_eixo === (string)$eixo['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($eixo['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-sm-6 col-lg-2 d-flex gap-2">
      <button type="submit" class="hd-btn primary w-100">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
      <a href="/admin/votos_tecnicos.php" class="hd-btn outline" title="Limpar filtros">
        <i class="bi bi-x-lg"></i>
      </a>
    </div>

  </form>
</div>

<?php if ($total_registros > 0): ?>
<p class="text-muted small mb-2">
  Exibindo <strong><?= number_format(min($offset + 1, $total_registros)) ?></strong>
  a <strong><?= number_format(min($offset + $por_pagina, $total_registros)) ?></strong>
  de <strong><?= number_format($total_registros) ?></strong> negócio(s) com inscrição concluída.
</p>
<?php endif; ?>

<!-- Tabela -->
<div class="card section-card mb-4">
  <div class="neg-table-wrap">
    <table class="neg-table">
      <thead>
        <tr>
          <th class="col-id">#</th>
          <th class="col-nome">
            <a href="<?= linkOrdenacaoVotos('nome') ?>" class="neg-sort-link">
              Nome Fantasia<?= iconeOrdenacaoVotos('nome') ?>
            </a>
          </th>
          <th class="col-cat">Categoria</th>
          <th>ODS Prioritária</th>
          <th>Eixo Temático</th>
          <th class="col-score text-center">
            <a href="<?= linkOrdenacaoVotos('geral') ?>" class="neg-sort-link">
              Score Geral<?= iconeOrdenacaoVotos('geral') ?>
            </a>
          </th>
          <th class="col-acoes text-center">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($negocios)): ?>
          <tr>
            <td colspan="7" class="text-center py-5" style="color:#9aab9d;">
              <i class="bi bi-briefcase" style="font-size:2rem; opacity:.4; display:block; margin-bottom:.5rem;"></i>
              Nenhum negócio encontrado com os filtros selecionados.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($negocios as $neg): ?>
            <?php
              $nid        = (int)$neg['id'];
              $ods_numero = trim((string)($neg['ods_numero'] ?? ''));
              $ods_nome   = trim((string)($neg['ods_nome']   ?? ''));
              $ods_icone  = trim((string)($neg['ods_icone']  ?? ''));
              $tem_ods    = ($neg['ods_id'] ?? null) !== null;
            ?>
            <tr>

              <td class="col-id" style="color:#9aab9d; font-size:.78rem; font-family:monospace;">
                #<?= $nid ?>
              </td>

              <td class="col-nome">
                <strong><?= htmlspecialchars($neg['nome_fantasia']) ?></strong>
              </td>

              <td class="col-cat">
                <span class="neg-cat-badge">
                  <?= htmlspecialchars($neg['categoria'] ?: '—') ?>
                </span>
              </td>

              <!-- ODS Prioritária — usa ods_id como guard, não n_ods -->
              <td>
                <?php if ($tem_ods && $ods_icone !== ''): ?>
                  <div class="d-flex align-items-center gap-2">
                    <img src="<?= htmlspecialchars($ods_icone) ?>"
                         alt="ODS <?= htmlspecialchars($ods_numero) ?>"
                         title="ODS <?= htmlspecialchars($ods_numero) ?> — <?= htmlspecialchars($ods_nome) ?>"
                         style="width:36px;height:36px;object-fit:contain;border-radius:4px;flex-shrink:0;">
                    <span style="font-size:.82rem;color:#4a5e4f;line-height:1.2;">
                      <strong>ODS <?= htmlspecialchars($ods_numero) ?></strong><br>
                      <span style="font-size:.75rem;color:#6c8070;">
                        <?= htmlspecialchars(mb_strimwidth($ods_nome, 0, 40, '…')) ?>
                      </span>
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
                  <span style="font-size:.84rem;color:#1E3425;">
                    <?= htmlspecialchars($neg['eixo_nome']) ?>
                  </span>
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

              <!-- Ações -->
              <td class="col-acoes text-center">
                <div style="display:flex;flex-direction:column;align-items:center;gap:.4rem;">

                  <a href="/admin/visualizar_negocio.php?id=<?= $nid ?>"
                     class="act-btn edit"
                     title="Visualizar detalhes do negócio"
                     style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .7rem;font-size:.78rem;white-space:nowrap;width:100%;justify-content:center;">
                    <i class="bi bi-eye"></i>
                    <span>Ver Detalhes</span>
                  </a>

                  <a href="<?= $voto_url ?>?negocio_id=<?= $nid ?>"
                     class="act-btn"
                     title="<?= htmlspecialchars($voto_label) ?>"
                     style="display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .7rem;font-size:.78rem;white-space:nowrap;width:100%;justify-content:center;<?= $voto_style ?>">
                    <i class="bi <?= $voto_icon ?>"></i>
                    <span><?= htmlspecialchars($voto_label) ?></span>
                  </a>

                </div>
              </td>

            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Paginação -->
<?php if ($total_paginas > 1): ?>
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-3 mb-4">
    <div class="text-muted small">
      Exibindo <strong><?= number_format(min($offset + 1, $total_registros)) ?></strong>
      a <strong><?= number_format(min($offset + $por_pagina, $total_registros)) ?></strong>
      de <strong><?= number_format($total_registros) ?></strong> negócios
    </div>
    <nav>
      <ul class="pagination pagination-sm mb-0 ip-pagination">
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

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>
