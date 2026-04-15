<?php
// /public_html/empreendedores/dashboard.php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$pageTitle = 'Dashboard — Impactos Positivos';

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
    $config['user'], $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Estatísticas dos negócios do empreendedor
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

// Busca os negócios recentes
$stmtNegocios = $pdo->prepare("
    SELECT n.id, n.nome_fantasia, n.categoria, n.etapa_atual,
           n.inscricao_completa, n.publicado_vitrine, n.created_at,
           a.logo_negocio
    FROM negocios n
    LEFT JOIN negocio_apresentacao a ON a.negocio_id = n.id
    WHERE n.empreendedor_id = ?
    ORDER BY n.updated_at DESC
    LIMIT 5
");
$stmtNegocios->execute([$_SESSION['empreendedor_id']]);
$negociosRecentes = $stmtNegocios->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<?php if (!empty($_SESSION['flash_message'])): ?>
  <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    <?= htmlspecialchars($_SESSION['flash_message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<!-- Título da página -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h1 class="emp-page-title mb-1">
      Olá, <?= htmlspecialchars(explode(' ', $emp_nome)[0]) ?>
    </h1>
    <p class="emp-page-subtitle mb-0">Aqui está um resumo da sua área</p>
  </div>
  <a href="/negocios/etapa1_dados_negocio.php" class="btn-emp-primary">
    <i class="bi bi-plus-lg"></i> Cadastrar Negócio
  </a>
</div>

<!-- Cards de estatísticas -->
<div class="row g-3 mb-4">
  <div class="col-12 col-sm-4">
    <div class="emp-stat-card">
      <div class="emp-stat-icon" style="background:#e8f0e4;">
        <i class="bi bi-briefcase-fill" style="color:#1E3425;"></i>
      </div>
      <div>
        <div class="emp-stat-value"><?= $stats['total'] ?? 0 ?></div>
        <div class="emp-stat-label">Total de Negócios</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-4">
    <div class="emp-stat-card">
      <div class="emp-stat-icon" style="background:#e8f5e9;">
        <i class="bi bi-check-circle-fill" style="color:#2e7d32;"></i>
      </div>
      <div>
        <div class="emp-stat-value" style="color:#2e7d32;"><?= $stats['concluidos'] ?? 0 ?></div>
        <div class="emp-stat-label">Concluídos</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-4">
    <div class="emp-stat-card">
      <div class="emp-stat-icon" style="background:#fff8e1;">
        <i class="bi bi-hourglass-split" style="color:#f57f17;"></i>
      </div>
      <div>
        <div class="emp-stat-value" style="color:#f57f17;"><?= $stats['andamento'] ?? 0 ?></div>
        <div class="emp-stat-label">Em Andamento</div>
      </div>
    </div>
  </div>
</div>

<!-- Negócios Recentes -->
<div class="emp-card">
  <div class="emp-card-header">
    <i class="bi bi-clock-history"></i> Negócios Recentes
    <a href="/empreendedores/meus-negocios.php" class="ms-auto btn-emp-outline" style="font-size:.8rem; padding:.25rem .9rem;">
      Ver todos <i class="bi bi-arrow-right ms-1"></i>
    </a>
  </div>

  <?php if (!empty($negociosRecentes)): ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="emp-table-head">
          <tr>
            <th>Negócio</th>
            <th>Categoria</th>
            <th>Etapa</th>
            <th>Status</th>
            <th>Vitrine</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($negociosRecentes as $neg): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if (!empty($neg['logo_negocio'])): ?>
                  <img src="<?= htmlspecialchars($neg['logo_negocio']) ?>"
                       alt="Logo" class="emp-table-logo">
                <?php else: ?>
                  <div class="emp-table-logo-placeholder">
                    <i class="bi bi-building"></i>
                  </div>
                <?php endif; ?>
                <span class="fw-600 text-dark" style="font-size:.9rem;">
                  <?= htmlspecialchars($neg['nome_fantasia']) ?>
                </span>
              </div>
            </td>
            <td class="small text-muted"><?= htmlspecialchars($neg['categoria'] ?? '—') ?></td>
            <td>
              <span class="emp-etapa-badge">
                Etapa <?= (int)$neg['etapa_atual'] ?>/9
              </span>
            </td>
            <td>
              <?php if ($neg['inscricao_completa']): ?>
                <span class="emp-badge-ativo">Concluído</span>
              <?php else: ?>
                <span class="emp-badge-pendente">Em andamento</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($neg['publicado_vitrine']): ?>
                <i class="bi bi-eye-fill text-success" title="Publicado na vitrine"></i>
              <?php else: ?>
                <i class="bi bi-eye-slash text-muted" title="Não publicado"></i>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a href="/empreendedores/meus-negocios.php?id=<?= $neg['id'] ?>"
                 class="btn-emp-outline" style="font-size:.78rem; padding:.2rem .75rem;">
                Gerenciar
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="text-center py-5">
      <i class="bi bi-briefcase" style="font-size:2.5rem; color:#c8d4c0;"></i>
      <p class="mt-3 text-muted">Você ainda não cadastrou nenhum negócio.</p>
      <a href="/negocios/etapa1_dados_negocio.php" class="btn-emp-primary mt-1">
        <i class="bi bi-plus-lg me-1"></i> Cadastrar meu primeiro negócio
      </a>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>