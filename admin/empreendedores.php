<?php
// /public_html/admin/empreendedores.php
declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

$config = require __DIR__ . '/../app/config/db.php';

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
} catch (PDOException $e) {
    die('Erro na conexão com o banco: ' . $e->getMessage());
}

// Filtros capturados via GET
$f_nome = $_GET['nome'] ?? '';
$f_email = $_GET['email'] ?? '';
$f_status = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($f_nome !== '') {
    $where[] = "nome LIKE ?";
    $params[] = "%$f_nome%";
}
if ($f_email !== '') {
    $where[] = "email LIKE ?";
    $params[] = "%$f_email%";
}
if ($f_status !== '') {
    $where[] = "status = ?";
    $params[] = $f_status;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = "WHERE " . implode(' AND ', $where);
}

// Configurações da Paginação
$limit = 100;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$offset = ($page - 1) * $limit;


// Contar o total de registros
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM empreendedores $whereSql");
$stmtCount->execute($params);
$totalRecords = (int)$stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Forçar página atual a ser no máximo a última página disponível
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}
echo "<!-- DEBUG: Page=$page, Limit=$limit, Offset=$offset, TotalPages=$totalPages, TotalRecords=$totalRecords -->";
// Definir ordenação
$orderBy = "nome ASC";
if (isset($_GET['filter']) && $_GET['filter'] === 'recentes') {
    $orderBy = "ultimo_login DESC";
}

// Buscar opções de status dinamicamente
$stmtStatus = $pdo->query("SELECT DISTINCT status FROM empreendedores WHERE status IS NOT NULL AND status != '' ORDER BY status");
$statusOptions = $stmtStatus->fetchAll(PDO::FETCH_COLUMN);

// Consulta final usando bindValue para os limites (Segurança do PDO)
$sql = "SELECT id, nome, sobrenome, email, status, ultimo_login FROM empreendedores $whereSql ORDER BY {$orderBy} LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

// Bind dos filtros de texto, se houver
foreach ($params as $key => $val) {
    $stmt->bindValue($key + 1, $val);
}

// Bind dos números do limite da página (Isso resolve 99% de travamentos de página 1 em PDO)
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$empreendedores = $stmt->fetchAll();

include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="row mb-3 align-items-center">
  <div class="col-md-5">
    <h4>Lista de Empreendedores</h4>
    <small class="text-muted">Total: <?= $totalRecords ?> resultados encontrados</small>
  </div>
  <div class="col-md-7 text-end">
    <a href="/admin/empreendedores.php?filter=recentes" class="btn btn-sm btn-outline-secondary">Ordenar por Últimos Logins</a>
    <a href="/admin/enviar_email_status.php" class="btn btn-sm btn-outline-primary">Enviar E-mail</a>
    <a href="/admin/create_empreendedor.php" class="btn btn-sm btn-primary">Criar Empreendedor</a>
  </div>
</div>

<!-- Caixa de Filtros -->
<div class="card mb-4 shadow-sm border-0 bg-light">
  <div class="card-body">
    <form method="GET" action="/admin/empreendedores.php" class="row g-3 align-items-end">
        <?php if(isset($_GET['filter'])): ?>
        <input type="hidden" name="filter" value="<?= htmlspecialchars($_GET['filter']) ?>">
        <?php endif; ?>

        <div class="col-md-4">
            <label class="form-label text-muted small fw-bold mb-1">Buscar por Nome</label>
            <input type="text" name="nome" class="form-control form-control-sm" placeholder="Ex: João da Silva" value="<?= htmlspecialchars($f_nome) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted small fw-bold mb-1">Buscar por E-mail</label>
            <input type="text" name="email" class="form-control form-control-sm" placeholder="Ex: joao@email.com" value="<?= htmlspecialchars($f_email) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label text-muted small fw-bold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach($statusOptions as $opt): ?>
                    <option value="<?= htmlspecialchars((string)$opt) ?>" <?= $f_status === $opt ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst((string)$opt)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filtrar</button>
            <a href="/admin/empreendedores.php<?= isset($_GET['filter']) ? '?filter='.htmlspecialchars($_GET['filter']) : '' ?>" class="btn btn-sm btn-outline-secondary w-100">Limpar</a>
        </div>
    </form>
  </div>
</div>

