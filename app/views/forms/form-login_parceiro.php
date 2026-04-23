<!-- O formulário de login para o Parceiro -->
<form action="/parceiros/processar_login_parceiro.php" method="POST">
    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? '') ?>">
    <div class="mb-3">
        <label for="login_parceiro" class="form-label fw-bold">E-mail Institucional ou CNPJ</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-briefcase text-muted"></i></span>
            <input type="text" class="form-control" id="login_parceiro" name="login" required placeholder="Digite seu e-mail ou CNPJ">
        </div>
    </div>
    <div class="mb-3">
        <label for="senha_parceiro" class="form-label fw-bold">Senha</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-lock text-muted"></i></span>
            <input type="password" class="form-control" id="senha_parceiro" name="senha" required placeholder="Sua senha">
            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="senha_parceiro">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="lembrar_parceiro">
            <label class="form-check-label text-muted" for="lembrar_parceiro" style="font-size: 0.9rem;">Lembrar-me</label>
        </div>
        <a href="/../auth/forgot_password_form.php" class="text-decoration-none text-primary" style="font-size: 0.9rem;">Esqueceu a senha?</a>
    </div>
    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3">
        <i class="bi bi-box-arrow-in-right me-2"></i>Acessar Painel do Parceiro
    </button>
    <div class="text-center">
        <span class="text-muted" style="font-size: 0.9rem;">Sua empresa não é parceira?</span> 
        <a href="/parceiros/cadastro.php" class="text-decoration-none fw-bold text-primary" style="font-size: 0.9rem;">Cadastre-se</a>
    </div>
</form>
