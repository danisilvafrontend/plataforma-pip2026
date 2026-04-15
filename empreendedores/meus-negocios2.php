<?php
// /public_html/empreendedores/meus-negocios.php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$pageTitle = 'Meus Negócios — Impactos Positivos';

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'], $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->prepare("
    SELECT n.id, n.nome_fantasia, n.categoria, n.etapa_atual,
           n.inscricao_completa, n.status_operacional,
           n.status_vitrine, n.publicado_vitrine,
           a.logo_negocio, a.imagem_destaque
    FROM negocios n
    LEFT JOIN negocio_apresentacao a ON a.negocio_id = n.id
    WHERE n.empreendedor_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$negocios = $stmt->fetchAll();

$etapas = [
    1 => 'Dados do Negócio',       2 => 'Fundadores',
    3 => 'Eixo Temático',          4 => 'Conexão com os ODS',
    5 => 'Apresentação',           6 => 'Dados Financeiros',
    7 => 'Avaliação de Impacto',   8 => 'Visão de Futuro',
    9 => 'Documentação',           10 => 'Revisão e Confirmação'
];
$arquivosEtapas = [
    1 => 'etapa1_dados_negocio.php', 2 => 'etapa2_fundadores.php',
    3 => 'etapa3_eixo_tematico.php', 4 => 'etapa4_ods.php',
    5 => 'etapa5_apresentacao.php',  6 => 'etapa6_financeiro.php',
    7 => 'etapa7_impacto.php',       8 => 'etapa8_visao.php',
    9 => 'etapa9_documentacao.php',  10 => 'confirmacao.php'
];

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<?php if (!empty($_SESSION['success_message'])): ?>
  <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    <?= htmlspecialchars($_SESSION['success_message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['errors_message'])): ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?= htmlspecialchars($_SESSION['errors_message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['errors_message']); ?>
<?php endif; ?>

<?php if (isset($_GET['ok'])): ?>
  <div class="alert alert-<?= $_GET['ok'] === 'publicado' ? 'success' : 'info' ?> alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-<?= $_GET['ok'] === 'publicado' ? 'check-circle' : 'eye-slash' ?> me-2"></i>
    <?= $_GET['ok'] === 'publicado' ? 'Negócio publicado com sucesso na vitrine!' : 'Negócio ocultado da vitrine pública.' ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Título -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h1 class="emp-page-title mb-1"><i class="bi bi-briefcase me-2"></i>Meus Negócios</h1>
    <p class="emp-page-subtitle mb-0">Acompanhe e gerencie todos os seus negócios cadastrados</p>
  </div>
  <a href="/negocios/etapa1_dados_negocio.php" class="btn-emp-primary">
    <i class="bi bi-plus-lg"></i> Cadastrar Novo Negócio
  </a>
</div>

<?php if (empty($negocios)): ?>

  <!-- Estado vazio -->
  <div class="emp-card text-center py-5">
    <i class="bi bi-briefcase" style="font-size:3rem; color:#c8d4c0;"></i>
    <h5 class="mt-3 mb-1" style="color:#1E3425;">Nenhum negócio cadastrado ainda</h5>
    <p class="text-muted small mb-4">Comece agora e apresente seu negócio de impacto para o mundo.</p>
    <a href="/negocios/etapa1_dados_negocio.php" class="btn-emp-primary">
      <i class="bi bi-plus-lg me-1"></i> Cadastrar meu primeiro negócio
    </a>
  </div>

<?php else: ?>

  <div class="row g-4">
    <?php foreach ($negocios as $n): ?>

      <?php
        $etapaAtual    = (int)$n['etapa_atual'];
        $completo      = (bool)$n['inscricao_completa'];
        $encerrado     = ($n['status_operacional'] ?? '') === 'encerrado';
        $publicado     = (int)($n['publicado_vitrine'] ?? 0) === 1;
        $statusVitrine = $n['status_vitrine'] ?? 'pendente';

        // Badge status vitrine
        $vitrineBadge = match($statusVitrine) {
            'aprovado'   => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'text' => 'Aprovado',    'icon' => 'bi-check-circle-fill'],
            'em_analise' => ['bg' => '#fff8e1', 'color' => '#f57f17', 'text' => 'Em Análise',  'icon' => 'bi-hourglass-split'],
            'rejeitado'  => ['bg' => '#fdecea', 'color' => '#c62828', 'text' => 'Rejeitado',   'icon' => 'bi-x-circle-fill'],
            default      => ['bg' => '#f5f5f5', 'color' => '#757575', 'text' => 'Pendente',    'icon' => 'bi-clock'],
        };

        // Progresso
        $progresso = $completo ? 100 : min(round(($etapaAtual / 10) * 100), 95);
      ?>

      <div class="col-12 col-md-6 col-xl-4">
        <div class="emp-negocio-card">

          <!-- Capa / Imagem de destaque -->
          <div class="emp-negocio-capa">
            <?php if (!empty($n['imagem_destaque'])): ?>
              <img src="<?= htmlspecialchars($n['imagem_destaque']) ?>" alt="Capa">
            <?php elseif (!empty($n['logo_negocio'])): ?>
              <img src="<?= htmlspecialchars($n['logo_negocio']) ?>"
                   alt="Logo" style="object-fit:contain; padding:1rem; background:#f0f4ed;">
            <?php else: ?>
              <div class="emp-negocio-capa-placeholder">
                <i class="bi bi-building"></i>
              </div>
            <?php endif; ?>

            <!-- Badge vitrine sobreposta -->
            <span class="emp-negocio-vitrine-badge"
                  style="background:<?= $vitrineBadge['bg'] ?>; color:<?= $vitrineBadge['color'] ?>;">
              <i class="bi <?= $vitrineBadge['icon'] ?> me-1"></i><?= $vitrineBadge['text'] ?>
            </span>

            <?php if ($encerrado): ?>
              <span class="emp-negocio-encerrado-badge">
                <i class="bi bi-slash-circle me-1"></i> Encerrado
              </span>
            <?php endif; ?>
          </div>

          <!-- Corpo do card -->
          <div class="emp-negocio-body">

            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
              <h5 class="emp-negocio-nome"><?= htmlspecialchars($n['nome_fantasia']) ?></h5>
              <?php if ($completo && !$encerrado): ?>
                <span class="emp-badge-ativo flex-shrink-0">Completo</span>
              <?php elseif ($encerrado): ?>
                <span class="emp-badge-rascunho flex-shrink-0">Encerrado</span>
              <?php else: ?>
                <span class="emp-badge-pendente flex-shrink-0">Em andamento</span>
              <?php endif; ?>
            </div>

            <p class="emp-negocio-categoria mb-2">
              <i class="bi bi-tag me-1"></i><?= htmlspecialchars($n['categoria'] ?? '—') ?>
            </p>

            <!-- Barra de progresso -->
            <?php if (!$completo): ?>
              <div class="emp-progress-wrap mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="small" style="color:#6c8070; font-size:.75rem;">
                    <?= $etapas[$etapaAtual] ?? "Etapa $etapaAtual" ?>
                  </span>
                  <span class="small fw-bold" style="color:#1E3425; font-size:.75rem;">
                    <?= $etapaAtual ?>/10
                  </span>
                </div>
                <div class="emp-progress-bar-wrap">
                  <div class="emp-progress-bar-fill" style="width:<?= $progresso ?>%"></div>
                </div>
              </div>
            <?php else: ?>
              <div class="d-flex align-items-center gap-1 mb-3 small" style="color:#2e7d32;">
                <i class="bi bi-check-circle-fill"></i> Todas as etapas concluídas
              </div>
            <?php endif; ?>

            <!-- Ações -->
            <div class="emp-negocio-acoes">

              <?php if ($completo): ?>
                <a href="/negocios/confirmacao.php?id=<?= $n['id'] ?>" class="btn-emp-outline flex-1">
                  <i class="bi bi-card-checklist me-1"></i> Ver Revisão
                </a>
                <?php if ($publicado && !$encerrado): ?>
                  <a href="/negocio.php?id=<?= $n['id'] ?>" target="_blank" class="btn-emp-primary flex-1">
                    <i class="bi bi-eye me-1"></i> Ver na Vitrine
                  </a>
                  <button class="btn-emp-icon text-danger" title="Ocultar da Vitrine"
                          onclick="abrirModalOcultar(<?= $n['id'] ?>)">
                    <i class="bi bi-eye-slash"></i>
                  </button>
                <?php elseif ($encerrado && $statusVitrine === 'aprovado'): ?>
                  <form action="/negocios/publicar.php" method="post" class="flex-1">
                    <input type="hidden" name="negocio_id" value="<?= $n['id'] ?>">
                    <input type="hidden" name="acao" value="republicar">
                    <button type="submit" class="btn-emp-primary w-100">
                      <i class="bi bi-arrow-repeat me-1"></i> Republicar
                    </button>
                  </form>
                <?php endif; ?>

              <?php elseif ($etapaAtual >= 10): ?>
                <a href="/negocios/confirmacao.php?id=<?= $n['id'] ?>" class="btn-emp-primary flex-1">
                  <i class="bi bi-send me-1"></i> Revisão Final
                </a>

              <?php else: ?>
                <a href="/negocios/<?= $arquivosEtapas[$etapaAtual] ?? 'etapa1_dados_negocio.php' ?>?id=<?= $n['id'] ?>"
                   class="btn-emp-primary flex-1">
                  <i class="bi bi-arrow-right me-1"></i> Continuar
                </a>

                <!-- Dropdown editar etapas anteriores -->
                <div class="dropdown">
                  <button class="btn-emp-icon" type="button" data-bs-toggle="dropdown"
                          title="Editar etapa anterior">
                    <i class="bi bi-pencil-square"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end emp-dropdown">
                    <li class="px-3 py-1 emp-dropdown-role">Editar Etapa</li>
                    <?php for ($num = 1; $num <= $etapaAtual; $num++): ?>
                      <li>
                        <a class="dropdown-item emp-dropdown-item"
                           href="/negocios/editar_etapa<?= $num ?>.php?id=<?= $n['id'] ?>">
                          <i class="bi bi-pencil me-2"></i>
                          <?= $num ?>. <?= $etapas[$num] ?? "Etapa $num" ?>
                        </a>
                      </li>
                    <?php endfor; ?>
                  </ul>
                </div>
              <?php endif; ?>

            </div>
          </div>
        </div>
      </div>

    <?php endforeach; ?>
  </div>

