<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$pageTitle = 'Etapa 7 — Avaliação de Impacto';
$config = require __DIR__ . '/../app/config/db.php';

$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id = (int)($_GET['id'] ?? $_SESSION['negocio_id'] ?? 0);
if ($negocio_id === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}

$_SESSION['negocio_id'] = $negocio_id;

$stmt = $pdo->prepare("
    SELECT n.*, e.eh_fundador
    FROM negocios n
    JOIN empreendedores e ON n.empreendedor_id = e.id
    WHERE n.id = ? AND n.empreendedor_id = ?
");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$negocio) {
    die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);
}

$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca dados de impacto já salvos
$stmtImp = $pdo->prepare("SELECT * FROM negocio_impacto WHERE negocio_id = ?");
$stmtImp->execute([$negocio_id]);
$impacto = $stmtImp->fetch(PDO::FETCH_ASSOC) ?: [];

// Decodifica arrays JSON
if (!empty($impacto['beneficiarios'])) {
    $impacto['beneficiarios'] = json_decode($impacto['beneficiarios'], true) ?: [];
} else {
    $impacto['beneficiarios'] = [];
}

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container py-4" style="max-width: 980px;">
    <div class="mb-4">
        <h1 class="emp-page-title mb-1">Etapa 7 — Avaliação de Impacto</h1>
        <p class="emp-page-subtitle mb-0">Preencha as informações sobre intencionalidade, medição e resultados de impacto do negócio.</p>
    </div>

    <?php
        $etapaAtual = 7;
        include __DIR__ . '/../app/views/partials/progress.php';
        include __DIR__ . '/../app/views/partials/intro_text_impacto.php';
    ?>

    <?php if (!empty($_SESSION['errors_etapa7'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Corrija os erros:</h6>
            <ul class="mb-0 ps-3 small">
            <?php foreach ($_SESSION['errors_etapa7'] as $erro): ?>
                <li><?= htmlspecialchars($erro) ?></li>
            <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['errors_etapa7']); ?>
    <?php endif; ?>

    <form action="/negocios/processar_etapa7.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="cadastro">

        <div class="form-section mb-4">
            <div class="form-section-title">
                <i class="bi bi-bullseye"></i> Intencionalidade e tipo de impacto
            </div>

            <div class="mb-4">
                <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Qual das opções melhor representa a relação entre geração de receita e missão do seu negócio? *</label>
                <?php
                $opcoesIntencionalidade = [
                    "integrado" => "Lucro com impacto intencional integrado ao modelo. A geração de receita está diretamente ligada à solução de um problema social ou ambiental. O impacto positivo faz parte central do modelo de negócio e é intencional.",
                    "prioridade" => "Missão de impacto como prioridade principal. A razão de existir do negócio é gerar impacto social e/ou ambiental. A sustentabilidade financeira é importante, mas serve principalmente para viabilizar a missão.",
                    "secundario" => "Lucro como foco principal, com impacto secundário. O principal objetivo do negócio é o retorno financeiro. O impacto positivo pode existir, mas não é o foco central nem está estruturado como parte estratégica do modelo."
                ];
                foreach ($opcoesIntencionalidade as $chave => $texto) {
                    $valorSalvo = $impacto['intencionalidade'] ?? '';
                    $checked = ($valorSalvo === $chave) ? 'checked' : '';
                    ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio"
                            name="intencionalidade" value="<?= $chave ?>"
                            id="intenc_<?= $chave ?>" <?= $checked ?> required>
                        <label class="form-check-label" for="intenc_<?= $chave ?>">
                            <?= $texto ?>
                        </label>
                    </div>
                <?php } ?>
            </div>

            <div class="mb-0">
                <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Como você classificaria o tipo de impacto que seu negócio gera hoje? *</label>
                <select name="tipo_impacto" class="form-select" required>
                    <?php
                    $opcoesTipoImpacto = [
                        "Impacto direto – atinge beneficiários de forma imediata e mensurável.",
                        "Impacto indireto – afeta o entorno, cadeia de valor ou sociedade como consequência da atuação.",
                        "Impacto em cadeia – influencia atores secundários por meio de parceiros, políticas públicas ou clientes.",
                        "Impacto sistêmico – contribui para transformação estrutural de um setor, território ou comportamento social."
                    ];
                    foreach ($opcoesTipoImpacto as $op) {
                        $sel = ($impacto['tipo_impacto'] ?? '') === $op ? 'selected' : '';
                        echo "<option $sel>$op</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="form-section mb-4">
            <div class="form-section-title">
                <i class="bi bi-people"></i> Beneficiários e alcance
            </div>

            <div class="mb-4">
                <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Quem são os principais grupos beneficiados pelo seu negócio? *</label>
                <?php
                $listaB = [
                    "Agricultores familiares","Crianças e adolescentes","Ex-infratores","Extrativistas","Idosos","Indígenas","Juventude",
                    "LGBTQIAP+","Migrantes","Minorias étnicas","Mulheres","Pessoas com deficiência","Pessoas com problemas de saúde",
                    "Pessoas de baixa renda","Pessoas em risco de tráfico de pessoas","Pessoas em situação de rua","Pessoas em situação de violência",
                    "Quilombolas","Trabalhadores migrantes, apátridas ou comunidades vulneráveis","Comunidade local","Futuras gerações","População em geral","Outro"
                ];
                $selecionadosB = $impacto['beneficiarios'];
                foreach ($listaB as $b): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="beneficiarios[]" value="<?= $b ?>"
                            id="<?= md5($b) ?>" <?= in_array($b, $selecionadosB) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="<?= md5($b) ?>"><?= $b ?></label>
                    </div>
                <?php endforeach; ?>

                <input type="text" name="beneficiario_outro" id="beneficiario_outro"
                    class="form-control mt-2 <?= in_array('Outro', $selecionadosB) ? '' : 'd-none' ?>"
                    value="<?= htmlspecialchars($impacto['beneficiario_outro'] ?? '') ?>"
                    placeholder="Se marcou 'Outro', especifique aqui"
                    maxlength="120"
                    <?= in_array('Outro', $selecionadosB) ? 'required' : '' ?>>
            </div>

            <div class="mb-0">
                <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Alcance do impacto – beneficiários diretos nos últimos 2 anos *</label>
                <select name="alcance" class="form-select" required>
                    <?php
                    $opcoesAlcance = ["1 a 50","51 a 100","101 a 200","201 a 500","Acima de 500"];
                    foreach ($opcoesAlcance as $op) {
                        $sel = ($impacto['alcance'] ?? '') === $op ? 'selected' : '';
                        echo "<option $sel>$op</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="form-section mb-4">
            <div class="form-section-title">
                <i class="bi bi-bar-chart"></i> Métricas e medição
            </div>

            <div class="mb-4">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Quais indicadores você já utiliza (formal ou informalmente)? <small class="text-muted">(Opcional)</small></label>
                <small class="text-muted d-block mb-3">Especifique os indicadores que utiliza em cada dimensão ESG (máx. 300 caracteres cada).</small>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold" for="indicador_ambiental">
                            <i class="bi bi-tree text-success me-1"></i> Ambiental
                        </label>
                        <textarea name="indicador_ambiental" id="indicador_ambiental"
                            class="form-control" rows="3" maxlength="300"
                            placeholder="Ex: toneladas de CO₂ evitadas, área preservada, resíduos reciclados..."><?= htmlspecialchars($impacto['indicador_ambiental'] ?? '') ?></textarea>
                        <div class="form-text">Máx. 300 caracteres.</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold" for="indicador_social">
                            <i class="bi bi-people text-primary me-1"></i> Social
                        </label>
                        <textarea name="indicador_social" id="indicador_social"
                            class="form-control" rows="3" maxlength="300"
                            placeholder="Ex: número de pessoas atendidas, empregos gerados, melhoria em saúde/educação..."><?= htmlspecialchars($impacto['indicador_social'] ?? '') ?></textarea>
                        <div class="form-text">Máx. 300 caracteres.</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold" for="indicador_governanca">
                            <i class="bi bi-shield-check text-warning-emphasis me-1"></i> Governança
                        </label>
                        <textarea name="indicador_governanca" id="indicador_governanca"
                            class="form-control" rows="3" maxlength="300"
                            placeholder="Ex: políticas de compliance, diversidade na liderança, transparência com stakeholders..."><?= htmlspecialchars($impacto['indicador_governanca'] ?? '') ?></textarea>
                        <div class="form-text">Máx. 300 caracteres.</div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> A empresa mede seu impacto socioambiental? *</label>
                <select name="medicao" class="form-select" required>
                    <?php
                    $opcoesMedicao = [
                        "Sim – utilizamos auditoria ou certificação externa",
                        "Sim – fazemos medição e controle internamente",
                        "Ainda não medimos, mas temos indicadores definidos",
                        "Ainda não medimos e não temos indicadores"
                    ];
                    foreach ($opcoesMedicao as $op) {
                        $sel = ($impacto['medicao'] ?? '') === $op ? 'selected' : '';
                        echo "<option $sel>$op</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-0">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Existe algum tipo de reporte ou prestação de contas do impacto? *</label>
                <select name="reporte" class="form-select" required>
                    <?php
                    $opcoesReporte = [
                        "Sim – relatórios regulares para investidores, apoiadores ou público geral",
                        "Sim – relatórios internos não publicados",
                        "Não – mas pretendemos criar esse processo",
                        "Não fazemos nenhum tipo de reporte de impacto"
                    ];
                    foreach ($opcoesReporte as $op) {
                        $sel = ($impacto['reporte'] ?? '') === $op ? 'selected' : '';
                        echo "<option $sel>$op</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="form-section mb-4">
            <div class="form-section-title">
                <i class="bi bi-graph-up-arrow"></i> Resultados e evidências
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold"><i class="bi bi-graph-up-arrow text-secondary me-1"></i> Quais são os resultados de impacto mais relevantes alcançados até hoje? <small>(Opcional)</small></label>
                <small class="text-muted d-block mb-2">Descreva brevemente os principais resultados (até 1000 caracteres).</small>
                <textarea name="resultados" class="form-control" rows="4" maxlength="1000"><?= htmlspecialchars($impacto['resultados'] ?? '') ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="bi bi-link-45deg text-secondary me-1"></i> Links externos (máx. 4) <small>(Opcional)</small></label>
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> <strong>Exemplos de links:</strong> vídeos institucionais, apresentações (Pitch Deck no Canva/Google Slides), matérias na mídia, ou painéis interativos de resultados.
                    </div>
                    <div id="links-container">
                        <input type="url" name="resultados_link[]" class="form-control mb-2" placeholder="https://...">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addLink()"><i class="bi bi-plus-circle me-1"></i> Adicionar link</button>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="bi bi-file-earmark-pdf text-danger me-1"></i> PDFs (máx. 4, até 5MB cada) <small>(Opcional)</small></label>
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> <strong>Exemplos de PDFs:</strong> relatórios de impacto anuais, certificados, dossiês de resultados ou documentos de validação do negócio.
                    </div>
                    <div id="pdfs-container">
                        <input type="file" name="resultados_pdf[]" class="form-control mb-2" accept="application/pdf">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="addPdf()"><i class="bi bi-plus-circle me-1"></i> Adicionar PDF</button>
                </div>
            </div>
        </div>

        <div class="form-section mb-4">
            <div class="form-section-title">
                <i class="bi bi-signpost-2"></i> Próximos passos
            </div>

            <div class="mb-0">
                <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Quais os próximos passos planejados para ampliar ou fortalecer o impacto? *</label>
                <small class="text-muted d-block mb-2">Conte-nos como pretende escalar, medir ou qualificar ainda mais seu impacto nos próximos 12 a 24 meses (até 1000 caracteres).</small>
                <textarea name="proximos_passos" class="form-control" rows="4" maxlength="1000"><?= htmlspecialchars($impacto['proximos_passos'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap justify-content-between">
            <a href="/negocios/etapa6_financeiro.php?id=<?= $negocio_id ?>" class="btn-emp-outline">
                <i class="bi bi-arrow-left me-1"></i> Etapa Anterior
            </a>
            <button type="submit" class="btn-emp-primary">
                <i class="bi bi-arrow-right me-1"></i> Salvar e Continuar
            </button>
        </div>
    </form>
</div>

<script>
function addLink() {
    const container = document.getElementById('links-container');
    if (container.querySelectorAll('input').length >= 4) { alert('Máximo de 4 links permitidos.'); return; }
    const input = document.createElement('input');
    input.type = 'url'; input.name = 'resultados_link[]'; input.className = 'form-control mb-2'; input.placeholder = 'https://...';
    container.appendChild(input);
}
function addPdf() {
    const container = document.getElementById('pdfs-container');
    if (container.querySelectorAll('input').length >= 4) { alert('Máximo de 4 PDFs permitidos.'); return; }
    const input = document.createElement('input');
    input.type = 'file'; input.name = 'resultados_pdf[]'; input.className = 'form-control mb-2'; input.accept = 'application/pdf';
    container.appendChild(input);
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const outrosBenef = document.querySelector("input[name='beneficiarios[]'][value='Outro']");
    const inputBenefOutro = document.getElementById('beneficiario_outro');
    if (outrosBenef && inputBenefOutro) {
        outrosBenef.addEventListener('change', function() {
            inputBenefOutro.classList.toggle('d-none', !this.checked);
            inputBenefOutro.required = this.checked;
            if (!this.checked) inputBenefOutro.value = '';
        });
    }

    document.querySelectorAll("input[type='text'], textarea").forEach(campo => {
        campo.addEventListener('input', function() {
            const letras = (this.value.match(/[a-zA-ZÀ-ÿ]/g) || []).length;
            this.setCustomValidity(letras > 0 && letras < 5 ? 'Digite um texto válido (mínimo 5 letras reais).' : '');
        });
    });
});
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>