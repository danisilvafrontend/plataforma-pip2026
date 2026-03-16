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

// Verifica login
if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php?msg=login_necessario");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Busca os dados do contrato para pré-preencher
$stmt = $pdo->prepare("SELECT duracao_meses, escopo_atuacao, escopo_outro, nivel_engajamento, oferece_premiacao, premio_descricao FROM parceiro_contrato WHERE parceiro_id = ?");

$stmt->execute([$parceiro_id]);
$contrato = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Decodifica JSON do escopo
$escopo_salvo = !empty($contrato['escopo_atuacao']) ? json_decode($contrato['escopo_atuacao'], true) : [];
if (!is_array($escopo_salvo)) $escopo_salvo = [];

$duracao = isset($contrato['duracao_meses']) ? (string)$contrato['duracao_meses'] : '';
$nivel = $contrato['nivel_engajamento'] ?? '';
$escopo_outro = $contrato['escopo_outro'] ?? '';

include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Progresso -->
            <div class="mb-4">
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span class="fw-bold text-primary">Etapa 3: Definição do Combinado</span>
                    <span>3 de 6</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 50%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold text-dark mb-1">O Nosso Acordo</h3>
                    <p class="text-muted mb-4">Defina o escopo, o tempo e a profundidade do envolvimento na nossa plataforma.</p>

                    <?php if (isset($_SESSION['erro_etapa3'])): ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= htmlspecialchars($_SESSION['erro_etapa3']) ?></div>
                        </div>
                        <?php unset($_SESSION['erro_etapa3']); ?>
                    <?php endif; ?>

                    <form method="POST" action="processar_etapa3.php">
                    <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
    
                        <!-- DURAÇÃO -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-2">Duração da Parceria</h5>
                        
                        <div class="row mb-4">
                            <?php 
                            $duracao_opcoes = [
                                '6' => '6 meses',
                                '12' => '12 meses',
                                '24' => '24 meses',
                                'projeto' => 'Projeto Específico'
                            ];
                            foreach ($duracao_opcoes as $val => $label): 
                                $checked = ((string)$duracao === (string)$val) ? 'checked' : '';

                            ?>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="form-check custom-radio-card border rounded p-2 text-center">
                                        <input class="form-check-input d-none" type="radio" name="duracao_meses" value="<?= $val ?>" id="dur_<?= $val ?>" <?= $checked ?> required>
                                        <label class="form-check-label w-100 fw-medium m-0 py-1" style="cursor:pointer;" for="dur_<?= $val ?>">
                                            <?= $label ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- NÍVEL DE ENGAJAMENTO -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">Nível de Engajamento</h5>
                        
                        <div class="mb-4">
                            <div class="list-group">
                                <label class="list-group-item list-group-item-action d-flex gap-3 py-3 <?= $nivel === 'básico' ? 'bg-light border-primary' : '' ?>">
                                    <input class="form-check-input flex-shrink-0" type="radio" name="nivel_engajamento" value="básico" <?= $nivel === 'básico' ? 'checked' : '' ?> required>
                                    <span class="pt-1 form-checked-content">
                                        <strong>Básico</strong>
                                        <small class="d-block text-muted">Presença institucional na plataforma.</small>
                                    </span>
                                </label>
                                <label class="list-group-item list-group-item-action d-flex gap-3 py-3 <?= $nivel === 'ativo' ? 'bg-light border-primary' : '' ?>">
                                    <input class="form-check-input flex-shrink-0" type="radio" name="nivel_engajamento" value="ativo" <?= $nivel === 'ativo' ? 'checked' : '' ?>>
                                    <span class="pt-1 form-checked-content">
                                        <strong>Ativo</strong>
                                        <small class="d-block text-muted">Geração de conteúdo e participação em eventos.</small>
                                    </span>
                                </label>
                                <label class="list-group-item list-group-item-action d-flex gap-3 py-3 <?= $nivel === 'estratégico' ? 'bg-light border-primary' : '' ?>">
                                    <input class="form-check-input flex-shrink-0" type="radio" name="nivel_engajamento" value="estratégico" <?= $nivel === 'estratégico' ? 'checked' : '' ?>>
                                    <span class="pt-1 form-checked-content">
                                        <strong>Estratégico</strong>
                                        <small class="d-block text-muted">Cocriação de programas e editais direcionados.</small>
                                    </span>
                                </label>
                                <label class="list-group-item list-group-item-action d-flex gap-3 py-3 <?= $nivel === 'estruturante' ? 'bg-light border-primary' : '' ?>">
                                    <input class="form-check-input flex-shrink-0" type="radio" name="nivel_engajamento" value="estruturante" <?= $nivel === 'estruturante' ? 'checked' : '' ?>>
                                    <span class="pt-1 form-checked-content">
                                        <strong>Estruturante</strong>
                                        <small class="d-block text-muted">Patrocinador principal de eixos e pilares do ecossistema.</small>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- ESCOPO DE ATUAÇÃO -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">Escopo de Atuação (Selecione todos que se aplicam)</h5>
                        
                        <div class="row mb-4">
                            <?php 
                            $escopo_opcoes = [
                                "Plataforma geral",
                                "Premiação Impactos Positivos",
                                "Programa Impact Chains",
                                "Programa de Aceleração",
                                "Eventos e Fóruns",
                                "Série Narrativas de Impacto",
                                "Rede de Impacto",
                                "Conteúdos educacionais"
                            ];

                            foreach ($escopo_opcoes as $esc): 
                                $checked = in_array($esc, $escopo_salvo) ? 'checked' : '';
                            ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check custom-checkbox-card border rounded p-2 px-3">
                                        <input class="form-check-input mt-1" type="checkbox" name="escopo_atuacao[]" value="<?= htmlspecialchars($esc) ?>" id="esc_<?= md5($esc) ?>" <?= $checked ?>>
                                        <label class="form-check-label w-100 fw-medium ms-2" style="cursor:pointer;" for="esc_<?= md5($esc) ?>">
                                            <?= htmlspecialchars($esc) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Checkbox OUTROS -->
                            <div class="col-12 mb-2 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input mt-2" type="checkbox" id="check_outro" <?= !empty($escopo_outro) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold ms-2 mt-1" for="check_outro">Outro escopo (especifique):</label>
                                </div>
                                <div class="mt-2" id="div_outro" style="<?= empty($escopo_outro) ? 'display: none;' : '' ?>">
                                    <input type="text" class="form-control" name="escopo_outro" id="input_outro" value="<?= htmlspecialchars($escopo_outro) ?>" placeholder="Ex: Projeto regional exclusivo...">
                                </div>
                            </div>
                        </div>

                        <!-- NOVO BLOCO: PREMIAÇÃO -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">Premiação Impactos Positivos</h5>
                        <p class="small text-muted mb-3">Deseja oferecer prêmio para os 4 ganhadores da Premiação do ano vigente?</p>

                        <!-- Checkbox principal -->
                        <div class="col-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="oferece_premiacao" id="premio_check" 
                                    value="1" <?= !empty($contrato['oferece_premiacao']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold" for="premio_check">
                                    Sim, oferecerei prêmio para os 4 ganhadores
                                </label>
                            </div>
                        </div>

                        <!-- Campo condicional: Tipo e valor do prêmio -->
                        <div class="col-12 mb-4" id="div_premio" style="<?= empty($contrato['oferece_premiacao']) ? 'display: none;' : '' ?>">
                            <label class="form-label fw-semibold">Qual prêmio e valor de mercado?</label>
                            <textarea class="form-control" name="premio_descricao" id="premio_descricao" rows="3" 
                                    placeholder="Ex: 1h de consultoria em gestão ambiental - correspondente a R$1.000,00. Para cada vencedor." 
                                    style="resize: vertical;"><?= htmlspecialchars($contrato['premio_descricao'] ?? '') ?></textarea>
                            <div class="form-text">Descreva o prêmio e o valor estimado de mercado para cada um dos 4 vencedores.</div>
                        </div>


                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <?php if (($_GET['from'] ?? '') === 'confirmacao'): ?>
                                <button type="submit" name="acao" value="confirmacao" class="btn btn-outline-primary">
                                    Salvar e voltar à revisão
                                </button>
                            <?php endif; ?>
                            <a href="etapa2_combinado.php" class="btn btn-outline-secondary btn-lg fw-bold"><i class="bi bi-arrow-left me-2"></i> Voltar</a>
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
/* CSS para o Radio Button ficar como um botão clicável */
.custom-radio-card { transition: all 0.2s; background: #fff; }
.custom-radio-card input:checked + label { color: #0d6efd; font-weight: bold !important; }
.custom-radio-card:has(input:checked) { border-color: #0d6efd !important; background-color: #e9ecef; }
.custom-checkbox-card { transition: all 0.2s; }
.custom-checkbox-card:hover { border-color: #0d6efd !important; background-color: #f8f9fa; }
.custom-checkbox-card input:checked + label { color: #0d6efd; }
.custom-checkbox-card:has(input:checked) { border-color: #0d6efd !important; background-color: #e9ecef !important; }

/* Para pintar de azul a lista de engajamento quando selecionada */
.list-group-item:has(input:checked) {
    background-color: #f8f9fa;
    border-color: #0d6efd;
    z-index: 2;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const checkOutro = document.getElementById('check_outro');
    const divOutro = document.getElementById('div_outro');
    const inputOutro = document.getElementById('input_outro');

    checkOutro.addEventListener('change', function() {
        if(this.checked) {
            divOutro.style.display = 'block';
            inputOutro.focus();
        } else {
            divOutro.style.display = 'none';
            inputOutro.value = '';
        }
    });
});
</script>

<script>
document.getElementById('premio_check').addEventListener('change', function() {
    const divPremio = document.getElementById('div_premio');
    const textarea = document.getElementById('premio_descricao');
    
    if (this.checked) {
        divPremio.style.display = 'block';
        textarea.required = true;
        textarea.focus();
    } else {
        divPremio.style.display = 'none';
        textarea.required = false;
        textarea.value = '';
    }
});
</script>


<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
