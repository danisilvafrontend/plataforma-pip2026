<?php
declare(strict_types=1);
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
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

// Busca dados do negócio
$stmt = $pdo->prepare("SELECT * FROM negocios WHERE id = ? AND empreendedor_id = ?");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$negocio) {
    die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);
}

// Busca dados de impacto já cadastrados
$stmt = $pdo->prepare("SELECT * FROM negocio_impacto WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$impacto = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Decodifica arrays
$beneficiarios = json_decode($impacto['beneficiarios'] ?? '[]', true);
$metricas = json_decode($impacto['metricas'] ?? '[]', true);
$formas_medicao = json_decode($impacto['formas_medicao'] ?? '[]', true);
$links = json_decode($impacto['resultados_links'] ?? '[]', true);
$pdfs = json_decode($impacto['resultados_pdfs'] ?? '[]', true);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-4">Etapa 7 - Avaliação de Impacto</h1>
        <a href="/empreendedores/meus-negocios.php" class="btn btn-secondary">← Voltar aos negócios</a>
    </div>
    
    <?php
        include __DIR__ . '/../app/views/partials/intro_text_impacto.php';
    ?>

    <form action="/negocios/processar_etapa7.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="editar">


        <!-- Exemplo: Intencionalidade -->
        <div class="mb-3">
            <label class="form-label">
                <i class="bi bi-eye text-secondary me-1"></i> Qual das opções melhor representa a relação entre geração de receita e missão do seu negócio?
            </label>

            <?php
            $opcoesIntencionalidade = [
                "Lucro com impacto intencional integrado ao modelo. A geração de receita está diretamente ligada à solução de um problema social ou ambiental. O impacto positivo faz parte central do modelo de negócio e é intencional.",
                "Missão de impacto como prioridade principal. A razão de existir do negócio é gerar impacto social e/ou ambiental. A sustentabilidade financeira é importante, mas serve principalmente para viabilizar a missão.",
                "Lucro como foco principal, com impacto secundário. O principal objetivo do negócio é o retorno financeiro. O impacto positivo pode existir, mas não é o foco central nem está estruturado como parte estratégica do modelo."
            ];

            foreach ($opcoesIntencionalidade as $op) {
                $checked = ($impacto['intencionalidade'] ?? '') === $op ? 'checked' : '';
                ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio"
                        name="intencionalidade" value="<?= htmlspecialchars($op) ?>"
                        id="<?= md5($op) ?>" <?= $checked ?> required>
                    <label class="form-check-label" for="<?= md5($op) ?>">
                        <?= nl2br($op) ?>
                    </label>
                </div>
            <?php } ?>
        </div>

        <!-- 2. Tipo de impacto -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Como você classificaria o tipo de impacto que seu negócio gera hoje?</label>
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

        <!-- 3. Beneficiários -->
        <div class="mb-3">
            <label class="form-label">
                <i class="bi bi-eye text-secondary me-1"></i> Quem são os principais grupos beneficiados pelo seu negócio? (até 3)
            </label>
            <?php
            $beneficiariosLista = [
                "Agricultores familiares","Crianças e adolescentes","Ex-infratores","Extrativistas","Idosos","Indígenas","Juventude",
                "LGBTQIAP+","Migrantes","Minorias étnicas","Mulheres","Pessoas com deficiência","Pessoas com problemas de saúde",
                "Pessoas de baixa renda","Pessoas em risco de tráfico de pessoas","Pessoas em situação de rua","Pessoas em situação de violência",
                "Quilombolas","Trabalhadores migrantes, apátridas ou comunidades vulneráveis","Comunidade local","Futuras gerações","População em geral","Outro"
            ];
            foreach ($beneficiariosLista as $b): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="beneficiarios[]" value="<?= $b ?>"
                        id="<?= md5($b) ?>" <?= in_array($b, $beneficiarios) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="<?= md5($b) ?>"><?= $b ?></label>
                </div>
            <?php endforeach; ?>

            <!-- Campo "Outro" -->
            <input type="text" name="beneficiario_outro" id="beneficiario_outro"
                class="form-control mt-2 <?= in_array("Outro", $beneficiarios) ? '' : 'd-none' ?>"
                value="<?= htmlspecialchars($impacto['beneficiario_outro'] ?? '') ?>"
                placeholder="Se marcou 'Outro', especifique aqui"
                maxlength="120"
                <?= in_array("Outro", $beneficiarios) ? 'required' : '' ?>>
        </div>

        <!-- 4. Alcance -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Alcance do impacto – beneficiários diretos nos últimos 2 anos</label>
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

        <!-- 5. Métricas -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Métricas e indicadores utilizados para mensurar o impacto</label>
            <?php
            $metricasLista = [
                "Número de pessoas atendidas","Geração de renda ou empregos","Redução de emissões de CO₂",
                "Área preservada ou protegida","Resíduos reciclados ou reaproveitados","Melhoria em indicadores de saúde ou educação",
                "Área reflorestada, regenerada ou recuperada","Outro"
            ];
            foreach ($metricasLista as $m): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="metricas[]" value="<?= $m ?>"
                        id="<?= md5($m) ?>" <?= in_array($m, $metricas) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="<?= md5($m) ?>"><?= $m ?></label>
                </div>
            <?php endforeach; ?>

            <!-- Campo "Outro" -->
            <input type="text" name="metrica_outro" id="metrica_outro"
                class="form-control mt-2 <?= in_array("Outro", $metricas) ? '' : 'd-none' ?>"
                value="<?= htmlspecialchars($impacto['metrica_outro'] ?? '') ?>"
                placeholder="Se marcou 'Outro', especifique aqui"
                maxlength="120"
                <?= in_array("Outro", $metricas) ? 'required' : '' ?>>
        </div>


        <!-- 6. Medição -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> A empresa mede seu impacto socioambiental?</label>
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

        <!-- 7. Como é medido -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Como o impacto é medido hoje?</label>
            <?php
            $formasLista = [
                "Ferramentas e frameworks reconhecidos (ex: GRI, IRIS+, SDG Compass, GIIRS, SROI etc.)",
                "Relatórios internos manuais ou dashboards próprios",
                "Parcerias com especialistas, consultorias ou ONGs",
                "Não fazemos medição formal ainda",
                "Outro"
            ];
            foreach ($formasLista as $fm): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="formas_medicao[]" value="<?= $fm ?>"
                        id="<?= md5($fm) ?>" <?= in_array($fm, $formas_medicao) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="<?= md5($fm) ?>"><?= $fm ?></label>
                </div>
            <?php endforeach; ?>

            <!-- Campo "Outro" -->
            <input type="text" name="forma_outro" id="forma_outro"
                class="form-control mt-2 <?= in_array("Outro", $formas_medicao) ? '' : 'd-none' ?>"
                value="<?= htmlspecialchars($impacto['forma_outro'] ?? '') ?>"
                placeholder="Se marcou 'Outro', especifique aqui"
                maxlength="120"
                <?= in_array("Outro", $formas_medicao) ? 'required' : '' ?>>
        </div>

        <!-- 8. Reporte -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Existe algum tipo de reporte ou prestação de contas do impacto?</label>
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


        <h3>Resultados</h3>
        <div class="mb-3">
            <label class="form-label fw-bold"><i class="bi bi-graph-up-arrow text-secondary me-1"></i> Quais são os resultados de impacto mais relevantes alcançados até hoje?</label>
            <small class="text-muted d-block mb-2">Descreva brevemente os principais resultados (até 1000 caracteres).</small>
            <textarea name="resultados" class="form-control" rows="4" maxlength="1000"><?= htmlspecialchars($impacto['resultados'] ?? '') ?></textarea>

            <div class="row mt-4">
                <!-- Links externos -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="bi bi-link-45deg text-secondary me-1"></i> Links externos (máx. 4)</label>
                    
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> <strong>Exemplos de links:</strong> vídeos institucionais, apresentações (Pitch Deck no Canva/Google Slides), matérias na mídia, ou painéis interativos de resultados.
                    </div>

                    <div id="links-container">
                        <?php
                        if (!empty($links)):
                            foreach ($links as $link): ?>
                                <input type="url" name="resultados_link[]" class="form-control mb-2"
                                       value="<?= htmlspecialchars($link) ?>" placeholder="https://...">
                            <?php endforeach;
                        endif; ?>
                        <input type="url" name="resultados_link[]" class="form-control mb-2" placeholder="Adicionar novo link">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addLink()"><i class="bi bi-plus-circle me-1"></i> Adicionar link</button>
                </div>

                <!-- PDFs -->
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold"><i class="bi bi-file-earmark-pdf text-danger me-1"></i> PDFs (máx. 4, até 5MB cada)</label>
                    
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> <strong>Exemplos de PDFs:</strong> relatórios de impacto anuais, certificados, dossiês de resultados ou documentos de validação do negócio.
                    </div>

                    <div id="pdfs-container">
                        <?php
                        if (!empty($pdfs)):
                            foreach ($pdfs as $pdf): ?>
                                <div class="mb-2 p-2 border rounded bg-light d-flex justify-content-between align-items-center">
                                    <a href="/<?= htmlspecialchars($pdf) ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 70%;"><i class="bi bi-file-earmark-text me-1"></i> Ver PDF atual</a>
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" name="remover_pdf[]" value="<?= htmlspecialchars($pdf) ?>" id="remover<?= md5($pdf) ?>">
                                        <label class="form-check-label text-danger small" for="remover<?= md5($pdf) ?>">Remover</label>
                                    </div>
                                </div>
                            <?php endforeach;
                        endif; ?>
                        <input type="file" name="resultados_pdf[]" class="form-control mb-2" accept="application/pdf">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="addPdf()"><i class="bi bi-plus-circle me-1"></i> Adicionar PDF</button>
                </div>
            </div>
        </div>


        <!-- 10. Próximos passos -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Quais os próximos passos planejados para ampliar ou fortalecer o impacto?</label>
            <small class="text-muted">Conte-nos como pretende escalar, medir ou qualificar ainda mais seu impacto nos próximos 12 a 24 meses (até 1000 caracteres).</small>
            <textarea name="proximos_passos" class="form-control" rows="4" maxlength="1000"><?= htmlspecialchars($impacto['proximos_passos'] ?? '') ?></textarea>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="/negocios/editar_etapa6.php?id=<?= $negocio_id ?>" class="btn btn-secondary me-md-2">← Voltar</a>
            <button type="submit" class="btn btn-primary">Salvar e avançar</button>
        </div>
    </form>
</div>

<script>
function addLink() {
    const container = document.getElementById('links-container');
    const inputs = container.querySelectorAll('input[name="resultados_link[]"]');
    if (inputs.length >= 4) {
        alert("Máximo de 4 links permitidos.");
        return;
    }
    const input = document.createElement('input');
    input.type = 'url';
    input.name = 'resultados_link[]';
    input.className = 'form-control mb-2';
    input.placeholder = 'Adicionar novo link';
    container.appendChild(input);
}

function addPdf() {
    const container = document.getElementById('pdfs-container');
    const inputs = container.querySelectorAll('input[name="resultados_pdf[]"]');
    if (inputs.length >= 4) {
        alert("Máximo de 4 PDFs permitidos.");
        return;
    }
    const input = document.createElement('input');
    input.type = 'file';
    input.name = 'resultados_pdf[]';
    input.className = 'form-control mb-2';
    input.accept = 'application/pdf';
    container.appendChild(input);
}
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Mapeia cada grupo pelo name do checkbox
    const outrosMap = {
        "beneficiarios[]": document.getElementById("beneficiario_outro"),
        "metricas[]": document.getElementById("metrica_outro"),
        "formas_medicao[]": document.getElementById("forma_outro")
    };

    // Seleciona todos os checkboxes "Outro"
    const outros = document.querySelectorAll("input[value='Outro']");

    outros.forEach(function(outroCheckbox) {
        const outroInput = outrosMap[outroCheckbox.name];
        if (!outroInput) return; // segurança

        function toggleOutro() {
            if (outroCheckbox.checked) {
                outroInput.classList.remove("d-none");
                outroInput.setAttribute("required", "required");
            } else {
                outroInput.classList.add("d-none");
                outroInput.removeAttribute("required");
                outroInput.value = "";
            }
        }

        // Executa ao carregar (útil no editar)
        toggleOutro();

        // Executa quando o usuário marca/desmarca
        outroCheckbox.addEventListener("change", toggleOutro);
    });
});
</script>

<!-- VALIDAR TEXTOS -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Seleciona todos os inputs de texto e textareas
    const camposTexto = document.querySelectorAll("input[type='text'], textarea");

    camposTexto.forEach(campo => {
        campo.addEventListener("input", function() {
            // Regex: conta letras (inclui acentos)
            const regex = /[a-zA-ZÀ-ÿ]/g;
            const letras = (this.value.match(regex) || []).length;

            if (letras < 5) {
                this.setCustomValidity("Digite um texto válido (mínimo 5 letras reais).");
            } else {
                this.setCustomValidity("");
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>