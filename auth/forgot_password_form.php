<?php
// auth/forgot_password_form.php
session_start();
include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container d-flex flex-column min-vh-100">
  <div class="row justify-content-center flex-grow-1">
    <div class="col-md-6">
      <div class="card shadow-sm mt-5">
        <div class="card-header bg-primary text-white">
          <h2 class="h5 mb-0">Recuperar senha</h2>
        </div>
        <div class="card-body">
          <p>Informe o e‑mail cadastrado para receber instruções de redefinição.</p>
          <form method="post" action="/auth/forgot_password.php">
            <div class="mb-3">
              <label for="email" class="form-label">E‑mail</label>
              <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success">Enviar instruções</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>