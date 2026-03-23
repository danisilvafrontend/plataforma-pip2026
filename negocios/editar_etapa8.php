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

// Busca dados da visão já cadastrados
$stmt = $pdo->prepare("SELECT * FROM negocio_visao WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$visao = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Decodifica arrays
$apoios = json_decode($visao['apoios'] ?? '[]', true);
$areas  = json_decode($visao['areas'] ?? '[]', true);
$temas  = json_decode($visao['temas'] ?? '[]', true);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <!-- Título à esquerda -->
        <h1 class="mb-4">Editar - Etapa 8: Visão de Futuro</h1>
        
        <!-- Botões à direita -->
        <div class="d-flex gap-2">
            <a href="/negocios/confirmacao.php?id=<?= htmlspecialchars($_GET['id'] ?? 0) ?>" class="btn btn-warning">
                <i class="bi bi-card-checklist me-1"></i> Voltar para revisão
            </a>
            <a href="/empreendedores/meus-negocios.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Voltar aos negócios
            </a>
        </div>
    </div>
    
    <?php
        include __DIR__ . '/../app/views/partials/intro_text_visao.php';
    ?>

    <form action="/negocios/processar_etapa8.php" method="post">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="editar">

        <!-- 1. Visão estratégica -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Qual é a visão estratégica do fundador(a) para os próximos 5 anos?</label>
            <select name="visao_estrategica" class="form-select" required>
                <option value="" <?= empty($visao['visao_estrategica']) ? 'selected' : '' ?>>Selecione uma opção</option>
                <?php
                $opcoesVisao = [
                    "Consolidar presença no mercado local com mais profundidade",
                    "Expandir regionalmente ou para novos estados no Brasil",
                    "Crescer em escala nacional como referência em impacto",
                    "Internacionalizar o modelo para ampliar alcance",
                    "Pivotar o modelo de negócio com base em aprendizados",
                    "Outro"
                ];
                foreach ($opcoesVisao as $op) {
                    $sel = ($visao['visao_estrategica'] ?? '') === $op ? 'selected' : '';
                    echo "<option value=\"$op\" $sel>$op</option>";
                }
                ?>
            </select>
            <input type="text" name="visao_outro" class="form-control mt-2 d-none"
                   value="<?= htmlspecialchars($visao['visao_outro'] ?? '') ?>"
                   placeholder="Se marcou 'Outro', especifique aqui" maxlength="120">
        </div>

        <!-- 2. Sustentabilidade -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Como você avalia a sustentabilidade financeira de longo prazo do seu negócio?</label>
            <select name="sustentabilidade" class="form-select" required>
                <option value="" <?= empty($visao['sustentabilidade']) ? 'selected' : '' ?>>Selecione uma opção</option>
                <?php
                $opcoesSust = [
                    "Alta sustentabilidade – Receita diversificada, margens saudáveis e plano de crescimento validado",
                    "Moderada sustentabilidade – Produto validado, mas com necessidade de novas fontes de receita/melhoria na margem",
                    "Sustentabilidade projetada – Modelo em desenvolvimento, com expectativa de break-even em até 2 anos",
                    "Dependente de capital externo – Sustentação atual via editais, doações ou patrocínios",
                    "Modelo ainda em validação – Sem plano financeiro claro ou histórico de receita"
                ];
                foreach ($opcoesSust as $op) {
                    $sel = ($visao['sustentabilidade'] ?? '') === $op ? 'selected' : '';
                    echo "<option value=\"$op\" $sel>$op</option>";
                }
                ?>
            </select>
        </div>

        <!-- 3. Escala -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Qual é a sua ambição de escala nos próximos anos?</label>
            <select name="escala" class="form-select" required>
                <option value="" <?= empty($visao['escala']) ? 'selected' : '' ?>>Selecione uma opção</option>
                <?php
                $opcoesEscala = [
                    "Escalar localmente (mais profundidade e alcance na mesma região)",
                    "Escalar nacionalmente (atuar em novas regiões/mercados do Brasil)",
                    "Escalar internacionalmente (expandir o modelo para fora do país)",
                    "Manter o modelo atual como negócio de nicho ou territorial",
                    "Ainda em definição"
                ];
                foreach ($opcoesEscala as $op) {
                    $sel = ($visao['escala'] ?? '') === $op ? 'selected' : '';
                    echo "<option value=\"$op\" $sel>$op</option>";
                }
                ?>
            </select>
        </div>

        <!-- 4. Apoios -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Qual o tipo de apoio financeiro ou estratégico que você busca atualmente?</label>
            <?php
            $apoiosLista = [
                "Investimento Anjo","Venture Capital (VC)","Parcerias corporativas ou estratégicas",
                "Editais públicos e subsídios","Financiamento bancário ou crédito com impacto",
                "Doações filantrópicas ou blended finance","Outro"
            ];
            foreach ($apoiosLista as $a): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="apoios[]" value="<?= $a ?>"
                        id="<?= md5($a) ?>" <?= in_array($a, $apoios) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="<?= md5($a) ?>"><?= $a ?></label>
                </div>
            <?php endforeach; ?>
            <input type="text" name="apoio_outro" class="form-control mt-2"
                   value="<?= htmlspecialchars($visao['apoio_outro'] ?? '') ?>"
                   placeholder="Se marcou 'Outro', especifique aqui" maxlength="120">
        </div>

        <!-- 5. Áreas -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Quais áreas do seu negócio você gostaria de fortalecer com apoio externo? (até 3)</label>
            <?php
            $areasLista = [
                "Capital de giro ou fluxo de caixa","Expansão comercial e abertura de mercado",
                "Desenvolvimento de tecnologia ou produto","Reforço da estrutura operacional (equipamentos, logística etc.)",
                "Formação de equipe e qualificação técnica","Comunicação estratégica e branding",
                "Medição e gestão do impacto socioambiental","Governança e profissionalização da gestão","Outro"
            ];
            foreach ($areasLista as $ar): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="areas[]" value="<?= $ar ?>"
                        id="<?= md5($ar) ?>" <?= in_array($ar, $areas) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="<?= md5($ar) ?>"><?= $ar ?></label>
                </div>
            <?php endforeach; ?>
            <input type="text" name="area_outro" class="form-control mt-2"
                   value="<?= htmlspecialchars($visao['area_outro'] ?? '') ?>"
                   placeholder="Se marcou 'Outro', especifique aqui" maxlength="120">
        </div>

        <!-- 6. Temas -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Em quais temas você gostaria de aprender ou trocar com outros empreendedores/mentores? (até 3)</label>
            <?php
            $temasLista = [
                "Finanças para impacto (valuation, métricas, captação)",
                "ESG e gestão do impacto",
                "Marketing digital e posicionamento de marca",
                "Gestão de pessoas e cultura organizacional",
                "Tecnologia e inovação aplicada ao impacto",
                "Expansão e modelos de escala",
                "Liderança e tomada de decisão",
                "Relacionamento com investidores e stakeholders",
                "Outro"
            ];
            foreach ($temasLista as $t): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="temas[]" value="<?= $t ?>"
                        id="<?= md5($t) ?>" <?= in_array($t, $temas) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="<?= md5($t) ?>"><?= $t ?></label>
                </div>
            <?php endforeach; ?>
            <input type="text" name="tema_outro" class="form-control mt-2"
                value="<?= htmlspecialchars($visao['tema_outro'] ?? '') ?>"
                placeholder="Se marcou 'Outro', especifique aqui" maxlength="120">
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
            <a href="/negocios/editar_etapa7.php?id=<?= $negocio_id ?>" class="btn btn-secondary me-md-2">← Voltar</a>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
    </form>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Mapeia cada grupo pelo name do checkbox/select
    const outrosMap = {
        "visao_estrategica": document.querySelector("input[name='visao_outro']"),
        "apoios[]": document.querySelector("input[name='apoio_outro']"),
        "areas[]": document.querySelector("input[name='area_outro']"),
        "temas[]": document.querySelector("input[name='tema_outro']")
    };

    // Seleciona todos os checkboxes e selects que têm opção "Outro"
    const outros = document.querySelectorAll("input[value='Outro'], select[name='visao_estrategica']");

    outros.forEach(function(outroCampo) {
        let outroInput = null;

        if (outroCampo.name === "visao_estrategica") {
            outroInput = outrosMap["visao_estrategica"];
            outroCampo.addEventListener("change", function() {
                if (this.value === "Outro") {
                    outroInput.classList.remove("d-none");
                    outroInput.setAttribute("required", "required");
                } else {
                    outroInput.classList.add("d-none");
                    outroInput.removeAttribute("required");
                    outroInput.value = "";
                }
            });
            // Executa ao carregar
            if (outroCampo.value === "Outro") {
                outroInput.classList.remove("d-none");
                outroInput.setAttribute("required", "required");
            }
        } else {
            outroInput = outrosMap[outroCampo.name];
            if (!outroInput) return;

            function toggleOutro() {
                if (outroCampo.checked) {
                    outroInput.classList.remove("d-none");
                    outroInput.setAttribute("required", "required");
                } else {
                    outroInput.classList.add("d-none");
                    outroInput.removeAttribute("required");
                    outroInput.value = "";
                }
            }

            // Executa ao carregar
            toggleOutro();

            // Executa quando o usuário marca/desmarca
            outroCampo.addEventListener("change", toggleOutro);
        }
    });

    // Validação de texto (mínimo 5 letras reais)
    const camposTexto = document.querySelectorAll("input[type='text'], textarea");
    camposTexto.forEach(campo => {
        campo.addEventListener("input", function() {
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