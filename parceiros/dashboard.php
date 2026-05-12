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

// ✅ Query PRIMEIRO — $parceiro precisa existir antes de qualquer verificação
$stmt = $pdo->prepare("
    SELECT p.*, c.tipos_parceria, c.escopo_atuacao, c.deseja_publicar, c.rede_impacto
    FROM parceiros p
    LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id
    WHERE p.id = ?
");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parceiro) {
    session_destroy();
    header("Location: /login.php?msg=sessao_invalida");
    exit;
}

// Redireciona se cadastro ainda incompleto
if ($parceiro['etapa_atual'] < 7) {
    header("Location: etapa" . (int)$parceiro['etapa_atual'] . "_dados.php");
    exit;
}

// Redireciona se ainda não assinou a carta-acordo
if ($parceiro['etapa_atual'] == 7 && empty($parceiro['acordo_aceito'])) {
    header("Location: assinar_acordo.php");
    exit;
}

// --- Decodifica campos JSON ---
$tipos      = !empty($parceiro['tipos_parceria'])  ? json_decode($parceiro['tipos_parceria'],  true) : [];
$publicacoes = !empty($parceiro['deseja_publicar']) ? json_decode($parceiro['deseja_publicar'], true) : [];
if (!is_array($tipos))       $tipos       = [];
if (!is_array($publicacoes)) $publicacoes = [];

// --- Detecta se o tipo do parceiro exige etapa extra ---
$tipos_com_etapa_extra = [
    'Patrocinador Institucional',
    'Patrocinador Estratégico de Impacto',
    'Investidor de Ecossistema',
    'Doador de Impacto',
];
$precisa_etapa_extra   = count(array_intersect($tipos, $tipos_com_etapa_extra)) > 0;
$etapa_extra_concluida = !empty($parceiro['etapa_extra_concluida']);

// --- Status badge ---
$status_cores = [
    'em_cadastro' => ['bg' => 'bg-secondary',           'label' => 'Cadastro Incompleto'],
    'analise'     => ['bg' => 'bg-warning text-dark',   'label' => 'Em Análise'],
    'ativo'       => ['bg' => 'bg-success',              'label' => 'Parceria Ativa'],
];
$cor_status = $status_cores[$parceiro['status']] ?? $status_cores['analise'];

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

            <!-- ============================================================
                 BANNER ETAPA EXTRA — exibe apenas se necessário e pendente
            ============================================================ -->
            <?php if ($precisa_etapa_extra && !$etapa_extra_concluida): ?>
                <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden"
                     style="border-left: 5px solid #f59e0b !important;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0">
                                <span class="d-flex align-items-center justify-content-center rounded-3"
                                      style="width:52px;height:52px;background:#fff8e1;">
                                    <i class="bi bi-star-fill fs-4" style="color:#f59e0b;"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h5 class="fw-bold mb-0">Etapa Extra Pendente</h5>
                                    <span class="badge rounded-pill text-white fw-semibold"
                                          style="background:#f59e0b;">Obrigatória</span>
                                </div>
                                <p class="text-muted mb-3">
                                    Como <strong><?= htmlspecialchars(implode(', ', array_intersect($tipos, $tipos_com_etapa_extra))) ?></strong>,
                                    você precisa preencher o formulário de Patrocinadores &amp; Investidores para dar continuidade à sua parceria.
                                </p>
                                <a href="etapa_extra_patrocinadores.php" class="btn btn-warning fw-bold text-white px-4">
                                    <i class="bi bi-arrow-right-circle me-2"></i>Preencher agora
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($precisa_etapa_extra && $etapa_extra_concluida): ?>
                <div class="card border-0 shadow-sm mb-4 rounded-4"
                     style="border-left: 5px solid #22c55e !important;">
                    <div class="card-body p-3 px-4">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-patch-check-fill fs-4 text-success"></i>
                            <div>
                                <span class="fw-semibold d-block">Etapa Extra concluída</span>
                                <span class="text-muted small">
                                    Formulário de Patrocinadores &amp; Investidores enviado com sucesso.
                                    <a href="etapa_extra_patrocinadores.php" class="ms-1">Editar</a>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- CARDS DE MÉTRICAS -->
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

            <!-- BANNER STATUS ANÁLISE -->
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
