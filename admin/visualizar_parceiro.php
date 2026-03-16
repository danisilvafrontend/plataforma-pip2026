<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autenticação admin
require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ID do parceiro (via GET)
$parceiro_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($parceiro_id <= 0) {
    die('Parceiro não informado.');
}

// BUSCA DADOS PRINCIPAIS + CONTRATO
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        c.tipos_parceria,
        c.natureza_parceria,
        c.duracao_meses,
        c.nivel_engajamento,
        c.escopo_atuacao,
        c.escopo_outro,
        c.deseja_publicar,
        c.rede_impacto,
        c.logo_url,
        c.manual_marca_url,
        c.termos_aceitos,
        c.data_assinatura,
        c.data_vencimento
    FROM parceiros p
    LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id
    WHERE p.id = ?
");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parceiro) {
    die('Parceiro não encontrado.');
}

// BUSCA INTERESSES
$stmtInt = $pdo->prepare("SELECT * FROM parceiro_interesses WHERE parceiro_id = ?");
$stmtInt->execute([$parceiro_id]);
$interesses = $stmtInt->fetch(PDO::FETCH_ASSOC) ?: [];

// BUSCA ODS
$stmtOds = $pdo->prepare("
    SELECT o.* 
    FROM parceiro_ods po
    INNER JOIN ods o ON o.id = po.ods_id
    WHERE po.parceiro_id = ?
    ORDER BY o.id ASC
");
$stmtOds->execute([$parceiro_id]);
$odsParceiro = $stmtOds->fetchAll(PDO::FETCH_ASSOC);

// Decodifica JSONs do contrato
$tipos_parceria = !empty($parceiro['tipos_parceria']) ? json_decode($parceiro['tipos_parceria'], true) : [];
$natureza_parceria = !empty($parceiro['natureza_parceria']) ? json_decode($parceiro['natureza_parceria'], true) : [];
$deseja_publicar = !empty($parceiro['deseja_publicar']) ? json_decode($parceiro['deseja_publicar'], true) : [];
$rede_impacto = $parceiro['rede_impacto'] ?? null;

// Decodifica JSONs de interesses
$eixos = !empty($interesses['eixos_interesse']) ? json_decode($interesses['eixos_interesse'], true) : [];
$maturidade = !empty($interesses['maturidade_negocios']) ? json_decode($interesses['maturidade_negocios'], true) : [];
$setores = !empty($interesses['setores_interesse']) ? json_decode($interesses['setores_interesse'], true) : [];
$perfilImpacto = !empty($interesses['perfil_impacto']) ? json_decode($interesses['perfil_impacto'], true) : [];

if (!is_array($tipos_parceria)) $tipos_parceria = [];
if (!is_array($natureza_parceria)) $natureza_parceria = [];
if (!is_array($deseja_publicar)) $deseja_publicar = [];

if (!is_array($eixos)) $eixos = [];
if (!is_array($maturidade)) $maturidade = [];
if (!is_array($setores)) $setores = [];
if (!is_array($perfilImpacto)) $perfilImpacto = [];

$alcance = $interesses['alcance_impacto'] ?? null;
$orcamento_anual = $interesses['orcamento_anual'] ?? null;
$tipo_relacionamento = $interesses['tipo_relacionamento'] ?? null;
$horizonte_engajamento = $interesses['horizonte_engajamento'] ?? null;

// Formatações auxiliares
function listaOuTraco(?array $arr): string {
    return !empty($arr) ? implode(', ', $arr) : '-';
}

function dataBr(?string $dt): string {
    if (empty($dt) || $dt === '0000-00-00 00:00:00') return '-';
    return date('d/m/Y H:i', strtotime($dt));
}

$pageTitle = "Visualizar Parceiro";
include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <?= htmlspecialchars($parceiro['nome_fantasia'] ?? $parceiro['razao_social'] ?? 'Parceiro') ?>
            </h2>
            <p class="text-muted mb-0">
                CNPJ: <?= htmlspecialchars($parceiro['cnpj'] ?? '-') ?> · Status: 
                <span class="badge bg-<?= $parceiro['status'] === 'ativo' ? 'success' : ($parceiro['status'] === 'vencido' ? 'secondary' : 'warning') ?>">
                    <?= htmlspecialchars(ucfirst($parceiro['status'] ?? 'pendente')) ?>
                </span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="parceiros.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
            <a href="processar_status_parceiro.php?id=<?= $parceiro_id ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square"></i> Alterar Status
            </a>
        </div>
    </div>

    <!-- Bloco 1: Dados da Organização -->
    <div class="card shadow-sm mb-4 border-0 rounded-4">
        <div class="card-header bg-white border-0 pt-3 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-primary mb-0">
                <i class="bi bi-building me-2"></i>Dados da Organização
            </h5>
        </div>
        <div class="card-body px-4 pb-4 pt-3">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <small class="text-muted d-block">Razão Social</small>
                    <span class="fw-semibold"><?= htmlspecialchars($parceiro['razao_social'] ?? '-') ?></span>
                </div>
                <div class="col-md-6 mb-2">
                    <small class="text-muted d-block">Nome Fantasia</small>
                    <span class="fw-semibold"><?= htmlspecialchars($parceiro['nome_fantasia'] ?? '-') ?></span>
                </div>

                <div class="col-md-4 mb-2">
                    <small class="text-muted d-block">CNPJ</small>
                    <span class="fw-semibold"><?= htmlspecialchars($parceiro['cnpj'] ?? '-') ?></span>
                </div>
                <div class="col-md-4 mb-2">
                    <small class="text-muted d-block">Telefone Institucional</small>
                    <span class="fw-semibold"><?= htmlspecialchars($parceiro['telefone_institucional'] ?? '-') ?></span>
                </div>
                <div class="col-md-4 mb-2">
                    <small class="text-muted d-block">Site</small>
                    <span class="fw-semibold">
                        <?php if (!empty($parceiro['site'])): ?>
                            <a href="<?= htmlspecialchars($parceiro['site']) ?>" target="_blank">
                                <?= htmlspecialchars($parceiro['site']) ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </span>
                </div>

                <div class="col-md-4 mb-2">
                    <small class="text-muted d-block">Cidade</small>
                    <span class="fw-semibold"><?= htmlspecialchars($parceiro['cidade'] ?? '-') ?></span>
                </div>
                <div class="col-md-4 mb-2">
                    <small class="text-muted d-block">Estado</small>
                    <span class="fw-semibold"><?= htmlspecialchars($parceiro['estado'] ?? '-') ?></span>
                </div>
                <div class="col-md-4 mb-2">
                    <small class="text-muted d-block">País</small>
                    <span class="fw-semibold"><?= htmlspecialchars($parceiro['pais'] ?? '-') ?></span>
                </div>

                <div class="col-12 mt-3">
                    <small class="text-muted d-block">Endereço Completo</small>
                    <span class="fw-semibold">
                        <?= htmlspecialchars($parceiro['endereco_completo'] ?? '-') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bloco 2: Representante Legal e Contato Operacional -->
    <div class="card shadow-sm mb-4 border-0 rounded-4">
        <div class="card-header bg-white border-0 pt-3 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-primary mb-0">
                <i class="bi bi-person-badge me-2"></i>Representante Legal & Contato Operacional
            </h5>
        </div>
        <div class="card-body px-4 pb-4 pt-3">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <small class="text-muted d-block">Representante Legal</small>
                    <span class="fw-semibold">
                        <?= htmlspecialchars($parceiro['rep_nome'] ?? '-') ?> 
                        (<?= htmlspecialchars($parceiro['rep_cargo'] ?? '-') ?>)
                    </span>
                    <div class="small mt-1 text-muted">
                        E-mail: <span class="fw-semibold"><?= htmlspecialchars($parceiro['rep_email'] ?? '-') ?></span><br>
                        Celular: <span class="fw-semibold"><?= htmlspecialchars($parceiro['rep_telefone'] ?? '-') ?></span>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <small class="text-muted d-block">Contato Operacional</small>
                    <span class="fw-semibold">
                        <?= htmlspecialchars($parceiro['op_nome'] ?? '-') ?> 
                        (<?= htmlspecialchars($parceiro['op_cargo'] ?? '-') ?>)
                    </span>
                    <div class="small mt-1 text-muted">
                        E-mail: <span class="fw-semibold"><?= htmlspecialchars($parceiro['op_email'] ?? '-') ?></span><br>
                        Telefone: <span class="fw-semibold"><?= htmlspecialchars($parceiro['op_telefone'] ?? '-') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bloco 3: Dados de Contrato / Carta-Acordo -->
    <div class="card shadow-sm mb-4 border-0 rounded-4">
        <div class="card-header bg-white border-0 pt-3 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-primary mb-0">
                <i class="bi bi-handshake me-2"></i>Contrato / Carta-Acordo
            </h5>
        </div>
        <div class="card-body px-4 pb-4 pt-3">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <small class="text-muted d-block">Etapa Atual do Fluxo</small>
                    <span class="fw-semibold"><?= (int)($parceiro['etapa_atual'] ?? 1) ?> de 7</span>
                </div>
                <div class="col-md-3 mb-2">
                    <small class="text-muted d-block">Acordo Aceito?</small>
                    <span class="fw-semibold">
                        <?= !empty($parceiro['acordo_aceito']) ? 'Sim' : 'Não' ?>
                    </span>
                </div>
                <div class="col-md-3 mb-2">
                    <small class="text-muted d-block">Data Aceite</small>
                    <span class="fw-semibold"><?= dataBr($parceiro['acordo_data'] ?? null) ?></span>
                </div>
                <div class="col-md-3 mb-2">
                    <small class="text-muted d-block">IP Aceite</small>
                    <span class="fw-semibold"><?= htmlspecialchars($parceiro['acordo_ip'] ?? '-') ?></span>
                </div>

                <div class="col-md-4 mb-2 mt-3">
                    <small class="text-muted d-block">Duração da Parceria</small>
                    <span class="fw-semibold">
                        <?= !empty($parceiro['duracao_meses']) ? $parceiro['duracao_meses'] . ' meses' : '-' ?>
                    </span>
                </div>
                <div class="col-md-4 mb-2 mt-3">
                    <small class="text-muted d-block">Início Vigência</small>
                    <span class="fw-semibold"><?= dataBr($parceiro['data_assinatura'] ?? null) ?></span>
                </div>
                <div class="col-md-4 mb-2 mt-3">
                    <small class="text-muted d-block">Fim Vigência</small>
                    <span class="fw-semibold"><?= dataBr($parceiro['data_vencimento'] ?? null) ?></span>
                </div>

                <div class="col-md-6 mb-3 mt-3">
                    <small class="text-muted d-block">Tipos de Parceria</small>
                    <span class="fw-semibold"><?= listaOuTraco($tipos_parceria) ?></span>
                </div>
                <div class="col-md-6 mb-3 mt-3">
                    <small class="text-muted d-block">Natureza da Parceria</small>
                    <span class="fw-semibold"><?= listaOuTraco($natureza_parceria) ?></span>
                </div>

                <div class="col-12 mt-3">
                    <small class="text-muted d-block">Escopo Detalhado</small>
                    <div class="fw-semibold">
                        <?= nl2br(htmlspecialchars($parceiro['escopo_atuacao'] ?? '-')) ?>
                        <?php if (!empty($parceiro['escopo_outro'])): ?>
                            <div class="mt-1 text-muted small">
                                Outro escopo: <?= htmlspecialchars($parceiro['escopo_outro']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-6 mt-3">
                    <small class="text-muted d-block">O que deseja publicar na plataforma?</small>
                    <span class="fw-semibold"><?= listaOuTraco($deseja_publicar) ?></span>
                </div>

                <div class="col-md-6 mt-3">
                    <small class="text-muted d-block">Participação na Rede de Impacto</small>
                    <span class="fw-semibold">
                        <?php 
                            if ($rede_impacto === 'sim') echo 'Sim, perfil ativo na Rede';
                            elseif ($rede_impacto === 'nao') echo 'Não deseja ativar a Rede';
                            elseif ($rede_impacto === 'avaliar_depois') echo 'Avaliar depois';
                            else echo '-';
                        ?>
                    </span>
                </div>

                <div class="col-md-6 mt-4">
                    <small class="text-muted d-block">Logomarca</small>
                    <?php if (!empty($parceiro['logo_url'])): ?>
                        <div class="mt-1">
                            <a href="<?= htmlspecialchars($parceiro['logo_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-image"></i> Ver Logomarca
                            </a>
                        </div>
                    <?php else: ?>
                        <span class="fw-semibold">Não enviada</span>
                    <?php endif; ?>
                </div>

                <div class="col-md-6 mt-4">
                    <small class="text-muted d-block">Manual da Marca</small>
                    <?php if (!empty($parceiro['manual_marca_url'])): ?>
                        <div class="mt-1">
                            <a href="<?= htmlspecialchars($parceiro['manual_marca_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-file-earmark-pdf"></i> Ver Manual
                            </a>
                        </div>
                    <?php else: ?>
                        <span class="fw-semibold">Não enviado</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bloco 4: Interesses e Matchmaking -->
    <div class="card shadow-sm mb-4 border-0 rounded-4">
        <div class="card-header bg-white border-0 pt-3 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-primary mb-0">
                <i class="bi bi-bullseye me-2"></i>Perfil de Impacto & Matchmaking
            </h5>
            <a href="editar_interesses.php?id=<?= $parceiro_id ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i> Editar Interesses
            </a>
        </div>
        <div class="card-body px-4 pb-4 pt-3">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <small class="text-muted d-block">Orçamento anual estimado (via plataforma)</small>
                    <span class="fw-semibold"><?= htmlspecialchars($orcamento_anual ?: '-') ?></span>
                </div>
                <div class="col-md-4 mb-3">
                    <small class="text-muted d-block">Tipo de relacionamento preferido</small>
                    <span class="fw-semibold"><?= htmlspecialchars($tipo_relacionamento ?: '-') ?></span>
                </div>
                <div class="col-md-4 mb-3">
                    <small class="text-muted d-block">Horizonte de engajamento</small>
                    <span class="fw-semibold"><?= htmlspecialchars($horizonte_engajamento ?: '-') ?></span>
                </div>

                <div class="col-md-6 mb-3 mt-2">
                    <small class="text-muted d-block">Eixos temáticos</small>
                    <span class="fw-semibold"><?= listaOuTraco($eixos) ?></span>
                </div>
                <div class="col-md-6 mb-3 mt-2">
                    <small class="text-muted d-block">Maturidade dos negócios</small>
                    <span class="fw-semibold"><?= listaOuTraco($maturidade) ?></span>
                </div>

                <div class="col-md-6 mb-3 mt-2">
                    <small class="text-muted d-block">Setores / Indústrias</small>
                    <span class="fw-semibold"><?= listaOuTraco($setores) ?></span>
                </div>
                <div class="col-md-6 mb-3 mt-2">
                    <small class="text-muted d-block">Perfil de impacto desejado</small>
                    <span class="fw-semibold"><?= listaOuTraco($perfilImpacto) ?></span>
                </div>

                <div class="col-md-4 mb-3 mt-2">
                    <small class="text-muted d-block">Alcance do Impacto</small>
                    <span class="fw-semibold">
                        <?php
                            $mapAlcance = [
                                'local' => 'Local',
                                'nacional' => 'Nacional',
                                'global' => 'Global',
                                'todos' => 'Todos os níveis'
                            ];
                            echo htmlspecialchars($mapAlcance[$alcance] ?? '-');
                        ?>
                    </span>
                </div>

                <div class="col-12 mt-4">
                    <small class="text-muted d-block mb-2">ODS de Interesse</small>
                    <?php if (!empty($odsParceiro)): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($odsParceiro as $ods): ?>
                                <span class="badge rounded-pill bg-light text-dark border">
                                    ODS <?= htmlspecialchars($ods['n_ods']) ?> - <?= htmlspecialchars($ods['nome']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span class="fw-semibold">Nenhuma ODS selecionada.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bloco 5: Metadados e Auditoria -->
    <div class="card shadow-sm mb-5 border-0 rounded-4">
        <div class="card-header bg-white border-0 pt-3 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-primary mb-0">
                <i class="bi bi-clock-history me-2"></i>Histórico & Sistema
            </h5>
        </div>
        <div class="card-body px-4 pb-4 pt-3">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <small class="text-muted d-block">Criado em</small>
                    <span class="fw-semibold"><?= dataBr($parceiro['criado_em'] ?? null) ?></span>
                </div>
                <div class="col-md-4 mb-2">
                    <small class="text-muted d-block">Atualizado em</small>
                    <span class="fw-semibold"><?= dataBr($parceiro['atualizado_em'] ?? null) ?></span>
                </div>
                <div class="col-md-4 mb-2">
                    <small class="text-muted d-block">ID Interno</small>
                    <span class="fw-semibold">#<?= (int)$parceiro['id'] ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>
