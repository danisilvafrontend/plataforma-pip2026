<?php
// bloco_etapa9.php - Visualização da Etapa 9 (Documentação)
// Espera: $negocio, $negocio_id, $docs (array de negocio_documentos)

if (!isset($negocio) || !isset($negocio_id)) return;

// Se $docs vier como false do fetch, normaliza para []
$docs = is_array($docs) ? $docs : [];

$nomeTrab = !empty($docs['certidao_trabalhista_path'])
    ? basename(parse_url($docs['certidao_trabalhista_path'], PHP_URL_PATH))
    : 'Não enviado';

$nomeAmb = !empty($docs['certidao_ambiental_path'])
    ? basename(parse_url($docs['certidao_ambiental_path'], PHP_URL_PATH))
    : 'Não enviado';

$ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
$statusAprovado = (isset($negocio['status_vitrine']) && $negocio['status_vitrine'] === 'aprovado');
?>

<div class="emp-review-card mb-4">
    <div class="emp-review-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="emp-review-card-title">
            <i class="bi bi-file-earmark-lock-fill me-1"></i>
            Documentação Legal
            <span class="emp-review-step">(Etapa 9)</span>
            <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
        </div>

        <?php if (!$ehAdmin && !$statusAprovado): ?>
            <a href="/negocios/editar_etapa9.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn-emp-outline btn-sm">
                <i class="bi bi-pencil"></i> Editar
            </a>
        <?php endif; ?>
    </div>

    <div class="emp-review-card-body">
        <?php if (empty($docs['certidao_trabalhista_path']) && empty($docs['certidao_ambiental_path'])): ?>
            <div class="alert alert-warning text-center">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-4 text-warning"></i>
                <strong>Documentação pendente</strong><br>
                Certidões trabalhista e ambiental ainda não foram enviadas.
            </div>
        <?php else: ?>

            <div class="emp-review-subblock mb-4">
                <div class="emp-review-subblock-title secondary">
                    <i class="bi bi-folder2-open me-1"></i> Arquivos enviados
                </div>

                <div class="etapa9-review-grid">

                    <!-- Certidão Trabalhista -->
                    <div class="etapa9-review-doc-card">
                        <div class="etapa9-review-doc-top">
                            
                            <div class="etapa9-review-doc-meta">
                                <div class="etapa9-review-doc-title">Certidão Negativa de Débitos Trabalhistas</div>
                                <div class="etapa9-review-doc-status">
                                    <?php if (!empty($docs['certidao_trabalhista_path'])): ?>
                                        <span class="etapa9-review-status-badge success">CNDT - Enviada</span>
                                    <?php else: ?>
                                        <span class="etapa9-review-status-badge warning">CNDT - Pendente</span>
                                    <?php endif; ?>
                                </div>
                                <div class="etapa9-review-doc-filename"><?= htmlspecialchars($nomeTrab) ?></div>
                            </div>
                        </div>

                        <?php if (!empty($docs['certidao_trabalhista_path'])): ?>
                            <div class="etapa9-review-doc-actions">
                                <a href="<?= htmlspecialchars($docs['certidao_trabalhista_path']) ?>"
                                   target="_blank"
                                   class="emp-review-link-chip">
                                    <i class="bi bi-eye"></i> Visualizar PDF
                                </a>

                                <?php if ($ehAdmin): ?>
                                    <a href="<?= htmlspecialchars($docs['certidao_trabalhista_path']) ?>"
                                       download
                                       class="emp-review-link-chip">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Certidão Ambiental -->
                    <div class="etapa9-review-doc-card">
                        <div class="etapa9-review-doc-top">

                            <div class="etapa9-review-doc-meta">
                                <div class="etapa9-review-doc-title">Certidão de Regularidade Ambiental</div>
                                <div class="etapa9-review-doc-status">
                                    <?php if (!empty($docs['certidao_ambiental_path'])): ?>
                                        <span class="etapa9-review-status-badge info">Ambiental - Enviada</span>
                                    <?php else: ?>
                                        <span class="etapa9-review-status-badge warning">Ambiental - Pendente</span>
                                    <?php endif; ?>
                                </div>
                                <div class="etapa9-review-doc-filename"><?= htmlspecialchars($nomeAmb) ?></div>
                            </div>
                        </div>

                        <?php if (!empty($docs['certidao_ambiental_path'])): ?>
                            <div class="etapa9-review-doc-actions">
                                <a href="<?= htmlspecialchars($docs['certidao_ambiental_path']) ?>"
                                   target="_blank"
                                   class="emp-review-link-chip">
                                    <i class="bi bi-eye"></i> Visualizar PDF
                                </a>

                                <?php if ($ehAdmin): ?>
                                    <a href="<?= htmlspecialchars($docs['certidao_ambiental_path']) ?>"
                                       download
                                       class="emp-review-link-chip">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <?php if ($ehAdmin): ?>
                <div class="emp-review-divider"></div>

                <div class="emp-review-subblock">
                    <div class="emp-review-subblock-title secondary">
                        <i class="bi bi-shield-check me-1"></i> Controle administrativo
                    </div>

                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="small text-muted">
                                <i class="bi bi-calendar-check me-1"></i>
                                Última atualização:
                                <?= date('d/m/Y H:i', strtotime($docs['data_atualizacao'] ?? 'now')) ?>
                            </div>
                        </div>

                        <div class="col-md-6 text-md-end">
                            <span class="etapa9-review-status-badge <?= empty($docs['certidao_trabalhista_path']) ? 'warning' : 'success' ?>">
                                Trabalhista: <?= empty($docs['certidao_trabalhista_path']) ? 'Pendente' : 'OK' ?>
                            </span>
                            <span class="etapa9-review-status-badge <?= empty($docs['certidao_ambiental_path']) ? 'warning' : 'success' ?>">
                                Ambiental: <?= empty($docs['certidao_ambiental_path']) ? 'Pendente' : 'OK' ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>