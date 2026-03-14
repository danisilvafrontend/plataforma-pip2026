<?php
session_start();
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Busca os dados iniciais
$stmt = $pdo->prepare("SELECT razao_social, nome_fantasia, cnpj, rep_nome, rep_cpf, email_login FROM parceiros WHERE id = ?");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

// Se houver erro de validação vindo do processador
$erro = $_SESSION['erro_editar_cadastro'] ?? '';
unset($_SESSION['erro_editar_cadastro']);

$from = $_GET['from'] ?? 'confirmacao'; // Padrão é voltar pra confirmação

include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Editar Dados Cadastrais Iniciais</h3>
                <a href="<?= htmlspecialchars($from) ?>.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i> Voltar</a>
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="processar_editar_cadastro.php">
                        <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">

                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Organização</h5>
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Razão Social</label>
                                <input type="text" name="razao_social" class="form-control" value="<?= htmlspecialchars($parceiro['razao_social']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nome Fantasia</label>
                                <input type="text" name="nome_fantasia" class="form-control" value="<?= htmlspecialchars($parceiro['nome_fantasia']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">CNPJ</label>
                                <input type="text" name="cnpj" class="form-control cnpj-mask" value="<?= htmlspecialchars($parceiro['cnpj']) ?>" required>
                            </div>
                        </div>

                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Representante Legal e Acesso</h5>
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nome do Representante</label>
                                <input type="text" name="rep_nome" class="form-control" value="<?= htmlspecialchars($parceiro['rep_nome']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">CPF do Representante</label>
                                <input type="text" name="rep_cpf" class="form-control cpf-mask" value="<?= htmlspecialchars($parceiro['rep_cpf']) ?>" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold">E-mail de Login</label>
                                <input type="email" name="email_login" class="form-control" value="<?= htmlspecialchars($parceiro['email_login']) ?>" required>
                                <small class="text-muted">Este é o e-mail usado para acessar o painel.</small>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-4">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Opcional: Inserir scripts de máscara aqui (jQuery Mask) se não estiver no footer -->
<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
