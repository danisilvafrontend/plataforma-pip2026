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

// Helpers de e-mail 
require_once __DIR__ . '/../app/helpers/mail.php';
require_once __DIR__ . '/../app/helpers/email_template.php';

if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Busca TODOS os dados para o contrato
$stmt = $pdo->prepare("
    SELECT p.*, 
           c.tipos_parceria, c.natureza_parceria, c.duracao_meses, c.nivel_engajamento,
           c.escopo_atuacao, c.escopo_outro, c.oferece_premiacao, c.premio_descricao,
           c.facebook_url, c.instagram_url, c.linkedin_url, c.youtube_url, c.autoriza_marca
    FROM parceiros p
    LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id
    WHERE p.id = ?
");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parceiro || empty($parceiro['razao_social'])) {
    die('Dados do parceiro não encontrados.');
}

// Se já assinou, redireciona
if ($parceiro['acordo_aceito'] == 1) {
    header("Location: dashboard.php?msg=ja_assinado");
    exit;
}

// Decodifica JSONs e monta as strings das contrapartidas
$tipos_parceria = !empty($parceiro['tipos_parceria']) ? json_decode($parceiro['tipos_parceria'], true) : [];
$natureza_parceria = !empty($parceiro['natureza_parceria']) ? json_decode($parceiro['natureza_parceria'], true) : [];
$escopo_atuacao = !empty($parceiro['escopo_atuacao']) ? json_decode($parceiro['escopo_atuacao'], true) : [];

$tipos_str = implode(', ', $tipos_parceria);
$natureza_str = implode(', ', $natureza_parceria);
$escopo_str = implode(', ', $escopo_atuacao);

// Cálculo das datas
$duracao_meses = $parceiro['duracao_meses'] ?? 12;
$data_assinatura = date('Y-m-d H:i:s');
$data_vencimento = date('Y-m-d H:i:s', strtotime("+{$duracao_meses} months"));

$data_assinatura_br = date('d/m/Y');
$data_vencimento_br = date('d/m/Y', strtotime($data_vencimento));


