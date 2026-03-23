<?php
session_start();
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

require_once __DIR__ . '/../app/helpers/functions.php'; // Usa suas funções de validar CPF/CNPJ

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados da Instituição
    $razao_social = trim($_POST['razao_social'] ?? '');
    $nome_fantasia = trim($_POST['nome_fantasia'] ?? '');
    $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? ''); // Limpa a máscara do CNPJ
    
    // Dados do Representante
    $rep_nome = trim($_POST['rep_nome'] ?? '');
    $rep_cpf = preg_replace('/[^0-9]/', '', $_POST['rep_cpf'] ?? ''); // Limpa a máscara do CPF
    
    // Acesso à Plataforma
    $email_login = trim($_POST['email_login'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';

    // Validações Básicas
    if (empty($razao_social) || empty($nome_fantasia) || empty($cnpj) || empty($rep_nome) || empty($rep_cpf) || empty($email_login) || empty($senha)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif (!filter_var($email_login, FILTER_VALIDATE_EMAIL)) {
        $erro = "Formato de e-mail inválido.";
    } elseif ($senha !== $senha_confirmar) {
        $erro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 8) {
        $erro = "A senha deve ter pelo menos 8 caracteres.";
    } elseif (!isValidCPF($rep_cpf)) { // Função que você já tem no helpers/functions.php
        $erro = "CPF do representante inválido.";
    } elseif (!isValidCNPJ($cnpj)) { // Assumindo que você tem isValidCNPJ no helpers, se não tiver, me avise!
        $erro = "CNPJ inválido.";
    } else {
        try {
            // VERIFICAÇÃO CRUZADA DE E-MAIL E CPF/CNPJ
            // O e-mail e o CPF do representante não podem existir nas outras tabelas
            
            // 1. Verifica na tabela PARCEIROS
            $stmt = $pdo->prepare("SELECT id FROM parceiros WHERE email_login = ? OR cnpj = ? OR rep_cpf = ?");
            $stmt->execute([$email_login, $cnpj, $rep_cpf]);
            if ($stmt->fetch()) {
                $erro = "Já existe um Parceiro com este E-mail, CNPJ ou CPF do representante.";
            }
            
            if (!$erro) {
                // 2. Verifica na tabela EMPREENDEDORES
                $stmt = $pdo->prepare("SELECT id FROM empreendedores WHERE email = ? OR cpf = ?");
                $stmt->execute([$email_login, $rep_cpf]);
                if ($stmt->fetch()) {
                    $erro = "Este E-mail ou CPF já está cadastrado como Empreendedor.";
                }
            }

            if (!$erro) {
                // 3. Verifica na tabela SOCIEDADE_CIVIL
                $stmt = $pdo->prepare("SELECT id FROM sociedade_civil WHERE email = ? OR cpf = ?");
                $stmt->execute([$email_login, $rep_cpf]);
                if ($stmt->fetch()) {
                    $erro = "Este E-mail ou CPF já está cadastrado na Sociedade Civil.";
                }
            }

            // SE PASSOU POR TODAS AS VERIFICAÇÕES, INSERE NO BANCO!
            if (!$erro) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                $sql = "INSERT INTO parceiros (
                    razao_social, nome_fantasia, cnpj, 
                    rep_nome, rep_cpf, rep_email,
                    email_login, senha_hash, 
                    etapa_atual, status, criado_em
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'em_cadastro', NOW())";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $razao_social, $nome_fantasia, $cnpj, 
                    $rep_nome, $rep_cpf, $email_login, // Usamos o e-mail de login como e-mail do representante provisoriamente
                    $email_login, $senha_hash
                ]);

                
                $novo_parceiro_id = $pdo->lastInsertId();

                // Faz login automático guardando na sessão
                $_SESSION['parceiro_id'] = $novo_parceiro_id;
                $_SESSION['parceiro_nome'] = $nome_fantasia;
                
                // Redireciona para completar a Etapa 1
                header("Location: etapa1_dados.php");
                exit;
            }
        } catch (PDOException $e) {
            $erro = "Erro no banco de dados: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            
            <div class="text-center mb-4">
                <h2 class="fw-bold text-primary">Seja um Parceiro</h2>
                <p class="text-muted">Faça parte do nosso ecossistema e ajude a impulsionar negócios de impacto.</p>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4 p-md-5">
                    
                    <?php if ($erro): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= htmlspecialchars($erro) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Dados da Instituição</h5>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Razão Social *</label>
                            <input type="text" name="razao_social" class="form-control" required value="<?= htmlspecialchars($_POST['razao_social'] ?? '') ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nome Fantasia *</label>
                                <input type="text" name="nome_fantasia" class="form-control" required value="<?= htmlspecialchars($_POST['nome_fantasia'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold">CNPJ *</label>
                                <input type="text" name="cnpj" class="form-control cnpj_mask" placeholder="00.000.000/0000-00" required value="<?= htmlspecialchars($_POST['cnpj'] ?? '') ?>">
                            </div>
                        </div>

                        <h5 class="fw-bold mb-3 border-bottom pb-2 pt-2">Representante Legal</h5>
                        <p class="small text-muted mb-3">A pessoa que possui poderes para assinar a carta-acordo da parceria.</p>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nome Completo *</label>                                
                                <input type="text" name="rep_nome" class="form-control" required value="<?= htmlspecialchars($_POST['rep_nome'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-semibold">CPF do Representante *</label>
                                <input type="text" name="rep_cpf" class="form-control cpf_mask" placeholder="000.000.000-00" required value="<?= htmlspecialchars($_POST['rep_cpf'] ?? '') ?>">
                                <div class="form-text" style="font-size: 0.7rem;">Um CPF só pode ter um perfil na plataforma.</div>
                            </div>
                        </div>

                        <h5 class="fw-bold mb-3 border-bottom pb-2 pt-2">Acesso à Plataforma</h5>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">E-mail de Acesso *</label>
                            <input type="email" name="email_login" class="form-control" placeholder="contato@empresa.com.br" required value="<?= htmlspecialchars($_POST['email_login'] ?? '') ?>">
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label fw-semibold">Senha *</label>
                                <input type="password" name="senha" class="form-control" required>
                                <div class="form-text">Mínimo 8 caracteres.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirmar Senha *</label>
                                <input type="password" name="senha_confirmar" class="form-control" required>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">Começar Cadastro de Parceiro</button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <span class="text-muted">Já é parceiro?</span> <a href="login.php" class="text-decoration-none fw-semibold">Faça Login</a>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- Scripts de Máscara de CPF e CNPJ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    $(document).ready(function(){
        $('.cnpj_mask').mask('00.000.000/0000-00', {reverse: true});
        $('.cpf_mask').mask('000.000.000-00', {reverse: true});
    });
</script>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
