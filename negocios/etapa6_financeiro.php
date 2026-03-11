<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// Busca fundadores já cadastrados (CORRIGIDO: usa $negocio_id)
$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);  // ✅ $negocio_id
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/empreendedor/header.php'; ?>

<div class="container my-5">
    <h1 class="mb-4">Etapa 6 - Dados Financeiros e Modelo de Receita  </h1>

    <?php
        $etapaAtual = 6;
        include __DIR__ . '/../app/views/partials/progress.php';
        include __DIR__ . '/../app/views/partials/intro_text_financeiro.php';
    ?>

    <form action="/negocios/processar_etapa6.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="cadastro">


        <h3>Estágio atual de faturamento do negócio</h3>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="estagio_faturamento" class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Qual das opções melhor representa o momento atual do seu negócio? </label>
                <select name="estagio_faturamento" id="estagio_faturamento" class="form-select" required>
                    <option value="">Selecione...</option>
                    <option>Ainda não temos produto/serviço validado – sem faturamento previsto.</option>
                    <option>Produto/serviço desenvolvido, mas ainda sem faturamento e sem previsão para os próximos 6 meses.</option>
                    <option>Produto/serviço desenvolvido, com previsão de início de faturamento nos próximos 6 meses.</option>
                    <option>Já há faturamento, mas ainda operando abaixo do ponto de equilíbrio (break-even).</option>
                    <option>Ponto de equilíbrio atingido – início da geração de lucro.</option>
                    <option>Operando com lucro e crescimento consistente de receita.</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="faixa_faturamento" class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Faixa de faturamento bruto nos últimos 12 meses</label>
                <select name="faixa_faturamento" id="faixa_faturamento" class="form-select" required>
                    <option value="">Selecione...</option>
                    <option>Não houve faturamento ainda</option>
                    <option>Até R$ 100 mil</option>
                    <option>R$ 100 mil – R$ 500 mil</option>
                    <option>R$ 500 mil – R$ 1 milhão</option>
                    <option>R$ 1 milhão – R$ 5 milhões</option>
                    <option>R$ 5 milhões – R$ 20 milhões</option>
                    <option>Acima de R$ 20 milhões</option>
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
                <input class="form-check-input" type="checkbox" name="fontes_receita[]" value="<?= $f ?>" id="<?= md5($f) ?>">
                <label class="form-check-label" for="<?= md5($f) ?>"><?= $f ?></label>
                </div>
            <?php endforeach; ?>
            <input type="text" name="fonte_outro" id="fonte_outro" class="form-control mt-2 d-none" placeholder="Se marcou 'Outro', especifique aqui" maxlength="120">
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
            <label for="modelo_monetizacao" class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Modelo de monetização principal</label>
            <small class="text-muted">
                 Descreva brevemente como o seu negócio gera receita atualmente e como pretende monetizar no médio prazo (se for diferente). ⚠️ Até 250 caracteres.
            </small>
            <textarea name="modelo_monetizacao" id="modelo_monetizacao" class="form-control" maxlength="250"><?= htmlspecialchars($financeiro['modelo_monetizacao'] ?? '') ?></textarea>
            
        </div>

        <h3>Dependência de produtos/serviços próprios</h3>

        <div class="mb-3">
            <label for="margem_bruta" class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Atualmente, mais de 50% da sua receita vem de produtos ou serviços próprios? </label>
            <select name="margem_bruta" id="margem_bruta" class="form-select" required>
                <option value="">Selecione...</option>
                <option>Ainda não mensurada</option>
                <option>Menor que 20%</option>
                <option>Entre 20% e 40%</option>
                <option>Entre 40% e 60%</option>
                <option>Acima de 60%</option>
            </select>
        </div>

        <div class="row mb-3">
            <!-- Primeira coluna (Sempre visível) -->
            <div class="col-md-6">
                <label for="dependencia_proprios" class="form-label">
                    <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Mais de 50% da receita vem de produtos/serviços próprios?
                </label>
                <select name="dependencia_proprios" id="dependencia_proprios" class="form-select" required>
                    <option value="">Selecione...</option>
                    <option value="Sim" <?= ($financeiro['dependencia_proprios'] ?? '') === 'Sim' ? 'selected' : '' ?>>Sim</option>
                    <option value="Não" <?= ($financeiro['dependencia_proprios'] ?? '') === 'Não' ? 'selected' : '' ?>>Não</option>
                </select>
            </div>

            <!-- Segunda coluna (Envolve a div inteira com d-none e um ID) -->
            <div class="col-md-6 d-none" id="div_previsao_proprios">
                <label for="previsao_proprios" class="form-label">
                    <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Se não, há previsão de ultrapassar 50% nos próximos 2 anos?
                </label>
                <select name="previsao_proprios" id="previsao_proprios" class="form-select">
                    <option value="">Selecione...</option>
                    <option value="Sim" <?= ($financeiro['previsao_proprios'] ?? '') === 'Sim' ? 'selected' : '' ?>>Sim</option>
                    <option value="Não" <?= ($financeiro['previsao_proprios'] ?? '') === 'Não' ? 'selected' : '' ?>>Não</option>
                </select>
            </div>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const dependencia = document.getElementById("dependencia_proprios");
            const divPrevisao = document.getElementById("div_previsao_proprios"); // Agora pega a div inteira
            const previsaoSelect = document.getElementById("previsao_proprios");  // Pega o select para o required

            function togglePrevisao() {
                if (dependencia.value === "Não") {
                    // Mostra a div inteira (label + select)
                    divPrevisao.classList.remove("d-none");
                    // Torna o select obrigatório
                    previsaoSelect.setAttribute("required", "required");
                } else {
                    // Esconde a div inteira
                    divPrevisao.classList.add("d-none");
                    // Remove a obrigatoriedade
                    previsaoSelect.removeAttribute("required");
                    // Limpa o valor para não salvar sujeira no banco
                    previsaoSelect.value = ""; 
                }
            }

            dependencia.addEventListener("change", togglePrevisao);

            // Executa ao carregar a página (útil no editar)
            togglePrevisao();
        });
        </script>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="previsao_crescimento" class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Previsão de crescimento de receita (próximos 12 meses)</label>
                <select name="previsao_crescimento" id="previsao_crescimento" class="form-select" required>
                    <option value="">Selecione...</option>
                    <option>Estável ou retração esperada</option>
                    <option>Crescimento de até 50%</option>
                    <option>Crescimento entre 50% e 100%</option>
                    <option>Crescimento acima de 100%</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="investimento_externo" class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Investimento externo já captado</label>
                <select name="investimento_externo" id="investimento_externo" class="form-select" required>
                    <option value="">Selecione...</option>
                    <option>Não</option>
                    <option>Sim, investimento anjo</option>
                    <option>Sim, pré-seed / seed</option>
                    <option>Sim, Série A ou superior</option>
                    <option>Apenas recursos próprios (bootstrapping)</option>
                    <option>Doações</option>
                    <option>Premiações</option>
                </select>
            </div>
        </div>

        <!-- Prioridade estratégica -->
         
        <div class="row mb-3">
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

            <!-- 2️⃣ Pronto para investimento/parceria -->
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

            <!-- 3️⃣ Faixa de investimento -->
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