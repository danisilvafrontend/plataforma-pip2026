<?php
// /home/.../app/views/admin/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($pageTitle)) $pageTitle = 'Painel — Impactos Positivos';

$currentPage = $_SERVER['REQUEST_URI'];

$usuariosPages   = ['/admin/administradores.php', '/admin/empreendedores.php', '/admin/usuarios.php'];
$relatoriosPages = ['/admin/relatorios_negocios.php'];
$premiacaoPages = [
    '/admin/premiacao_edicoes.php',
    '/admin/premiacao_periodos.php',
    '/admin/premiacao_categorias.php',
    '/admin/premiacao_inscricoes.php',
    '/admin/premiacao_voto_popular.php',
    '/admin/premiacao_voto_tecnico.php',
    '/admin/premiacao_juri.php',
    '/admin/premiacao_resultados.php'
];
$configPages = [
    '/admin/importar_negocios.php',
    '/admin/importar_empreendedores.php',
    '/admin/atribuir_negocio.php',
    '/admin/gerenciar_notificacoes.php'
];

$isUsuariosActive   = (bool) array_filter($usuariosPages, fn($p) => str_contains($currentPage, $p));
$isRelatoriosActive = (bool) array_filter($relatoriosPages, fn($p) => str_contains($currentPage, $p));
$isPremiacaoActive  = (bool) array_filter($premiacaoPages, fn($p) => str_contains($currentPage, $p));
$isConfigActive     = (bool) array_filter($configPages, fn($p) => str_contains($currentPage, $p));

$userName = $_SESSION['user_name'] ?? 'Usuário';
$userRole = $_SESSION['user_role'] ?? '';

function isActive(string $current, string $page): string {
    return str_contains($current, $page) ? 'active' : '';
}

function podeVerMenuCompleto(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, ['admin', 'superadmin'], true);
}

function isJuriOuTecnica(): bool {
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, ['juri', 'tecnica'], true);
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?> — Impactos Positivos</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
  <!-- CSS dos blocos -->
  <link rel="stylesheet" href="/negocios/blocos-cadastros/assets/blocos.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/negocios/blocos-cadastros/assets/blocos.css') ?: time() ?>">
  <!-- Estilos globais + admin -->
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/css/style.css') ?: time() ?>">
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/css/admin.css') ?: time() ?>">
  <!-- Favicon -->
  <link rel="icon" href="/assets/favicon.ico" type="image/x-icon">


  <?php if (!empty($extraHead ?? null)) echo $extraHead; ?>
