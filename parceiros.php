<?php

$config = require __DIR__ . '/app/config/db.php';

$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

function jsonArrayContains(?string $jsonColumn, string $needle): bool {
    if (empty($jsonColumn)) return false;
    $arr = json_decode($jsonColumn, true);
    if (!is_array($arr)) return false;
    return in_array($needle, $arr, true);
}

$f_ods = isset($_GET['ods']) ? (int)$_GET['ods'] : 0;
$f_eixo = trim($_GET['eixo'] ?? '');
$f_setor = trim($_GET['setor'] ?? '');
$f_perfil = trim($_GET['perfil'] ?? '');

// Busca parceiros públicos
$sql = "
    SELECT 
        p.id,
        p.nome_fantasia,
        p.razao_social,
        pp.slogan,
        pp.setor_atuacao,
        pp.imagem_capa_url,
        pp.logo_url AS logo_perfil_url,
        c.logo_url AS logo_contrato_url,
        pi.eixos_interesse,
        pi.setores_interesse,
        pi.perfil_impacto
    FROM parceiros p
    INNER JOIN parceiros_perfil pp ON pp.parceiro_id = p.id
    LEFT JOIN parceiro_contrato c ON c.parceiro_id = p.id
    LEFT JOIN parceiro_interesses pi ON pi.parceiro_id = p.id
    WHERE pp.perfil_publicado = 1
";

$params = [];

if ($f_ods > 0) {
    $sql .= " AND EXISTS (
        SELECT 1
        FROM parceiro_ods po
        WHERE po.parceiro_id = p.id
          AND po.ods_id = :ods
    )";
    $params[':ods'] = $f_ods;
}

$sql .= " ORDER BY p.nome_fantasia ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$parceirosBrutos = $stmt->fetchAll();

// Filtro em PHP para campos JSON
$parceiros = array_values(array_filter($parceirosBrutos, function ($parceiro) use ($f_eixo, $f_setor, $f_perfil) {
    if ($f_eixo !== '' && !jsonArrayContains($parceiro['eixos_interesse'] ?? null, $f_eixo)) {
        return false;
    }
    if ($f_setor !== '' && !jsonArrayContains($parceiro['setores_interesse'] ?? null, $f_setor)) {
        return false;
    }
    if ($f_perfil !== '' && !jsonArrayContains($parceiro['perfil_impacto'] ?? null, $f_perfil)) {
        return false;
    }
    return true;
}));

