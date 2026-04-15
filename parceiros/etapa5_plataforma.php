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

// Busca os dados da etapa 5 na tabela de contratos
$stmt = $pdo->prepare("SELECT deseja_publicar, rede_impacto FROM parceiro_contrato WHERE parceiro_id = ?");
$stmt->execute([$parceiro_id]);
$contrato = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Prepara as opções já salvas
$publicar_salvo = !empty($contrato['deseja_publicar']) ? json_decode($contrato['deseja_publicar'], true) : [];
if (!is_array($publicar_salvo)) $publicar_salvo = [];

$rede_impacto = $contrato['rede_impacto'] ?? '';

include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5 parceiro-step-shell">
    <div class="parceiro-step-top mb-4 mb-lg-5">
        <div class="parceiro-step-progress-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <span class="parceiro-step-kicker">Etapa 5 de 6</span>
                    <h1 class="parceiro-step-title mb-1">Uso da Plataforma</h1>
                    <p class="parceiro-step-subtitle mb-0">
                        Escolha como sua organização deseja atuar dentro da plataforma e quais frentes pretende ativar no dia a dia.
                    </p>
                </div>
                <div class="parceiro-step-indicator">83%</div>
            </div>

            <div class="progress parceiro-step-progress" role="progressbar" aria-valuenow="83" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-primary" style="width: 83%;"></div>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <aside class="parceiro-step-aside">
                <div class="parceiro-step-aside-card">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-grid-1x2-fill"></i>
                        Nesta etapa
                    </div>
                    <ul class="parceiro-step-aside-list">
                        <li>Defina o que sua organização pretende publicar ou promover dentro da plataforma.</li>
                        <li>Indique se deseja ativar sua presença na Rede de Impacto.</li>
                        <li>Ajude a configurar um uso mais alinhado ao perfil da parceria.</li>
                    </ul>
                </div>

                <div class="parceiro-step-aside-card parceiro-step-aside-highlight">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-people-fill"></i>
                        Rede de Impacto
                    </div>
                    <p class="mb-0">
                        Esse ambiente conecta sua organização a negócios aprovados, propostas de conexão e oportunidades de matchmaking estratégico.
                    </p>
                </div>
            </aside>
        </div>

        <div class="col-lg-8">
            <div class="parceiro-step-card">
                <div class="parceiro-step-card-header">
                    <div>
                        <h2 class="parceiro-step-card-title mb-1">Como você quer atuar?</h2>
                        <p class="parceiro-step-card-subtitle mb-0">
                            A plataforma Impactos Positivos é viva. Escolha as ferramentas e possibilidades que farão parte da atuação da sua organização aqui dentro.
                        </p>
                    </div>
                </div>

                <div class="parceiro-step-card-body">
                    <?php if (isset($_SESSION['erro_etapa5'])): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 parceiro-step-alert">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div><?= htmlspecialchars($_SESSION['erro_etapa5']) ?></div>
                        </div>
                        <?php unset($_SESSION['erro_etapa5']); ?>
                    <?php endif; ?>

                    <form method="POST" action="processar_etapa5.php">
                        <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Geração de Conteúdo e Oportunidades</h3>
                                <p class="parceiro-step-section-text">
                                    Marque tudo o que sua organização planeja publicar, promover ou disponibilizar dentro da plataforma.
                                </p>
                            </div>

                            <div class="row g-3">
                                <?php 
                                $publicacoes = [
                                    ["icone" => "bi-file-text", "texto" => "Artigos"],
                                    ["icone" => "bi-play-btn", "texto" => "Vídeos"],
                                    ["icone" => "bi-mic", "texto" => "Podcasts"],
                                    ["icone" => "bi-camera-video", "texto" => "Webinars"],
                                    ["icone" => "bi-megaphone", "texto" => "Editais / Chamadas"],
                                    ["icone" => "bi-calendar-event", "texto" => "Convites para Eventos"],
                                    ["icone" => "bi-lightbulb", "texto" => "Oportunidades de Mentoria"],
                                    ["icone" => "bi-mortarboard", "texto" => "Incentivos / Bolsas"],
                                    ["icone" => "bi-box", "texto" => "Produtos e Serviços"],
                                    ["icone" => "bi-graph-up-arrow", "texto" => "Investimentos"],
                                    ["icone" => "bi-heart", "texto" => "Doações estruturadas"]
                                ];

                                foreach ($publicacoes as $pub): 
                                    $checked = in_array($pub['texto'], $publicar_salvo) ? 'checked' : '';
                                ?>
                                    <div class="col-md-6">
                                        <div class="parceiro-choice-card parceiro-choice-card-icon">
                                            <div class="form-check parceiro-choice-check parceiro-choice-check-icon">
                                                <input
                                                    class="form-check-input parceiro-choice-input"
                                                    type="checkbox"
                                                    name="deseja_publicar[]"
                                                    value="<?= htmlspecialchars($pub['texto']) ?>"
                                                    id="pub_<?= md5($pub['texto']) ?>"
                                                    <?= $checked ?>
                                                >
                                                <label class="form-check-label parceiro-choice-label parceiro-choice-label-icon" for="pub_<?= md5($pub['texto']) ?>">
                                                    <span class="parceiro-choice-icon">
                                                        <i class="bi <?= $pub['icone'] ?>"></i>
                                                    </span>
                                                    <span class="parceiro-choice-title"><?= $pub['texto'] ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-highlight-box">
                                <div class="parceiro-step-highlight-head">
                                    <div class="parceiro-step-highlight-icon">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div>
                                        <h3 class="parceiro-step-section-title mb-1">Participação na Rede de Impacto</h3>
                                        <p class="parceiro-step-section-text mb-0">
                                            A Rede de Impacto é um ambiente de matchmaking inteligente, onde sua organização pode conversar com negócios aprovados, criar pontes estratégicas e receber propostas de conexão.
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="parceiro-step-label mb-3 d-block">
                                        Deseja ativar seu perfil na Rede de Impacto agora?
                                    </label>

                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="parceiro-radio-card parceiro-radio-card-center parceiro-radio-card-compact">
                                                <input
                                                    class="form-check-input parceiro-radio-input"
                                                    type="radio"
                                                    name="rede_impacto"
                                                    id="rede_sim"
                                                    value="sim"
                                                    <?= ($rede_impacto === 'sim') ? 'checked' : '' ?>
                                                    required
                                                >
                                                <span class="parceiro-radio-content">
                                                    <span class="parceiro-radio-title">Sim, quero participar</span>
                                                </span>
                                            </label>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="parceiro-radio-card parceiro-radio-card-center parceiro-radio-card-compact">
                                                <input
                                                    class="form-check-input parceiro-radio-input"
                                                    type="radio"
                                                    name="rede_impacto"
                                                    id="rede_nao"
                                                    value="nao"
                                                    <?= ($rede_impacto === 'nao') ? 'checked' : '' ?>
                                                >
                                                <span class="parceiro-radio-content">
                                                    <span class="parceiro-radio-title">Não</span>
                                                </span>
                                            </label>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="parceiro-radio-card parceiro-radio-card-center parceiro-radio-card-compact">
                                                <input
                                                    class="form-check-input parceiro-radio-input"
                                                    type="radio"
                                                    name="rede_impacto"
                                                    id="rede_avaliar"
                                                    value="avaliar_depois"
                                                    <?= ($rede_impacto === 'avaliar_depois') ? 'checked' : '' ?>
                                                >
                                                <span class="parceiro-radio-content">
                                                    <span class="parceiro-radio-title">Avaliar depois</span>
                                                </span>
                                            </label>
                                        </div>
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

                            <a href="etapa4_interesses.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Voltar
                            </a>

                            <button type="submit" class="btn-reg-submit">
                                Salvar e continuar
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
