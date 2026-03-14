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

// Busca Impacto
$stmt = $pdo->prepare("SELECT * FROM negocio_impacto WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$impacto = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Busca eixo
$stmt = $pdo->prepare("SELECT nome as eixo_nome FROM eixos_tematicos WHERE id = ?");
$stmt->execute([$negocio['eixo_principal_id']]);
$eixo_principal = $stmt->fetch(PDO::FETCH_ASSOC);

// Busca ODS
// Busca ODS Prioritária (agora puxando o nome também)
$stmt = $pdo->prepare("SELECT nome, icone_url FROM ods WHERE id = ?");
$stmt->execute([$negocio['ods_prioritaria_id']]);
$ods_prioritaria = $stmt->fetch(PDO::FETCH_ASSOC);


// Busca ODS Relacionadas (agora puxando o nome também)
$stmt = $pdo->prepare("SELECT o.nome, o.icone_url, o.n_ods FROM ods o INNER JOIN negocio_ods no ON o.id = no.ods_id WHERE no.negocio_id = ?");
$stmt->execute([$negocio_id]);
$ods_relacionadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Galeria
$galeria = gallery_from_apresentacao($apresentacao);
?>

<?php include __DIR__ . '/app/views/public/header_public.php'; ?>

<style>
    /* Estilos personalizados para a página de Perfil */
    .profile-avatar-container {
        margin-top: -40px;
        text-align: center;
    }
    .profile-avatar {
        width: 80%;
        height: 130px;
        object-fit: contain;
        background: #fff;
        border: 4px solid #fff;
        border-radius: 8px;
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
                    
                    <p class="text-center text-muted small mb-2 d-flex flex-wrap justify-content-center align-items-center gap-2">
                        <!-- Localização -->
                        <span>
                            <i class="bi bi-geo-alt"></i> 
                            <?= htmlspecialchars($negocio['municipio']) ?> / <?= htmlspecialchars($negocio['estado']) ?>
                        </span>

                        <!-- Divisor e Data de Fundação -->
                        <?php if (!empty($negocio['data_fundacao'])): ?>
                            <span class="d-none d-sm-inline opacity-50">|</span>
                            <span>
                                <i class="bi bi-calendar3"></i> Fundado em <?= date('Y', strtotime($negocio['data_fundacao'])) ?>
                            </span>
                        <?php endif; ?>

                        <!-- Divisor e Setor -->
                        <?php if (!empty($negocio['setor'])): ?>
                            <span class="d-none d-sm-inline opacity-50">|</span>
                            <span>
                                <i class="bi bi-briefcase"></i> Setor: <?= htmlspecialchars($negocio['setor']) ?>
                            </span>
                        <?php endif; ?>
                    </p>

                    <div class="d-flex justify-content-center flex-wrap gap-1 mb-4">
                        <span class="badge bg-secondary"><?= htmlspecialchars($negocio['categoria']) ?></span>
                        <?php if ($eixo_principal): ?>
                            <span class="badge bg-primary text-wrap text-center" style="max-width: 100%;"><?= htmlspecialchars($eixo_principal['eixo_nome']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Bloco de Chamadas para Ação (Engajamento) -->                     
                    <!-- <h6 class="fw-bold border-bottom pb-2">Quer Apoiar?</h6>
                    <div class="d-flex flex-wrap justify-content-between gap-2 mt-3 mb-4">                        
                        <a href="#" class="btn btn-sm btn-outline-secondary rounded-2 px-3 py-1 fw-medium" title="Trocar ideias ou formar parcerias">
                            <i class="bi bi-people me-1"></i> Conectar
                        </a>
                        
                        <a href="#" class="btn btn-sm btn-outline-secondary rounded-2 px-3 py-1 fw-medium" title="Doar tempo, mentoria ou serviços">
                            <i class="bi bi-hands-heart me-1"></i> Voluntariar
                        </a>
                        
                        <a href="#" class="btn btn-sm btn-outline-primary rounded-2 px-3 py-1 fw-medium" title="Aportar recursos ou fomento">
                            <i class="bi bi-graph-up-arrow me-1"></i> Investir
                        </a>
                    </div> -->
                    
                    <!-- Contatos -->
                    <h6 class="fw-bold border-bottom pb-2">Contato</h6>
                    <ul class="list-unstyled small mb-4">
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

                    <div class="mb-4">
                        <!-- Bloco de Destaque da ODS Prioritária -->
                        <?php if (!empty($ods_prioritaria['icone_url'])): ?>
                            <div class="d-flex align-items-center bg-light border border-2 border-warning rounded p-2 mb-3 shadow-sm" style="max-width: fit-content;">
                                <img src="<?= htmlspecialchars($ods_prioritaria['icone_url']) ?>" 
                                    alt="ODS Prioritária" 
                                    class="ods-badge me-2" 
                                    style="transform: scale(1.1);" 
                                    title="ODS Principal do Negócio">
                                <div>
                                    <span class="badge bg-primary text-bg-primary text-uppercase mb-1" style="font-size: 0.7rem;">ODS Prioritária</span>
                                    <div class="small fw-bold lh-sm text-primary"><?= htmlspecialchars($ods_prioritaria['nome']) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                       <!-- ODS Relacionadas (Em formato de Tags/Chips) -->
                        <?php if (!empty($ods_relacionadas)): ?>
                            <div class="mb-2 small text-muted fw-semibold">ODS Relacionadas:</div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($ods_relacionadas as $ods): ?>
                                    <div class="d-flex align-items-center bg-white border rounded-2 pe-3 p-1 shadow-sm opacity-75 hover-opacity-100 transition" style="cursor: default;">
                                        <img src="<?= htmlspecialchars($ods['icone_url']) ?>" 
                                            alt="<?= htmlspecialchars($ods['nome'] ?? 'ODS') ?>" 
                                            class="rounded-2 me-2" 
                                            style="max-height: 35px;">
                                        <span class="small fw-medium" style="font-size: 0.75rem;">
                                            <strong><?= htmlspecialchars($ods['n_ods'] ?? '') ?></strong> - <?= htmlspecialchars($ods['nome'] ?? '') ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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
                    
                    <h3 class="fw-bold border-bottom pb-2"> Quem somos</h3>

                    <?php if (!empty($apresentacao['frase_negocio'])): ?>
                        <blockquote class="apresentacao-quote fst-italic text-primary border-start border-4 ps-3"> <?= htmlspecialchars($apresentacao['frase_negocio']) ?>"</blockquote>
                    <?php endif; ?>  

                    <div class="row mt-4 mb-4">
                        <div class="col-md-6 mb-2">
                            <?php if (!empty($apresentacao['problema_resolvido'])): ?>
                                <h5><i class="bi bi-exclamation-triangle text-danger me-2"></i> Problema </h5>
                                <div class="p-3 bg-light rounded border-start border-4 border-danger">
                                    <p class="mb-0 text-muted">
                                        <?= !empty($apresentacao['problema_resolvido']) ? nl2br(htmlspecialchars($apresentacao['problema_resolvido'])) : '<em>Não informado</em>' ?>
                                    </p>
                                </div>                            
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <?php if (!empty($apresentacao['problema_resolvido'])): ?>
                                <h5><i class="bi bi-check-circle text-success me-2"></i> Solução </h5>
                                <div class="p-3 bg-light rounded border-start border-4 border-success">
                                    <p class="mb-0 text-muted">
                                        <?= !empty($apresentacao['solucao_oferecida']) ? nl2br(htmlspecialchars($apresentacao['solucao_oferecida'])) : '<em>Não informado</em>' ?>
                                    </p>
                                </div>               
                            <?php endif; ?>
                        </div>
                    </div>
                   
                    <?php
                    // Prepara as inovações selecionadas
                    $tiposInovacao = [
                        'inovacao_tecnologica' => 'Tecnológica',
                        'inovacao_produto' => 'Produto',
                        'inovacao_servico' => 'Serviço',
                        'inovacao_modelo' => 'Modelo de Negócio',
                        'inovacao_social' => 'Social',
                        'inovacao_ambiental' => 'Ambiental',
                        'inovacao_cadeia_valor' => 'Cadeia de Valor',
                        'inovacao_governanca' => 'Governança',
                        'inovacao_impacto' => 'Impacto',
                        'inovacao_financiamento' => 'Financiamento'
                    ];

                    $inovacoesAtivas = [];
                    foreach ($tiposInovacao as $campo => $label) {
                        if (!empty($apresentacao[$campo])) {
                            $inovacoesAtivas[] = $label;
                        }
                    }

                    // Verifica se há alguma inovação selecionada OU uma descrição preenchida
                    $temInovacao = !empty($apresentacao['descricao_inovacao']) || count($inovacoesAtivas) > 0;
                    ?>

                    <?php if ($temInovacao): ?>
                        <div class="mt-4 mb-5">
                            <h6 class="fw-bold border-bottom pb-2 mb-3">Inovação e Diferenciais</h6>
                            
                            <div class="p-4 bg-light border border-secondary border-opacity-25 rounded">
                                
                                <!-- Tags de Inovação -->
                                <?php if (count($inovacoesAtivas) > 0): ?>
                                    <div class="mb-3">
                                        <span class="d-block small text-muted fw-semibold mb-2">Tipos de Inovação Aplicadas:</span>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($inovacoesAtivas as $tag): ?>
                                                <span class="badge border border-primary text-primary bg-white px-3 py-2 rounded-pill shadow-sm">
                                                    <i class="bi bi-lightbulb me-1"></i> <?= $tag ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Descrição da Inovação -->
                                <?php if (!empty($apresentacao['descricao_inovacao'])): ?>
                                    <div class="<?= count($inovacoesAtivas) > 0 ? 'mt-3 pt-3 border-top' : '' ?>">
                                        <span class="d-block small text-muted fw-semibold mb-2">Detalhes da Inovação:</span>
                                        <p class="mb-0" style="line-height: 1.6;">
                                            <?= nl2br(htmlspecialchars($apresentacao['descricao_inovacao'])) ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                        </div>
                    <?php endif; ?>


                    <div class="row mt-4 mb-4">
                        <div class="col-md-4 mb-3">
                            <!-- BAIXAR APRESENTAÇÃO (PDF) -->
                            <?php if (!empty($apresentacao['apresentacao_pdf'])): ?>
                                <a href="<?= htmlspecialchars($apresentacao['apresentacao_pdf']) ?>" 
                                class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center py-2" 
                                download 
                                target="_blank">
                                    <i class="bi bi-file-earmark-pdf fs-4 me-2"></i>
                                    <div class="text-start lh-sm">
                                        <span class="d-block fw-bold">Baixar Apresentação</span>
                                        <small class="text-muted" style="font-size: 0.75rem;">Arquivo PDF</small>
                                    </div>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <!-- VER VÍDEO PITCH -->
                            <?php if (!empty($apresentacao['video_pitch_url'])): ?>
                                <a href="<?= htmlspecialchars($apresentacao['video_pitch_url']) ?>" 
                                class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center py-2" 
                                target="_blank"
                                rel="noopener noreferrer">
                                    <i class="bi bi-youtube fs-4 me-2"></i>
                                    <div class="text-start lh-sm">
                                        <span class="d-block fw-bold">Ver Vídeo Pitch</span>
                                        <small class="text-muted" style="font-size: 0.75rem;">Assistir no YouTube</small>
                                    </div>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4 mb-3">
                            <!-- VER VÍDEO INSTITUCIONAL -->
                            <?php if (!empty($apresentacao['apresentacao_video_url'])): ?>
                                <a href="<?= htmlspecialchars($apresentacao['apresentacao_video_url']) ?>" 
                                class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center py-2" 
                                target="_blank"
                                rel="noopener noreferrer">
                                    <i class="bi bi-youtube fs-4 me-2"></i>
                                    <div class="text-start lh-sm">
                                        <span class="d-block fw-bold">Ver Vídeo Institucional</span>
                                        <small class="text-muted" style="font-size: 0.75rem;">Assistir no YouTube</small>
                                    </div>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h3 class="fw-bold border-bottom pb-2"> Como Geramos Impacto</h3>
                    <!-- IMPACTO -->
                    <div class="row mt-4 mb-4">
                        <div class="col-md-6">
                            <?php if (!empty($impacto['intencionalidade'])): ?>
                                <div class="p-3 bg-success-subtle rounded mb-1">
                                    <h5 class="fw-bold fs-6 border-bottom border-primary pb-2"> Intencionalidade</h5>
                                    <p class="mb-0 text-muted small">
                                        <?= !empty($impacto['intencionalidade']) ? nl2br(htmlspecialchars($impacto['intencionalidade'])) : '<em>Não informado</em>' ?>
                                    </p>
                                </div>                            
                            <?php endif; ?>
                            <?php if (!empty($impacto['tipo_impacto'])): ?>
                                <div class="p-3 bg-success-subtle rounded mb-1">
                                    <h5 class="fw-bold fs-6 border-bottom border-primary pb-2"> Tipo de Impacto</h5>
                                    <p class="mb-0 text-muted small">
                                        <?= !empty($impacto['tipo_impacto']) ? nl2br(htmlspecialchars($impacto['tipo_impacto'])) : '<em>Não informado</em>' ?>
                                    </p>
                                </div>                            
                            <?php endif; ?>
                        </div>

                        <div class="col-md-3 text-center">
                            <?php 
                            $beneficiarios_lista = !empty($impacto['beneficiarios']) ? json_decode($impacto['beneficiarios'], true) : [];
                            if (!empty($impacto['beneficiario_outro'])) {
                                $beneficiarios_lista[] = $impacto['beneficiario_outro'];
                            }

                            if (!empty($beneficiarios_lista) && is_array($beneficiarios_lista)): 
                            ?>
                                <div class="p-3 bg-warning-subtle rounded h-100 mb-1">
                                    <h5 class="fw-bold fs-6 border-bottom border-warning pb-2 d-flex justify-content-between align-items-center">
                                        Beneficiários
                                        <i class="bi bi-info-circle text-muted" 
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="top" 
                                        title="Grupos de pessoas ou comunidades diretamente impactadas pelas ações e soluções do negócio.">
                                        </i>
                                    </h5>

                                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                        <?php foreach ($beneficiarios_lista as $beneficiario): ?>
                                            <li class="text-center fs-5 py-1">
                                                <?= htmlspecialchars(trim($beneficiario)) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>              
                            <?php endif; ?>
                        </div>

                        
                        <div class="col-md-3 text-center">
                            <?php if (!empty($impacto['alcance'])): ?>
                                <div class="p-3 bg-info-subtle rounded h-100 mb-1">

                                    <h5 class="fw-bold fs-6 border-bottom border-info pb-2 d-flex justify-content-between align-items-center">
                                        Alcance
                                        <i class="bi bi-info-circle text-muted" 
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="top" 
                                        title="Número de beneficioários diretos nos últimos 2 anos.">
                                        </i>
                                    </h5>
                                    <p class="mb-0 fs-3">
                                        <?= !empty($impacto['alcance']) ? nl2br(htmlspecialchars($impacto['alcance'])) : '<em>Não informado</em>' ?>
                                    </p>
                                </div>                            
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- RESULTADOS ALCANÇADOS -->
                    <div class="row mt-4 mb-2">
                        <div class="col-md-12 mb-3">
                          <?php if (!empty($impacto['resultados'])): ?>
                                <div class="mb-1">
                                    <h5 class="fw-bold fs-6 border-bottom pb-2">Resultados Alcançados</h5>
                                    <p class="mb-0 text-muted small">
                                        <?= !empty($impacto['resultados']) ? nl2br(htmlspecialchars($impacto['resultados'])) : '<em>Não informado</em>' ?>
                                    </p>
                                </div>                            
                            <?php endif; ?>
                        </div>
                    </div>

                   <!-- ÁREA DE LINKS E DOCUMENTOS DE IMPACTO -->
                    <?php 
                    // Decodifica os JSONs para arrays do PHP
                    $resultados_links = !empty($impacto['resultados_links']) ? json_decode($impacto['resultados_links'], true) : [];
                    $resultados_pdfs = !empty($impacto['resultados_pdfs']) ? json_decode($impacto['resultados_pdfs'], true) : [];

                    // Só mostra o bloco se tiver pelo menos 1 link OU 1 PDF cadastrado
                    if (!empty($resultados_links) || !empty($resultados_pdfs)): 
                    ?>
                        <div class="mb-4">
                            <h5 class="fw-bold fs-6 border-bottom pb-2"> Documentos e Evidências</h5>
                            <div class="row">
                                
                                <!-- Loop para gerar os botões de PDFs -->
                                <?php if (!empty($resultados_pdfs) && is_array($resultados_pdfs)): ?>
                                    <?php foreach ($resultados_pdfs as $index => $pdf): ?>
                                        <?php 
                                            // Garante que o caminho comece com barra (/) para não quebrar a URL
                                            $pdf_url = (strpos($pdf, '/') === 0) ? $pdf : '/' . $pdf; 
                                        ?>
                                        <div class="col-md-6 mb-3">
                                            <a href="<?= htmlspecialchars($pdf_url) ?>" 
                                            class="btn btn-outline-dark w-100 d-flex align-items-center justify-content-center py-2 bg-white" 
                                            download 
                                            target="_blank">
                                                <i class="bi bi-file-earmark-pdf fs-4 me-2 text-danger"></i>
                                                <div class="text-start lh-sm">
                                                    <span class="d-block fw-bold text-dark">Baixar Relatório de Impacto <?= count($resultados_pdfs) > 1 ? ($index + 1) : '' ?></span>
                                                    <small class="text-muted" style="font-size: 0.75rem;">Documento PDF</small>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- Loop para gerar os botões de Links Externos -->
                                <?php if (!empty($resultados_links) && is_array($resultados_links)): ?>
                                    <?php foreach ($resultados_links as $index => $link): ?>
                                        <div class="col-md-6 mb-3">
                                            <a href="<?= htmlspecialchars($link) ?>" 
                                            class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center py-2 bg-white" 
                                            target="_blank"
                                            rel="noopener noreferrer">
                                                <i class="bi bi-link-45deg fs-4 me-2"></i>
                                                <div class="text-start lh-sm">
                                                    <span class="d-block fw-bold">Acessar Evidência Externa <?= count($resultados_links) > 1 ? ($index + 1) : '' ?></span>
                                                    <small class="text-muted" style="font-size: 0.75rem;">Página Web</small>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                            </div>
                        </div>
                    <?php endif; ?>                    
                    
                    <h3 class="fw-bold border-bottom pb-2"> Galeria de Fotos</h3>

                    <div class="row mt-4 mb-3">
                        <div class="col-md-12">
                        <!-- Galeria (Seu código original mantido intacto) -->
                        <?php if (!empty($galeria) && is_array($galeria)): ?>
                            <?php
                                $uid = preg_replace('/[^a-zA-Z0-9]/', '', (string)($negocio_id ?? '0'));
                                if ($uid === '') $uid = 'x' . mt_rand(1000, 9999);
                            ?>
                            <div class="mb-4">
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


                <div class="row mt-4 mb-2">
                    <div class="col-md-12 mb-3">
                        <?php if (!empty($impacto['proximos_passos'])): ?>
                            
                        <h3 class="fw-bold border-bottom pb-2">Visão para o Futuro</h3>
                            <div class="mb-1">
                                <p class="mb-0 text-muted small">
                                    <?= !empty($impacto['proximos_passos']) ? nl2br(htmlspecialchars($impacto['proximos_passos'])) : '<em>Não informado</em>' ?>
                                </p>
                            </div>                            
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mt-2 mb-2">
                    <div class="col-md-12 mb-3">
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
                            <h5 class="fw-bold fs-6 border-bottom pb-2">Nos Acompanhe</h5>
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
                    </div>
                </div>


            </div>  
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>


<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>