<?php
// bloco_etapa8.php - Visualização da Etapa 8 (Visão de Futuro)
// Espera: $negocio, $negocio_id, $visao (array do negocio_visao)

if (!isset($negocio) || !isset($negocio_id)) return;

// Se $visao vier como false do fetch, normaliza para []
$visao = is_array($visao) ? $visao : [];

// Helpers do _shared.php
$apoios = decode_json_array($visao['apoios'] ?? '[]');
$areas  = decode_json_array($visao['areas'] ?? '[]');
$temas  = decode_json_array($visao['temas'] ?? '[]');
?>

<div class="emp-review-card mb-4">
    <div class="emp-review-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="emp-review-card-title">
            <i class="bi bi-eye-fill me-1"></i> Visão de Futuro
            <span class="emp-review-step">(Etapa 7)</span>
            <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
        </div>

        <?php
        $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
        $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;

        if (!$ehAdmin && !$somenteLeitura):
        ?>
            <a href="/negocios/editar_etapa7.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn-emp-outline btn-sm">
                Editar
            </a>
        <?php endif; ?>
    </div>

    <div class="emp-review-card-body">
        <?php if (empty(array_filter($visao))): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                Nenhuma informação de visão cadastrada ainda.
            </div>
        <?php else: ?>

            <div class="row g-4">

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock h-100">
                        <div class="emp-review-subblock-title principal">
                            <i class="bi bi-lightbulb-fill me-1"></i> Visão Estratégica
                        </div>

                        <?php if (!empty($visao['visao_estrategica'])): ?>
                            <div class="emp-review-text-box">
                                <?= nl2br(e($visao['visao_estrategica'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="emp-review-empty-box">Não informado</div>
                        <?php endif; ?>

                        <?php if (!empty($visao['visao_outro'])): ?>
                            <div class="emp-review-extra-note mt-2">
                                <strong>Outro:</strong> <?= e($visao['visao_outro']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock h-100">
                        <div class="emp-review-subblock-title principal">
                            <i class="bi bi-tree-fill me-1"></i> Sustentabilidade
                        </div>

                        <?php if (!empty($visao['sustentabilidade'])): ?>
                            <div class="emp-review-text-box">
                                <?= nl2br(e($visao['sustentabilidade'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="emp-review-empty-box">Não informado</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock h-100">
                        <div class="emp-review-subblock-title secondary">
                            <i class="bi bi-arrows-expand me-1"></i> Escala
                        </div>

                        <?php if (!empty($visao['escala'])): ?>
                            <div class="emp-review-text-box">
                                <?= nl2br(e($visao['escala'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="emp-review-empty-box">Não informado</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock h-100">
                        <div class="emp-review-subblock-title secondary">
                            <i class="bi bi-hand-thumbs-up-fill me-1"></i> Apoios
                        </div>

                        <div class="emp-review-helper-text">
                            Apoio financeiro ou estratégico que você busca atualmente.
                        </div>

                        <?= render_badges($apoios, 'primary') ?>

                        <?php if (!empty($visao['apoio_outro'])): ?>
                            <div class="emp-review-extra-note mt-2">
                                <strong>Outro:</strong> <?= e($visao['apoio_outro']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock h-100">
                        <div class="emp-review-subblock-title secondary">
                            <i class="bi bi-geo-alt-fill me-1"></i> Áreas de Atuação
                        </div>

                        <div class="emp-review-helper-text">
                            Áreas do seu negócio que você gostaria de fortalecer com apoio externo.
                        </div>

                        <?= render_badges($areas, 'primary') ?>

                        <?php if (!empty($visao['area_outro'])): ?>
                            <div class="emp-review-extra-note mt-2">
                                <strong>Outra:</strong> <?= e($visao['area_outro']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="emp-review-subblock h-100">
                        <div class="emp-review-subblock-title secondary">
                            <i class="bi bi-bookmark-star-fill me-1"></i> Temas Prioritários
                        </div>

                        <div class="emp-review-helper-text">
                            Temas que você gostaria de aprender ou trocar com outros empreendedores e mentores.
                        </div>

                        <?= render_badges($temas, 'danger') ?>

                        <?php if (!empty($visao['tema_outro'])): ?>
                            <div class="emp-review-extra-note mt-2">
                                <strong>Outro:</strong> <?= e($visao['tema_outro']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        <?php endif; ?>
    </div>
</div>