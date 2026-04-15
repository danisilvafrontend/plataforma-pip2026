<?php
// /public_html/admin/dashboard.php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

require_once __DIR__ . '/../app/services/Database.php';

$pageTitle = 'Dashboard';
$extraFooter = '<script>console.log("Dashboard ready")</script>';

include __DIR__ . '/../app/views/admin/header.php';

function getCount(string $table, string $where = '1 = 1'): int {
    try {
        $pdo = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        $stmt = $pdo->query($sql);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('KPI query error: ' . $e->getMessage());
        return 0;
    }
}

// KPIs de Negócios
$totalNegocios        = getCount('negocios', '1=1');
$negociosEncerrados   = getCount('negocios', "status_operacional = 'encerrado'");
$negociosConcluidos   = getCount('negocios', "inscricao_completa = 1 AND (status_operacional != 'encerrado' OR status_operacional IS NULL)");
$negociosEmAndamento  = getCount('negocios', "(inscricao_completa = 0 OR inscricao_completa IS NULL) AND (status_operacional != 'encerrado' OR status_operacional IS NULL)");

// KPIs de Usuários
$totalEmpreendedores  = getCount('empreendedores');
$empreendedoresAtivos = getCount('empreendedores', "status = 'ativo'");

$totalParceiros       = getCount('parceiros');
$parceirosAtivos      = getCount('parceiros', "status = 'ativo'");

$totalSociedadeCivil  = getCount('sociedade_civil');

// Se sociedade_civil não tiver coluna status, mantemos apenas no total
$sociedadeCivilAtivos = 0;

// Totais gerais
$totalUsuarios  = $totalEmpreendedores + $totalParceiros + $totalSociedadeCivil;
$usuariosAtivos = $empreendedoresAtivos + $parceirosAtivos + $sociedadeCivilAtivos;

$taxaUsuariosAtivos = $totalUsuarios > 0
    ? round(($usuariosAtivos / $totalUsuarios) * 100)
    : 0;


// Últimos logins
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


<!-- ══════════════════════════════════
     Saudação + data
═══════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0" style="color:#1E3425;">Painel Administrativo</h4>
    <small style="color:#6c8070; font-size:.82rem;">
      <?php
        $fmt = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
        echo ucfirst($fmt->format(new DateTime()));
        // Resultado: "Quinta-feira, 26 de março de 2026"
      ?>
    </small>
  </div>
  <span class="badge rounded-pill px-3 py-2" style="background:#CDDE00; color:#1E3425; font-weight:700; font-size:.8rem;">
    <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i> Sistema online
  </span>
</div>

<!-- ══════════════════════════════════
     KPI Cards — linha 1
