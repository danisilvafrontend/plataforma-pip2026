<?php
session_start();
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
    $rep_data_nascimento = $_POST['rep_data_nascimento'] ?? ''; // Nova captura da data
    
    // Acesso à Plataforma
    $email_login = trim($_POST['email_login'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';

    // Validação de Idade (Maior de 18 anos)
    $idade = 0;
    if (!empty($rep_data_nascimento)) {
        $nascimento = new DateTime($rep_data_nascimento);
        $hoje = new DateTime();
        $idade = $hoje->diff($nascimento)->y;
    }

    // Validações Básicas
    if (empty($razao_social) || empty($nome_fantasia) || empty($cnpj) || empty($rep_nome) || empty($rep_cpf) || empty($rep_data_nascimento) || empty($email_login) || empty($senha)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif ($idade < 18) {
        $erro = "O representante legal deve ser maior de 18 anos para assinar a carta-acordo.";
    } elseif (!filter_var($email_login, FILTER_VALIDATE_EMAIL)) {
        $erro = "Formato de e-mail inválido.";
    } elseif ($senha !== $senha_confirmar) {
        $erro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 8) {
        $erro = "A senha deve ter pelo menos 8 caracteres.";
    } elseif (!isValidCPF($rep_cpf)) { // Função que você já tem no helpers/functions.php
        $erro = "CPF do representante inválido.";
    } elseif (!isValidCNPJ($cnpj)) { // Assumindo que você tem isValidCNPJ no helpers
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
                    rep_nome, rep_cpf, rep_data_nascimento, rep_email,
                    email_login, senha_hash, 
                    etapa_atual, status, criado_em
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'em_cadastro', NOW())";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $razao_social, $nome_fantasia, $cnpj, 
                    $rep_nome, $rep_cpf, $rep_data_nascimento, $email_login, // Incluindo a data aqui
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

<div class="container py-5 parceiro-reg-shell">
    <div class="parceiro-reg-hero text-center mb-4 mb-lg-5">
        <span class="parceiro-reg-kicker">Cadastro de Parceiros</span>
        <h1 class="parceiro-reg-page-title">Seja um Parceiro da Plataforma</h1>
        <p class="parceiro-reg-page-subtitle">
            Faça parte do nosso ecossistema e ajude a impulsionar negócios de impacto em todo o Brasil.
        </p>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <aside class="parceiro-reg-aside">
                <div class="parceiro-reg-aside-card">
                    <div class="parceiro-reg-aside-title">
                        <i class="bi bi-info-circle-fill"></i>
                        Antes de começar
                    </div>

                    <ul class="parceiro-reg-aside-list">
                        <li>Este é o primeiro passo do cadastro da instituição parceira.</li>
                        <li>O representante legal informado deve ser maior de 18 anos.</li>
                        <li>O CPF do representante e o e-mail de acesso não podem estar vinculados a outro perfil na plataforma.</li>
                        <li>Após esta etapa, você seguirá para o preenchimento dos dados complementares da parceria.</li>
                    </ul>
                </div>

                <div class="parceiro-reg-aside-card parceiro-reg-aside-highlight">
                    <div class="parceiro-reg-aside-title">
                        <i class="bi bi-pen-fill"></i>
                        Carta-acordo
                    </div>
                    <p class="mb-0">
                        O representante legal será a pessoa responsável por assinar a carta-acordo da parceria nas próximas etapas.
                    </p>
                </div>
            </aside>
        </div>

        <div class="col-lg-8">
            <div class="parceiro-reg-card">
                <div class="parceiro-reg-card-header">
                    <div>
                        <h2 class="parceiro-reg-card-title mb-1">Dados iniciais do parceiro</h2>
                        <p class="parceiro-reg-card-subtitle mb-0">
                            Preencha os dados da instituição, do representante legal e do acesso à plataforma.
                        </p>
                    </div>
                </div>

                <div class="parceiro-reg-card-body">
                    <?php if ($erro): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 parceiro-reg-alert" role="alert">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div><?= htmlspecialchars($erro) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" novalidate>
                        <section class="parceiro-reg-section">
                            <div class="parceiro-reg-section-head">
                                <h3 class="parceiro-reg-section-title">Dados da Instituição</h3>
                                <p class="parceiro-reg-section-text">
                                    Informe os dados principais da organização parceira.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label parceiro-reg-label">Razão Social *</label>
                                    <input type="text" name="razao_social" class="form-control" required value="<?= htmlspecialchars($_POST['razao_social'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-reg-label">Nome Fantasia *</label>
                                    <input type="text" name="nome_fantasia" class="form-control" required value="<?= htmlspecialchars($_POST['nome_fantasia'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-reg-label">CNPJ *</label>
                                    <input type="text" name="cnpj" class="form-control cnpj_mask" placeholder="00.000.000/0000-00" required value="<?= htmlspecialchars($_POST['cnpj'] ?? '') ?>">
                                </div>
                            </div>
                        </section>

                        <section class="parceiro-reg-section">
                            <div class="parceiro-reg-section-head">
                                <h3 class="parceiro-reg-section-title">Representante Legal</h3>
                                <p class="parceiro-reg-section-text">
                                    Essa pessoa será responsável por representar a instituição e assinar a carta-acordo.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label parceiro-reg-label">Nome Completo *</label>
                                    <input type="text" name="rep_nome" class="form-control" required value="<?= htmlspecialchars($_POST['rep_nome'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-reg-label">CPF do Representante *</label>
                                    <input type="text" name="rep_cpf" class="form-control cpf_mask" placeholder="000.000.000-00" required value="<?= htmlspecialchars($_POST['rep_cpf'] ?? '') ?>">
                                    <div class="form-text parceiro-reg-help">Um CPF só pode estar vinculado a um perfil na plataforma.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-reg-label">Data de Nascimento *</label>
                                    <input type="date" name="rep_data_nascimento" class="form-control" max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required value="<?= htmlspecialchars($_POST['rep_data_nascimento'] ?? '') ?>">
                                    <div class="form-text parceiro-reg-help">O representante deve ser maior de 18 anos.</div>
                                </div>
                            </div>
                        </section>

                        <section class="parceiro-reg-section">
                            <div class="parceiro-reg-section-head">
                                <h3 class="parceiro-reg-section-title">Acesso à Plataforma</h3>
                                <p class="parceiro-reg-section-text">
                                    Defina o e-mail e a senha que serão usados para acessar o painel do parceiro.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label parceiro-reg-label">E-mail de Acesso *</label>
                                    <input type="email" name="email_login" class="form-control" placeholder="contato@empresa.com.br" required value="<?= htmlspecialchars($_POST['email_login'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-reg-label">Senha *</label>
                                    <input type="password" name="senha" class="form-control" required>
                                    <div class="form-text parceiro-reg-help">Mínimo de 8 caracteres.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-reg-label">Confirmar Senha *</label>
                                    <input type="password" name="senha_confirmar" class="form-control" required>
                                </div>
                            </div>
                        </section>

                        <div class="parceiro-reg-actions">
                            <button type="submit" class="btn-reg-submit">
                                Começar Cadastro de Parceiro
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>

                        <div class="parceiro-reg-login text-center">
                            <span class="text-muted">Já é parceiro?</span>
                            <a href="login.php" class="fw-semibold">Faça login</a>
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
