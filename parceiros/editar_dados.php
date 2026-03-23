<?php
session_start();
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Verifica login
if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];
$mensagem = '';
$tipo_msg = '';

// PROCESSAMENTO DO FORMULÁRIO DE EDIÇÃO (Se for POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados Institucionais
    $nome_fantasia = trim($_POST['nome_fantasia'] ?? '');
    $razao_social = trim($_POST['razao_social'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $endereco_completo = trim($_POST['endereco_completo'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $telefone_institucional = trim($_POST['telefone_institucional'] ?? '');
    $site = trim($_POST['site'] ?? '');
    $linkedin_institucional = trim($_POST['linkedin_institucional'] ?? '');
    
    // Representante Legal
    $rep_nome = trim($_POST['rep_nome'] ?? '');
    $rep_cargo = trim($_POST['rep_cargo'] ?? '');
    $rep_email = trim($_POST['rep_email'] ?? '');
    $rep_telefone = trim($_POST['rep_telefone'] ?? '');
    $rep_email_optin = isset($_POST['rep_email_optin']) ? 1 : 0;
    $rep_whatsapp_optin = isset($_POST['rep_whatsapp_optin']) ? 1 : 0;
    
    // Contato Operacional
    $op_nome = trim($_POST['op_nome'] ?? '');
    $op_cargo = trim($_POST['op_cargo'] ?? '');
    $op_email = trim($_POST['op_email'] ?? '');
    $op_telefone = trim($_POST['op_telefone'] ?? '');
    $op_email_optin = isset($_POST['op_email_optin']) ? 1 : 0;
    $op_whatsapp_optin = isset($_POST['op_whatsapp_optin']) ? 1 : 0;

    // Validação básica
    if (empty($nome_fantasia) || empty($razao_social) || empty($rep_nome) || empty($rep_email)) {
        $mensagem = "Preencha os campos obrigatórios (Nome Fantasia, Razão Social e Nome/Email do Representante).";
        $tipo_msg = "danger";
    } else {
        try {
            $sql = "UPDATE parceiros SET 
                    nome_fantasia = ?, razao_social = ?, cep = ?, endereco_completo = ?, 
                    cidade = ?, estado = ?, telefone_institucional = ?, site = ?, linkedin_institucional = ?,
                    rep_nome = ?, rep_cargo = ?, rep_email = ?, rep_telefone = ?, rep_email_optin = ?, rep_whatsapp_optin = ?,
                    op_nome = ?, op_cargo = ?, op_email = ?, op_telefone = ?, op_email_optin = ?, op_whatsapp_optin = ?
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nome_fantasia, $razao_social, $cep, $endereco_completo, 
                $cidade, $estado, $telefone_institucional, $site, $linkedin_institucional,
                $rep_nome, $rep_cargo, $rep_email, $rep_telefone, $rep_email_optin, $rep_whatsapp_optin,
                $op_nome, $op_cargo, $op_email, $op_telefone, $op_email_optin, $op_whatsapp_optin,
                $parceiro_id
            ]);

            // Atualiza o nome na sessão
            $_SESSION['parceiro_nome'] = $nome_fantasia;

            $mensagem = "Perfil atualizado com sucesso!";
            $tipo_msg = "success";

        } catch (PDOException $e) {
            $mensagem = "Erro ao atualizar perfil. Tente novamente.";
            $tipo_msg = "danger";
            error_log("Erro em editar_perfil_parceiro: " . $e->getMessage());
        }
    }
}

// BUSCA OS DADOS ATUAIS PARA PREENCHER O FORMULÁRIO
$stmt = $pdo->prepare("SELECT * FROM parceiros WHERE id = ?");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parceiro) {
    die("Parceiro não encontrado.");
}

