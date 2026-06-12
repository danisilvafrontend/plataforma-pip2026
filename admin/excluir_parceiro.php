<?php
// ============================================================
// admin/excluir_parceiro.php
// Exclui permanentemente um parceiro e seus dados relacionados
// ============================================================
declare(strict_types=1);
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

// Apenas superadmin
if (!is_superadmin()) {
    http_response_code(403);
    die("Acesso negado. Apenas superadmin pode excluir parceiro.");
}

$config = require __DIR__ . '/../app/config/db.php';

$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['msg_erro'] = 'ID de parceiro inválido.';
    header('Location: parceiros.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, razao_social, nome_fantasia, email_login, rep_nome, rep_email FROM parceiros WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$parceiro = $stmt->fetch();

if (!$parceiro) {
    $_SESSION['msg_erro'] = 'Parceiro não encontrado.';
    header('Location: parceiros.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_exclusao'])) {
    try {
        $pdo->beginTransaction();

        $pdo->prepare('DELETE FROM parceiro_ods WHERE parceiro_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM parceiro_interesses WHERE parceiro_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM parceiro_etapa_extra WHERE parceiro_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM parceiro_contrato WHERE parceiro_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM parceiros_perfil WHERE parceiro_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM parceiros WHERE id = ?')->execute([$id]);

        $pdo->commit();

        $_SESSION['msg_sucesso'] = 'Parceiro excluído com sucesso.';
        header('Location: parceiros.php');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['msg_erro'] = 'Erro ao excluir parceiro: ' . $e->getMessage();
        header('Location: visualizar_parceiro.php?id=' . $id);
        exit;
    }
}

$pageTitle = "Excluir Parceiro";
include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Excluir Parceiro</h2>
            <p class="text-muted mb-0">
                <?= htmlspecialchars($parceiro['nome_fantasia'] ?? $parceiro['razao_social'] ?? 'Parceiro') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="visualizar_parceiro.php?id=<?= (int) $parceiro['id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
            <a href="parceiros.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul"></i> Lista de Parceiros
            </a>
        </div>
    </div>

    <div class="card border-0 rounded-4 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-3 pb-0 px-4 d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
            <h5 class="fw-bold text-danger mb-0">Confirmação de Exclusão Permanente</h5>
        </div>
        <div class="card-body px-4 pb-4 pt-3">

            <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                <i class="bi bi-shield-exclamation fs-5 mt-1"></i>
                <div>
                    <strong>Atenção:</strong> esta ação remove permanentemente o parceiro e todos os dados relacionados (contrato, ODS, interesses, perfil). Esta operação <strong>não pode ser desfeita</strong>.
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 mb-2">
                    <small class="text-muted d-block">ID</small>
                    <span class="fw-semibold">#<?= (int) $parceiro['id'] ?></span>
                </div>
                <div class="col-md-6 mb-2">
                    <small class="text-muted d-block">Razão Social</small>
                    <span class="fw-semibold"><?= htmlspecialchars((string) ($parceiro['razao_social'] ?? '-')) ?></span>
                </div>
                <div class="col-md-6 mb-2">
                    <small class="text-muted d-block">Nome Fantasia</small>
                    <span class="fw-semibold"><?= htmlspecialchars((string) ($parceiro['nome_fantasia'] ?? '-')) ?></span>
                </div>
                <div class="col-md-6 mb-2">
                    <small class="text-muted d-block">E-mail de Login</small>
                    <span class="fw-semibold"><?= htmlspecialchars((string) ($parceiro['email_login'] ?? '-')) ?></span>
                </div>
                <div class="col-md-6 mb-2">
                    <small class="text-muted d-block">Representante</small>
                    <span class="fw-semibold"><?= htmlspecialchars((string) ($parceiro['rep_nome'] ?? '-')) ?></span>
                </div>
                <div class="col-md-6 mb-2">
                    <small class="text-muted d-block">E-mail do Representante</small>
                    <span class="fw-semibold"><?= htmlspecialchars((string) ($parceiro['rep_email'] ?? '-')) ?></span>
                </div>
            </div>

            <form method="post" class="d-flex gap-2 flex-wrap">
                <input type="hidden" name="confirmar_exclusao" value="1">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash3"></i> Sim, excluir permanentemente
                </button>
                <a href="visualizar_parceiro.php?id=<?= (int) $parceiro['id'] ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>
