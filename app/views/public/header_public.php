<?php
// header_public.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($pageTitle)) $pageTitle = 'Impactos Positivos';

$is_logged_in    = false;
$user_name       = 'Usuário';
$user_role_label = '';
$dashboard_link  = '#';
$conta_link      = '#';
$is_admin        = false;

try {
    $role = $_SESSION['user_role'] ?? $_SESSION['usuario_tipo'] ?? null;

    if (isset($_SESSION['user_id']) && in_array($role, ['admin', 'superadmin'])) {
        $is_logged_in    = true;
        $is_admin        = true;
        $dashboard_link  = '/admin/dashboard.php';
        $user_name       = $_SESSION['user_name'] ?? 'Admin';
        $user_role_label = 'Administrador';

    } elseif (isset($_SESSION['user_id']) && $role === 'empreendedor') {
        $is_logged_in    = true;
        $user_role_label = 'Empreendedor';
        $dashboard_link  = '/empreendedores/dashboard.php';
        $conta_link      = '/empreendedores/editar_conta.php';
        $stmt = $pdo->prepare("SELECT nome FROM empreendedores WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $emp       = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_name = $emp['nome'] ?? $_SESSION['empreendedor_nome'] ?? 'Empreendedor';

    } elseif (isset($_SESSION['parceiro_id'])) {
        $is_logged_in    = true;
        $user_role_label = 'Parceiro';
        $dashboard_link  = '/parceiros/dashboard.php';
        $conta_link      = '/parceiros/editar_dados.php';
        $stmt = $pdo->prepare("SELECT nome_fantasia FROM parceiros WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['parceiro_id']]);
        $par       = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_name = $par['nome_fantasia'] ?? $_SESSION['parceiro_nome'] ?? 'Parceiro';

    } elseif (
        !empty($_SESSION['logado']) &&
        ($_SESSION['usuario_tipo'] ?? '') === 'sociedade_civil' &&
        !empty($_SESSION['usuario_id'])
    ) {
        $is_logged_in    = true;
        $user_role_label = 'Sociedade Civil';
        $dashboard_link  = '/sociedade_civil/minha_conta.php';
        $conta_link      = '/sociedade_civil/editar_conta.php';
        $user_name       = $_SESSION['usuario_nome'] ?? 'Membro';
    }

} catch (Exception $e) {
    $user_name = 'Usuário';
}
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
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/style.css') ?>">
  <link rel="stylesheet" href="/negocios/blocos-cadastros/assets/blocos.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/negocios/blocos-cadastros/assets/blocos.css') ?>">
  <link rel="icon" href="/assets/favicon.ico" type="image/x-icon">
  <meta name="theme-color" content="#1E3425">

  <?php if (!empty($extraHead ?? null)) echo $extraHead; ?>
  <!-- Google tag (gtag.js) -->
  <!-- <script async src="https://www.googletagmanager.com/gtag/js?id=G-QB72YGX3EV"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-QB72YGX3EV');
  </script> -->
</head>
<body>
<div class="gtranslate_wrapper"></div>
<script>window.gtranslateSettings = {"default_language":"pt","native_language_names":true,"languages":["pt","es","en"],"wrapper_selector":".gtranslate_wrapper","flag_size":24,"horizontal_position":"right","vertical_position":"top","alt_flags":{"en":"usa","pt":"brazil"}}</script>
<script src="https://cdn.gtranslate.net/widgets/latest/flags.js" defer></script>
<header class="site-header">
  <nav class="navbar navbar-expand-lg container">

    <a class="navbar-brand d-flex align-items-center" href="https://impactospositivos.com/">
      <img src="/../assets/images/impactos_positivos.png" alt="Impactos Positivos" style="height:54px;">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#mainNav" aria-controls="mainNav"
            aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-1">
        <li class="nav-item">
          <a class="nav-link" href="/">
            <i class="bi bi-house"></i> Início
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/vitrine_nacional.php">
            <i class="bi bi-grid me-1"></i> Vitrine Nacional
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/premiacao.php">
            <i class="bi bi-trophy-fill me-1"></i> Premiação 2026
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/parceiros.php">
            <i class="bi bi-diagram-3"></i> Parceiros
          </a>
        </li>

        <?php if ($is_logged_in): ?>
          <?php if ($is_admin): ?>
            <li class="nav-item ms-2">
              <a class="btn-header-admin" href="<?= $dashboard_link ?>">
                <i class="bi bi-gear me-1"></i> Acessar Painel
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item dropdown ms-2">
              <a class="nav-link dropdown-toggle user-avatar-pill" href="#"
                 id="navbarUser" role="button"
                 data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-1"></i>
                <?= htmlspecialchars($user_name) ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUser">
                <li class="px-3 py-2" style="color:#9aab9d; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">
                  <?= $user_role_label ?>
                </li>
                <li><a class="dropdown-item" href="<?= $dashboard_link ?>">
                  <i class="bi bi-grid-1x2 me-2"></i> Meu Painel
                </a></li>
                <?php if ($role === 'empreendedor'): ?>
                  <li><a class="dropdown-item" href="/empreendedores/meus-negocios.php">
                    <i class="bi bi-briefcase me-2"></i> Meus Negócios
                  </a></li>
                <?php endif; ?>
                <li><a class="dropdown-item" href="<?= $conta_link ?>">
                  <i class="bi bi-person-vcard me-2"></i> Meus Dados
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="/logout.php">
                  <i class="bi bi-box-arrow-right me-2"></i> Sair
                </a></li>
              </ul>
            </li>
          <?php endif; ?>
        <?php else: ?>
          <li class="nav-item ms-2">
            <a class="btn-header-login" href="/login.php">
              <i class="bi bi-person me-1"></i> Entrar / Cadastre-se
            </a>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </nav>
</header>

<main role="main" class="container">