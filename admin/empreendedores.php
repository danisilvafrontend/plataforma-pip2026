<?php
// /public_html/admin/empreendedores.php
declare(strict_types=1);
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
} catch (PDOException $e) {
    die('Erro na conexão com o banco: ' . $e->getMessage());
}

// ── Filtros ───────────────────────────────────────────
$f_nome   = trim($_GET['nome']   ?? '');
$f_email  = trim($_GET['email']  ?? '');
$f_status = trim($_GET['status'] ?? '');

$where  = [];
$params = [];

if ($f_nome !== '') {
    $where[] = "(
        nome LIKE :nome
        OR sobrenome LIKE :sobrenome
        OR CONCAT_WS(' ', nome, sobrenome) LIKE :nome_completo
    )";
    $params[':nome'] = "%{$f_nome}%";
    $params[':sobrenome'] = "%{$f_nome}%";
    $params[':nome_completo'] = "%{$f_nome}%";
}

if ($f_email !== '') {
    $where[] = "email LIKE :email";
    $params[':email'] = "%{$f_email}%";
}

if ($f_status !== '') {
    $where[] = "status = :status";
    $params[':status'] = $f_status;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Paginação ─────────────────────────────────────────
$limit  = 100;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM empreendedores $whereSql");
$stmtCount->execute($params);
$totalRecords = (int)$stmtCount->fetchColumn();
$totalPages   = max(1, (int)ceil($totalRecords / $limit));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $limit; }

// ── Ordenação ─────────────────────────────────────────
$orderBy = (isset($_GET['filter']) && $_GET['filter'] === 'recentes') ? 'ultimo_login DESC' : 'nome ASC';

// ── Status disponíveis ────────────────────────────────
$statusOptions = $pdo->query(
    "SELECT DISTINCT status FROM empreendedores WHERE status IS NOT NULL AND status != '' ORDER BY status"
)->fetchAll(PDO::FETCH_COLUMN);

// ── Consulta principal ────────────────────────────────
$sql  = "SELECT id, nome, sobrenome, email, status, ultimo_login FROM empreendedores $whereSql ORDER BY {$orderBy} LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}

$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$empreendedores = $stmt->fetchAll();

include __DIR__ . '/../app/views/admin/header.php';

// ── Helpers de badge ──────────────────────────────────
function empStatusBadge(string $s): string {
    $map = [
        'ativo'    => ['#CDDE00', '#1E3425'],
        'inativo'  => ['#fde8ea', '#842029'],
        'pendente' => ['#fff3cd', '#856404'],
        'suspenso' => ['#fff0e0', '#8a4700'],
    ];
    [$bg, $color] = $map[strtolower($s)] ?? ['#f0f0f0', '#6c757d'];
    return "<span class=\"emp-badge\" style=\"background:{$bg};color:{$color}\">" . htmlspecialchars(ucfirst($s)) . "</span>";
}
?>


<!-- ── Cabeçalho da página ───────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0" style="color:#1E3425;">Empreendedores</h4>
    <small class="text-muted">
      <?= number_format($totalRecords, 0, ',', '.') ?> registro<?= $totalRecords !== 1 ? 's' : '' ?> encontrado<?= $totalRecords !== 1 ? 's' : '' ?>
      <?php if ($f_nome || $f_email || $f_status): ?>
        — <a href="/admin/empreendedores.php" style="color:#97A327;font-size:.8rem;">limpar filtros</a>
      <?php endif; ?>
    </small>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a href="/admin/empreendedores.php?filter=recentes<?= $f_nome || $f_email || $f_status ? '&'.http_build_query(['nome'=>$f_nome,'email'=>$f_email,'status'=>$f_status]) : '' ?>"
       class="hd-btn outline <?= isset($_GET['filter']) && $_GET['filter'] === 'recentes' ? 'active' : '' ?>">
      <i class="bi bi-clock-history"></i> Últimos logins
    </a>
    <a href="/admin/enviar_email_status.php" class="hd-btn outline">
      <i class="bi bi-envelope"></i> Enviar e-mail
    </a>
    <a href="/admin/create_empreendedor.php" class="hd-btn lime">
      <i class="bi bi-plus-lg"></i> Novo empreendedor
    </a>
  </div>
</div>

