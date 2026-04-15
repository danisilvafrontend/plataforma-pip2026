<?php
session_start();
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Verifica login
if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php?msg=login_necessario");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Busca os dados do contrato para pré-preencher
$stmt = $pdo->prepare("SELECT escopo_atuacao, escopo_outro, nivel_engajamento, oferece_premiacao, premio_descricao FROM parceiro_contrato WHERE parceiro_id = ?");

$stmt->execute([$parceiro_id]);
$contrato = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Decodifica JSON do escopo
$escopo_salvo = !empty($contrato['escopo_atuacao']) ? json_decode($contrato['escopo_atuacao'], true) : [];
if (!is_array($escopo_salvo)) $escopo_salvo = [];

$nivel = $contrato['nivel_engajamento'] ?? '';
$escopo_outro = $contrato['escopo_outro'] ?? '';

include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5 parceiro-step-shell">
    <div class="parceiro-step-top mb-4 mb-lg-5">
        <div class="parceiro-step-progress-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <span class="parceiro-step-kicker">Etapa 3 de 6</span>
                    <h1 class="parceiro-step-title mb-1">Definição do Combinado</h1>
                    <p class="parceiro-step-subtitle mb-0">
                        Defina o escopo da parceria, o nível de engajamento e possíveis contribuições para a premiação do ano vigente.
                    </p>
                </div>
                <div class="parceiro-step-indicator">50%</div>
            </div>

            <div class="progress parceiro-step-progress" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-primary" style="width: 50%;"></div>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <aside class="parceiro-step-aside">
                <div class="parceiro-step-aside-card">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-clipboard2-check-fill"></i>
                        Nesta etapa
                    </div>
                    <ul class="parceiro-step-aside-list">
                        <li>Defina o nível de profundidade da participação da sua organização.</li>
                        <li>Selecione os programas, frentes ou iniciativas onde a parceria irá atuar.</li>
                        <li>Informe se haverá oferta de premiação para os 4 vencedores do ano vigente.</li>
                    </ul>
                </div>

                <div class="parceiro-step-aside-card parceiro-step-aside-highlight">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-award-fill"></i>
                        Premiação anual
                    </div>
                    <p class="mb-0">
                        Caso sua organização ofereça um prêmio, descreva de forma objetiva o tipo de benefício e o valor estimado por vencedor.
                    </p>
                </div>
            </aside>
        </div>

        <div class="col-lg-8">
            <div class="parceiro-step-card">
                <div class="parceiro-step-card-header">
                    <div>
                        <h2 class="parceiro-step-card-title mb-1">O nosso acordo</h2>
                        <p class="parceiro-step-card-subtitle mb-0">
                            Defina o escopo, o envolvimento e as possibilidades de contribuição dentro da plataforma.
                        </p>
                    </div>
                </div>

                <div class="parceiro-step-card-body">
                    <?php if (isset($_SESSION['erro_etapa3'])): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 parceiro-step-alert">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div><?= htmlspecialchars($_SESSION['erro_etapa3']) ?></div>
                        </div>
                        <?php unset($_SESSION['erro_etapa3']); ?>
                    <?php endif; ?>

                    <form method="POST" action="processar_etapa3.php">
                        <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Nível de Engajamento</h3>
                                <p class="parceiro-step-section-text">
                                    Escolha o formato que melhor representa a profundidade da atuação da sua organização.
                                </p>
                            </div>

                            <div class="parceiro-radio-stack">
                                <label class="parceiro-radio-card">
                                    <input class="form-check-input parceiro-radio-input" type="radio" name="nivel_engajamento" value="básico" <?= $nivel === 'básico' ? 'checked' : '' ?> required>
                                    <span class="parceiro-radio-content">
                                        <span class="parceiro-radio-title">Básico</span>
                                        <span class="parceiro-radio-text">Presença institucional na plataforma.</span>
                                    </span>
                                </label>

                                <label class="parceiro-radio-card">
                                    <input class="form-check-input parceiro-radio-input" type="radio" name="nivel_engajamento" value="ativo" <?= $nivel === 'ativo' ? 'checked' : '' ?>>
                                    <span class="parceiro-radio-content">
                                        <span class="parceiro-radio-title">Ativo</span>
                                        <span class="parceiro-radio-text">Geração de conteúdo e participação em eventos.</span>
                                    </span>
                                </label>

                                <label class="parceiro-radio-card">
                                    <input class="form-check-input parceiro-radio-input" type="radio" name="nivel_engajamento" value="estratégico" <?= $nivel === 'estratégico' ? 'checked' : '' ?>>
                                    <span class="parceiro-radio-content">
                                        <span class="parceiro-radio-title">Estratégico</span>
                                        <span class="parceiro-radio-text">Cocriação de programas e editais direcionados.</span>
                                    </span>
                                </label>

                                <label class="parceiro-radio-card">
                                    <input class="form-check-input parceiro-radio-input" type="radio" name="nivel_engajamento" value="estruturante" <?= $nivel === 'estruturante' ? 'checked' : '' ?>>
                                    <span class="parceiro-radio-content">
                                        <span class="parceiro-radio-title">Estruturante</span>
                                        <span class="parceiro-radio-text">Patrocinador principal de eixos e pilares do ecossistema.</span>
                                    </span>
                                </label>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Escopo de Atuação</h3>
                                <p class="parceiro-step-section-text">
                                    Selecione todas as frentes, programas ou iniciativas em que a parceria irá atuar.
                                </p>
                            </div>

                            <div class="row g-3">
                                <?php 
                                $escopo_opcoes = [
                                    "Plataforma geral",
                                    "Premiação Impactos Positivos",
                                    "Programa Impact Chains",
                                    "Programa de Aceleração",
                                    "Eventos e Fóruns",
                                    "Série Narrativas de Impacto",
                                    "Rede de Impacto",
                                    "Conteúdos educacionais"
                                ];

                                foreach ($escopo_opcoes as $esc): 
                                    $checked = in_array($esc, $escopo_salvo) ? 'checked' : '';
                                ?>
                                    <div class="col-md-6">
                                        <div class="parceiro-choice-card">
                                            <div class="form-check parceiro-choice-check">
                                                <input
                                                    class="form-check-input parceiro-choice-input"
                                                    type="checkbox"
                                                    name="escopo_atuacao[]"
                                                    value="<?= htmlspecialchars($esc) ?>"
                                                    id="esc_<?= md5($esc) ?>"
                                                    <?= $checked ?>
                                                >
                                                <label class="form-check-label parceiro-choice-label" for="esc_<?= md5($esc) ?>">
                                                    <span class="parceiro-choice-title"><?= htmlspecialchars($esc) ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="col-12">
                                    <div class="parceiro-step-toggle-box">
                                        <div class="form-check m-0">
                                            <input class="form-check-input" type="checkbox" id="check_outro" <?= !empty($escopo_outro) ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-semibold" for="check_outro">
                                                Outro escopo (especifique)
                                            </label>
                                        </div>

                                        <div class="mt-3" id="div_outro" style="<?= empty($escopo_outro) ? 'display: none;' : '' ?>">
                                            <input
                                                type="text"
                                                class="form-control"
                                                name="escopo_outro"
                                                id="input_outro"
                                                value="<?= htmlspecialchars($escopo_outro) ?>"
                                                placeholder="Ex: Projeto regional exclusivo..."
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Premiação Impactos Positivos</h3>
                                <p class="parceiro-step-section-text">
                                    Informe se sua organização deseja oferecer prêmio para os 4 ganhadores da edição do ano vigente.
                                </p>
                            </div>

                            <div class="parceiro-step-toggle-box mb-3">
                                <div class="form-check m-0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="oferece_premiacao"
                                        id="premio_check"
                                        value="1"
                                        <?= !empty($contrato['oferece_premiacao']) ? 'checked' : '' ?>
                                    >
                                    <label class="form-check-label fw-semibold" for="premio_check">
                                        Sim, oferecerei prêmio para os 4 ganhadores
                                    </label>
                                </div>
                            </div>

                            <div id="div_premio" style="<?= empty($contrato['oferece_premiacao']) ? 'display: none;' : '' ?>">
                                <label class="form-label parceiro-step-label">Qual prêmio e valor de mercado?</label>
                                <textarea
                                    class="form-control"
                                    name="premio_descricao"
                                    id="premio_descricao"
                                    rows="3"
                                    placeholder="Ex: 1h de consultoria em gestão ambiental, correspondente a R$ 1.000,00 para cada vencedor."
                                    style="resize: vertical;"
                                ><?= htmlspecialchars($contrato['premio_descricao'] ?? '') ?></textarea>
                                <div class="form-text">
                                    Descreva o benefício oferecido e o valor estimado de mercado para cada um dos 4 vencedores.
                                </div>
                            </div>
                        </section>

                        <div class="parceiro-step-actions">
                            <?php if (($_GET['from'] ?? '') === 'confirmacao'): ?>
                                <button type="submit" name="acao" value="confirmacao" class="btn btn-outline-primary">
                                    Salvar e voltar à revisão
                                </button>
                            <?php endif; ?>

                            <a href="etapa2_tipo.php" class="btn btn-outline-secondary">
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
<script>
document.addEventListener("DOMContentLoaded", function () {
    const checkOutro = document.getElementById("check_outro");
    const divOutro = document.getElementById("div_outro");
    const inputOutro = document.getElementById("input_outro");

    const premioCheck = document.getElementById("premio_check");
    const divPremio = document.getElementById("div_premio");
    const premioDescricao = document.getElementById("premio_descricao");

    if (checkOutro) {
        checkOutro.addEventListener("change", function () {
            if (this.checked) {
                divOutro.style.display = "block";
                inputOutro.focus();
            } else {
                divOutro.style.display = "none";
                inputOutro.value = "";
            }
        });
    }

    if (premioCheck) {
        const atualizarPremio = function () {
            if (premioCheck.checked) {
                divPremio.style.display = "block";
                premioDescricao.required = true;
            } else {
                divPremio.style.display = "none";
                premioDescricao.required = false;
                premioDescricao.value = "";
            }
        };

        premioCheck.addEventListener("change", function () {
            atualizarPremio();
            if (premioCheck.checked) {
                premioDescricao.focus();
            }
        });

        atualizarPremio();
    }
});
</script>


<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
