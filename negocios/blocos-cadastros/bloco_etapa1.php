<!-- Bloco 01 - Dados do Negócio -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-building me-1"></i> Dados do Negócio (Etapa 1)</strong>
            <?php 
            $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
            $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;
            
            if (!$ehAdmin && !$somenteLeitura): 
            ?>
                <a href="/negocios/editar_etapa1.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nome Fantasia:</strong> <?= htmlspecialchars($negocio['nome_fantasia'] ?? 'Não informado') ?> <i class="bi bi-eye text-secondary me-1"></i></p>
                    <p><strong>Razão Social:</strong> <?= htmlspecialchars($negocio['razao_social'] ?? 'Não informado') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <p><strong>CNPJ/CPF:</strong> <?= formatCNPJ($negocio['cnpj_cpf'] ?? '') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <p><strong>E-mail Comercial:</strong> <?= htmlspecialchars($negocio['email_comercial'] ?? 'Não informado') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <p><strong>Telefone Comercial:</strong> <?= htmlspecialchars($negocio['telefone_comercial'] ?? 'Não informado') ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <p><strong>Data Fundação:</strong> <?= formatDateBR($negocio['data_fundacao'] ?? '') ?> <i class="bi bi-eye text-secondary me-1"></i></p>
                    <p><strong>Endereço:</strong> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> <?= htmlspecialchars($negocio['rua'] ?? '') ?>, <?= htmlspecialchars($negocio['numero'] ?? '') ?> <?= !empty($negocio['complemento']) ? ' - ' . htmlspecialchars($negocio['complemento']) : '' ?> - <?= htmlspecialchars($negocio['cep'] ?? '') ?> <br>
                     <i class="bi bi-eye text-secondary me-1"></i> <?= htmlspecialchars($negocio['municipio'] ?? '') ?> / <?= htmlspecialchars($negocio['estado'] ?? '') ?></p>
                </div>
                <div class="col-md-6">                        
                    <p><strong>Categoria:</strong> <?= htmlspecialchars($negocio['categoria'] ?? 'Não informado') ?> <i class="bi bi-eye text-secondary me-1"></i></p>
                    <p><strong>Setor:</strong> <?= htmlspecialchars($negocio['setor'] ?? '') ?>
                        <?= !empty($negocio['setor_detalhe']) ? ' - ' . htmlspecialchars($negocio['setor_detalhe']) : '' ?> <i class="bi bi-eye text-secondary me-1"></i></p>                    
                    <p><strong>Formato Legal:</strong> <?= htmlspecialchars($negocio['formato_legal'] ?? '') ?>
                        <?= !empty($negocio['formato_outros']) ? ' (' . htmlspecialchars($negocio['formato_outros']) . ')' : '' ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    
                </div>
            </div>
            <?php if (!empty($negocio['site']) || !empty($negocio['linkedin']) || !empty($negocio['instagram'])): ?>
            <hr>
            <h6>Redes e Site:  <i class="bi bi-eye text-secondary me-1"></i></h6>
            <div class="row">
                <?php if ($negocio['site']): ?><div class="col-md-2"><a href="<?= htmlspecialchars($negocio['site']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-globe"></i> Site</a></div><?php endif; ?>
                <?php if ($negocio['linkedin']): ?><div class="col-md-2"><a href="<?= htmlspecialchars($negocio['linkedin']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-linkedin"></i> LinkedIn</a></div><?php endif; ?>
                <?php if ($negocio['instagram']): ?><div class="col-md-2"><a href="<?= htmlspecialchars($negocio['instagram']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-instagram"></i> Instagram</a></div><?php endif; ?>
                <?php if ($negocio['facebook']): ?><div class="col-md-2"><a href="<?= htmlspecialchars($negocio['facebook']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-facebook"></i> Facebook</a></div><?php endif; ?>
                <?php if ($negocio['tiktok']): ?><div class="col-md-2"><a href="<?= htmlspecialchars($negocio['tiktok']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-tiktok"></i> TikTok</a></div><?php endif; ?>
                <?php if ($negocio['youtube']): ?><div class="col-md-2"><a href="<?= htmlspecialchars($negocio['youtube']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-youtube"></i> YouTube</a></div><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>