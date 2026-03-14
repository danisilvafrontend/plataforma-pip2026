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

// Busca todos os dados da Tabela principal de Parceiros e do Contrato
$stmt = $pdo->prepare("
    SELECT p.*, c.tipos_parceria, c.duracao_meses, c.escopo_atuacao, c.escopo_outro 
    FROM parceiros p 
    LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id 
    WHERE p.id = ?
");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

// Decodifica os JSONs
$tipos = !empty($parceiro['tipos_parceria']) ? json_decode($parceiro['tipos_parceria'], true) : [];
$escopo_array = !empty($parceiro['escopo_atuacao']) ? json_decode($parceiro['escopo_atuacao'], true) : [];

if (!is_array($tipos)) $tipos = [];
if (!is_array($escopo_array)) $escopo_array = [];

include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5">
    
    <div class="text-center mb-5">
        <h2 class="fw-bold mb-2">Revisão de Cadastro</h2>
        <p class="text-muted">Por favor, revise todas as suas informações abaixo antes de gerar e assinar sua Carta-Acordo.</p>
        
        <div class="progress mx-auto mt-4" style="height: 10px; max-width: 500px; border-radius: 10px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: 98%" aria-valuenow="98" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <small class="text-muted mt-2 d-block">Quase lá! Etapa de Revisão Final</small>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- BLOCO 0: DADOS CADASTRAIS INICIAIS (cadastro.php) -->
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-primary mb-0"><i class="bi bi-card-heading me-2"></i>Dados Cadastrais Iniciais</h5>
                    <a href="editar_cadastro.php?from=confirmacao" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-pencil me-1"></i> Editar</a>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Razão Social</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($parceiro['razao_social'] ?? 'Não informado') ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Nome Fantasia</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($parceiro['nome_fantasia'] ?? 'Não informado') ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small mb-1">CNPJ</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($parceiro['cnpj'] ?? 'Não informado') ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small mb-1">Representante Legal</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($parceiro['rep_nome'] ?? 'Não informado') ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small mb-1">E-mail de Login</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($parceiro['email_login'] ?? 'Não informado') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BLOCO 1: ENDEREÇO E CONTATOS (etapa1_dados.php) -->
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-primary mb-0"><i class="bi bi-geo-alt me-2"></i>Endereço e Contatos (Etapa 1)</h5>
                    <a href="etapa1_dados.php?from=confirmacao" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-pencil me-1"></i> Editar</a>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="text-muted small mb-1">Sede / Endereço</label>
                            <p class="fw-semibold mb-0">
                                <?= htmlspecialchars($parceiro['endereco_completo'] ?? '') ?> - 
                                <?= htmlspecialchars($parceiro['cidade'] ?? '') ?>/<?= htmlspecialchars($parceiro['estado'] ?? '') ?>
                                (CEP: <?= htmlspecialchars($parceiro['cep'] ?? '') ?>)
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">E-mail do Representante</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($parceiro['rep_email'] ?? 'Não informado') ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Celular / WhatsApp do Representante</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($parceiro['rep_telefone'] ?? 'Não informado') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BLOCO 2 e 3: ESCOPO DO CONTRATO (etapa2_tipo e etapa3_combinado) -->
            <div class="card shadow-sm border-0 mb-5 rounded-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-primary mb-0"><i class="bi bi-handshake me-2"></i>Escopo da Parceria</h5>
                    <div>
                        <a href="etapa2_tipo.php?from=confirmacao" class="btn btn-sm btn-outline-secondary rounded-pill px-3 me-1"><i class="bi bi-pencil me-1"></i> Tipos (Etapa 2)</a>
                        <a href="etapa3_combinado.php?from=confirmacao" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-pencil me-1"></i> Escopo (Etapa 3)</a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <label class="text-muted small mb-2">Tipos de Parceria Oferecidos:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if(!empty($tipos)): ?>
                                <?php foreach($tipos as $t): ?>
                                    <span class="badge bg-light text-dark border py-2 px-3"><?= htmlspecialchars($t) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">Nenhum tipo selecionado.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-0 bg-light p-3 rounded-3 border">
                        <label class="text-muted small fw-bold mb-2"><i class="bi bi-quote me-1"></i>Descrição do Apoio / Escopo de Atuação:</label>
                        
                        <div class="mb-3 d-flex flex-wrap gap-2">
                            <?php if(!empty($escopo_array)): ?>
                                <?php foreach($escopo_array as $esc): ?>
                                    <span class="badge bg-primary text-white py-1 px-2"><?= htmlspecialchars($esc) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if(!empty($parceiro['escopo_outro'])): ?>
                            <p class="mb-3 text-dark" style="font-size: 0.95rem;">
                                <strong>Detalhes adicionais:</strong><br>
                                <?= nl2br(htmlspecialchars($parceiro['escopo_outro'])) ?>
                            </p>
                        <?php endif; ?>

                        <p class="mb-0 text-dark small border-top pt-2 mt-2">
                            <strong>Duração da Parceria:</strong> <?= htmlspecialchars($parceiro['duracao_meses'] ?? 'Não informada') ?> meses
                        </p>
                    </div>
                </div>
            </div>

            <!-- ALERTA E BOTÃO DE AVANÇAR -->
            <div class="alert alert-warning border-warning shadow-sm d-flex align-items-center rounded-3 p-4 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-3 me-4 text-warning"></i>
                <div>
                    <h6 class="fw-bold mb-1 text-dark">Declaração de Veracidade</h6>
                    <p class="mb-0 text-dark small">Declaro que as informações acima são verdadeiras e estou ciente de que elas serão utilizadas para gerar automaticamente a Carta-Acordo e firmar o vínculo com a plataforma.</p>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 border-top pt-4 gap-3">
                <a href="etapa6_juridico.php" class="btn btn-outline-secondary btn-lg px-4 w-100 w-md-auto"><i class="bi bi-arrow-left me-2"></i> Voltar à Etapa 6</a>
                
                <a href="assinar_acordo.php" class="btn btn-success btn-lg px-5 fw-bold shadow-sm w-100 w-md-auto">
                    Avançar para Assinatura <i class="bi bi-file-earmark-check ms-2"></i>
                </a>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
