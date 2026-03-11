<div class="d-flex flex-column flex-shrink-0 p-3 bg-light" >
  <a href="/empreendedores/dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none">
    <span class="fs-6">Área do Empreendedor</span>
  </a>
  <hr>
  <ul class="nav nav-pills flex-column mb-auto">
    <li class="nav-item">
      <a href="/empreendedores/dashboard.php" class="nav-link fs-6 <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
    </li>
    <li>
      <a href="/negocios/etapa1_dados_negocio.php" class="nav-link fs-6 <?= basename($_SERVER['PHP_SELF']) === 'cadastrar_negocio.php' ? 'active' : '' ?>">
        <i class="bi bi-plus-circle"></i> Cadastrar Negócio
      </a>
    </li>
    <li>
      <a href="/empreendedores/meus-negocios.php" class="nav-link fs-6 <?= basename($_SERVER['PHP_SELF']) === 'meus_negocios.php' ? 'active' : '' ?>">
        <i class="bi bi-building"></i> Meus Negócios
      </a>
    </li>
    <li>
      <a href="/empreendedores/editar_conta.php" class="nav-link fs-6 <?= basename($_SERVER['PHP_SELF']) === 'editar_conta.php' ? 'active' : '' ?>">
        <i class="bi bi-person"></i> Editar Conta
      </a>
    </li>
  </ul>
</div>