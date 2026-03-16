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

// Verifica se o admin está logado (Ajuste conforme a sua lógica de sessão de admin)
if (!isset($_SESSION['user_id'])) {
    header("Location: /login_admin.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID do parceiro não fornecido.");
}

$parceiro_id = (int)$_GET['id'];

// Busca TODOS os dados para o contrato
$stmt = $pdo->prepare("
    SELECT p.*, 
           c.tipos_parceria, c.natureza_parceria, c.duracao_meses, c.nivel_engajamento,
           c.escopo_atuacao, c.escopo_outro, c.oferece_premiacao, c.premio_descricao,
           c.autoriza_marca, c.data_assinatura, c.data_vencimento
    FROM parceiros p
    LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id
    WHERE p.id = ?
");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parceiro) {
    die('Parceiro não encontrado.');
}

if ($parceiro['acordo_aceito'] != 1) {
    die('Este parceiro ainda não assinou a Carta-Acordo.');
}

// Decodifica JSONs e monta as strings das contrapartidas
$tipos_parceria = !empty($parceiro['tipos_parceria']) ? json_decode($parceiro['tipos_parceria'], true) : [];
$natureza_parceria = !empty($parceiro['natureza_parceria']) ? json_decode($parceiro['natureza_parceria'], true) : [];
$escopo_atuacao = !empty($parceiro['escopo_atuacao']) ? json_decode($parceiro['escopo_atuacao'], true) : [];

if(!is_array($tipos_parceria)) $tipos_parceria = [];
if(!is_array($natureza_parceria)) $natureza_parceria = [];
if(!is_array($escopo_atuacao)) $escopo_atuacao = [];

$tipos_str = implode(', ', $tipos_parceria);
$natureza_str = implode(', ', $natureza_parceria);
$escopo_str = implode(', ', $escopo_atuacao);

// Datas e Vigência
$data_assinatura_br = !empty($parceiro['data_assinatura']) ? date('d/m/Y', strtotime($parceiro['data_assinatura'])) : 'N/I';
$data_vencimento_br = !empty($parceiro['data_vencimento']) ? date('d/m/Y', strtotime($parceiro['data_vencimento'])) : 'N/I';
$hora_assinatura = !empty($parceiro['data_assinatura']) ? date('H:i:s', strtotime($parceiro['data_assinatura'])) : '';