</head>
<body>
<!-- ── Topbar ─────────────────────────────────────────── -->
<header class="ip-topbar">
  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir menu">
    <i class="bi bi-list"></i>
  </button>

  <a href="/admin/dashboard.php" class="brand">
    <div class="brand-dot"><i class="bi bi-droplet-fill"></i></div>
    <div class="brand-name">
      Impactos Positivos
      <small>Painel Admin</small>
    </div>
  </a>

  <div class="topbar-right">
    <div class="user-chip">
      <div class="user-avatar"><?= strtoupper(mb_substr($userName, 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($userName, ENT_QUOTES) ?></div>
        <div class="user-role"><?= htmlspecialchars($userRole, ENT_QUOTES) ?></div>
      </div>
    </div>
    <a href="/logout.php" class="btn-logout">
      <i class="bi bi-box-arrow-right me-1"></i>Sair
    </a>
  </div>
</header>

<!-- ── Backdrop mobile ────────────────────────────────── -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- ── Sidebar ────────────────────────────────────────── -->
<aside class="ip-sidebar" id="ipSidebar">

  <span class="nav-group-label">Principal</span>
  <ul class="nav flex-column mb-1">
    <li class="nav-item">
      <a class="nav-link <?= isActive($currentPage, '/admin/dashboard.php') ?>" href="/admin/dashboard.php">
        <i class="bi bi-grid-fill"></i> Visão Geral
      </a>
    </li>
  </ul>

  <?php if (podeVerMenuCompleto()): ?>
    <span class="nav-group-label">Pessoas</span>
    <ul class="nav flex-column mb-1">
      <li class="nav-item">
        <a class="nav-link <?= $isUsuariosActive ? 'active' : '' ?>"
           href="#usuariosSubmenu"
           data-bs-toggle="collapse"
           role="button"
           aria-expanded="<?= $isUsuariosActive ? 'true' : 'false' ?>"
           aria-controls="usuariosSubmenu">
          <i class="bi bi-people-fill"></i> Usuários
          <i class="bi bi-chevron-down chevron"></i>
        </a>
        <div class="collapse <?= $isUsuariosActive ? 'show' : '' ?>" id="usuariosSubmenu">
          <ul class="nav flex-column submenu">
            <li><a class="nav-link <?= isActive($currentPage, '/admin/administradores.php') ?>" href="/admin/administradores.php"><i class="bi bi-shield-lock"></i> Administradores</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/empreendedores.php') ?>" href="/admin/empreendedores.php"><i class="bi bi-person-badge"></i> Empreendedores</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/usuarios.php') ?>" href="/admin/usuarios.php"><i class="bi bi-person"></i> Usuários</a></li>
          </ul>
        </div>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= isActive($currentPage, '/admin/parceiros.php') ?>" href="/admin/parceiros.php">
          <i class="bi bi-diagram-3-fill"></i> Parceiros
        </a>
      </li>
    </ul>
  <?php endif; ?>

  <span class="nav-group-label">Operacional</span>
  <ul class="nav flex-column mb-1">
    <li class="nav-item">
      <a class="nav-link <?= isActive($currentPage, '/admin/negocios.php') || isActive($currentPage, '/admin/visualizar_negocio.php') ?>" href="/admin/negocios.php">
        <i class="bi bi-briefcase-fill"></i> Negócios
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= isActive($currentPage, '/admin/relatorios_negocios.php') ?>" href="/admin/relatorios_negocios.php">
        <i class="bi bi-bar-chart-fill"></i> Relatórios
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= $isPremiacaoActive ? 'active' : '' ?>"
         href="#PremiacaoSubmenu"
         data-bs-toggle="collapse"
         role="button"
         aria-expanded="<?= $isPremiacaoActive ? 'true' : 'false' ?>"
         aria-controls="PremiacaoSubmenu">
        <i class="bi bi-trophy-fill"></i> Premiação
        <i class="bi bi-chevron-down chevron"></i>
      </a>

      <div class="collapse <?= $isPremiacaoActive ? 'show' : '' ?>" id="PremiacaoSubmenu">
        <ul class="nav flex-column submenu">
          <?php if (podeVerMenuCompleto()): ?>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/premiacao_edicoes.php') ?>" href="/admin/premiacao_edicoes.php"><i class="bi bi-calendar3"></i> Edições Premiação</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/premiacao_periodos.php') ?>" href="/admin/premiacao_periodos.php"><i class="bi bi-calendar3-range"></i> Periodo Premiação</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/premiacao_categorias.php') ?>" href="/admin/premiacao_categorias.php"><i class="bi bi-grid"></i> Categorias</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/premiacao_inscricoes.php') ?>" href="/admin/premiacao_inscricoes.php"><i class="bi bi-calendar-check"></i> Inscrições</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/premiacao_voto_popular.php') ?>" href="/admin/premiacao_voto_popular.php"><i class="bi bi-bar-chart-steps"></i> Votos Popular</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/premiacao_voto_tecnico.php') ?>" href="/admin/premiacao_voto_tecnico.php"><i class="bi bi-person-gear"></i> Votos Bancada Técnica</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/premiacao_juri.php') ?>" href="/admin/premiacao_juri.php"><i class="bi bi-person-check"></i> Votos Juri</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/premiacao_resultados.php') ?>" href="/admin/premiacao_resultados.php"><i class="bi bi-award"></i> Resultados</a></li>
          <?php elseif (isJuriOuTecnica()): ?>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/premiacao_voto_popular.php') ?>" href="/admin/premiacao_voto_popular.php"><i class="bi bi-bar-chart-steps"></i> Votos Popular</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/premiacao_voto_tecnico.php') ?>" href="/admin/premiacao_voto_tecnico.php"><i class="bi bi-person-gear"></i> Votos Bancada Técnica</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/premiacao_juri.php') ?>" href="/admin/premiacao_juri.php"><i class="bi bi-person-check"></i> Votos Juri</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </li>
  </ul>

  <?php if (($_SESSION['user_role'] ?? '') === 'superadmin'): ?>
    <span class="nav-group-label">Sistema</span>
    <ul class="nav flex-column mb-1">
      <li class="nav-item">
        <a class="nav-link <?= $isConfigActive ? 'active' : '' ?>"
           href="#configSubmenu"
           data-bs-toggle="collapse"
           role="button"
           aria-expanded="<?= $isConfigActive ? 'true' : 'false' ?>"
           aria-controls="configSubmenu">
          <i class="bi bi-gear-fill"></i> Configurações
          <i class="bi bi-chevron-down chevron"></i>
        </a>
        <div class="collapse <?= $isConfigActive ? 'show' : '' ?>" id="configSubmenu">
          <ul class="nav flex-column submenu">
            <li><a class="nav-link <?= isActive($currentPage, '/admin/importar_negocios.php') ?>" href="/admin/importar_negocios.php"><i class="bi bi-upload"></i> Importar Negócios</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/importar_empreendedores.php') ?>" href="/admin/importar_empreendedores.php"><i class="bi bi-upload"></i> Importar Empreendedores</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/gerenciar_notificacoes.php') ?>" href="/admin/gerenciar_notificacoes.php"><i class="bi bi-megaphone"></i> Gerenciar Notificações</a></li>
            <li><a class="nav-link <?= isActive($currentPage, '/admin/atribuir_negocio.php') ?>" href="/admin/atribuir_negocio.php"><i class="bi bi-diagram-3"></i> Atribuir Negócios</a></li>
          </ul>
        </div>
      </li>
    </ul>
  <?php endif; ?>

  <div class="sidebar-footer">
    <i class="bi bi-droplet-fill me-1" style="color:var(--ip-lime);"></i>
    Impactos Positivos 2026
  </div>
</aside>

<!-- ── Main content ───────────────────────────────────── -->
<main class="ip-main">

<script>
// Toggle sidebar mobile — inline para garantir execução imediata
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var btn      = document.getElementById('sidebarToggle');
    var sidebar  = document.getElementById('ipSidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!btn || !sidebar || !backdrop) return;

    function openSidebar()  { sidebar.classList.add('open');  backdrop.classList.add('show'); }
    function closeSidebar() { sidebar.classList.remove('open'); backdrop.classList.remove('show'); }

    btn.addEventListener('click', function () {
      sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    backdrop.addEventListener('click', closeSidebar);
  });
}());
</script>