<!-- ── Filtros ────────────────────────────────────────── -->
<div class="filter-card">
  <div class="card-body p-3">
    <form method="GET" action="/admin/empreendedores.php" class="row g-2 align-items-end">
      <?php if (isset($_GET['filter'])): ?>
        <input type="hidden" name="filter" value="<?= htmlspecialchars($_GET['filter']) ?>">
      <?php endif; ?>

      <div class="col-12 col-md-4">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control form-control-sm"
               placeholder="Ex: João da Silva"
               value="<?= htmlspecialchars($f_nome) ?>">
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">E-mail</label>
        <input type="text" name="email" class="form-control form-control-sm"
               placeholder="Ex: joao@email.com"
               value="<?= htmlspecialchars($f_email) ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Todos</option>
          <?php foreach ($statusOptions as $opt): ?>
            <option value="<?= htmlspecialchars((string)$opt) ?>" <?= $f_status === $opt ? 'selected' : '' ?>>
              <?= htmlspecialchars(ucfirst((string)$opt)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2 d-flex gap-2">
        <button type="submit" class="hd-btn primary w-100">
          <i class="bi bi-search"></i> Filtrar
        </button>
        <a href="/admin/empreendedores.php<?= isset($_GET['filter']) ? '?filter='.htmlspecialchars($_GET['filter']) : '' ?>"
           class="hd-btn outline w-100 justify-content-center">
          <i class="bi bi-x-lg"></i>
        </a>
      </div>
    </form>
  </div>
</div>

<!-- ── Tabela ─────────────────────────────────────────── -->
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 8px rgba(30,52,37,.08);overflow:hidden;">
  <div class="table-responsive">
    <table class="emp-table">
      <thead>
        <tr>
          <th style="width:50px;">#</th>
          <th>Empreendedor</th>
          <th>E-mail</th>
          <th>Status</th>
          <th>Último login</th>
          <th style="width:130px; text-align:right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($empreendedores)): ?>
          <tr>
            <td colspan="6" class="text-center py-5" style="color:#9aab9d;">
              <i class="bi bi-person-x" style="font-size:2rem;opacity:.3;"></i>
              <p class="mt-2 mb-0">Nenhum empreendedor encontrado.</p>
              <?php if ($f_nome || $f_email || $f_status): ?>
                <a href="/admin/empreendedores.php" style="color:#97A327;font-size:.82rem;">Limpar filtros</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php else: foreach ($empreendedores as $e): ?>
          <tr>
            <td style="color:#9aab9d;font-size:.78rem;"><?= htmlspecialchars((string)$e['id']) ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="emp-avatar"><?= strtoupper(mb_substr((string)$e['nome'], 0, 1)) ?></div>
                <span class="fw-semibold">
                  <?= htmlspecialchars((string)$e['nome']) ?>
                  <?= htmlspecialchars((string)$e['sobrenome']) ?>
                </span>
              </div>
            </td>
            <td style="color:#4a5e4f;"><?= htmlspecialchars((string)$e['email']) ?></td>
            <td><?= empStatusBadge((string)$e['status']) ?></td>
            <td style="color:#9aab9d; font-size:.82rem;">
              <?php if (!empty($e['ultimo_login'])): ?>
                <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime((string)$e['ultimo_login'])) ?>
              <?php else: ?>
                <span style="color:#c8d0ca;">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex justify-content-end gap-1">
                <?php if (is_superadmin() || is_admin()): ?>
                  <a href="/admin/editar_empreendedor.php?id=<?= $e['id'] ?>" class="act-btn edit" title="Editar">
                    <i class="bi bi-pencil-fill"></i>
                  </a>
                  <a href="/admin/reset_email.php?id=<?= $e['id'] ?>" class="act-btn email" title="Redefinir e-mail">
                    <i class="bi bi-envelope-fill"></i>
                  </a>
                  <a href="/admin/reset_password.php?id=<?= $e['id'] ?>" class="act-btn pwd" title="Redefinir senha">
                    <i class="bi bi-key-fill"></i>
                  </a>
                <?php endif; ?>
                <?php if (is_superadmin()): ?>
                  <a href="/admin/excluir_empreendedor.php?id=<?= $e['id'] ?>"
                     class="act-btn del" title="Excluir"
                     onclick="return confirm('Confirmar exclusão do empreendedor #<?= $e['id'] ?>?')">
                    <i class="bi bi-trash-fill"></i>
                  </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Paginação ──────────────────────────────────────── -->
<?php if ($totalPages > 1):
  $getParams = $_GET;
  unset($getParams['page']);
  $qs       = http_build_query($getParams);
  $linkBase = '?' . ($qs ? $qs . '&' : '') . 'page=';
  $cp = (int)$page;
  $tp = (int)$totalPages;
?>
<nav aria-label="Paginação" class="mb-5">
  <ul class="pagination justify-content-center ip-pagination flex-wrap">

    <li class="page-item <?= $cp <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= $linkBase . max(1, $cp - 1) ?>">
        <i class="bi bi-chevron-left"></i>
      </a>
    </li>

    <?php
    if ($tp <= 7) {
        for ($i = 1; $i <= $tp; $i++) {
            $active = ($cp === $i) ? 'active' : '';
            echo "<li class='page-item {$active}'><a class='page-link' href='{$linkBase}{$i}'>{$i}</a></li>";
        }
    } elseif ($cp <= 4) {
        for ($i = 1; $i <= 5; $i++) {
            $active = ($cp === $i) ? 'active' : '';
            echo "<li class='page-item {$active}'><a class='page-link' href='{$linkBase}{$i}'>{$i}</a></li>";
        }
        echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        echo "<li class='page-item'><a class='page-link' href='{$linkBase}{$tp}'>{$tp}</a></li>";
    } elseif ($cp >= $tp - 3) {
        echo "<li class='page-item'><a class='page-link' href='{$linkBase}1'>1</a></li>";
        echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        for ($i = $tp - 4; $i <= $tp; $i++) {
            $active = ($cp === $i) ? 'active' : '';
            echo "<li class='page-item {$active}'><a class='page-link' href='{$linkBase}{$i}'>{$i}</a></li>";
        }
    } else {
        echo "<li class='page-item'><a class='page-link' href='{$linkBase}1'>1</a></li>";
        echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        for ($i = $cp - 1; $i <= $cp + 1; $i++) {
            $active = ($cp === $i) ? 'active' : '';
            echo "<li class='page-item {$active}'><a class='page-link' href='{$linkBase}{$i}'>{$i}</a></li>";
        }
        echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        echo "<li class='page-item'><a class='page-link' href='{$linkBase}{$tp}'>{$tp}</a></li>";
    }
    ?>

    <li class="page-item <?= $cp >= $tp ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= $linkBase . min($tp, $cp + 1) ?>">
        <i class="bi bi-chevron-right"></i>
      </a>
    </li>

  </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>