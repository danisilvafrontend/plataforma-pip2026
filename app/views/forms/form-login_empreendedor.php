<?php if (!empty($_SESSION['login_error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($_SESSION['login_error'], ENT_QUOTES, 'UTF-8') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
  </div>
  <?php unset($_SESSION['login_error']); ?>
<?php endif; ?>

<form method="post" action="/empreendedores/login_process.php">
  <div class="mb-3">
    <label class="form-label">E-mail</label>
    <input type="email" name="email" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Senha</label>
    <input type="password" name="senha" class="form-control" required>
  </div>
  <div class="mb-3">
    <p class="text-end mt-2">
      <a href="/../auth/forgot_password_form.php" class="link-secondary">Esqueci minha senha</a>
    </p>
  </div>
  <div class="row mb-3">
    <div class="col-6">      
      <button type="submit" class="btn btn-success w-100">Entrar como Empreendedor</button>
    </div>    
    <div class="col-6">      
      <a href="/../../empreendedores/register.php" type="submit" class="btn btn-success w-100">Cadastrar</a>
    </div>  
  </div>   
</form>