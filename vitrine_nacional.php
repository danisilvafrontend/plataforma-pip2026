<?php
session_start();

// Conexão com banco
$config = require __DIR__ . '/app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Base da query
$sql = "
    SELECT n.id, n.nome_fantasia, n.categoria, n.municipio, n.estado,
       a.frase_negocio, a.logo_negocio, a.imagem_destaque,
       o.icone_url,
       e.nome AS eixo_tematico_nome
    FROM negocios n
    LEFT JOIN negocio_apresentacao a ON a.negocio_id = n.id
    LEFT JOIN ods o ON o.id = n.ods_prioritaria_id
    LEFT JOIN eixos_tematicos e ON e.id = n.eixo_principal_id
    WHERE n.publicado_vitrine = 1
";
$params = [];


// Aplica filtros se existirem
if (!empty($_GET['categoria'])) {
    $sql .= " AND n.categoria = :categoria";
    $params[':categoria'] = $_GET['categoria'];
}
if (!empty($_GET['estado'])) {
    $sql .= " AND n.estado = :estado";
    $params[':estado'] = $_GET['estado'];
}
if (!empty($_GET['municipio'])) {
    $sql .= " AND n.municipio = :municipio";
    $params[':municipio'] = $_GET['municipio'];
}
if (!empty($_GET['eixo'])) {
    $sql .= " AND n.eixo_principal_id = :eixo";
    $params[':eixo'] = $_GET['eixo'];
}
if (!empty($_GET['ods'])) {
    $sql .= " AND n.ods_prioritaria_id = :ods";
    $params[':ods'] = $_GET['ods'];
}

