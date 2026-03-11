<?php
declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/functions.php';


require_role(['empreendedor']);

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$errors = [];


// Captura os dados do formulário
$id             = (int)($_POST['id'] ?? 0);
$nome_fantasia  = trim($_POST['nome_fantasia'] ?? '');
$razao_social   = trim($_POST['razao_social'] ?? '');
$categoria      = trim($_POST['categoria'] ?? '');
// normaliza: remove tudo que não for dígito
$cnpj_cpf_raw = $_POST['cnpj_cpf'] ?? '';
$cnpj_cpf = preg_replace('/\D/', '', $cnpj_cpf_raw); // apenas dígitos

$formato_legal  = trim($_POST['formato_legal'] ?? '');
$formato_outros = trim($_POST['formato_outros'] ?? '');
$data_fundacao  = trim($_POST['data_fundacao'] ?? '');
$setor          = trim($_POST['setor'] ?? '');
$rua            = trim($_POST['rua'] ?? '');
$numero         = trim($_POST['numero'] ?? '');
$complemento    = trim($_POST['complemento'] ?? '');
$cep            = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$municipio      = trim($_POST['municipio'] ?? '');
$estado         = trim($_POST['estado'] ?? '');
$pais           = 'Brasil';
$site           = trim($_POST['site'] ?? '');
$linkedin       = trim($_POST['linkedin'] ?? '');
$instagram      = trim($_POST['instagram'] ?? '');
$facebook       = trim($_POST['facebook'] ?? '');
$tiktok         = trim($_POST['tiktok'] ?? '');
$youtube        = trim($_POST['youtube'] ?? '');
$outros_links   = trim($_POST['outros_links'] ?? '');

// DEBUG temporário - remova em produção
error_log("DEBUG CNPJ raw: {$cnpj_cpf_raw} | digits: {$cnpj_cpf}");

// ---------- Helpers de validação (servidor) ----------
function only_digits(string $s): string {
    return preg_replace('/\D/', '', $s);
}

function isValidCPF(string $cpf): bool {
    $cpf = only_digits($cpf);
    if (strlen($cpf) !== 11) return false;
    if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;

    $sum = 0;
    for ($i = 0, $p = 10; $i < 9; $i++, $p--) {
        $sum += (int)$cpf[$i] * $p;
    }
    $r = $sum % 11;
    $d1 = ($r < 2) ? 0 : 11 - $r;
    if ($d1 !== (int)$cpf[9]) return false;

    $sum = 0;
    for ($i = 0, $p = 11; $i < 10; $i++, $p--) {
        $sum += (int)$cpf[$i] * $p;
    }
    $r = $sum % 11;
    $d2 = ($r < 2) ? 0 : 11 - $r;
    return $d2 === (int)$cpf[10];
}


// ---------- Validações simples ----------
if ($id <= 0) {
    $errors[] = "ID do negócio inválido.";
}

if ($nome_fantasia === '') {
    $errors[] = "Nome Fantasia é obrigatório.";
}
if ($razao_social === '') {
    $errors[] = "Razão Social é obrigatória.";
}
if ($categoria === '') {
    $errors[] = "Categoria é obrigatória.";
}
if ($cnpj_cpf === '') {
    $errors[] = "CPF ou CNPJ é obrigatório.";
}
if ($formato_legal === '') {
    $errors[] = "Formato Legal é obrigatório.";
} elseif ($formato_legal === 'Outros' && $formato_outros === '') {
    $errors[] = "Informe o formato legal quando selecionar 'Outros'.";
}
if ($data_fundacao === null || $data_fundacao === '') {
    $errors[] = "Informe a data de fundação.";
} else {
    $d = DateTime::createFromFormat('Y-m-d', $data_fundacao);
    $hoje = new DateTime('today'); // pega a data atual do servidor

    if (!$d || $d->format('Y-m-d') !== $data_fundacao) {
        $errors[] = "Data de fundação inválida.";
    } elseif ($d > $hoje) {
        $errors[] = "Data de fundação não pode ser futura.";
    }
}
if ($setor === '') {
    $errors[] = "Setor é obrigatório.";
}
if ($rua === '' || $numero === '' || $cep === '' || $municipio === '' || $estado === '') {
    $errors[] = "Endereço completo é obrigatório.";
}

