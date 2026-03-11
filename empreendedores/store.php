<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

// CSRF
if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
    http_response_code(403);
    die('Requisição inválida.');
}

require_once __DIR__ . '/../app/validators/validate_empreendedor.php';
require_once __DIR__ . '/../app/helpers/functions.php';

// Sanitização
$data = [
    'nome' => trim($_POST['nome'] ?? ''),
    'sobrenome' => trim($_POST['sobrenome'] ?? ''),
    'cpf' => sanitize_text($_POST['cpf'] ?? ''),
    'email' => trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL)),
    'celular' => sanitize_text($_POST['celular'] ?? ''),
    'data_nascimento' => $_POST['data_nascimento'] ?? '',
    'genero' => sanitize_text($_POST['genero'] ?? ''),
    'cidade' => trim($_POST['cidade'] ?? ''),
    'estado' => trim($_POST['estado'] ?? ''),
    'pais' => sanitize_text($_POST['pais'] ?? ''),
    'regiao' => trim($_POST['regiao'] ?? ''),
    'cargo' => trim($_POST['cargo'] ?? ''),
    'origem_conhecimento' => sanitize_text($_POST['origem_conhecimento'] ?? ''),
    'consentimento_email' => isset($_POST['consentimento_email']) ? 1 : 0,
    'consentimento_whatsapp' => isset($_POST['consentimento_whatsapp']) ? 1 : 0,
    'termos_uso' => isset($_POST['termos_uso']) ? 1 : 0,
    'senha' => $_POST['senha'] ?? '',
    'senha_confirm' => $_POST['senha_confirm'] ?? '',
    'eh_fundador' => $_POST['eh_fundador'] ?? 'Não',
    'formacao' => $_POST['formacao'] ?? null,
    'etnia' => $_POST['etnia'] ?? null,
];

// Validação
$erros = validar_empreendedor($data);

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $erros['email'] = "E-mail inválido.";
}
// Verifica termos de uso
if (!$data['termos_uso']) {
    $erros['termos_uso'] = "É necessário concordar com os termos de uso e a política de privacidade.";
}

// Confirmação de senha
if ($data['senha'] !== $data['senha_confirm']) {
    $erros['senha_confirm'] = "As senhas não conferem.";
}

// Se é fundador, formação e etnia obrigatórios
if ($data['eh_fundador'] === 'Sim') {
    if (empty($data['formacao'])) {
        $erros['formacao'] = "Informe sua formação.";
    }
    if (empty($data['etnia'])) {
        $erros['etnia'] = "Informe sua etnia/raça.";
    }
} else {
    $data['formacao'] = null;
    $data['etnia'] = null;
}

if (!empty($erros)) {
    $mensagens = implode("\\n", $erros);
    echo "<script>alert('Corrija os erros:\\n{$mensagens}'); window.history.back();</script>";
    exit;
}

// Conexão PDO
$config = require_once __DIR__ . '/../app/config/db.php';

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];


try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
    
    // FORÇA UTF-8MB4 em todas as variáveis de conexão
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET character_set_client = utf8mb4");
    $pdo->exec("SET character_set_connection = utf8mb4");
    $pdo->exec("SET character_set_results = utf8mb4");
    $pdo->exec("SET collation_connection = utf8mb4_unicode_ci");
    
} catch (PDOException $e) {
    die("Erro na conexão com o banco: " . $e->getMessage());
}
// Normalizações
$data['cpf'] = only_digits($data['cpf']);
$data['celular'] = only_digits($data['celular']);
$data['eh_fundador'] = (($_POST['eh_fundador'] ?? 'Não') === 'Sim') ? 1 : 0;
$passwordHash = password_hash($data['senha'], PASSWORD_BCRYPT);

// Regras de unicidade
$stmt = $pdo->prepare("SELECT id FROM empreendedores WHERE email = ? LIMIT 1");
$stmt->execute([$data['email']]);
if ($stmt->fetch()) {
    http_response_code(409);
    die("E-mail já cadastrado.");
}

if (!empty($data['cpf'])) {
    $stmt = $pdo->prepare("SELECT id FROM empreendedores WHERE cpf = ? LIMIT 1");
    $stmt->execute([$data['cpf']]);
    if ($stmt->fetch()) {
        http_response_code(409);
        die("CPF já cadastrado.");
    }
}

// Inserção
$sql = "INSERT INTO empreendedores
        (nome, sobrenome, cpf, email, celular, data_nascimento, genero, cidade, estado, pais, regiao,
         cargo, origem_conhecimento, consentimento_email, consentimento_whatsapp, termos_uso, senha_hash,
         eh_fundador, formacao, etnia, criado_em)
        VALUES (:nome, :sobrenome, :cpf, :email, :celular, :data_nascimento, :genero, :cidade, :estado, :pais, :regiao,
                :cargo, :origem_conhecimento, :consentimento_email, :consentimento_whatsapp, :termos_uso, :senha_hash,
                :eh_fundador, :formacao, :etnia, CURRENT_TIMESTAMP)";

$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([
        ':nome' => $data['nome'],
        ':sobrenome' => $data['sobrenome'],
        ':cpf' => $data['cpf'] ?: null,
        ':email' => $data['email'],
        ':celular' => $data['celular'] ?: null,
        ':data_nascimento' => !empty($data['data_nascimento']) ? $data['data_nascimento'] : null,
        ':genero' => !empty($data['genero']) ? $data['genero'] : null,
        ':cidade' => !empty($data['cidade']) ? $data['cidade'] : null,
        ':estado' => !empty($data['estado']) ? $data['estado'] : null,
        ':pais' => !empty($data['pais']) ? $data['pais'] : null,
        ':regiao' => !empty($data['regiao']) ? $data['regiao'] : null,
        ':cargo' => !empty($data['cargo']) ? $data['cargo'] : null,
        ':origem_conhecimento' => !empty($data['origem_conhecimento']) ? $data['origem_conhecimento'] : null,
        ':consentimento_email' => $data['consentimento_email'],
        ':consentimento_whatsapp' => $data['consentimento_whatsapp'],
        ':termos_uso' => $data['termos_uso'],
        ':senha_hash' => $passwordHash,
        ':eh_fundador' => $data['eh_fundador'],
        ':formacao' => $data['formacao'],
        ':etnia' => $data['etnia'],
    ]);

    $empreendedorId = $pdo->lastInsertId();
    $_SESSION['empreendedor_id'] = $empreendedorId;
    $_SESSION['empreendedor_nome'] = $data['nome'];
    $_SESSION['empreendedor_email'] = $data['email'];
    $_SESSION['eh_fundador'] = $data['eh_fundador'];
    $_SESSION['logged_at'] = time();

} catch (PDOException $e) {
    http_response_code(500);
    die("Erro ao salvar cadastro: " . $e->getMessage());
}

// Envio de e-mail
require_once __DIR__ . '/../app/helpers/mail.php';
require_once __DIR__ . '/../app/helpers/email_template.php';

$subject = "Bem-vindo à Plataforma Impactos Positivos";
$body = render_email_template(
    __DIR__ . '/../app/views/emails/new_empreendedor.php',
    [
        'nome' => $data['nome'],
        'sobrenome' => $data['sobrenome'],
        'email' => $data['email'],
    ]
);

$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: Plataforma Impactos Positivos <noreply@impactospositivos.com.br>\r\n";


send_mail(
    $data['email'],
    $data['nome'] . ' ' . $data['sobrenome'],
    $subject,
    $body,
    $headers
);

session_write_close(); 

// Redireciona para sucesso
header("Location: /empreendedores/sucesso.php?email=" . urlencode($data['email']));
exit;