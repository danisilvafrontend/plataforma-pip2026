<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
           a.frase_negocio, a.logo_negocio, o.icone_url,
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

<div class="container my-5">
    <h2 class="mb-4">Vitrine Nacional</h2>

     <!-- Filtros -->
    <form method="get" class="row mb-4">
        <div class="col-md-2">
            <select name="categoria" class="form-select">
                <option value="">Categoria</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= ($_GET['categoria'] ?? '') === $c ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <select name="estado" class="form-select">
                <option value="">Estado</option>
                <?php foreach ($estados as $e): ?>
                    <option value="<?= htmlspecialchars($e) ?>" <?= ($_GET['estado'] ?? '') === $e ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <select name="municipio" class="form-select">
                <option value="">Município</option>
                <?php foreach ($municipios as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= ($_GET['municipio'] ?? '') === $m ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <select name="eixo" class="form-select">
                <option value="">Eixo Temático</option>
                <?php foreach ($eixos as $e): ?>
                    <option value="<?= htmlspecialchars($e['id']) ?>" <?= ($_GET['eixo'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <select name="ods" class="form-select">
                <option value="">ODS Prioritária</option>
                <?php foreach ($ods as $o): ?>
                    <option value="<?= htmlspecialchars($o['id']) ?>" <?= ($_GET['ods'] ?? '') == $o['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($o['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 d-flex">
            <button type="submit" class="btn btn-primary me-2">Filtrar</button>
            <a href="vitrine_nacional.php" class="btn btn-secondary">Limpar</a>
        </div>
    </form>

    <!-- Grid de negócios -->
    <div class="row">
        <?php foreach ($negocios as $n): ?>
            <div class="col-md-4 mb-4"> <!-- Coluna limpa, sem overflow -->
                
                <!-- O CARD: Aqui entram as classes position-relative e overflow-hidden -->
                <div class="card d-flex justify-content-between h-100 p-3 shadow-sm position-relative overflow-hidden">
                    
                    <!-- A Faixa Diagonal (Ajustada para textos longos) -->
                    <div class="position-absolute" style="top: -5px; left: -5px; width: 130px; height: 130px; z-index: 10;">
                        <div class="text-bg-primary bg-opacity-50 text-center shadow-sm text-uppercase d-flex align-items-center justify-content-center" 
                            style="position: absolute; top: 30px; left: -40px; width: 180px; height: 30px; transform: rotate(-45deg); font-size: 0.6rem; font-weight: bold; line-height: 1;">
                            <span class="d-inline-block px-2" style="max-width: 100%; white-space: normal;">
                                <?= htmlspecialchars($n['categoria'] ?? '') ?>
                            </span>
                        </div>
                    </div>

                    <div class="row d-flex align-items-center">
                        <div class="col-md-4 mb-2 text-center">
                            <!-- A Imagem do Logotipo (removi o overflow daqui também) -->
                            <?php if (!empty($n['logo_negocio'])): ?>
                                <img src="<?= htmlspecialchars($n['logo_negocio']) ?>" 
                                    class="card-logo mt-4 mb-3 position-relative" 
                                    style="z-index: 1;"
                                    alt="Logo do negócio">
                            <?php endif; ?>
                        </div>

                        <div class="col-md-8 mb-2 text-center">
                            <h5 class="card-title text-center"><?= htmlspecialchars($n['nome_fantasia']) ?></h5>                        
                            <span class="small-muted"><?= htmlspecialchars($n['municipio']) ?>/<?= htmlspecialchars($n['estado']) ?></span> 
                            <div class="d-flex justify-content-center">                  
                                <span class="badge m-1 text-wrap text-bg-primary text-center"> <?= htmlspecialchars($n['eixo_tematico_nome'] ?? '') ?> </span>  
                            </div> 
                        </div>
                    </div>

                    <div class="row d-flex align-items-center">
                        <div class="col-md-3 mb-2">
                            <div class="d-flex justify-content-center">
                                <?php if (!empty($n['icone_url'])): ?>
                                    <img src="<?= htmlspecialchars($n['icone_url']) ?>" alt="ODS" style="max-height:50px;">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-9 mb-2">
                            <blockquote class="apresentacao-quote fst-italic text-primary border-start border-4 ps-3">
                                <i class="bi bi-quote"></i> <?= htmlspecialchars($n['frase_negocio']) ?>
                            </blockquote>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <div class="d-grid">
                                <a href="/negocio.php?id=<?= $n['id'] ?>" class="btn btn-outline-primary mt-2">Ver negócio</a>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="d-grid">
                                <a href="/negocio.php?id=<?= $n['id'] ?>" class="btn btn-outline-secondary mt-2">Apoiar</a>
                            </div>
                        </div>
                    </div>
                </div> <!-- Fim do Card -->

            </div> <!-- Fim da Coluna -->
        <?php endforeach; ?>
    </div>

</div>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>