// Filtros disponíveis
$ods = $pdo->query("
    SELECT DISTINCT o.id, o.nome, o.icone_url
    FROM parceiro_ods po
    INNER JOIN ods o ON o.id = po.ods_id
    INNER JOIN parceiros_perfil pp ON pp.parceiro_id = po.parceiro_id
    WHERE pp.perfil_publicado = 1
    ORDER BY o.id
")->fetchAll();

$stmtFiltros = $pdo->query("
    SELECT 
        pi.eixos_interesse,
        pi.setores_interesse,
        pi.perfil_impacto
    FROM parceiro_interesses pi
    INNER JOIN parceiros_perfil pp ON pp.parceiro_id = pi.parceiro_id
    WHERE pp.perfil_publicado = 1
");
$filtrosRaw = $stmtFiltros->fetchAll();

$eixos = [];
$setores = [];
$perfis = [];

foreach ($filtrosRaw as $row) {
    $arrEixos = json_decode($row['eixos_interesse'] ?? '[]', true);
    $arrSetores = json_decode($row['setores_interesse'] ?? '[]', true);
    $arrPerfis = json_decode($row['perfil_impacto'] ?? '[]', true);

    if (is_array($arrEixos)) $eixos = array_merge($eixos, $arrEixos);
    if (is_array($arrSetores)) $setores = array_merge($setores, $arrSetores);
    if (is_array($arrPerfis)) $perfis = array_merge($perfis, $arrPerfis);
}

$eixos = array_values(array_unique(array_filter(array_map('trim', $eixos))));
$setores = array_values(array_unique(array_filter(array_map('trim', $setores))));
$perfis = array_values(array_unique(array_filter(array_map('trim', $perfis))));

sort($eixos, SORT_NATURAL | SORT_FLAG_CASE);
sort($setores, SORT_NATURAL | SORT_FLAG_CASE);
sort($perfis, SORT_NATURAL | SORT_FLAG_CASE);

$pageTitle = 'Parceiros';
include __DIR__ . '/app/views/public/header_public.php';
?>


<div class="container vitrine-nacional-page">

    <div class="vitrine-nacional-hero mb-4">
        <div class="vitrine-nacional-hero-content">
            <span class="vitrine-kicker">Rede de Impacto</span>
            <h1 class="vitrine-title mb-2">Parceiros da Plataforma</h1>
            <p class="vitrine-subtitle mb-0">
                Conheça parceiros com perfil público ativo, explore conexões estratégicas e descubra organizações alinhadas a agendas de impacto.
            </p>
        </div>
    </div>

    <div class="vitrine-toolbar mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <span class="badge text-bg-light px-3 py-2 rounded-pill border">
                    <?= count($parceiros ?? []) ?> resultado(s)
                </span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button
                    class="btn btn-outline-primary vitrine-filtros-toggle"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#painelFiltrosParceiros"
                    aria-expanded="false"
                    aria-controls="painelFiltrosParceiros">
                    <i class="bi bi-sliders me-2"></i> Filtros
                </button>

                <a href="parceiros.php" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </div>

        <?php if (
            !empty($_GET['ods']) ||
            !empty($_GET['eixo']) ||
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

    <div class="collapse mb-4" id="painelFiltrosParceiros">
        <form method="GET" class="vitrine-filtros-collapse">
            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <label for="filtro-ods" class="vitrine-filtro-label">ODS</label>
                    <select id="filtro-ods" name="ods" class="form-select vitrine-select">
                        <option value="">Todas</option>
                        <?php foreach ($ods as $o): ?>
                            <option value="<?= htmlspecialchars($o['id']) ?>" <?= ($_GET['ods'] ?? '') == $o['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($o['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label for="filtro-eixo" class="vitrine-filtro-label">Eixos temáticos adicionais</label>
                    <select id="filtro-eixo" name="eixo" class="form-select vitrine-select">
                        <option value="">Todos</option>
                        <?php foreach ($eixos as $eixo): ?>
                            <option value="<?= htmlspecialchars($eixo) ?>" <?= ($_GET['eixo'] ?? '') === $eixo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($eixo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label for="filtro-setor" class="vitrine-filtro-label">Setores / Indústrias</label>
                    <select id="filtro-setor" name="setor" class="form-select vitrine-select">
                        <option value="">Todos</option>
                        <?php foreach ($setores as $setor): ?>
                            <option value="<?= htmlspecialchars($setor) ?>" <?= ($_GET['setor'] ?? '') === $setor ? 'selected' : '' ?>>
                                <?= htmlspecialchars($setor) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label for="filtro-perfil" class="vitrine-filtro-label">Perfil de Impacto Desejado</label>
                    <select id="filtro-perfil" name="perfil" class="form-select vitrine-select">
                        <option value="">Todos</option>
                        <?php foreach ($perfis as $perfil): ?>
                            <option value="<?= htmlspecialchars($perfil) ?>" <?= ($_GET['perfil'] ?? '') === $perfil ? 'selected' : '' ?>>
                                <?= htmlspecialchars($perfil) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <div class="vitrine-filtro-acoes">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="parceiros.php" class="btn btn-outline-secondary">Limpar</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php if (!empty($parceiros)): ?>
        <div class="row g-4">
            <?php foreach ($parceiros as $p): ?>
                <?php
                $nome = $p['nome_fantasia'] ?: ($p['razao_social'] ?? 'Parceiro');
                $logo = $p['logo_perfil_url'] ?: ($p['logo_contrato_url'] ?? '');
                $capa = $p['imagem_capa_url'] ?? '';
                ?>
                <div class="col-md-6 col-xl-4">
                    <article class="vitrine-card parceiro-card-publico h-100">
                        <a href="perfil_parceiro.php?id=<?= (int)$p['id'] ?>" class="text-decoration-none text-reset d-block h-100">
                            <div class="vitrine-card-media <?= empty($capa) ? 'sem-capa' : '' ?>">
                                <?php if (!empty($capa)): ?>
                                    <img
                                        src="<?= htmlspecialchars($capa) ?>"
                                        alt="Capa de <?= htmlspecialchars($nome) ?>"
                                        class="vitrine-card-cover">
                                <?php else: ?>
                                    <div class="w-100 h-100 parceiro-card-cover-placeholder"></div>
                                <?php endif; ?>

                                <div class="vitrine-card-logo-wrap">
                                    <?php if (!empty($logo)): ?>
                                        <img
                                            src="<?= htmlspecialchars($logo) ?>"
                                            alt="Logo de <?= htmlspecialchars($nome) ?>"
                                            class="vitrine-card-logo">
                                    <?php else: ?>
                                        <div class="vitrine-card-fallback">
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr($nome, 0, 1))) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="vitrine-card-body">
                                <div class="d-flex flex-column gap-2">
                                    <h3 class="vitrine-card-title mb-0">
                                        <?= htmlspecialchars($nome) ?>
                                    </h3>

                                    <?php if (!empty($p['setor_atuacao'])): ?>
                                        <p class="vitrine-card-local mb-0">
                                            <i class="bi bi-briefcase me-1"></i>
                                            <?= htmlspecialchars($p['setor_atuacao']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($p['slogan'])): ?>
                                        <p class="vitrine-card-frase">
                                            <?= htmlspecialchars($p['slogan']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>

                        <div class="vitrine-card-actions">
                            <a href="perfil_parceiro.php?id=<?= (int)$p['id'] ?>" class="btn btn-primary">
                                Ver perfil
                            </a>
                            <a href="#" class="btn btn-outline-secondary">
                                Conectar
                            </a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="vitrine-empty">
            <h3>Nenhum parceiro encontrado</h3>
            <p class="mb-0">
                Tente ajustar ou limpar os filtros para visualizar outros parceiros publicados.
            </p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>