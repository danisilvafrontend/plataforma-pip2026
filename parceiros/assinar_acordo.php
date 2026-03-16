<?php
session_start();
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Inclui a sua função de envio de e-mail (o mesmo usado no store.php)
require_once __DIR__ . '/../app/helpers/mail.php';
require_once __DIR__ . '/../app/helpers/email_template.php';

if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Pega os dados do parceiro para preencher o contrato
$stmt = $pdo->prepare("SELECT p.*, c.tipos_parceria, c.escopo_atuacao, c.duracao_meses 
                       FROM parceiros p 
                       LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id 
                       WHERE p.id = ?");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

// Se já assinou, manda pro dashboard
if ($parceiro['acordo_aceito'] == 1) {
    header("Location: dashboard.php?msg=ja_assinado");
    exit;
}

// PROCESSA A ASSINATURA (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aceito'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $data_atual = date('Y-m-d H:i:s');
    
    // Cálculo das datas de vigência
    $duracao_meses = $parceiro['duracao_meses'] ?? '12';
    $data_vencimento = null;
    
    if (is_numeric($duracao_meses)) {
        $data_vencimento = date('Y-m-d H:i:s', strtotime("+$duracao_meses months"));
    }
    
    try {
        $pdo->beginTransaction();

        // 1. Atualiza a tabela principal de parceiros (status e etapa)
        $stmtParceiro = $pdo->prepare("UPDATE parceiros SET acordo_aceito = 1, acordo_data = ?, acordo_ip = ?, status = 'analise', etapa_atual = 7 WHERE id = ?");
        $stmtParceiro->execute([$data_atual, $ip, $parceiro_id]);
        
        // 2. Atualiza a tabela de contratos com as datas
        $stmtContrato = $pdo->prepare("UPDATE parceiro_contrato SET data_assinatura = ?, data_vencimento = ? WHERE parceiro_id = ?");
        $stmtContrato->execute([$data_atual, $data_vencimento, $parceiro_id]);

        // 3. Busca todos os Administradores ativos da Plataforma
        $stmtAdmins = $pdo->query("SELECT nome, email FROM users WHERE role IN ('superadmin', 'admin') AND status = 'ativo'");
        $admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);

        // 4. Se houver admins, prepara e envia o e-mail
        if (!empty($admins)) {
            $subject = "Nova Carta-Acordo Assinada: " . $parceiro['nome_fantasia'];
            
            // Corpo do E-mail em HTML
            $body = "
                <p>Olá, Equipe Impactos Positivos!</p>
                <p>A organização <strong>{$parceiro['nome_fantasia']}</strong> (CNPJ: {$parceiro['cnpj']}) acaba de assinar a Carta-Acordo digitalmente e definiu o prazo de parceria de <strong>{$duracao_meses} meses</strong>.</p>
                <p><strong>Dados da Assinatura:</strong><br>
                Data: " . date('d/m/Y H:i:s') . "<br>
                Representante: {$parceiro['rep_nome']}<br>
                E-mail: {$parceiro['rep_email']}</p>
                <p>Acesse o painel administrativo da Impactos Positivos para visualizar o cadastro completo e alterar o status para 'Ativo'.</p>
            ";

            $altBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $body));

            // Dispara para cada admin
            foreach ($admins as $admin) {
                // Monta com o template bonito se quiser, ou usa o corpo direto
                $htmlTemplate = apply_email_template($subject, $body, $admin['nome']);
                send_mail($admin['email'], $admin['nome'], $subject, $htmlTemplate, $altBody);
            }
        }

        $pdo->commit();

        // Deu certo! Redireciona pro dashboard
        $_SESSION['sucesso'] = "Carta-Acordo assinada com sucesso! Seu cadastro está em análise.";
        header("Location: dashboard.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao assinar acordo: " . $e->getMessage());
        $erro = "Ocorreu um erro ao registrar sua assinatura. Tente novamente ou contate o suporte.";
    }
}

// -------------------------------------------------------------
// PREPARAÇÃO DE DADOS PARA EXIBIR NA TELA (MOCK DO CONTRATO)
// -------------------------------------------------------------

// Formatação da Data Atual (ex: 25 de Outubro de 2023)
$fmt = new IntlDateFormatter(
    'pt_BR', 
    IntlDateFormatter::LONG, 
    IntlDateFormatter::NONE, 
    'America/Sao_Paulo', 
    IntlDateFormatter::GREGORIAN, 
    "dd 'de' MMMM 'de' yyyy"
);
$data_contrato = $fmt->format(new DateTime());

// Formatação do texto de Vigência para a Carta
$texto_vigencia = "prazo vinculado à execução de Projeto Específico.";
if (is_numeric($parceiro['duracao_meses'])) {
    $texto_vigencia = "<strong>" . $parceiro['duracao_meses'] . " meses</strong>, contados a partir da data de assinatura digital deste documento.";
}

