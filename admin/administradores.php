<?php
// /public_html/admin/administradores.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

// localizar app automaticamente
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
require_admin_login(); // verifica usuário e role
if (!empty($_SESSION['flash_message'])) {
    $messages[] = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
require_once $appBase . '/services/Database.php';
require_once $appBase . '/models/UserModel.php';

$header = $appBase . '/views/admin/header.php';
$footer = $appBase . '/views/admin/footer.php';
if (is_file($header)) include $header; else echo "<h2>Usuários</h2>";

// CSRF
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];

$um = new UserModel();
$messages = [];
$errors = [];

// processa ações POST (delete / quick status change)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, (string)$posted)) {
        $errors[] = 'Requisição inválida.';
    } else {
        $action = $_POST['action'];
        if ($action === 'delete') {
            $id = (int)($_POST['user_id'] ?? 0);
            if ($id > 0) {
                try {
                    $um->delete($id);
                    $messages[] = 'Usuário excluído.';
                } catch (Throwable $e) {
                    $errors[] = 'Erro ao excluir usuário.';
                    error_log('User delete error: ' . $e->getMessage());
                }
            } else {
                $errors[] = 'ID inválido.';
            }
        } elseif ($action === 'quick_status') {
            $id = (int)($_POST['user_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? '';
            if ($id <= 0) {
                $errors[] = 'ID inválido.';
            } elseif ($newStatus === 'inativo' && !is_superadmin()) {
                $errors[] = 'Apenas superadmins podem marcar contas como inativas.';
            } else {
                try {
                    // atualizar apenas o status de forma segura
                    $user = $um->getById($id);
                    if ($user) {
                        $um->update($id, [
                            'nome' => $user['nome'],
                            'email' => $user['email'],
                            'role' => $user['role'],
                            'status' => in_array($newStatus, ['ativo','pendente','suspenso','inativo','excluido'], true) ? $newStatus : $user['status'],
                            'senha' => null
                        ]);
                        $messages[] = 'Status atualizado.';
                    } else {
                        $errors[] = 'Usuário não encontrado.';
                    }
                } catch (Throwable $e) {
                    $errors[] = 'Erro ao atualizar status.';
                    error_log('Quick status update error: ' . $e->getMessage());
                }
            }
        }
    }
}

// listagem
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$q = trim((string)($_GET['q'] ?? ''));

$total = $um->count($q);
$pages = (int)ceil($total / $perPage);
$users = $um->getAll($page, $perPage, $q);
?>
<div class="row mb-3">
  <div class="col-6"><h4>Administradores Plataforma Impactos Positivos</h4></div>
  <div class="col-6 text-end">
    <a class="btn btn-sm btn-primary" href="/admin/create_user.php">Novo Usuário</a>
  </div>
</div>

<?php if (!empty($messages)): ?>
  <div class="alert alert-success"><?php foreach($messages as $m) echo htmlspecialchars($m).' '; ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><?php foreach($errors as $e) echo htmlspecialchars($e).' '; ?></div>
<?php endif; ?>

<div class="mb-3 d-flex">
  <form class="me-auto" method="get">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar por nome ou e-mail" value="<?=htmlspecialchars($q)?>">
  </form>
</div>

<table class="table table-striped table-sm">
  <thead><tr><th>ID</th><th>Nome</th><th>E-mail</th><th>Role</th><th>Status</th><th></th></tr></thead>
  <tbody>
    <?php if (empty($users)): ?>
      <tr><td colspan="6" class="text-center">Nenhum usuário encontrado.</td></tr>
    <?php else: foreach ($users as $u): ?>
      <tr>
        <td><?php echo htmlspecialchars((string)$u['id'], ENT_QUOTES) ?></td>
        <td><?php echo htmlspecialchars((string)$u['nome'], ENT_QUOTES) ?></td>
        <td><?php echo htmlspecialchars((string)$u['email'], ENT_QUOTES) ?></td>
        <td><?php echo htmlspecialchars((string)$u['role'], ENT_QUOTES) ?></td>
        <td><?php echo htmlspecialchars((string)$u['status'], ENT_QUOTES) ?></td>
        <td class="text-end">
          <a href="/admin/edit_user.php?id=<?php echo urlencode((string)$u['id']) ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Confirmar exclusão do usuário #<?php echo htmlspecialchars((string)$u['id'], ENT_QUOTES) ?>?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars((string)$u['id'], ENT_QUOTES) ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES) ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>

<nav>
  <ul class="pagination pagination-sm">
    <?php for($p=1;$p<=$pages;$p++): ?>
      <li class="page-item <?= $p===$page ? 'active' : '' ?>"><a class="page-link" href="?page=<?=$p?>&q=<?=urlencode($q)?>"><?=$p?></a></li>
    <?php endfor; ?>
  </ul>
</nav>

<?php
if (is_file($footer)) include $footer;
?>