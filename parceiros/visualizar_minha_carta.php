<?php
session_start();

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Verifica login do parceiro
if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Busca TODOS os dados para o contrato
$stmt = $pdo->prepare("
    SELECT p.*, 
           c.tipos_parceria, c.natureza_parceria, c.nivel_engajamento,
           c.escopo_atuacao, c.escopo_outro, c.oferece_premiacao, c.premio_descricao,
           c.autoriza_marca, c.data_assinatura, c.data_vencimento
    FROM parceiros p
    LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id
    WHERE p.id = ?
");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parceiro || $parceiro['acordo_aceito'] != 1) {
    // Se tentar acessar sem ter assinado
    header("Location: dashboard.php");
    exit;
}

// Decodifica JSONs
$tipos_parceria = !empty($parceiro['tipos_parceria']) ? json_decode($parceiro['tipos_parceria'], true) : [];
$natureza_parceria = !empty($parceiro['natureza_parceria']) ? json_decode($parceiro['natureza_parceria'], true) : [];
$escopo_atuacao = !empty($parceiro['escopo_atuacao']) ? json_decode($parceiro['escopo_atuacao'], true) : [];

if(!is_array($tipos_parceria)) $tipos_parceria = [];
if(!is_array($natureza_parceria)) $natureza_parceria = [];
if(!is_array($escopo_atuacao)) $escopo_atuacao = [];

$tipos_str = implode(', ', $tipos_parceria);
$natureza_str = implode(', ', $natureza_parceria);
$escopo_str = implode(', ', $escopo_atuacao);

