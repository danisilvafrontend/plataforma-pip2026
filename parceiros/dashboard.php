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

// Se concluiu o cadastro mas ainda não aceitou a carta-acordo
if ($parceiro['etapa_atual'] == 7 && $parceiro['acordo_aceito'] == 0) {
    header("Location: assinar_acordo.php");
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, c.tipos_parceria, c.escopo_atuacao, c.deseja_publicar, c.rede_impacto FROM parceiros p LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id WHERE p.id = ?");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

if ($parceiro['etapa_atual'] < 7) {
    // ... (mesma lógica de redirecionamento de antes)
    header("Location: etapa" . $parceiro['etapa_atual'] . "_dados.php");
    exit;
}

$tipos = !empty($parceiro['tipos_parceria']) ? json_decode($parceiro['tipos_parceria'], true) : [];
$publicacoes = !empty($parceiro['deseja_publicar']) ? json_decode($parceiro['deseja_publicar'], true) : [];

$status_cores = [
    'em_cadastro' => ['bg' => 'bg-secondary', 'label' => 'Cadastro Incompleto'],
    'analise' => ['bg' => 'bg-warning text-dark', 'label' => 'Em Análise'],
    'ativo' => ['bg' => 'bg-success', 'label' => 'Parceria Ativa']
];
$cor_status = $status_cores[$parceiro['status']] ?? $status_cores['analise'];

include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5">
    <div class="row">
        
        <!-- SIDEBAR (Menu Lateral) -->
        <div class="col-lg-3 col-md-4 mb-4 mb-md-0">
            <?php include __DIR__ . '/../app/views/parceiros/sidebar.php'; ?>
        </div>

        <!-- CONTEÚDO PRINCIPAL -->
        <div class="col-lg-9 col-md-8">
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'sucesso'): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Cadastro concluído! Seus dados foram enviados para análise.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <h2 class="fw-bold mb-0">Dashboard</h2>
                <span class="badge <?= $cor_status['bg'] ?> p-2 px-3 rounded-pill fs-6">
                    <i class="bi bi-info-circle me-1"></i> <?= $cor_status['label'] ?>
                </span>
            </div>

            <!-- CARDS DE MÉTRICAS / RESUMO (Estilo idêntico ao da imagem) -->
            <div class="row g-4 mb-5 text-center">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 rounded-3 h-100 py-3">
                        <h6 class="fw-bold mb-2">Minhas Oportunidades</h6>
                        <h2 class="display-5 fw-light text-secondary mb-0">0</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 rounded-3 h-100 py-3">
                        <h6 class="fw-bold mb-2">Conexões de Rede</h6>
                        <h2 class="display-5 fw-light text-primary mb-0">0</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 rounded-3 h-100 py-3">
                        <h6 class="fw-bold mb-2">Nível de Engajamento</h6>
                        <h2 class="h3 fw-light text-success mb-0 mt-2">Básico</h2>
                    </div>
                </div>
            </div>

            <?php if ($parceiro['status'] === 'analise'): ?>
                <div class="card bg-light border border-warning shadow-sm mb-4 rounded-4">
                    <div class="card-body p-4 text-center">
                        <i class="bi bi-hourglass-split text-warning fs-1"></i>
                        <h5 class="fw-bold mt-2">Sua parceria está sendo avaliada</h5>
                        <p class="text-muted mb-0 mx-auto" style="max-width: 600px;">
                            Nossa equipe está analisando sua Carta-Acordo. Assim que for aprovada, as funcionalidades da plataforma serão desbloqueadas.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
