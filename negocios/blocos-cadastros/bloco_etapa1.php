<!-- Bloco 01 - Dados do Negócio -->
<?php
$ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
$somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;
$ocultarCamposSensiveis = function_exists('is_juri_ou_tecnica') && is_juri_ou_tecnica();
?>
<div class="emp-review-card mb-4">
    <div class="emp-review-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="emp-review-card-title">
            <i class="bi bi-building me-1"></i> Dados do Negócio <span class="emp-review-step">(Etapa 1)</span>
        </div>

        <?php
        $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
        $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;

        if (!$ehAdmin && !$somenteLeitura):
        ?>
            <a href="/negocios/editar_etapa1.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn-emp-outline btn-sm">
                Editar
            </a>
        <?php endif; ?>
    </div>

    <div class="emp-review-card-body">
        <div class="row g-4">
            <div class="col-12 col-md-6">
                <div class="emp-review-group">
                    <div class="emp-review-item">
                        <span class="emp-review-label">Nome Fantasia</span>
                        <div class="emp-review-value">
                            <?= htmlspecialchars($negocio['nome_fantasia'] ?? 'Não informado') ?>
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>
                    </div>

                    <div class="emp-review-item">
                        <span class="emp-review-label">Razão Social</span>
                        <div class="emp-review-value">
                            <?= htmlspecialchars($negocio['razao_social'] ?? 'Não informado') ?>
                            <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                        </div>
                    </div>

                    <div class="emp-review-item">
                        <span class="emp-review-label">CNPJ/CPF</span>
                        <div class="emp-review-value">
                            <?= formatCNPJ($negocio['cnpj_cpf'] ?? '') ?>
                            <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                        </div>
                    </div>

                    <div class="emp-review-item">
                        <span class="emp-review-label">E-mail Comercial</span>
                        <div class="emp-review-value">
                            <?= htmlspecialchars($negocio['email_comercial'] ?? 'Não informado') ?>
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>
                    </div>

                    <?php if (!$ocultarCamposSensiveis): ?>
                    <div class="emp-review-item">
                        <span class="emp-review-label">Telefone Comercial</span>
                        <div class="emp-review-value">
                            <?= htmlspecialchars($negocio['telefone_comercial'] ?? 'Não informado') ?>
                            <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="emp-review-item">
                        <span class="emp-review-label">Data Fundação</span>
                        <div class="emp-review-value">
                            <?= formatDateBR($negocio['data_fundacao'] ?? '') ?>
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="emp-review-group">
                    <div class="emp-review-item">
                        <span class="emp-review-label">Categoria</span>
                        <div class="emp-review-value">
                            <?= htmlspecialchars($negocio['categoria'] ?? 'Não informado') ?>
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>
                    </div>

                    <div class="emp-review-item">
                        <span class="emp-review-label">Setor</span>
                        <div class="emp-review-value">
                            <?= htmlspecialchars($negocio['setor'] ?? '') ?>
                            <?= !empty($negocio['setor_detalhe']) ? ' - ' . htmlspecialchars($negocio['setor_detalhe']) : '' ?>
                            <i class="bi bi-eye text-secondary ms-1"></i>
                        </div>
                    </div>

                    <div class="emp-review-item">
                        <span class="emp-review-label">Formato Legal</span>
                        <div class="emp-review-value">
                            <?= htmlspecialchars($negocio['formato_legal'] ?? '') ?>
                            <?= !empty($negocio['formato_outros']) ? ' (' . htmlspecialchars($negocio['formato_outros']) . ')' : '' ?>
                            <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                        </div>
                    </div>

                    <div class="emp-review-item">
                        <span class="emp-review-label">Endereço</span>
                        <div class="emp-review-value">
                            <?php if (!$ocultarCamposSensiveis): ?>
                            <div>
                                <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                                <?= htmlspecialchars($negocio['rua'] ?? '') ?>, <?= htmlspecialchars($negocio['numero'] ?? '') ?>
                                <?= !empty($negocio['complemento']) ? ' - ' . htmlspecialchars($negocio['complemento']) : '' ?>
                                - <?= htmlspecialchars($negocio['cep'] ?? '') ?>
                            </div>
                            <?php endif; ?>
                            <div class="mt-1">
                                <i class="bi bi-eye text-secondary me-1"></i>
                                <?= htmlspecialchars($negocio['municipio'] ?? '') ?> / <?= htmlspecialchars($negocio['estado'] ?? '') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (
            !empty($negocio['site']) ||
            !empty($negocio['linkedin']) ||
            !empty($negocio['instagram']) ||
            !empty($negocio['facebook']) ||
            !empty($negocio['tiktok']) ||
            !empty($negocio['youtube'])
        ): ?>
            <div class="emp-review-divider"></div>

            <div class="emp-review-subtitle">
                Redes e Site <i class="bi bi-eye text-secondary ms-1"></i>
            </div>

            <div class="emp-review-links">
                <?php if (!empty($negocio['site'])): ?>
                    <a href="<?= htmlspecialchars($negocio['site']) ?>" target="_blank" class="emp-review-link-chip">
                        <i class="bi bi-globe"></i> Site
                    </a>
                <?php endif; ?>

                <?php if (!empty($negocio['linkedin'])): ?>
                    <a href="<?= htmlspecialchars($negocio['linkedin']) ?>" target="_blank" class="emp-review-link-chip">
                        <i class="bi bi-linkedin"></i> LinkedIn
                    </a>
                <?php endif; ?>

                <?php if (!empty($negocio['instagram'])): ?>
                    <a href="<?= htmlspecialchars($negocio['instagram']) ?>" target="_blank" class="emp-review-link-chip">
                        <i class="bi bi-instagram"></i> Instagram
                    </a>
                <?php endif; ?>

                <?php if (!empty($negocio['facebook'])): ?>
                    <a href="<?= htmlspecialchars($negocio['facebook']) ?>" target="_blank" class="emp-review-link-chip">
                        <i class="bi bi-facebook"></i> Facebook
                    </a>
                <?php endif; ?>

                <?php if (!empty($negocio['tiktok'])): ?>
                    <a href="<?= htmlspecialchars($negocio['tiktok']) ?>" target="_blank" class="emp-review-link-chip">
                        <i class="bi bi-tiktok"></i> TikTok
                    </a>
                <?php endif; ?>

                <?php if (!empty($negocio['youtube'])): ?>
                    <a href="<?= htmlspecialchars($negocio['youtube']) ?>" target="_blank" class="emp-review-link-chip">
                        <i class="bi bi-youtube"></i> YouTube
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>