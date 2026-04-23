<?php
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

// Aceita ID via GET (de meus-negocios) OU sessão
$negocio_id = (int)($_GET['id'] ?? $_SESSION['negocio_id'] ?? 0);
if ($negocio_id === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}

// Define na sessão para usar no formulário
$_SESSION['negocio_id'] = $negocio_id;

// Busca dados do negócio e empreendedor
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

// Busca fundadores já cadastrados
$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Caso não exista ainda carregamento prévio do financeiro
$financeiro = $financeiro ?? [];

// Busca dados financeiros já salvos (para repopular após erro ou edição)
$stmtFin = $pdo->prepare("SELECT * FROM negocio_financeiro WHERE negocio_id = ?");
$stmtFin->execute([$negocio_id]);
$financeiro = $stmtFin->fetch(PDO::FETCH_ASSOC) ?: [];

// Decodifica fontes_receita de JSON para array
if (!empty($financeiro['fontes_receita'])) {
    $financeiro['fontes_receita'] = json_decode($financeiro['fontes_receita'], true) ?: [];
}

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container py-4 etapa6-page" style="max-width: 980px;">
    <div class="mb-4">
        <h1 class="emp-page-title mb-1">Etapa 5 — Dados Financeiros e Modelo de Receita</h1>
        <p class="emp-page-subtitle mb-0">Preencha as informações financeiras atuais e as perspectivas de crescimento do negócio.</p>
    </div>

    <?php
        $etapaAtual = 5;
        include __DIR__ . '/../app/views/partials/progress.php';
        include __DIR__ . '/../app/views/partials/intro_text_financeiro.php';
    ?>

    <?php if (!empty($_SESSION['errors_etapa5'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Corrija os erros:</h6>
            <ul class="mb-0 ps-3 small">
            <?php foreach ($_SESSION['errors_etapa5'] as $erro): ?>
                <li><?= htmlspecialchars($erro) ?></li>
            <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['errors_etapa5']); ?>
    <?php endif; ?>

    <form action="/negocios/processar_etapa5.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="negocio_id" value="<?= (int)$negocio_id ?>">
        <input type="hidden" name="modo" value="cadastro">

        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-bar-chart-line"></i> Estágio financeiro atual
            </div>

            <p class="etapa6-section-text">
                Informe o estágio de faturamento e a faixa de receita do negócio nos últimos 12 meses.
            </p>

            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <label for="estagio_faturamento" class="form-label etapa6-label">
                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                        Qual das opções melhor representa o momento atual do seu negócio? *
                    </label>
                    <select name="estagio_faturamento" id="estagio_faturamento" class="form-select etapa6-select" required>
                        <option value="">Selecione...</option>
                        <option <?= ($financeiro['estagio_faturamento'] ?? '') === 'Ainda não temos produto/serviço validado – sem faturamento previsto.' ? 'selected' : '' ?>>Ainda não temos produto/serviço validado – sem faturamento previsto.</option>
                        <option <?= ($financeiro['estagio_faturamento'] ?? '') === 'Produto/serviço desenvolvido, mas ainda sem faturamento e sem previsão para os próximos 6 meses.' ? 'selected' : '' ?>>Produto/serviço desenvolvido, mas ainda sem faturamento e sem previsão para os próximos 6 meses.</option>
                        <option <?= ($financeiro['estagio_faturamento'] ?? '') === 'Produto/serviço desenvolvido, com previsão de início de faturamento nos próximos 6 meses.' ? 'selected' : '' ?>>Produto/serviço desenvolvido, com previsão de início de faturamento nos próximos 6 meses.</option>
                        <option <?= ($financeiro['estagio_faturamento'] ?? '') === 'Já há faturamento, mas ainda operando abaixo do ponto de equilíbrio (break-even).' ? 'selected' : '' ?>>Já há faturamento, mas ainda operando abaixo do ponto de equilíbrio (break-even).</option>
                        <option <?= ($financeiro['estagio_faturamento'] ?? '') === 'Ponto de equilíbrio atingido – início da geração de lucro.' ? 'selected' : '' ?>>Ponto de equilíbrio atingido – início da geração de lucro.</option>
                        <option <?= ($financeiro['estagio_faturamento'] ?? '') === 'Operando com lucro e crescimento consistente de receita.' ? 'selected' : '' ?>>Operando com lucro e crescimento consistente de receita.</option>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label for="faixa_faturamento" class="form-label etapa6-label">
                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                        Faixa de faturamento bruto nos últimos 12 meses *
                    </label>
                    <select name="faixa_faturamento" id="faixa_faturamento" class="form-select etapa6-select" required>
                        <option value="">Selecione...</option>
                        <option <?= ($financeiro['faixa_faturamento'] ?? '') === 'Não houve faturamento ainda' ? 'selected' : '' ?>>Não houve faturamento ainda</option>
                        <option <?= ($financeiro['faixa_faturamento'] ?? '') === 'Até R$ 100 mil' ? 'selected' : '' ?>>Até R$ 100 mil</option>
                        <option <?= ($financeiro['faixa_faturamento'] ?? '') === 'R$ 100 mil – R$ 500 mil' ? 'selected' : '' ?>>R$ 100 mil – R$ 500 mil</option>
                        <option <?= ($financeiro['faixa_faturamento'] ?? '') === 'R$ 500 mil – R$ 1 milhão' ? 'selected' : '' ?>>R$ 500 mil – R$ 1 milhão</option>
                        <option <?= ($financeiro['faixa_faturamento'] ?? '') === 'R$ 1 milhão – R$ 5 milhões' ? 'selected' : '' ?>>R$ 1 milhão – R$ 5 milhões</option>
                        <option <?= ($financeiro['faixa_faturamento'] ?? '') === 'R$ 5 milhões – R$ 20 milhões' ? 'selected' : '' ?>>R$ 5 milhões – R$ 20 milhões</option>
                        <option <?= ($financeiro['faixa_faturamento'] ?? '') === 'Acima de R$ 20 milhões' ? 'selected' : '' ?>>Acima de R$ 20 milhões</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-cash-coin"></i> Receita e monetização
            </div>

            <p class="etapa6-section-text">
                Indique as fontes de receita ativas e descreva brevemente o modelo principal de monetização.
            </p>

            <div class="mb-4">
                <label class="form-label etapa6-label">
                    <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                    Fontes de receita ativas *
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
                $fontesSelecionadas = $financeiro['fontes_receita'] ?? [];
                if (!is_array($fontesSelecionadas)) {
                    $fontesSelecionadas = [];
                }
                ?>

                <div class="etapa6-check-grid">
                    <?php foreach ($fontes as $f): ?>
                        <label class="etapa6-check-card">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="fontes_receita[]"
                                value="<?= htmlspecialchars($f) ?>"
                                id="<?= md5($f) ?>"
                                <?= in_array($f, $fontesSelecionadas, true) ? 'checked' : '' ?>
                            >
                            <span><?= htmlspecialchars($f) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <input
                    type="text"
                    name="fonte_outro"
                    id="fonte_outro"
                    class="form-control etapa6-input mt-3 <?= !empty($financeiro['fonte_outro']) ? '' : 'd-none' ?>"
                    placeholder="Se marcou 'Outro', especifique aqui"
                    maxlength="120"
                    value="<?= htmlspecialchars($financeiro['fonte_outro'] ?? '') ?>"
                >
            </div>

            <div class="mb-0">
                <label for="modelo_monetizacao" class="form-label etapa6-label">
                    <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                    Modelo de monetização principal
                </label>
                <small class="text-muted d-block mb-2">
                    Descreva brevemente como o seu negócio gera receita atualmente e como pretende monetizar no médio prazo (se for diferente). ⚠️ Até 250 caracteres.
                </small>
                <textarea
                    name="modelo_monetizacao"
                    id="modelo_monetizacao"
                    class="form-control etapa6-textarea"
                    maxlength="250"
                ><?= htmlspecialchars($financeiro['modelo_monetizacao'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-box-seam"></i> Produtos e margem
            </div>

            <p class="etapa6-section-text">
                Responda sobre margem e dependência de produtos ou serviços próprios na composição da receita.
            </p>

            <div class="mb-4">
                <label for="margem_bruta" class="form-label etapa6-label">
                    <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                    Atualmente, mais de 50% da sua receita vem de produtos ou serviços próprios? *
                </label>
                <select name="margem_bruta" id="margem_bruta" class="form-select etapa6-select" required>
                    <option value="">Selecione...</option>
                    <option <?= ($financeiro['margem_bruta'] ?? '') === 'Ainda não mensurada' ? 'selected' : '' ?>>Ainda não mensurada</option>
                    <option <?= ($financeiro['margem_bruta'] ?? '') === 'Menor que 20%' ? 'selected' : '' ?>>Menor que 20%</option>
                    <option <?= ($financeiro['margem_bruta'] ?? '') === 'Entre 20% e 40%' ? 'selected' : '' ?>>Entre 20% e 40%</option>
                    <option <?= ($financeiro['margem_bruta'] ?? '') === 'Entre 40% e 60%' ? 'selected' : '' ?>>Entre 40% e 60%</option>
                    <option <?= ($financeiro['margem_bruta'] ?? '') === 'Acima de 60%' ? 'selected' : '' ?>>Acima de 60%</option>
                </select>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <label for="dependencia_proprios" class="form-label etapa6-label">
                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                        Mais de 50% da receita vem de produtos/serviços próprios? *
                    </label>
                    <select name="dependencia_proprios" id="dependencia_proprios" class="form-select etapa6-select" required>
                        <option value="">Selecione...</option>
                        <option value="Sim" <?= ($financeiro['dependencia_proprios'] ?? '') === 'Sim' ? 'selected' : '' ?>>Sim</option>
                        <option value="Não" <?= ($financeiro['dependencia_proprios'] ?? '') === 'Não' ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 <?= ($financeiro['dependencia_proprios'] ?? '') === 'Não' ? '' : 'd-none' ?>" id="div_previsao_proprios">
                    <label for="previsao_proprios" class="form-label etapa6-label">
                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                        Se não, há previsão de ultrapassar 50% nos próximos 2 anos? *
                    </label>
                    <select name="previsao_proprios" id="previsao_proprios" class="form-select etapa6-select">
                        <option value="">Selecione...</option>
                        <option value="Sim" <?= ($financeiro['previsao_proprios'] ?? '') === 'Sim' ? 'selected' : '' ?>>Sim</option>
                        <option value="Não" <?= ($financeiro['previsao_proprios'] ?? '') === 'Não' ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-graph-up-arrow"></i> Crescimento e capital
            </div>

            <p class="etapa6-section-text">
                Informe a expectativa de crescimento e o estágio atual de captação de investimento externo.
            </p>

            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <label for="previsao_crescimento" class="form-label etapa6-label">
                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                        Previsão de crescimento de receita (próximos 12 meses) *
                    </label>
                    <select name="previsao_crescimento" id="previsao_crescimento" class="form-select etapa6-select" required>
                        <option value="">Selecione...</option>
                        <option <?= ($financeiro['previsao_crescimento'] ?? '') === 'Estável ou retração esperada' ? 'selected' : '' ?>>Estável ou retração esperada</option>
                        <option <?= ($financeiro['previsao_crescimento'] ?? '') === 'Crescimento de até 50%' ? 'selected' : '' ?>>Crescimento de até 50%</option>
                        <option <?= ($financeiro['previsao_crescimento'] ?? '') === 'Crescimento entre 50% e 100%' ? 'selected' : '' ?>>Crescimento entre 50% e 100%</option>
                        <option <?= ($financeiro['previsao_crescimento'] ?? '') === 'Crescimento acima de 100%' ? 'selected' : '' ?>>Crescimento acima de 100%</option>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label for="investimento_externo" class="form-label etapa6-label">
                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                        Investimento externo já captado *
                    </label>
                    <select name="investimento_externo" id="investimento_externo" class="form-select etapa6-select" required>
                        <option value="">Selecione...</option>
                        <option <?= ($financeiro['investimento_externo'] ?? '') === 'Não' ? 'selected' : '' ?>>Não</option>
                        <option <?= ($financeiro['investimento_externo'] ?? '') === 'Sim, investimento anjo' ? 'selected' : '' ?>>Sim, investimento anjo</option>
                        <option <?= ($financeiro['investimento_externo'] ?? '') === 'Sim, pré-seed / seed' ? 'selected' : '' ?>>Sim, pré-seed / seed</option>
                        <option <?= ($financeiro['investimento_externo'] ?? '') === 'Sim, Série A ou superior' ? 'selected' : '' ?>>Sim, Série A ou superior</option>
                        <option <?= ($financeiro['investimento_externo'] ?? '') === 'Apenas recursos próprios (bootstrapping)' ? 'selected' : '' ?>>Apenas recursos próprios (bootstrapping)</option>
                        <option <?= ($financeiro['investimento_externo'] ?? '') === 'Doações' ? 'selected' : '' ?>>Doações</option>
                        <option <?= ($financeiro['investimento_externo'] ?? '') === 'Premiações' ? 'selected' : '' ?>>Premiações</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-bullseye"></i> Prioridades estratégicas
            </div>

            <p class="etapa6-section-text">
                Indique o foco estratégico mais imediato, o preparo para investimento/parceria e a faixa de apoio buscada.
            </p>

            <div class="row g-4">
                <div class="col-12 col-lg-4">
                    <label class="form-label etapa6-label">
                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                        Qual é sua prioridade estratégica nos próximos 6 meses? *
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
                    ?>
                    <div class="etapa6-radio-group">
                        <?php foreach ($opcoesPrioridade as $opcao): ?>
                            <label class="etapa6-radio-card">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="prioridade_estrategica"
                                    value="<?= htmlspecialchars($opcao) ?>"
                                    <?= ($financeiro['prioridade_estrategica'] ?? '') === $opcao ? 'checked' : '' ?>
                                >
                                <span><?= htmlspecialchars($opcao) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label etapa6-label">
                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                        Você está pronto para receber investimento ou parceria agora? *
                    </label>

                    <?php
                    $opcoesPronto = [
                        "Sim – temos documentação e estrutura organizadas",
                        "Parcialmente – precisamos de ajustes",
                        "Ainda não estamos preparados"
                    ];
                    ?>
                    <div class="etapa6-radio-group">
                        <?php foreach ($opcoesPronto as $opcao): ?>
                            <label class="etapa6-radio-card">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="pronto_investimento"
                                    value="<?= htmlspecialchars($opcao) ?>"
                                    <?= ($financeiro['pronto_investimento'] ?? '') === $opcao ? 'checked' : '' ?>
                                >
                                <span><?= htmlspecialchars($opcao) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <label class="form-label etapa6-label">
                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                        Qual faixa de investimento ou apoio você busca? *
                    </label>

                    <?php
                    $opcoesFaixa = [
                        "Até R$ 100 mil",
                        "R$ 100 mil – R$ 500 mil",
                        "R$ 500 mil – R$ 2 milhões",
                        "Acima de R$ 2 milhões",
                        "Não buscamos capital financeiro"
                    ];
                    ?>
                    <div class="etapa6-radio-group">
                        <?php foreach ($opcoesFaixa as $opcao): ?>
                            <label class="etapa6-radio-card">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="faixa_investimento"
                                    value="<?= htmlspecialchars($opcao) ?>"
                                    <?= ($financeiro['faixa_investimento'] ?? '') === $opcao ? 'checked' : '' ?>
                                >
                                <span><?= htmlspecialchars($opcao) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-4 etapa6-actions">
            <a href="/negocios/editar_etapa4.php?id=<?= (int)$negocio_id ?>" class="btn-emp-outline">
                <i class="bi bi-arrow-left me-1"></i> Voltar
            </a>

            <button type="submit" class="btn-emp-primary">
                <i class="bi bi-floppy me-1"></i> Salvar e avançar
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const outroCheckbox = document.querySelector("input[value='Outro (especificar)']");
    const outroInput = document.getElementById("fonte_outro");

    if (outroCheckbox && outroInput) {
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

        outroCheckbox.addEventListener("change", toggleOutro);
        toggleOutro();
    }
});

document.addEventListener("DOMContentLoaded", function() {
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
});

document.addEventListener("DOMContentLoaded", function() {
    const camposTexto = document.querySelectorAll("input[type='text'], textarea");

    camposTexto.forEach(campo => {
        campo.addEventListener("input", function() {
            const regex = /[a-zA-ZÀ-ÿ]/g;
            const letras = (this.value.match(regex) || []).length;

            if (this.value.trim() !== "" && letras < 5) {
                this.setCustomValidity("Digite um texto válido (mínimo 5 letras reais).");
            } else {
                this.setCustomValidity("");
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>