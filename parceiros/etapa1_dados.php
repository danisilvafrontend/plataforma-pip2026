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

// Verifica se o parceiro está logado
if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php?msg=login_necessario");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Busca os dados atuais do parceiro para preencher o formulário
$stmt = $pdo->prepare("SELECT * FROM parceiros WHERE id = ?");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parceiro) {
    die("Parceiro não encontrado.");
}

include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Progresso -->
            <div class="mb-4">
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span class="fw-bold text-primary">Etapa 1: Dados Complementares</span>
                    <span>1 de 6</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 16%;" aria-valuenow="16" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold text-dark mb-1">Complete o seu Perfil</h3>
                    <p class="text-muted mb-4">Essas informações são importantes para a formalização da nossa parceria e geração automática do seu contrato.</p>

                    <?php if (isset($_SESSION['erro_etapa1'])): ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= htmlspecialchars($_SESSION['erro_etapa1']) ?></div>
                        </div>
                        <?php unset($_SESSION['erro_etapa1']); ?>
                    <?php endif; ?>

                    <form method="POST" action="processar_etapa1.php">
                    <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
    
                        <!-- DADOS DA INSTITUIÇÃO (Endereço) -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Endereço e Contato Institucional</h5>
                        
                        <div class="row">
                            <!-- Os IDs aqui devem ser os mesmos que o seu scripts.js está esperando -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">CEP</label>
                                <input type="text" name="cep" id="cep" class="form-control" value="<?= htmlspecialchars($parceiro['cep'] ?? '') ?>">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-semibold">Rua / Logradouro</label>
                                <input type="text" name="endereco_completo" id="rua" class="form-control" value="<?= htmlspecialchars($parceiro['endereco_completo'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Cidade</label>
                                <input type="text" name="cidade" id="municipio" class="form-control" value="<?= htmlspecialchars($parceiro['cidade'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Estado (UF)</label>
                                <input type="text" name="estado" id="estado" class="form-control" value="<?= htmlspecialchars($parceiro['estado'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">País</label>
                                <input type="text" name="pais" class="form-control" value="<?= htmlspecialchars($parceiro['pais'] ?? 'Brasil') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Telefone Institucional</label>
                                <input type="text" name="telefone_institucional" class="form-control phone_mask" value="<?= htmlspecialchars($parceiro['telefone_institucional'] ?? '') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Site</label>
                                <input type="url" name="site" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($parceiro['site'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- REPRESENTANTE LEGAL COMPLEMENTO -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">Representante Legal</h5>
                        <p class="small text-muted mb-3">A pessoa que possui poderes para assinar a carta-acordo da parceria.</p>

                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Cargo</label>
                                <input type="text" name="rep_cargo" class="form-control" value="<?= htmlspecialchars($parceiro['rep_cargo'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">E-mail do Representante</label>
                                <input type="email" name="rep_email" class="form-control" value="<?= htmlspecialchars($parceiro['rep_email'] ?? '') ?>">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="rep_email_optin" name="rep_email_optin" value="1" <?= (!empty($parceiro['rep_email_optin'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small text-muted" for="rep_email_optin">Aceito receber atualizações via e-mail</label>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Telefone / Celular</label>
                                <input type="text" name="rep_telefone" class="form-control phone_mask" value="<?= htmlspecialchars($parceiro['rep_telefone'] ?? '') ?>">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="rep_whatsapp_optin" name="rep_whatsapp_optin" value="1" <?= (!empty($parceiro['rep_whatsapp_optin'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label small text-muted" for="rep_whatsapp_optin">Aceito receber novidades via WhatsApp</label>
                                </div>
                            </div>
                        </div>

                        <!-- CONTATO OPERACIONAL -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">Contato Operacional</h5>
                        <p class="small text-muted mb-3">Quem vai operar a plataforma no dia a dia (pode ser a mesma pessoa).</p>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="mesmo_contato">
                            <label class="form-check-label" for="mesmo_contato">
                                O contato operacional é o mesmo que o Representante Legal
                            </label>
                        </div>

                        <div class="row mb-4" id="bloco_operacional">
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


                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <?php if (($_GET['from'] ?? '') === 'confirmacao'): ?>
                                <button type="submit" name="acao" value="confirmacao" class="btn btn-outline-primary">
                                    Salvar e voltar à revisão
                                </button>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                Salvar e continuar
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Somente máscara de telefone e script do checkbox (o ViaCEP já vem do scripts.js global) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    $(document).ready(function(){
        // Máscara inteligente para telefone (8 ou 9 dígitos)
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
                $('#op_nome').val('<?= htmlspecialchars($parceiro['rep_nome'] ?? '') ?>').prop('readonly', true);
                $('#op_cargo').val($('input[name="rep_cargo"]').val()).prop('readonly', true);
                $('#op_email').val($('input[name="rep_email"]').val()).prop('readonly', true);
                $('#op_telefone').val($('input[name="rep_telefone"]').val()).prop('readonly', true);
                
                // Copia os checkboxes
                $('#op_email_optin').prop('checked', $('#rep_email_optin').is(':checked'));
                $('#op_whatsapp_optin').prop('checked', $('#rep_whatsapp_optin').is(':checked'));
            } else {
                $('#op_nome').prop('readonly', false).val('');
                $('#op_cargo').prop('readonly', false).val('');
                $('#op_email').prop('readonly', false).val('');
                $('#op_telefone').prop('readonly', false).val('');
                
                // Desmarca os checkboxes
                $('#op_email_optin').prop('checked', false);
                $('#op_whatsapp_optin').prop('checked', false);
            }
        });

        // Atualiza checkboxes em tempo real
        $('#rep_email_optin, #rep_whatsapp_optin').change(function() {
            if($('#mesmo_contato').is(':checked')) {
                var op_id = $(this).attr('id').replace('rep_', 'op_');
                $('#' + op_id).prop('checked', $(this).is(':checked'));
            }
        });

    });
</script>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
