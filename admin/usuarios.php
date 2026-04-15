<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

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
$f_nome   = trim($_GET['nome'] ?? '');
$f_email  = trim($_GET['email'] ?? '');
$f_cpf    = trim($_GET['cpf'] ?? '');
$f_estado = trim($_GET['estado'] ?? '');

$where  = [];
$params = [];

if ($f_nome !== '') {
    $where[] = "nome LIKE :nome";
    $params[':nome'] = '%' . $f_nome . '%';
}

if ($f_email !== '') {
    $where[] = "email LIKE :email";
    $params[':email'] = '%' . $f_email . '%';
}

if ($f_cpf !== '') {
    $where[] = "cpf LIKE :cpf";
    $params[':cpf'] = '%' . $f_cpf . '%';
}

if ($f_estado !== '') {
    $where[] = "estado LIKE :estado";
    $params[':estado'] = '%' . $f_estado . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Paginação ─────────────────────────────────────────
$limit  = 100;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM sociedade_civil {$whereSql}");
$stmtCount->execute($params);
$totalRecords = (int)$stmtCount->fetchColumn();

$totalPages = max(1, (int)ceil($totalRecords / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

// ── Estados disponíveis ───────────────────────────────
$stmtEstados = $pdo->query("
    SELECT DISTINCT estado
    FROM sociedade_civil
    WHERE estado IS NOT NULL
      AND estado != ''
    ORDER BY estado ASC
");
$estadoOptions = $stmtEstados->fetchAll(PDO::FETCH_COLUMN);

// ── Consulta principal ────────────────────────────────
$sql = "
    SELECT id, nome, email, cpf, estado
    FROM sociedade_civil
    {$whereSql}
    ORDER BY nome ASC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$usuarios = $stmt->fetchAll();

include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1E3425;">Usuários</h4>
        <small class="text-muted">
            <?= number_format($totalRecords, 0, ',', '.') ?> registro<?= $totalRecords !== 1 ? 's' : '' ?> encontrado<?= $totalRecords !== 1 ? 's' : '' ?>
            <?php if ($f_nome || $f_email || $f_cpf || $f_estado): ?>
                — <a href="/admin/usuarios.php" style="color:#97A327;font-size:.8rem;">limpar filtros</a>
            <?php endif; ?>
        </small>
    </div>
</div>

<div class="filter-card">
    <div class="card-body p-3">
        <form method="GET" action="/admin/usuarios.php" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label">Nome completo</label>
                <input
                    type="text"
                    name="nome"
                    class="form-control form-control-sm"
                    placeholder="Ex: Maria Silva"
                    value="<?= htmlspecialchars($f_nome) ?>"
                >
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label">E-mail</label>
                <input
                    type="text"
                    name="email"
                    class="form-control form-control-sm"
                    placeholder="Ex: maria@email.com"
                    value="<?= htmlspecialchars($f_email) ?>"
                >
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">CPF</label>
                <input
                    type="text"
                    name="cpf"
                    class="form-control form-control-sm"
                    placeholder="Ex: 000.000.000-00"
                    value="<?= htmlspecialchars($f_cpf) ?>"
                >
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($estadoOptions as $estado): ?>
                        <option value="<?= htmlspecialchars((string)$estado) ?>" <?= $f_estado === $estado ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$estado) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="hd-btn primary w-100 justify-content-center">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="/admin/usuarios.php" class="hd-btn outline w-100 justify-content-center">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 2px 8px rgba(30,52,37,.08);overflow:hidden;">
    <div class="table-responsive">
        <table class="emp-table">
            <thead>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>CPF</th>
                    <th style="width:90px; text-align:right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5" style="color:#9aab9d;">
                            <i class="bi bi-people" style="font-size:2rem;opacity:.3;"></i>
                            <p class="mt-2 mb-0">Nenhum usuário encontrado.</p>
                            <?php if ($f_nome || $f_email || $f_cpf || $f_estado): ?>
                                <a href="/admin/usuarios.php" style="color:#97A327;font-size:.82rem;">Limpar filtros</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $u): ?>
                        <?php
                        $nomeCompleto = trim(($u['nome'] ?? '') . ' ' . ($u['sobrenome'] ?? ''));
                        $inicial = strtoupper(mb_substr((string)($u['nome'] ?? ''), 0, 1));
                        ?>
                        <tr>
                            <td style="color:#9aab9d;font-size:.78rem;"><?= (int)$u['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="emp-avatar"><?= htmlspecialchars($inicial !== '' ? $inicial : 'U') ?></div>
                                    <span class="fw-semibold"><?= htmlspecialchars($nomeCompleto) ?></span>
                                </div>
                            </td>
                            <td style="color:#4a5e4f;"><?= htmlspecialchars((string)($u['email'] ?? '')) ?></td>
                            <td style="color:#4a5e4f;"><?= htmlspecialchars((string)($u['cpf'] ?? '')) ?></td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <?php if (is_superadmin()): ?>
                                        <a
                                            href="/admin/excluir_usuario.php?id=<?= (int)$u['id'] ?>"
                                            class="act-btn del"
                                            title="Excluir"
                                            onclick="return confirm('Confirmar exclusão do usuário #<?= (int)$u['id'] ?>?')"
                                        >
                                            <i class="bi bi-trash-fill"></i>
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