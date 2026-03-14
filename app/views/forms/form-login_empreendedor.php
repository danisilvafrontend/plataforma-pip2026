<!-- app/views/forms/form-login_empreendedor.php -->
<form action="/empreendedores/login_process.php" method="POST">
    <div class="mb-3">
        <label for="login_empreendedor" class="form-label fw-bold">E-mail de Cadastro</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-envelope text-muted"></i></span>
            <input type="email" class="form-control" id="login_empreendedor" name="email" required placeholder="Digite seu e-mail">
        </div>
    </div>
    
    <div class="mb-3">
        <label for="senha_empreendedor" class="form-label fw-bold">Senha</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-lock text-muted"></i></span>
            <input type="password" class="form-control" id="senha_empreendedor" name="senha" required placeholder="Sua senha">
            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="senha_empreendedor">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="lembrar_empreendedor" name="lembrar">
            <label class="form-check-label text-muted" for="lembrar_empreendedor" style="font-size: 0.9rem;">Lembrar-me</label>
        </div>
        <a href="/../auth/forgot_password_form.php" class="text-decoration-none text-primary" style="font-size: 0.9rem;">Esqueceu a senha?</a>
    </div>
    
    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3">
        <i class="bi bi-box-arrow-in-right me-2"></i>Acessar Painel de Empreendedor
    </button>
    
    <div class="text-center">
        <span class="text-muted" style="font-size: 0.9rem;">Tem um negócio de impacto?</span> 
        <a href="/../../empreendedores/register.php" class="text-decoration-none fw-bold text-primary" style="font-size: 0.9rem;">Inscreva-se</a>
    </div>
</form>
