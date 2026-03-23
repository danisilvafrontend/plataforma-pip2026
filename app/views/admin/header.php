<?php
// /home/.../app/views/admin/header.php
// Uso: include __DIR__ . '/header.php'; antes do conteúdo da página
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// título dinâmico
if (!isset($pageTitle)) {
    $pageTitle = 'Painel — Impactos Positivos';
}

// Pega a URL atual
$currentPage = $_SERVER['REQUEST_URI'];

// Define quais páginas pertencem ao submenu "Usuários"
$usuariosPages = [
    '/admin/administradores.php',
    '/admin/empreendedores.php',
    '/admin/parceiros.php',
    '/admin/usuarios.php'
];

// Verifica se está em alguma página de usuários
$isUsuariosActive = false;
foreach ($usuariosPages as $page) {
    if (strpos($currentPage, $page) !== false) {
        $isUsuariosActive = true;
        break;
    }
}

// Relatórios
$relatoriosPages = ['/admin/relatorios_negocios.php'];
$isrelatoriosActive = false;
foreach ($relatoriosPages as $page) {
    if (strpos($currentPage, $page) !== false) {
        $isrelatoriosActive = true;
        break;
    }
}

// Configurações
$configPages = ['/admin/importar_negocios.php', '/admin/importar_empreendedores.php', '/admin/atribuir_negocio.php'];
$isconfigActive = false;
foreach ($configPages as $page) {
    if (strpos($currentPage, $page) !== false) {
        $isconfigActive = true;
        break;
    }
}


