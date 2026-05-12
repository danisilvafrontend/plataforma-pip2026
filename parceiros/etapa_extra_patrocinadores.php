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

// Verifica se o parceiro chegou até aqui pelo fluxo correto (etapa 5 concluída)
$stmt_check = $pdo->prepare("SELECT etapa_atual FROM parceiros WHERE id = ? LIMIT 1");
$stmt_check->execute([$parceiro_id]);
$check = $stmt_check->fetch(PDO::FETCH_ASSOC);

if (!$check || $check['etapa_atual'] < 5) {
    header("Location: etapa5_plataforma.php");
    exit;
}

// Busca dados já salvos
$stmt = $pdo->prepare("SELECT * FROM parceiro_etapa_extra WHERE parceiro_id = ? LIMIT 1");
$stmt->execute([$parceiro_id]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$objetivo_parceria   = !empty($dados['objetivo_parceria'])   ? json_decode($dados['objetivo_parceria'],   true) : [];
$objetivo_outro      = $dados['objetivo_outro']      ?? '';
$modalidade          = !empty($dados['modalidade'])          ? json_decode($dados['modalidade'],          true) : [];
$modalidade_outro    = $dados['modalidade_outro']    ?? '';
$faixa_apoio         = $dados['faixa_apoio']         ?? '';
$interesse_proposta  = !empty($dados['interesse_proposta'])  ? json_decode($dados['interesse_proposta'],  true) : [];
$observacoes         = $dados['observacoes']         ?? '';
$declara_interesse   = $dados['declara_interesse']   ?? 0;

if (!is_array($objetivo_parceria))  $objetivo_parceria  = [];
if (!is_array($modalidade))         $modalidade         = [];
if (!is_array($interesse_proposta)) $interesse_proposta = [];

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container py-5 parceiro-step-shell">
    <div class="parceiro-step-top mb-4 mb-lg-5">
        <div class="parceiro-step-progress-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <span class="parceiro-step-kicker text-warning">
                        <i class="bi bi-star-fill me-1"></i>Etapa Extra
                    </span>
                    <h1 class="parceiro-step-title mb-1">Patrocinadores &amp; Investidores</h1>
                    <p class="parceiro-step-subtitle mb-0">
                        Etapa destinada a organizações, marcas, fundos e parceiros estratégicos interessados em apoiar, financiar ou desenvolver iniciativas junto ao ecossistema Impactos Positivos.
                    </p>
                </div>
                <div class="parceiro-step-indicator" style="background: var(--bs-warning-bg-subtle, #fff8e1); color: #b45309;">Extra</div>
            </div>
            <div class="progress parceiro-step-progress" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: 100%; background-color: #f59e0b;"></div>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        <!-- ASIDE -->
        <div class="col-lg-4">
            <aside class="parceiro-step-aside">
                <div class="parceiro-step-aside-card">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-stars"></i>
                        Sobre esta etapa
                    </div>
                    <ul class="parceiro-step-aside-list">
                        <li>Informe o objetivo principal da sua organização na parceria.</li>
                        <li>Indique a modalidade de interesse e a faixa estimada de apoio ou investimento.</li>
                        <li>Declare interesse em receber proposta ou agendar conversa estratégica.</li>
                        <li>Use o campo de observações para compartilhar expectativas e metas.</li>
                    </ul>
                </div>

                <div class="parceiro-step-aside-card parceiro-step-aside-highlight" style="border-left-color: #f59e0b;">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-info-circle-fill"></i>
                        Próximos passos
                    </div>
                    <p class="mb-0">
                        Após preencher esta etapa, você irá para a área jurídica para finalizar seu cadastro e assinar a Carta-Acordo.
                    </p>
                </div>
            </aside>
        </div>

        <!-- CARD PRINCIPAL -->
        <div class="col-lg-8">
            <div class="parceiro-step-card">
                <div class="parceiro-step-card-header">
                    <div>
                        <h2 class="parceiro-step-card-title mb-1">Perfil de Patrocínio e Investimento</h2>
                        <p class="parceiro-step-card-subtitle mb-0">
                            Preencha as informações abaixo para que a equipe Impactos Positivos possa criar a melhor proposta de parceria para a sua organização.
                        </p>
                    </div>
                </div>

                <div class="parceiro-step-card-body">

                    <?php if (isset($_SESSION['erro_etapa_extra'])): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 parceiro-step-alert">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div><?= htmlspecialchars($_SESSION['erro_etapa_extra']) ?></div>
                        </div>
                        <?php unset($_SESSION['erro_etapa_extra']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['sucesso_etapa_extra'])): ?>
                        <div class="alert alert-success d-flex align-items-start gap-2 parceiro-step-alert">
                            <i class="bi bi-check-circle-fill mt-1"></i>
                            <div><?= htmlspecialchars($_SESSION['sucesso_etapa_extra']) ?></div>
                        </div>
                        <?php unset($_SESSION['sucesso_etapa_extra']); ?>
                    <?php endif; ?>

                    <form method="POST" action="processar_etapa_extra.php">

                        <!-- BLOCO 1 — OBJETIVO DA PARCERIA -->
                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Objetivo da Parceria</h3>
                                <p class="parceiro-step-section-text">
                                    Qual o principal interesse da sua organização ao se tornar parceira da Impactos Positivos?
                                </p>
                            </div>

                            <?php
                            $objetivos = [
                                'Fortalecimento institucional e posicionamento de marca',
                                'Apoio a iniciativas de impacto socioambiental',
                                'Conexão com negócios e soluções inovadoras',
                                'ESG, sustentabilidade e reputação',
                                'Relacionamento com ecossistema e lideranças',
                                'Investimento em negócios de impacto',
                                'Desenvolvimento de projetos e programas especiais',
                            ];
                            ?>
                            <div class="row g-2">
                                <?php foreach ($objetivos as $obj):
                                    $checked = in_array($obj, $objetivo_parceria) ? 'checked' : '';
                                ?>
                                    <div class="col-12">
                                        <label class="match-card match-card-check">
                                            <input class="visually-hidden match-check" type="checkbox" name="objetivo_parceria[]" value="<?= htmlspecialchars($obj) ?>" <?= $checked ?>>
                                            <div class="match-card-inner">
                                                <div class="match-card-content">
                                                    <div class="match-card-title"><?= htmlspecialchars($obj) ?></div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Outro -->
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="chk_obj_outro" <?= !empty($objetivo_outro) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold" for="chk_obj_outro">Outro</label>
                                    </div>
                                    <div id="div_obj_outro" style="<?= empty($objetivo_outro) ? 'display:none;' : '' ?> margin-top:0.5rem;">
                                        <input type="text" class="form-control form-control-sm" name="objetivo_outro" id="inp_obj_outro"
                                            value="<?= htmlspecialchars($objetivo_outro) ?>" placeholder="Descreva o objetivo...">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- BLOCO 2 — MODALIDADE DE INTERESSE -->
                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Modalidade de Interesse</h3>
                                <p class="parceiro-step-section-text">
                                    De que forma sua organização prefere apoiar ou se envolver com o ecossistema?
                                </p>
                            </div>

                            <?php
                            $modalidades = [
                                'Patrocínio institucional',
                                'Patrocínio de eventos, premiações ou campanhas',
                                'Apoio a programas especiais e aceleração',
                                'Investimento em ecossistema ou negócios de impacto',
                                'Apoio via permuta, serviços ou produtos',
                                'Cocriação de iniciativas estratégicas',
                            ];
                            ?>
                            <div class="row g-2">
                                <?php foreach ($modalidades as $mod):
                                    $checked = in_array($mod, $modalidade) ? 'checked' : '';
                                ?>
                                    <div class="col-md-6">
                                        <label class="match-card match-card-check">
                                            <input class="visually-hidden match-check" type="checkbox" name="modalidade[]" value="<?= htmlspecialchars($mod) ?>" <?= $checked ?>>
                                            <div class="match-card-inner">
                                                <div class="match-card-content">
                                                    <div class="match-card-title"><?= htmlspecialchars($mod) ?></div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Outro -->
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="chk_mod_outro" <?= !empty($modalidade_outro) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold" for="chk_mod_outro">Outro</label>
                                    </div>
                                    <div id="div_mod_outro" style="<?= empty($modalidade_outro) ? 'display:none;' : '' ?> margin-top:0.5rem;">
                                        <input type="text" class="form-control form-control-sm" name="modalidade_outro" id="inp_mod_outro"
                                            value="<?= htmlspecialchars($modalidade_outro) ?>" placeholder="Descreva a modalidade...">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- BLOCO 3 — FAIXA DE APOIO -->
                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Faixa Estimada de Apoio ou Investimento</h3>
                                <p class="parceiro-step-section-text">
                                    Isso nos ajuda a criar uma proposta compatível com o porte e os objetivos da sua organização.
                                </p>
                            </div>

                            <?php
                            $faixas = [
                                'Ate 10k'             => 'Até R$ 10 mil',
                                '10k a 50k'           => 'R$ 10 mil a R$ 50 mil',
                                '50k a 100k'          => 'R$ 50 mil a R$ 100 mil',
                                'Acima de 100k'       => 'Acima de R$ 100 mil',
                                'Alinhar diretamente' => 'Prefiro alinhar diretamente com a equipe',
                            ];
                            ?>
                            <div class="row g-3">
                                <?php foreach ($faixas as $val => $label): ?>
                                    <div class="col-md-4 col-6">
                                        <label class="match-card match-card-radio match-card-center">
                                            <input class="visually-hidden match-radio" type="radio" name="faixa_apoio" value="<?= htmlspecialchars($val) ?>" <?= ($faixa_apoio === $val) ? 'checked' : '' ?> required>
                                            <div class="match-card-inner">
                                                <div class="match-card-title"><?= htmlspecialchars($label) ?></div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <!-- BLOCO 4 — INTERESSE EM PROPOSTA -->
                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Interesse em Proposta Personalizada</h3>
                                <p class="parceiro-step-section-text">
                                    Como você gostaria de avançar com a Impactos Positivos?
                                </p>
                            </div>

                            <?php
                            $propostas = [
                                'Sim, desejo receber uma proposta institucional',
                                'Sim, desejo agendar uma conversa estratégica',
                                'Já recebi uma proposta da Impactos Positivos e concordo em seguir com os próximos passos relacionados à parceria, patrocínio ou investimento.',
                                'Não neste momento',
                            ];
                            ?>
                            <div class="row g-2">
                                <?php foreach ($propostas as $prop):
                                    $checked = in_array($prop, $interesse_proposta) ? 'checked' : '';
                                ?>
                                    <div class="col-12">
                                        <label class="match-card match-card-check">
                                            <input class="visually-hidden match-check" type="checkbox" name="interesse_proposta[]" value="<?= htmlspecialchars($prop) ?>" <?= $checked ?>>
                                            <div class="match-card-inner">
                                                <div class="match-card-content">
                                                    <div class="match-card-title"><?= htmlspecialchars($prop) ?></div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <!-- BLOCO 5 — OBSERVAÇÕES -->
                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Observações e Objetivos</h3>
                                <p class="parceiro-step-section-text">
                                    Espaço aberto para compartilhar expectativas, áreas de interesse, metas da parceria ou oportunidades de colaboração.
                                </p>
                            </div>
                            <textarea
                                name="observacoes"
                                id="observacoes"
                                class="form-control"
                                rows="5"
                                maxlength="3000"
                                placeholder="Descreva livremente seus objetivos, metas e expectativas com essa parceria..."
                            ><?= htmlspecialchars($observacoes) ?></textarea>
                            <div class="form-text mt-1">Máximo de 3.000 caracteres.</div>
                        </section>

                        <!-- BLOCO 6 — CONCLUSÃO E DECLARAÇÃO -->
                        <section class="parceiro-step-section">
                            <div class="parceiro-step-highlight-box parceiro-step-highlight-box-success">
                                <div class="parceiro-step-highlight-head">
                                    <div class="parceiro-step-highlight-icon parceiro-step-highlight-icon-success">
                                        <i class="bi bi-patch-check-fill"></i>
                                    </div>
                                    <div>
                                        <h3 class="parceiro-step-section-title mb-1">Conclusão e Próximos Passos</h3>
                                        <p class="parceiro-step-section-text mb-0">
                                            Após o envio deste formulário, a equipe administrativa e de relacionamento da Impactos Positivos poderá entrar em contato para os próximos passos, incluindo alinhamentos estratégicos, reuniões, propostas institucionais, planos de parceria, contratos, invoices e demais informações necessárias para continuidade da parceria.
                                        </p>
                                    </div>
                                </div>

                                <div class="parceiro-step-toggle-box mt-4">
                                    <div class="form-check m-0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="declara_interesse"
                                            id="declara_interesse"
                                            value="1"
                                            <?= $declara_interesse ? 'checked' : '' ?>
                                            required
                                        >
                                        <label class="form-check-label fw-semibold" for="declara_interesse">
                                            Declaro interesse em avançar nas conversas relacionadas a patrocínio, investimento ou parceria estratégica com a Impactos Positivos.
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- AÇÕES -->
                        <div class="parceiro-step-actions">
                            <a href="etapa5_plataforma.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Voltar
                            </a>
                            <button type="submit" class="btn-reg-submit">
                                Salvar e avançar
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
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.match-radio').forEach(function (radio) {
        const activate = function (r) {
            const name = r.getAttribute('name');
            document.querySelectorAll('.match-radio[name="' + name + '"]').forEach(function (item) {
                const card = item.closest('.match-card');
                if (card) card.classList.remove('selected');
            });
            const card = r.closest('.match-card');
            if (card) card.classList.add('selected');
        };
        radio.addEventListener('change', function () { activate(this); });
        if (radio.checked) activate(radio);
    });

    document.querySelectorAll('.match-check').forEach(function (check) {
        check.addEventListener('change', function () {
            const card = this.closest('.match-card');
            if (card) card.classList.toggle('selected', this.checked);
        });
        if (check.checked) {
            const card = check.closest('.match-card');
            if (card) card.classList.add('selected');
        }
    });

    document.querySelectorAll('[id^="chk_"]').forEach(function (chk) {
        const suffix = chk.id.replace('chk_', '');
        const div    = document.getElementById('div_' + suffix);
        const inp    = document.getElementById('inp_' + suffix);
        if (!div) return;
        chk.addEventListener('change', function () {
            div.style.display = this.checked ? 'block' : 'none';
            if (!this.checked && inp) inp.value = '';
            if (this.checked && inp) inp.focus();
        });
    });

    const ta = document.getElementById('observacoes');
    if (ta) {
        const hint = ta.nextElementSibling;
        ta.addEventListener('input', function () {
            const restante = 3000 - this.value.length;
            if (hint) hint.textContent = restante + ' caracteres restantes.';
        });
    }
});
</script>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
