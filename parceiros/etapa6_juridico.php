<?php
session_start();
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Progresso -->
            <div class="mb-4">
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span class="fw-bold text-success">Etapa 6: Área Jurídica e Finalização</span>
                    <span>6 de 6</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold text-dark mb-1">Últimos Detalhes</h3>
                    <p class="text-muted mb-4">Para gerar sua carta-acordo e finalizar o processo, faça o upload da sua identidade visual e aceite os termos da parceria.</p>

                    <?php if (isset($_SESSION['erro_etapa6'])): ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= htmlspecialchars($_SESSION['erro_etapa6']) ?></div>
                        </div>
                        <?php unset($_SESSION['erro_etapa6']); ?>
                    <?php endif; ?>

                    <!-- Importante: enctype para upload de arquivos -->
                    <form method="POST" action="processar_etapa6.php" enctype="multipart/form-data">
                    <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
    
                        <!-- UPLOADS DE MARCA -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Identidade Visual Institucional</h5>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Logomarca Oficial (PNG, JPG, SVG)</label>
                                <input type="file" name="logo" id="logo" class="form-control" accept=".png, .jpg, .jpeg, .svg">
                                <?php if (!empty($logo_atual)): ?>
                                    <div class="form-text text-success mt-1"><i class="bi bi-check-circle-fill"></i> Logomarca já enviada. <a href="<?= htmlspecialchars($logo_atual) ?>" target="_blank">Visualizar</a></div>
                                <?php else: ?>
                                    <div class="form-text">Recomendamos fundo transparente.</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Manual da Marca (Opcional - PDF)</label>
                                <input type="file" name="manual_marca" id="manual_marca" class="form-control" accept=".pdf">
                                <?php if (!empty($manual_atual)): ?>
                                    <div class="form-text text-success mt-1"><i class="bi bi-check-circle-fill"></i> Manual já enviado. <a href="<?= htmlspecialchars($manual_atual) ?>" target="_blank">Visualizar</a></div>
                                <?php endif; ?>
                            </div>
                        </div>

                       <!-- REDES SOCIAIS -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">Redes sociais oficiais</h5>
                        <p class="small text-muted mb-3">
                        Informe apenas perfis institucionais da organização. Esses links poderão aparecer no seu perfil público na plataforma.
                        </p>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-facebook text-primary me-1"></i> Facebook
                                </label>
                                <input type="url" name="facebook_url" class="form-control"
                                    placeholder="https://www.facebook.com/suaempresa"
                                    value="<?= htmlspecialchars($facebook_url) ?>">
                                <div class="form-text">Página oficial da organização no Facebook.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-instagram text-danger me-1"></i> Instagram
                                </label>
                                <input type="url" name="instagram_url" class="form-control"
                                    placeholder="https://www.instagram.com/suaempresa"
                                    value="<?= htmlspecialchars($instagram_url) ?>">
                                <div class="form-text">Perfil institucional no Instagram.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-linkedin text-primary me-1"></i> LinkedIn
                                </label>
                                <input type="url" name="linkedin_url" class="form-control"
                                    placeholder="https://www.linkedin.com/company/suaempresa"
                                    value="<?= htmlspecialchars($linkedin_url) ?>">
                                <div class="form-text">Página da empresa no LinkedIn.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-youtube text-danger me-1"></i> YouTube
                                </label>
                                <input type="url" name="youtube_url" class="form-control"
                                    placeholder="https://www.youtube.com/@suaempresa"
                                    value="<?= htmlspecialchars($youtube_url) ?>">
                                <div class="form-text">Canal oficial da organização no YouTube.</div>
                            </div>
                        </div>

                        <!-- AUTORIZAÇÃO DE USO DE IMAGEM/MARCA -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="autoriza_marca" id="autoriza_marca"
                                value="1" <?= $autoriza_marca ? 'checked' : '' ?> required>
                            <label class="form-check-label fw-bold" for="autoriza_marca">
                                Declaro estar ciente e concordo com o uso da logomarca, banners, imagens, voz e textos 
                                disponibilizados pela organização para fins institucionais da parceria Impactos Positivos.
                            </label>
                        </div>



                        <!-- TERMOS LEGAIS -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">Termos de Parceria</h5>
                        
                        <div class="bg-light p-4 rounded border mb-4">
                            <p class="small text-muted mb-3">
                                Ao prosseguir, sua organização concorda com os princípios e valores da Plataforma Impactos Positivos. Os dados preenchidos ao longo deste cadastro serão utilizados para gerar, automaticamente, o documento de formalização (Carta-Acordo) que regerá nossa relação institucional e comercial.
                            </p>
                            
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="termos_aceitos" id="termos_aceitos" value="1" <?= $termos_aceitos ? 'checked' : '' ?> required>
                                <label class="form-check-label fw-bold" for="termos_aceitos">
                                    Declaro que sou representante legal ou tenho autorização para assinar em nome da organização, e aceito os Termos de Parceria. *
                                </label>
                            </div>
                        </div>
                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <?php if (($_GET['from'] ?? '') === 'confirmacao'): ?>
                                <button type="submit" name="acao" value="confirmacao" class="btn btn-outline-primary">
                                    Salvar e voltar à revisão
                                </button>
                            <?php endif; ?>
                            <a href="etapa5_plataforma.php" class="btn btn-outline-secondary btn-lg fw-bold"><i class="bi bi-arrow-left me-2"></i> Voltar</a>
                            <button type="submit" class="btn btn-success btn-lg px-5 fw-bold"><i class="bi bi-check2-circle me-2"></i> Finalizar Cadastro</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
