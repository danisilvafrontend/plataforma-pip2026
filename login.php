<?php
session_start();
$pageTitle = "Login - Impactos Positivos";
include __DIR__ . '/app/views/public/header_public.php';
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h2 class="h5 mb-0">Login</h2>
        </div>
        <div class="card-body">

          <!-- Exibe alerta de erro -->
          <?php if (!empty($_SESSION['login_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= htmlspecialchars($_SESSION['login_error'], ENT_QUOTES, 'UTF-8') ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
            <?php unset($_SESSION['login_error']); ?>
          <?php endif; ?>

          <!-- Nav tabs -->
                    <!-- Nav tabs -->
          <ul class="nav nav-tabs" id="loginTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <!-- Mudei data-bs-target para #pane-empreendedor -->
              <button class="nav-link active" id="empreendedor-tab" data-bs-toggle="tab" data-bs-target="#pane-empreendedor" type="button" role="tab" aria-controls="pane-empreendedor" aria-selected="true">
                Login Negócio de Impacto
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <!-- Mudei data-bs-target para #pane-parceiro -->
              <button class="nav-link" id="parceiro-tab" data-bs-toggle="tab" data-bs-target="#pane-parceiro" type="button" role="tab" aria-controls="pane-parceiro" aria-selected="false">
                Login Parceiro
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <!-- Mudei data-bs-target para #pane-sociedade -->
              <button class="nav-link" id="sociedade-tab" data-bs-toggle="tab" data-bs-target="#pane-sociedade" type="button" role="tab" aria-controls="pane-sociedade" aria-selected="false">
                Login Sociedade Civil
              </button>
            </li>
          </ul>

          <!-- Tab content -->
          <div class="tab-content mt-3" id="loginTabsContent">

            <!-- Empreendedor -->
            <!-- ID alterado para pane-empreendedor -->
            <div class="tab-pane fade show active" id="pane-empreendedor" role="tabpanel" aria-labelledby="empreendedor-tab">
              <?php include __DIR__ . '/app/views/forms/form-login_empreendedor.php'; ?>
            </div>

            <!-- Apoiador -->
            <!-- ID alterado para pane-parceiro -->
            <div class="tab-pane fade" id="pane-parceiro" role="tabpanel" aria-labelledby="parceiro-tab">
              <?php include __DIR__ . '/app/views/forms/form-login_parceiro.php'; ?>
            </div>

            <!-- Comunidade Civil -->
            <!-- ID alterado para pane-sociedade -->
            <div class="tab-pane fade" id="pane-sociedade" role="tabpanel" aria-labelledby="sociedade-tab">
              <?php include __DIR__ . '/app/views/forms/form-login_sociedade.php'; ?>
            </div>

          </div>


        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const triggerTabList = [].slice.call(document.querySelectorAll('#loginTabs button'));
  triggerTabList.forEach(function(triggerEl) {
    triggerEl.addEventListener('click', function(e) {
      e.preventDefault();
      const targetId = this.getAttribute('data-bs-target');
      
      // Remove active de todos os botões e panes
      document.querySelectorAll('#loginTabs button').forEach(btn => {
        btn.classList.remove('active');
        btn.setAttribute('aria-selected', 'false');
      });
      document.querySelectorAll('#loginTabsContent .tab-pane').forEach(pane => {
        pane.classList.remove('show', 'active');
      });
      
      // Adiciona active ao botão e pane clicado
      this.classList.add('active');
      this.setAttribute('aria-selected', 'true');
      document.querySelector(targetId).classList.add('show', 'active');
    });
  });
});
</script>

<!-- Adicione isso no final do seu login.php -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Seleciona todos os botões de visualizar senha
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    
    togglePasswordBtns.forEach(button => {
        button.addEventListener('click', function() {
            // Pega o ID do input de senha através do data-target do botão
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            // Alterna o tipo do input e o ícone
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });
});
</script>


<?php
include __DIR__ . '/app/views/public/footer_public.php';
?>