// Datas
$data_assinatura_br = !empty($parceiro['data_assinatura']) ? date('d/m/Y', strtotime($parceiro['data_assinatura'])) : 'N/I';
$data_vencimento_br = !empty($parceiro['data_vencimento']) ? date('d/m/Y', strtotime($parceiro['data_vencimento'])) : 'N/I';
$hora_assinatura = !empty($parceiro['data_assinatura']) ? date('H:i:s', strtotime($parceiro['data_assinatura'])) : '';

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container py-5">
    <div class="row">
        
        <!-- SIDEBAR DO PARCEIRO -->
        <div class="col-lg-3 col-md-4 mb-4 mb-md-0 d-print-none">
            <?php include __DIR__ . '/../app/views/parceiros/sidebar.php'; ?>
        </div>

        <!-- CONTEÚDO PRINCIPAL -->
        <div class="col-lg-9 col-md-8">
            
            <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
                <div>
                    <h2 class="fw-bold mb-1">Meus Documentos</h2>
                    <p class="text-muted mb-0">Sua via da Carta-Acordo assinada eletronicamente.</p>
                </div>
            </div>

            <!-- CARD DA CARTA ACORDO -->
            <div class="card shadow-sm border-0 rounded-4 mb-5" id="area-contrato">
                
                <!-- Cabeçalho do Contrato -->
                <div class="card-header text-white p-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center flex-wrap gap-3" style="background-color: #97A327;">
                    <div>
                        <h2 class="mb-0 fw-bold h5 d-flex align-items-center">
                            <i class="bi bi-file-earmark-text me-2"></i>
                            CARTA-ACORDO DE PARCERIA OFICIAL
                        </h2>
                        <p class="mb-0 opacity-75 mt-1 small">IMPACTOS POSITIVOS <?= date('Y', strtotime($parceiro['data_assinatura'] ?? date('Y-m-d'))) ?></p>
                    </div>
                    
                    <button type="button" onclick="window.print();" class="btn btn-light btn-sm text-primary fw-bold shadow-sm d-print-none">
                        <i class="bi bi-printer me-1"></i> Imprimir / Gerar PDF
                    </button>
                </div>
                
                <!-- Corpo do Documento -->
                <div class="card-body p-4 p-md-5 text-dark" style="font-size: 0.95rem; line-height: 1.6;">
                    
                    <p><strong>De um lado,</strong></p>
                    
                    <p><strong>Impactos Positivos Global Platform Inc.</strong>, organização sem fins lucrativos registrada nos Estados Unidos da América sob a categoria 501(c)(3), neste ato representada no Brasil por <strong>Global Vision Access Comunicação e Marketing Ltda.</strong>, pessoa jurídica de direito privado, inscrita no CNPJ nº 08.817.535/0001-61, com sede na Rua Apeninos, 429, conjunto 1206, Aclimação -- São Paulo/SP, doravante denominada <strong>PLATAFORMA IMPACTOS POSITIVOS</strong>;</p>
                    
                    <p><strong>E, de outro lado,</strong></p>
                    
                    <p><strong><?= htmlspecialchars($parceiro['razao_social']) ?></strong>, inscrita sob nº <strong><?= htmlspecialchars($parceiro['cnpj']) ?></strong>, com sede em <strong><?= htmlspecialchars($parceiro['endereco_completo'] ?? '') ?> <?= htmlspecialchars($parceiro['cidade'] ?? '') ?>/<?= htmlspecialchars($parceiro['estado'] ?? '') ?></strong>, neste ato representada por <strong><?= htmlspecialchars($parceiro['rep_nome']) ?></strong>, <strong><?= htmlspecialchars($parceiro['rep_cargo']) ?></strong>, CPF/Documento nº <strong><?= htmlspecialchars($parceiro['rep_cpf'] ?? 'N/I') ?></strong>, doravante denominada <strong>APOIADOR</strong>;</p>
                    
                    <p>Resolvem firmar a presente Carta-Acordo de Parceria Oficial, mediante as seguintes cláusulas e condições:</p>

                    <hr class="my-4" style="border-top: 1px dashed #ccc;">

                    <h6 class="fw-bold mb-3" style="color: #1E3425;">CLÁUSULA 1 -- OBJETO</h6>
                    <p>1.1. O presente instrumento tem por objeto a formalização da parceria institucional entre as partes no âmbito das iniciativas da Plataforma Impactos Positivos.</p>
                    <p>1.2. A parceria poderá envolver ações de comunicação, visibilidade institucional e apoio estratégico, conforme descrito no formulário eletrônico preenchido pelo APOIADOR, que passa a integrar este instrumento.</p>

                    <h6 class="fw-bold mb-3 mt-4" style="color: #1E3425;">CLÁUSULA 2 -- CONTRAPARTIDAS</h6>
                    <p>2.1. A PLATAFORMA IMPACTOS POSITIVOS compromete-se a conceder visibilidade institucional ao APOIADOR por meio de seus canais oficiais.</p>
                    <p>2.2. O APOIADOR compromete-se a cumprir as entregas descritas no campo específico do formulário eletrônico:</p>
                    <div class="bg-light p-3 border-start border-4 border-primary rounded ms-3 mb-3 small">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-1"><strong>Modalidade de Apoio:</strong> <?= htmlspecialchars($tipos_str ?: 'Não especificado') ?></li>
                            <li class="mb-1"><strong>Natureza dos Recursos:</strong> <?= htmlspecialchars($natureza_str ?: 'Não especificado') ?></li>
                            <li class="mb-1"><strong>Nível de Engajamento:</strong> <?= htmlspecialchars(ucfirst($parceiro['nivel_engajamento'] ?? 'Não especificado')) ?></li>
                            <li class="mb-1"><strong>Escopo de Atuação Acordado:</strong> <?= htmlspecialchars($escopo_str ?: 'Não especificado') ?></li>
                            
                            <?php if (!empty($parceiro['escopo_outro'])): ?>
                                <li class="mb-1 mt-2"><strong>Escopos Adicionais:</strong> <?= nl2br(htmlspecialchars($parceiro['escopo_outro'])) ?></li>
                            <?php endif; ?>
                            
                            <?php if (!empty($parceiro['oferece_premiacao'])): ?>
                                <li class="mb-0 mt-3 p-2 bg-white border border-primary rounded text-primary">
                                    <strong><i class="bi bi-gift-fill me-1"></i> Oferta de Premiação:</strong> <?= htmlspecialchars($parceiro['premio_descricao']) ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <h6 class="fw-bold mb-3 mt-4" style="color: #1E3425;">CLÁUSULA 3 -- VIGÊNCIA</h6>
                    <p>3.1. O presente acordo entra em vigor na data da assinatura eletrônica (<strong><?= $data_assinatura_br ?></strong>) e permanecerá válido até <strong><?= $data_vencimento_br ?></strong>.</p>
                    
                    <h6 class="fw-bold mb-3 mt-4" style="color: #1E3425;">CLÁUSULA 4 -- NATUREZA DA RELAÇÃO E APOIO GRATUITO</h6>
                    <p>4.1. A presente parceria possui caráter institucional e colaborativo, não estabelecendo vínculo empregatício, societário ou de exclusividade.</p>
                    <p>4.2. As partes reconhecem que o presente apoio é gratuito, não envolvendo transferência de recursos financeiros entre as partes, salvo se formalizado em documento apartado.</p>

                    <h6 class="fw-bold mb-3 mt-4" style="color: #1E3425;">CLÁUSULA 5 -- USO DE MARCA, INTEGRIDADE E ESG</h6>
                    <p>5.1. O APOIADOR autoriza a utilização de sua marca exclusivamente para fins relacionados às ações da parceria.</p>
                    <p>5.2. As partes reafirmam compromisso com elevados padrões de ética (Lei nº 12.846/2013) e declaram compartilhar dos princípios ambientais, sociais e de governança (ESG) da Plataforma.</p>

                    <h6 class="fw-bold mb-3 mt-4" style="color: #1E3425;">CLÁUSULA 6 -- DISPOSIÇÕES GERAIS</h6>
                    <p>6.1. As partes comprometem-se a cumprir a LGPD (Lei nº 13.709/2018).</p>
                    <p>6.2. O acordo poderá ser encerrado por qualquer parte mediante comunicação formal.</p>
                    <p>6.3. Fica eleito o foro da Comarca de São Paulo/SP para dirimir eventuais controvérsias.</p>

                    <hr class="my-5">

                    <!-- REGISTRO DE ASSINATURA -->
                    <div class="p-4 border border-2 border-success rounded bg-light">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                            <div>
                                <h5 class="fw-bold text-success mb-0">DOCUMENTO ASSINADO DIGITALMENTE</h5>
                                <span class="text-muted small">Via do Apoiador</span>
                            </div>
                        </div>
                        
                        <div class="row text-start mt-4">
                            <div class="col-md-6 border-end">
                                <small class="text-muted d-block text-uppercase fw-bold mb-1">Impactos Positivos Global Platform Inc.</small>
                                <span class="small">Representada por Global Vision Access<br>Comunicação e Marketing Ltda.</span>
                            </div>
                            <div class="col-md-6 ps-md-4 mt-3 mt-md-0">
                                <small class="text-muted d-block text-uppercase fw-bold mb-1">Apoiador Signatário</small>
                                <strong><?= htmlspecialchars($parceiro['razao_social']) ?></strong><br>
                                <span class="small text-muted">
                                    Assinado por: <?= htmlspecialchars($parceiro['rep_nome']) ?><br>
                                    Data: <?= $data_assinatura_br ?> às <?= $hora_assinatura ?><br>
                                    IP: <?= htmlspecialchars($parceiro['acordo_ip'] ?? 'Não registrado') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Estilo nativo para impressão */
@media print {
    body * { visibility: hidden; }
    #area-contrato, #area-contrato * { visibility: visible; }
    #area-contrato {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
    }
    #area-contrato .card-header {
        background: transparent !important;
        color: #000 !important;
        border-bottom: 2px solid #000 !important;
        padding: 0 0 20px 0 !important;
        margin-bottom: 30px !important;
    }
    #area-contrato .card-header h2 { color: #000 !important; font-size: 20px !important; }
    #area-contrato .card-header p { color: #000 !important; opacity: 1 !important; }
    #area-contrato .card-body { padding: 0 !important; }
    .d-print-none { display: none !important; }
    .bg-light, .bg-white { background-color: transparent !important; }
    .border-success { border-color: #000 !important; }
    .text-success { color: #000 !important; }
}
</style>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
