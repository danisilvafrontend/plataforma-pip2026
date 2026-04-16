<?php
// bloco_etapa7.php - Visualização da Etapa 7 (Avaliação de Impacto)
// Espera: $negocio, $negocio_id, $impacto (array do negocio_impacto)

if (!isset($negocio) || !isset($negocio_id)) return;

// Se $impacto vier como false do fetch, normaliza para []
$impacto = is_array($impacto) ? $impacto : [];

// Helpers do _shared.php
$beneficiarios = impacto_beneficiarios($impacto);
$metricas      = impacto_metricas($impacto);
$formas        = impacto_formas_medicao($impacto);
$links         = impacto_links($impacto);
$pdfs          = impacto_pdfs($impacto);
?>

<div class="emp-review-card mb-4">
    <div class="emp-review-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="emp-review-card-title">
            <i class="bi bi-bar-chart-line me-1"></i> Avaliação de Impacto
            <span class="emp-review-step">(Etapa 6)</span>
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
        <?php if (empty(array_filter($impacto))): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                Nenhuma informação de impacto cadastrada ainda.
            </div>
        <?php else: ?>

            <div class="row g-4">

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock">
                        <div class="emp-review-subblock-title principal">
                            <i class="bi bi-lightbulb-fill text-success me-1"></i> Intencionalidade
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>
                        <div class="emp-review-context">Qual das opções melhor representa a relação entre geração de receita e missão do seu negócio?</div>
                        <?= !empty($impacto['intencionalidade'])
                            ? '<div class="emp-review-content-box">'.nl2br(e($impacto['intencionalidade'])).'</div>'
                            : '<div class="emp-review-empty">Não informado</div>'; ?>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock">
                        <div class="emp-review-subblock-title secondary">
                            <i class="bi bi-diagram-3-fill text-info me-1"></i> Tipo de Impacto
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>
                        <div class="emp-review-context">Como você classificaria o tipo de impacto que seu negócio gera hoje?</div>
                        <?= !empty($impacto['tipo_impacto'])
                            ? '<div class="emp-review-content-box">'.nl2br(e($impacto['tipo_impacto'])).'</div>'
                            : '<div class="emp-review-empty">Não informado</div>'; ?>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock">
                        <div class="emp-review-subblock-title secondary">
                            <i class="bi bi-people-fill text-primary me-1"></i> Beneficiários
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>
                        <div class="emp-review-context">Quem são os principais grupos beneficiados pelo seu negócio?</div>
                        <div class="emp-review-content-box">
                            <?= render_badges($beneficiarios, 'primary') ?>
                            <?php if (!empty($impacto['beneficiario_outro'])): ?>
                                <div class="mt-2 small-muted">Outro: <?= e($impacto['beneficiario_outro']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock">
                        <div class="emp-review-subblock-title secondary">
                            <i class="bi bi-geo-alt-fill text-warning me-1"></i> Alcance
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>
                        <div class="emp-review-context">Beneficiários diretos nos últimos 2 anos</div>
                        <?= !empty($impacto['alcance'])
                            ? '<div class="emp-review-content-box">'.nl2br(e($impacto['alcance'])).'</div>'
                            : '<div class="emp-review-empty">Não informado</div>'; ?>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock">
                        <div class="emp-review-subblock-title secondary">
                            <i class="bi bi-graph-up-arrow text-success me-1"></i> Métricas
                            <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                        </div>
                        <div class="emp-review-context">Métricas e indicadores utilizados para mensurar o impacto</div>
                        <div class="emp-review-content-box">
                            <?= render_badges($metricas, 'success') ?>
                            <?php if (!empty($impacto['metrica_outro'])): ?>
                                <div class="mt-2 small-muted">Outra: <?= e($impacto['metrica_outro']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock">
                        <div class="emp-review-subblock-title secondary">
                            <i class="bi bi-clipboard-check text-info me-1"></i> Medição
                            <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                        </div>

                        <div class="emp-review-context">A empresa mede seu impacto socioambiental?</div>
                        <?= !empty($impacto['medicao'])
                            ? '<div class="emp-review-content-box mb-3">'.nl2br(e($impacto['medicao'])).'</div>'
                            : '<div class="emp-review-empty mb-3">Não informado</div>'; ?>

                        <div class="emp-review-context">Como o impacto é medido hoje?</div>
                        <div class="emp-review-content-box">
                            <?= render_badges($formas, 'secondary') ?>
                            <?php if (!empty($impacto['forma_outro'])): ?>
                                <div class="mt-2 small-muted">Outra: <?= e($impacto['forma_outro']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock">
                        <div class="emp-review-subblock-title secondary">
                            <i class="bi bi-journal-text text-primary me-1"></i> Reporte
                            <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                        </div>
                        <div class="emp-review-context">Tipo de reporte ou prestação de contas do impacto</div>
                        <?= !empty($impacto['reporte'])
                            ? '<div class="emp-review-content-box">'.nl2br(e($impacto['reporte'])).'</div>'
                            : '<div class="emp-review-empty">Não informado</div>'; ?>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock">
                        <div class="emp-review-subblock-title secondary">
                            <i class="bi bi-link-45deg text-success me-1"></i> Links de Resultados
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>

                        <?php if (!empty($links)): ?>
                            <div class="emp-review-links-list">
                                <?php foreach ($links as $link): ?>
                                    <a href="<?= attr($link) ?>" target="_blank" class="emp-review-link-row">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                        <span><?= e($link) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="emp-review-empty">Nenhum link informado</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12">
                    <div class="emp-review-subblock">
                        <div class="emp-review-subblock-title secondary">
                            <i class="bi bi-file-earmark-pdf text-danger me-1"></i> PDFs de Resultados
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>

                        <?php if (!empty($pdfs)): ?>
                            <div class="emp-review-links-list">
                                <?php foreach ($pdfs as $pdf): ?>
                                    <a href="/<?= attr($pdf) ?>" target="_blank" class="emp-review-link-row">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                        <span><?= e(basename($pdf)) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="emp-review-empty">Nenhum PDF enviado</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12">
                    <div class="emp-review-subblock">
                        <div class="emp-review-subblock-title principal">
                            <i class="bi bi-bar-chart-fill text-danger me-1"></i> Resultados
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>
                        <div class="emp-review-context">Resultados de impacto mais relevantes alcançados até hoje</div>
                        <?= !empty($impacto['resultados'])
                            ? '<div class="emp-review-content-box">'.nl2br(e($impacto['resultados'])).'</div>'
                            : '<div class="emp-review-empty">Não informado</div>'; ?>
                    </div>
                </div>

                <div class="col-12">
                    <div class="emp-review-subblock">
                        <div class="emp-review-subblock-title principal">
                            <i class="bi bi-forward-fill text-warning me-1"></i> Próximos Passos
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>
                        <?= !empty($impacto['proximos_passos'])
                            ? '<div class="emp-review-content-box">'.nl2br(e($impacto['proximos_passos'])).'</div>'
                            : '<div class="emp-review-empty">Não informado</div>'; ?>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</div>