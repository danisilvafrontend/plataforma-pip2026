<!-- Bloco 02 - Fundadores e Cofundadores -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-people-fill me-1"></i> Fundadores e Cofundadores (Etapa 2)</strong>
            <?php 
            $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
            $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;
            
            if (!$ehAdmin && !$somenteLeitura): 
            ?>
                <a href="/negocios/editar_etapa2.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($fundador_principal): ?>
            <h6 class="mb-3 text-primary"><i class="bi bi-person-fill-star me-1"></i> Fundador Principal</h6>
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>Nome Completo:</strong> <?= htmlspecialchars(($fundador_principal['nome'] ?? '') . ' ' . ($fundador_principal['sobrenome'] ?? '')) ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <p><strong>CPF:</strong> <?= formatCPF($fundador_principal['cpf'] ?? '') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($fundador_principal['email'] ?? '') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <p><strong>Celular:</strong> <?= formatPhone($fundador_principal['celular'] ?? '') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Data Nasc.:</strong> <?= formatDateBR($fundador_principal['data_nascimento'] ?? '') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <p><strong>Gênero:</strong> <?= htmlspecialchars($fundador_principal['genero'] ?? '') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <p><strong>Formação:</strong> <?= htmlspecialchars($fundador_principal['formacao'] ?? '') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <p><strong>Etnia:</strong> <?= htmlspecialchars($fundador_principal['etnia'] ?? '') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <?php if (!empty($fundador_principal['endereco_tipo']) && $fundador_principal['endereco_tipo'] === 'residencial'): ?>
                    <p><strong>Endereço Residencial:</strong> <?= htmlspecialchars($fundador_principal['rua'] ?? '') ?>, <?= $fundador_principal['numero'] ?? '' ?> - <?= $fundador_principal['cep'] ?? '' ?> / <?= $fundador_principal['municipio'] ?? '' ?> / <?= $fundador_principal['estado'] ?? '' ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Fundador Principal:</strong> Não informado (empreendedor é o fundador principal)
            </div>
            <?php endif; ?>

            <?php if (!empty($cofundadores)): ?>
            <h6 class="mb-3 text-secondary"><i class="bi bi-people me-1"></i> Cofundadores (<?= count($cofundadores) ?>)</h6>
            <?php foreach ($cofundadores as $i => $cof): ?>
            <div class="border-bottom pb-3 mb-3">
                <h6 class="text-muted"><i class="bi bi-person-plus me-1"></i> Cofundador <?= $i+1 ?></h6>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nome:</strong> <?= htmlspecialchars(($cof['nome'] ?? '') . ' ' . ($cof['sobrenome'] ?? '')) ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                        <p><strong>CPF:</strong> <?= formatCPF($cof['cpf'] ?? '') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($cof['email'] ?? '') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Celular:</strong> <?= formatPhone($cof['celular'] ?? '') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="alert alert-info mt-3">
                <i class="bi bi-info-circle-fill me-2"></i> <strong>Cofundadores:</strong> Não cadastrados (opcional)
            </div>
            <?php endif; ?>
        </div>
    </div>