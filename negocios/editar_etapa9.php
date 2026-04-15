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

// Busca dados atuais para preencher (OBRIGATÓRIO para edição)
$stmt = $pdo->prepare("
    SELECT certidao_trabalhista_path, certidao_ambiental_path
    FROM negocios_documentos
    WHERE negocio_id = ?
");
$stmt->execute([$negocio_id]);
$docs = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$docs) {
    $_SESSION['erro_etapa9'] = "Documentação não encontrada. Faça o cadastro primeiro.";
    header("Location: etapa9_documentacao.php");
    exit;
}

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container py-4" style="max-width: 1100px;">

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

    <?php if (!empty($_SESSION['erro_etapa9'])): ?>
        <div class="alert alert-danger mt-4">
            <?= htmlspecialchars($_SESSION['erro_etapa9']) ?>
        </div>
        <?php unset($_SESSION['erro_etapa9']); ?>
    <?php endif; ?>

    <form method="POST" action="processar_etapa9.php" enctype="multipart/form-data" class="mt-4">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="editar">

        <div class="row g-4">
            <div class="col-12 col-lg-8">

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-file-earmark-check"></i> Documentos obrigatórios
                    </div>

                    <p class="etapa9-subtitle mb-4">
                        Substitua os arquivos apenas se precisar atualizar a documentação já enviada.
                    </p>

                    <div class="etapa9-doc-grid">

                        <div class="etapa9-doc-card">
                            <div class="etapa9-doc-header">
                                <h3 class="etapa9-doc-title">
                                    Certidão Negativa de Débitos Trabalhistas (PDF)
                                    <span class="text-danger">*</span>
                                </h3>
                                <p class="etapa9-doc-text">
                                    Envie novo PDF apenas se desejar substituir o documento atual.
                                </p>
                            </div>

                            <div class="etapa9-doc-current">
                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                Arquivo atual:
                                <a href="<?= htmlspecialchars($docs['certidao_trabalhista_path']) ?>"
                                   target="_blank"
                                   class="fw-semibold">
                                    <?= htmlspecialchars(basename($docs['certidao_trabalhista_path'])) ?>
                                </a>
                                <div class="small text-muted mt-1">(Clique para visualizar)</div>
                            </div>

                            <input type="file"
                                   name="certidao_trabalhista"
                                   class="form-control etapa9-file-input"
                                   accept="application/pdf">

                            <div class="form-text small mt-2">
                                Deixe em branco para <strong>manter o arquivo atual</strong>.
                            </div>
                        </div>

                        <div class="etapa9-doc-card">
                            <div class="etapa9-doc-header">
                                <h3 class="etapa9-doc-title">
                                    Certidão de Regularidade Ambiental (PDF)
                                    <span class="text-danger">*</span>
                                </h3>
                                <p class="etapa9-doc-text">
                                    Envie novo PDF apenas se desejar substituir o documento atual.
                                </p>
                            </div>

                            <div class="etapa9-doc-current">
                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                Arquivo atual:
                                <a href="<?= htmlspecialchars($docs['certidao_ambiental_path']) ?>"
                                   target="_blank"
                                   class="fw-semibold">
                                    <?= htmlspecialchars(basename($docs['certidao_ambiental_path'])) ?>
                                </a>
                                <div class="small text-muted mt-1">(Clique para visualizar)</div>
                            </div>

                            <input type="file"
                                   name="certidao_ambiental"
                                   class="form-control etapa9-file-input"
                                   accept="application/pdf">

                            <div class="form-text small mt-2">
                                Deixe em branco para <strong>manter o arquivo atual</strong>.
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <div class="col-12 col-lg-4">
                <div class="etapa9-sticky-side">

                    <div class="emp-card mb-4">
                        <div class="emp-card-header">
                            <i class="bi bi-info-circle"></i> Orientações
                        </div>

                        <p class="small text-muted mb-2">
                            Revise os documentos enviados e substitua apenas se houver versão mais recente ou necessidade de correção.
                        </p>

                        <p class="small text-muted mb-0">
                            Os arquivos devem permanecer em PDF e preferencialmente dentro do prazo de validade exigido.
                        </p>
                    </div>

                    <div class="emp-card">
                        <div class="emp-card-header">
                            <i class="bi bi-floppy"></i> Ações
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn-emp-primary w-100 justify-content-center">
                                <i class="bi bi-floppy me-1"></i> Salvar Alterações
                            </button>

                            <?php if (!empty($negocio['inscricao_completa'])): ?>
                                <a href="/negocios/confirmacao.php?id=<?= (int)$negocio_id ?>" class="btn-emp-outline w-100 justify-content-center">
                                    <i class="bi bi-card-checklist me-1"></i> Voltar à Revisão
                                </a>
                            <?php endif; ?>

                            <a href="/negocios/editar_etapa8.php?id=<?= $negocio_id ?>" class="btn-emp-outline w-100 justify-content-center">
                                <i class="bi bi-arrow-left me-1"></i> Etapa Anterior
                            </a>

                            <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline w-100 justify-content-center">
                                <i class="bi bi-grid me-1"></i> Meus Negócios
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>