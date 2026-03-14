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


// Verifica se está logado e se é POST
if (!isset($_SESSION['parceiro_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Captura os dados
$cep = trim($_POST['cep'] ?? '');
$endereco_completo = trim($_POST['endereco_completo'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$estado = trim($_POST['estado'] ?? '');
$pais = trim($_POST['pais'] ?? '');
$telefone_institucional = trim($_POST['telefone_institucional'] ?? '');
$site = trim($_POST['site'] ?? '');

$rep_cargo = trim($_POST['rep_cargo'] ?? '');
$rep_email = trim($_POST['rep_email'] ?? '');
$rep_telefone = trim($_POST['rep_telefone'] ?? '');
// Opt-ins do Representante
$rep_email_optin = isset($_POST['rep_email_optin']) ? 1 : 0;
$rep_whatsapp_optin = isset($_POST['rep_whatsapp_optin']) ? 1 : 0;

$op_nome = trim($_POST['op_nome'] ?? '');
$op_cargo = trim($_POST['op_cargo'] ?? '');
$op_email = trim($_POST['op_email'] ?? '');
$op_telefone = trim($_POST['op_telefone'] ?? '');
// Opt-ins do Operacional
$op_email_optin = isset($_POST['op_email_optin']) ? 1 : 0;
$op_whatsapp_optin = isset($_POST['op_whatsapp_optin']) ? 1 : 0;

// Validação simples (exige pelo menos o email do representante, que é crucial pro contrato)
if (empty($rep_email)) {
    $_SESSION['erro_etapa1'] = "O E-mail do Representante Legal é obrigatório.";
    header("Location: etapa1_dados.php");
    exit;
}

try {
    // Atualiza a tabela parceiros
    $sql = "UPDATE parceiros SET 
                cep = ?, 
                endereco_completo = ?, 
                cidade = ?, 
                estado = ?, 
                pais = ?, 
                telefone_institucional = ?, 
                site = ?, 
                rep_cargo = ?, 
                rep_email = ?, 
                rep_telefone = ?, 
                rep_email_optin = ?,
                rep_whatsapp_optin = ?,
                op_nome = ?, 
                op_cargo = ?, 
                op_email = ?, 
                op_telefone = ?,
                op_email_optin = ?,
                op_whatsapp_optin = ?,
                etapa_atual = GREATEST(etapa_atual, 2)
            WHERE id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $cep, $endereco_completo, $cidade, $estado, $pais, $telefone_institucional, $site,
        $rep_cargo, $rep_email, $rep_telefone, $rep_email_optin, $rep_whatsapp_optin,
        $op_nome, $op_cargo, $op_email, $op_telefone, $op_email_optin, $op_whatsapp_optin,
        $parceiro_id
    ]);

    // Redireciona para a Etapa 2 (Tipo de Parceria)
    $from = $_POST['from'] ?? '';
    $destino = ($from === 'confirmacao') ? 'confirmacao.php' : 'etapa2_tipo.php';
    header("Location: " . $destino);
    exit;


} catch (PDOException $e) {
    error_log("Erro ao salvar Etapa 1 do Parceiro: " . $e->getMessage());
    $_SESSION['erro_etapa1'] = "Erro ao salvar os dados. Tente novamente.";
    header("Location: etapa1_dados.php");
    exit;
}
