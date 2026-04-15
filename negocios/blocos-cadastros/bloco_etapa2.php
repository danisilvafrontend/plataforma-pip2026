<!-- Bloco 02 - Fundadores e Cofundadores -->
<div class="emp-review-card mb-4">
    <div class="emp-review-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="emp-review-card-title">
            <i class="bi bi-people-fill me-1"></i> Fundadores e Cofundadores <span class="emp-review-step">(Etapa 2)</span>
        </div>

        <?php
        $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
        $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;

        if (!$ehAdmin && !$somenteLeitura):
        ?>
            <a href="/negocios/editar_etapa2.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn-emp-outline btn-sm">
                Editar
            </a>
        <?php endif; ?>
    </div>

    <div class="emp-review-card-body">

        <?php if ($fundador_principal): ?>
            <div class="emp-review-subblock mb-4">
                <div class="emp-review-subblock-title principal">
                    <i class="bi bi-person-fill-star me-1"></i> Fundador Principal
                </div>

                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="emp-review-group">
                            <div class="emp-review-item">
                                <span class="emp-review-label">Nome Completo</span>
                                <div class="emp-review-value">
                                    <?= htmlspecialchars(($fundador_principal['nome'] ?? '') . ' ' . ($fundador_principal['sobrenome'] ?? '')) ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>

                            <div class="emp-review-item">
                                <span class="emp-review-label">CPF</span>
                                <div class="emp-review-value">
                                    <?= formatCPF($fundador_principal['cpf'] ?? '') ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>

                            <div class="emp-review-item">
                                <span class="emp-review-label">Email</span>
                                <div class="emp-review-value">
                                    <?= htmlspecialchars($fundador_principal['email'] ?? '') ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>

                            <div class="emp-review-item">
                                <span class="emp-review-label">Celular</span>
                                <div class="emp-review-value">
                                    <?= formatPhone($fundador_principal['celular'] ?? '') ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="emp-review-group">
                            <div class="emp-review-item">
                                <span class="emp-review-label">Data de Nascimento</span>
                                <div class="emp-review-value">
                                    <?= formatDateBR($fundador_principal['data_nascimento'] ?? '') ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>

                            <div class="emp-review-item">
                                <span class="emp-review-label">Gênero</span>
                                <div class="emp-review-value">
                                    <?= htmlspecialchars($fundador_principal['genero'] ?? '') ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>

                            <div class="emp-review-item">
                                <span class="emp-review-label">Formação</span>
                                <div class="emp-review-value">
                                    <?= htmlspecialchars($fundador_principal['formacao'] ?? '') ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>

                            <div class="emp-review-item">
                                <span class="emp-review-label">Etnia</span>
                                <div class="emp-review-value">
                                    <?= htmlspecialchars($fundador_principal['etnia'] ?? '') ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>

                            <?php if (!empty($fundador_principal['endereco_tipo']) && $fundador_principal['endereco_tipo'] === 'residencial'): ?>
                                <div class="emp-review-item">
                                    <span class="emp-review-label">Endereço Residencial</span>
                                    <div class="emp-review-value">
                                        <?= htmlspecialchars($fundador_principal['rua'] ?? '') ?>,
                                        <?= htmlspecialchars($fundador_principal['numero'] ?? '') ?>
                                        - <?= htmlspecialchars($fundador_principal['cep'] ?? '') ?>
                                        / <?= htmlspecialchars($fundador_principal['municipio'] ?? '') ?>
                                        / <?= htmlspecialchars($fundador_principal['estado'] ?? '') ?>
                                        <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Fundador Principal:</strong> Não informado (empreendedor é o fundador principal)
            </div>
        <?php endif; ?>

        <?php if (!empty($cofundadores)): ?>
            <div class="emp-review-subblock">
                <div class="emp-review-subblock-title secondary">
                    <i class="bi bi-people me-1"></i> Cofundadores <span class="emp-review-count">(<?= count($cofundadores) ?>)</span>
                </div>

                <div class="emp-review-cofounders">
                    <?php foreach ($cofundadores as $i => $cof): ?>
                        <div class="emp-review-cofounder-card">
                            <div class="emp-review-cofounder-title">
                                <i class="bi bi-person-plus me-1"></i> Cofundador <?= $i + 1 ?>
                            </div>

                            <div class="row g-4">
                                <div class="col-12 col-md-6">
                                    <div class="emp-review-group">
                                        <div class="emp-review-item">
                                            <span class="emp-review-label">Nome</span>
                                            <div class="emp-review-value">
                                                <?= htmlspecialchars(($cof['nome'] ?? '') . ' ' . ($cof['sobrenome'] ?? '')) ?>
                                                <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                            </div>
                                        </div>

                                        <div class="emp-review-item">
                                            <span class="emp-review-label">CPF</span>
                                            <div class="emp-review-value">
                                                <?= formatCPF($cof['cpf'] ?? '') ?>
                                                <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                            </div>
                                        </div>

                                        <div class="emp-review-item">
                                            <span class="emp-review-label">Email</span>
                                            <div class="emp-review-value">
                                                <?= htmlspecialchars($cof['email'] ?? '') ?>
                                                <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="emp-review-group">
                                        <div class="emp-review-item">
                                            <span class="emp-review-label">Celular</span>
                                            <div class="emp-review-value">
                                                <?= formatPhone($cof['celular'] ?? '') ?>
                                                <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-3">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Cofundadores:</strong> Não cadastrados (opcional)
            </div>
        <?php endif; ?>

    </div>
</div>