<?php endif; ?>

<!-- Modal Ocultar -->
<div class="modal fade" id="modalOcultar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px; border:none;">
      <form action="/negocios/publicar.php" method="post">
        <div class="modal-header" style="border-bottom:1px solid #f0f4ed;">
          <h5 class="modal-title text-danger">
            <i class="bi bi-eye-slash me-2"></i>Ocultar Negócio
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small mb-4">Escolha o motivo para remover este negócio da vitrine pública:</p>
          <input type="hidden" name="negocio_id" id="modal_ocultar_negocio_id" value="">
          <input type="hidden" name="acao" value="remover">

          <div class="form-check p-3 mb-2 rounded" style="background:#f5f7f2; border:1px solid #e8ede5;">
            <input class="form-check-input" type="radio" name="motivo" id="motivoOcultar" value="oculto" checked>
            <label class="form-check-label" for="motivoOcultar">
              <strong class="d-block">Ocultar temporariamente</strong>
              <small class="text-muted">O negócio continua em operação, mas ficará fora da vitrine por ora.</small>
            </label>
          </div>
          <div class="form-check p-3 rounded" style="background:#fff5f5; border:1px solid #ffd7d7;">
            <input class="form-check-input" type="radio" name="motivo" id="motivoEncerrado" value="encerrado">
            <label class="form-check-label text-danger" for="motivoEncerrado">
              <strong class="d-block">Este negócio foi encerrado</strong>
              <small style="color:#e57373;">As atividades foram encerradas. Os dados são mantidos no seu histórico.</small>
            </label>
          </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid #f0f4ed;">
          <button type="button" class="btn-emp-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger rounded-pill px-4">Confirmar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function abrirModalOcultar(id) {
  document.getElementById('modal_ocultar_negocio_id').value = id;
  new bootstrap.Modal(document.getElementById('modalOcultar')).show();
}
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>