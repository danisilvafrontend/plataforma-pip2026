<?php
declare(strict_types=1);
session_start();


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

<div class="container py-4" style="max-width: 1100px;">

    <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="emp-page-title mb-1">
                Editar: <?= htmlspecialchars($negocio['nome_fantasia'] ?? '') ?>
            </h1>
            <p class="emp-page-subtitle mb-0">Etapa 7 — Visão de Futuro</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <?php if (!empty($negocio['inscricao_completa'])): ?>
                <a href="/negocios/confirmacao.php?id=<?= (int)$negocio_id ?>" class="btn-emp-outline">
                    <i class="bi bi-card-checklist me-1"></i> Voltar à Revisão
                </a>
            <?php endif; ?>

            <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline">
                <i class="bi bi-arrow-left me-1"></i> Meus Negócios
            </a>
        </div>
    </div>


    <form action="/negocios/processar_etapa7.php" method="post">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="editar">

        <div class="row g-4">
            <div class="col-12 col-lg-8">

                <div class="form-section mb-4">
                    <div class="form-section-title">
                        <i class="bi bi-binoculars"></i> Visão estratégica e sustentabilidade
                    </div>

                    <div class="mb-4">
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

                    <div class="mb-0">
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
                </div>

                <div class="form-section mb-4">
                    <div class="form-section-title">
                        <i class="bi bi-arrows-angle-expand"></i> Escala e apoios buscados
                    </div>

                    <div class="mb-4">
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

                    <div class="mb-0">
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
                </div>

                <div class="form-section mb-4">
                    <div class="form-section-title">
                        <i class="bi bi-building-gear"></i> Áreas para fortalecimento
                    </div>

                    <div class="mb-0">
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
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-mortarboard"></i> Temas de aprendizagem e troca
                    </div>

                    <div class="mb-0">
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
                </div>

            </div>

            <div class="col-12 col-lg-4">
                <div class="etapa8-sticky-side">

                    <div class="emp-card mb-4">
                        <div class="emp-card-header">
                            <i class="bi bi-info-circle"></i> Orientações
                        </div>

                        <p class="small text-muted mb-2">
                            Revise a visão de futuro com foco em clareza estratégica, sustentabilidade financeira e prioridades reais de crescimento.
                        </p>

                        <p class="small text-muted mb-0">
                            Use esta etapa para mostrar onde o negócio quer chegar e quais apoios podem acelerar esse caminho.
                        </p>
                    </div>

                    <div class="emp-card">
                        <div class="emp-card-header">
                            <i class="bi bi-floppy"></i> Ações
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn-emp-primary w-100 justify-content-center">
                                <i class="bi bi-floppy me-1"></i> Salvar Alterações
                            </button>

                            <?php if (!empty($negocio['inscricao_completa'])): ?>
                                <a href="/negocios/confirmacao.php?id=<?= (int)$negocio_id ?>" class="btn-emp-outline w-100 justify-content-center">
                                    <i class="bi bi-card-checklist me-1"></i> Voltar à Revisão
                                </a>
                            <?php endif; ?>

                            <a href="/negocios/editar_etapa7.php?id=<?= $negocio_id ?>" class="btn-emp-outline w-100 justify-content-center">
                                <i class="bi bi-arrow-left me-1"></i> Etapa Anterior
                            </a>

                            <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline w-100 justify-content-center">
                                <i class="bi bi-grid me-1"></i> Meus Negócios
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const outrosMap = {
        "visao_estrategica": document.querySelector("input[name='visao_outro']"),
        "apoios[]": document.querySelector("input[name='apoio_outro']"),
        "areas[]": document.querySelector("input[name='area_outro']"),
        "temas[]": document.querySelector("input[name='tema_outro']")
    };

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

            toggleOutro();
            outroCampo.addEventListener("change", toggleOutro);
        }
    });

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