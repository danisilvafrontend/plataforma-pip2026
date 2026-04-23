<?php
session_start();

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
$stmt = $pdo->prepare("SELECT nome, icone_url FROM ods WHERE id = ?");
$stmt->execute([$negocio['ods_prioritaria_id']]);
$ods_prioritaria = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT o.nome, o.icone_url, o.n_ods FROM ods o INNER JOIN negocio_ods no ON o.id = no.ods_id WHERE no.negocio_id = ?");
$stmt->execute([$negocio_id]);
$ods_relacionadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Galeria
$galeria = gallery_from_apresentacao($apresentacao);

// Premiação ativa com voto popular em andamento
$votacaoNegocio   = false;
$faseVotNegocio   = null;
$inscricaoNegocio = null;
$jaVotouNegocio   = false;

$stmtPrem = $pdo->query("
    SELECT p.id AS premiacao_id, p.data_inicio_votacao, p.data_fim_votacao,
           pf.id AS fase_id
    FROM premiacoes p
    INNER JOIN premiacao_fases pf
        ON pf.premiacao_id = p.id
       AND pf.permite_voto_popular = 1
       AND pf.status = 'em_andamento'
    WHERE p.status IN ('ativa','planejada')
    ORDER BY p.ano DESC
    LIMIT 1
");
$dadosVot = $stmtPrem->fetch(PDO::FETCH_ASSOC);

if ($dadosVot) {
    $agora = time();
    $ini   = strtotime($dadosVot['data_inicio_votacao'] ?? '');
    $fim   = strtotime($dadosVot['data_fim_votacao']    ?? '');

    if ($ini && $fim && $agora >= $ini && $agora <= $fim) {
        // Negócio está inscrito e elegível nesta premiação?
        $stmtInsc = $pdo->prepare("
            SELECT id FROM premiacao_inscricoes
            WHERE negocio_id   = ?
              AND premiacao_id = ?
              AND status       = 'elegivel'
            LIMIT 1
        ");
        $stmtInsc->execute([$negocio['id'], $dadosVot['premiacao_id']]);
        $inscricaoNegocio = $stmtInsc->fetch(PDO::FETCH_ASSOC);

        if ($inscricaoNegocio) {
            $votacaoNegocio = true;
            $faseVotNegocio = $dadosVot;

            // Já votou?
            if (isset($_SESSION['user_id'])) {
                $tipoEl = $_SESSION['tipo_usuario'] ?? 'empreendedor';
                $stmtJaV = $pdo->prepare("
                    SELECT COUNT(*) FROM premiacao_votos_populares
                    WHERE fase_id      = ?
                      AND inscricao_id = ?
                      AND tipo_eleitor = ?
                      AND eleitor_id   = ?
                ");
                $stmtJaV->execute([
                    $dadosVot['fase_id'],
                    $inscricaoNegocio['id'],
                    $tipoEl,
                    $_SESSION['user_id']
                ]);
                $jaVotouNegocio = (int)$stmtJaV->fetchColumn() > 0;
            }
        }
    }
}
?>

<?php include __DIR__ . '/app/views/public/header_public.php'; ?>

<div class="negocio-publico-page py-4 py-md-5">
    <div class="container">
        <section class="negocio-publico-header card border-0 shadow-sm overflow-hidden">
            <div class="negocio-publico-cover <?= !empty($apresentacao['imagem_destaque']) ? '' : 'bg-secondary' ?>">
                <?php if (!empty($apresentacao['imagem_destaque'])): ?>
                    <img
                        src="<?= htmlspecialchars($apresentacao['imagem_destaque']) ?>"
                        alt="Capa do negócio <?= htmlspecialchars($negocio['nome_fantasia']) ?>"
                        class="negocio-publico-cover-img"
                    >
                <?php endif; ?>
            </div>

            <div class="negocio-publico-header-body">
                <div class="negocio-publico-logo-wrap">
                    <div class="negocio-publico-logo-box">
                        <?php if (!empty($apresentacao['logo_negocio'])): ?>
                            <img
                                src="<?= htmlspecialchars($apresentacao['logo_negocio']) ?>"
                                alt="Logo do negócio <?= htmlspecialchars($negocio['nome_fantasia']) ?>"
                                class="negocio-publico-logo-img"
                            >
                        <?php else: ?>
                            <div class="negocio-publico-logo-placeholder bg-light text-muted">
                                <i class="bi bi-image"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="negocio-publico-header-content">
                    <h1 class="negocio-publico-titulo mb-2"><?= htmlspecialchars($negocio['nome_fantasia']) ?></h1>
                    <?php if (!empty($apresentacao['frase_negocio'])): ?>
                        <div class="negocio-publico-frase-wrap">
                            <p class="negocio-publico-frase mb-3">
                                <i class="bi bi-quote quote-icon"></i>
                                <?= htmlspecialchars($apresentacao['frase_negocio']) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="negocio-publico-meta d-flex flex-wrap gap-2">
                        <?php if (!empty($negocio['municipio']) || !empty($negocio['estado'])): ?>
                            <span class="negocio-publico-pill">
                                <i class="bi bi-geo-alt"></i>
                                <?= htmlspecialchars(trim(($negocio['municipio'] ?? '') . ' / ' . ($negocio['estado'] ?? ''), ' /')) ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($negocio['categoria'])): ?>
                            <span class="negocio-publico-pill"><?= htmlspecialchars($negocio['categoria']) ?></span>
                        <?php endif; ?>

                        <?php if (!empty($eixo_principal['eixo_nome'])): ?>
                            <span class="negocio-publico-pill negocio-publico-pill-primary"><?= htmlspecialchars($eixo_principal['eixo_nome']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>


        <div class="row g-4 mt-1">
            <div class="col-lg-4">
                <?php
                $icones = [
                    'linkedin' => ['icone' => 'bi-linkedin', 'cor' => '#0a66c2', 'label' => 'LinkedIn'],
                    'instagram' => ['icone' => 'bi-instagram', 'cor' => '#e1306c', 'label' => 'Instagram'],
                    'facebook' => ['icone' => 'bi-facebook', 'cor' => '#1877f2', 'label' => 'Facebook'],
                    'youtube' => ['icone' => 'bi-youtube', 'cor' => '#ff0000', 'label' => 'YouTube'],
                    'tiktok' => ['icone' => 'bi-tiktok', 'cor' => '#111111', 'label' => 'TikTok']
                ];

                $temRedes = false;
                foreach ($icones as $rede => $dados) {
                    if (!empty($negocio[$rede])) {
                        $temRedes = true;
                        break;
                    }
                }

                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $link_compartilhar = $protocol . $_SERVER['HTTP_HOST'] . '/negocio.php?id=' . $negocio['id'];

                $titulo_negocio = $negocio['nome_fantasia'];
                $texto_whats = "Conheça o negócio de impacto: {$titulo_negocio}! Acesse a Plataforma Impactos Positivos para saber mais: {$link_compartilhar}";
                $texto_email = "Gostaria de compartilhar este negócio de impacto incrível que encontrei na Plataforma Impactos Positivos:\n\n{$titulo_negocio}\n\nConfira todos os detalhes aqui: {$link_compartilhar}";
                ?>

                <div class="negocio-publico-sidebar">

                    <aside class="negocio-side-card">
                        
                        <h3 class="negocio-side-title">Ações</h3>

                        <div class="negocio-action-grid">
                            <?php if ($votacaoNegocio): ?>
                                <div class="negocio-acao-votar mb-3">
                                    <?php
                                    $usuarioLogado = !empty($_SESSION['user_id'])
                                                || !empty($_SESSION['parceiro_id'])
                                                || (!empty($_SESSION['logado']) && ($_SESSION['usuario_tipo'] ?? '') === 'sociedade_civil');
                                    ?>

                                    <?php if (!$usuarioLogado): ?>
                                        <a href="/login.php?redirect=<?= urlencode('/negocio.php?id=' . $negocio['id']) ?>"
                                        class="negocio-action-btn negocio-action-btn-primary w-100">
                                            <i class="bi bi-trophy me-2"></i> Faça o login para votar
                                        </a>

                                    <?php elseif ($jaVotouNegocio): ?>
                                        <button class="btn btn-success w-100" disabled>
                                            <i class="bi bi-check-lg me-2"></i> Você já votou aqui
                                        </button>

                                    <?php else: ?>
                                        <form method="POST" action="/premiacao/votar.php">
                                            <input type="hidden" name="inscricao_id" value="<?= (int)$inscricaoNegocio['id'] ?>">
                                            <input type="hidden" name="fase_id"      value="<?= (int)$faseVotNegocio['fase_id'] ?>">
                                            <input type="hidden" name="redirect"     value="/negocio.php?id=<?= $negocio['id'] ?>">
                                            <button type="submit" class="negocio-action-btn negocio-action-btn-primary w-100">
                                                <i class="bi bi-trophy me-2"></i> Votar neste negócio
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <a href="#" class="negocio-action-btn" title="Trocar ideias ou formar parcerias">
                                <i class="bi bi-people"></i>
                                <span>Conectar</span>
                            </a>

                            <a href="#" class="negocio-action-btn" title="Doar tempo, mentoria ou serviços">
                                <i class="bi bi-hands-heart"></i>
                                <span>Voluntariar</span>
                            </a>

                            <a href="#" class="negocio-action-btn negocio-action-btn-primary" title="Aportar recursos ou fomento">
                                <i class="bi bi-graph-up-arrow"></i>
                                <span>Investir</span>
                            </a>
                        </div>
                    </aside>

                    <?php if (
                        !empty($negocio['email_comercial']) ||
                        !empty($negocio['site']) ||
                        $temRedes
                    ): ?>
                        <aside class="negocio-side-card">
                            <h3 class="negocio-side-title">Contato</h3>

                            <div class="negocio-contact-list">
                                <?php if (!empty($negocio['email_comercial'])): ?>
                                    <a
                                        href="mailto:<?= htmlspecialchars($negocio['email_comercial']) ?>"
                                        class="negocio-contact-item"
                                        title="<?= htmlspecialchars($negocio['email_comercial']) ?>"
                                    >
                                        <span class="negocio-contact-icon">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <span class="negocio-contact-content">
                                            <small>E-mail comercial</small>
                                            <strong><?= htmlspecialchars($negocio['email_comercial']) ?></strong>
                                        </span>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($negocio['site'])): ?>
                                    <a
                                        href="<?= htmlspecialchars($negocio['site']) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="negocio-contact-item"
                                    >
                                        <span class="negocio-contact-icon">
                                            <i class="bi bi-globe"></i>
                                        </span>
                                        <span class="negocio-contact-content">
                                            <small>Site oficial</small>
                                            <strong><?= htmlspecialchars($negocio['site']) ?></strong>
                                        </span>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php if ($temRedes): ?>
                                <div class="negocio-side-divider"></div>

                                <div class="negocio-social-list">
                                    <?php foreach ($icones as $rede => $dados): ?>
                                        <?php if (!empty($negocio[$rede])): ?>
                                            <a
                                                href="<?= htmlspecialchars($negocio[$rede]) ?>"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="negocio-social-btn"
                                                title="<?= htmlspecialchars($dados['label']) ?>"
                                                aria-label="<?= htmlspecialchars($dados['label']) ?>"
                                                style="--social-color: <?= htmlspecialchars($dados['cor']) ?>;"
                                            >
                                                <i class="<?= htmlspecialchars($dados['icone']) ?>"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </aside>
                    <?php endif; ?>

                    <?php if (!empty($ods_prioritaria['icone_url']) || !empty($ods_relacionadas)): ?>
                        <aside class="negocio-side-card">
                            <h3 class="negocio-side-title">Impacto e ODS</h3>

                            <?php if (!empty($ods_prioritaria['icone_url'])): ?>
                                <div class="negocio-ods-feature">
                                    <img
                                        src="<?= htmlspecialchars($ods_prioritaria['icone_url']) ?>"
                                        alt="ODS prioritária <?= htmlspecialchars($ods_prioritaria['nome'] ?? '') ?>"
                                        class="negocio-ods-feature-img"
                                    >
                                    <div class="negocio-ods-feature-content">
                                        <span class="negocio-ods-kicker">ODS prioritária</span>
                                        <strong><?= htmlspecialchars($ods_prioritaria['nome']) ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($ods_relacionadas)): ?>
                                <div class="negocio-ods-list">
                                    <?php foreach ($ods_relacionadas as $ods): ?>
                                        <div class="negocio-ods-chip">
                                            <img
                                                src="<?= htmlspecialchars($ods['icone_url']) ?>"
                                                alt="<?= htmlspecialchars($ods['nome'] ?? 'ODS') ?>"
                                                class="negocio-ods-chip-img"
                                            >
                                            <span>
                                                <strong><?= htmlspecialchars($ods['n_ods'] ?? '') ?></strong>
                                                <?= htmlspecialchars($ods['nome'] ?? '') ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </aside>
                    <?php endif; ?>

                    <aside class="negocio-side-card">
                        <h3 class="negocio-side-title">Compartilhar</h3>
                        <p class="negocio-side-text text-muted">Ajude esta iniciativa a alcançar mais pessoas.</p>

                        <div class="negocio-share-grid">
                            <a
                                href="https://api.whatsapp.com/send?text=<?= urlencode($texto_whats) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="negocio-share-btn whatsapp"
                                title="Compartilhar no WhatsApp"
                            >
                                <i class="bi bi-whatsapp"></i>
                                <span>WhatsApp</span>
                            </a>

                            <a
                                href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($link_compartilhar) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="negocio-share-btn linkedin"
                                title="Compartilhar no LinkedIn"
                            >
                                <i class="bi bi-linkedin"></i>
                                <span>LinkedIn</span>
                            </a>

                            <a
                                href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($link_compartilhar) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="negocio-share-btn facebook"
                                title="Compartilhar no Facebook"
                            >
                                <i class="bi bi-facebook"></i>
                                <span>Facebook</span>
                            </a>

                            <a
                                href="mailto:?subject=<?= rawurlencode("Conheça o negócio de impacto: {$titulo_negocio}") ?>&body=<?= rawurlencode($texto_email) ?>"
                                class="negocio-share-btn email"
                                title="Enviar por e-mail"
                            >
                                <i class="bi bi-envelope"></i>
                                <span>E-mail</span>
                            </a>
                        </div>
                    </aside>

                </div>
            </div>

            <div class="col-lg-8">
                
                <div class="negocio-publico-content">
                    
                    <!-- PROBLEMA X SOLUÇÃO -->

                    <?php if (!empty($apresentacao['problema_resolvido']) || !empty($apresentacao['solucao_oferecida'])): ?>
                        <section class="negocio-proposta">
                            <?php if (!empty($apresentacao['problema_resolvido'])): ?>
                                <div class="negocio-proposta-problema">
                                    <span class="negocio-proposta-label">Problema</span>
                                    <p class="negocio-proposta-problema-texto mb-0">
                                        <?= nl2br(htmlspecialchars($apresentacao['problema_resolvido'])) ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($apresentacao['solucao_oferecida'])): ?>
                                <div class="negocio-proposta-solucao">
                                    <span class="negocio-proposta-label negocio-proposta-label-destaque">Solução</span>
                                    <div class="negocio-proposta-solucao-texto">
                                        <?= nl2br(htmlspecialchars($apresentacao['solucao_oferecida'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <!-- VIDEOS E APRESENTAÇÃO -->
                    <?php
                    $video_pitch_embed = '';

                    if (!empty($apresentacao['video_pitch_url'])) {
                        $video_pitch_url = trim($apresentacao['video_pitch_url']);

                        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([a-zA-Z0-9_-]{11})~', $video_pitch_url, $matches)) {
                            $video_pitch_embed = 'https://www.youtube-nocookie.com/embed/' . $matches[1] . '?rel=0';
                        }
                    }
                    ?>

                    <?php if ($video_pitch_embed || !empty($apresentacao['apresentacao_video_url']) || !empty($apresentacao['apresentacao_pdf'])): ?>
                        <section class="negocio-midia">
                            <div class="negocio-midia-inner">
                                <div class="negocio-midia-header">
                                    <span class="negocio-section-kicker">Apresentação</span>
                                    <h2 class="negocio-section-title mb-0">Pitch e materiais</h2>
                                </div>

                                <?php if ($video_pitch_embed): ?>
                                    <div class="negocio-video-moldura">
                                        <div class="negocio-video-moldura-inner">
                                            <div class="negocio-video-frame ratio ratio-16x9">
                                                <iframe
                                                    src="<?= htmlspecialchars($video_pitch_embed) ?>"
                                                    title="Vídeo pitch de <?= htmlspecialchars($negocio['nome_fantasia']) ?>"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                    referrerpolicy="strict-origin-when-cross-origin"
                                                    allowfullscreen
                                                ></iframe>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($apresentacao['apresentacao_video_url']) || !empty($apresentacao['apresentacao_pdf'])): ?>
                                    <div class="negocio-midia-links">
                                        <?php if (!empty($apresentacao['apresentacao_video_url'])): ?>
                                            <a
                                                href="<?= htmlspecialchars($apresentacao['apresentacao_video_url']) ?>"
                                                class="negocio-midia-cta"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                <span class="negocio-midia-cta-icon text-danger">
                                                    <i class="bi bi-youtube"></i>
                                                </span>
                                                <span class="negocio-midia-cta-content">
                                                    <strong>Ver vídeo institucional</strong>
                                                    <small>Assistir em nova aba</small>
                                                </span>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($apresentacao['apresentacao_pdf'])): ?>
                                            <a
                                                href="<?= htmlspecialchars($apresentacao['apresentacao_pdf']) ?>"
                                                class="negocio-midia-cta"
                                                download
                                                target="_blank"
                                            >
                                                <span class="negocio-midia-cta-icon text-primary">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                </span>
                                                <span class="negocio-midia-cta-content">
                                                    <strong>Baixar apresentação institucional</strong>
                                                    <small>Arquivo PDF</small>
                                                </span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>


                    <!-- INOVAÇÃO -->
                    <?php
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

                    $temInovacao = !empty($apresentacao['descricao_inovacao']) || count($inovacoesAtivas) > 0;
                    ?>

                    <?php if ($temInovacao): ?>
                        <section class="negocio-inovacao">
                            <div class="negocio-inovacao-header">
                                <span class="negocio-section-kicker">Diferenciais</span>
                                <h2 class="negocio-section-title mb-0">Inovação</h2>
                            </div>

                            <?php if (count($inovacoesAtivas) > 0): ?>
                                <div class="negocio-inovacao-destaque">
                                    <span class="negocio-inovacao-label">Tipos de inovação aplicados</span>

                                    <div class="negocio-inovacao-tags">
                                        <?php foreach ($inovacoesAtivas as $tag): ?>
                                            <span class="negocio-inovacao-tag">
                                                <i class="bi bi-lightbulb-fill"></i>
                                                <?= htmlspecialchars($tag) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($apresentacao['descricao_inovacao'])): ?>
                                <div class="negocio-inovacao-texto">
                                    <?= nl2br(htmlspecialchars($apresentacao['descricao_inovacao'])) ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <!-- COMO GERAMOS IMPACTO -->
                    <?php
                    $beneficiarios_lista = !empty($impacto['beneficiarios']) ? json_decode($impacto['beneficiarios'], true) : [];
                    if (!empty($impacto['beneficiario_outro'])) {
                        $beneficiarios_lista[] = $impacto['beneficiario_outro'];
                    }

                    $resultados_links = !empty($impacto['resultados_links']) ? json_decode($impacto['resultados_links'], true) : [];
                    $resultados_pdfs = !empty($impacto['resultados_pdfs']) ? json_decode($impacto['resultados_pdfs'], true) : [];

                    $temImpactoSessao =
                        !empty($impacto['intencionalidade']) ||
                        !empty($impacto['tipo_impacto']) ||
                        !empty($beneficiarios_lista) ||
                        !empty($impacto['alcance']) ||
                        !empty($impacto['resultados']) ||
                        !empty($resultados_links) ||
                        !empty($resultados_pdfs);
                    ?>

                    <?php if ($temImpactoSessao): ?>
                        <section class="negocio-impacto-v4">
                            <div class="negocio-impacto-header">
                                <span class="negocio-section-kicker">Impacto</span>
                                <h2 class="negocio-section-title mb-0">Como geramos impacto</h2>
                            </div>

                            <?php if (!empty($impacto['intencionalidade']) || !empty($impacto['tipo_impacto'])): ?>
                                <div class="impacto-textos">
                                    <?php if (!empty($impacto['intencionalidade'])): ?>
                                        <article class="impacto-texto-bloco impacto-texto-bloco-primary">
                                            <span class="impacto-texto-label">Intencionalidade</span>
                                            <div class="impacto-texto-conteudo">
                                                <?= nl2br(htmlspecialchars($impacto['intencionalidade'])) ?>
                                            </div>
                                        </article>
                                    <?php endif; ?>

                                    <?php if (!empty($impacto['tipo_impacto'])): ?>
                                        <article class="impacto-texto-bloco impacto-texto-bloco-secondary">
                                            <span class="impacto-texto-label">Tipo de impacto</span>
                                            <div class="impacto-texto-conteudo">
                                                <?= nl2br(htmlspecialchars($impacto['tipo_impacto'])) ?>
                                            </div>
                                        </article>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($beneficiarios_lista) || !empty($impacto['alcance'])): ?>
                                <div class="impacto-destaques">
                                    <?php if (!empty($beneficiarios_lista) && is_array($beneficiarios_lista)): ?>
                                        <article class="impacto-card impacto-card-beneficiarios">
                                            <div class="impacto-card-head">
                                                <span class="impacto-card-icon">
                                                    <i class="bi bi-people-fill"></i>
                                                </span>
                                                <div>
                                                    <h3 class="impacto-card-titulo">Beneficiários</h3>
                                                    <p class="impacto-card-apoio">Pessoas, grupos ou comunidades diretamente impactadas pelas ações e soluções do negócio.</p>
                                                </div>
                                            </div>

                                            <div class="impacto-card-tags">
                                                <?php foreach ($beneficiarios_lista as $beneficiario): ?>
                                                    <span class="impacto-card-tag">
                                                        <?= htmlspecialchars(trim($beneficiario)) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </article>
                                    <?php endif; ?>

                                    <?php if (!empty($impacto['alcance'])): ?>
                                        <article class="impacto-card impacto-card-alcance">
                                            <h3 class="impacto-card-titulo">Alcance</h3>
                                            <p class="impacto-card-apoio">Número de beneficiários diretos alcançados nos últimos 2 anos.</p>

                                            <div class="impacto-card-metrica">
                                                <?= nl2br(htmlspecialchars($impacto['alcance'])) ?>
                                            </div>
                                        </article>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($impacto['resultados']) || !empty($resultados_links) || !empty($resultados_pdfs)): ?>
                                <div class="impacto-moldura">
                                    <div class="impacto-moldura-inner">
                                        <?php if (!empty($impacto['resultados'])): ?>
                                            <article class="impacto-resultado-bloco">
                                                <span class="impacto-texto-label">Resultados alcançados</span>
                                                <div class="impacto-texto-conteudo">
                                                    <?= nl2br(htmlspecialchars($impacto['resultados'])) ?>
                                                </div>
                                            </article>
                                        <?php endif; ?>

                                        <?php if (!empty($resultados_links) || !empty($resultados_pdfs)): ?>
                                            <div class="impacto-evidencias-bloco">
                                                <span class="impacto-texto-label">Documentos e evidências</span>

                                                <div class="negocio-evidencias-grid">
                                                    <?php if (!empty($resultados_pdfs) && is_array($resultados_pdfs)): ?>
                                                        <?php foreach ($resultados_pdfs as $index => $pdf): ?>
                                                            <?php $pdf_url = (strpos($pdf, '/') === 0) ? $pdf : '/' . $pdf; ?>
                                                            <a href="<?= htmlspecialchars($pdf_url) ?>" class="negocio-evidencia-item" download target="_blank">
                                                                <span class="negocio-evidencia-icone pdf">
                                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                                </span>
                                                                <span class="negocio-evidencia-conteudo">
                                                                    <strong>Relatório de impacto <?= count($resultados_pdfs) > 1 ? ($index + 1) : '' ?></strong>
                                                                    <small>Documento PDF para download</small>
                                                                </span>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>

                                                    <?php if (!empty($resultados_links) && is_array($resultados_links)): ?>
                                                        <?php foreach ($resultados_links as $index => $link): ?>
                                                            <a href="<?= htmlspecialchars($link) ?>" class="negocio-evidencia-item" target="_blank" rel="noopener noreferrer">
                                                                <span class="negocio-evidencia-icone link">
                                                                    <i class="bi bi-link-45deg"></i>
                                                                </span>
                                                                <span class="negocio-evidencia-conteudo">
                                                                    <strong>Evidência externa <?= count($resultados_links) > 1 ? ($index + 1) : '' ?></strong>
                                                                    <small>Link para conteúdo complementar</small>
                                                                </span>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <!-- GALERIA DE FOTOS -->

                    <section class="negocio-galeria-fotos">
                        <div class="negocio-galeria-topo">
                            <h3 class="fw-bold mb-0">Galeria de Fotos</h3>
                        </div>

                        <div class="row mt-4 mb-2">
                            <div class="col-md-12">
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
                    </section>

                    <!-- PRÓXIMOS PASSOS -->

                    <?php if (!empty($impacto['proximos_passos'])): ?>
                        <section class="negocio-futuro">
                            <div class="negocio-futuro-header">
                                <span class="negocio-section-kicker">Próximos passos</span>
                                <h3 class="negocio-section-title mb-0">Visão para o Futuro</h3>
                            </div>

                            <div class="negocio-futuro-conteudo">
                                <?= nl2br(htmlspecialchars($impacto['proximos_passos'])) ?>
                            </div>
                        </section>
                    <?php endif; ?>
                    
                    <!-- REDES SOCIAIS -->
                    <?php
                    $icones = [
                        'linkedin' => ['icone' => 'bi-linkedin', 'cor' => '#0a66c2', 'label' => 'LinkedIn'],
                        'instagram' => ['icone' => 'bi-instagram', 'cor' => '#e1306c', 'label' => 'Instagram'],
                        'facebook' => ['icone' => 'bi-facebook', 'cor' => '#1877f2', 'label' => 'Facebook'],
                        'youtube' => ['icone' => 'bi-youtube', 'cor' => '#ff0000', 'label' => 'YouTube'],
                        'tiktok' => ['icone' => 'bi-tiktok', 'cor' => '#111111', 'label' => 'TikTok']
                    ];

                    $temRedes = false;
                    foreach ($icones as $rede => $dados) {
                        if (!empty($negocio[$rede])) { 
                            $temRedes = true; 
                            break; 
                        }
                    }
                    ?>

                    <?php if ($temRedes): ?>
                        <section class="negocio-redes">
                            <div class="negocio-redes-header">
                                <span class="negocio-section-kicker">Conexões</span>
                                <h3 class="negocio-section-title mb-0">Nos acompanhe</h3>
                            </div>

                            <div class="negocio-redes-lista">
                                <?php foreach ($icones as $rede => $dados): ?>
                                    <?php if (!empty($negocio[$rede])): ?>
                                        <a
                                            href="<?= htmlspecialchars($negocio[$rede]) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="negocio-rede-pill"
                                            style="--rede-cor: <?= htmlspecialchars($dados['cor']) ?>;"
                                            aria-label="<?= htmlspecialchars($dados['label']) ?>"
                                        >
                                            <i class="<?= $dados['icone'] ?>"></i>
                                            <span><?= htmlspecialchars($dados['label']) ?></span>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                </div> <!-- FIM CONTENT -->
                
            </div> <!-- FIM COLUNA DIREITA -->
            
        </div>

    </div>
</div>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>