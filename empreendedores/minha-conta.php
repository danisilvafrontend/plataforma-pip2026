<?php
session_start();

// Verifica se existe sessão de empreendedor
if (empty($_SESSION['empreendedor_id'])) {
    http_response_code(403);
    die("Acesso negado. Faça login como empreendedor.");
}

// Inclui o header específico de empreendedor
include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h2 class="h5 mb-0">Minha Conta</h2>
        </div>
        <div class="card-body">
          <p class="lead">Bem-vindo ao seu painel de empreendedor!</p>
          <p class="text-muted">Aqui você pode gerenciar seus negócios e editar suas informações pessoais.</p>

          <div class="row mt-4">
            <!-- Cadastrar negócio -->
            <div class="col-md-4 mb-3">
              <div class="card h-100">
                <div class="card-body text-center">
                  <h5 class="card-title">Cadastrar Negócio</h5>
                  <p class="card-text">Adicione um novo negócio ao seu perfil.</p>
                  <a href="/empreendedores/cadastrar-negocio.php" class="btn btn-success">Cadastrar</a>
                </div>
              </div>
            </div>

            <!-- Editar conta -->
            <div class="col-md-4 mb-3">
              <div class="card h-100">
                <div class="card-body text-center">
                  <h5 class="card-title">Editar Conta</h5>
                  <p class="card-text">Atualize seus dados pessoais e de acesso.</p>
                  <a href="/empreendedores/editar-conta.php" class="btn btn-warning">Editar</a>
                </div>
              </div>
            </div>

            <!-- Visualizar negócios -->
            <div class="col-md-4 mb-3">
              <div class="card h-100">
                <div class="card-body text-center">
                  <h5 class="card-title">Meus Negócios</h5>
                  <p class="card-text">Veja os negócios que você já cadastrou.</p>
                  <a href="/empreendedores/meus-negocios.php" class="btn btn-info">Visualizar</a>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<?php
// Inclui o footer específico de empreendedor
include __DIR__ . '/../app/views/empreendedor/footer.php';
?>