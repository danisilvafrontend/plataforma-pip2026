<form action="/auth/processar_login_sociedade.php" method="post">
  <div class="mb-3">
    <label class="form-label">Email ou CPF</label>
    <input type="text" name="login" class="form-control" required>
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
      <button type="submit" class="btn btn-success w-100">Entrar</button>
    </div>    
    <div class="col-6">      
      <a href="/cadastro.php" type="submit" class="btn btn-success w-100">Cadastrar</a>
    </div>   
  </div>   
</form>