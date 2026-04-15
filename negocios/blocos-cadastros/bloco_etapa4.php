<!-- Bloco 04 - Conexão com os ODS -->
<div class="emp-review-card mb-4">
    <div class="emp-review-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="emp-review-card-title">
            <i class="bi bi-universal-access-circle me-1"></i> Conexão com ODS
            <span class="emp-review-step">(Etapa 4)</span>
            <i class="bi bi-eye text-secondary ms-1"></i>
        </div>

        <?php
        $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
        $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;

        if (!$ehAdmin && !$somenteLeitura):
        ?>
            <a href="/negocios/editar_etapa4.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn-emp-outline btn-sm">
                Editar
            </a>
        <?php endif; ?>
    </div>

    <div class="emp-review-card-body">

        <div class="emp-review-subblock mb-4">
            <div class="emp-review-subblock-title principal">
                <i class="bi bi-star-fill me-1"></i> ODS Prioritária
            </div>

            <?php if ($ods_prioritaria && !empty($ods_prioritaria['icone_url'])): ?>
                <div class="emp-review-ods-principal">
                    <div class="emp-review-ods-principal-card">
                        <img
                            src="<?= htmlspecialchars($ods_prioritaria['icone_url']) ?>"
                            alt="ODS Prioritária"
                            class="emp-review-ods-principal-img"
                            title="ODS Prioritária"
                        >
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Nenhuma ODS prioritária
                </div>
            <?php endif; ?>
        </div>

        <div class="emp-review-subblock">
            <div class="emp-review-subblock-title secondary">
                <i class="bi bi-link-45deg me-1"></i> ODS Relacionadas
                <span class="emp-review-count">(<?= !empty($ods_relacionadas) ? count($ods_relacionadas) : 0 ?>)</span>
            </div>

            <?php if (!empty($ods_relacionadas)): ?>
                <div class="emp-review-ods-grid">
                    <?php foreach ($ods_relacionadas as $idx => $ods): ?>
                        <?php if (!empty($ods['icone_url'])): ?>
                            <div class="emp-review-ods-item">
                                <div class="emp-review-ods-thumb">
                                    <img
                                        src="<?= htmlspecialchars($ods['icone_url']) ?>"
                                        alt="ODS Relacionada <?= $idx + 1 ?>"
                                        class="emp-review-ods-img"
                                        title="ODS Relacionada"
                                    >
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center mb-0">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Sem ODS relacionadas
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>