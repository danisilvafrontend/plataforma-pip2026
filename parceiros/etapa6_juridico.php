<?php
session_start();
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php?msg=login_necessario");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Busca os dados do contrato para ver se já há arquivos salvos e se os termos foram aceitos
$stmt = $pdo->prepare("
    SELECT logo_url, manual_marca_url, termos_aceitos,
           facebook_url, instagram_url, linkedin_url, youtube_url, autoriza_marca
    FROM parceiro_contrato
    WHERE parceiro_id = ?
");
$stmt->execute([$parceiro_id]);
$contrato = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$logo_atual     = $contrato['logo_url'] ?? '';
$manual_atual   = $contrato['manual_marca_url'] ?? '';
$termos_aceitos = $contrato['termos_aceitos'] ?? 0;

$facebook_url   = $contrato['facebook_url']  ?? '';
$instagram_url  = $contrato['instagram_url'] ?? '';
$linkedin_url   = $contrato['linkedin_url']  ?? '';
$youtube_url    = $contrato['youtube_url']   ?? '';
$autoriza_marca = $contrato['autoriza_marca'] ?? 0;


include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5 parceiro-step-shell">
    <div class="parceiro-step-top mb-4 mb-lg-5">
        <div class="parceiro-step-progress-card parceiro-step-progress-card-final">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <span class="parceiro-step-kicker text-success">Etapa 6 de 6</span>
                    <h1 class="parceiro-step-title mb-1">Área Jurídica e Finalização</h1>
                    <p class="parceiro-step-subtitle mb-0">
                        Envie a identidade visual da organização, informe os canais institucionais e confirme as autorizações necessárias para concluir o cadastro.
                    </p>
                </div>
                <div class="parceiro-step-indicator parceiro-step-indicator-success">100%</div>
            </div>

            <div class="progress parceiro-step-progress" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-success" style="width: 100%;"></div>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <aside class="parceiro-step-aside">
                <div class="parceiro-step-aside-card">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-shield-check"></i>
                        Etapa final
                    </div>
                    <ul class="parceiro-step-aside-list">
                        <li>Envie a logomarca oficial e, se desejar, o manual da marca.</li>
                        <li>Cadastre os perfis institucionais da organização nas redes sociais.</li>
                        <li>Confirme a autorização de uso de marca e o aceite dos termos da parceria.</li>
                    </ul>
                </div>

                <div class="parceiro-step-aside-card parceiro-step-aside-highlight parceiro-step-aside-highlight-success">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-file-earmark-text-fill"></i>
                        Formalização
                    </div>
                    <p class="mb-0">
                        Essas informações serão usadas na geração da carta-acordo e na apresentação institucional da parceria dentro da plataforma.
                    </p>
                </div>
            </aside>
        </div>

        <div class="col-lg-8">
            <div class="parceiro-step-card">
                <div class="parceiro-step-card-header">
                    <div>
                        <h2 class="parceiro-step-card-title mb-1">Últimos detalhes</h2>
                        <p class="parceiro-step-card-subtitle mb-0">
                            Para finalizar o processo, envie os arquivos institucionais e confirme as autorizações necessárias.
                        </p>
                    </div>
                </div>

                <div class="parceiro-step-card-body">
                    <?php if (isset($_SESSION['erro_etapa6'])): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 parceiro-step-alert">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div><?= htmlspecialchars($_SESSION['erro_etapa6']) ?></div>
                        </div>
                        <?php unset($_SESSION['erro_etapa6']); ?>
                    <?php endif; ?>

                    <form method="POST" action="processar_etapa6.php" enctype="multipart/form-data">
                        <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Identidade Visual Institucional</h3>
                                <p class="parceiro-step-section-text">
                                    Envie os arquivos principais da marca para uso institucional e composição da documentação da parceria.
                                </p>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="parceiro-upload-card">
                                        <label class="form-label parceiro-step-label">Logomarca Oficial (PNG, JPG, SVG)</label>
                                        <input type="file" name="logo" id="logo" class="form-control" accept=".png, .jpg, .jpeg, .svg">

                                        <?php if (!empty($logo_atual)): ?>
                                            <div class="form-text text-success mt-2">
                                                <i class="bi bi-check-circle-fill me-1"></i>
                                                Logomarca já enviada.
                                                <a href="<?= htmlspecialchars($logo_atual) ?>" target="_blank" rel="noopener noreferrer">Visualizar</a>
                                            </div>
                                        <?php else: ?>
                                            <div class="form-text mt-2">Recomendamos arquivo com fundo transparente.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="parceiro-upload-card">
                                        <label class="form-label parceiro-step-label">Manual da Marca (opcional - PDF)</label>
                                        <input type="file" name="manual_marca" id="manual_marca" class="form-control" accept=".pdf">

                                        <?php if (!empty($manual_atual)): ?>
                                            <div class="form-text text-success mt-2">
                                                <i class="bi bi-check-circle-fill me-1"></i>
                                                Manual já enviado.
                                                <a href="<?= htmlspecialchars($manual_atual) ?>" target="_blank" rel="noopener noreferrer">Visualizar</a>
                                            </div>
                                        <?php else: ?>
                                            <div class="form-text mt-2">Envie o guia institucional da marca, caso exista.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Redes sociais oficiais</h3>
                                <p class="parceiro-step-section-text">
                                    Informe apenas perfis institucionais da organização. Esses links poderão aparecer no perfil público da plataforma.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="parceiro-social-field">
                                        <label class="form-label parceiro-step-label">
                                            <i class="bi bi-facebook me-1 text-primary"></i> Facebook
                                        </label>
                                        <input
                                            type="url"
                                            name="facebook_url"
                                            class="form-control"
                                            placeholder="https://www.facebook.com/suaempresa"
                                            value="<?= htmlspecialchars($facebook_url) ?>"
                                        >
                                        <div class="form-text">Página oficial da organização no Facebook.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="parceiro-social-field">
                                        <label class="form-label parceiro-step-label">
                                            <i class="bi bi-instagram me-1 text-danger"></i> Instagram
                                        </label>
                                        <input
                                            type="url"
                                            name="instagram_url"
                                            class="form-control"
                                            placeholder="https://www.instagram.com/suaempresa"
                                            value="<?= htmlspecialchars($instagram_url) ?>"
                                        >
                                        <div class="form-text">Perfil institucional no Instagram.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="parceiro-social-field">
                                        <label class="form-label parceiro-step-label">
                                            <i class="bi bi-linkedin me-1 text-primary"></i> LinkedIn
                                        </label>
                                        <input
                                            type="url"
                                            name="linkedin_url"
                                            class="form-control"
                                            placeholder="https://www.linkedin.com/company/suaempresa"
                                            value="<?= htmlspecialchars($linkedin_url) ?>"
                                        >
                                        <div class="form-text">Página oficial da empresa no LinkedIn.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="parceiro-social-field">
                                        <label class="form-label parceiro-step-label">
                                            <i class="bi bi-youtube me-1 text-danger"></i> YouTube
                                        </label>
                                        <input
                                            type="url"
                                            name="youtube_url"
                                            class="form-control"
                                            placeholder="https://www.youtube.com/@suaempresa"
                                            value="<?= htmlspecialchars($youtube_url) ?>"
                                        >
                                        <div class="form-text">Canal oficial da organização no YouTube.</div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-highlight-box parceiro-step-highlight-box-success">
                                <div class="parceiro-step-highlight-head">
                                    <div class="parceiro-step-highlight-icon parceiro-step-highlight-icon-success">
                                        <i class="bi bi-building-check"></i>
                                    </div>
                                    <div>
                                        <h3 class="parceiro-step-section-title mb-1">Autorização de uso de marca e materiais</h3>
                                        <p class="parceiro-step-section-text mb-0">
                                            Confirme que a plataforma pode utilizar a identidade visual e os materiais institucionais enviados para fins relacionados à parceria.
                                        </p>
                                    </div>
                                </div>

                                <div class="parceiro-step-toggle-box mt-4">
                                    <div class="form-check m-0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="autoriza_marca"
                                            id="autoriza_marca"
                                            value="1"
                                            <?= $autoriza_marca ? 'checked' : '' ?>
                                            required
                                        >
                                        <label class="form-check-label fw-semibold" for="autoriza_marca">
                                            Declaro estar ciente e concordo com o uso da logomarca, banners, imagens, voz e textos disponibilizados pela organização para fins institucionais da parceria Impactos Positivos.
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Termos de Parceria</h3>
                                <p class="parceiro-step-section-text">
                                    Ao prosseguir, os dados deste cadastro poderão ser usados para a geração automática da carta-acordo de formalização.
                                </p>
                            </div>

                            <div class="parceiro-step-highlight-box parceiro-step-highlight-box-neutral">
                                <p class="small text-muted mb-3">
                                    Ao prosseguir, sua organização concorda com os princípios e valores da Plataforma Impactos Positivos. Os dados preenchidos ao longo deste cadastro serão utilizados para gerar, automaticamente, o documento de formalização (Carta-Acordo) que regerá nossa relação institucional e comercial.
                                </p>

                                <div class="parceiro-step-toggle-box">
                                    <div class="form-check m-0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="termos_aceitos"
                                            id="termos_aceitos"
                                            value="1"
                                            <?= $termos_aceitos ? 'checked' : '' ?>
                                            required
                                        >
                                        <label class="form-check-label fw-semibold" for="termos_aceitos">
                                            Declaro que sou representante legal ou tenho autorização para assinar em nome da organização, e aceito os Termos de Parceria.
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="parceiro-step-actions">
                            <?php if (($_GET['from'] ?? '') === 'confirmacao'): ?>
                                <button type="submit" name="acao" value="confirmacao" class="btn btn-outline-primary">
                                    Salvar e voltar à revisão
                                </button>
                            <?php endif; ?>

                            <a href="etapa5_plataforma.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Voltar
                            </a>

                            <button type="submit" class="btn btn-success btn-lg px-4 fw-bold">
                                <i class="bi bi-check2-circle me-2"></i>Finalizar cadastro
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
