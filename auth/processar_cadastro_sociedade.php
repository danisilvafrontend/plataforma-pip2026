<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config/db.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro ao conectar no banco de dados: " . $e->getMessage());
}

require_once __DIR__ . '/../app/helpers/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /cadastro.php");
    exit;
}

try {
    // Campos básicos
    $nome              = trim($_POST['nome'] ?? '');
    $sobrenome         = trim($_POST['sobrenome'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $cpf               = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $senha             = $_POST['senha'] ?? '';
    $senha_confirmacao = $_POST['senha_confirmacao'] ?? '';

    // Novos campos
    $celular           = trim($_POST['celular'] ?? '');
    $cidade            = trim($_POST['cidade'] ?? '');
    $estado            = trim($_POST['estado'] ?? '');
    $data_nascimento   = $_POST['data_nascimento'] ?? null;
    $profissao         = trim($_POST['profissao'] ?? '');
    $organizacao       = trim($_POST['organizacao'] ?? '');

    // Autorizações
    $email_autorizacao   = isset($_POST['email_autorizacao']) ? 1 : 0;
    $celular_autorizacao = isset($_POST['celular_autorizacao']) ? 1 : 0;

    // Campos múltipla escolha (JSON)
    $identificacoes    = json_encode($_POST['identificacoes'] ?? []);
    $motivacoes        = json_encode($_POST['motivacoes'] ?? []);
    $interesses        = json_encode($_POST['interesses'] ?? []);
    $ods               = json_encode($_POST['ods'] ?? []);
    $maturidade        = json_encode($_POST['maturidade'] ?? []);
    $setores           = json_encode($_POST['setores'] ?? []);
    $perfil_impacto    = json_encode($_POST['perfil_impacto'] ?? []);
    $alcance           = json_encode($_POST['alcance'] ?? '');
    $engajamento       = json_encode($_POST['engajamento'] ?? []);

    $errors = [];

    // Validações básicas
    if (empty($nome) || empty($sobrenome)) {
        $errors[] = "Informe nome e sobrenome.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido.";
    }
    if (!isValidCPF($cpf)) {
        $errors[] = "CPF inválido.";
    }
    if (strlen($senha) < 8) {
        $errors[] = "A senha deve ter pelo menos 8 caracteres.";
    }
    if ($senha !== $senha_confirmacao) {
        $errors[] = "As senhas não conferem.";
    }

    if (!empty($errors)) {
        $_SESSION['cadastro_errors'] = $errors;
        header("Location: /cadastro.php");
        exit;
    }

    // Checagem duplicidade
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sociedade_civil WHERE cpf = ? OR email = ?");
    $stmt->execute([$cpf, $email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "CPF ou email já cadastrado na sociedade civil.";
    }

    if (!empty($errors)) {
        $_SESSION['cadastro_errors'] = $errors;
        header("Location: /cadastro.php");
        exit;
    }

    // Criar hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Inserção completa
    $stmt = $pdo->prepare("INSERT INTO sociedade_civil 
        (nome, sobrenome, email, cpf, celular, cidade, estado, data_nascimento, profissao, organizacao,
         email_autorizacao, celular_autorizacao,
         identificacoes, motivacoes, interesses, ods, maturidade, setores, perfil_impacto, alcance, engajamento, senha_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $nome, $sobrenome, $email, $cpf, $celular, $cidade, $estado, $data_nascimento, $profissao, $organizacao,
        $email_autorizacao, $celular_autorizacao,
        $identificacoes, $motivacoes, $interesses, $ods, $maturidade, $setores, $perfil_impacto, $alcance, $engajamento, $senha_hash
    ]);

    header("Location: /cadastro_sucesso.php");
    exit;

} catch (PDOException $e) {
    error_log("Erro no cadastro: " . $e->getMessage());
    $_SESSION['cadastro_errors'] = ["Erro interno ao processar cadastro. Tente novamente."];
    header("Location: /cadastro.php");
    exit;
}