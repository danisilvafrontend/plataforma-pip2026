<?php
session_start();
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

<div class="container py-5 parceiro-step-shell">
    <div class="parceiro-step-top mb-4 mb-lg-5">
        <div class="parceiro-step-progress-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <span class="parceiro-step-kicker">Etapa 1 de 6</span>
                    <h1 class="parceiro-step-title mb-1">Dados Complementares</h1>
                    <p class="parceiro-step-subtitle mb-0">
                        Complete as informações institucionais e os contatos da parceria.
                    </p>
                </div>
                <div class="parceiro-step-indicator">16%</div>
            </div>

            <div class="progress parceiro-step-progress" role="progressbar" aria-valuenow="16" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-primary" style="width: 16%;"></div>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <aside class="parceiro-step-aside">
                <div class="parceiro-step-aside-card">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-compass-fill"></i>
                        Orientações desta etapa
                    </div>
                    <ul class="parceiro-step-aside-list">
                        <li>Preencha os dados institucionais para formalização da parceria.</li>
                        <li>Essas informações serão usadas nas próximas etapas e na geração automática do contrato.</li>
                        <li>Revise com atenção os dados do representante legal e do contato operacional.</li>
                    </ul>
                </div>

                <div class="parceiro-step-aside-card parceiro-step-aside-highlight">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-envelope-paper-fill"></i>
                        Comunicação da parceria
                    </div>
                    <p class="mb-0">
                        Você poderá definir quem recebe comunicações institucionais e operacionais por e-mail e WhatsApp.
                    </p>
                </div>
            </aside>
        </div>

        <div class="col-lg-8">
            <div class="parceiro-step-card">
                <div class="parceiro-step-card-header">
                    <div>
                        <h2 class="parceiro-step-card-title mb-1">Complete o seu perfil</h2>
                        <p class="parceiro-step-card-subtitle mb-0">
                            Essas informações são importantes para a formalização da parceria e geração automática do contrato.
                        </p>
                    </div>
                </div>

                <div class="parceiro-step-card-body">
                    <?php if (isset($_SESSION['erro_etapa1'])): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 parceiro-step-alert">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div><?= htmlspecialchars($_SESSION['erro_etapa1']) ?></div>
                        </div>
                        <?php unset($_SESSION['erro_etapa1']); ?>
                    <?php endif; ?>

                    <form method="POST" action="processar_etapa1.php">
                        <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Endereço e Contato Institucional</h3>
                                <p class="parceiro-step-section-text">
                                    Informe os dados de localização e contato principal da instituição.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label parceiro-step-label">CEP</label>
                                    <input type="text" name="cep" id="cep" class="form-control" value="<?= htmlspecialchars($parceiro['cep'] ?? '') ?>">
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label parceiro-step-label">Rua / Logradouro</label>
                                    <input type="text" name="endereco_completo" id="rua" class="form-control" value="<?= htmlspecialchars($parceiro['endereco_completo'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-step-label">Cidade</label>
                                    <input type="text" name="cidade" id="municipio" class="form-control" value="<?= htmlspecialchars($parceiro['cidade'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-step-label">Estado (UF)</label>
                                    <input type="text" name="estado" id="estado" class="form-control" value="<?= htmlspecialchars($parceiro['estado'] ?? '') ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label parceiro-step-label">País</label>
                                    <input type="text" name="pais" class="form-control" value="<?= htmlspecialchars($parceiro['pais'] ?? 'Brasil') ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label parceiro-step-label">Telefone Institucional</label>
                                    <input type="text" name="telefone_institucional" class="form-control phone_mask" value="<?= htmlspecialchars($parceiro['telefone_institucional'] ?? '') ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label parceiro-step-label">Site</label>
                                    <input type="url" name="site" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($parceiro['site'] ?? '') ?>">
                                </div>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Representante Legal</h3>
                                <p class="parceiro-step-section-text">
                                    A pessoa com poderes para assinar a carta-acordo da parceria.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label parceiro-step-label">Cargo</label>
                                    <input type="text" name="rep_cargo" class="form-control" value="<?= htmlspecialchars($parceiro['rep_cargo'] ?? '') ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label parceiro-step-label">E-mail do Representante</label>
                                    <input type="email" name="rep_email" class="form-control" value="<?= htmlspecialchars($parceiro['rep_email'] ?? '') ?>">
                                    <div class="form-check parceiro-step-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="rep_email_optin" name="rep_email_optin" value="1" <?= (!empty($parceiro['rep_email_optin'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label small text-muted" for="rep_email_optin">Aceito receber atualizações via e-mail</label>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label parceiro-step-label">Telefone / Celular</label>
                                    <input type="text" name="rep_telefone" class="form-control phone_mask" value="<?= htmlspecialchars($parceiro['rep_telefone'] ?? '') ?>">
                                    <div class="form-check parceiro-step-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="rep_whatsapp_optin" name="rep_whatsapp_optin" value="1" <?= (!empty($parceiro['rep_whatsapp_optin'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label small text-muted" for="rep_whatsapp_optin">Aceito receber novidades via WhatsApp</label>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Contato Operacional</h3>
                                <p class="parceiro-step-section-text">
                                    Pessoa responsável por operar a plataforma no dia a dia, podendo ser a mesma do representante legal.
                                </p>
                            </div>

                            <div class="parceiro-step-toggle-box mb-3">
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" id="mesmo_contato">
                                    <label class="form-check-label fw-semibold" for="mesmo_contato">
                                        O contato operacional é o mesmo que o representante legal
                                    </label>
                                </div>
                            </div>

                            <div class="row g-3" id="bloco_operacional">
                                <div class="col-md-6">
                                    <label class="form-label parceiro-step-label">Nome Operacional</label>
                                    <input type="text" name="op_nome" id="op_nome" class="form-control" value="<?= htmlspecialchars($parceiro['op_nome'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-step-label">Cargo</label>
                                    <input type="text" name="op_cargo" id="op_cargo" class="form-control" value="<?= htmlspecialchars($parceiro['op_cargo'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-step-label">E-mail</label>
                                    <input type="email" name="op_email" id="op_email" class="form-control" value="<?= htmlspecialchars($parceiro['op_email'] ?? '') ?>">
                                    <div class="form-check parceiro-step-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="op_email_optin" name="op_email_optin" value="1" <?= (!empty($parceiro['op_email_optin'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label small text-muted" for="op_email_optin">Aceito receber atualizações via e-mail</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-step-label">Telefone</label>
                                    <input type="text" name="op_telefone" id="op_telefone" class="form-control phone_mask" value="<?= htmlspecialchars($parceiro['op_telefone'] ?? '') ?>">
                                    <div class="form-check parceiro-step-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="op_whatsapp_optin" name="op_whatsapp_optin" value="1" <?= (!empty($parceiro['op_whatsapp_optin'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label small text-muted" for="op_whatsapp_optin">Aceito receber novidades via WhatsApp</label>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="parceiro-step-actions">
                            <?php if (($_GET['from'] ?? '') === 'confirmacao'): ?>
                                <button type="submit" name="acao" value="confirmacao" class="btn btn-outline-primary">
                                    Salvar e voltar à revisão
                                </button>
                            <?php endif; ?>

                            <button type="submit" class="btn-reg-submit">
                                Salvar e continuar
                                <i class="bi bi-arrow-right"></i>
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
