 <!-- Bloco 03 - Eixo Temático -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-tag-fill me-1"></i> Eixo Temático (Etapa 3) <i class="bi bi-eye text-secondary me-1"></i></strong>
            <?php 
            $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
            $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;
            
            if (!$ehAdmin && !$somenteLeitura): 
            ?>
                <a href="/negocios/editar_etapa3.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($eixo_principal): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <p class="h6 mb-2"><strong><i class="bi bi-arrow-right-circle text-primary me-1"></i>Eixo Principal:</strong></p>
                    <span class="badge bg-primary fs-6"><?= htmlspecialchars($eixo_principal['eixo_nome'] ?? 'Não informado') ?></span>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i><strong>Eixo Principal:</strong> Não selecionado
            </div>
            <?php endif; ?>

            <?php if (!empty($subareas_lista)): ?>
            <h6 class="mb-3"><i class="bi bi-list-ul me-1"></i>Subáreas Selecionadas (<?= count($subareas_lista) ?>)</h6>
            <ul class="list-group list-group-flush">
                <?php foreach ($subareas_lista as $sub): ?>
                <li class="list-group-item px-0 border-0">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <?= htmlspecialchars($sub['nome']) ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i><strong>Subáreas:</strong> Nenhuma selecionada
            </div>
            <?php endif; ?>
        </div>
    </div>