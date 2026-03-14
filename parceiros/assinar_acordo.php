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

// Inclui a sua função de envio de e-mail (o mesmo usado no store.php)
require_once __DIR__ . '/../app/helpers/mail.php';
require_once __DIR__ . '/../app/helpers/email_template.php';


if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Pega os dados do parceiro para preencher o contrato
$stmt = $pdo->prepare("SELECT p.*, c.tipos_parceria, c.escopo_atuacao FROM parceiros p LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id WHERE p.id = ?");
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

    try {
        $pdo->beginTransaction();

        // 1. Atualiza o banco com o aceite digital
        $stmt = $pdo->prepare("UPDATE parceiros SET acordo_aceito = 1, acordo_data = ?, acordo_ip = ?, status = 'analise', etapa_atual = 7 WHERE id = ?");
        $stmt->execute([$data_atual, $ip, $parceiro_id]);

        // 2. Busca todos os Administradores ativos da Plataforma
        $stmtAdmins = $pdo->query("SELECT nome, email FROM users WHERE role IN ('superadmin', 'admin') AND status = 'ativo'");
        $admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);

        // 3. Se houver admins, prepara e envia o e-mail
        if (!empty($admins)) {
            $subject = "Nova Carta-Acordo Assinada: " . $parceiro['nome_fantasia'];
            
            // Corpo do E-mail em HTML
            $body = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <h2 style='color: #0d6efd;'>Novo Parceiro aguardando análise!</h2>
                <p>A organização <strong>{$parceiro['nome_fantasia']}</strong> (CNPJ: {$parceiro['cnpj']}) acaba de assinar a Carta-Acordo digitalmente.</p>
                <hr style='border: 1px solid #eee;' />
                <p><strong>Dados da Assinatura:</strong></p>
                <ul>
                    <li><strong>Representante:</strong> {$parceiro['rep_nome']}</li>
                    <li><strong>E-mail de Contato:</strong> {$parceiro['rep_email']}</li>
                    <li><strong>Endereço IP:</strong> {$ip}</li>
                    <li><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</li>
                </ul>
                <p style='margin-top: 20px;'>Acesse o painel administrativo da Impactos Positivos para visualizar o cadastro completo e alterar o status para 'Ativo'.</p>
            </div>
            ";

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: Plataforma Impactos Positivos <noreply@impactospositivos.com.br>\r\n";

            // Loop para enviar para cada Admin
            foreach ($admins as $admin) {
                // Aqui usamos o seu próprio helper de envio!
                send_mail($admin['email'], $admin['nome'], $subject, $body, $headers);
            }
        }

        $pdo->commit();

        // Redireciona para o dashboard com sucesso
        header("Location: dashboard.php?msg=sucesso");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = "Erro ao registrar assinatura: " . $e->getMessage();
    }
}

// Data por extenso para o PDF do contrato
date_default_timezone_set('America/Sao_Paulo');
$meses = [
    '01' => 'janeiro', '02' => 'fevereiro', '03' => 'março', '04' => 'abril', 
    '05' => 'maio', '06' => 'junho', '07' => 'julho', '08' => 'agosto', 
    '09' => 'setembro', '10' => 'outubro', '11' => 'novembro', '12' => 'dezembro'
];
$data_contrato = date('d') . ' de ' . $meses[date('m')] . ' de ' . date('Y');


include __DIR__ . '/../app/views/public/header_public.php'; 
?>

