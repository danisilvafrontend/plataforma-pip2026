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

// Busca dados do negócio
$stmt = $pdo->prepare("SELECT * FROM negocios WHERE id = ? AND empreendedor_id = ?");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$negocio) {
    die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);
}

// Busca dados atuais para preencher (caso já tenha enviado algo)
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

    <?php if (!empty($_SESSION['erro_etapa9'])): ?>
        <div class="alert alert-danger mt-4">
            <?= htmlspecialchars($_SESSION['erro_etapa9']) ?>
        </div>
        <?php unset($_SESSION['erro_etapa9']); ?>
    <?php endif; ?>

    <form method="POST" action="processar_etapa9.php" enctype="multipart/form-data" class="mt-4">
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

                <div class="etapa9-doc-card">
                    <div class="etapa9-doc-header">
                        <h3 class="etapa9-doc-title">
                            Certidão Negativa de Débitos Trabalhistas (PDF)
                            <span class="text-danger">*</span>
                        </h3>
                        <p class="etapa9-doc-text">
                            Envie a CNDT em formato PDF, emitida recentemente (validade típica: 180 dias).
                        </p>
                    </div>

                    <?php if (!empty($docs['certidao_trabalhista_path'])): ?>
                        <div class="etapa9-doc-current">
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
                        class="form-control etapa9-file-input"
                        accept="application/pdf"
                    >

                    <div class="form-text small mt-2">
                        Se já enviada e válida, você pode manter o arquivo atual.
                    </div>
                </div>

                <div class="etapa9-doc-card">
                    <div class="etapa9-doc-header">
                        <h3 class="etapa9-doc-title">
                            Certidão de Regularidade Ambiental (PDF)
                            <span class="text-danger">*</span>
                        </h3>
                        <p class="etapa9-doc-text">
                            Envie o documento emitido pelo órgão ambiental do seu estado ou órgão federal competente.
                        </p>
                    </div>

                    <?php if (!empty($docs['certidao_ambiental_path'])): ?>
                        <div class="etapa9-doc-current">
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
                        class="form-control etapa9-file-input"
                        accept="application/pdf"
                    >
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
                <a href="etapa8_visao.php" class="btn-emp-outline">
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

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>