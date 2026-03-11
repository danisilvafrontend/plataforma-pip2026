<?php
// /public_html/empreendedores/dashboard.php
declare(strict_types=1);
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

if (!empty($_SESSION['flash_message'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['flash_message']) ?>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif;

$config = require __DIR__ . '/../app/config/db.php';

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Busca estatísticas dos negócios do empreendedor logado
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN inscricao_completa = 1 THEN 1 ELSE 0 END) AS concluidos,
        SUM(CASE WHEN inscricao_completa = 0 THEN 1 ELSE 0 END) AS andamento
    FROM negocios
    WHERE empreendedor_id = ?
");
$stmt->execute([$_SESSION['empreendedor_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);


// Inclui o header específico de empreendedor
include __DIR__ . '/../app/views/empreendedor/header.php';
?>


<div class="container-fluid">
  <div class="row g-3">
    <!-- Menu lateral -->
    <nav class="col-md-3 col-lg-3 d-md-block bg-light sidebar">
      <?php include __DIR__ . '/../app/views/empreendedor/menu_lateral.php'; ?>
    </nav>

    <!-- Conteúdo principal -->
    <main class="col-md-9 col-lg-9 ms-sm-auto px-md-6">
      <h1 class="mt-4">Dashboard</h1>
      <div class="row mt-4">
        <div class="col-md-4">
          <div class="card text-center shadow-sm">
            <div class="card-body">
              <h5 class="card-title">Negócios</h5>
              <p class="display-6"><?= $stats['total'] ?? 0 ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-center shadow-sm">
            <div class="card-body">
              <h5 class="card-title">Concluídos</h5>
              <p class="display-6 text-success"><?= $stats['concluidos'] ?? 0 ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-center shadow-sm">
            <div class="card-body">
              <h5 class="card-title">Em andamento</h5>
              <p class="display-6 text-warning"><?= $stats['andamento'] ?? 0 ?></p>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-4">
        <a href="/empreendedores/meus-negocios.php" class="btn btn-outline-primary">
          Gerenciar negócios →
        </a>
      </div>
    </main>
  </div>
</div>



<?php
// Inclui o footer específico de empreendedor
include __DIR__ . '/../app/views/empreendedor/footer.php';
?>