<div class="container py-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2 class="fw-bold mb-0">Assinatura da Carta-Acordo</h2>
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="bi bi-printer"></i> Salvar PDF / Imprimir
        </button>
    </div>

    <?php if(isset($erro)): ?>
        <div class="alert alert-danger no-print"><?= $erro ?></div>
    <?php endif; ?>

    <!-- ÁREA DO DOCUMENTO (Ficará visível na impressão) -->
    <div class="card shadow-sm border-0 mb-4 document-area">
        <div class="card-body p-4 p-md-5" style="background-color: #fafafa; border: 1px solid #dee2e6;">
            
            <div class="text-center mb-5">
                <!-- Se tiver uma logo nas views públicas, puxe aqui. Se não, deixe apenas o texto -->
                <h4 class="fw-bold text-uppercase">Carta-Acordo de Parceria Institucional</h4>
            </div>

            <div class="conteudo-contrato" style="font-size: 1.05rem; line-height: 1.8; text-align: justify;">
                <p>Pelo presente instrumento, a Plataforma <strong>Impactos Positivos</strong> e a organização abaixo qualificada firmam a presente Carta-Acordo para adesão e integração à Rede de Impacto.</p>
                
                <h5 class="fw-bold mt-4">1. QUALIFICAÇÃO DO PARCEIRO</h5>
                <ul class="list-unstyled mb-4 ps-3 border-start border-3 border-primary">
                    <li><strong>Organização:</strong> <?= htmlspecialchars($parceiro['razao_social'] ?? $parceiro['nome_fantasia']) ?></li>
                    <li><strong>CNPJ:</strong> <?= htmlspecialchars($parceiro['cnpj']) ?></li>
                    <li><strong>Representante Legal:</strong> <?= htmlspecialchars($parceiro['rep_nome']) ?></li>
                    <li><strong>E-mail:</strong> <?= htmlspecialchars($parceiro['rep_email']) ?></li>
                </ul>

                <h5 class="fw-bold mt-4">2. DO ESCOPO DA PARCERIA</h5>
                <p>O PARCEIRO compromete-se a atuar conforme os eixos e interesses previamente declarados na plataforma, colaborando para o fortalecimento do ecossistema de impacto através das seguintes modalidades de apoio: 
                <strong>
                <?php 
                    $tipos = json_decode($parceiro['tipos_parceria'] ?? '[]', true);
                    echo !empty($tipos) ? implode(', ', $tipos) : 'Apoio Institucional';
                ?>.
                </strong></p>
                
                <p><strong>Escopo detalhado:</strong> <?= nl2br(htmlspecialchars($parceiro['escopo_atuacao'] ?? '-')) ?></p>

                <h5 class="fw-bold mt-4">3. COMPROMISSOS E SIGILO</h5>
                <p>O PARCEIRO declara ter lido e concordado com os Termos de Uso e Políticas de Privacidade da Plataforma. As partes comprometem-se a manter sigilo sobre informações confidenciais compartilhadas durante as rodadas de conexão.</p>

                <h5 class="fw-bold mt-4">4. VIGÊNCIA</h5>
                <p>Esta Carta-Acordo entra em vigor a partir da data de seu aceite digital e possui validade indeterminada, podendo ser rescindida por qualquer uma das partes mediante aviso prévio de 30 dias na própria plataforma.</p>

                <div class="mt-5 text-end">
                    <p>São Paulo, <?= $data_contrato ?>.</p>
                </div>


                <!-- Campo de assinatura visual (Aparece APENAS quando a pessoa manda imprimir o PDF) -->
                <div class="row mt-5 pt-4 text-center d-none d-print-flex">
                    <div class="col-6">
                        <hr class="w-75 mx-auto border-dark">
                        <p class="mb-0 fw-bold">Impactos Positivos</p>
                        <small>Plataforma Digital</small>
                    </div>
                    <div class="col-6">
                        <hr class="w-75 mx-auto border-dark">
                        <p class="mb-0 fw-bold"><?= htmlspecialchars($parceiro['rep_nome']) ?></p>
                        <small><?= htmlspecialchars($parceiro['nome_fantasia']) ?></small>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- FORMULÁRIO DE ACEITE (Some na hora de imprimir) -->
    <div class="card border-primary shadow-sm mb-5 no-print bg-light">
        <div class="card-body p-4 text-center">
            <h5 class="mb-3 text-primary"><i class="bi bi-shield-lock me-2"></i>Assinatura Digital</h5>
            <p class="text-muted small mb-4">Ao clicar em "Li e Aceito", você estará assinando este documento digitalmente. Seu endereço IP (<strong><?= $_SERVER['REMOTE_ADDR'] ?></strong>) e a data atual serão registrados, possuindo validade jurídica e legal.</p>
            
            <form method="POST" action="">
                <div class="form-check d-flex justify-content-center align-items-center mb-4 gap-2">
                    <input class="form-check-input mt-0 fs-4" type="checkbox" name="aceito" id="aceitoCheck" required style="cursor: pointer;">
                    <label class="form-check-label fs-5" for="aceitoCheck" style="cursor: pointer; user-select: none;">
                        Eu li, concordo e <strong>assino digitalmente</strong> esta Carta-Acordo.
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold" id="btnAssinar" disabled>
                    Finalizar e Assinar Contrato
                </button>
            </form>
        </div>
    </div>

</div>

<!-- CSS de Impressão -->
<style>
@media print {
    body { background-color: #fff; }
    .no-print, header, footer, nav, .navbar { display: none !important; }
    .container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .document-area { border: none !important; box-shadow: none !important; }
    .document-area .card-body { padding: 0 !important; }
    .d-print-flex { display: flex !important; }
}
</style>

<script>
// Libera o botão verde apenas quando o usuário marcar a caixa de seleção
document.getElementById('aceitoCheck').addEventListener('change', function() {
    document.getElementById('btnAssinar').disabled = !this.checked;
});
</script>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
