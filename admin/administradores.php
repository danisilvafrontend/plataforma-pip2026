<?php
// /public_html/admin/administradores.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

$possibleAppPaths = [
    __DIR__ . '/../app',
    __DIR__ . '/../../app',
    __DIR__ . '/app',
    __DIR__ . '/../../../app'
];
$appBase = null;
foreach ($possibleAppPaths as $p) if (is_dir($p)) { $appBase = realpath($p); break; }
if ($appBase === null) { http_response_code(500); echo "Erro: pasta app não encontrada."; exit; }

require_once $appBase . '/helpers/auth.php';
require_admin_login();
if (!empty($_SESSION['flash_message'])) {
    $messages[] = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
require_once $appBase . '/services/Database.php';
require_once $appBase . '/models/UserModel.php';

$header = $appBase . '/views/admin/header.php';
$footer = $appBase . '/views/admin/footer.php';
if (is_file($header)) include $header; else echo "<h2>Administradores</h2>";

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];

$um = new UserModel();
$messages = $messages ?? [];
$errors   = [];

// ── POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, (string)$posted)) {
        $errors[] = 'Requisição inválida.';
    } else {
        $action = $_POST['action'];
        if ($action === 'delete') {
            $id = (int)($_POST['user_id'] ?? 0);
            if ($id > 0) {
                try { $um->delete($id); $messages[] = 'Usuário excluído.'; }
                catch (Throwable $e) { $errors[] = 'Erro ao excluir usuário.'; error_log('User delete error: ' . $e->getMessage()); }
            } else { $errors[] = 'ID inválido.'; }
        } elseif ($action === 'quick_status') {
            $id        = (int)($_POST['user_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? '';
            if ($id <= 0) { $errors[] = 'ID inválido.'; }
            elseif ($newStatus === 'inativo' && !is_superadmin()) { $errors[] = 'Apenas superadmins podem marcar contas como inativas.'; }
            else {
                try {
                    $user = $um->getById($id);
                    if ($user) {
                        $um->update($id, [
                            'nome'   => $user['nome'],
                            'email'  => $user['email'],
                            'role'   => $user['role'],
                            'status' => in_array($newStatus, ['ativo','pendente','suspenso','inativo','excluido'], true) ? $newStatus : $user['status'],
                            'senha'  => null
                        ]);
                        $messages[] = 'Status atualizado.';
                    } else { $errors[] = 'Usuário não encontrado.'; }
                } catch (Throwable $e) { $errors[] = 'Erro ao atualizar status.'; error_log('Quick status update error: ' . $e->getMessage()); }
            }
        }
    }
}

// ── Listagem (sem paginação — poucos admins) ──────────
$q     = trim((string)($_GET['q'] ?? ''));
$users = $um->getAll(1, 200, $q); // limite alto — sem paginação visual
$total = count($users);

// helpers de badge
function statusBadge(string $s): string {
    $map = [
        'ativo'    => ['bg' => '#CDDE00', 'color' => '#1E3425', 'label' => 'Ativo'],
        'pendente' => ['bg' => '#fff3cd', 'color' => '#856404', 'label' => 'Pendente'],
        'suspenso' => ['bg' => '#fff0e0', 'color' => '#8a4700', 'label' => 'Suspenso'],
        'inativo'  => ['bg' => '#f0f0f0', 'color' => '#6c757d', 'label' => 'Inativo'],
        'excluido' => ['bg' => '#fde8ea', 'color' => '#842029', 'label' => 'Excluído'],
    ];
    $d = $map[$s] ?? ['bg' => '#eee', 'color' => '#333', 'label' => ucfirst($s)];
    return "<span class=\"status-badge\" style=\"background:{$d['bg']};color:{$d['color']}\">{$d['label']}</span>";
}

function roleBadge(string $r): string {
    $map = [
        'superadmin' => ['bg' => '#1E3425', 'color' => '#CDDE00'],
        'admin'      => ['bg' => 'rgba(30,52,37,.1)', 'color' => '#1E3425'],
        'user'       => ['bg' => 'rgba(149,188,204,.2)', 'color' => '#3a6f82'],
    ];
    $d = $map[$r] ?? ['bg' => '#eee', 'color' => '#333'];
    return "<span class=\"role-badge\" style=\"background:{$d['bg']};color:{$d['color']}\">".ucfirst($r)."</span>";
}
?>

<!-- ── Cabeçalho da página ───────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0" style="color:#1E3425;">Administradores</h4>
    <small class="text-muted">Plataforma Impactos Positivos &mdash; <strong><?= $total ?></strong> registro<?= $total !== 1 ? 's' : '' ?></small>
  </div>
  <a href="/admin/create_user.php" class="btn btn-sm fw-bold px-3" style="background:#CDDE00;color:#1E3425;border:none;border-radius:8px;">
    <i class="bi bi-plus-lg me-1"></i> Novo Administrador
  </a>
</div>

<!-- ── Alertas ───────────────────────────────────────── -->
<?php if (!empty($messages)): ?>
  <div class="alert d-flex align-items-center gap-2 mb-3" style="background:rgba(205,222,0,.15);border:1px solid rgba(205,222,0,.4);color:#1E3425;border-radius:10px;">
    <i class="bi bi-check-circle-fill" style="color:#7a8500;"></i>
    <?php foreach ($messages as $m) echo htmlspecialchars($m) . ' '; ?>
  </div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert d-flex align-items-center gap-2 mb-3" style="background:#fde8ea;border:1px solid #f5c2c7;color:#842029;border-radius:10px;">
    <i class="bi bi-exclamation-circle-fill"></i>
    <?php foreach ($errors as $e) echo htmlspecialchars($e) . ' '; ?>
  </div>
<?php endif; ?>

<!-- ── Barra de busca ────────────────────────────────── -->
<div class="card mb-3" style="border:none;border-radius:12px;box-shadow:0 2px 8px rgba(30,52,37,.08);">
  <div class="card-body py-2 px-3">
    <form method="get">
      <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Buscar por nome ou e-mail…"
               value="<?= htmlspecialchars($q) ?>">
      </div>
    </form>
  </div>
</div>

<!-- ── Tabela ────────────────────────────────────────── -->
<div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 8px rgba(30,52,37,.08);overflow:hidden;">
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Usuário</th>
          <th>E-mail</th>
          <th>Role</th>
          <th>Status</th>
          <th style="width:90px;text-align:right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr>
            <td colspan="6" class="text-center py-5" style="color:#9aab9d;">
              <i class="bi bi-people" style="font-size:2rem;opacity:.3;"></i>
              <p class="mt-2 mb-0">Nenhum administrador encontrado.</p>
            </td>
          </tr>
        <?php else: foreach ($users as $u): ?>
          <tr>
            <td style="color:#9aab9d;font-size:.78rem;"><?= htmlspecialchars((string)$u['id'], ENT_QUOTES) ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="user-avatar"><?= strtoupper(mb_substr($u['nome'], 0, 1)) ?></div>
                <span class="fw-semibold"><?= htmlspecialchars((string)$u['nome'], ENT_QUOTES) ?></span>
              </div>
            </td>
            <td style="color:#4a5e4f;"><?= htmlspecialchars((string)$u['email'], ENT_QUOTES) ?></td>
            <td><?= roleBadge((string)$u['role']) ?></td>
            <td><?= statusBadge((string)$u['status']) ?></td>
            <td class="text-end">
              <div class="d-flex justify-content-end gap-1">
                <a href="/admin/edit_user.php?id=<?= urlencode((string)$u['id']) ?>"
                   class="btn-icon edit" title="Editar">
                  <i class="bi bi-pencil-fill"></i>
                </a>
                <form method="post" style="display:inline;"
                      onsubmit="return confirm('Confirmar exclusão do usuário #<?= htmlspecialchars((string)$u['id'], ENT_QUOTES) ?>?')">
                  <input type="hidden" name="action"     value="delete">
                  <input type="hidden" name="user_id"    value="<?= htmlspecialchars((string)$u['id'], ENT_QUOTES) ?>">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
                  <button type="submit" class="btn-icon del" title="Excluir">
                    <i class="bi bi-trash-fill"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (is_file($footer)) include $footer; ?>