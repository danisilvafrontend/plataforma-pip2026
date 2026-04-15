<?php
session_start();

$config = require __DIR__ . '/app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Recebe ID do parceiro da URL
$parceiro_id = (int)($_GET['id'] ?? 0);
if ($parceiro_id <= 0) {
    http_response_code(404);
    die("Página não encontrada.");
}

// Busca os dados mesclando a tabela principal, o contrato (logo) e o perfil público
$stmt = $pdo->prepare("
    SELECT p.nome_fantasia, p.status, p.acordo_aceito,
           c.logo_url, c.tipos_parceria,
           pp.*
    FROM parceiros p
    LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id
    LEFT JOIN parceiros_perfil pp ON p.id = pp.parceiro_id
    WHERE p.id = ? AND p.status = 'ativo' AND p.acordo_aceito = 1
");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

// Verifica se existe e se ativou o perfil público
if (!$parceiro || empty($parceiro['perfil_publicado'])) {
    http_response_code(404);
    die("Este perfil ainda não está publicado na vitrine.");
}

// Decodifica JSONs
$tipos_parceria = !empty($parceiro['tipos_parceria']) ? json_decode($parceiro['tipos_parceria'], true) : [];
if (!is_array($tipos_parceria)) $tipos_parceria = [];

$especialidades = !empty($parceiro['tags_especialidades']) ? json_decode($parceiro['tags_especialidades'], true) : [];
if (!is_array($especialidades)) $especialidades = [];

include __DIR__ . '/app/views/public/header_public.php';
?>

<!-- <div class="container pt-4">
    <a href="parceiros.php" class="btn btn-light rounded-pill px-4 py-2 fw-semibold">
        <i class="bi bi-arrow-left me-2"></i> Voltar para parceiros
    </a>
</div> -->

<!-- BANNER / CAPA -->
<?php if (!empty($parceiro['imagem_capa_url'])): ?>
    <div class="w-100" style="height: 250px; background-image: url('<?= htmlspecialchars($parceiro['imagem_capa_url']) ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    </div>
<?php else: ?>
    <div class="bg-primary bg-gradient w-100" style="height: 200px;">
    </div>
<?php endif; ?>


<div class="container mb-5" style="margin-top: -80px;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    
                    <!-- CABEÇALHO DO PERFIL -->
                    <div class="d-flex flex-column flex-md-row align-items-center align-items-md-end text-center text-md-start mb-5 border-bottom pb-4">
                        
                        <!-- Logo -->
                        <div class="me-md-4 mb-3 mb-md-0 position-relative">
                            <?php if (!empty($parceiro['logo_url'])): ?>
                                <img src="<?= htmlspecialchars($parceiro['logo_url']) ?>" 
                                     alt="<?= htmlspecialchars($parceiro['nome_fantasia']) ?>"
                                     class="rounded-circle shadow bg-white p-1" 
                                     style="width: 150px; height: 150px; object-fit: cover; margin-top: -60px;">
                            <?php else: ?>
                                <div class="rounded-circle bg-white shadow d-flex align-items-center justify-content-center border" 
                                     style="width: 150px; height: 150px; margin-top: -60px;">
                                    <i class="bi bi-building text-primary" style="font-size: 4rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Título e Infos -->
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
                                <h1 class="fw-bold text-dark mb-0 fs-2"><?= htmlspecialchars($parceiro['nome_fantasia']) ?></h1>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-2 ms-md-2" title="Parceiro Oficial">
                                    <i class="bi bi-patch-check-fill me-1"></i> Parceiro de Impacto Oficial
                                </span>
                            </div>

                            <?php if (!empty($parceiro['slogan'])): ?>
                                <p class="text-secondary fs-5 mb-2">"<?= htmlspecialchars($parceiro['slogan']) ?>"</p>
                            <?php endif; ?>

                            <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-md-start text-muted small mt-3">
                                <?php if (!empty($parceiro['setor_atuacao'])): ?>
                                    <span><i class="bi bi-briefcase me-1"></i> <?= htmlspecialchars($parceiro['setor_atuacao']) ?></span>
                                <?php endif; ?>
                                
                                <?php if (!empty($parceiro['porte_empresa'])): ?>
                                    <span><i class="bi bi-people me-1"></i> <?= htmlspecialchars($parceiro['porte_empresa']) ?> colaboradores</span>
                                <?php endif; ?>

                                <?php if (!empty($parceiro['ano_fundacao'])): ?>
                                    <span><i class="bi bi-calendar3 me-1"></i> Desde <?= htmlspecialchars($parceiro['ano_fundacao']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Botão de Contato (se tiver whatsapp) -->
                        <?php if (!empty($parceiro['whatsapp_publico'])): ?>
                            <div class="mt-4 mt-md-0 ms-md-auto align-self-md-center">
                                <?php
                                    $num_whats = preg_replace('/[^0-9]/', '', $parceiro['whatsapp_publico']);
                                ?>
                                <a href="https://api.whatsapp.com/send?phone=55<?= $num_whats ?>" target="_blank" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm">
                                    <i class="bi bi-whatsapp me-2"></i> Contatar
                                </a>
                            </div>
                        <?php endif; ?>

                    </div> <!-- Fim Cabeçalho -->

                    <div class="row">
                        <!-- COLUNA PRINCIPAL -->
                        <div class="col-lg-8 pe-lg-4">
                            
                            <!-- Descrição -->
                            <?php if (!empty($parceiro['descricao_institucional'])): ?>
                                <h5 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i> Sobre a Empresa</h5>
                                <div class="text-muted lh-lg mb-5" style="white-space: pre-wrap; font-size: 1rem;"><?= htmlspecialchars($parceiro['descricao_institucional']) ?></div>
                            <?php endif; ?>

                            <!-- Compromisso ESG/Impacto -->
                            <?php if (!empty($parceiro['compromisso_impacto'])): ?>
                                <div class="card bg-success bg-opacity-10 border-0 rounded-4 mb-5">
                                    <div class="card-body p-4">
                                        <h5 class="fw-bold text-success mb-3"><i class="bi bi-tree me-2"></i> Compromisso com o Impacto Positivo</h5>
                                        <p class="mb-0 text-dark" style="white-space: pre-wrap;"><?= htmlspecialchars($parceiro['compromisso_impacto']) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- COLUNA LATERAL -->
                        <div class="col-lg-4">
                            
                            <!-- Especialidades -->
                            <?php if (!empty($especialidades)): ?>
                                <div class="mb-5">
                                    <h6 class="fw-bold mb-3 text-uppercase text-secondary" style="font-size: 0.85rem; letter-spacing: 1px;">Especialidades / Soluções</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($especialidades as $tag): ?>
                                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill"><?= htmlspecialchars($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Papel na Plataforma -->
                            <?php if (!empty($tipos_parceria)): ?>
                                <div class="mb-5">
                                    <h6 class="fw-bold mb-3 text-uppercase text-secondary" style="font-size: 0.85rem; letter-spacing: 1px;">Como apoia a plataforma</h6>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($tipos_parceria as $tipo): 
                                            $tipo_str = is_string($tipo) ? $tipo : (string)$tipo;
                                        ?>
                                            <li class="mb-2 d-flex align-items-center text-muted">
                                                <i class="bi bi-check2 text-success fs-5 me-2"></i> <?= htmlspecialchars($tipo_str) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <!-- Redes e Contato -->
                            <?php if (!empty($parceiro['email_publico']) || !empty($parceiro['linkedin_url']) || !empty($parceiro['instagram_url'])): ?>
                                <div class="card bg-light border-0 rounded-4">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold mb-3 text-uppercase text-secondary" style="font-size: 0.85rem; letter-spacing: 1px;">Canais Oficiais</h6>
                                        <div class="d-flex flex-column gap-3">
                                            
                                            <?php if (!empty($parceiro['email_publico'])): ?>
                                                <a href="mailto:<?= htmlspecialchars($parceiro['email_publico']) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm me-3" style="width: 40px; height: 40px;">
                                                        <i class="bi bi-envelope text-primary"></i>
                                                    </div>
                                                    <span class="text-break"><?= htmlspecialchars($parceiro['email_publico']) ?></span>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (!empty($parceiro['linkedin_url'])): ?>
                                                <a href="<?= htmlspecialchars($parceiro['linkedin_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-dark d-flex align-items-center">
                                                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm me-3" style="width: 40px; height: 40px;">
                                                        <i class="bi bi-linkedin" style="color: #0A66C2;"></i>
                                                    </div>
                                                    <span>LinkedIn</span>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (!empty($parceiro['instagram_url'])): ?>
                                                <a href="<?= htmlspecialchars($parceiro['instagram_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-dark d-flex align-items-center">
                                                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm me-3" style="width: 40px; height: 40px;">
                                                        <i class="bi bi-instagram text-danger"></i>
                                                    </div>
                                                    <span>Instagram</span>
                                                </a>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div> <!-- Fim Coluna Lateral -->
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
<div class="container mb-5">
    <div class="text-center">
        <a href="parceiros.php" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold">
            <i class="bi bi-arrow-left me-2"></i> Voltar para parceiros
        </a>
    </div>
</div>
<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>
