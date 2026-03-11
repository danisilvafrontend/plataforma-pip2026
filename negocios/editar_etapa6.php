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

// Aceita ID via GET ou sessão
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

// Busca dados financeiros já cadastrados
$stmt = $pdo->prepare("SELECT * FROM negocio_financeiro WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$financeiro = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Decodifica fontes de receita
$fontesSelecionadas = json_decode($financeiro['fontes_receita'] ?? '[]', true);
if (!is_array($fontesSelecionadas)) {
    $fontesSelecionadas = [];
}

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-4">Etapa 6 - Dados Financeiros e Modelo de Receita</h1>
        <a href="/empreendedores/meus-negocios.php" class="btn btn-secondary">← Voltar aos negócios</a>
    </div>
    <?php
        include __DIR__ . '/../app/views/partials/intro_text_financeiro.php';
    ?>
    <?php if (!empty($_SESSION['errors_etapa6'])): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
        <?php foreach ($_SESSION['errors_etapa6'] as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['errors_etapa6']); ?>
    <?php endif; ?>

    <form action="/negocios/processar_etapa6.php" method="post">

    
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="editar">

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Estágio atual de faturamento</label>
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
                        echo "<option $sel>$op</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Faixa de faturamento bruto nos últimos 12 meses</label>
                <select name="faixa_faturamento" class="form-select" required>
                    <?php
                    $opcoesFaixa = [
                        "Não houve faturamento ainda",
                        "Até R$ 100 mil",
                        "R$ 100 mil – R$ 500 mil",
                        "R$ 500 mil – R$ 1 milhão",
                        "R$ 1 milhão – R$ 5 milhões",
                        "R$ 5 milhões – R$ 20 milhões",
                        "Acima de R$ 20 milhões"
                    ];
                    foreach ($opcoesFaixa as $op) {
                        $sel = ($financeiro['faixa_faturamento'] ?? '') === $op ? 'selected' : '';
                        echo "<option $sel>$op</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Fontes de receita ativas (até 3)</label>
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
            foreach ($fontes as $f): ?>
                <div class="form-check">
                    <input class="form-check-input fonte-check" type="checkbox"
                        name="fontes_receita[]" value="<?= $f ?>"
                        id="<?= md5($f) ?>"
                        <?= in_array($f, $fontesSelecionadas) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="<?= md5($f) ?>"><?= $f ?></label>
                </div>
            <?php endforeach; ?>

            <!-- Campo "Outro" -->
            <input type="text" name="fonte_outro" id="fonte_outro"
                class="form-control mt-2 <?= in_array("Outro (especificar)", $fontesSelecionadas) ? '' : 'd-none' ?>"
                value="<?= htmlspecialchars($financeiro['fonte_outro'] ?? '') ?>"
                placeholder="Se marcou 'Outro', especifique aqui"
                maxlength="120"
                <?= in_array("Outro (especificar)", $fontesSelecionadas) ? 'required' : '' ?>>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const outroCheckbox = document.querySelector("input[value='Outro (especificar)']");
            const outroInput = document.getElementById("fonte_outro");

            outroCheckbox.addEventListener("change", function() {
                if (this.checked) {
                    outroInput.classList.remove("d-none");
                    outroInput.setAttribute("required", "required");
                } else {
                    outroInput.classList.add("d-none");
                    outroInput.removeAttribute("required");
                    outroInput.value = ""; // limpa se desmarcar
                }
            });
        });
        </script>

        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Modelo de monetização principal</label>
            <textarea name="modelo_monetizacao" class="form-control" maxlength="250"><?= htmlspecialchars($financeiro['modelo_monetizacao'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Margem bruta estimada</label>
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
                    echo "<option $sel>$op</option>";
                }
                ?>
            </select>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Mais de 50% da receita vem de produtos/serviços próprios?</label>
                <select name="dependencia_proprios" id="dependencia_proprios" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php
                    $opcoesDep = ["Sim","Não"];
                    foreach ($opcoesDep as $op) {
                        $sel = ($financeiro['dependencia_proprios'] ?? '') === $op ? 'selected' : '';
                        echo "<option value='$op' $sel>$op</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Movi a classe d-none dinâmica para a div que envolve o label e o select -->
            <div class="col-md-6 <?= ($financeiro['dependencia_proprios'] ?? '') === 'Não' ? '' : 'd-none' ?>" id="div_previsao_proprios">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Se não, há previsão de ultrapassar 50% nos próximos 2 anos?</label>
                <select name="previsao_proprios" id="previsao_proprios" class="form-select" <?= ($financeiro['dependencia_proprios'] ?? '') === 'Não' ? 'required' : '' ?>>
                    <option value="">Selecione...</option>
                    <?php
                    $opcoesPrev = ["Sim","Não"];
                    foreach ($opcoesPrev as $op) {
                        $sel = ($financeiro['previsao_proprios'] ?? '') === $op ? 'selected' : '';
                        echo "<option value='$op' $sel>$op</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const dependencia = document.getElementById("dependencia_proprios");
            const divPrevisao = document.getElementById("div_previsao_proprios"); // Pega a div para esconder o label também
            const previsaoSelect = document.getElementById("previsao_proprios"); // Pega o select para o required

            function togglePrevisao() {
                if (dependencia.value === "Não") {
                    divPrevisao.classList.remove("d-none");
                    previsaoSelect.setAttribute("required", "required");
                } else {
                    divPrevisao.classList.add("d-none");
                    previsaoSelect.removeAttribute("required");
                    previsaoSelect.value = ""; // limpa se não for necessário
                }
            }

            dependencia.addEventListener("change", togglePrevisao);

            // O PHP já está controlando a exibição inicial, mas deixamos aqui 
            // como garantia para o JavaScript manter a sincronia.
            togglePrevisao();
        });
        </script>


        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Previsão de crescimento de receita (próximos 12 meses)</label>
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
                        echo "<option $sel>$op</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Investimento externo já captado</label>
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
                        echo "<option $sel>$op</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Novas questões estratégicas -->
        <div class="row mb-3">
            <!-- Prioridade estratégica -->
            <div class="col-md-4">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Qual é sua prioridade estratégica nos próximos 6 meses?</label>
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
                        <input class="form-check-input" type="radio" name="prioridade_estrategica"
                            value="<?= $opcao ?>" <?= ($financeiro['prioridade_estrategica'] ?? '') === $opcao ? 'checked' : '' ?>>
                        <label class="form-check-label"><?= $opcao ?></label>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pronto para investimento/parceria -->
            <div class="col-md-4">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Você está pronto para receber investimento ou parceria agora?</label>
                <?php
                $opcoesPronto = [
                    "Sim – temos documentação e estrutura organizadas",
                    "Parcialmente – precisamos de ajustes",
                    "Ainda não estamos preparados"
                ];
                foreach ($opcoesPronto as $opcao): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="pronto_investimento"
                            value="<?= $opcao ?>" <?= ($financeiro['pronto_investimento'] ?? '') === $opcao ? 'checked' : '' ?>>
                        <label class="form-check-label"><?= $opcao ?></label>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Faixa de investimento -->
            <div class="col-md-4">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Qual faixa de investimento ou apoio você busca?</label>
                <?php
                $opcoesFaixa = [
                    "Até R$ 100 mil",
                    "R$ 100 mil – R$ 500 mil",
                    "R$ 500 mil – R$ 2 milhões",
                    "Acima de R$ 2 milhões",
                    "Não buscamos capital financeiro"
                ];
                foreach ($opcoesFaixa as $opcao): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="faixa_investimento"
                            value="<?= $opcao ?>" <?= ($financeiro['faixa_investimento'] ?? '') === $opcao ? 'checked' : '' ?>>
                        <label class="form-check-label"><?= $opcao ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="/negocios/editar_etapa5.php?id=<?= $negocio_id ?>" class="btn btn-secondary me-md-2">← Voltar</a>
            <button type="submit" class="btn btn-primary">Salvar e avançar</button>
        </div>
    </form>
</div>
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