$sql .= " ORDER BY n.nome_fantasia";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$negocios = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Categorias
$categorias = $pdo->query("
    SELECT DISTINCT categoria 
    FROM negocios 
    WHERE publicado_vitrine = 1 
    ORDER BY categoria
")->fetchAll(PDO::FETCH_COLUMN);

// Estados
$estados = $pdo->query("
    SELECT DISTINCT estado 
    FROM negocios 
    WHERE publicado_vitrine = 1 
    ORDER BY estado
")->fetchAll(PDO::FETCH_COLUMN);

// Municípios
$municipios = $pdo->query("
    SELECT DISTINCT municipio 
    FROM negocios 
    WHERE publicado_vitrine = 1 
    ORDER BY municipio
")->fetchAll(PDO::FETCH_COLUMN);

// ODS
$ods = $pdo->query("
    SELECT DISTINCT o.id, o.nome, o.icone_url
    FROM negocios n
    INNER JOIN ods o ON o.id = n.ods_prioritaria_id
    WHERE n.publicado_vitrine = 1
    ORDER BY o.id
")->fetchAll(PDO::FETCH_ASSOC);

// Eixos Temáticos
$eixos = $pdo->query("
    SELECT DISTINCT et.id, et.nome
    FROM negocios n
    INNER JOIN eixos_tematicos et ON et.id = n.eixo_principal_id
    WHERE n.publicado_vitrine = 1
    ORDER BY et.nome
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/app/views/public/header_public.php'; ?>

<div class="container vitrine-nacional-page">
    <div class="vitrine-nacional-hero mb-4">
        <div class="vitrine-nacional-hero-content">
            <span class="vitrine-kicker">Ecossistema</span>
            <h1 class="vitrine-title mb-2">Vitrine Nacional</h1>
            <p class="vitrine-subtitle mb-0">
                Conheça negócios de impacto publicados na vitrine, explore por filtros e descubra iniciativas em diferentes territórios, eixos e ODS.
            </p>
        </div>
    </div>

    <div class="vitrine-toolbar mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <span class="badge text-bg-light px-3 py-2 rounded-pill border">
                    <?= count($parceiros ?? $negocios ?? []) ?> resultado(s)
                </span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button
                    class="btn btn-outline-primary vitrine-filtros-toggle"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#painelFiltros"
                    aria-expanded="false"
                    aria-controls="painelFiltros">
                    <i class="bi bi-sliders me-2"></i> Filtros
                </button>

                <a href="vitrine_nacional.php"  class="btn btn-outline-secondary">Limpar</a>
            </div>
        </div>

        <?php if (
            !empty($_GET['ods']) ||
            !empty($_GET['eixo']) ||
            !empty($_GET['categoria']) ||
            !empty($_GET['estado']) ||
            !empty($_GET['municipio']) ||
            !empty($_GET['setor']) ||
            !empty($_GET['perfil'])
        ): ?>
            <div class="vitrine-filtros-ativos-inline mt-3">
                <div class="vitrine-filtros-chips">
                    <?php if (!empty($_GET['ods'])): ?>
                        <span class="vitrine-filtro-chip">ODS: <?= htmlspecialchars($_GET['ods']) ?></span>
                    <?php endif; ?>

                    <?php if (!empty($_GET['eixo'])): ?>
                        <span class="vitrine-filtro-chip">Eixo: <?= htmlspecialchars($_GET['eixo']) ?></span>
                    <?php endif; ?>

                    <?php if (!empty($_GET['setor'])): ?>
                        <span class="vitrine-filtro-chip">Setor: <?= htmlspecialchars($_GET['setor']) ?></span>
                    <?php endif; ?>

                    <?php if (!empty($_GET['perfil'])): ?>
                        <span class="vitrine-filtro-chip">Perfil: <?= htmlspecialchars($_GET['perfil']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="collapse mb-4" id="painelFiltros">
        <form method="GET" class="vitrine-filtros-collapse">
            <div class="row g-3">
                <div class="col-md-6 col-xl-2">
                    <label for="filtro-categoria" class="vitrine-filtro-label">Categoria</label>
                    <select id="filtro-categoria" name="categoria" class="form-select vitrine-select">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($_GET['categoria'] ?? '') === $c ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label for="filtro-estado" class="vitrine-filtro-label">Estado</label>
                    <select id="filtro-estado" name="estado" class="form-select vitrine-select">
                        <option value="">Todos</option>
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= htmlspecialchars($e) ?>" <?= ($_GET['estado'] ?? '') === $e ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label for="filtro-municipio" class="vitrine-filtro-label">Município</label>
                    <select id="filtro-municipio" name="municipio" class="form-select vitrine-select">
                        <option value="">Todos</option>
                        <?php foreach ($municipios as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= ($_GET['municipio'] ?? '') === $m ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label for="filtro-eixo" class="vitrine-filtro-label">Eixo temático</label>
                    <select id="filtro-eixo" name="eixo" class="form-select vitrine-select">
                        <option value="">Todos</option>
                        <?php foreach ($eixos as $e): ?>
                            <option value="<?= htmlspecialchars($e['id']) ?>" <?= ($_GET['eixo'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label for="filtro-ods" class="vitrine-filtro-label">ODS prioritária</label>
                    <select id="filtro-ods" name="ods" class="form-select vitrine-select">
                        <option value="">Todas</option>
                        <?php foreach ($ods as $o): ?>
                            <option value="<?= htmlspecialchars($o['id']) ?>" <?= ($_GET['ods'] ?? '') == $o['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($o['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-12 col-xl-2">
                    <label class="vitrine-filtro-label d-none d-xl-block">&nbsp;</label>
                    <div class="vitrine-filtro-acoes">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        <a href="vitrine_nacional.php" class="btn btn-outline-secondary w-100">Limpar</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php if (!empty($negocios)): ?>
        <div class="row g-4">
            <?php foreach ($negocios as $n): ?>
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
                            <a href="/negocio.php?id=<?= $n['id'] ?>#apoiar" class="btn btn-primary">Apoiar</a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="vitrine-empty">
            <h3>Nenhum negócio encontrado</h3>
            <p class="mb-0">Tente ajustar ou limpar os filtros para visualizar outros negócios publicados na vitrine.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>