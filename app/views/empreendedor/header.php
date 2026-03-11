<?php
// header_public.php
// Uso: definir $pageTitle (opcional), $extraHead (opcional) antes de incluir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($pageTitle)) {
    $pageTitle = 'Impactos Positivos';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>

  <!-- Bootstrap CSS via CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="/../assets/css/style.css">
  <link rel="stylesheet" href="/../negocios/blocos-cadastros/assets/blocos.css">


  <!-- Ícone e meta básicos -->
  <link rel="icon" href="/assets/favicon.ico" type="image/x-icon">
  <meta name="theme-color" content="#0d6efd">

  <!-- Estilos públicos mínimos -->
  <style>
    body { background: #f7f9fc; color: #222; }
    .site-header { background: #ffffff; border-bottom: 1px solid #e9eef6; }
    .brand { font-weight:700; letter-spacing:.3px; }
    .hero { padding: 2.5rem 0; }
    footer.site-footer { background:#fff; border-top:1px solid #e9eef6; padding:1.25rem 0; }
  </style>

  <!-- área para CSS/links extras definidos pela página -->
  <?php if (!empty($extraHead ?? null)) echo $extraHead; ?>
</head>
<body>
  <header class="site-header">
    <nav class="navbar navbar-expand-lg navbar-light container">
      <a class="navbar-brand d-flex align-items-center" href="/">
        <img src="/../assets/images/impactos_positivos.svg" alt="Impactos Positivos" style="height:70px; margin-right:.5rem;">
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="/vitrine_nacional.php">Vitrine Nacional</a></li>
          <?php if (!empty($_SESSION['empreendedor_id'])): ?>
          <!-- Dropdown do usuário logado com avatar -->
          <li class="nav-item dropdown ms-2">
            <a class="nav-link dropdown-toggle d-flex align-items-center btn btn-outline-secondary btn-sm" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <!-- Nome do usuário -->
              <span>Olá, <strong><?= htmlspecialchars($_SESSION['empreendedor_nome'], ENT_QUOTES) ?></strong></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="/empreendedores/dashboard.php">Minha Conta</a></li>
              <li><a class="dropdown-item" href="/empreendedores/meus-negocios.php">Meus Negócios</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="/logout.php">Sair</a></li>
            </ul>
          </li>
        <?php else: ?>
          <!-- Se não estiver logado -->
          <li class="nav-item">
            <a class="nav-link btn btn-outline-primary btn-sm ms-2" href="/login.php">Entrar</a>
          </li>
        <?php endif; ?>
        </ul>
      </div>
    </nav>
  </header>

  <main role="main" class="container">