<!-- Tabela de Resultados -->
<div class="table-responsive bg-white shadow-sm rounded border mb-4">
  <table class="table table-striped table-hover mb-0 align-middle">
    <thead class="table-light">
      <tr>
        <th class="ps-3">ID</th>
        <th>Nome</th>
        <th>E-mail</th>
        <th>Status</th>
        <th>Último login</th>
        <th class="text-end pe-3">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($empreendedores)): ?>
        <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum empreendedor encontrado.</td></tr>
      <?php else: ?>
        <?php foreach ($empreendedores as $e): ?>
          <tr>
            <td class="ps-3 text-muted">#<?= htmlspecialchars((string)$e['id']) ?></td>
            <td><?= htmlspecialchars((string)$e['nome']) ?> <?= htmlspecialchars((string)$e['sobrenome']) ?></td>
            <td><?= htmlspecialchars((string)$e['email']) ?></td>
            <td>
                <?php 
                $statusStr = (string)$e['status'];
                $badgeClass = 'bg-secondary';
                if(strtolower($statusStr) === 'ativo') $badgeClass = 'bg-success';
                if(strtolower($statusStr) === 'inativo') $badgeClass = 'bg-danger';
                if(strtolower($statusStr) === 'pendente') $badgeClass = 'bg-warning text-dark';
                ?>
                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusStr) ?></span>
            </td>
            <td class="text-muted small">
              <?php 
                if (!empty($e['ultimo_login'])) {
                    echo date('d/m/Y H:i', strtotime((string)$e['ultimo_login']));
                } else {
                    echo '-';
                }
              ?>
            </td>
            <td class="text-end pe-3">
              <?php if (is_superadmin()): ?>
                <a href="/admin/editar_empreendedor.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-warning" title="Editar">Editar</a>
                <a href="/admin/reset_email.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-info" title="Email">Email</a>
                <a href="/admin/reset_password.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-secondary" title="Senha">Senha</a>
                <a href="/admin/excluir_empreendedor.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir?')" title="Excluir">Excluir</a>
              <?php elseif (is_admin()): ?>
                <a href="/admin/editar_empreendedor.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-warning" title="Editar">Editar</a>
                <a href="/admin/reset_email.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-info" title="Email">Email</a>
                <a href="/admin/reset_password.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-secondary" title="Senha">Senha</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Bloco da Paginação -->
<?php if ($totalPages > 1): ?>
<nav aria-label="Navegação da página" class="mb-5 mt-4">
  <ul class="pagination justify-content-center">
    <?php
      $getParams = $_GET;
      unset($getParams['page']); // remove sempre
      $qs = http_build_query($getParams);

      $linkBase = '?' . ($qs ? $qs . '&' : '') . 'page=';
      
      $currentPage = (int)$page;
      $totalPgs = (int)$totalPages;
    ?>
    
    <!-- Botão Anterior -->
    <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= $linkBase . max(1, $currentPage - 1) ?>">Anterior</a>
    </li>


    <?php 
      if ($totalPgs <= 7) {
          for ($i = 1; $i <= $totalPgs; $i++) {
              $active = ($currentPage === $i) ? 'active' : '';
              echo "<li class='page-item {$active}'><a class='page-link' href='{$linkBase}{$i}'>{$i}</a></li>";
          }
      } else {
          if ($currentPage <= 4) {
              for ($i = 1; $i <= 5; $i++) {
                  $active = ($currentPage === $i) ? 'active' : '';
                  echo "<li class='page-item {$active}'><a class='page-link' href='{$linkBase}{$i}'>{$i}</a></li>";
              }
              echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
              echo "<li class='page-item'><a class='page-link' href='{$linkBase}{$totalPgs}'>{$totalPgs}</a></li>";
          } elseif ($currentPage >= $totalPgs - 3) {
              echo "<li class='page-item'><a class='page-link' href='{$linkBase}1'>1</a></li>";
              echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
              for ($i = $totalPgs - 4; $i <= $totalPgs; $i++) {
                  $active = ($currentPage === $i) ? 'active' : '';
                  echo "<li class='page-item {$active}'><a class='page-link' href='{$linkBase}{$i}'>{$i}</a></li>";
              }
          } else {
              echo "<li class='page-item'><a class='page-link' href='{$linkBase}1'>1</a></li>";
              echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
              for ($i = $currentPage - 1; $i <= $currentPage + 1; $i++) {
                  $active = ($currentPage === $i) ? 'active' : '';
                  echo "<li class='page-item {$active}'><a class='page-link' href='{$linkBase}{$i}'>{$i}</a></li>";
              }
              echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
              echo "<li class='page-item'><a class='page-link' href='{$linkBase}{$totalPgs}'>{$totalPgs}</a></li>";
          }
      }
    ?>

    <!-- Botão Próxima -->
    <li class="page-item <?= ($currentPage >= $totalPgs) ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= $linkBase . min($totalPgs, $currentPage + 1) ?>">Próxima</a>
    </li>
  </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>
