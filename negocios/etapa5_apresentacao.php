<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
$pageTitle = 'Etapa 5 — Apresentação do Negócio';
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

$stmt = $pdo->prepare("
    SELECT n.*, e.eh_fundador 
    FROM negocios n 
    JOIN empreendedores e ON n.empreendedor_id = e.id 
    WHERE n.id = ? AND n.empreendedor_id = ?
");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$negocio) die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);

// ✅ CORRIGIDO: busca dados salvos para repopulação
$stmt = $pdo->prepare("SELECT * FROM negocio_apresentacao WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$apresentacao = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$galeriaExistente = json_decode($apresentacao['galeria_imagens'] ?? '[]', true);

$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container my-5 emp-inner">

    <?php
        $etapaAtual = 5;
        include __DIR__ . '/../app/views/partials/progress.php';
        include __DIR__ . '/../app/views/partials/intro_text_apresentacao_negocios.php';
    ?>

    <?php if (!empty($_SESSION['errors_etapa5'])): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-circle-fill mt-1"></i>
            <ul class="mb-0 ps-2">
                <?php foreach ($_SESSION['errors_etapa5'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors_etapa5']); ?>
    <?php endif; ?>

    <form action="/negocios/processar_etapa5.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="cadastro">

        <!-- ══════════════════════════════════════════════════
             SEÇÃO 1 — Identidade Visual e Uploads Principais
        ═══════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-image"></i> Identidade Visual e Uploads Principais</div>

            <!-- Logotipo -->
            <div class="mb-4">
                <label class="form-label">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Logotipo do negócio *
                </label>
                <?php if (!empty($apresentacao['logo_negocio'])): ?>
                    <div class="mb-2">
                        <img src="<?= htmlspecialchars($apresentacao['logo_negocio']) ?>" alt="Logo atual" style="max-height:80px;" class="border rounded p-1">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="remover_logo" value="1" id="removerLogo">
                            <label class="form-check-label small text-danger" for="removerLogo">Remover logo atual</label>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" name="logo_negocio" id="logo_negocio" class="form-control"
                    accept="image/png,image/jpeg,image/jpg,image/webp"
                    <?= empty($apresentacao['logo_negocio']) ? 'required' : '' ?>>
                <div class="form-text">
                    Envie o logotipo oficial da sua empresa/negócio.<br>
                    ⚠️ Máx. 50MB. Recomendação: imagem quadrada (ex.: 500×500px) em formato PNG, JPG, JPEG ou WebP.
                </div>
            </div>

            <!-- Imagem de Destaque -->
            <div class="mb-2">
                <label class="form-label d-flex align-items-center gap-2">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Imagem de Destaque
                    <span class="badge" style="background:#CDDE00;color:#1E3425;font-size:.7rem;">Capa da Vitrine</span>
                    <span class="badge" style="background:rgba(151,163,39,.15);color:#5c6318;font-size:.7rem;">Recomendado</span>
                    <small class="text-muted">(opcional)</small>
                </label>

                <div class="destaque-info-box mb-3">
                    <i class="bi bi-layout-text-window-reverse destaque-info-icon"></i>
                    <div>
                        <strong>Esta será a capa do seu negócio na Vitrine Nacional</strong>
                        <p class="mb-0 small">Escolha uma imagem que represente bem seu negócio — ela será exibida em destaque para todos os visitantes da plataforma. Proporção recomendada: <strong>16:9</strong> (ex: 1280×720px). Máximo 5MB.</p>
                    </div>
                </div>

                <?php if (!empty($apresentacao['imagem_destaque'])): ?>
                <div class="destaque-preview-wrap mb-3" id="destaque-preview-atual">
                    <img src="<?= htmlspecialchars($apresentacao['imagem_destaque']) ?>"
                         alt="Imagem de Destaque atual" class="destaque-preview-img">
                    <div class="destaque-preview-overlay">
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Capa atual</span>
                    </div>
                    <label class="destaque-remover-btn">
                        <input type="checkbox" name="remover_imagem_destaque" value="1" class="d-none" id="removerImagemDestaque">
                        <span id="removerDestaqueLabel"><i class="bi bi-trash me-1"></i> Remover capa</span>
                    </label>
                </div>
                <?php endif; ?>

                <div class="upload-area-destaque" id="uploadAreaDestaque">
                    <input type="file" name="imagem_destaque" id="imagemDestaque"
                           accept="image/png,image/jpeg,image/jpg,image/webp" class="d-none">
                    <label for="imagemDestaque" class="upload-label-destaque" id="uploadLabelDestaque">
                        <i class="bi bi-cloud-arrow-up-fill upload-icon-destaque"></i>
                        <span class="upload-text-main">Clique para selecionar a imagem de capa</span>
                        <span class="upload-text-sub">JPG, PNG ou WebP · Máx. 5MB · Proporção 16:9 recomendada</span>
                    </label>
                    <div id="novoDestaquePreview" class="d-none mt-3 text-center">
                        <img id="novoDestaqueImg" src="#" alt="Preview" class="destaque-preview-img">
                        <p class="small text-success mt-2"><i class="bi bi-check-circle me-1"></i> Nova imagem selecionada</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             SEÇÃO 2 — Apresentação do Negócio
        ═══════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-chat-quote"></i> Apresentação do Negócio</div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Descreva seu negócio em uma frase (até 120 caracteres) *
                </label>
                <input type="text" name="frase_negocio" class="form-control" maxlength="120" required
                    value="<?= htmlspecialchars($apresentacao['frase_negocio'] ?? '') ?>">
                <div class="form-text">Exemplo: Plataforma que conecta pessoas, negócios e instituições...</div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-fill lbl-pub me-1"></i> Qual problema você resolve? (até 200 caracteres) *
                    </label>
                    <textarea name="problema_resolvido" class="form-control" maxlength="200" rows="4" required><?= htmlspecialchars($apresentacao['problema_resolvido'] ?? '') ?></textarea>
                    <div class="form-text">Descreva a dor ou desafio do seu público-alvo.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-fill lbl-pub me-1"></i> Qual solução você oferece? (até 200 caracteres) *
                    </label>
                    <textarea name="solucao_oferecida" class="form-control" maxlength="200" rows="4" required><?= htmlspecialchars($apresentacao['solucao_oferecida'] ?? '') ?></textarea>
                    <div class="form-text">Descreva como seu produto/serviço resolve o problema acima.</div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             SEÇÃO 3 — Materiais e Mídia
        ═══════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-play-circle"></i> Materiais e Mídia</div>

            <!-- Vídeo Pitch -->
            <div class="mb-4">
                <label class="form-label">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Vídeo Pitch — até 3 minutos (YouTube) <small>(opcional)</small>
                </label>
                <input type="url" name="video_pitch_url" class="form-control"
                    placeholder="Cole aqui a URL do YouTube"
                    pattern="^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[A-Za-z0-9_-]{11}$"
                    value="<?= htmlspecialchars($apresentacao['video_pitch_url'] ?? '') ?>"
                    >
                <div class="form-text">
                    Exemplo válido: https://www.youtube.com/watch?v=XXXXXXXXXXX<br>
                    Esse vídeo será sua apresentação na vitrine de negócios e para a premiação.
                </div>
            </div>

            <!-- PDF + Vídeo Institucional -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-fill lbl-pub me-1"></i> Apresentação institucional (PDF) <small class="text-muted">(opcional)</small>
                    </label>
                    <?php if (!empty($apresentacao['apresentacao_pdf'])): ?>
                        <div class="mb-2">
                            <a href="<?= htmlspecialchars($apresentacao['apresentacao_pdf']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Ver PDF atual
                            </a>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="remover_pdf" value="1" id="removerPdf">
                                <label class="form-check-label small text-danger" for="removerPdf">Remover PDF atual</label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="apresentacao_pdf" class="form-control" accept=".pdf">
                    <div class="form-text">
                        Upload de material explicativo sobre sua solução, trajetória e impacto.<br>
                        ⚠️ Máx. 5MB.
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-fill lbl-pub me-1"></i> Vídeo institucional (YouTube) <small class="text-muted">(opcional)</small>
                    </label>
                    <input type="url" name="apresentacao_video_url" class="form-control"
                        placeholder="Cole aqui a URL do YouTube"
                        pattern="^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[A-Za-z0-9_-]{11}$"
                        value="<?= htmlspecialchars($apresentacao['apresentacao_video_url'] ?? '') ?>">
                    <div class="form-text">Somente vídeos do YouTube são aceitos.</div>
                </div>
            </div>

            <!-- Galeria -->
            <div>
                <label class="form-label">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Galeria de imagens do negócio <small class="text-muted">(opcional)</small>
                </label>
                <?php if (!empty($galeriaExistente)): ?>
                    <div class="row g-2 mb-3">
                        <?php foreach ($galeriaExistente as $idx => $imgUrl): ?>
                        <div class="col-6 col-md-3 text-center">
                            <img src="<?= htmlspecialchars($imgUrl) ?>" class="img-thumbnail mb-1" style="max-height:100px;">
                            <div class="form-check d-flex justify-content-center">
                                <input class="form-check-input me-1" type="checkbox" name="remover_imagem[]" value="<?= $idx ?>">
   