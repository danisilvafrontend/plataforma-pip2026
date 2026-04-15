<?php
// Assumindo que _shared.php já foi incluído na página principal e define $negocioid e funções de busca
// Exemplo: require_once '_shared.php'; $financeiro = buscarFinanceiro($negocioid ?? 0);
?>

<!-- Bloco 06 - Informações Financeiras -->
<div class="emp-review-card mb-4">
    <div class="emp-review-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="emp-review-card-title">
            <i class="bi bi-currency-dollar me-1"></i>
            Informações Financeiras
            <span class="emp-review-step">(Etapa 6)</span>
            <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
        </div>

        <?php
        $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
        $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;

        if (!$ehAdmin && !$somenteLeitura):
        ?>
            <a href="/negocios/editar_etapa6.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn-emp-outline btn-sm">
                Editar
            </a>
        <?php endif; ?>
    </div>

    <div class="emp-review-card-body">

        <div class="emp-review-subblock mb-4">
            <div class="emp-review-subblock-title principal">
                <i class="bi bi-graph-up-arrow me-1"></i> Estágio de Faturamento
            </div>

            <?php if (!empty($financeiro['estagio_faturamento'])): ?>
                <div class="emp-review-finance-highlight">
                    <div class="emp-review-finance-highlight-text">
                        <?= htmlspecialchars($financeiro['estagio_faturamento']); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Nenhum estágio de faturamento informado
                </div>
            <?php endif; ?>
        </div>

        <div class="emp-review-subblock mb-4">
            <div class="emp-review-subblock-title secondary">
                <i class="bi bi-bar-chart-line me-1"></i> Estrutura financeira
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <div class="emp-review-finance-card">
                        <div class="emp-review-finance-card-title">
                            <i class="bi bi-tag text-info me-1"></i> Faixa de Faturamento
                        </div>
                        <div class="emp-review-finance-card-value">
                            <?= !empty($financeiro['faixa_faturamento']) ? htmlspecialchars($financeiro['faixa_faturamento']) : 'Não informado'; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-finance-card">
                        <div class="emp-review-finance-card-title">
                            <i class="bi bi-piggy-bank text-warning me-1"></i> Modelo de Monetização
                        </div>
                        <div class="emp-review-finance-card-value">
                            <?= !empty($financeiro['modelo_monetizacao']) ? htmlspecialchars($financeiro['modelo_monetizacao']) : 'Não informado'; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-finance-card">
                        <div class="emp-review-finance-card-title">
                            <i class="bi bi-percent text-danger me-1"></i> Margem Bruta
                        </div>
                        <div class="emp-review-finance-card-value">
                            <?= !empty($financeiro['margem_bruta']) ? htmlspecialchars($financeiro['margem_bruta']) : 'Não informado'; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-finance-card">
                        <div class="emp-review-finance-card-title">
                            <i class="bi bi-arrow-up-right text-success me-1"></i> Previsão de Crescimento
                        </div>
                        <div class="emp-review-finance-card-value">
                            <?= !empty($financeiro['previsao_crescimento']) ? htmlspecialchars($financeiro['previsao_crescimento']) : 'Não informado'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($financeiro['fontes_receita'])):
            $fontes = json_decode($financeiro['fontes_receita'], true) ?? [];
            $fontes = is_array($fontes) ? array_slice($fontes, 0, 3) : [];
        ?>
            <div class="emp-review-subblock mb-4">
                <div class="emp-review-subblock-title secondary">
                    <i class="bi bi-cash-stack me-1"></i> Fontes de Receita
                    <span class="emp-review-count">(<?= count($fontes) + (!empty($financeiro['fonte_outro']) ? 1 : 0) ?>)</span>
                </div>

                <div class="emp-review-links">
                    <?php foreach ($fontes as $fonte): ?>
                        <span class="emp-review-link-chip">
                            <?= htmlspecialchars($fonte ?: 'Outra'); ?>
                        </span>
                    <?php endforeach; ?>

                    <?php if (!empty($financeiro['fonte_outro'])): ?>
                        <span class="emp-review-link-chip">
                            <?= htmlspecialchars($financeiro['fonte_outro']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="emp-review-subblock mb-4">
            <div class="emp-review-subblock-title secondary">
                <i class="bi bi-diagram-3 me-1"></i> Receitas próprias
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <div class="emp-review-finance-card h-100">
                        <div class="emp-review-finance-card-title">
                            <i class="bi bi-people-fill text-primary me-1"></i> Dependência de Próprios
                        </div>
                        <div class="emp-review-finance-card-help">
                            Mais de 50% da receita vem de produtos/serviços próprios
                        </div>
                        <div class="emp-review-finance-card-value">
                            <?= !empty($financeiro['dependencia_proprios']) ? nl2br(htmlspecialchars($financeiro['dependencia_proprios'])) : 'Não informado'; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-finance-card h-100">
                        <div class="emp-review-finance-card-title">
                            <i class="bi bi-calendar-check text-info me-1"></i> Previsão Próprios
                        </div>
                        <div class="emp-review-finance-card-help">
                            Se não, há previsão de ultrapassar 50% nos próximos 2 anos
                        </div>
                        <div class="emp-review-finance-card-value">
                            <?= !empty($financeiro['previsao_proprios']) ? nl2br(htmlspecialchars($financeiro['previsao_proprios'])) : 'Não informado'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="emp-review-subblock">
            <div class="emp-review-subblock-title secondary">
                <i class="bi bi-rocket-takeoff-fill me-1"></i> Estratégia e Captação
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <div class="emp-review-finance-card h-100">
                        <div class="emp-review-finance-card-title">
                            <i class="bi bi-crosshair text-danger me-1"></i> Prioridade estratégica
                        </div>
                        <div class="emp-review-finance-card-help">
                            Próximos 6 meses
                        </div>
                        <div class="emp-review-finance-card-value">
                            <?= !empty($financeiro['prioridade_estrategica']) ? htmlspecialchars($financeiro['prioridade_estrategica']) : 'Não informado'; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-finance-card h-100">
                        <div class="emp-review-finance-card-title">
                            <i class="bi bi-hand-thumbs-up-fill text-warning me-1"></i> Histórico de Investimento Externo
                        </div>
                        <div class="emp-review-finance-card-value">
                            <?= !empty($financeiro['investimento_externo']) ? nl2br(htmlspecialchars($financeiro['investimento_externo'])) : 'Nenhum histórico registrado'; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-finance-card h-100">
                        <div class="emp-review-finance-card-title">
                            <i class="bi bi-clipboard2-check-fill text-success me-1"></i> Prontidão para investimento
                        </div>
                        <?php if (!empty($financeiro['pronto_investimento'])): ?>
                            <?php
                                $statusClass = 'neutral';
                                if (strpos($financeiro['pronto_investimento'], 'Sim') !== false) {
                                    $statusClass = 'success';
                                } elseif (strpos($financeiro['pronto_investimento'], 'Parcialmente') !== false) {
                                    $statusClass = 'warning';
                                } elseif (strpos($financeiro['pronto_investimento'], 'Ainda não') !== false) {
                                    $statusClass = 'danger';
                                }
                            ?>
                            <div class="emp-review-status-pill <?= $statusClass ?>">
                                <?= htmlspecialchars($financeiro['pronto_investimento']); ?>
                            </div>
                        <?php else: ?>
                            <div class="emp-review-finance-card-value">Não informado</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-finance-card h-100">
                        <div class="emp-review-finance-card-title">
                            <i class="bi bi-wallet2 text-primary me-1"></i> Faixa de investimento buscado
                        </div>
                        <div class="emp-review-finance-card-value">
                            <?= !empty($financeiro['faixa_investimento']) ? htmlspecialchars($financeiro['faixa_investimento']) : 'Não informado'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty(array_filter($financeiro))): ?>
            <div class="alert alert-light text-center mt-4 border">
                <i class="bi bi-info-circle-fill me-2 fs-3"></i>
                Nenhuma informação financeira cadastrada ainda.
            </div>
        <?php endif; ?>

    </div>
</div>