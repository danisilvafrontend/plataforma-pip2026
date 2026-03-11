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
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="/../assets/css/style.css">
  <!-- CSS dos blocos -->
  <link rel="stylesheet" href="/negocios/blocos-cadastros/assets/blocos.css">
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
          <li class="nav-item"><a class="nav-link btn btn-primary btn-sm ms-2" href="/empreendedores/dashboard.php">Minha Conta</a></li>
          <?php else: ?>
          <li class="nav-item"><a class="nav-link btn btn-outline-primary btn-sm ms-2" href="/login.php">Login</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </nav>
  </header>

  <main role="main" class="container">