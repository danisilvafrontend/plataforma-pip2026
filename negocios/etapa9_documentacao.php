<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

<div class="container my-5">
    <h1 class="mb-4">Etapa 9 - Documentação</h1>

    <?php
        $etapaAtual = 9;
        include __DIR__ . '/../app/views/partials/progress.php';
        include __DIR__ . '/../app/views/partials/intro_text_documentacao.php';
    ?>

    <?php if (!empty($_SESSION['erro_etapa9'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['erro_etapa9']) ?>
        </div>
        <?php unset($_SESSION['erro_etapa9']); ?>
    <?php endif; ?>

    <form method="POST" action="processar_etapa9.php" enctype="multipart/form-data" class="mt-4">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="cadastro">

        <div class="mb-4">
            <label class="form-label fw-semibold">
                Certidão Negativa de Débitos Trabalhistas (PDF)
                <span class="text-danger">*</span>
            </label>
            <?php if (!empty($docs['certidao_trabalhista_path'])): ?>
                <div class="mb-2 small">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                    Arquivo já enviado:
                    <a href="<?= htmlspecialchars($docs['certidao_trabalhista_path']) ?>" target="_blank">
                        Ver CNDT atual
                    </a>
                </div>
            <?php endif; ?>
            <input type="file"
                    name="certidao_trabalhista"
                    class="form-control"
                    accept="application/pdf">
            <div class="form-text small">
                Envie a CNDT em formato PDF, emitida recentemente (validade típica: 180 dias).
                Se já enviada e válida, você pode manter o arquivo atual.
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold">
                Certidão de Regularidade Ambiental (PDF)
                <span class="text-danger">*</span>
            </label>
            <?php if (!empty($docs['certidao_ambiental_path'])): ?>
                <div class="mb-2 small">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                    Arquivo já enviado:
                    <a href="<?= htmlspecialchars($docs['certidao_ambiental_path']) ?>" target="_blank">
                        Ver certidão atual
                    </a>
                </div>
            <?php endif; ?>
            <input type="file"
                    name="certidao_ambiental"
                    class="form-control"
                    accept="application/pdf">
            <div class="form-text small">
                Envie o documento emitido pelo órgão ambiental do seu estado ou órgão federal competente.
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
            <a href="etapa8_visao.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Voltar
            </a>
            <button type="submit" class="btn btn-primary">
                Salvar e ir para revisão
                <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>

    </form>
        
</div>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>
