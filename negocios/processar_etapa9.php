<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Aceita tanto $_SESSION['negocio_id'] quanto POST['negocio_id']
$negocio_id = (int)($_POST['negocio_id'] ?? $_SESSION['negocio_id'] ?? 0);

if ($negocio_id === 0 || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /login_empreendedor.php");
    exit;
}

// Valida permissão do empreendedor
$stmt = $pdo->prepare("SELECT id FROM negocios WHERE id = ? AND empreendedor_id = ?");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
if (!$stmt->fetch()) {
    die("Acesso negado ao negócio ID: " . $negocio_id);
}

$_SESSION['negocio_id'] = $negocio_id;

// Pasta de uploads
$uploadDir = __DIR__ . '/../uploads/negocios/documentos';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Busca arquivos já existentes
$stmt = $pdo->prepare("
    SELECT certidao_trabalhista_path, certidao_ambiental_path
    FROM negocios_documentos
    WHERE negocio_id = ?
");
$stmt->execute([$negocio_id]);
$docsAtuais = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'certidao_trabalhista_path' => null,
    'certidao_ambiental_path'   => null,
];

$cert_trab_path = $docsAtuais['certidao_trabalhista_path'];
$cert_amb_path  = $docsAtuais['certidao_ambiental_path'];

function uploadPdfIfSent(string $fieldName, string $prefix, int $negocio_id, string $uploadDir): ?string {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        return 'INVALID';
    }

    $fileName = $prefix . '_' . $negocio_id . '_' . date('Ymd_His') . '.pdf';
    $destPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destPath)) {
        return null;
    }

    // CAMINHO ABSOLUTO para raiz do site
    return '/uploads/negocios/documentos/' . $fileName;
}


// Processa uploads se houver novos arquivos
$novoTrab = uploadPdfIfSent('certidao_trabalhista', 'cndt', $negocio_id, $uploadDir);
$novoAmb  = uploadPdfIfSent('certidao_ambiental', 'ambiental', $negocio_id, $uploadDir);

// Validação de tipo de arquivo
if ($novoTrab === 'INVALID' || $novoAmb === 'INVALID') {
    $_SESSION['erro_etapa9'] = "Envie apenas arquivos em formato PDF.";
    $redirect = ($_POST['modo'] ?? 'cadastro') === 'edicao' ? 'editar_etapa9.php' : 'etapa9_documentacao.php';
    header("Location: $redirect");
    exit;
}

// Se veio novo arquivo, substitui o caminho
if ($novoTrab !== null && $novoTrab !== 'INVALID') {
    $cert_trab_path = $novoTrab;
}
if ($novoAmb !== null && $novoAmb !== 'INVALID') {
    $cert_amb_path = $novoAmb;
}

// Ambas certidões precisam existir
if (empty($cert_trab_path) || empty($cert_amb_path)) {
    $_SESSION['erro_etapa9'] = "Ambas as certidões são obrigatórias.";
    $redirect = ($_POST['modo'] ?? 'cadastro') === 'edicao' ? 'editar_etapa9.php' : 'etapa9_documentacao.php';
    header("Location: $redirect");
    exit;
}

try {
    // Salva/atualiza tabela de documentos
    $sql = "
        INSERT INTO negocios_documentos (negocio_id, certidao_trabalhista_path, certidao_ambiental_path)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            certidao_trabalhista_path = VALUES(certidao_trabalhista_path),
            certidao_ambiental_path   = VALUES(certidao_ambiental_path),
            data_atualizacao = CURRENT_TIMESTAMP
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$negocio_id, $cert_trab_path, $cert_amb_path]);

    // Atualiza etapa_atual para 10 (confirmação)
    $stmt = $pdo->prepare("UPDATE negocios SET etapa_atual = GREATEST(etapa_atual, 10), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$negocio_id]);

    // Decide destino baseado no modo
    $modo = $_POST['modo'] ?? 'cadastro';
    if ($modo === 'editar') {
        // Edição: volta para meus negócios com sucesso
        $_SESSION['sucesso'] = "Documentação atualizada com sucesso!";
        header("Location: /empreendedores/meus-negocios.php");
    } else {
        // Cadastro: vai para confirmação
        header("Location: /negocios/confirmacao.php?id=" . $negocio_id);
    }
    exit;

} catch (PDOException $e) {
    error_log("Erro ao salvar documentação (Etapa 9) do negócio $negocio_id: " . $e->getMessage());
    $_SESSION['erro_etapa9'] = "Erro ao salvar a documentação. Tente novamente.";
    $redirect = ($_POST['modo'] ?? 'cadastro') === 'edicao' ? 'editar_etapa9.php' : 'etapa9_documentacao.php';
    header("Location: $redirect");
    exit;
}
?>
