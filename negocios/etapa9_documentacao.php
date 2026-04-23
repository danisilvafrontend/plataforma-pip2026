<?php
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

$negocio_id = (int)($_GET['id'] ?? $_SESSION['negocio_id'] ?? 0);
if ($negocio_id === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}
$_SESSION['negocio_id'] = $negocio_id;

$stmt = $pdo->prepare("SELECT * FROM negocios WHERE id = ? AND empreendedor_id = ?");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$negocio) {
    die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);
}

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

<div class="container py-4" style="max-width: 980px;">

    <div class="mb-4">
        <h1 class="emp-page-title mb-1">Etapa 9 — Documentação</h1>
        <p class="emp-page-subtitle mb-0">
            Envie os documentos obrigatórios para concluir o cadastro e seguir para a revisão final.
        </p>
    </div>

    <?php
        $etapaAtual = 9;
        include __DIR__ . '/../app/views/partials/progress.php';
        include __DIR__ . '/../app/views/partials/intro_text_documentacao.php';
    ?>

    <?php if (!empty($_SESSION['errors_etapa9'])): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2 mt-4">
            <i class="bi bi-exclamation-circle-fill mt-1"></i>
            <ul class="mb-0 ps-2">
                <?php foreach ($_SESSION['errors_etapa9'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors_etapa9']); ?>
    <?php endif; ?>

    <form method="POST" action="/negocios/processar_etapa9.php" enctype="multipart/form-data" class="mt-4">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="cadastro">

        <div class="form-section mb-4">
            <div class="form-section-title">
                <i class="bi bi-file-earmark-check"></i> Documentos obrigatórios
            </div>

            <p class="etapa9-subtitle mb-4">
                Os arquivos devem ser enviados em PDF. Caso já exista um documento válido enviado anteriormente, você poderá mantê-lo.
            </p>

            <div class="etapa9-doc-grid">

                <!-- Certidão Trabalhista -->
                <div class="etapa9-doc-card">
                    <div class="etapa9-doc-header">
                        <h3 class="etapa9-doc-title">
                            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i>
                            Certidão Negativa de Débitos Trabalhistas (CNDT) *
                        </h3>
                        <p class="etapa9-doc-text">
                            Envie a CNDT em formato PDF, emitida recentemente (validade típica: 180 dias).
                        </p>
                    </div>

                    <?php if (!empty($docs['certidao_trabalhista_path'])): ?>
                        <div class="etapa9-doc-current mb-2">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                            Arquivo já enviado:
                            <a href="<?= htmlspecialchars($docs['certidao_trabalhista_path']) ?>" target="_blank">
                                Ver CNDT atual
                            </a>
                        </div>
                    <?php endif; ?>

                    <input
                        type="file"
                        name="certidao_trabalhista"
                        id="certidao_trabalhista"
                        class="form-control etapa9-file-input"
                        accept="application/pdf"
                        <?= empty($docs['certidao_trabalhista_path']) ? 'required' : '' ?>
                    >
                    <div class="form-text small mt-2">
                        ⚠️ Somente PDF. Máx. 5MB.
                        <?= !empty($docs['certidao_trabalhista_path']) ? 'Se já enviada e válida, você pode manter o arquivo atual.' : '' ?>
                    </div>
                </div>

                <!-- Certidão Ambiental -->
                <div class="etapa9-doc-card">
                    <div class="etapa9-doc-header">
                        <h3 class="etapa9-doc-title">
                            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i>
                            Certidão de Regularidade Ambiental *
                        </h3>
                        <p class="etapa9-doc-text">
                            Envie o documento emitido pelo órgão ambiental do seu estado ou órgão federal competente.
                        </p>
                    </div>

                    <?php if (!empty($docs['certidao_ambiental_path'])): ?>
                        <div class="etapa9-doc-current mb-2">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                            Arquivo já enviado:
                            <a href="<?= htmlspecialchars($docs['certidao_ambiental_path']) ?>" target="_blank">
                                Ver certidão atual
                            </a>
                        </div>
                    <?php endif; ?>

                    <input
                        type="file"
                        name="certidao_ambiental"
                        id="certidao_ambiental"
                        class="form-control etapa9-file-input"
                        accept="application/pdf"
                        <?= empty($docs['certidao_ambiental_path']) ? 'required' : '' ?>
                    >
                    <div class="form-text small mt-2">
                        ⚠️ Somente PDF. Máx. 5MB.
                        <?= !empty($docs['certidao_ambiental_path']) ? 'Se já enviada e válida, você pode manter a certidão atual.' : '' ?>
                    </div>
                </div>

            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-shield-check"></i> Finalização
            </div>

            <p class="etapa9-subtitle mb-4">
                Após salvar esta etapa, o cadastro seguirá para a revisão final.
            </p>

            <div class="d-flex justify-content-between flex-wrap gap-2">
                <a href="/negocios/etapa8_apresentacao.php?id=<?= $negocio_id ?>" class="btn-emp-outline">
                    <i class="bi bi-arrow-left me-1"></i> Voltar
                </a>

                <button type="submit" class="btn-emp-primary">
                    Salvar e ir para revisão
                    <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>

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

    document.getElementById('certidao_trabalhista')?.addEventListener('change', function () { validarPdf(this); });
    document.getElementById('certidao_ambiental')?.addEventListener('change',   function () { validarPdf(this); });
});
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>