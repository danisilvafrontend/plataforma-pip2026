<!-- Bloco 03 - Eixo Temático -->
<div class="emp-review-card mb-4">
    <div class="emp-review-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="emp-review-card-title">
            <i class="bi bi-tag-fill me-1"></i> Eixo Temático
            <span class="emp-review-step">(Etapa 3)</span>
            <i class="bi bi-eye text-secondary ms-1"></i>
        </div>

        <?php
        $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
        $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;

        if (!$ehAdmin && !$somenteLeitura):
        ?>
            <a href="/negocios/editar_etapa3.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn-emp-outline btn-sm">
                Editar
            </a>
        <?php endif; ?>
    </div>

    <div class="emp-review-card-body">

        <?php if ($eixo_principal): ?>
            <div class="emp-review-subblock mb-4">
                <div class="emp-review-subblock-title principal">
                    <i class="bi bi-arrow-right-circle me-1"></i> Eixo Principal
                </div>

                <div class="emp-review-eixo-hero">
                    <?php
                    $iconeEixo = $eixo_principal['icone_url'] ?? '';

                    if ($iconeEixo !== '' && strpos($iconeEixo, '/assets/') !== 0) {
                        $iconeEixo = '/assets/images/icons/' . ltrim($iconeEixo, '/');
                    }
                    ?>

                    <?php if (!empty($iconeEixo)): ?>
                        <div class="emp-review-eixo-icon">
                            <img
                                src="<?= htmlspecialchars($iconeEixo) ?>"
                                alt="<?= htmlspecialchars($eixo_principal['eixo_nome'] ?? 'Eixo temático') ?>"
                            >
                        </div>
                    <?php endif; ?>

                    <div class="emp-review-eixo-content">
                        <span class="emp-review-highlight-badge">
                            <?= htmlspecialchars($eixo_principal['eixo_nome'] ?? 'Não informado') ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mb-4">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Eixo Principal:</strong> Não selecionado
            </div>
        <?php endif; ?>

        <?php if (!empty($subareas_lista)): ?>
            <div class="emp-review-subblock">
                <div class="emp-review-subblock-title secondary">
                    <i class="bi bi-list-ul me-1"></i> Subáreas Selecionadas
                    <span class="emp-review-count">(<?= count($subareas_lista) ?>)</span>
                </div>

                <div class="emp-review-tags-grid">
                    <?php foreach ($subareas_lista as $sub): ?>
                        <div class="emp-review-tag-item">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <span><?= htmlspecialchars($sub['nome']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Subáreas:</strong> Nenhuma selecionada
            </div>
        <?php endif; ?>

    </div>
</div>