// dados do usuário (preenchidos na sessão ao logar)
$userName = $_SESSION['user_name'] ?? 'Usuário';
$userRole = $_SESSION['user_role'] ?? '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>

  <!-- Bootstrap CSS (CDN) -->
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="/../assets/css/style.css">
  <!-- CSS dos blocos -->
  <link rel="stylesheet" href="/negocios/blocos-cadastros/assets/blocos.css">
  <!-- Ícone e meta básicos -->
  <link rel="icon" href="/assets/favicon.ico" type="image/x-icon">
  <!-- Estilos customizados mínimos -->
  <style>
    :root { --sidebar-width: 250px; }
    body { min-height:100vh; background:#f8f9fa; }
    .app-sidebar { width: var(--sidebar-width); min-height:100vh; background:#ffffff; border-right:1px solid #e6e9ee; }
    .app-main { margin-left: var(--sidebar-width); padding: 1.5rem; }
    .brand { font-weight:700; letter-spacing:.3px; font-size:1.05rem; }
    @media (max-width: 767px) {
      .app-sidebar { position: relative; width:100%; }
      .app-main { margin-left:0; padding-top:1rem; }
    }
  </style>

  <!-- Place to add page-specific CSS -->
  <?php if (!empty($extraHead ?? null)) echo $extraHead; ?>
</head>
<body>
  <!-- Top navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="/admin/dashboard.php">
        <span class="brand">Impactos Positivos</span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topbarMenu" aria-controls="topbarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="topbarMenu">
        <ul class="navbar-nav ms-auto align-items-center">
          <li class="nav-item me-3 text-white small">
            <?=htmlspecialchars($userName, ENT_QUOTES)?> <span class="text-muted"> (<?=htmlspecialchars($userRole, ENT_QUOTES)?>)</span>
          </li>
          <li class="nav-item">
            <a class="btn btn-outline-light btn-sm" href="/../logout.php">Sair</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Layout: sidebar + main -->
  <div class="d-flex">
    <aside class="app-sidebar p-3">
      <div class="mb-4">
        <strong class="d-block mb-1">Menu</strong>
        <small class="text-muted">Navegação rápida</small>
      </div>

      <ul class="nav flex-column">

        <li class="nav-item">
          <a class="nav-link <?= strpos($currentPage, '/admin/dashboard.php') !== false ? 'active' : '' ?>" 
            href="/admin/dashboard.php">Visão Geral</a>
        </li>

        <!-- Usuários (expande/contrai) -->
        <li class="nav-item">
          <a class="nav-link d-flex justify-content-between align-items-center <?= $isUsuariosActive ? 'active' : '' ?>"
            href="#usuariosSubmenu"
            data-bs-toggle="collapse"
            role="button"
            aria-expanded="<?= $isUsuariosActive ? 'true' : 'false' ?>"
            aria-controls="usuariosSubmenu">
            Usuários
            <span class="small"><i class="bi bi-caret-down-fill"></i></span>
          </a>

          <div class="collapse <?= $isUsuariosActive ? 'show' : '' ?>" id="usuariosSubmenu">
            <ul class="nav flex-column ms-3 mt-1">
              <li class="nav-item">
                <a class="nav-link py-1 <?= strpos($currentPage, '/admin/administradores.php') !== false ? 'active' : '' ?>" 
                  href="/admin/administradores.php">Administradores</a>
              </li>
              <li class="nav-item">
                <a class="nav-link py-1 <?= strpos($currentPage, '/admin/empreendedores.php') !== false ? 'active' : '' ?>" 
                  href="/admin/empreendedores.php">Empreendedores</a>
              </li>
              <li class="nav-item">
                <a class="nav-link py-1 <?= strpos($currentPage, '/admin/usuarios.php') !== false ? 'active' : '' ?>" 
                  href="/admin/usuarios.php">Usuários</a>
              </li>
            </ul>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= strpos($currentPage, '/admin/negocios.php') !== false ? 'active' : '' ?>" 
            href="/admin/negocios.php">Negócios</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= strpos($currentPage, '/admin/parceiros.php') !== false ? 'active' : '' ?>" 
            href="/admin/parceiros.php">Parceiros</a>
        </li>

        <!-- Relatórios (expande/contrai) -->
        <li class="nav-item">
          <a class="nav-link d-flex justify-content-between align-items-center <?= $isrelatoriosActive ? 'active' : '' ?>"
            href="#relatorioSubmenu"
            data-bs-toggle="collapse"
            role="button"
            aria-expanded="<?= $isrelatoriosActive ? 'true' : 'false' ?>"
            aria-controls="relatorioSubmenu">
            Relatórios
            <span class="small"><i class="bi bi-caret-down-fill"></i></span>
          </a>

          <div class="collapse <?= $isrelatoriosActive ? 'show' : '' ?>" id="relatorioSubmenu">
            <ul class="nav flex-column ms-3 mt-1">
              <li class="nav-item">
                <a class="nav-link py-1 <?= strpos($currentPage, '/admin/relatorios_negocios.php') !== false ? 'active' : '' ?>" 
                  href="/admin/relatorios_negocios.php">Negócios</a>
              </li>
            </ul>
          </div>
        </li>

        <!-- Configurações (expande/contrai) -->
        <li class="nav-item">
          <a class="nav-link d-flex justify-content-between align-items-center <?= $isconfigActive ? 'active' : '' ?>"
            href="#configSubmenu"
            data-bs-toggle="collapse"
            role="button"
            aria-expanded="<?= $isconfigActive ? 'true' : 'false' ?>"
            aria-controls="configSubmenu">
            Configurações
            <span class="small"><i class="bi bi-caret-down-fill"></i></span>
          </a>

          <div class="collapse <?= $isconfigActive ? 'show' : '' ?>" id="configSubmenu">
            <ul class="nav flex-column ms-3 mt-1">
              <li class="nav-item">
                <a class="nav-link py-1 <?= strpos($currentPage, '/admin/importar_negocios.php') !== false ? 'active' : '' ?>" 
                  href="/admin/importar_negocios.php">Importar Negócios</a>
              </li>
              <li class="nav-item">
                <a class="nav-link py-1 <?= strpos($currentPage, '/admin/importar_empreendedores.php') !== false ? 'active' : '' ?>" 
                  href="/admin/importar_empreendedores.php">Importar Empreendedores</a>
              </li>
              <li class="nav-item">
                <a class="nav-link py-1 <?= strpos($currentPage, '/admin/atribuir_negocio.php') !== false ? 'active' : '' ?>" 
                  href="/admin/atribuir_negocio.php">Atribuir Negócios</a>
              </li>
            </ul>
          </div>
        </li>
        <!-- <li class="nav-item">
          <a class="nav-link <?= strpos($currentPage, '/admin/importar_negocios.php') !== false ? 'active' : '' ?>" 
            href="/admin/importar_negocios.php">Importar Negócios</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= strpos($currentPage, '/admin/atribuir_negocio.php') !== false ? 'active' : '' ?>" 
            href="/admin/atribuir_negocio.php">Atribuir Negócios</a>
        </li> -->
      </ul>

      <hr>
      <div class="small text-muted">Versão MVP</div>
    </aside>



    <main class="container">
      <!-- lugar para breadcrumb ou título da página -->
      <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
        <h4 class="mb-3"><?=htmlspecialchars($pageTitle, ENT_QUOTES)?></h4>
      </div>