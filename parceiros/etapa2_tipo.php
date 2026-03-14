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

// Busca os dados atuais do contrato (se já existir) para pré-preencher
$stmt = $pdo->prepare("SELECT tipos_parceria, natureza_parceria FROM parceiro_contrato WHERE parceiro_id = ?");
$stmt->execute([$parceiro_id]);
$contrato = $stmt->fetch(PDO::FETCH_ASSOC);

// Decodifica o JSON para arrays do PHP (ou cria array vazio se não existir)
$tipos_salvos = !empty($contrato['tipos_parceria']) ? json_decode($contrato['tipos_parceria'], true) : [];
$natureza_salva = !empty($contrato['natureza_parceria']) ? json_decode($contrato['natureza_parceria'], true) : [];

if (!is_array($tipos_salvos)) $tipos_salvos = [];
if (!is_array($natureza_salva)) $natureza_salva = [];

include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Progresso -->
            <div class="mb-4">
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span class="fw-bold text-primary">Etapa 2: Tipo de Parceria</span>
                    <span>2 de 6</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 33%;" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold text-dark mb-1">Como você deseja atuar?</h3>
                    <p class="text-muted mb-4">Selecione os papéis que melhor descrevem a intenção da sua organização na plataforma.</p>

                    <?php if (isset($_SESSION['erro_etapa2'])): ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= htmlspecialchars($_SESSION['erro_etapa2']) ?></div>
                        </div>
                        <?php unset($_SESSION['erro_etapa2']); ?>
                    <?php endif; ?>

                    <form method="POST" action="processar_etapa2.php">
                    <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
    
                        <!-- TIPOS DE PARCERIA -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-2">Tipo de Parceria</h5>
                        <p class="small text-muted mb-3">Você pode escolher mais de uma opção.</p>
                        
                        <div class="row mb-4">
                            <?php 
                            $tipos_opcoes = [
                                "Patrocinador Institucional",
                                "Patrocinador Estratégico de Impacto",
                                "Apoiador Institucional",
                                "Apoiador Estratégico de Impacto",
                                "Investidor de Ecossistema",
                                "Doador de Impacto",
                                "Mentor",
                                "Embaixador",
                                "Voluntário"
                            ];

                            foreach ($tipos_opcoes as $tipo): 
                                $checked = in_array($tipo, $tipos_salvos) ? 'checked' : '';
                            ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check custom-checkbox-card border rounded p-2 px-3">
                                        <input class="form-check-input mt-1" type="checkbox" name="tipos_parceria[]" value="<?= htmlspecialchars($tipo) ?>" id="tipo_<?= md5($tipo) ?>" <?= $checked ?>>
                                        <label class="form-check-label w-100 fw-medium ms-2" style="cursor:pointer;" for="tipo_<?= md5($tipo) ?>">
                                            <?= htmlspecialchars($tipo) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- NATUREZA DA PARCERIA -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">Natureza da Parceria</h5>
                        <p class="small text-muted mb-3">Esta parceria envolverá quais tipos de recursos ou apoios?</p>

                        <div class="row mb-4">
                            <?php 
                            $natureza_opcoes = [
                                "Financeira",
                                "Institucional",
                                "Técnica",
                                "Conteúdo",
                                "Múltipla"
                            ];

                            foreach ($natureza_opcoes as $nat): 
                                $checked = in_array($nat, $natureza_salva) ? 'checked' : '';
                            ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check custom-checkbox-card border rounded p-2 px-3 bg-light">
                                        <input class="form-check-input mt-1" type="checkbox" name="natureza_parceria[]" value="<?= htmlspecialchars($nat) ?>" id="nat_<?= md5($nat) ?>" <?= $checked ?>>
                                        <label class="form-check-label w-100 fw-medium ms-2" style="cursor:pointer;" for="nat_<?= md5($nat) ?>">
                                            <?= htmlspecialchars($nat) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <?php if (($_GET['from'] ?? '') === 'confirmacao'): ?>
                                <button type="submit" name="acao" value="confirmacao" class="btn btn-outline-primary">
                                    Salvar e voltar à revisão
                                </button>
                            <?php endif; ?>
                            <a href="etapa1_dados.php" class="btn btn-outline-secondary btn-lg fw-bold"><i class="bi bi-arrow-left me-2"></i> Voltar</a>
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

<style>
/* Efeito visual para os cards de checkbox ficarem com cara de botão */
.custom-checkbox-card {
    transition: all 0.2s ease;
}
.custom-checkbox-card:hover {
    border-color: #0d6efd !important;
    background-color: #f8f9fa;
}
.custom-checkbox-card input:checked + label {
    color: #0d6efd;
}
.custom-checkbox-card:has(input:checked) {
    border-color: #0d6efd !important;
    background-color: #e9ecef !important;
}
</style>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
