<?php
// /home/dscria59_dani/public_html/index.php
declare(strict_types=1);

// título e extras opcionais para o header
$pageTitle = 'Impactos Positivos — Home';

// opcional: scripts a serem inseridos antes de </body>
$extraFooter = '<script>console.log("Home carregada");</script>';

// Conexão com banco para buscar negócios
$config = require __DIR__ . '/app/config/db.php';
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Busca 8 negócios aleatórios com inscrição completa
        // Busca 8 negócios aleatórios publicados na vitrine
    $sqlNegocios = "
    SELECT n.id, n.nome_fantasia, n.categoria, n.municipio, n.estado,
           a.frase_negocio, a.logo_negocio, o.icone_url,
           e.nome AS eixo_tematico_nome
    FROM negocios n
    LEFT JOIN negocio_apresentacao a ON a.negocio_id = n.id
    LEFT JOIN ods o ON o.id = n.ods_prioritaria_id
    LEFT JOIN eixos_tematicos e ON e.id = n.eixo_principal_id
    WHERE n.publicado_vitrine = 1
    ORDER BY RAND()
    LIMIT 9";
$stmtNegocios = $pdo->query($sqlNegocios);
$negociosDestaque = $stmtNegocios->fetchAll(PDO::FETCH_ASSOC);

    
} catch (PDOException $e) {
    $negociosDestaque = [];
    error_log("Erro ao buscar negócios: " . $e->getMessage());
}

// inclui header público
include __DIR__ . '/app/views/public/header_public.php';
?>

<!-- Hero Section -->
<section class="hero text-center py-5 bg-light">
  <div class="container">
    <h1 class="display-5 mb-3">Bem-vindo(a) ao Impactos Positivos</h1>
    <p class="lead text-muted mb-4">Conectando empreendedores de impacto, cursos, eventos e uma comunidade engajada.</p>
    <a class="btn btn-success btn-lg shadow-sm" href="/empreendedores/register.php">
        <i class="bi bi-pencil-square"></i> FAÇA SUA INSCRIÇÃO AQUI!
    </a>
  </div>
</section>

<!-- Vitrine de Negócios (8 aleatórios) -->
<?php if (!empty($negociosDestaque)): ?>
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">Conheça Negócios de Impacto</h2>
            <a href="/vitrine_nacional.php" class="btn btn-outline-primary">
                Ver Todos <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <?php
        // inclui grid da vitrine
        include __DIR__ . '/app/views/public/grid_vitrine.php';?>
    </div>
</section>
<?php endif; ?>
<!-- FIM VITRINE DE NEGÓCIOS 8 (aleatorios) -->



<?php
// inclui footer público
include __DIR__ . '/app/views/public/footer_public.php';