$pageTitle = "Carta-Acordo - " . htmlspecialchars($parceiro['nome_fantasia']);
include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
        <a href="parceiros.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar aos Parceiros
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="card shadow border-0 rounded-0 mb-5" id="area-contrato">
                <!-- Cabeçalho do Contrato -->
                <div class="card-header text-white p-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-3" style="background-color: #00458a;">
                    <div>
                        <h2 class="mb-0 fw-bold h4 d-flex align-items-center">
                            <i class="bi bi-file-earmark-text me-2"></i>
                            CARTA-ACORDO DE PARCERIA OFICIAL
                        </h2>
                        <p class="mb-0 opacity-75 mt-1">IMPACTOS POSITIVOS <?= date('Y', strtotime($parceiro['data_assinatura'] ?? date('Y-m-d'))) ?></p>
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

                    <h5 class="fw-bold mb-3" style="color: #00458a;">CLÁUSULA 1 -- OBJETO</h5>
                    <p>1.1. O presente instrumento tem por objeto a formalização da parceria institucional entre as partes no âmbito das iniciativas da Plataforma Impactos Positivos.</p>
                    <p>1.2. A parceria poderá envolver ações de comunicação, visibilidade institucional e apoio estratégico, conforme descrito no formulário eletrônico preenchido pelo APOIADOR, que passa a integrar este instrumento.</p>

                    <h5 class="fw-bold mb-3 mt-4" style="color: #00458a;">CLÁUSULA 2 -- CONTRAPARTIDAS</h5>
                    <p>2.1. A PLATAFORMA IMPACTOS POSITIVOS compromete-se a conceder visibilidade institucional ao APOIADOR por meio de seus canais oficiais, incluindo redes sociais, newsletters, eventos, releases à imprensa e demais materiais vinculados às ações.</p>
                    
                    <p>2.2. O APOIADOR compromete-se a cumprir as entregas descritas no campo específico do formulário eletrônico:</p>
                    <div class="bg-light p-3 border-start border-4 border-primary rounded ms-3 mb-3">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-1"><strong>Modalidade de Apoio:</strong> <?= htmlspecialchars($tipos_str ?: 'Não especificado') ?></li>
                            <li class="mb-1"><strong>Natureza dos Recursos:</strong> <?= htmlspecialchars($natureza_str ?: 'Não especificado') ?></li>
                            <li class="mb-1"><strong>Nível de Engajamento:</strong> <?= htmlspecialchars(ucfirst($parceiro['nivel_engajamento'] ?? 'Não especificado')) ?></li>
                            <li class="mb-1"><strong>Escopo de Atuação Acordado:</strong> <?= htmlspecialchars($escopo_str ?: 'Não especificado') ?></li>
                            
                            <?php if (!empty($parceiro['escopo_outro'])): ?>
                                <li class="mb-1 mt-2"><strong>Escopos Adicionais Específicos:</strong><br><?= nl2br(htmlspecialchars($parceiro['escopo_outro'])) ?></li>
                            <?php endif; ?>
                            
                            <?php if (!empty($parceiro['oferece_premiacao'])): ?>
                                <li class="mb-0 mt-3 p-2 bg-white border border-success rounded text-success">
                                    <strong><i class="bi bi-gift-fill me-1"></i> Oferta de Premiação aos Ganhadores:</strong><br>
                                    <?= htmlspecialchars($parceiro['premio_descricao']) ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <h5 class="fw-bold mb-3 mt-4" style="color: #00458a;">CLÁUSULA 3 -- VIGÊNCIA</h5>
                    <p>3.1. O presente acordo entra em vigor na data da assinatura eletrônica (<strong><?= $data_assinatura_br ?></strong>) e permanecerá válido até <strong><?= $data_vencimento_br ?></strong> (Duração: <?= htmlspecialchars($parceiro['duracao_meses']) ?> meses).</p>
                    <p>3.2. Poderá ser renovado mediante comum acordo entre as partes.</p>

                    <h5 class="fw-bold mb-3 mt-4" style="color: #00458a;">CLÁUSULA 4 -- NATUREZA DA RELAÇÃO</h5>
                    <p>4.1. A presente parceria possui caráter institucional e colaborativo.</p>
                    <p>4.2. Não estabelece vínculo empregatício, societário, associativo, representação comercial ou exclusividade entre as partes.</p>
                    <p>4.3. Cada parte atuará de forma independente, sendo responsável por suas obrigações legais e regulatórias.</p>

                    <h5 class="fw-bold mb-3 mt-4" style="color: #00458a;">CLÁUSULA 5 -- NATUREZA GRATUITA DO APOIO</h5>
                    <p>5.1. As partes reconhecem que o presente apoio é gratuito, não envolvendo transferência de recursos financeiros entre as partes.</p>
                    <p>5.2. Não há remuneração, cobrança de valores ou obrigação financeira decorrente deste instrumento, salvo se formalizado acordo específico em documento apartado.</p>

                    <h5 class="fw-bold mb-3 mt-4" style="color: #00458a;">CLÁUSULA 6 -- USO DE MARCA</h5>
                    <p>6.1. O APOIADOR autoriza a utilização de sua marca e identidade visual exclusivamente para fins relacionados às ações da parceria. <?= !empty($parceiro['autoriza_marca']) ? '<em>(Autorização formal registrada na plataforma)</em>' : '' ?></p>
                    <p>6.2. O uso da marca Impactos Positivos pelo APOIADOR deverá estar vinculado exclusivamente às ações formalmente acordadas.</p>
                    <p>6.3. Qualquer utilização fora do escopo da parceria deverá ser previamente alinhada por escrito.</p>

                    <h5 class="fw-bold mb-3 mt-4" style="color: #00458a;">CLÁUSULA 7 -- INTEGRIDADE E CONFORMIDADE</h5>
                    <p>7.1. As partes reafirmam seu compromisso com elevados padrões de ética e integridade, observando a legislação aplicável, incluindo a Lei nº 12.846/2013 (Lei Anticorrupção Brasileira), quando aplicável.</p>
                    <p>7.2. Comprometem-se a atuar com transparência e a não adotar práticas que possam configurar vantagem indevida ou violação normativa.</p>

                    <h5 class="fw-bold mb-3 mt-4" style="color: #00458a;">CLÁUSULA 8 -- COMPROMISSO ESG</h5>
                    <p>8.1. O APOIADOR declara compartilhar dos princípios ambientais, sociais e de governança (ESG) que norteiam a Plataforma Impactos Positivos.</p>
                    <p>8.2. Ambas as partes se comprometem a atuar de forma ética, sustentável, responsável e alinhada aos direitos humanos e à legislação ambiental vigente.</p>

                    <h5 class="fw-bold mb-3 mt-4" style="color: #00458a;">CLÁUSULA 9 -- PROTEÇÃO DE DADOS</h5>
                    <p>9.1. As partes comprometem-se a cumprir a Lei Geral de Proteção de Dados (Lei nº 13.709/2018 -- LGPD).</p>
                    <p>9.2. Os dados pessoais eventualmente compartilhados serão utilizados exclusivamente para finalidades relacionadas a esta parceria.</p>

                    <h5 class="fw-bold mb-3 mt-4" style="color: #00458a;">CLÁUSULA 10 -- LIMITAÇÃO DE RESPONSABILIDADE</h5>
                    <p>10.1. A parceria possui natureza institucional e de visibilidade, não havendo garantia de resultados financeiros, comerciais ou midiáticos.</p>
                    <p>10.2. Cada parte será responsável exclusivamente por seus próprios atos e decisões, não havendo responsabilidade solidária perante terceiros.</p>

                    <h5 class="fw-bold mb-3 mt-4" style="color: #00458a;">CLÁUSULA 11 -- RESCISÃO</h5>
                    <p>11.1. O presente acordo poderá ser encerrado por qualquer das partes mediante comunicação formal, preservando-se as ações já realizadas até a data do encerramento.</p>

                    <h5 class="fw-bold mb-3 mt-4" style="color: #00458a;">CLÁUSULA 12 -- FORO</h5>
                    <p>12.1. Para dirimir eventuais controvérsias oriundas deste instrumento, fica eleito o foro da Comarca de São Paulo/SP, Brasil.</p>

                    <hr class="my-5">

                    <!-- REGISTRO DE ASSINATURA -->
                    <div class="p-4 border border-2 border-success rounded bg-white">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                            <h4 class="fw-bold text-success mb-0">DOCUMENTO ASSINADO DIGITALMENTE</h4>
                        </div>
                        
                        <p class="mb-4">O <strong>APOIADOR</strong> aceitou os termos deste instrumento via plataforma eletrônica, possuindo validade legal.</p>
                        
                        <div class="row text-start">
                            <div class="col-md-5 border-end">
                                <small class="text-muted d-block text-uppercase fw-bold mb-1">Impactos Positivos Global Platform Inc.</small>
                                <span class="small">Representada por Global Vision Access<br>Comunicação e Marketing Ltda.</span>
                            </div>
                            <div class="col-md-6 ms-md-3 mt-3 mt-md-0">
                                <small class="text-muted d-block text-uppercase fw-bold mb-1">Apoiador Signatário</small>
                                <strong><?= htmlspecialchars($parceiro['razao_social']) ?> (<?= htmlspecialchars($parceiro['cnpj']) ?>)</strong><br>
                                <span class="small">
                                    Representante: <?= htmlspecialchars($parceiro['rep_nome']) ?> (<?= htmlspecialchars($parceiro['rep_cargo']) ?>)<br>
                                    Data de Assinatura: <?= $data_assinatura_br ?> às <?= $hora_assinatura ?><br>
                                    Endereço IP: <?= htmlspecialchars($parceiro['acordo_ip'] ?? 'Não registrado') ?>
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
/* Estilo nativo para impressão - Identico ao do Parceiro */
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

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>