// PROCESSA A ASSINATURA (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aceito'])) {
    $ip = $_SERVER['REMOTE_ADDR'];

    try {
        $pdo->beginTransaction();

        // 1. Atualiza a tabela principal
        $stmtParceiro = $pdo->prepare("UPDATE parceiros SET acordo_aceito = 1, acordo_data = ?, acordo_ip = ?, status = 'analise', etapa_atual = 7 WHERE id = ?");
        $stmtParceiro->execute([$data_assinatura, $ip, $parceiro_id]);

        // 2. Atualiza a tabela de contratos
        $stmtContrato = $pdo->prepare("UPDATE parceiro_contrato SET data_assinatura = ?, data_vencimento = ? WHERE parceiro_id = ?");
        $stmtContrato->execute([$data_assinatura, $data_vencimento, $parceiro_id]);

        // 3. E-mail pros admins
        $stmtAdmins = $pdo->query("SELECT nome, email FROM users WHERE role IN ('superadmin', 'admin') AND status = 'ativo'");
        $admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($admins)) {
            $subject = "Nova Carta-Acordo Assinada: " . $parceiro['nome_fantasia'];
            $link_admin = get_base_url() . "/admin/parceiros.php";
                $body = "
                <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #eaeaea; border-radius: 8px; padding: 30px; background-color: #ffffff;'>
                    
                    <h2 style='color: #0d6efd; border-bottom: 2px solid #e9f2ff; padding-bottom: 10px; margin-top: 0;'>
                        Nova Assinatura Recebida
                    </h2>
                    
                    <p>Olá, Equipe Impactos Positivos,</p>
                    <p>A organização abaixo acaba de assinar a Carta-Acordo digitalmente e aguarda a ativação da parceria na plataforma.</p>
                    
                    <div style='background-color: #f8f9fa; padding: 20px; border-left: 4px solid #0d6efd; margin: 25px 0; border-radius: 4px;'>
                        <h4 style='margin: 0 0 15px 0; color: #444; border-bottom: 1px solid #ddd; padding-bottom: 5px;'>Detalhes do Parceiro</h4>
                        <p style='margin: 0 0 8px 0;'><strong>Organização:</strong> {$parceiro['nome_fantasia']}</p>
                        <p style='margin: 0 0 8px 0;'><strong>CNPJ:</strong> {$parceiro['cnpj']}</p>
                        
                        <h4 style='margin: 20px 0 10px 0; color: #444; border-bottom: 1px solid #ddd; padding-bottom: 5px;'>Dados da Assinatura</h4>
                        <p style='margin: 0 0 8px 0;'><strong>Representante Legal:</strong> {$parceiro['rep_nome']}</p>
                        <p style='margin: 0 0 8px 0;'><strong>Data e Hora:</strong> " . date('d/m/Y \à\s H:i:s') . "</p>
                        <p style='margin: 0 0 0 0;'><strong>IP Registrado:</strong> {$_SERVER['REMOTE_ADDR']}</p>
                    </div>

                    <div style='background-color: #fff3cd; color: #842029; padding: 12px 15px; border: 1px solid #f5c2c7; border-radius: 5px; font-size: 14px; margin-bottom: 25px;'>
                        <strong>Próximo passo:</strong> Verifique o documento assinado no painel e clique em \"Ativar Parceria\" para liberar o acesso da organização.
                    </div>
                    
                    <p style='text-align: center; margin: 35px 0;'>
                        <a href='{$link_admin}' style='background-color: #0d6efd; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block; box-shadow: 0 4px 6px rgba(13,110,253,0.2);'>
                            Acessar Painel e Ativar
                        </a>
                    </p>
                    
                    <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                    <p style='font-size: 12px; color: #999; text-align: center; margin: 0;'>
                        Este é um aviso automático do sistema Impactos Positivos.<br>
                        " . date('Y') . " © Todos os direitos reservados.
                    </p>
                </div>
            ";

            
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "From: Plataforma Impactos Positivos <nao-responda@dscriacaoweb.com.br>\r\n";

            foreach ($admins as $admin) {
                // Usa a função send_mail com o nome correto do admin vindo do banco
                send_mail($admin['email'], $admin['nome'], $subject, $body, $headers);
            }
        }


        $pdo->commit();
        header("Location: dashboard.php?msg=sucesso");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao assinar acordo: " . $e->getMessage());
        $erro = "Ocorreu um erro ao processar sua assinatura. Tente novamente.";
    }
}

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Progresso final -->
            <div class="mb-5">
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span class="fw-bold text-success">Etapa 7 - Assinatura Digital</span>
                    <span>7 de 7</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>

            <?php if (isset($erro)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <div class="card shadow-lg border-0 rounded-4" id="area-contrato">
                <div class="card-header bg-gradient p-4 border-0 rounded-top-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2 class="mb-0 fw-bold h4 d-flex align-items-center">
                            <i class="bi bi-file-earmark-text me-2"></i>
                            CARTA-ACORDO DE PARCERIA OFICIAL
                        </h2>
                        <p class="mb-0 opacity-75 mt-1">IMPACTOS POSITIVOS <?= date('Y') ?></p>
                    </div>
                    
                    <!-- Botão Imprimir / PDF -->
                    <button type="button" onclick="window.print();" class="btn btn-light btn-sm text-primary fw-bold shadow-sm d-print-none">
                        <i class="bi bi-printer me-1"></i> Imprimir / Salvar PDF
                    </button>
                </div>

                
                <div class="card-body p-4 p-md-5 text-dark" style="font-size: 0.95rem; line-height: 1.6;">
                    
                    <p><strong>De um lado,</strong></p>
                    
                    <p><strong>Impactos Positivos Global Platform Inc.</strong>, organização sem fins lucrativos registrada nos Estados Unidos da América sob a categoria 501(c)(3), neste ato representada no Brasil por <strong>Global Vision Access Comunicação e Marketing Ltda.</strong>, pessoa jurídica de direito privado, inscrita no CNPJ nº 08.817.535/0001-61, com sede na Rua Apeninos, 429, conjunto 1206, Aclimação -- São Paulo/SP, doravante denominada <strong>PLATAFORMA IMPACTOS POSITIVOS</strong>;</p>
                    
                    <p><strong>E, de outro lado,</strong></p>
                    
                    <p><strong><?= htmlspecialchars($parceiro['razao_social']) ?></strong>, inscrita sob nº <strong><?= htmlspecialchars($parceiro['cnpj']) ?></strong>, com sede em <strong><?= htmlspecialchars($parceiro['endereco_completo']) ?></strong>, neste ato representada por <strong><?= htmlspecialchars($parceiro['rep_nome']) ?></strong>, <strong><?= htmlspecialchars($parceiro['rep_cargo']) ?></strong>, CPF/Documento nº <strong><?= htmlspecialchars($parceiro['rep_cpf'] ?? 'N/I') ?></strong>, doravante denominada <strong>APOIADOR</strong>;</p>
                    
                    <p>Resolvem firmar a presente Carta-Acordo de Parceria Oficial -- <?= date('Y') ?>, mediante as seguintes cláusulas e condições:</p>

                    <hr class="my-4">

                    <h5 class="fw-bold text-primary mb-3">CLÁUSULA 1 -- OBJETO</h5>
                    <p>1.1. O presente instrumento tem por objeto a formalização da parceria institucional entre as partes no âmbito das iniciativas da Plataforma Impactos Positivos durante o ano de <?= date('Y') ?>.</p>
                    <p>1.2. A parceria poderá envolver ações de comunicação, visibilidade institucional e apoio estratégico, conforme descrito no formulário eletrônico preenchido pelo APOIADOR, que passa a integrar este instrumento.</p>

                    <h5 class="fw-bold text-primary mb-3 mt-4">CLÁUSULA 2 -- CONTRAPARTIDAS</h5>
                    <p>2.1. A PLATAFORMA IMPACTOS POSITIVOS compromete-se a conceder visibilidade institucional ao APOIADOR por meio de seus canais oficiais, incluindo redes sociais, newsletters, eventos, releases à imprensa e demais materiais vinculados às ações de <?= date('Y') ?>.</p>
                    
                    <p>2.2. O APOIADOR compromete-se a cumprir as entregas descritas no campo específico do formulário eletrônico:</p>
                    <div class="bg-light p-3 border-start border-4 border-primary rounded ms-3 mb-3">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-1"><strong>Modalidade de Apoio:</strong> <?= htmlspecialchars($tipos_str ?: 'Não especificado') ?></li>
                            <li class="mb-1"><strong>Natureza dos Recursos:</strong> <?= htmlspecialchars($natureza_str ?: 'Não especificado') ?></li>
                            <li class="mb-1"><strong>Nível de Engajamento:</strong> <?= htmlspecialchars(ucfirst($parceiro['nivel_engajamento'] ?? 'Não especificado')) ?></li>
                            <li class="mb-1"><strong>Escopo de Atuação Acordado:</strong> <?= htmlspecialchars($escopo_str ?: 'Não especificado') ?></li>
                            
                            <?php if (!empty($parceiro['escopo_outro'])): ?>
                                <li class="mb-1"><strong>Escopos Adicionais Específicos:</strong> <?= htmlspecialchars($parceiro['escopo_outro']) ?></li>
                            <?php endif; ?>
                            
                            <?php if (!empty($parceiro['oferece_premiacao'])): ?>
                                <li class="mb-0 mt-2 text-primary">
                                    <strong><i class="bi bi-gift-fill me-1"></i> Oferta de Premiação aos Ganhadores:</strong> 
                                    <?= htmlspecialchars($parceiro['premio_descricao']) ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <h5 class="fw-bold text-primary mb-3 mt-4">CLÁUSULA 3 -- VIGÊNCIA</h5>
                    <p>3.1. O presente acordo entra em vigor na data da assinatura eletrônica (<strong><?= $data_assinatura_br ?></strong>) e permanecerá válido até <strong><?= $data_vencimento_br ?></strong>.</p>
                    <p>3.2. Poderá ser renovado mediante comum acordo entre as partes.</p>

                    <h5 class="fw-bold text-primary mb-3 mt-4">CLÁUSULA 4 -- NATUREZA DA RELAÇÃO</h5>
                    <p>4.1. A presente parceria possui caráter institucional e colaborativo.</p>
                    <p>4.2. Não estabelece vínculo empregatício, societário, associativo, representação comercial ou exclusividade entre as partes.</p>
                    <p>4.3. Cada parte atuará de forma independente, sendo responsável por suas obrigações legais e regulatórias.</p>

                    <h5 class="fw-bold text-primary mb-3 mt-4">CLÁUSULA 5 -- NATUREZA GRATUITA DO APOIO</h5>
                    <p>5.1. As partes reconhecem que o presente apoio é gratuito, não envolvendo transferência de recursos financeiros entre as partes.</p>
                    <p>5.2. Não há remuneração, cobrança de valores ou obrigação financeira decorrente deste instrumento, salvo se formalizado acordo específico em documento apartado.</p>

                    <h5 class="fw-bold text-primary mb-3 mt-4">CLÁUSULA 6 -- USO DE MARCA</h5>
                    <p>6.1. O APOIADOR autoriza a utilização de sua marca e identidade visual exclusivamente para fins relacionados às ações da parceria <?= date('Y') ?>. <?= !empty($parceiro['autoriza_marca']) ? '<span class="badge bg-primary ms-1">Autorização concedida na Etapa 6</span>' : '' ?></p>
                    <p>6.2. O uso da marca Impactos Positivos pelo APOIADOR deverá estar vinculado exclusivamente às ações formalmente acordadas.</p>
                    <p>6.3. Qualquer utilização fora do escopo da parceria deverá ser previamente alinhada por escrito.</p>

                    <h5 class="fw-bold text-primary mb-3 mt-4">CLÁUSULA 7 -- INTEGRIDADE E CONFORMIDADE</h5>
                    <p>7.1. As partes reafirmam seu compromisso com elevados padrões de ética e integridade, observando a legislação aplicável, incluindo a Lei nº 12.846/2013 (Lei Anticorrupção Brasileira), quando aplicável.</p>
                    <p>7.2. Comprometem-se a atuar com transparência e a não adotar práticas que possam configurar vantagem indevida ou violação normativa.</p>
                    <p>7.3. Eventuais situações que possam comprometer esses princípios deverão ser tratadas com diálogo e responsabilidade institucional.</p>

                    <h5 class="fw-bold text-primary mb-3 mt-4">CLÁUSULA 8 -- COMPROMISSO ESG</h5>
                    <p>8.1. O APOIADOR declara compartilhar dos princípios ambientais, sociais e de governança (ESG) que norteiam a Plataforma Impactos Positivos.</p>
                    <p>8.2. Ambas as partes se comprometem a atuar de forma ética, sustentável, responsável e alinhada aos direitos humanos e à legislação ambiental vigente.</p>

                    <h5 class="fw-bold text-primary mb-3 mt-4">CLÁUSULA 9 -- PROTEÇÃO DE DADOS</h5>
                    <p>9.1. As partes comprometem-se a cumprir a Lei Geral de Proteção de Dados (Lei nº 13.709/2018 -- LGPD).</p>
                    <p>9.2. Os dados pessoais eventualmente compartilhados serão utilizados exclusivamente para finalidades relacionadas a esta parceria.</p>
                    <p>9.3. Cada parte será responsável pelos dados sob sua gestão, adotando medidas adequadas de segurança.</p>

                    <h5 class="fw-bold text-primary mb-3 mt-4">CLÁUSULA 10 -- LIMITAÇÃO DE RESPONSABILIDADE</h5>
                    <p>10.1. A parceria possui natureza institucional e de visibilidade, não havendo garantia de resultados financeiros, comerciais ou midiáticos.</p>
                    <p>10.2. Cada parte será responsável exclusivamente por seus próprios atos e decisões, não havendo responsabilidade solidária perante terceiros.</p>

                    <h5 class="fw-bold text-primary mb-3 mt-4">CLÁUSULA 11 -- RESCISÃO</h5>
                    <p>11.1. O presente acordo poderá ser encerrado por qualquer das partes mediante comunicação formal, preservando-se as ações já realizadas até a data do encerramento.</p>

                    <h5 class="fw-bold text-primary mb-3 mt-4">CLÁUSULA 12 -- FORO</h5>
                    <p>12.1. Para dirimir eventuais controvérsias oriundas deste instrumento, fica eleito o foro da Comarca de São Paulo/SP, Brasil.</p>

                    <hr class="my-5">

                    <!-- BLOCO DE ASSINATURA -->
                    <div class="bg-light p-4 rounded border text-center">
                        <h5 class="fw-bold mb-1 text-primary"><i class="bi bi-shield-lock-fill me-2"></i>Assinatura Eletrônica</h5>
                        <p class="mb-4 text-muted small">
                            Ao clicar em "Li e Aceito", você estará assinando este documento digitalmente. Seu endereço IP (<strong><?= $_SERVER['REMOTE_ADDR'] ?></strong>) e a data atual serão registrados, possuindo validade jurídica e legal.
                        </p>
                        
                        <div class="row text-start justify-content-center mb-4">
                            <div class="col-md-5 border-end">
                                <small class="text-muted d-block">PLATAFORMA IMPACTOS POSITIVOS</small>
                                <strong>Impactos Positivos Global Platform Inc.</strong><br>
                                <span class="small">Representada por Global Vision Access<br>Comunicação e Marketing Ltda.</span>
                            </div>
                            <div class="col-md-5 ms-md-3 mt-3 mt-md-0">
                                <small class="text-muted d-block">APOIADOR</small>
                                <strong><?= htmlspecialchars($parceiro['razao_social']) ?></strong><br>
                                <span class="small">Representante Legal: <?= htmlspecialchars($parceiro['rep_nome']) ?></span><br>
                                <span class="small">
                                    <strong>Data:</strong> <?= date('d/m/Y \à\s H:i') ?> | <strong>IP:</strong> <?= $_SERVER['REMOTE_ADDR'] ?>
                                </span>
                            </div>
                        </div>

                        <form method="POST" action="" onsubmit="return confirm('Tem certeza que deseja assinar este documento digitalmente? Esta ação não pode ser desfeita.');" class="d-print-none">
                            <div class="form-check d-inline-block text-start mb-4">
                                <input class="form-check-input" type="checkbox" name="aceito" id="aceito" value="1" required style="transform: scale(1.3); margin-right: 10px;">
                                <label class="form-check-label fw-bold text-dark" for="aceito" style="font-size: 1.1rem; cursor: pointer;">
                                    Li e concordo e assino digitalmente esta Carta-Acordo.
                                </label>
                            </div>
                            <br>
                            <button type="submit" class="btn btn-success btn-lg px-5 fw-bold shadow-sm">
                                <i class="bi bi-pen me-2"></i> Finalizar e Assinar Contrato
                            </button>
                        </form>
                    </div>

                </div>
            </div>            

            <!-- Botão Voltar (Apenas link visual) -->
            <div class="text-center mt-4 mb-5">
                <a href="confirmacao.php" class="text-secondary text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Voltar e revisar dados</a>
            </div>
        </div>
    </div>
</div>

<style>
/* CSS EXCLUSIVO PARA A IMPRESSÃO */
@media print {
    /* Esconde o que não é o contrato */
    body * {
        visibility: hidden;
    }
    
    /* Mostra apenas a área do contrato */
    #area-contrato, #area-contrato * {
        visibility: visible;
    }
    
    /* Reposiciona a área do contrato para o topo da folha */
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

    /* Ajustes visuais para economizar tinta e focar no texto */
    #area-contrato .card-header {
        background: transparent !important;
        color: #000 !important;
        border-bottom: 2px solid #000 !important;
        padding: 0 0 20px 0 !important;
        margin-bottom: 30px !important;
    }
    
    #area-contrato .card-header h2 {
        color: #000 !important;
        font-size: 20px !important;
    }
    
    #area-contrato .card-header p.opacity-75 {
        color: #000 !important;
        opacity: 1 !important;
    }

    #area-contrato .card-body {
        padding: 0 !important;
    }

    /* Esconde botões dentro do documento na impressão */
    .d-print-none {
        display: none !important;
    }
    
    /* Força fundos e bordas que o navegador costuma tirar */
    .bg-light {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>


<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>

