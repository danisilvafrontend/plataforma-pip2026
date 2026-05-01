<?php
// /app/views/public/grid_vitrine.php
// Incluído por outras páginas (index, etc.). Requer $pdo já definido no escopo pai.
if (!isset($pdo)) {
    throw new RuntimeException('grid_vitrine.php requer que $pdo esteja definido pela página que faz o include.');
}

$stmtGrid = $pdo->query("
    SELECT
        n.id,
        n.nome_fantasia,
        n.categoria,
        n.municipio,
        n.estado,
        a.frase_negocio,
        a.logo_negocio,
        a.imagem_destaque,
        o.icone_url,
        e.nome AS eixo_tematico_nome
    FROM negocios n
    LEFT JOIN negocio_apresentacao a ON a.negocio_id = n.id
    LEFT JOIN ods o ON o.id = n.ods_prioritaria_id
    LEFT JOIN eixos_tematicos e ON e.id = n.eixo_principal_id
    WHERE n.publicado_vitrine = 1
    ORDER BY RAND()
    LIMIT 6
");
$negociosGrid = $stmtGrid->fetchAll();
?>

<div class="row g-4">
    <?php foreach ($negociosGrid as $n):
        $cores  = ['Ideação'=>'#f59e0b','Operação'=>'#3b82f6','Tração/Escala'=>'#16a34a','Dinamizador'=>'#9333ea'];
        $corCat = $cores[$n['categoria']] ?? '#1E3425';
        $local  = trim(($n['municipio'] ?? '') . ' / ' . ($n['estado'] ?? ''), ' /');
    ?>
        <div class="col-12 col-md-6 col-xl-4">
            <article class="vitrine-card h-100">

                <a href="/negocio.php?id=<?= (int)$n['id'] ?>" class="vitrine-card-link-area">

                    <div class="vitrine-card-media <?= empty($n['imagem_destaque']) ? 'sem-capa' : '' ?>">
                        <?php if (!empty($n['imagem_destaque'])): ?>
                            <img
                                src="<?= htmlspecialchars($n['imagem_destaque']) ?>"
                                alt="<?= htmlspecialchars($n['nome_fantasia']) ?>"
                                class="vitrine-card-cover">
                        <?php elseif (!empty($n['logo_negocio'])): ?>
                            <div class="vitrine-card-logo-wrap">
                                <img
                                    src="<?= htmlspecialchars($n['logo_negocio']) ?>"
                                    alt="<?= htmlspecialchars($n['nome_fantasia']) ?>"
                                    class="vitrine-card-logo">
                            </div>
                        <?php else: ?>
                            <div class="vitrine-card-fallback">
                                <span><?= htmlspecialchars(mb_strtoupper(mb_substr($n['nome_fantasia'], 0, 1))) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($n['categoria'])): ?>
                            <span class="vitrine-card-categoria"
                                  style="--categoria-cor:<?= $corCat ?>;">
                                <?= htmlspecialchars($n['categoria']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="vitrine-card-body">
                        <div class="vitrine-card-top">
                            <h3 class="vitrine-card-title"><?= htmlspecialchars($n['nome_fantasia']) ?></h3>
                            <?php if ($local): ?>
                                <p class="vitrine-card-local">
                                    <i class="bi bi-geo-alt"></i>
                                    <?= htmlspecialchars($local) ?>
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
                                    <img src="<?= htmlspecialchars($n['icone_url']) ?>" alt="ODS">
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
                    <a href="/negocio.php?id=<?= (int)$n['id'] ?>" class="btn btn-outline-primary w-100">
                        Conhecer negócio
                    </a>
                </div>

            </article>
        </div>
    <?php endforeach; ?>
</div>