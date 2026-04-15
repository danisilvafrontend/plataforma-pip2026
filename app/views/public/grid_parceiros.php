<?php if (!empty($parceirosGrid)): ?>
    <div class="parceiros-grid">
        <?php foreach ($parceirosGrid as $parceiro): ?>
            <?php $temPerfilPublico = !empty($parceiro['perfil_publicado']); ?>

            <?php if ($temPerfilPublico): ?>
                <a href="/perfil_parceiro.php?id=<?= (int)$parceiro['id'] ?>" class="parceiro-card parceiro-card-link">
            <?php else: ?>
                <div class="parceiro-card">
            <?php endif; ?>

                <div class="parceiro-card-logo-wrap">
                    <?php if (!empty($parceiro['logo_url'])): ?>
                        <img
                            src="<?= htmlspecialchars($parceiro['logo_url']) ?>"
                            alt="<?= htmlspecialchars($parceiro['nome_fantasia']) ?>"
                            class="parceiro-card-logo"
                            loading="lazy"
                        >
                    <?php else: ?>
                        <div class="parceiro-card-logo-fallback">
                            <i class="bi bi-building"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <h3 class="parceiro-card-title">
                    <?= htmlspecialchars($parceiro['nome_fantasia']) ?>
                </h3>

                <?php if ($temPerfilPublico): ?>
                    <span class="parceiro-card-cta">
                        Ver perfil <i class="bi bi-arrow-right-short"></i>
                    </span>
                <?php endif; ?>

            <?php if ($temPerfilPublico): ?>
                </a>
            <?php else: ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="vitrine-empty text-center">
        <h3>Nenhum parceiro disponível no momento</h3>
        <p class="mb-0">Em breve novos parceiros serão exibidos aqui.</p>
    </div>
<?php endif; ?>