═══════════════════════════════════ -->
<div class="row g-3 mb-4">

  <!-- Empreendedores Total -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card kpi-card p-3 h-100">
      <div class="d-flex align-items-start gap-3">
        <div class="kpi-icon primary"><i class="bi bi-people-fill"></i></div>
        <div class="flex-grow-1">
          <div class="kpi-label mb-1">Total de usuários</div>
          <div class="kpi-value"><?= $totalUsuarios ?></div>
        </div>
      </div>
      <div class="kpi-progress mt-3">
        <div class="kpi-progress-bar" style="width:<?= $totalUsuarios > 0 ? min(100, round(($usuariosAtivos / $totalUsuarios) * 100)) : 0 ?>%"></div>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-2">
        <small class="text-muted" style="font-size:.75rem;"><?= $totalEmpreendedores ?> Empreendedores</small>
        <a href="/admin/empreendedores.php" class="kpi-link">Ver todos <i class="bi bi-arrow-right"></i></a>
      </div>
      
      <div class="d-flex justify-content-between align-items-center mt-2">
        <small class="text-muted" style="font-size:.75rem;"><?= $totalParceiros ?> Parceiros</small>
        <a href="/admin/parceiros.php" class="kpi-link">Ver todos <i class="bi bi-arrow-right"></i></a>
      </div>
      
      <div class="d-flex justify-content-between align-items-center mt-2">
        <small class="text-muted" style="font-size:.75rem;"><?= $totalSociedadeCivil ?> Sociedade</small>
        <a href="/admin/usuarios.php" class="kpi-link">Ver todos <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
  </div>

  <!-- Empreendedores Ativos -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card kpi-card p-3 h-100">
      <div class="d-flex align-items-start gap-3">
        <div class="kpi-icon secondary"><i class="bi bi-person-check-fill"></i></div>
        <div class="flex-grow-1">
          <div class="kpi-label mb-1">Usuários ativos</div>
          <div class="kpi-value"><?= $usuariosAtivos ?></div>
        </div>
      </div>
      <div class="mt-auto pt-3">
        <a href="/admin/empreendedores.php?filter=ativos" class="kpi-link">Filtrar ativos <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
  </div>

  <!-- Total Negócios -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card kpi-card p-3 h-100">
      <div class="d-flex align-items-start gap-3">
        <div class="kpi-icon success"><i class="bi bi-briefcase-fill"></i></div>
        <div class="flex-grow-1">
          <div class="kpi-label mb-1">Total de negócios</div>
          <div class="kpi-value"><?= $totalNegocios ?></div>
        </div>
      </div>
      <div class="mt-3">
        <div class="neg-row">
          <span class="neg-label"><i class="bi bi-check-circle-fill text-success"></i> Concluídos</span>
          <span class="badge rounded-pill" style="background:rgba(205,222,0,.2);color:#7a8500;"><?= $negociosConcluidos ?></span>
        </div>
        <div class="neg-row">
          <span class="neg-label"><i class="bi bi-hourglass-split" style="color:#97A327;"></i> Em andamento</span>
          <span class="badge rounded-pill" style="background:rgba(151,163,39,.15);color:#5c6318;"><?= $negociosEmAndamento ?></span>
        </div>
        <div class="neg-row">
          <span class="neg-label"><i class="bi bi-x-circle-fill text-danger"></i> Encerrados</span>
          <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger"><?= $negociosEncerrados ?></span>
        </div>
      </div>
      <div class="mt-2 pt-1">
        <a href="/admin/negocios.php" class="kpi-link">Gerenciar negócios <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
  </div>

  <!-- Taxa de atividade -->
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card kpi-card p-3 h-100" style="background: linear-gradient(135deg,#1E3425 0%,#2d5039 100%);">
      <div class="d-flex align-items-start gap-3">
        <div class="kpi-icon" style="background:rgba(205,222,0,.15);color:#CDDE00;"><i class="bi bi-activity"></i></div>
        <div class="flex-grow-1">
          <div class="kpi-label mb-1" style="color:rgba(255,255,255,.6);">Taxa de atividade</div>
          <div class="kpi-value" style="color:#CDDE00;">
            <?= $taxaUsuariosAtivos ?>%
          </div>
        </div>
      </div>
      <div class="kpi-progress mt-3" style="background:rgba(255,255,255,.15);">
        <div class="kpi-progress-bar" style="width:<?= $taxaUsuariosAtivos ?>%; background:#CDDE00;"></div>
      </div>
      <div class="mt-2">
        <small style="color:rgba(255,255,255,.5); font-size:.75rem;">Usuários ativos vs total geral</small>
      </div>
    </div>
  </div>

</div>

<!-- ══════════════════════════════════
     Atalhos rápidos
═══════════════════════════════════ -->
<div class="card section-card mb-4">
  <div class="card-header d-flex align-items-center gap-2">
    <i class="bi bi-lightning-charge-fill" style="color:#CDDE00;"></i>
    <h5>Acesso rápido</h5>
  </div>
  <div class="card-body">
    <div class="d-flex flex-wrap gap-2">
      <a href="/admin/administradores.php" class="shortcut-btn"><i class="bi bi-shield-lock me-1"></i>Administradores</a>
      <a href="/admin/empreendedores.php" class="shortcut-btn"><i class="bi bi-people me-1"></i>Empreendedores</a>
      <a href="/admin/parceiros.php"       class="shortcut-btn"><i class="bi bi-handshake me-1"></i>Parceiros</a>
      <a href="/admin/usuarios.php"        class="shortcut-btn"><i class="bi bi-person me-1"></i>Usuários</a>
      <a href="/admin/negocios.php"        class="shortcut-btn"><i class="bi bi-briefcase me-1"></i>Negócios</a>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════
     Visão geral + Últimos logins
═══════════════════════════════════ -->
<div class="row g-3">

  <div class="col-12 col-lg-8">
    <div class="card section-card h-100">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-bar-chart-fill" style="color:#97A327;"></i>
        <h5>Visão Geral</h5>
      </div>
      <div class="card-body">
        <div class="overview-placeholder">
          <i class="bi bi-graph-up"></i>
          <span style="font-size:.85rem; font-weight:600;">Gráficos e relatórios em breve</span>
          <span style="font-size:.78rem;">Adicione aqui gráficos, últimas atividades ou resumo mensal</span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card section-card h-100">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-clock-history" style="color:#95BCCC;"></i>
        <h5>Últimos logins</h5>
      </div>
      <div class="card-body">
        <?php if (!empty($ultimosLogins)): ?>
          <?php foreach ($ultimosLogins as $login): ?>
            <div class="login-item">
              <div class="d-flex align-items-center gap-2">
                <div class="login-avatar">
                  <?= strtoupper(mb_substr($login['nome'], 0, 1)) ?>
                </div>
                <div>
                  <div class="login-name"><?= htmlspecialchars($login['nome']) ?></div>
                  <div class="login-email"><?= htmlspecialchars($login['email']) ?></div>
                </div>
              </div>
              <span class="login-badge">
                <i class="bi bi-clock me-1"></i><?= date('d/m H:i', strtotime($login['ultimo_login'])) ?>
              </span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center py-4" style="color:#9aab9d;">
            <i class="bi bi-person-x" style="font-size:1.8rem; opacity:.4;"></i>
            <p class="mt-2 mb-0" style="font-size:.85rem;">Nenhum login recente encontrado.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>