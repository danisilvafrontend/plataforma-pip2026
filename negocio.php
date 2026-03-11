<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexão com banco
$config = require __DIR__ . '/app/config/db.php';
require_once __DIR__ . '/negocios/blocos-cadastros/_shared.php';

$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Recebe id do negócio
$negocio_id = (int)($_GET['id'] ?? 0);
if ($negocio_id <= 0) {
    die("Negócio inválido.");
}

// Busca dados principais
$stmt = $pdo->prepare("SELECT * FROM negocios WHERE id = ? AND publicado_vitrine = 1");
$stmt->execute([$negocio_id]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$negocio) die("Negócio não encontrado ou não publicado.");

// Busca apresentação
$stmt = $pdo->prepare("SELECT * FROM negocio_apresentacao WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$apresentacao = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Busca eixo
$stmt = $pdo->prepare("SELECT nome as eixo_nome FROM eixos_tematicos WHERE id = ?");
$stmt->execute([$negocio['eixo_principal_id']]);
$eixo_principal = $stmt->fetch(PDO::FETCH_ASSOC);

// Busca ODS
$stmt = $pdo->prepare("SELECT icone_url FROM ods WHERE id = ?");
$stmt->execute([$negocio['ods_prioritaria_id']]);
$ods_prioritaria = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT o.icone_url FROM ods o INNER JOIN negocio_ods no ON o.id = no.ods_id WHERE no.negocio_id = ?");
$stmt->execute([$negocio_id]);
$ods_relacionadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Galeria
$galeria = gallery_from_apresentacao($apresentacao);
?>

<?php include __DIR__ . '/app/views/public/header_public.php'; ?>

<style>
    /* Estilos personalizados para a página de Perfil */
    .profile-header {
        height: 200px;
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        border-radius: 10px 10px 0 0;
        position: relative;
    }
    .profile-avatar-container {
        margin-top: -80px;
        text-align: center;
    }
    .profile-avatar {
        width: 160px;
        height: 160px;
        object-fit: contain;
        background: #fff;
        border: 4px solid #fff;
        border-radius: 50%;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .ods-badge {
        width: 40px;
        height: 40px;
        object-fit: contain;
        border-radius: 4px;
    }
</style>

<div class="container my-5">
    
    <div class="card shadow-sm border-0 rounded-3">
        <!-- CAPA DO PERFIL -->
        
        <div class="card-body px-4 pb-4">
            <div class="row">
                
                <!-- COLUNA ESQUERDA: INFOS PRINCIPAIS & CONTATO -->
                <div class="col-md-4 border-end">
                    
                    <!-- Avatar/Logo -->
                    <div class="profile-avatar-container mb-3">
                        <?php if (!empty($apresentacao['logo_negocio'])): ?>
                            <img src="<?= htmlspecialchars($apresentacao['logo_negocio']) ?>" alt="Logo" class="profile-avatar">
                        <?php else: ?>
                            <img src="/assets/images/placeholder-logo.png" alt="Sem Logo" class="profile-avatar bg-light">
                        <?php endif; ?>
                    </div>

                    <!-- Nome e Tags -->
                    <h3 class="text-center fw-bold mb-1"><?= htmlspecialchars($negocio['nome_fantasia']) ?></h3>
                    <p class="text-center text-muted small mb-2"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($negocio['municipio']) ?> / <?= htmlspecialchars($negocio['estado']) ?></p>
                    
                    <div class="d-flex justify-content-center flex-wrap gap-1 mb-4">
                        <span class="badge bg-secondary"><?= htmlspecialchars($negocio['categoria']) ?></span>
                        <?php if ($eixo_principal): ?>
                            <span class="badge bg-primary text-wrap text-center" style="max-width: 100%;"><?= htmlspecialchars($eixo_principal['eixo_nome']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Contatos -->
                    <h6 class="fw-bold border-bottom pb-2">Contato</h6>
                    <ul class="list-unstyled small mb-4">
                        <?php if(!empty($negocio['telefone_comercial'])): ?>
                            <li class="mb-2"><i class="bi bi-whatsapp text-success me-2"></i> <?= htmlspecialchars($negocio['telefone_comercial']) ?></li>
                        <?php endif; ?>
                        <?php if(!empty($negocio['email_comercial'])): ?>
                            <li class="mb-2 text-truncate" title="<?= htmlspecialchars($negocio['email_comercial']) ?>">
                                <i class="bi bi-envelope text-secondary me-2"></i> <a href="mailto:<?= htmlspecialchars($negocio['email_comercial']) ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($negocio['email_comercial']) ?></a>
                            </li>
                        <?php endif; ?>
                        <?php if(!empty($negocio['site'])): ?>
                            <li class="mb-2 text-truncate">
                                <i class="bi bi-globe text-primary me-2"></i> <a href="<?= htmlspecialchars($negocio['site']) ?>" target="_blank" class="text-decoration-none text-dark"><?= htmlspecialchars($negocio['site']) ?></a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <!-- Redes Sociais -->
                    <?php 
                    $icones = [
                        'linkedin' => ['icone' => 'bi-linkedin', 'cor' => '#0a66c2'], 
                        'instagram' => ['icone' => 'bi-instagram', 'cor' => '#e1306c'], 
                        'facebook' => ['icone' => 'bi-facebook', 'cor' => '#1877f2'], 
                        'youtube' => ['icone' => 'bi-youtube', 'cor' => '#ff0000'], 
                        'tiktok' => ['icone' => 'bi-tiktok', 'cor' => '#000000']
                    ];
                    $temRedes = false;
                    foreach ($icones as $rede => $dados) {
                        if (!empty($negocio[$rede])) { $temRedes = true; break; }
                    }
                    ?>

                    <?php if ($temRedes): ?>
                        <h6 class="fw-bold border-bottom pb-2">Redes Sociais</h6>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <?php foreach ($icones as $rede => $dados): ?>
                                <?php if (!empty($negocio[$rede])): ?>
                                    <a href="<?= htmlspecialchars($negocio[$rede]) ?>" target="_blank" class="btn btn-light btn-sm rounded-circle shadow-sm" style="color: <?= $dados['cor'] ?>;">
                                        <i class="<?= $dados['icone'] ?> fs-5"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- ODS -->
                    <h6 class="fw-bold border-bottom pb-2">Impacto (ODS)</h6>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php if (!empty($ods_prioritaria['icone_url'])): ?>
                            <img src="<?= htmlspecialchars($ods_prioritaria['icone_url']) ?>" alt="ODS Prioritária" class="ods-badge border border-warning" title="Prioritária">
                        <?php endif; ?>
                        
                        <?php if (!empty($ods_relacionadas)): ?>
                            <?php foreach ($ods_relacionadas as $ods): ?>
                                <img src="<?= htmlspecialchars($ods['icone_url']) ?>" alt="ODS" class="ods-badge">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Compartilhar -->
                    <h6 class="fw-bold border-bottom pb-2">Compartilhar</h6>
                    <div class="d-flex gap-1">
                        <a href="https://api.whatsapp.com/send?text=Confira este negócio de impacto: <?= urlencode('https://seusite.com.br/negocio.php?id='.$negocio['id']) ?>" target="_blank" class="btn btn-outline-success btn-sm flex-fill"><i class="bi bi-whatsapp"></i></a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode('https://seusite.com.br/negocio.php?id='.$negocio['id']) ?>" target="_blank" class="btn btn-outline-primary btn-sm flex-fill"><i class="bi bi-linkedin"></i></a>
                    </div>

                </div>

                <!-- COLUNA DIREITA: CONTEÚDO / PITCH -->
                <div class="col-md-8 ps-md-4 pt-4 pt-md-0">
                    
                    <?php if (!empty($apresentacao['frase_negocio'])): ?>
                        <h4 class="text-dark fw-bold mb-4">"<?= htmlspecialchars($apresentacao['frase_negocio']) ?>"</h4>
                    <?php endif; ?>                    

                    <!-- Vídeo Pitch -->
                    <?php if (!empty($apresentacao['video_pitch_url'])): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold text-secondary"><i class="bi bi-play-btn me-2"></i> Pitch</h5>
                            <div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm">
                                <iframe src="<?= str_replace("watch?v=", "embed/", htmlspecialchars($apresentacao['video_pitch_url'])) ?>" allowfullscreen></iframe>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Problema -->
                    <?php if (!empty($apresentacao['problema_resolvido'])): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold text-secondary"><i class="bi bi-exclamation-circle text-warning me-2"></i> O Problema</h5>
                            <div class="p-3 bg-white border border-light rounded-3 shadow-sm">
                                <p class="mb-0 text-muted" style="line-height: 1.6; text-align: justify;">
                                    <?= nl2br(htmlspecialchars($apresentacao['problema_resolvido'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Solução -->
                    <?php if (!empty($apresentacao['solucao_oferecida'])): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold text-secondary"><i class="bi bi-bullseye text-success me-2"></i> A Solução</h5>
                            <div class="p-3 rounded-3 shadow-sm" style="background-color: #f8fff9; border-left: 4px solid #198754;">
                                <p class="mb-0 text-dark" style="line-height: 1.6; text-align: justify;">
                                    <?= nl2br(htmlspecialchars($apresentacao['solucao_oferecida'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($apresentacao['descricao_inovacao'])): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold text-secondary"><i class="bi bi-lightbulb me-2"></i> Inovação</h5>
                            <div class="p-3 bg-light rounded-3 border-start border-4 border-warning">
                                <p class="mb-0 text-muted" style="line-height: 1.5;">
                                    <?= nl2br(htmlspecialchars($apresentacao['descricao_inovacao'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Galeria (Seu código original mantido intacto) -->
                    <?php if (!empty($galeria) && is_array($galeria)): ?>
                    <?php
                        $uid = preg_replace('/[^a-zA-Z0-9]/', '', (string)($negocio_id ?? '0'));
                        if ($uid === '') $uid = 'x' . mt_rand(1000, 9999);
                    ?>
                    <div class="mb-4">
                        <h5 class="fw-bold text-secondary mb-3"><i class="bi bi-images me-2"></i> Galeria de Fotos</h5>
                        <div class="jg" data-jg-uid="<?= $uid ?>" data-jg-target-h="150" data-jg-max-h="210">
                            <?php foreach ($galeria as $i => $img): if (empty($img)) continue; ?>
                                <a href="<?= htmlspecialchars($img) ?>">
                                    <img src="<?= htmlspecialchars($img) ?>" alt="Galeria <?= ($i+1) ?>" loading="lazy" class="rounded">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="jg-lightbox" id="jgLightbox-<?= $uid ?>" aria-hidden="true">
                        <button type="button" class="jg-close" aria-label="Fechar">×</button>
                        <button type="button" class="jg-btn jg-prev" aria-label="Anterior">‹</button>
                        <img class="jg-full" alt="">
                        <button type="button" class="jg-btn jg-next" aria-label="Próximo">›</button>
                        <div class="jg-caption"></div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>


<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>