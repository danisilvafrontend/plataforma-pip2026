<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: /negocios/meus-negocios.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$user_id = $_SESSION['user_id'];
$negocio_id = (int) $_GET['id'];
$pdo = getPDO();

$stmt = $pdo->prepare("SELECT id FROM negocios WHERE id = :id AND user_id = :user_id LIMIT 1");
$stmt->execute([
    'id' => $negocio_id,
    'user_id' => $user_id,
]);

if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    die('Negócio não encontrado ou acesso negado.');
}

$stmt = $pdo->prepare("SELECT * FROM negocio_financeiro WHERE negocio_id = :id LIMIT 1");
$stmt->execute(['id' => $negocio_id]);
$financeiro = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$fontesSelecionadas = json_decode($financeiro['fontes_receita'] ?? '[]', true);
if (!is_array($fontesSelecionadas)) {
    $fontesSelecionadas = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Etapa 6 — Financeiro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/negocios.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">

                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h1 class="h3 mb-1">Etapa 6 — Financeiro</h1>
                            <p class="text-muted mb-0">Receita, monetização, crescimento e prontidão para investimento.</p>
                        </div>
                        <a href="/negocios/meus-negocios.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                    </div>

                    <?php if (!empty($_SESSION['errors_etapa6'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($_SESSION['errors_etapa6'] as $erro): ?>
                                    <li><?= htmlspecialchars($erro) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['errors_etapa6']); ?>
                    <?php endif; ?>

                    <form action="/negocios/processar_etapa6.php" method="POST">
                        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">

                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="bi bi-graph-up-arrow"></i> Faturamento e estrutura financeira
                            </div>

                            <div class="row g-4">
                                <div class="col-12 col-md-6">
                                    <label for="estagio_faturamento" class="form-label etapa6-label">
                                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                        Estágio de faturamento *
                                    </label>
                                    <select name="estagio_faturamento" id="estagio_faturamento" class="form-select etapa6-select" required>
                                        <option value="">Selecione...</option>
                                        <option <?= ($financeiro['estagio_faturamento'] ?? '') === 'Ainda sem faturamento' ? 'selected' : '' ?>>Ainda sem faturamento</option>
                                        <option <?= ($financeiro['estagio_faturamento'] ?? '') === 'Primeiras vendas' ? 'selected' : '' ?>>Primeiras vendas</option>
                                        <option <?= ($financeiro['estagio_faturamento'] ?? '') === 'Faturamento recorrente' ? 'selected' : '' ?>>Faturamento recorrente</option>
                                        <option <?= ($financeiro['estagio_faturamento'] ?? '') === 'Escala comercial' ? 'selected' : '' ?>>Escala comercial</option>
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
                                <i class="bi bi-bar-chart-line"></i> Validação de mercado
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Como você avalia a demanda pelo seu produto ou serviço hoje?
                                    <span class="text-danger">*</span>
                                </label>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="validacao_mercado"
                                           id="dem_crescente" value="demanda_crescente_validada"
                                           <?= ($financeiro['validacao_mercado'] ?? '') === 'demanda_crescente_validada' ? 'checked' : '' ?>
                                           required>
                                    <label class="form-check-label" for="dem_crescente">
                                        Demanda comprovada e crescente — temos clientes recorrentes, contratos ativos ou lista de espera
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="validacao_mercado"
                                           id="dem_local" value="demanda_validada_local"
                                           <?= ($financeiro['validacao_mercado'] ?? '') === 'demanda_validada_local' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="dem_local">
                                        Demanda validada localmente — já temos vendas, mas ainda concentradas em uma região ou nicho específico
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="validacao_mercado"
                                           id="dem_validacao" value="demanda_em_validacao"
                                           <?= ($financeiro['validacao_mercado'] ?? '') === 'demanda_em_validacao' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="dem_validacao">
                                        Demanda em validação — estamos testando com primeiros clientes ou em fase piloto
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="validacao_mercado"
                                           id="dem_nao_testada" value="demanda_nao_testada"
                                           <?= ($financeiro['validacao_mercado'] ?? '') === 'demanda_nao_testada' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="dem_nao_testada">
                                        Demanda identificada, mas ainda não testada com clientes reais
                                    </label>
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
                                    "Editais / convênios / contratos públicos",
                                    "Patrocínio ou doações",
                                    "Outro (especificar)",
                                ];
                                ?>

                                <div class="row g-2">
                                    <?php foreach ($fontes as $fonte): ?>
                                        <div class="col-12 col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="fontes_receita[]" value="<?= $fonte ?>"
                                                    id="<?= md5($fonte) ?>" <?= in_array($fonte, $fontesSelecionadas) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="<?= md5($fonte) ?>"><?= $fonte ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <input type="text" name="fonte_outro" class="form-control mt-3" maxlength="120"
                                       placeholder="Se marcou 'Outro', especifique aqui"
                                       value="<?= htmlspecialchars($financeiro['fonte_outro'] ?? '') ?>">
                            </div>

                            <div class="mb-0">
                                <label for="modelo_monetizacao" class="form-label etapa6-label">
                                    <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                    Descreva brevemente o modelo principal de monetização *
                                </label>
                                <textarea name="modelo_monetizacao" id="modelo_monetizacao" rows="4" class="form-control" maxlength="1000" required><?= htmlspecialchars($financeiro['modelo_monetizacao'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="bi bi-speedometer2"></i> Margem, recursos próprios e crescimento
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="margem_bruta" class="form-label etapa6-label">
                                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                        Margem bruta aproximada *
                                    </label>
                                    <select name="margem_bruta" id="margem_bruta" class="form-select etapa6-select" required>
                                        <option value="">Selecione...</option>
                                        <option <?= ($financeiro['margem_bruta'] ?? '') === 'Menor que 20%' ? 'selected' : '' ?>>Menor que 20%</option>
                                        <option <?= ($financeiro['margem_bruta'] ?? '') === 'Entre 20% e 40%' ? 'selected' : '' ?>>Entre 20% e 40%</option>
                                        <option <?= ($financeiro['margem_bruta'] ?? '') === 'Entre 40% e 60%' ? 'selected' : '' ?>>Entre 40% e 60%</option>
                                        <option <?= ($financeiro['margem_bruta'] ?? '') === 'Acima de 60%' ? 'selected' : '' ?>>Acima de 60%</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="dependencia_proprios" class="form-label etapa6-label">
                                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                        Mais de 50% da operação depende de recursos próprios? *
                                    </label>
                                    <select name="dependencia_proprios" id="dependencia_proprios" class="form-select etapa6-select" required>
                                        <option value="">Selecione...</option>
                                        <option <?= ($financeiro['dependencia_proprios'] ?? '') === 'Sim' ? 'selected' : '' ?>>Sim</option>
                                        <option <?= ($financeiro['dependencia_proprios'] ?? '') === 'Não' ? 'selected' : '' ?>>Não</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="previsao_proprios" class="form-label etapa6-label">
                                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                        Se não, quando estima reduzir ainda mais a dependência?
                                    </label>
                                    <select name="previsao_proprios" id="previsao_proprios" class="form-select etapa6-select">
                                        <option value="">Selecione...</option>
                                        <option <?= ($financeiro['previsao_proprios'] ?? '') === 'Até 6 meses' ? 'selected' : '' ?>>Até 6 meses</option>
                                        <option <?= ($financeiro['previsao_proprios'] ?? '') === 'Entre 6 e 12 meses' ? 'selected' : '' ?>>Entre 6 e 12 meses</option>
                                        <option <?= ($financeiro['previsao_proprios'] ?? '') === 'Mais de 12 meses' ? 'selected' : '' ?>>Mais de 12 meses</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="previsao_crescimento" class="form-label etapa6-label">
                                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                        Previsão de crescimento para os próximos 12 meses *
                                    </label>
                                    <select name="previsao_crescimento" id="previsao_crescimento" class="form-select etapa6-select" required>
                                        <option value="">Selecione...</option>
                                        <option <?= ($financeiro['previsao_crescimento'] ?? '') === 'Estável ou retração esperada' ? 'selected' : '' ?>>Estável ou retração esperada</option>
                                        <option <?= ($financeiro['previsao_crescimento'] ?? '') === 'Crescimento de até 50%' ? 'selected' : '' ?>>Crescimento de até 50%</option>
                                        <option <?= ($financeiro['previsao_crescimento'] ?? '') === 'Crescimento entre 50% e 100%' ? 'selected' : '' ?>>Crescimento entre 50% e 100%</option>
                                        <option <?= ($financeiro['previsao_crescimento'] ?? '') === 'Crescimento acima de 100%' ? 'selected' : '' ?>>Crescimento acima de 100%</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="bi bi-bank"></i> Investimento e prioridade estratégica
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="investimento_externo" class="form-label etapa6-label">
                                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                        O negócio já recebeu investimento externo? *
                                    </label>
                                    <select name="investimento_externo" id="investimento_externo" class="form-select etapa6-select" required>
                                        <option value="">Selecione...</option>
                                        <option <?= ($financeiro['investimento_externo'] ?? '') === 'Não' ? 'selected' : '' ?>>Não</option>
                                        <option <?= ($financeiro['investimento_externo'] ?? '') === 'Sim, investimento anjo' ? 'selected' : '' ?>>Sim, investimento anjo</option>
                                        <option <?= ($financeiro['investimento_externo'] ?? '') === 'Sim, fundo / VC / impacto' ? 'selected' : '' ?>>Sim, fundo / VC / impacto</option>
                                        <option <?= ($financeiro['investimento_externo'] ?? '') === 'Sim, outro tipo' ? 'selected' : '' ?>>Sim, outro tipo</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="prioridade_estrategica" class="form-label etapa6-label">
                                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                        Principal prioridade estratégica nos próximos 6 meses *
                                    </label>
                                    <select name="prioridade_estrategica" id="prioridade_estrategica" class="form-select etapa6-select" required>
                                        <option value="">Selecione...</option>
                                        <option <?= ($financeiro['prioridade_estrategica'] ?? '') === 'Vender mais / crescer receita' ? 'selected' : '' ?>>Vender mais / crescer receita</option>
                                        <option <?= ($financeiro['prioridade_estrategica'] ?? '') === 'Estruturar operação' ? 'selected' : '' ?>>Estruturar operação</option>
                                        <option <?= ($financeiro['prioridade_estrategica'] ?? '') === 'Desenvolver produto / tecnologia' ? 'selected' : '' ?>>Desenvolver produto / tecnologia</option>
                                        <option <?= ($financeiro['prioridade_estrategica'] ?? '') === 'Captar investimento / parceria' ? 'selected' : '' ?>>Captar investimento / parceria</option>
                                        <option <?= ($financeiro['prioridade_estrategica'] ?? '') === 'Expandir mercado / novas regiões' ? 'selected' : '' ?>>Expandir mercado / novas regiões</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="pronto_investimento" class="form-label etapa6-label">
                                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                        Está pronto para receber investimento ou parceria? *
                                    </label>
                                    <select name="pronto_investimento" id="pronto_investimento" class="form-select etapa6-select" required>
                                        <option value="">Selecione...</option>
                                        <option <?= ($financeiro['pronto_investimento'] ?? '') === 'Sim, já temos materiais e estrutura mínima' ? 'selected' : '' ?>>Sim, já temos materiais e estrutura mínima</option>
                                        <option <?= ($financeiro['pronto_investimento'] ?? '') === 'Parcialmente, ainda ajustando estrutura' ? 'selected' : '' ?>>Parcialmente, ainda ajustando estrutura</option>
                                        <option <?= ($financeiro['pronto_investimento'] ?? '') === 'Ainda não, precisamos amadurecer o negócio' ? 'selected' : '' ?>>Ainda não, precisamos amadurecer o negócio</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="faixa_investimento" class="form-label etapa6-label">
                                        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                        Faixa de investimento ou apoio buscada *
                                    </label>
                                    <select name="faixa_investimento" id="faixa_investimento" class="form-select etapa6-select" required>
                                        <option value="">Selecione...</option>
                                        <option <?= ($financeiro['faixa_investimento'] ?? '') === 'Até R$ 100 mil' ? 'selected' : '' ?>>Até R$ 100 mil</option>
                                        <option <?= ($financeiro['faixa_investimento'] ?? '') === 'R$ 100 mil – R$ 500 mil' ? 'selected' : '' ?>>R$ 100 mil – R$ 500 mil</option>
                                        <option <?= ($financeiro['faixa_investimento'] ?? '') === 'R$ 500 mil – R$ 1 milhão' ? 'selected' : '' ?>>R$ 500 mil – R$ 1 milhão</option>
                                        <option <?= ($financeiro['faixa_investimento'] ?? '') === 'Acima de R$ 1 milhão' ? 'selected' : '' ?>>Acima de R$ 1 milhão</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="/negocios/etapa5_apresentacao.php?id=<?= $negocio_id ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Etapa anterior
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Salvar e continuar <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
