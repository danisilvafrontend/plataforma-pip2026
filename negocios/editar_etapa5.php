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

$stmt = $pdo->prepare("SELECT * FROM negocios WHERE id = ? AND empreendedor_id = ?");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$negocio) {
    die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);
}

$stmt = $pdo->prepare("SELECT * FROM negocio_financeiro WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$financeiro = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$fontesSelecionadas = json_decode($financeiro['fontes_receita'] ?? '[]', true);
if (!is_array($fontesSelecionadas)) {
    $fontesSelecionadas = [];
}

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container py-4 emp-inner" style="max-width: 1100px;">

    <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="emp-page-title mb-1">
                Editar: <?= htmlspecialchars($negocio['nome_fantasia'] ?? '') ?>
            </h1>
            <p class="emp-page-subtitle mb-0">Etapa 5 — Dados Financeiros e Modelo de Receita</p>
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

    <?php if (!empty($_SESSION['errors_etapa5'])): ?>
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 ps-3">
                <?php foreach ($_SESSION['errors_etapa5'] as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors_etapa5']); ?>
    <?php endif; ?>

    <form action="/negocios/processar_etapa5.php" method="post">
        <input type="hidden" name="negocio_id" value="<?= (int)$negocio_id ?>">
        <input type="hidden" name="modo" value="editar">

        <div class="row g-4">
            <div class="col-12 col-lg-8">

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-graph-up-arrow"></i> Faturamento e estágio do negócio
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">
                                <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                Estágio atual de faturamento
                            </label>
                            <select name="estagio_faturamento" class="form-select" required>
                                <?php
                                $opcoesEstagio = [
                                    "Ainda não temos produto/serviço validado – sem faturamento previsto.",
                                    "Produto/serviço desenvolvido, mas ainda sem faturamento e sem previsão para os próximos 6 meses.",
                                    "Produto/serviço desenvolvido, com previsão de início de faturamento nos próximos 6 meses.",
                                    "Já há faturamento, mas ainda operando abaixo do ponto de equilíbrio (break-even).",
                                    "Ponto de equilíbrio atingido – início da geração de lucro.",
                                    "Operando com lucro e crescimento consistente de receita."
                                ];
                                foreach ($opcoesEstagio as $op) {
                                    $sel = ($financeiro['estagio_faturamento'] ?? '') === $op ? 'selected' : '';
                                    echo "<option $sel>" . htmlspecialchars($op) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">
                                <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                Faixa de faturamento bruto nos últimos 12 meses
                            </label>
                            <select name="faixa_faturamento" class="form-select" required>
                                <?php
                                $opcoesFaixaFat = [
                                    "Não houve faturamento ainda",
                                    "Até R$ 100 mil",
                                    "R$ 100 mil – R$ 500 mil",
                                    "R$ 500 mil – R$ 1 milhão",
                                    "R$ 1 milhão – R$ 5 milhões",
                                    "R$ 5 milhões – R$ 20 milhões",
                                    "Acima de R$ 20 milhões"
                                ];
                                foreach ($opcoesFaixaFat as $op) {
                                    $sel = ($financeiro['faixa_faturamento'] ?? '') === $op ? 'selected' : '';
                                    echo "<option $sel>" . htmlspecialchars($op) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-cash-coin"></i> Fontes de receita e monetização
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                            Fontes de receita ativas (até 3)
                        </label>

                        <?php
                        $fontes = [
                            "Venda direta única (produto ou serviço)",
                            "Venda direta recorrente (assinaturas, mensalidades)",
                            "Licenciamento de tecnologia",
                            "Plataforma como serviço (PaaS)",
                            "Infraestrutura como serviço (IaaS)",
                            "Comissões / Sucess fee",
                            "Publicidade",
                            "Marketplace",
                            "Consultoria / mentoria / treinamento",
                            "Venda de dados / analytics",
                            "Micro pagamentos",
                            "Modelo ainda não definido",
                            "Outro (especificar)"
                        ];
                        foreach ($fontes as $f):
                            $idFonte = md5($f);
                        ?>
                            <div class="form-check">
                                <input class="form-check-input fonte-check"
                                       type="checkbox"
                                       name="fontes_receita[]"
                                       value="<?= htmlspecialchars($f) ?>"
                                       id="<?= $idFonte ?>"
                                       <?= in_array($f, $fontesSelecionadas, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="<?= $idFonte ?>">
                                    <?= htmlspecialchars($f) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>

                        <input type="text"
                               name="fonte_outro"
                               id="fonte_outro"
                               class="form-control mt-2 <?= in_array('Outro (especificar)', $fontesSelecionadas, true) ? '' : 'd-none' ?>"
                               value="<?= htmlspecialchars($financeiro['fonte_outro'] ?? '') ?>"
                               placeholder="Se marcou 'Outro', especifique aqui"
                               maxlength="120"
                               <?= in_array('Outro (especificar)', $fontesSelecionadas, true) ? 'required' : '' ?>>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">
                            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                            Modelo de monetização principal
                        </label>
                        <textarea name="modelo_monetizacao" class="form-control" maxlength="250" rows="4"><?= htmlspecialchars($financeiro['modelo_monetizacao'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-percent"></i> Margem e dependência de receita
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                            Margem bruta estimada
                        </label>
                        <select name="margem_bruta" class="form-select" required>
                            <?php
                            $opcoesMargem = [
                                "Ainda não mensurada",
                                "Menor que 20%",
                                "Entre 20% e 40%",
                                "Entre 40% e 60%",
                                "Acima de 60%"
                            ];
                            foreach ($opcoesMargem as $op) {
                                $sel = ($financeiro['margem_bruta'] ?? '') === $op ? 'selected' : '';
                                echo "<option $sel>" . htmlspecialchars($op) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">
                                <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                Mais de 50% da receita vem de produtos/serviços próprios?
                            </label>
                            <select name="dependencia_proprios" id="dependencia_proprios" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php
                                $opcoesDep = ["Sim", "Não"];
                                foreach ($opcoesDep as $op) {
                                    $sel = ($financeiro['dependencia_proprios'] ?? '') === $op ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($op) . "' $sel>" . htmlspecialchars($op) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 <?= ($financeiro['dependencia_proprios'] ?? '') === 'Não' ? '' : 'd-none' ?>" id="div_previsao_proprios">
                            <label class="form-label">
                                <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                Se não, há previsão de ultrapassar 50% nos próximos 2 anos?
                            </label>
                            <select name="previsao_proprios" id="previsao_proprios" class="form-select" <?= ($financeiro['dependencia_proprios'] ?? '') === 'Não' ? 'required' : '' ?>>
                                <option value="">Selecione...</option>
                                <?php
                                $opcoesPrev = ["Sim", "Não"];
                                foreach ($opcoesPrev as $op) {
                                    $sel = ($financeiro['previsao_proprios'] ?? '') === $op ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($op) . "' $sel>" . htmlspecialchars($op) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-bar-chart-line"></i> Crescimento e captação
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">
                                <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                Previsão de crescimento de receita (próximos 12 meses)
                            </label>
                            <select name="previsao_crescimento" class="form-select" required>
                                <?php
                                $opcoesCresc = [
                                    "Estável ou retração esperada",
                                    "Crescimento de até 50%",
                                    "Crescimento entre 50% e 100%",
                                    "Crescimento acima de 100%"
                                ];
                                foreach ($opcoesCresc as $op) {
                                    $sel = ($financeiro['previsao_crescimento'] ?? '') === $op ? 'selected' : '';
                                    echo "<option $sel>" . htmlspecialchars($op) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">
                                <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                Investimento externo já captado
                            </label>
                            <select name="investimento_externo" class="form-select" required>
                                <?php
                                $opcoesInvest = [
                                    "Não",
                                    "Sim, investimento anjo",
                                    "Sim, pré-seed / seed",
                                    "Sim, Série A ou superior",
                                    "Apenas recursos próprios (bootstrapping)",
                                    "Doações",
                                    "Premiações"
                                ];
                                foreach ($opcoesInvest as $op) {
                                    $sel = ($financeiro['investimento_externo'] ?? '') === $op ? 'selected' : '';
                                    echo "<option $sel>" . htmlspecialchars($op) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-bullseye"></i> Questões estratégicas
                    </div>

                    <div class="row g-4">
                        <div class="col-12 mb-2">
                            <label class="form-label">
                                <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                Qual é sua prioridade estratégica nos próximos 6 meses?
                            </label>
                            <?php
                            $opcoesPrioridade = [
                                "Captar investimento",
                                "Fechar parcerias comerciais",
                                "Buscar patrocínio institucional",
                                "Estruturar governança e processos",
                                "Ampliar visibilidade",
                                "Testar novo produto",
                                "Ainda definindo"
                            ];
                            foreach ($opcoesPrioridade as $opcao): ?>
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="prioridade_estrategica"
                                           value="<?= htmlspecialchars($opcao) ?>"
                                           <?= ($financeiro['prioridade_estrategica'] ?? '') === $opcao ? 'checked' : '' ?>>
                                    <label class="form-check-label"><?= htmlspecialchars($opcao) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="col-12 mb-2">
                            <label class="form-label">
                                <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                Você está pronto para receber investimento ou parceria agora?
                            </label>
                            <?php
                            $opcoesPronto = [
                                "Sim – temos documentação e estrutura organizadas",
                                "Parcialmente – precisamos de ajustes",
                                "Ainda não estamos preparados"
                            ];
                            foreach ($opcoesPronto as $opcao): ?>
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="pronto_investimento"
                                           value="<?= htmlspecialchars($opcao) ?>"
                                           <?= ($financeiro['pronto_investimento'] ?? '') === $opcao ? 'checked' : '' ?>>
                                    <label class="form-check-label"><?= htmlspecialchars($opcao) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="col-12 mb-2">
                            <label class="form-label">
                                <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                Qual faixa de investimento ou apoio você busca?
                            </label>
                            <?php
                            $opcoesFaixaInvestimento = [
                                "Até R$ 100 mil",
                                "R$ 100 mil – R$ 500 mil",
                                "R$ 500 mil – R$ 2 milhões",
                                "Acima de R$ 2 milhões",
                                "Não buscamos capital financeiro"
                            ];
                            foreach ($opcoesFaixaInvestimento as $opcao): ?>
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="radio"
                                           name="faixa_investimento"
                                           value="<?= htmlspecialchars($opcao) ?>"
                                           <?= ($financeiro['faixa_investimento'] ?? '') === $opcao ? 'checked' : '' ?>>
                                    <label class="form-check-label"><?= htmlspecialchars($opcao) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-12 col-lg-4">
                <div class="etapa6-sticky-actions">

                    <div class="emp-card mb-4">
                        <div class="emp-card-header">
                            <i class="bi bi-info-circle"></i> Orientações
                        </div>

                        <p class="small text-muted mb-2">
                            Preencha os dados financeiros com a maior precisão possível para melhorar a análise do negócio.
                        </p>
                        <p class="small text-muted mb-0">
                            Caso alguma informação ainda não esteja consolidada, selecione a opção mais próxima da realidade atual.
                        </p>
                    </div>

                    <div class="emp-card">
                        <div class="emp-card-header">
                            <i class="bi bi-floppy"></i> Ações
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn-emp-primary w-100 justify-content-center">
                                <i class="bi bi-floppy me-1"></i> Salvar alterações
                            </button>

                            <a href="/negocios/editar_etapa5.php?id=<?= (int)$negocio_id ?>" class="btn-emp-outline w-100 justify-content-center">
                                <i class="bi bi-arrow-left me-1"></i> Etapa anterior
                            </a>

                            <?php if (!empty($negocio['inscricao_completa'])): ?>
                                <a href="/negocios/confirmacao.php?id=<?= (int)$negocio_id ?>" class="btn-emp-outline w-100 justify-content-center">
                                    <i class="bi bi-card-checklist me-1"></i> Voltar à revisão
                                </a>
                            <?php endif; ?>

                            <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline w-100 justify-content-center">
                                <i class="bi bi-grid me-1"></i> Meus negócios
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
    const outroCheckbox = document.querySelector("input[value='Outro (especificar)']");
    const outroInput = document.getElementById("fonte_outro");

    if (outroCheckbox && outroInput) {
        outroCheckbox.addEventListener("change", function() {
            if (this.checked) {
                outroInput.classList.remove("d-none");
                outroInput.setAttribute("required", "required");
            } else {
                outroInput.classList.add("d-none");
                outroInput.removeAttribute("required");
                outroInput.value = "";
            }
        });
    }

    const dependencia = document.getElementById("dependencia_proprios");
    const divPrevisao = document.getElementById("div_previsao_proprios");
    const previsaoSelect = document.getElementById("previsao_proprios");

    function togglePrevisao() {
        if (!dependencia || !divPrevisao || !previsaoSelect) return;

        if (dependencia.value === "Não") {
            divPrevisao.classList.remove("d-none");
            previsaoSelect.setAttribute("required", "required");
        } else {
            divPrevisao.classList.add("d-none");
            previsaoSelect.removeAttribute("required");
            previsaoSelect.value = "";
        }
    }

    if (dependencia) {
        dependencia.addEventListener("change", togglePrevisao);
        togglePrevisao();
    }

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