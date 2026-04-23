<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id = (int)($_POST['negocio_id'] ?? $_SESSION['negocio_id'] ?? 0);

if ($negocio_id === 0 || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /login.php");
    exit;
}

// Valida permissão do empreendedor
$stmt = $pdo->prepare("SELECT id FROM negocios WHERE id = ? AND empreendedor_id = ?");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
if (!$stmt->fetch()) {
    die("Acesso negado ao negócio ID: " . $negocio_id);
}

$_SESSION['negocio_id'] = $negocio_id;
$modo = $_POST['modo'] ?? 'cadastro';

// Pasta de uploads
$uploadDir = __DIR__ . '/../uploads/negocios/documentos';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
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

// ====== Função de upload com validação de mime ======
function uploadPdfIfSent(string $fieldName, string $prefix, int $negocio_id, string $uploadDir): ?string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $ext      = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    $mimeType = mime_content_type($_FILES[$fieldName]['tmp_name']);

    if ($ext !== 'pdf' || $mimeType !== 'application/pdf') {
        return 'INVALID';
    }

    $fileName = $prefix . '_' . $negocio_id . '_' . date('Ymd_His') . '.pdf';
    $destPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destPath)) {
        return null;
    }

    return '/uploads/negocios/documentos/' . $fileName;
}

// Processa uploads
$novoTrab = uploadPdfIfSent('certidao_trabalhista', 'cndt',      $negocio_id, $uploadDir);
$novoAmb  = uploadPdfIfSent('certidao_ambiental',  'ambiental', $negocio_id, $uploadDir);

// ====== Validação de tipo de arquivo ======
$redirectErro = ($modo === 'editar')
    ? "/negocios/editar_etapa9.php?id=$negocio_id"
    : "/negocios/etapa9_documentacao.php?id=$negocio_id";

if ($novoTrab === 'INVALID' || $novoAmb === 'INVALID') {
    $_SESSION['errors_etapa9'][] = "Envie apenas arquivos em formato PDF válido.";
    header("Location: $redirectErro");
    exit;
}

// Substitui caminho se veio novo arquivo
if ($novoTrab !== null) $cert_trab_path = $novoTrab;
if ($novoAmb  !== null) $cert_amb_path  = $novoAmb;

// ====== Ambas certidões precisam existir ======
if (empty($cert_trab_path) || empty($cert_amb_path)) {
    if (empty($cert_trab_path)) {
        $_SESSION['errors_etapa9'][] = "A Certidão Negativa de Débitos Trabalhistas (CNDT) é obrigatória.";
    }
    if (empty($cert_amb_path)) {
        $_SESSION['errors_etapa9'][] = "A Certidão de Regularidade Ambiental é obrigatória.";
    }
    header("Location: $redirectErro");
    exit;
}

try {
    $sql = "
        INSERT INTO negocios_documentos (negocio_id, certidao_trabalhista_path, certidao_ambiental_path)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            certidao_trabalhista_path = VALUES(certidao_trabalhista_path),
            certidao_ambiental_path   = VALUES(certidao_ambiental_path),
            data_atualizacao          = CURRENT_TIMESTAMP
    ";
    $pdo->prepare($sql)->execute([$negocio_id, $cert_trab_path, $cert_amb_path]);

    // ====== Redirecionamento Inteligente ======
    $stmtProgresso = $pdo->prepare("SELECT etapa_atual, inscricao_completa FROM negocios WHERE id = ?");
    $stmtProgresso->execute([$negocio_id]);
    $progresso = $stmtProgresso->fetch(PDO::FETCH_ASSOC);

    if ($modo === 'cadastro') {
        $etapaAtualNoBanco = (int)($progresso['etapa_atual'] ?? 1);
        if ($etapaAtualNoBanco < 10) {
            $pdo->prepare("
                UPDATE negocios
                SET etapa_atual = 10, updated_at = NOW()
                WHERE id = ? AND empreendedor_id = ?
            ")->execute([$negocio_id, $_SESSION['user_id']]);
        }
        header("Location: /negocios/confirmacao.php?id=" . $negocio_id);
        exit;

    } else {
        // Modo editar
        if (!empty($progresso['inscricao_completa'])) {
            header("Location: /negocios/confirmacao.php?id=" . $negocio_id);
            exit;
        }

        $rotas_etapas = [
            1  => '/negocios/etapa1_dados_negocio.php',
            2  => '/negocios/etapa2_fundadores.php',
            3  => '/negocios/etapa3_eixo_tematico.php',
            4  => '/negocios/etapa4_ods.php',
            5  => '/negocios/etapa5_financeiro.php',
            6  => '/negocios/etapa6_impacto.php',
            7  => '/negocios/etapa7_visao.php',
            8  => '/negocios/etapa8_apresentacao.php',
            9  => '/negocios/etapa9_documentacao.php',
            10 => '/negocios/confirmacao.php',
        ];

        $etapaParada = (int)($progresso['etapa_atual'] ?? 10);
        header("Location: " . ($rotas_etapas[$etapaParada] ?? '/empreendedores/meus-negocios.php') . "?id=" . $negocio_id);
        exit;
    }

} catch (PDOException $e) {
    error_log("Erro ao salvar documentação (Etapa 9) do negócio $negocio_id: " . $e->getMessage());
    $_SESSION['errors_etapa9'][] = "Erro ao salvar a documentação. Tente novamente.";
    header("Location: $redirectErro");
    exit;
}