<?php
// /public_html/admin/dashboard.php
session_start();
require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

// Ajuste estes requires conforme sua estrutura se necessário
require_once __DIR__ . '/../app/services/Database.php';

// define título e, opcionalmente, CSS/JS extras
$pageTitle = 'Dashboard';
$extraHead = '<link rel="stylesheet" href="/assets/admin.css">';
$extraFooter = '<script>console.log("Dashboard ready")</script>';

// inclui header
include __DIR__ . '/../app/views/admin/header.php';

/**
 * Função auxiliar para KPIs
 * Observação: use com cuidado para evitar SQL injection — aqui o $table e $where são controlados internamente.
 */
function getCount(string $table, string $where = '1 = 1'): int {
    try {
        $pdo = Database::getInstance(); // espera que Database::getInstance() retorne PDO
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        $stmt = $pdo->query($sql);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('KPI query error: ' . $e->getMessage());
        return 0;
    }
}

// KPIs de Negócios
$totalNegocios = getCount('negocios', '1=1');
$negociosEncerrados = getCount('negocios', "status_operacional = 'encerrado'");
$negociosConcluidos = getCount('negocios', "inscricao_completa = 1 AND (status_operacional != 'encerrado' OR status_operacional IS NULL)");
$negociosEmAndamento = getCount('negocios', "(inscricao_completa = 0 OR inscricao_completa IS NULL) AND (status_operacional != 'encerrado' OR status_operacional IS NULL)");



// KPIs focados em empreendedores
$totalEmpreendedores = getCount('empreendedores');                         // todos
$empreendedoresAtivos = getCount('empreendedores', "status = 'ativo'");    // ativos
$totalNegocios = getCount('negocios');                                     // negócios

// Últimos logins de empreendedores
try {
    $pdo = Database::getInstance();
    $stmt = $pdo->query("
        SELECT nome, email, ultimo_login
        FROM empreendedores
        WHERE ultimo_login IS NOT NULL
        ORDER BY ultimo_login DESC
        LIMIT 5
    ");
    $ultimosLogins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Erro ao buscar últimos logins: ' . $e->getMessage());
    $ultimosLogins = [];
}

?>
<div class="row g-3">
  <div class="col-12 col-md-4">
    <div class="card">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted">Usuários (total)</h6>
        <h3 class="card-title"><?= htmlspecialchars((string)$totalEmpreendedores) ?></h3>
        <p class="mb-0"><a href="/admin/empreendedores.php" class="stretched-link">Ver lista de empreendedores</a></p>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted">Usuários ativos</h6>
        <h3 class="card-title"><?= htmlspecialchars((string)$empreendedoresAtivos) ?></h3>
        <p class="mb-0"><a href="/admin/empreendedores.php?filter=ativos" class="stretched-link">Filtrar ativos</a></p>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card h-100 shadow-sm border-left-primary"> <!-- border-left-primary se tiver css custom -->
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h6 class="card-subtitle text-muted mb-1">Negócios</h6>
                    <h3 class="card-title fw-bold text-primary mb-0"><?= $totalNegocios ?></h3>
                </div>
            </div>

            <div class="mt-3 mb-2">
                <!-- Concluídos -->
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small text-muted"><i class="bi bi-check-circle-fill text-success me-1"></i> Concluídos</span>
                    <span class="badge bg-success rounded-pill"><?= $negociosConcluidos ?></span>
                </div>
                
                <!-- Em Andamento -->
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small text-muted"><i class="bi bi-hourglass-split text-warning text-dark me-1"></i> Em andamento</span>
                    <span class="badge bg-warning text-dark rounded-pill"><?= $negociosEmAndamento ?></span>
                </div>

                <!-- Encerrados -->
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted"><i class="bi bi-x-circle-fill text-danger me-1"></i> Encerrados</span>
                    <span class="badge bg-danger rounded-pill"><?= $negociosEncerrados ?></span>
                </div>
            </div>
            
            <hr class="my-2">
            
            <a href="/admin/negocios.php" class="small text-decoration-none fw-bold stretched-link">
                Gerenciar negócios <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
  </div>


</div>

<hr class="my-4">

<div class="row">
  <div class="col-12">
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title">Atalhos</h5>
        <div class="d-flex gap-2">
          <a href="/admin/administradores.php" class="btn btn-outline-primary">Administradores</a>
          <a href="/admin/empreendedores.php" class="btn btn-outline-primary">Empreendedores</a>
          <a href="/admin/usuarios.php" class="btn btn-outline-primary">Usuários</a>
          <a href="/admin/negocios.php" class="btn btn-outline-primary">Negócios</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- espaço para widgets, gráficos e tabelas -->
<div class="row">
  <div class="col-12 col-lg-8">
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title">Visão Geral</h5>
        <p class="text-muted">Adicione aqui gráficos, últimas atividades, ou um resumo mensal.</p>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title">Últimos logins</h5>
        <?php if (!empty($ultimosLogins)): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($ultimosLogins as $login): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <strong><?= htmlspecialchars($login['nome']) ?></strong><br>
                  <small class="text-muted"><?= htmlspecialchars($login['email']) ?></small>
                </div>
                <span class="badge bg-secondary">
                  <?= date('d/m/Y H:i', strtotime($login['ultimo_login'])) ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-muted">Nenhum login recente encontrado.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
// inclui footer
include __DIR__ . '/../app/views/admin/footer.php';