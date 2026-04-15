<?php
// /app/views/empreendedor/menu-lateral.php
$currentPage = $_SERVER['PHP_SELF'];

// Grupos com submenus ativos
$isNegociosActive = str_contains($currentPage, 'meus-negocios') 
                 || str_contains($currentPage, 'etapa') 
                 || str_contains($currentPage, 'confirmacao')
                 || str_contains($currentPage, 'premiacao')
                 || str_contains($currentPage, 'editar_etapa');
?>

<aside class="emp-sidebar" id="empSidebar">

  <!-- Logo / título da área -->
  <div class="emp-sidebar-brand">
    <img src="/assets/images/impactos_positivos_branco.png" alt="Impactos Positivos" style="height:38px;">
    <span class="emp-sidebar-area-label">Área do Empreendedor</span>
  </div>

  <!-- ── Principal ── -->
  <span class="emp-nav-group-label">Principal</span>
  <ul class="nav flex-column mb-1">
    <li class="nav-item">
      <a class="emp-sidebar-link <?= str_contains($currentPage, 'dashboard') ? 'active' : '' ?>"
         href="/empreendedores/dashboard.php">
        <i class="bi bi-grid-fill"></i> Dashboard
      </a>
    </li>
  </ul>

  <!-- ── Negócios ── -->
  <span class="emp-nav-group-label">Negócios</span>
  <ul class="nav flex-column mb-1">

    <!-- Meus Negócios (dropdown) -->
    <li class="nav-item">
      <a class="emp-sidebar-link <?= $isNegociosActive ? 'active' : '' ?>"
         href="#negociosSubmenu"
         data-bs-toggle="collapse"
         role="button"
         aria-expanded="<?= $isNegociosActive ? 'true' : 'false' ?>"
         aria-controls="negociosSubmenu">
        <i class="bi bi-briefcase-fill"></i> Meus Negócios
        <i class="bi bi-chevron-down emp-chevron"></i>
      </a>
      <div class="collapse <?= $isNegociosActive ? 'show' : '' ?>" id="negociosSubmenu">
        <ul class="nav flex-column emp-submenu">
          <li>
            <a class="nav-link <?= str_contains($currentPage, 'meus-negocios') ? 'active' : '' ?>"
               href="/empreendedores/meus-negocios.php">
              <i class="bi bi-list-ul"></i> Listar Negócios
            </a>
          </li>
          <li>
            <a class="nav-link <?= str_contains($currentPage, 'etapa1') ? 'active' : '' ?>"
               href="/negocios/etapa1_dados_negocio.php">
              <i class="bi bi-plus-circle"></i> Cadastrar Novo
            </a>
          </li>          
          <li>
            <a class="nav-link <?= str_contains($currentPage, 'premiacao') ? 'active' : '' ?>"
               href="/empreendedores/minhas_inscricoes_premiacao.php">
              <i class="bi bi-trophy"></i> Minhas Inscrições Premiação
            </a>
          </li>
        </ul>
      </div>
    </li>

    <li class="nav-item">
      <a class="emp-sidebar-link <?= str_contains($currentPage, 'vitrine') ? 'active' : '' ?>"
         href="/vitrine_nacional.php">
        <i class="bi bi-grid"></i> Vitrine Nacional
      </a>
    </li>

  </ul>

  <!-- ── Minha Conta ── -->
  <span class="emp-nav-group-label">Minha Conta</span>
  <ul class="nav flex-column mb-1">
    
    <li class="nav-item">
      <a class="emp-sidebar-link <?= str_contains($currentPage, 'editar_perfil') ? 'active' : '' ?>"
         href="/empreendedores/editar_conta.php">
        <i class="bi bi-pencil-square"></i> Editar Perfil
      </a>
    </li>
  </ul>

  <!-- ── Rodapé ── -->
  <div class="emp-sidebar-footer">
    <a href="/../../logout.php" class="emp-sidebar-logout">
      <i class="bi bi-box-arrow-right me-1"></i> Sair
    </a>
  </div>

</aside>