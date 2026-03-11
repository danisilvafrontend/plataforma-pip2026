<?php
// Assumindo que _shared.php já foi incluído na página principal e define $negocioid e funções de busca
// Exemplo: require_once '_shared.php'; $financeiro = buscarFinanceiro($negocioid ?? 0);
?>

<!-- Bloco 06 - Informações Financeiras -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>
            <i class="bi bi-currency-dollar me-1"></i>
            Informações Financeiras - Etapa 6 <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
        </strong>
        <?php 
            $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
            $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;
            
            if (!$ehAdmin && !$somenteLeitura): 
            ?>
                <a href="/negocios/editar_etapa6.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn btn-sm btn-outline-primary">Editar</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-12">
                <p class="h5 mb-3">
                    <strong>
                        <i class="bi bi-graph-up-arrow text-success me-1"></i>
                        Estágio de Faturamento
                    </strong>
                </p>
                <?php if (!empty($financeiro['estagio_faturamento'])): ?>
                    <div class="alert alert-light text-center">
                        <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                        <?php echo htmlspecialchars($financeiro['estagio_faturamento']); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-3"></i>
                        Nenhum estágio de faturamento informado
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <p class="h5 mb-3">
                    <strong>
                        <i class="bi bi-tag text-info me-1"></i>
                        Faixa de Faturamento
                    </strong>
                </p>
                <?php if (!empty($financeiro['faixa_faturamento'])): ?>
                    <div class="alert alert-light border">
                        <?php echo htmlspecialchars($financeiro['faixa_faturamento']); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary text-center border">
                        <i class="bi bi-dash me-2"></i>
                        Não informado
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <p class="h5 mb-3">
                    <strong>
                        <i class="bi bi-piggy-bank text-warning me-1"></i>
                        Modelo de Monetização
                    </strong>
                </p>
                <?php if (!empty($financeiro['modelo_monetizacao'])): ?>
                    <div class="alert alert-light border">
                        <?php echo htmlspecialchars($financeiro['modelo_monetizacao']); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light text-center border">
                        <i class="bi bi-dash me-2"></i>
                        Não informado
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($financeiro['fontes_receita'])): 
            $fontes = json_decode($financeiro['fontes_receita'], true) ?? [];
            $fontes = is_array($fontes) ? array_slice($fontes, 0, 3) : [];
        ?>
        <h5 class="mb-3">
            <strong>
                <i class="bi bi-cash-stack me-1"></i>
                Fontes de Receita (<?php echo count($fontes); ?>)
            </strong>
        </h5>
        <div class="row g-3 justify-content-start mb-4">
            <?php foreach ($fontes as $fonte): ?>
                <div class="col-auto">
                    <span class="badge bg-secondary fs-6 px-3 py-2">
                        <?php echo htmlspecialchars($fonte ?: 'Outra'); ?>
                    </span>
                </div>
            <?php endforeach; ?>
            <?php if (!empty($financeiro['fonte_outro'])): ?>
                <div class="col-auto">
                    <span class="badge bg-secondary fs-6 px-3 py-2">
                        <?php echo htmlspecialchars($financeiro['fonte_outro']); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <p class="h5 mb-3">
                    <strong>
                        <i class="bi bi-percent text-danger me-1"></i>
                        Margem Bruta
                    </strong>
                </p>
                <?php echo !empty($financeiro['margem_bruta']) ? 
                    '<div class="alert alert-light border">' . htmlspecialchars($financeiro['margem_bruta']) . '</div>' : 
                    '<div class="alert alert-light text-center border"><i class="bi bi-dash me-2"></i>Não informado</div>'; ?>
            </div>
            <div class="col-md-6">
                <p class="h5 mb-3">
                    <strong>
                        <i class="bi bi-arrow-up-right text-success me-1"></i>
                        Previsão de Crescimento
                    </strong>
                </p>
                <?php echo !empty($financeiro['previsao_crescimento']) ? 
                    '<div class="alert alert-light border">' . htmlspecialchars($financeiro['previsao_crescimento']) . '</div>' : 
                    '<div class="alert alert-light text-center border"><i class="bi bi-dash me-2"></i>Não informado</div>'; ?>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <p class="h5 mb-3">
                    <strong>
                        <i class="bi bi-people-fill text-primary me-1"></i>
                        Dependências de Próprios
                    </strong>                    
                    <span class="small-muted d-block mt-1">Mais de 50% da receita vem de produtos/serviços próprios</span>
                </p>
                <?php echo !empty($financeiro['dependencia_proprios']) ? 
                    '<div class="alert alert-light border">' . nl2br(htmlspecialchars($financeiro['dependencia_proprios'])) . '</div>' : 
                    '<div class="alert alert-light text-center border"><i class="bi bi-dash me-2"></i>Não informado</div>'; ?>
            </div>
            <div class="col-md-6">
                <p class="h5 mb-3">
                    <strong>
                        <i class="bi bi-calendar-check text-info me-1"></i>
                        Previsão Próprios
                    </strong>                    
                    <span class="small-muted d-block mt-1">Se não, há previsão de ultrapassar 50% nos próximos 2 anos</span>
                </p>
                <?php echo !empty($financeiro['previsao_proprios']) ? 
                    '<div class="alert alert-light border">' . nl2br(htmlspecialchars($financeiro['previsao_proprios'])) . '</div>' : 
                    '<div class="alert alert-light text-center border"><i class="bi bi-dash me-2"></i>Não informado</div>'; ?>
            </div>
        </div>

        <!-- NOVA SEÇÃO: ESTRATÉGIA E CAPTAÇÃO DE RECURSOS -->
        <h5 class="mb-4">
            <i class="bi bi-rocket-takeoff-fill me-2"></i> Estratégia e Captação
        </h5>

        <div class="row mb-4">
            <div class="col-md-6">
                <p class="h5 mb-3">
                    <strong>
                        <i class="bi bi-crosshair text-danger me-1"></i>
                        Prioridade estratégica (próximos 6 meses)
                    </strong>
                </p>
                <?php if (!empty($financeiro['prioridade_estrategica'])): ?>
                    <div class="alert alert-light border">
                        <strong><i class="bi bi-bullseye me-2"></i></strong> <?php echo htmlspecialchars($financeiro['prioridade_estrategica']); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light text-center border">
                        <i class="bi bi-dash me-2"></i>Não informado
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <p class="h5 mb-3">
                    <strong>
                        <i class="bi bi-hand-thumbs-up-fill text-warning me-1"></i>
                        Histórico de Investimento Externo
                    </strong>
                </p>
                <?php if (!empty($financeiro['investimento_externo'])): ?>
                    <div class="alert alert-light border">
                        <?php echo nl2br(htmlspecialchars($financeiro['investimento_externo'])); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light text-center border">
                        <i class="bi bi-dash me-2"></i>Nenhum histórico registrado
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <p class="h5 mb-3">
                    <strong>
                        <i class="bi bi-clipboard2-check-fill text-success me-1"></i>
                        Prontidão para receber investimento
                    </strong>
                </p>
                <?php if (!empty($financeiro['pronto_investimento'])): ?>
                    <?php 
                        $badgeClass = 'bg-secondary';
                        if (strpos($financeiro['pronto_investimento'], 'Sim') !== false) $badgeClass = 'bg-success';
                        elseif (strpos($financeiro['pronto_investimento'], 'Parcialmente') !== false) $badgeClass = 'bg-warning text-dark';
                        elseif (strpos($financeiro['pronto_investimento'], 'Ainda não') !== false) $badgeClass = 'bg-danger';
                    ?>
                    <div class="alert alert-light border d-flex align-items-center">
                        <span class="badge <?php echo $badgeClass; ?> p-2 me-3 fs-6">Status</span>
                        <span class="fs-6"><?php echo htmlspecialchars($financeiro['pronto_investimento']); ?></span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light text-center border">
                        <i class="bi bi-dash me-2"></i>Não informado
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <p class="h5 mb-3">
                    <strong>
                        <i class="bi bi-wallet2 text-primary me-1"></i>
                        Faixa de investimento buscado
                    </strong>
                </p>
                <?php if (!empty($financeiro['faixa_investimento'])): ?>
                    <div class="alert alert-light border d-flex align-items-center">
                        <i class="bi bi-cash-coin fs-3 text-secondary me-3"></i>
                        <span class="fs-6 fw-bold text-dark"><?php echo htmlspecialchars($financeiro['faixa_investimento']); ?></span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light text-center border">
                        <i class="bi bi-dash me-2"></i>Não informado
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty(array_filter($financeiro))): ?>
        <div class="alert alert-light text-center mt-5 border">
            <i class="bi bi-info-circle-fill me-2 fs-3"></i>
            Nenhuma informação financeira cadastrada ainda.
        </div>
        <?php endif; ?>
    </div>
</div>