include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-lg-3 col-md-4 mb-4 mb-md-0">
            <?php include __DIR__ . '/../app/views/parceiros/sidebar.php'; ?>
        </div>

        <!-- CONTEÚDO PRINCIPAL -->
        <div class="col-lg-9 col-md-8">            
    
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">Editar Perfil</h2>
                    <p class="text-muted mb-0">Atualize as informações de contato da sua organização.</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i> Voltar ao Painel</a>
            </div>

            <?php if ($mensagem): ?>
                <div class="alert alert-<?= $tipo_msg ?> alert-dismissible fade show" role="alert">
                    <i class="bi <?= $tipo_msg == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                    <?= htmlspecialchars($mensagem) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-body p-4 p-md-5">
                        
                        <!-- DADOS INSTITUCIONAIS -->
                        <h5 class="fw-bold mb-4 border-bottom pb-2 text-primary"><i class="bi bi-building me-2"></i> Dados da Instituição</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nome Fantasia *</label>
                                <input type="text" name="nome_fantasia" class="form-control" required value="<?= htmlspecialchars($parceiro['nome_fantasia'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Razão Social *</label>
                                <input type="text" name="razao_social" class="form-control" required value="<?= htmlspecialchars($parceiro['razao_social'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-muted">CNPJ (Não editável)</label>
                                <input type="text" class="form-control bg-light" readonly value="<?= htmlspecialchars($parceiro['cnpj'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Telefone Institucional</label>
                                <input type="text" name="telefone_institucional" class="form-control phone_mask" value="<?= htmlspecialchars($parceiro['telefone_institucional'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Site</label>
                                <input type="url" name="site" class="form-control" value="<?= htmlspecialchars($parceiro['site'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">LinkedIn Institucional</label>
                            <input type="url" name="linkedin_institucional" class="form-control" value="<?= htmlspecialchars($parceiro['linkedin_institucional'] ?? '') ?>">
                        </div>

                        <!-- ENDEREÇO -->
                        <h6 class="fw-bold mt-4 mb-3 text-secondary">Endereço Institucional</h6>
                        
                        <div class="row">
                            <!-- IDs alinhados com o scripts.js global para o ViaCEP -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">CEP</label>
                                <input type="text" name="cep" id="cep" class="form-control" value="<?= htmlspecialchars($parceiro['cep'] ?? '') ?>">
                            </div>
                            <div class="col-md-9 mb-3">
                                <label class="form-label fw-semibold">Endereço Completo</label>
                                <input type="text" name="endereco_completo" id="rua" class="form-control" value="<?= htmlspecialchars($parceiro['endereco_completo'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-5">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Cidade</label>
                                <input type="text" name="cidade" id="municipio" class="form-control" value="<?= htmlspecialchars($parceiro['cidade'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Estado (UF)</label>
                                <input type="text" name="estado" id="estado" class="form-control" value="<?= htmlspecialchars($parceiro['estado'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- REPRESENTANTE LEGAL -->
                        <h5 class="fw-bold mb-4 border-bottom pb-2 text-primary"><i class="bi bi-person-badge me-2"></i> Representante Legal</h5>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nome Completo *</label>
                                <input type="text" name="rep_nome" class="form-control" required value="<?= htmlspecialchars($parceiro['rep_nome'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Cargo</label>
                                <input type="text" name="rep_cargo" class="form-control" value="<?= htmlspecialchars($parceiro['rep_cargo'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">E-mail do Representante *</label>
                                <input type="email" name="rep_email" class="form-control" required value="<?= htmlspecialchars($parceiro['rep_email'] ?? '') ?>">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="rep_email_optin" name="rep_email_optin" value="1" <?= (!empty($parceiro['rep_email_optin'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small text-muted" for="rep_email_optin">Aceito receber atualizações via e-mail</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Telefone / Celular</label>
                                <input type="text" name="rep_telefone" class="form-control phone_mask" value="<?= htmlspecialchars($parceiro['rep_telefone'] ?? '') ?>">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="rep_whatsapp_optin" name="rep_whatsapp_optin" value="1" <?= (!empty($parceiro['rep_whatsapp_optin'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small text-muted" for="rep_whatsapp_optin">Aceito receber novidades via WhatsApp</label>
                                </div>
                            </div>
                        </div>

                        <!-- CONTATO OPERACIONAL -->
                        <h5 class="fw-bold mb-4 border-bottom pb-2 text-primary mt-5"><i class="bi bi-person-workspace me-2"></i> Contato Operacional</h5>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="mesmo_contato">
                            <label class="form-check-label text-muted" for="mesmo_contato">
                                Copiar dados do Representante Legal
                            </label>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nome Operacional</label>
                                <input type="text" name="op_nome" id="op_nome" class="form-control" value="<?= htmlspecialchars($parceiro['op_nome'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Cargo</label>
                                <input type="text" name="op_cargo" id="op_cargo" class="form-control" value="<?= htmlspecialchars($parceiro['op_cargo'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">E-mail</label>
                                <input type="email" name="op_email" id="op_email" class="form-control" value="<?= htmlspecialchars($parceiro['op_email'] ?? '') ?>">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="op_email_optin" name="op_email_optin" value="1" <?= (!empty($parceiro['op_email_optin'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small text-muted" for="op_email_optin">Aceito receber atualizações via e-mail</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Telefone</label>
                                <input type="text" name="op_telefone" id="op_telefone" class="form-control phone_mask" value="<?= htmlspecialchars($parceiro['op_telefone'] ?? '') ?>">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="op_whatsapp_optin" name="op_whatsapp_optin" value="1" <?= (!empty($parceiro['op_whatsapp_optin'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small text-muted" for="op_whatsapp_optin">Aceito receber novidades via WhatsApp</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-5 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold"><i class="bi bi-floppy me-2"></i> Salvar Alterações</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>




<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    $(document).ready(function(){
        // Máscara de telefone
        var SPMaskBehavior = function (val) {
            return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
        },
        spOptions = {
            onKeyPress: function(val, e, field, options) {
                field.mask(SPMaskBehavior.apply({}, arguments), options);
            }
        };
        $('.phone_mask').mask(SPMaskBehavior, spOptions);

        // Copiar dados do Representante para Operacional
        $('#mesmo_contato').change(function() {
            if($(this).is(':checked')) {
                $('#op_nome').val($('input[name="rep_nome"]').val());
                $('#op_cargo').val($('input[name="rep_cargo"]').val());
                $('#op_email').val($('input[name="rep_email"]').val());
                $('#op_telefone').val($('input[name="rep_telefone"]').val());
                
                $('#op_email_optin').prop('checked', $('#rep_email_optin').is(':checked'));
                $('#op_whatsapp_optin').prop('checked', $('#rep_whatsapp_optin').is(':checked'));
            } else {
                $('#op_nome').val('');
                $('#op_cargo').val('');
                $('#op_email').val('');
                $('#op_telefone').val('');
                
                $('#op_email_optin').prop('checked', false);
                $('#op_whatsapp_optin').prop('checked', false);
            }
        });
    });
</script>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
