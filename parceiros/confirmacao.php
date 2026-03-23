<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Verifica o login do parceiro
if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Busca todos os dados da Tabela principal de Parceiros e do Contrato
$stmt = $pdo->prepare("
    SELECT p.*, 
           c.tipos_parceria, c.natureza_parceria, c.escopo_atuacao, c.escopo_outro, 
           c.nivel_engajamento, c.oferece_premiacao, c.premio_descricao,
           c.deseja_publicar, c.rede_impacto 
    FROM parceiros p
    LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id
    WHERE p.id = ?
");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

// Decodifica os JSONs
$tipos = !empty($parceiro['tipos_parceria']) ? json_decode($parceiro['tipos_parceria'], true) : [];
$naturezas = !empty($parceiro['natureza_parceria']) ? json_decode($parceiro['natureza_parceria'], true) : [];
$escopo = !empty($parceiro['escopo_atuacao']) ? json_decode($parceiro['escopo_atuacao'], true) : [];
$publicacoes = !empty($parceiro['deseja_publicar']) ? json_decode($parceiro['deseja_publicar'], true) : [];
$rede_impacto = $parceiro['rede_impacto'] ?? '';

if (!is_array($tipos)) $tipos = [];
if (!is_array($naturezas)) $naturezas = [];
if (!is_array($escopo)) $escopo = [];
if (!is_array($publicacoes)) $publicacoes = [];

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="text-center mb-5">
                <h2 class="fw-bold text-dark mb-2">Revisão Final</h2>
                <p class="text-muted">Por favor, revise as informações abaixo antes de gerar e assinar sua Carta-Acordo.</p>
            </div>

            <!-- BLOCO 1: DADOS INSTITUCIONAIS -->
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="fw-bold text-primary mb-0">Etapa 1: Dados da Instituição</h5>
                    <a href="etapa1_dados.php?from=confirmacao" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong class="text-muted d-block small">Nome Fantasia</strong>
                            <span><?= htmlspecialchars($parceiro['nome_fantasia']) ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong class="text-muted d-block small">Razão Social</strong>
                            <span><?= htmlspecialchars($parceiro['razao_social']) ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong class="text-muted d-block small">CNPJ</strong>
                            <span><?= htmlspecialchars($parceiro['cnpj']) ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong class="text-muted d-block small">Localização</strong>
                            <span><?= htmlspecialchars($parceiro['cidade'] ?? '') ?> - <?= htmlspecialchars($parceiro['estado'] ?? '') ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong class="text-muted d-block small">Representante Legal</strong>
                            <span><?= htmlspecialchars($parceiro['rep_nome']) ?> (<?= htmlspecialchars($parceiro['rep_cargo']) ?>)</span>
                            <br><span class="small text-muted">CPF: <?= htmlspecialchars($parceiro['rep_cpf'] ?? 'Não informado') ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong class="text-muted d-block small">Contatos do Representante</strong>
                            <span><?= htmlspecialchars($parceiro['rep_email']) ?></span><br>
                            <span><?= htmlspecialchars($parceiro['rep_telefone'] ?? '') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BLOCO 2: TIPO E NATUREZA -->
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="fw-bold text-primary mb-0">Etapa 2: Tipo de Parceria</h5>
                    <a href="etapa2_tipo.php?from=confirmacao" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <strong class="text-muted d-block small mb-2">Papéis selecionados:</strong>
                            <?php if(!empty($tipos)): ?>
                                <ul class="mb-0 ps-3">
                                    <?php foreach($tipos as $t): ?>
                                        <li><?= htmlspecialchars($t) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">Nenhum tipo selecionado.</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-muted d-block small mb-2">Natureza dos recursos:</strong>
                            <?php if(!empty($naturezas)): ?>
                                <ul class="mb-0 ps-3">
                                    <?php foreach($naturezas as $n): ?>
                                        <li><?= htmlspecialchars($n) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">Nenhuma natureza selecionada.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BLOCO 3: O COMBINADO -->
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="fw-bold text-primary mb-0">Etapa 3: O Nosso Acordo</h5>
                    <a href="etapa3_combinado.php?from=confirmacao" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <strong class="text-muted d-block small">Nível de Engajamento</strong>
                            <span><?= htmlspecialchars(ucfirst($parceiro['nivel_engajamento'] ?? 'Não informado')) ?></span>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <strong class="text-muted d-block small mb-2">Escopo de Atuação:</strong>
                            <?php if(!empty($escopo)): ?>
                                <ul class="mb-1 ps-3">
                                    <?php foreach($escopo as $e): ?>
                                        <li><?= htmlspecialchars($e) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">Nenhum escopo selecionado.</span>
                            <?php endif; ?>
                            
                            <?php if(!empty($parceiro['escopo_outro'])): ?>
                                <div class="mt-2 p-2 bg-light rounded small">
                                    <strong>Outro:</strong> <?= htmlspecialchars($parceiro['escopo_outro']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if(!empty($parceiro['oferece_premiacao'])): ?>
                        <div class="col-12">
                            <strong class="text-success d-block small mb-1"><i class="bi bi-gift me-1"></i> Premiação Oferecida:</strong>
                            <div class="p-2 border border-success rounded bg-light small">
                                <?= htmlspecialchars($parceiro['premio_descricao']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- BLOCO 5: PLATAFORMA -->
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="fw-bold text-primary mb-0">Etapa 5: Uso da Plataforma</h5>
                    <a href="etapa5_plataforma.php?from=confirmacao" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
                </div>
                <div class="card-body p-4">
                    <strong class="text-muted d-block small mb-2">Deseja Publicar/Promover:</strong>
                    <?php if(!empty($publicacoes)): ?>
                        <ul class="mb-3 ps-3">
                            <?php foreach($publicacoes as $pub): ?>
                                <li><?= htmlspecialchars($pub) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted small">Nada selecionado.</p>
                    <?php endif; ?>

                    <strong class="text-muted d-block small mb-1">Participação na Rede de Impacto:</strong>
                    <span>
                        <?php 
                            if($rede_impacto === 'sim') echo "Sim, quero participar";
                            elseif($rede_impacto === 'nao') echo "Não";
                            elseif($rede_impacto === 'avaliar_depois') echo "Avaliar depois";
                            else echo "Não informado";
                        ?>
                    </span>
                </div>
            </div>

            <!-- DECLARAÇÃO FINAL E INSTRUÇÕES -->
            <div class="card shadow-sm border-0 rounded-3 mb-4 bg-light border-start border-4 border-warning">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="bi bi-info-circle-fill text-warning me-2"></i> Próximo Passo: Geração da Carta-Acordo
                    </h6>
                    <p class="small text-muted mb-4">
                        Ao avançar, o sistema irá gerar o documento oficial da <strong>Carta-Acordo de Parceria</strong> utilizando os dados que você acabou de revisar. <br>
                        <strong>Fique tranquilo(a):</strong> você ainda poderá ler o documento completo e verificar todas as cláusulas na próxima tela <strong>antes</strong> de realizar a assinatura digital.
                    </p>
                    
                    <div class="form-check d-inline-block text-start p-3 bg-white border rounded w-100 shadow-sm">
                        <input class="form-check-input ms-1" type="checkbox" id="checkRevisao" required style="transform: scale(1.2); margin-top: 5px; cursor: pointer;">
                        <label class="form-check-label fw-bold text-dark ms-2" for="checkRevisao" style="cursor: pointer;">
                            Declaro que as informações acima foram revisadas e são verdadeiras, e desejo gerar a minuta da Carta-Acordo.
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mb-5 flex-wrap gap-3">
                <a href="etapa6_juridico.php" class="btn btn-outline-secondary btn-lg fw-bold px-4">
                    <i class="bi bi-arrow-left me-2"></i> Voltar e Editar
                </a>
                <a href="assinar_acordo.php" class="btn btn-primary btn-lg px-5 fw-bold text-white shadow-sm" id="btnAvancar" style="pointer-events: none; opacity: 0.5;">
                    <i class="bi bi-file-earmark-text me-2"></i> Gerar e Ler Carta-Acordo <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById('checkRevisao').addEventListener('change', function() {
    const btn = document.getElementById('btnAvancar');
    if(this.checked) {
        btn.style.pointerEvents = 'auto';
        btn.style.opacity = '1';
        // Efeito de pulso rápido para incentivar o clique
        btn.classList.add('animate__animated', 'animate__pulse');
        setTimeout(() => btn.classList.remove('animate__animated', 'animate__pulse'), 1000);
    } else {
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.5';
    }
});
</script>


<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