// Tipos de parceria formatados
$tipos = !empty($parceiro['tipos_parceria']) ? json_decode($parceiro['tipos_parceria'], true) : [];
$tipos_str = is_array($tipos) ? implode(', ', $tipos) : 'Não especificado';

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <div class="text-center mb-5">
                <h2 class="fw-bold">Carta-Acordo de Parceria Institucional</h2>
                <p class="text-muted">Leia atentamente os termos abaixo. Ao final da página, você deverá assinar digitalmente para firmar o vínculo.</p>
            </div>

            <?php if(isset($erro)): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $erro ?></div>
            <?php endif; ?>

            <!-- DOCUMENTO GERADO MOCK -->
            <div class="card shadow-sm border-0 mb-4 bg-white" style="border-radius: 12px; border-top: 5px solid #0d6efd !important;">
                <div class="card-body p-4 p-md-5 text-dark" style="line-height: 1.8; font-size: 1rem; text-align: justify;">
                    
                    <h5 class="fw-bold text-center mb-4 pb-2 border-bottom">INSTRUMENTO PARTICULAR DE ADESÃO À REDE IMPACTOS POSITIVOS</h5>

                    <p>Pelo presente instrumento, a Plataforma <strong>Impactos Positivos</strong> e a organização abaixo qualificada firmam a presente Carta-Acordo para adesão e integração à Rede de Impacto.</p>

                    <h6 class="fw-bold mt-4">1. QUALIFICAÇÃO DO PARCEIRO</h6>
                    <ul class="list-unstyled ps-3 border-start border-3 border-primary ms-2 mb-4 bg-light p-3 rounded">
                        <li><strong>Razão Social:</strong> <?= htmlspecialchars($parceiro['razao_social'] ?? '') ?></li>
                        <li><strong>Nome Fantasia:</strong> <?= htmlspecialchars($parceiro['nome_fantasia'] ?? '') ?></li>
                        <li><strong>CNPJ:</strong> <?= htmlspecialchars($parceiro['cnpj'] ?? '') ?></li>
                        <li><strong>Representante Legal:</strong> <?= htmlspecialchars($parceiro['rep_nome'] ?? '') ?> (<?= htmlspecialchars($parceiro['rep_cargo'] ?? '') ?>)</li>
                    </ul>

                    <h6 class="fw-bold mt-4">2. OBJETO DA PARCERIA</h6>
                    <p>O PARCEIRO compromete-se a atuar conforme os eixos e interesses previamente declarados na plataforma, colaborando para o fortalecimento do ecossistema de impacto através das seguintes modalidades de apoio:</p>
                    <p class="ps-3 fst-italic border-start ms-2"><strong><?= htmlspecialchars($tipos_str) ?>.</strong></p>

                    <h6 class="fw-bold mt-4">3. ESCOPO DE ATUAÇÃO E ENGAJAMENTO</h6>
                    <p>Conforme alinhamento na etapa de mapeamento, o engajamento do parceiro na plataforma abrange as seguintes frentes:</p>
                    <div class="p-3 bg-light rounded border mb-4">
                        <strong>Escopo detalhado:</strong><br>
                        <?= nl2br(htmlspecialchars($parceiro['escopo_atuacao'] ?? '-')) ?>
                    </div>

                    <h6 class="fw-bold mt-4">4. DA VIGÊNCIA E RENOVAÇÃO</h6>
                    <p>O presente Acordo de Parceria terá duração de <?= $texto_vigencia ?> Após este período, a parceria será suspensa automaticamente até que uma nova renovação seja firmada entre as partes.</p>

                    <h6 class="fw-bold mt-4">5. DECLARAÇÕES E SIGILO</h6>
                    <p>O PARCEIRO declara ter lido e concordado com os Termos de Uso e Políticas de Privacidade da Plataforma. As partes comprometem-se a manter sigilo sobre informações confidenciais compartilhadas durante as rodadas de conexão.</p>

                    <div class="mt-5 pt-4 text-end fst-italic border-top">
                        São Paulo, <?= $data_contrato ?>.
                    </div>

                    <div class="row mt-5 text-center px-4">
                        <div class="col-md-6 mb-4">
                            <div class="border-bottom border-dark mx-4 mb-2"></div>
                            <span class="fw-bold">Impactos Positivos</span><br>
                            <span class="text-muted small">Plataforma Digital</span>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="border-bottom border-dark mx-4 mb-2"></div>
                            <span class="fw-bold"><?= htmlspecialchars($parceiro['rep_nome']) ?></span><br>
                            <span class="text-muted small"><?= htmlspecialchars($parceiro['nome_fantasia']) ?></span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ÁREA DE ASSINATURA -->
            <div class="card shadow border-0 bg-light rounded-4 border-start border-5 border-success">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-4">
                    <div>
                        <h5 class="fw-bold mb-1 text-success"><i class="bi bi-shield-lock-fill me-2"></i>Assinatura Eletrônica</h5>
                        <p class="mb-0 text-muted small" style="max-width: 500px;">
                            Ao clicar em "Li e Aceito", você estará assinando este documento digitalmente. Seu endereço IP (<strong><?= $_SERVER['REMOTE_ADDR'] ?></strong>) e a data atual serão registrados, possuindo validade jurídica e legal.
                        </p>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="aceito" value="1">
                        <button type="submit" class="btn btn-success btn-lg px-5 fw-bold shadow">
                            <i class="bi bi-check-circle me-2"></i> Li e Aceito o Acordo
                        </button>
                    </form>
                </div>
            </div>

            <!-- Botão Voltar (Apenas link visual) -->
            <div class="text-center mt-4 mb-5">
                <a href="confirmacao.php" class="text-secondary text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Voltar e revisar dados</a>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
