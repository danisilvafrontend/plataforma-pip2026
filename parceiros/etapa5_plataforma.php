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

if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php?msg=login_necessario");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Busca os dados da etapa 5 na tabela de contratos
$stmt = $pdo->prepare("SELECT deseja_publicar, rede_impacto FROM parceiro_contrato WHERE parceiro_id = ?");
$stmt->execute([$parceiro_id]);
$contrato = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Prepara as opções já salvas
$publicar_salvo = !empty($contrato['deseja_publicar']) ? json_decode($contrato['deseja_publicar'], true) : [];
if (!is_array($publicar_salvo)) $publicar_salvo = [];

$rede_impacto = $contrato['rede_impacto'] ?? '';

include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Progresso -->
            <div class="mb-4">
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span class="fw-bold text-primary">Etapa 5: Uso da Plataforma</span>
                    <span>5 de 6</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 83%;" aria-valuenow="83" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold text-dark mb-1">Como você quer atuar?</h3>
                    <p class="text-muted mb-4">A plataforma Impactos Positivos é viva! Escolha as ferramentas que farão parte do dia a dia da sua organização aqui dentro.</p>

                    <?php if (isset($_SESSION['erro_etapa5'])): ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= htmlspecialchars($_SESSION['erro_etapa5']) ?></div>
                        </div>
                        <?php unset($_SESSION['erro_etapa5']); ?>
                    <?php endif; ?>

                    <form method="POST" action="processar_etapa5.php">
                    <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
    
                        <!-- DESEJA PUBLICAR -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-2">Geração de Conteúdo e Oportunidades</h5>
                        <p class="small text-muted mb-3">Marque tudo o que você planeja publicar ou promover dentro da plataforma:</p>
                        
                        <div class="row mb-5">
                            <?php 
                            $publicacoes = [
                                ["icone" => "bi-file-text", "texto" => "Artigos"],
                                ["icone" => "bi-play-btn", "texto" => "Vídeos"],
                                ["icone" => "bi-mic", "texto" => "Podcasts"],
                                ["icone" => "bi-camera-video", "texto" => "Webinars"],
                                ["icone" => "bi-megaphone", "texto" => "Editais / Chamadas"],
                                ["icone" => "bi-calendar-event", "texto" => "Convites para Eventos"],
                                ["icone" => "bi-lightbulb", "texto" => "Oportunidades de Mentoria"],
                                ["icone" => "bi-mortarboard", "texto" => "Incentivos / Bolsas"],
                                ["icone" => "bi-box", "texto" => "Produtos e Serviços"],
                                ["icone" => "bi-graph-up-arrow", "texto" => "Investimentos"],
                                ["icone" => "bi-heart", "texto" => "Doações estruturadas"]
                            ];

                            foreach ($publicacoes as $pub): 
                                $checked = in_array($pub['texto'], $publicar_salvo) ? 'checked' : '';
                            ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check custom-checkbox-card border rounded p-2 px-3 d-flex align-items-center">
                                        <input class="form-check-input mt-0 me-2" type="checkbox" name="deseja_publicar[]" value="<?= htmlspecialchars($pub['texto']) ?>" id="pub_<?= md5($pub['texto']) ?>" <?= $checked ?>>
                                        <label class="form-check-label w-100 fw-medium m-0" style="cursor:pointer;" for="pub_<?= md5($pub['texto']) ?>">
                                            <i class="bi <?= $pub['icone'] ?> text-muted me-1"></i> <?= $pub['texto'] ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- REDE DE IMPACTO -->
                        <div class="bg-light p-4 rounded-3 border mb-4">
                            <h5 class="fw-bold text-primary mb-2"><i class="bi bi-people-fill me-2"></i>Participação na Rede de Impacto</h5>
                            <p class="small text-muted mb-3">
                                A <strong>Rede de Impacto</strong> é nosso ambiente de matchmaking inteligente, onde você pode conversar diretamente com negócios aprovados, criar pontes estratégicas (Impact Chains) e receber propostas de conexão.
                            </p>
                            
                            <label class="fw-semibold mb-2">Deseja ativar seu perfil na Rede de Impacto agora?</label>
                            
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="rede_impacto" id="rede_sim" value="sim" <?= ($rede_impacto === 'sim') ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="rede_sim">
                                        Sim, quero participar
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="rede_impacto" id="rede_nao" value="nao" <?= ($rede_impacto === 'nao') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="rede_nao">
                                        Não
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="rede_impacto" id="rede_avaliar" value="avaliar_depois" <?= ($rede_impacto === 'avaliar_depois') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="rede_avaliar">
                                        Avaliar depois
                                    </label>
                                </div>
                            </div>
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
/* Manter os cards bonitos */
.custom-checkbox-card { transition: all 0.2s; background: #fff; }
.custom-checkbox-card:hover { border-color: #0d6efd !important; background-color: #f8f9fa; }
.custom-checkbox-card input:checked + label { color: #0d6efd; font-weight: 600; }
.custom-checkbox-card input:checked + label i { color: #0d6efd !important; }
.custom-checkbox-card:has(input:checked) { border-color: #0d6efd !important; background-color: #e9ecef !important; }
</style>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
