<?php
// /app/views/empreendedor/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($pageTitle)) $pageTitle = 'Impactos Positivos';

$emp_nome = $_SESSION['empreendedor_nome'] ?? 'Empreendedor';
try {
    if (!empty($_SESSION['user_id']) && isset($pdo)) {
        $stmt = $pdo->prepare("SELECT nome FROM empreendedores WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        $emp_nome = $emp['nome'] ?? $emp_nome;
    }
} catch (Exception $e) {}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/css/style.css') ?: time() ?>">
  <link rel="stylesheet" href="/assets/css/empreendedor.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/css/empreendedor.css') ?: time() ?>">
  <!-- CSS dos blocos -->
  <link rel="stylesheet" href="/negocios/blocos-cadastros/assets/blocos.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/negocios/blocos-cadastros/assets/blocos.css') ?: time() ?>"> 
  <link rel="icon" href="/assets/favicon.ico" type="image/x-icon">
  <meta name="theme-color" content="#1E3425">

  <?php if (!empty($extraHead ?? null)) echo $extraHead; ?>
</head>
<body>
<div class="gtranslate_wrapper"></div>
<script>window.gtranslateSettings = {"default_language":"pt","native_language_names":true,"languages":["pt","es","en"],"wrapper_selector":".gtranslate_wrapper","flag_size":24,"horizontal_position":"right","vertical_position":"top","alt_flags":{"en":"usa","pt":"brazil"}}</script>
<script src="https://cdn.gtranslate.net/widgets/latest/flags.js" defer></script>
<!-- ── Layout wrapper ── -->
<div class="emp-layout">

  <!-- Sidebar -->
  <?php include __DIR__ . '/menu_lateral.php'; ?>

  <!-- Conteúdo principal -->
  <div class="emp-content">

    <!-- Topbar -->
    <div class="emp-topbar">
      <!-- Botão toggle mobile -->
      <button class="emp-topbar-toggle d-lg-none" id="empSidebarToggle" type="button">
        <i class="bi bi-list"></i>
      </button>

      <!-- Breadcrumb / título da página atual -->
      <div class="emp-topbar-title">
        <?= $pageTitle ?? 'Painel' ?>
      </div>

      <!-- Usuário -->
      <div class="dropdown ms-auto">
        <a class="emp-user-pill dropdown-toggle" href="#"
           id="empTopUser" role="button"
           data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-person-circle me-1"></i>
          <?= htmlspecialchars($emp_nome) ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end emp-dropdown" aria-labelledby="empTopUser">
          <li class="px-3 py-2 emp-dropdown-role">Empreendedor</li>
          <li><a class="dropdown-item emp-dropdown-item" href="/empreendedores/dashboard.php">
            <i class="bi bi-grid-1x2 me-2"></i> Dashboard
          </a></li>
          <li><a class="dropdown-item emp-dropdown-item" href="/empreendedores/meus-negocios.php">
            <i class="bi bi-briefcase me-2"></i> Meus Negócios
          </a></li>
          <li><a class="dropdown-item emp-dropdown-item" href="/empreendedores/editar_conta.php">
            <i class="bi bi-person-vcard me-2"></i> Meus Dados
          </a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item emp-dropdown-item text-danger" href="/logout.php">
            <i class="bi bi-box-arrow-right me-2"></i> Sair
          </a></li>
        </ul>
      </div>
    </div>
    <!-- /Topbar -->

    <!-- Conteúdo da página começa aqui -->
    <div class="emp-inner">