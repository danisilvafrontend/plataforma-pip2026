<?php if (!empty($negociosDestaque)): ?>
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($negociosDestaque as $n): ?>
                <?php
                    $categoria = trim($n['categoria'] ?? '');

                    $cores_categoria = [
                        'Ideação' => '#f59e0b',
                        'Operação' => '#3b82f6',
                        'Tração/Escala' => '#16a34a',
                        'Dinamizador' => '#9333ea'
                    ];

                    $cor_categoria = $cores_categoria[$categoria] ?? '#1E3425';

                    $temCapa = !empty($n['imagem_destaque']);
                    $temLogo = !empty($n['logo_negocio']);
                ?>

                <div class="col-md-6 col-xl-4">
                    <article class="vitrine-card h-100">
                        <a href="/negocio.php?id=<?= $n['id'] ?>" class="vitrine-card-link-area">
                            <div class="vitrine-card-media <?= !$temCapa ? 'sem-capa' : '' ?>">
                                <?php if ($temCapa): ?>
                                    <img
                                        src="<?= htmlspecialchars($n['imagem_destaque']) ?>"
                                        alt="Imagem de destaque de <?= htmlspecialchars($n['nome_fantasia']) ?>"
                                        class="vitrine-card-cover"
                                    >
                                <?php elseif ($temLogo): ?>
                                    <div class="vitrine-card-logo-wrap">
                                        <img
                                            src="<?= htmlspecialchars($n['logo_negocio']) ?>"
                                            alt="Logo de <?= htmlspecialchars($n['nome_fantasia']) ?>"
                                            class="vitrine-card-logo"
                                        >
                                    </div>
                                <?php else: ?>
                                    <div class="vitrine-card-fallback">
                                        <span><?= htmlspecialchars(mb_strtoupper(mb_substr($n['nome_fantasia'], 0, 1))) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($categoria)): ?>
                                    <span class="vitrine-card-categoria" style="--categoria-cor: <?= htmlspecialchars($cor_categoria) ?>;">
                                        <?= htmlspecialchars($categoria) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="vitrine-card-body">
                                <div class="vitrine-card-top">
                                    <h3 class="vitrine-card-title"><?= htmlspecialchars($n['nome_fantasia']) ?></h3>

                                    <?php if (!empty($n['municipio']) || !empty($n['estado'])): ?>
                                        <p class="vitrine-card-local">
                                            <i class="bi bi-geo-alt"></i>
                                            <?= htmlspecialchars(trim(($n['municipio'] ?? '') . ' / ' . ($n['estado'] ?? ''), ' /')) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="vitrine-card-meta">
                                    <?php if (!empty($n['eixo_tematico_nome'])): ?>
                                        <span class="vitrine-chip vitrine-chip-eixo">
                                            <?= htmlspecialchars($n['eixo_tematico_nome']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($n['icone_url'])): ?>
                                        <span class="vitrine-ods">
                                            <img src="<?= htmlspecialchars($n['icone_url']) ?>" alt="ODS prioritária">
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($n['frase_negocio'])): ?>
                                    <blockquote class="vitrine-card-frase">
                                        <?= htmlspecialchars($n['frase_negocio']) ?>
                                    </blockquote>
                                <?php endif; ?>
                            </div>
                        </a>

                        <div class="vitrine-card-actions">
                            <a href="/negocio.php?id=<?= $n['id'] ?>" class="btn btn-outline-primary">Ver negócio</a>
                            <a href="/negocio.php?id=<?= $n['id'] ?>#apoiar" class="btn btn-outline-secondary">Apoiar</a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>