// Validação CEP
if (!preg_match('/^\d{8}$/', $cep)) {
    $errors[] = "CEP inválido. Informe 8 dígitos.";
} else {
    $viacep = @file_get_contents("https://viacep.com.br/ws/{$cep}/json/");
    $dadosCep = json_decode($viacep, true);
    if (!is_array($dadosCep) || isset($dadosCep['erro'])) {
        $errors[] = "CEP não encontrado.";
    } else {
        // opcional: preencher campos vazios
        if (empty($municipio)) $municipio = $dadosCep['localidade'] ?? $municipio;
        if (empty($estado)) $estado = $dadosCep['uf'] ?? $estado;
        if (empty($rua)) $rua = $dadosCep['logradouro'] ?? $rua;
    }
}

// Validação CPF/CNPJ conforme categoria
if ($categoria === 'Ideação') {
    // aceita CPF (11) ou CNPJ (14)
    if ($cnpj_cpf === '') {
        $errors[] = "Informe CPF ou CNPJ.";
    } else {
        if (strlen($cnpj_cpf) === 11) {
            if (!isValidCPF($cnpj_cpf)) {
                $errors[] = "CPF inválido. Verifique os dígitos.";
            }
        } elseif (strlen($cnpj_cpf) === 14) {
            if (!isValidCNPJ($cnpj_cpf)) {
                $errors[] = "CNPJ inválido. Verifique os dígitos.";
            }
        } else {
            $errors[] = "Informe CPF (11 dígitos) ou CNPJ (14 dígitos).";
        }
    }
} else {
    // exige CNPJ 14 dígitos
    if ($cnpj_cpf === '') {
        $errors[] = "Informe o CNPJ.";
    } else {
        if (strlen($cnpj_cpf) !== 14) {
            $errors[] = "Informe um CNPJ válido com 14 dígitos.";
        } elseif (!isValidCNPJ($cnpj_cpf)) {
            $errors[] = "CNPJ inválido. Verifique os dígitos.";
        }
    }
}

// Validação simples de URLs (se preenchidas)
$urls = ['site' => $site, 'linkedin' => $linkedin, 'instagram' => $instagram, 'facebook' => $facebook, 'tiktok' => $tiktok, 'youtube' => $youtube, 'outros_links' => $outros_links];
foreach ($urls as $k => $u) {
    if ($u !== '' && !filter_var($u, FILTER_VALIDATE_URL)) {
        $errors[] = "URL inválida em {$k}. Use http:// ou https://";
    }
}

if (!empty($errors)) {
    $_SESSION['errors_etapa1'] = $errors;
    header("Location: /negocios/editar_etapa1.php?id=" . $id);
    exit;
}

// ---------- Atualiza no banco ----------
try {
    $sql = "UPDATE negocios SET
        nome_fantasia = ?,
        razao_social = ?,
        categoria = ?,
        cnpj_cpf = ?,
        formato_legal = ?,
        formato_outros = ?,
        data_fundacao = ?,
        setor = ?,
        rua = ?,
        numero = ?,
        complemento = ?,
        cep = ?,
        municipio = ?,
        estado = ?,
        pais = ?,
        site = ?,
        linkedin = ?,
        instagram = ?,
        facebook = ?,
        tiktok = ?,
        youtube = ?,
        outros_links = ?
    WHERE id = ? AND empreendedor_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nome_fantasia,
        $razao_social,
        $categoria,
        $cnpj_cpf, // apenas dígitos
        $formato_legal,
        $formato_outros,
        $data_fundacao,
        $setor,
        $rua,
        $numero,
        $complemento,
        $cep,
        $municipio,
        $estado,
        $pais,
        $site,
        $linkedin,
        $instagram,
        $facebook,
        $tiktok,
        $youtube,
        $outros_links,
        $id,
        $_SESSION['user_id']
    ]);

    header("Location: /empreendedores/meus-negocios.php");
    exit;

} catch (PDOException $e) {
    // Em produção, logar o erro em arquivo e mostrar mensagem genérica
    $errors[] = "Erro ao atualizar o negócio. Tente novamente.";
    $_SESSION['errors_etapa1'] = $errors;
    header("Location: /negocios/editar_etapa1.php?id=" . $id);
    exit;
}