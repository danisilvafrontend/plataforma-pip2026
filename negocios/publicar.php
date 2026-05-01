<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$appBase = dirname(__DIR__) . '/app';
$config  = require $appBase . '/config/db.php';

$dsn  = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s',
    $config['host'], $config['dbname'], $config['port'], $config['charset']);
$opts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $opts);
} catch (PDOException $e) {
    die('Erro na conexão com o banco: ' . $e->getMessage());
}


// Helpers de e-mail
require_once __DIR__ . '/../app/helpers/mail.php';
require_once __DIR__ . '/../app/helpers/email_template.php';

function premiacaoEdicoesComInscricoesAbertas(PDO $pdo): array
{
    $sql = "
        SELECT 
            p.id,
            p.nome,
            p.slug,
            p.ano,
            p.regulamento_url,
            pf.id AS fase_id,
            pf.nome AS fase_nome,
            pf.data_inicio,
            pf.data_fim
        FROM premiacoes p
        INNER JOIN premiacao_fases pf 
            ON pf.premiacao_id = p.id
        WHERE pf.tipo_fase = 'inscricoes'
          AND pf.status <> 'rascunho'
          AND NOW() BETWEEN pf.data_inicio AND pf.data_fim
        ORDER BY pf.data_inicio ASC, p.id ASC
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function premiacaoEdicaoInscricaoAtual(PDO $pdo): ?array
{
    $edicoes = premiacaoEdicoesComInscricoesAbertas($pdo);
    return $edicoes[0] ?? null;
}

$negocioId = (int)($_GET['negocio_id'] ?? $_POST['negocio_id'] ?? $_GET['id'] ?? $_POST['id'] ?? 0);
$empreendedorId = (int)$_SESSION['user_id'];

if ($negocioId === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}

$colDono = 'empreendedor_id';

// Verifica se negócio existe e pertence ao user
$stmt = $pdo->prepare("
    SELECT id, nome_fantasia, categoria, status_vitrine, etapa_atual, inscricao_completa
    FROM negocios
    WHERE id = ? AND {$colDono} = ?
    LIMIT 1
");
$stmt->execute([$negocioId, $empreendedorId]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$negocio) {
    die('Negócio não encontrado ou sem permissão.');
}

// Verifica se docs foram enviadas (obrigatório para aprovação)
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM negocios_documentos
    WHERE negocio_id = ?
      AND certidao_trabalhista_path IS NOT NULL
      AND certidao_ambiental_path IS NOT NULL
");
$stmt->execute([$negocioId]);
$docsOk = $stmt->fetchColumn() > 0;

if (!$docsOk) {
    $_SESSION['erro'] = 'Envie primeiro as certidões trabalhista e ambiental (Etapa 9).';
    header("Location: /negocios/confirmacao.php?id=" . $negocioId);
    exit;
}

$acao = $_POST['acao'] ?? '';
$premiacaoDesejaParticipar = ($acao === 'publicar_com_premiacao') ? 1 : 0;
$premiacaoAceiteRegulamento = isset($_POST['aceite_regulamento']) ? 1 : 0;
$premiacaoAceiteVeracidade = isset($_POST['aceite_veracidade']) ? 1 : 0;

if ($acao === 'remover') {
    $stmt = $pdo->prepare("UPDATE negocios SET publicado_vitrine = 0, status_operacional = 'encerrado' WHERE id = ?");
    $stmt->execute([$negocioId]);
    header('Location: /empreendedores/meus-negocios.php?ok=ocultado');
    exit;
} elseif ($acao === 'republicar') {
    $stmt = $pdo->prepare("UPDATE negocios SET publicado_vitrine = 1, status_operacional = 'ativo' WHERE id = ?");
    $stmt->execute([$negocioId]);
    header('Location: /empreendedores/meus-negocios.php?ok=republicado');
    exit;
}

// Envia para aprovação
try {
    $pdo->beginTransaction();

    // 1) Marca como enviado para análise
    $stmt = $pdo->prepare("
        UPDATE negocios
        SET status_vitrine = 'em_analise',
            etapa_atual = 11,
            inscricao_completa = 1,
            updated_at = NOW()
        WHERE id = ? AND {$colDono} = ?
    ");
    $stmt->execute([$negocioId, $empreendedorId]);

    // 1.1) Se desejar participar da premiação, inscreve somente na edição
    // que estiver com fase de inscrições aberta agora
    if ($premiacaoDesejaParticipar === 1) {
        if ($premiacaoAceiteRegulamento !== 1 || $premiacaoAceiteVeracidade !== 1) {
            throw new Exception('Para participar da premiação, é obrigatório aceitar o regulamento e declarar a veracidade das informações.');
        }

        $premiacaoVigente = premiacaoEdicaoInscricaoAtual($pdo);

        if (empty($premiacaoVigente['id'])) {
            throw new Exception('No momento não há nenhuma edição da premiação com inscrições abertas.');
        }

        $stmtPremiacaoUpsert = $pdo->prepare("
            INSERT INTO premiacao_inscricoes (
                premiacao_id,
                negocio_id,
                empreendedor_id,
                categoria,
                aceite_regulamento,
                aceite_veracidade,
                deseja_participar,
                status,
                enviado_em,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'enviada', NOW(), NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                empreendedor_id = VALUES(empreendedor_id),
                categoria = VALUES(categoria),
                aceite_regulamento = VALUES(aceite_regulamento),
                aceite_veracidade = VALUES(aceite_veracidade),
                deseja_participar = VALUES(deseja_participar),
                status = 'enviada',
                enviado_em = NOW(),
                updated_at = NOW()
        ");
        $stmtPremiacaoUpsert->execute([
            (int)$premiacaoVigente['id'],
            $negocioId,
            $empreendedorId,
            $negocio['categoria'],
            $premiacaoAceiteRegulamento,
            $premiacaoAceiteVeracidade,
            $premiacaoDesejaParticipar
        ]);
    }

    // 2) Envia email para admins
    $stmt = $pdo->prepare("
        SELECT email FROM users 
        WHERE role IN ('admin', 'superadmin') AND status = 'ativo'
    ");
    $stmt->execute();

    // Como usamos FETCH_COLUMN, $admins é um array direto de emails: ['a@b.com', 'c@d.com']
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($admins)) {
        $assunto = "Novo negócio aguardando aprovação: " . $negocio['nome_fantasia'];
        $linkAdmin = get_base_url() . "/admin/visualizar_negocio.php?id=" . $negocioId;
        $mensagem = "
            <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; padding: 20px;'>
                <h2 style='color: #d63384; border-bottom: 2px solid #f8d7da; padding-bottom: 10px;'>Novo Negócio Aguardando Avaliação</h2>
                
                <p>Olá, Equipe Impactos Positivos,</p>
                <p>O empreendedor concluiu todas as etapas e enviou o negócio abaixo para publicação na vitrine:</p>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #d63384; margin: 20px 0; border-radius: 4px;'>
                    <p style='margin: 0 0 5px 0;'><strong>Nome Fantasia:</strong> {$negocio['nome_fantasia']}</p>
                    <p style='margin: 0 0 5px 0;'><strong>Categoria:</strong> {$negocio['categoria']}</p>
                    <p style='margin: 0;'><strong>ID do Sistema:</strong> {$negocioId}</p>
                </div>

                <div style='background-color: #fff3cd; color: #842029; padding: 15px; border: 1px solid #f5c2c7; border-radius: 5px; margin-bottom: 25px;'>
                    <strong><span style='font-size: 16px;'>⚠️ Atenção Necessária:</span></strong><br>
                    Antes de aprovar e publicar o negócio, é obrigatório verificar a autenticidade e validade dos documentos legais enviados na Etapa 9:
                    <ul style='margin-top: 8px; margin-bottom: 0;'>
                        <li>Certidão Negativa de Débitos Trabalhistas (CNDT)</li>
                        <li>Certidão Negativa de Multas Ambientais (CNMA)</li>
                    </ul>
                </div>

                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$linkAdmin}' style='background-color: #d63384; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Acessar Perfil e Analisar Documentos</a>
                </p>
                
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 12px; color: #777; text-align: center;'>Enviado em: " . date('d/m/Y \à\s H:i') . "</p>
            </div>
        ";

        // Prepara os headers para o mail() nativo
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: Plataforma Impactos Positivos <nao-responda@dscriacaoweb.com.br>\r\n";

        // Loop sobre os emails diretamente
        foreach ($admins as $emailAdmin) {
            // Usa a sua função de e-mail centralizada do mail.php
            send_mail($emailAdmin, 'Administrador PIP', $assunto, $mensagem, $headers);
        }
    }

    $pdo->commit();
    
    $_SESSION['sucesso'] = "Negócio '{$negocio['nome_fantasia']}' enviado para aprovação! Recebemos sua solicitação e em breve analisaremos as documentações.";
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erro ao enviar negócio $negocioId para aprovação: " . $e->getMessage());
    $_SESSION['erro'] = "Erro ao enviar para aprovação: " . $e->getMessage();
}

// Redireciona para meus-negócios com status EM ANALISE
header("Location: /empreendedores/meus-negocios.php");
exit;
?>
