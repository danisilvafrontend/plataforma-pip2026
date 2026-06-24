<?php
// /app/api/verificar_cpf.php
// Verifica se um CPF (e opcionalmente e-mail) já existe em qualquer tabela da plataforma.
// Retorna JSON: { "disponivel": true } ou { "disponivel": false, "mensagem": "..." }

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit(json_encode(['erro' => 'Método não permitido.']));
}

$cpf   = preg_replace('/[^0-9]/', '', $_GET['cpf'] ?? '');
$email = trim($_GET['email'] ?? '');

if (strlen($cpf) !== 11) {
    http_response_code(400);
    exit(json_encode(['erro' => 'CPF inválido.']));
}

try {
    $config = require __DIR__ . '/../config/db.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit(json_encode(['erro' => 'Erro interno.']));
}

// ── Verifica sociedade_civil ────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sociedade_civil WHERE cpf = ?");
$stmt->execute([$cpf]);
if ((int)$stmt->fetchColumn() > 0) {
    http_response_code(200);
    exit(json_encode(['disponivel' => false, 'mensagem' => 'Este CPF já está cadastrado na Sociedade Civil.']));
}

// ── Verifica empreendedores ────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM empreendedores WHERE cpf = ?");
$stmt->execute([$cpf]);
if ((int)$stmt->fetchColumn() > 0) {
    http_response_code(200);
    exit(json_encode(['disponivel' => false, 'mensagem' => 'Este CPF já está cadastrado como Empreendedor. Cada pessoa só pode ter um perfil na plataforma.']));
}

// ── Verifica parceiros ─────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM parceiros WHERE rep_cpf = ?");
$stmt->execute([$cpf]);
if ((int)$stmt->fetchColumn() > 0) {
    http_response_code(200);
    exit(json_encode(['disponivel' => false, 'mensagem' => 'Este CPF já está cadastrado como representante de um Parceiro. Cada pessoa só pode ter um perfil na plataforma.']));
}

// ── Verifica e-mail (opcional) ─────────────────────────────────────────────
if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sociedade_civil WHERE email = ?");
    $stmt->execute([$email]);
    if ((int)$stmt->fetchColumn() > 0) {
        http_response_code(200);
        exit(json_encode(['disponivel' => false, 'mensagem' => 'Este e-mail já está cadastrado na plataforma.']));
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM empreendedores WHERE email = ?");
    $stmt->execute([$email]);
    if ((int)$stmt->fetchColumn() > 0) {
        http_response_code(200);
        exit(json_encode(['disponivel' => false, 'mensagem' => 'Este e-mail já está cadastrado como Empreendedor.']));
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM parceiros WHERE email_login = ?");
    $stmt->execute([$email]);
    if ((int)$stmt->fetchColumn() > 0) {
        http_response_code(200);
        exit(json_encode(['disponivel' => false, 'mensagem' => 'Este e-mail já está cadastrado como Parceiro.']));
    }
}

http_response_code(200);
echo json_encode(['disponivel' => true]);
