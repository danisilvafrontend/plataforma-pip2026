

<!-- app/views/forms/form-login_sociedade.php -->
<form action="/auth/processar_login_sociedade.php" method="POST">
    <div class="mb-3">
        <label for="login_sociedade" class="form-label fw-bold">E-mail ou CPF</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-person text-muted"></i></span>
            <input type="text" class="form-control" id="login_sociedade" name="login" required placeholder="Digite seu e-mail ou CPF">
        </div>
    </div>
    
    <div class="mb-3">
        <label for="senha_sociedade" class="form-label fw-bold">Senha</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-lock text-muted"></i></span>
            <input type="password" class="form-control" id="senha_sociedade" name="senha" required placeholder="Sua senha">
            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="senha_sociedade">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="lembrar_sociedade" name="lembrar">
            <label class="form-check-label text-muted" for="lembrar_sociedade" style="font-size: 0.9rem;">Lembrar-me</label>
        </div>
        <a href="/../auth/forgot_password_form.php" class="text-decoration-none text-primary" style="font-size: 0.9rem;">Esqueceu a senha?</a>
    </div>
    
    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3">
        <i class="bi bi-box-arrow-in-right me-2"></i>Acessar Minha Conta
    </button>
    
    <div class="text-center">
        <span class="text-muted" style="font-size: 0.9rem;">Ainda não faz parte?</span> 
        <a href="/cadastro.php" class="text-decoration-none fw-bold text-primary" style="font-size: 0.9rem;">Cadastre-se</a>
    </div>
</form>
