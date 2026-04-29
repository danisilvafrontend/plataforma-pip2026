<?php
// /app/api/cpfcnpj_proxy.php
// ─────────────────────────────────────────────────────────────────────────────
// Proxy seguro para a API cpfcnpj.com.br
// O token NUNCA é enviado ao navegador — fica somente neste arquivo de servidor.
//
// Como usar nas páginas:
//   fetch('/app/api/cpfcnpj_proxy.php?tipo=cnpj&doc=12345678000195')
//   fetch('/app/api/cpfcnpj_proxy.php?tipo=cpf&doc=12345678901')
// ─────────────────────────────────────────────────────────────────────────────
declare(strict_types=1);

// ── 1. Token (só existe aqui, no servidor) ────────────────────────────────────
define('CPFCNPJ_TOKEN', '02f7cd04ecd9b64e984dc74970f9f97b');

// ── Pacotes utilizados ────────────────────────────────────────────────────────
// Pacote 4 = CNPJ A  → retorna razao_social (dados básicos)
// Pacote 1 = CPF A   → retorna nome (dados básicos)
define('PACOTE_CNPJ', '4');
define('PACOTE_CPF',  '1');

// ── 2. Bloqueia qualquer método que não seja GET ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit(json_encode(['erro' => 'Método não permitido.']));
}

// ── 3. Valida os parâmetros recebidos ─────────────────────────────────────────
$tipo = strtolower(trim($_GET['tipo'] ?? ''));   // 'cnpj' ou 'cpf'
$doc  = preg_replace('/[^0-9]/', '', $_GET['doc'] ?? '');

if (!in_array($tipo, ['cnpj', 'cpf'], true)) {
    http_response_code(400);
    exit(json_encode(['erro' => 'Parâmetro "tipo" inválido. Use "cnpj" ou "cpf".']));
}

$tamanhoEsperado = ($tipo === 'cnpj') ? 14 : 11;
if (strlen($doc) !== $tamanhoEsperado) {
    http_response_code(400);
    exit(json_encode(['erro' => 'Documento com número de dígitos inválido.']));
}

// ── 4. Monta a URL da API externa ─────────────────────────────────────────────
$pacote = ($tipo === 'cnpj') ? PACOTE_CNPJ : PACOTE_CPF;
$url    = 'https://api.cpfcnpj.com.br/' . CPFCNPJ_TOKEN . '/' . $pacote . '/' . $doc;

// ── 5. Faz a requisição via cURL ──────────────────────────────────────────────
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    CURLOPT_SSL_VERIFYPEER => true,
]);

$resposta   = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErro   = curl_error($ch);
curl_close($ch);

// ── 6. Trata erros de conexão ─────────────────────────────────────────────────
if ($curlErro) {
    http_response_code(502);
    exit(json_encode(['erro' => 'Falha na conexão com a Receita Federal. Tente novamente.']));
}

// ── 7. Repassa o status HTTP e o corpo da resposta ao front-end ───────────────
header('Content-Type: application/json; charset=utf-8');

// Repassa apenas os status relevantes
if ($httpStatus === 200) {
    http_response_code(200);
    echo $resposta; // JSON original da API
} elseif ($httpStatus === 400 || $httpStatus === 404) {
    http_response_code(404);
    echo json_encode(['erro' => 'Documento não encontrado na Receita Federal.']);
} elseif ($httpStatus === 401 || $httpStatus === 403) {
    http_response_code(403);
    echo json_encode(['erro' => 'Sem créditos ou token inválido. Contate o administrador.']);
} elseif ($httpStatus === 429) {
    http_response_code(429);
    echo json_encode(['erro' => 'Limite de requisições atingido. Aguarde um momento.']);
} else {
    http_response_code(502);
    echo json_encode(['erro' => 'Erro inesperado ao consultar a Receita Federal (HTTP ' . $httpStatus . ').']);
}