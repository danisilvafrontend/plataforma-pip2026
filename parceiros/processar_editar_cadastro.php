<?php
session_start();
$config = require __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/helpers/functions.php';

if (!isset($_SESSION['parceiro_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /login.php");
    exit;
}

$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$parceiro_id = $_SESSION['parceiro_id'];
$from = $_POST['from'] ?? 'confirmacao';

// Captura e limpa os dados
$razao_social = trim($_POST['razao_social'] ?? '');
$nome_fantasia = trim($_POST['nome_fantasia'] ?? '');
$cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
$rep_nome = trim($_POST['rep_nome'] ?? '');
$rep_cpf = preg_replace('/[^0-9]/', '', $_POST['rep_cpf'] ?? '');
$email_login = trim($_POST['email_login'] ?? '');

// Validações básicas (pode usar os isValid do seu helper)
if (empty($razao_social) || empty($cnpj) || empty($email_login)) {
    $_SESSION['erro_editar_cadastro'] = "Preencha todos os campos obrigatórios.";
    header("Location: editar_cadastro.php?from=$from");
    exit;
}

try {
    // Verifica se já existe outro parceiro com esse E-mail, CNPJ ou CPF (excluindo ele mesmo)
    $stmt = $pdo->prepare("SELECT id FROM parceiros WHERE (email_login = ? OR cnpj = ? OR rep_cpf = ?) AND id != ?");
    $stmt->execute([$email_login, $cnpj, $rep_cpf, $parceiro_id]);
    
    if ($stmt->fetch()) {
        $_SESSION['erro_editar_cadastro'] = "Já existe outro parceiro cadastrado com este E-mail, CNPJ ou CPF.";
        header("Location: editar_cadastro.php?from=$from");
        exit;
    }

    // Tudo certo, atualiza os dados
    $sql = "UPDATE parceiros SET razao_social = ?, nome_fantasia = ?, cnpj = ?, rep_nome = ?, rep_cpf = ?, email_login = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$razao_social, $nome_fantasia, $cnpj, $rep_nome, $rep_cpf, $email_login, $parceiro_id]);

    // Redireciona de volta
    $destino = ($from === 'confirmacao') ? 'confirmacao.php' : 'dashboard.php';
    header("Location: " . $destino);
    exit;

} catch (PDOException $e) {
    error_log("Erro ao editar cadastro inicial: " . $e->getMessage());
    $_SESSION['erro_editar_cadastro'] = "Erro interno no banco de dados. Tente novamente.";
    header("Location: editar_cadastro.php?from=$from");
    exit;
}
