<?php
session_start();
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Verifica se o parceiro está logado
if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php?msg=login_necessario");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Busca os dados atuais do contrato (se já existir) para pré-preencher
$stmt = $pdo->prepare("SELECT tipos_parceria, natureza_parceria FROM parceiro_contrato WHERE parceiro_id = ?");
$stmt->execute([$parceiro_id]);
$contrato = $stmt->fetch(PDO::FETCH_ASSOC);

// Decodifica o JSON para arrays do PHP (ou cria array vazio se não existir)
$tipos_salvos = !empty($contrato['tipos_parceria']) ? json_decode($contrato['tipos_parceria'], true) : [];
$natureza_salva = !empty($contrato['natureza_parceria']) ? json_decode($contrato['natureza_parceria'], true) : [];

if (!is_array($tipos_salvos)) $tipos_salvos = [];
if (!is_array($natureza_salva)) $natureza_salva = [];

include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5 parceiro-step-shell">
    <div class="parceiro-step-top mb-4 mb-lg-5">
        <div class="parceiro-step-progress-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <span class="parceiro-step-kicker">Etapa 2 de 6</span>
                    <h1 class="parceiro-step-title mb-1">Tipo de Parceria</h1>
                    <p class="parceiro-step-subtitle mb-0">
                        Selecione como sua organização deseja atuar e qual será a natureza principal da parceria.
                    </p>
                </div>
                <div class="parceiro-step-indicator">33%</div>
            </div>

            <div class="progress parceiro-step-progress" role="progressbar" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-primary" style="width: 33%;"></div>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <aside class="parceiro-step-aside">
                <div class="parceiro-step-aside-card">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-diagram-3-fill"></i>
                        Como escolher
                    </div>

                    <ul class="parceiro-step-aside-list">
                        <li>Você pode selecionar mais de um tipo de parceria.</li>
                        <li>Escolha as opções que realmente representam a forma de atuação da sua organização.</li>
                        <li>Essas informações serão usadas para estruturar sua carta-acordo e o perfil da parceria.</li>
                    </ul>
                </div>

                <div class="parceiro-step-aside-card parceiro-step-aside-highlight">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-lightbulb-fill"></i>
                        Dica
                    </div>
                    <p class="mb-0">
                        Se sua organização oferece mais de um tipo de apoio, selecione todas as frentes relevantes e marque a natureza “Múltipla” quando fizer sentido.
                    </p>
                </div>
            </aside>
        </div>

        <div class="col-lg-8">
            <div class="parceiro-step-card">
                <div class="parceiro-step-card-header">
                    <div>
                        <h2 class="parceiro-step-card-title mb-1">Como você deseja atuar?</h2>
                        <p class="parceiro-step-card-subtitle mb-0">
                            Selecione os papéis que melhor descrevem a intenção da sua organização na plataforma.
                        </p>
                    </div>
                </div>

                <div class="parceiro-step-card-body">
                    <?php if (isset($_SESSION['erro_etapa2'])): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 parceiro-step-alert">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div><?= htmlspecialchars($_SESSION['erro_etapa2']) ?></div>
                        </div>
                        <?php unset($_SESSION['erro_etapa2']); ?>
                    <?php endif; ?>

                    <form method="POST" action="processar_etapa2.php">
                        <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Tipo de Parceria</h3>
                                <p class="parceiro-step-section-text">
                                    Você pode escolher mais de uma opção para representar a forma de atuação da sua organização.
                                </p>
                            </div>

                            <div class="row g-3">
                                <?php 
                                $tipos_opcoes = [
                                    "Patrocinador Institucional",
                                    "Patrocinador Estratégico de Impacto",
                                    "Apoiador Institucional",
                                    "Apoiador Estratégico de Impacto",
                                    "Investidor de Ecossistema",
                                    "Doador de Impacto",
                                    "Mentor",
                                    "Embaixador",
                                    "Voluntário"
                                ];

                                foreach ($tipos_opcoes as $tipo): 
                                    $checked = in_array($tipo, $tipos_salvos) ? 'checked' : '';
                                ?>
                                    <div class="col-md-6">
                                        <div class="parceiro-choice-card">
                                            <div class="form-check parceiro-choice-check">
                                                <input
                                                    class="form-check-input parceiro-choice-input"
                                                    type="checkbox"
                                                    name="tipos_parceria[]"
                                                    value="<?= htmlspecialchars($tipo) ?>"
                                                    id="tipo_<?= md5($tipo) ?>"
                                                    <?= $checked ?>
                                                >
                                                <label class="form-check-label parceiro-choice-label" for="tipo_<?= md5($tipo) ?>">
                                                    <span class="parceiro-choice-title"><?= htmlspecialchars($tipo) ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Natureza da Parceria</h3>
                                <p class="parceiro-step-section-text">
                                    Indique quais tipos de recursos, apoio ou contribuição estarão presentes na parceria.
                                </p>
                            </div>

                            <div class="row g-3">
                                <?php 
                                $natureza_opcoes = [
                                    "Financeira",
                                    "Institucional",
                                    "Técnica",
                                    "Conteúdo",
                                    "Múltipla"
                                ];

                                foreach ($natureza_opcoes as $nat): 
                                    $checked = in_array($nat, $natureza_salva) ? 'checked' : '';
                                ?>
                                    <div class="col-md-6">
                                        <div class="parceiro-choice-card parceiro-choice-card-soft">
                                            <div class="form-check parceiro-choice-check">
                                                <input
                                                    class="form-check-input parceiro-choice-input"
                                                    type="checkbox"
                                                    name="natureza_parceria[]"
                                                    value="<?= htmlspecialchars($nat) ?>"
                                                    id="nat_<?= md5($nat) ?>"
                                                    <?= $checked ?>
                                                >
                                                <label class="form-check-label parceiro-choice-label" for="nat_<?= md5($nat) ?>">
                                                    <span class="parceiro-choice-title"><?= htmlspecialchars($nat) ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <div class="parceiro-step-actions">
                            <?php if (($_GET['from'] ?? '') === 'confirmacao'): ?>
                                <button type="submit" name="acao" value="confirmacao" class="btn btn-outline-primary">
                                    Salvar e voltar à revisão
                                </button>
                            <?php endif; ?>

                            <a href="etapa1_dados.php" class="btn btn-outline-secondary">
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
