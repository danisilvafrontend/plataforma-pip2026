<!-- Bloco 04 - Conexão com os ODS (SÓ IMAGENS) -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-universal-access-circle me-1"></i> Conexão com ODS (Etapa 4) <i class="bi bi-eye text-secondary me-1"></i></strong>
            <?php 
            $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
            $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;
            
            if (!$ehAdmin && !$somenteLeitura): 
            ?>
                <a href="/negocios/editar_etapa4.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-12">
                    <p class="h6 mb-3"><strong><i class="bi bi-star-fill text-warning me-1"></i>ODS Prioritária:</strong></p>
                    <?php if ($ods_prioritaria && $ods_prioritaria['icone_url']): ?>
                    <div class="text-center">
                        <img src="<?= htmlspecialchars($ods_prioritaria['icone_url']) ?>" 
                            alt="ODS Prioritária" 
                            class="img-fluid shadow rounded" 
                            style="max-width: 120px; max-height: 120px;"
                            title="ODS Prioritária">
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-3"></i>Nenhuma ODS prioritária
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($ods_relacionadas)): ?>
            <h6 class="mb-3"><i class="bi bi-link-45deg me-1"></i>ODS Relacionadas (<?= count($ods_relacionadas) ?>)</h6>
            <div class="row g-3 justify-content-center">
                <?php foreach ($ods_relacionadas as $idx => $ods): ?>
                <?php if ($ods['icone_url']): ?>
                <div class="col-auto">
                    <img src="<?= htmlspecialchars($ods['icone_url']) ?>" 
                        alt="ODS <?= $idx+1 ?>" 
                        class="img-fluid shadow rounded" 
                        style="width: 80px; height: 80px;"
                        title="ODS Relacionada">
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle-fill me-2 fs-3"></i>Sem ODS relacionadas
            </div>
            <?php endif; ?>
        </div>
    </div>