<?php if (!empty($negociosDestaque)): ?>
<section class="py-5">
    <div class="container">
        <!-- Grid de negócios -->
        <div class="row">
            <?php foreach ($negociosDestaque as $n): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($n['logo_negocio'])): ?>
                            <img src="<?= htmlspecialchars($n['logo_negocio']) ?>" 
                                 class="card-logo mt-3" 
                                 alt="Logo do negócio">
                        <?php else: ?>
                            <div class="text-center text-muted p-3">
                                <i class="bi bi-image fs-1"></i>
                                <p class="small mb-0">Sem logo</p>
                            </div>
                        <?php endif; ?>

                        <div class="card-body">
                            <h5 class="card-title text-center">
                                <?= htmlspecialchars($n['nome_fantasia']) ?>
                            </h5>
                            <p class="card-text text-center">
                                <span class="badge text-bg-primary">
                                    <?= htmlspecialchars($n['categoria']) ?>
                                </span><br>
                                <?= htmlspecialchars($n['municipio']) ?>/<?= htmlspecialchars($n['estado']) ?>
                            </p>

                            <div class="d-flex justify-content-center mb-2">
                                <?php if (!empty($n['icone_url'])): ?>
                                    <img src="<?= htmlspecialchars($n['icone_url']) ?>" 
                                         alt="ODS" style="max-height:50px;">
                                <?php endif; ?>
                            </div>

                            <p class="card-frase">
                                <?= htmlspecialchars($n['frase_negocio'] ?? 'Negócio de impacto social e ambiental.') ?>
                            </p>

                            <div class="d-grid">
                                <a href="/negocio.php?id=<?= $n['id'] ?>" 
                                   class="btn btn-outline-primary mt-2">
                                    Ver negócio
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>