<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/helpers/functions.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /cadastro.php");
    exit;
}

try {
    $nome              = trim($_POST['nome'] ?? '');
    $sobrenome         = trim($_POST['sobrenome'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $cpf               = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $senha             = $_POST['senha'] ?? '';
    $senhaConfirmacao  = $_POST['senha_confirmacao'] ?? $_POST['senha_confirmacao'] ?? '';

    $celular           = trim($_POST['celular'] ?? '');
    $cep               = trim($_POST['cep'] ?? '');
    $cidade            = trim($_POST['cidade'] ?? '');
    $estado            = trim($_POST['estado'] ?? '');
    $dataNascimento    = $_POST['data_nascimento'] ?? $_POST['datanascimento'] ?? null;
    $profissao         = trim($_POST['profissao'] ?? '');
    $organizacao       = trim($_POST['organizacao'] ?? '');

    $emailAutorizacao   = isset($_POST['email_autorizacao']) || isset($_POST['emailautorizacao']) ? 1 : 0;
    $celularAutorizacao = isset($_POST['celular_autorizacao']) || isset($_POST['celularautorizacao']) ? 1 : 0;

    $identificacoes = $_POST['identificacoes'] ?? [];
    $motivacoes     = $_POST['motivacoes'] ?? [];
    $interesses     = $_POST['interesses'] ?? [];
    $ods            = $_POST['ods'] ?? [];
    $maturidade     = $_POST['maturidade'] ?? [];
    $setores        = $_POST['setores'] ?? [];
    $perfilImpacto  = $_POST['perfil_impacto'] ?? $_POST['perfilimpacto'] ?? [];
    $alcanceRaw = trim($_POST['alcance'] ?? '');
    $alcance    = $alcanceRaw !== '' ? json_encode($alcanceRaw, JSON_UNESCAPED_UNICODE) : null;
    $engajamento    = $_POST['engajamento'] ?? [];

    $errors = [];

    if ($nome === '' || $sobrenome === '') {
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

    if ($senha !== $senhaConfirmacao) {
        $errors[] = "As senhas não conferem.";
    }

    if (!empty($identificacoes) && count($identificacoes) > 3) {
        $errors[] = "Selecione no máximo 3 opções em identificação.";
    }

    if (!empty($errors)) {
        $_SESSION['cadastro_errors'] = $errors;
        header("Location: /cadastro.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sociedade_civil WHERE cpf = ? OR email = ?");
    $stmt->execute([$cpf, $email]);

    if ((int)$stmt->fetchColumn() > 0) {
        $_SESSION['cadastro_errors'] = ["CPF ou email já cadastrado na sociedade civil."];
        header("Location: /cadastro.php");
        exit;
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $identificacoesJson = json_encode(array_values($identificacoes), JSON_UNESCAPED_UNICODE);
    $motivacoesJson     = json_encode(array_values($motivacoes), JSON_UNESCAPED_UNICODE);
    $interessesJson     = json_encode(array_values($interesses), JSON_UNESCAPED_UNICODE);
    $odsJson            = json_encode(array_values($ods), JSON_UNESCAPED_UNICODE);
    $maturidadeJson     = json_encode(array_values($maturidade), JSON_UNESCAPED_UNICODE);
    $setoresJson        = json_encode(array_values($setores), JSON_UNESCAPED_UNICODE);
    $perfilImpactoJson  = json_encode(array_values($perfilImpacto), JSON_UNESCAPED_UNICODE);
    $engajamentoJson    = json_encode(array_values($engajamento), JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        INSERT INTO sociedade_civil (
            nome,
            sobrenome,
            email,
            cpf,
            celular,
            cidade,
            estado,
            data_nascimento,
            profissao,
            organizacao,
            email_autorizacao,
            celular_autorizacao,
            identificacoes,
            motivacoes,
            interesses,
            ods,
            maturidade,
            setores,
            perfil_impacto,
            alcance,
            engajamento,
            senha_hash
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $nome,
        $sobrenome,
        $email,
        $cpf,
        $celular,
        $cidade,
        $estado,
        $dataNascimento ?: null,
        $profissao,
        $organizacao,
        $emailAutorizacao,
        $celularAutorizacao,
        $identificacoesJson,
        $motivacoesJson,
        $interessesJson,
        $odsJson,
        $maturidadeJson,
        $setoresJson,
        $perfilImpactoJson,
        $alcance,
        $engajamentoJson,
        $senhaHash
    ]);

    $usuarioId = (int)$pdo->lastInsertId();

    // login ok
    session_regenerate_id(false);

    $_SESSION['logado']        = true;
    $_SESSION['usuario_id']    = $usuarioId; // ✅ variável correta
    $_SESSION['usuario_nome']  = $nome;      // ✅
    $_SESSION['usuario_email'] = $email;     // ✅
    $_SESSION['usuario_tipo']  = 'sociedade_civil';

    header("Location: /cadastro_sucesso.php");
    exit;

} catch (PDOException $e) {
    die("ERRO DO BANCO DE DADOS: " . $e->getMessage());
}