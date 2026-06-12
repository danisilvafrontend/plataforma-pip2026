<?php
// ============================================================
// admin/indeferir_carta_parceiro.php
// Cancela a assinatura da carta/acordo do parceiro, permitindo
// nova edição e nova assinatura
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
    die("Acesso negado. Apenas superadmin pode indeferir carta/acordo.");
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

$sql = "
    SELECT 
        p.id,
        p.razao_social,
        p.nome_fantasia,
        p.email_login,
        p.acordo_aceito,
        p.acordo_data,
        p.acordo_ip,
        pc.id AS contrato_id,
        pc.assinatura_digital_url,
        pc.data_assinatura,
        pc.motivo_indeferimento,
        pc.indeferido_em,
        pc.indeferido_por,
        pc.atualizado_em
    FROM parceiros p
    LEFT JOIN parceiro_contrato pc ON pc.parceiro_id = p.id
    WHERE p.id = ?
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$parceiro = $stmt->fetch();

if (!$parceiro) {
    $_SESSION['msg_erro'] = 'Parceiro não encontrado.';
    header('Location: parceiros.php');
    exit;
}

if (empty($parceiro['contrato_id'])) {
    $_SESSION['msg_erro'] = 'Este parceiro não possui registro em parceiro_contrato.';
    header('Location: visualizar_parceiro.php?id=' . $id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_indeferimento'])) {
    $motivo  = trim($_POST['motivo'] ?? '');
    $adminId = $_SESSION['admin_id'] ?? $_SESSION['usuario_id'] ?? 0;

    try {
        $pdo->beginTransaction();

        $pdo->prepare("
            UPDATE parceiro_contrato
            SET assinatura_digital_url = NULL,
                data_assinatura        = NULL,
                motivo_indeferimento   = ?,
                indeferido_em          = NOW(),
                indeferido_por         = ?
            WHERE parceiro_id = ?
        ")->execute([$motivo, $adminId, $id]);

        $pdo->prepare("
            UPDATE parceiros
            SET acordo_aceito  = 0,
                acordo_data    = NULL,
                acordo_ip      = NULL,
                atualizado_em  = NOW()
            WHERE id = ?
        ")->execute([$id]);

        $pdo->commit();

        $_SESSION['msg_sucesso'] = 'Carta/acordo indeferido com sucesso. O parceiro poderá editar os dados e assinar novamente.';
        header('Location: visualizar_parceiro.php?id=' . $id);
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['msg_erro'] = 'Erro ao indeferir carta/acordo: ' . $e->getMessage();
        header('Location: visualizar_parceiro.php?id=' . $id);
        exit;
    }
}

$assinou = !empty($parceiro['acordo_aceito'])
        || !empty($parceiro['data_assinatura'])
        || !empty($parceiro['assinatura_digital_url']);

$pageTitle = "Indeferir Carta/Acordo";
include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Indeferir Carta/Acordo</h2>
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
            <i class="bi bi-file-earmark-x-fill text-warning fs-5"></i>
            <h5 class="fw-bold text-warning mb-0">Confirmar Indeferimento da Carta/Acordo</h5>
        </div>
        <div class="card-body px-4 pb-4 pt-3">

            <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                <i class="bi bi-info-circle-fill fs-5 mt-1"></i>
                <div>
                    Esta ação <strong>cancela a assinatura atual</strong> e libera o parceiro para editar os dados e realizar uma nova assinatura.
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
                <div class="col-md-3 mb-2">
                    <small class="text-muted d-block">Contrato</small>
                    <span class="fw-semibold">#<?= (int) $parceiro['contrato_id'] ?></span>
                </div>
                <div class="col-md-3 mb-2">
                    <small class="text-muted d-block">Status Atual</small>
                    <?php if ($assinou): ?>
                        <span class="badge text-bg-success">Assinado</span>
                    <?php else: ?>
                        <span class="badge text-bg-secondary">Sem assinatura ativa</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-3 mb-2">
                    <small class="text-muted d-block">Data da Assinatura</small>
                    <span class="fw-semibold">
                        <?= !empty($parceiro['data_assinatura'])
                            ? htmlspecialchars((string) $parceiro['data_assinatura'])
                            : '-' ?>
                    </span>
                </div>
                <div class="col-md-3 mb-2">
                    <small class="text-muted d-block">Arquivo da Assinatura</small>
                    <span class="fw-semibold">
                        <?= !empty($parceiro['assinatura_digital_url'])
                            ? htmlspecialchars((string) $parceiro['assinatura_digital_url'])
                            : '-' ?>
                    </span>
                </div>
            </div>

            <form method="post">
                <input type="hidden" name="confirmar_indeferimento" value="1">

                <div class="mb-4">
                    <label for="motivo" class="form-label fw-semibold">Motivo do indeferimento</label>
                    <textarea name="motivo" id="motivo" class="form-control" rows="4"
                        placeholder="Descreva o motivo para registro interno ou para futura comunicação."></textarea>
                    <div class="form-text">Campo opcional, mas recomendado para fins de auditoria.</div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-file-earmark-x"></i> Confirmar Indeferimento
                    </button>
                    <a href="visualizar_parceiro.php?id=<?= (int) $parceiro['id'] ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                </div>
            </form>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>
