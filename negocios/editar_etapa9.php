<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id = (int)($_GET['id'] ?? 0);
if ($negocio_id === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}
$_SESSION['negocio_id'] = $negocio_id;

$stmt = $pdo->prepare("
    SELECT n.*, e.eh_fundador
    FROM negocios n
    JOIN empreendedores e ON n.empreendedor_id = e.id
    WHERE n.id = ? AND n.empreendedor_id = ?
");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$negocio) {
    die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);
}

// ✅ CORRIGIDO: garante array mesmo sem registro
$stmt = $pdo->prepare("
    SELECT certidao_trabalhista_path, certidao_ambiental_path
    FROM negocios_documentos
    WHERE negocio_id = ?
");
$stmt->execute([$negocio_id]);
$docs = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'certidao_trabalhista_path' => null,
    'certidao_ambiental_path'   => null,
];

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container py-4 emp-inner" style="max-width:1200px;">

    <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="emp-page-title mb-1">
                Editar: <?= htmlspecialchars($negocio['nome_fantasia'] ?? '') ?>
            </h1>
            <p class="emp-page-subtitle mb-0">Etapa 9 — Documentação</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (!empty($negocio['inscricao_completa'])): ?>
                <a href="/negocios/confirmacao.php?id=<?= (int)$negocio_id ?>" class="btn-emp-outline">
                    <i class="bi bi-card-checklist me-1"></i> Voltar à Revisão
                </a>
            <?php endif; ?>
            <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline">
                <i class="bi bi-arrow-left me-1"></i> Meus Negócios
            </a>
        </div>
    </div>

    <?php if (!empty($_SESSION['errors_etapa9'])): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-circle-fill mt-1"></i>
            <ul class="mb-0 ps-2">
                <?php foreach ($_SESSION['errors_etapa9'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors_etapa9']); ?>
    <?php endif; ?>

    <form action="/negocios/processar_etapa9.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="editar">

        <div class="row g-4">

            <!-- COLUNA PRINCIPAL -->
            <div class="col-12 col-lg-8">

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-file-earmark-check"></i> Documentos obrigatórios
                    </div>

                    <p class="etapa9-subtitle mb-4">
                        Os arquivos devem ser enviados em PDF. Caso já exista um documento válido enviado anteriormente, você poderá mantê-lo.
                    </p>

                    <!-- Certidão Trabalhista -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i>
                            Certidão Negativa de Débitos Trabalhistas (CNDT) *
                        </label>
                        <p class="text-muted small mb-2">
                            Envie a CNDT em formato PDF, emitida recentemente (validade típica: 180 dias).
                        </p>

                        <?php if (!empty($docs['certidao_trabalhista_path'])): ?>
                            <div class="d-flex align-items-center gap-3 mb-2 p-2 rounded" style="background:#f0f4ed;border-left:3px solid #CDDE00;">
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                <div>
                                    <strong class="small d-block">Arquivo atual:</strong>
                                    <a href="<?= htmlspecialchars($docs['certidao_trabalhista_path']) ?>" target="_blank" class="small">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Ver CNDT atual
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning py-2 small mb-2">
                                <i class="bi bi-exclamation-triangle me-1"></i> Nenhuma CNDT enviada ainda.
                            </div>
                        <?php endif; ?>

                        <input
                            type="file"
                            name="certidao_trabalhista"
                            id="certidao_trabalhista_edit"
                            class="form-control"
                            accept="application/pdf"
                            <?= empty($docs['certidao_trabalhista_path']) ? 'required' : '' ?>
                        >
                        <div class="form-text">
                            ⚠️ Somente PDF. Máx. 5MB.
                            <?= !empty($docs['certidao_trabalhista_path']) ? 'Envie um novo arquivo apenas se precisar substituir o atual.' : '' ?>
                        </div>
                    </div>

                    <!-- Certidão Ambiental -->
                    <div class="mb-2">
                        <label class="form-label">
                            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i>
                            Certidão de Regularidade Ambiental *
                        </label>
                        <p class="text-muted small mb-2">
                            Envie o documento emitido pelo órgão ambiental do seu estado ou órgão federal competente.
                        </p>

                        <?php if (!empty($docs['certidao_ambiental_path'])): ?>
                            <div class="d-flex align-items-center gap-3 mb-2 p-2 rounded" style="background:#f0f4ed;border-left:3px solid #CDDE00;">
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                <div>
                                    <strong class="small d-block">Arquivo atual:</strong>
                                    <a href="<?= htmlspecialchars($docs['certidao_ambiental_path']) ?>" target="_blank" class="small">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Ver certidão atual
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning py-2 small mb-2">
                                <i class="bi bi-exclamation-triangle me-1"></i> Nenhuma certidão ambiental enviada ainda.
                            </div>
                        <?php endif; ?>

                        <input
                            type="file"
                            name="certidao_ambiental"
                            id="certidao_ambiental_edit"
                            class="form-control"
                            accept="application/pdf"
                            <?= empty($docs['certidao_ambiental_path']) ? 'required' : '' ?>
                        >
                        <div class="form-text">
                            ⚠️ Somente PDF. Máx. 5MB.
                            <?= !empty($docs['certidao_ambiental_path']) ? 'Envie um novo arquivo apenas se precisar substituir o atual.' : '' ?>
                        </div>
                    </div>
                </div>

            </div><!-- /col-lg-8 -->

            <!-- COLUNA LATERAL -->
            <div class="col-12 col-lg-4">

                <div class="emp-card mb-3">
                    <div class="emp-card-header"><i class="bi bi-info-circle"></i> Orientações</div>
                    <ul class="mb-0 ps-3" style="font-size:.82rem;color:#6c8070;line-height:1.7;">
                        <li>Ambos os documentos são <strong>obrigatórios</strong> para a inscrição ser considerada completa.</li>
                        <li>Envie apenas arquivos em formato <strong>PDF</strong> com no máximo <strong>5MB</strong> cada.</li>
                        <li>Se o documento já foi enviado e ainda é válido, não precisa reenviar.</li>
                        <li>A CNDT tem validade típica de <strong>180 dias</strong> — verifique se não expirou.</li>
                    </ul>
                </div>

                <div class="emp-card">
                    <div class="emp-card-header"><i class="bi bi-floppy-fill"></i> Salvar</div>
                    <p class="small mb-3" style="color:#9aab9d;">
                        Salve as alterações desta etapa. Os demais dados do negócio não serão afetados.
                    </p>
                    <button type="submit" class="btn-emp-primary w-100 justify-content-center mb-2">
                        <i class="bi bi-floppy me-2"></i> Salvar Alterações
                    </button>
                    <?php if (!empty($negocio['inscricao_completa'])): ?>
                        <a href="/negocios/confirmacao.php?id=<?= (int)$negocio_id ?>" class="btn-emp-outline w-100 justify-content-center mb-2">
                            <i class="bi bi-card-checklist me-1"></i> Voltar à Revisão
                        </a>
                    <?php endif; ?>
                    <a href="/negocios/editar_etapa8.php?id=<?= (int)$negocio_id ?>" class="btn-emp-outline w-100 justify-content-center mb-2">
                        <i class="bi bi-arrow-left me-2"></i> Etapa Anterior
                    </a>
                    <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline w-100 justify-content-center">
                        <i class="bi bi-arrow-left me-2"></i> Meus Negócios
                    </a>
                </div>

            </div><!-- /col-lg-4 -->
        </div><!-- /row -->
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const MB = 1024 * 1024;

    function validarPdf(input) {
        const file = input.files[0];
        if (!file) return;

        let feedbackEl = input.parentElement.querySelector('.upload-feedback');
        if (!feedbackEl) {
            feedbackEl = document.createElement('div');
            feedbackEl.className = 'upload-feedback mt-1';
            input.parentElement.appendChild(feedbackEl);
        }

        if (file.type !== 'application/pdf') {
            feedbackEl.innerHTML = `<div class="alert alert-danger py-2 px-3 mb-0 small">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Apenas arquivos PDF são aceitos.
            </div>`;
            input.value = '';
            return;
        }

        if (file.size > 5 * MB) {
            feedbackEl.innerHTML = `<div class="alert alert-danger py-2 px-3 mb-0 small">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                O arquivo excede 5MB (${(file.size / MB).toFixed(2)} MB).
            </div>`;
            input.value = '';
            return;
        }

        feedbackEl.innerHTML = `<div class="text-success small mt-1">
            <i class="bi bi-check-circle-fill me-1"></i>
            ${file.name} (${(file.size / MB).toFixed(2)} MB)
        </div>`;
    }

    document.getElementById('certidao_trabalhista_edit')?.addEventListener('change', function () { validarPdf(this); });
    document.getElementById('certidao_ambiental_edit')?.addEventListener('change',   function () { validarPdf(this); });
});
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>