<?php
session_start();

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

        // --------- Redirecionamento Inteligente ---------
    $modo = $_POST['modo'] ?? 'cadastro';

    // Busca o status de andamento do negócio
    $stmtProgresso = $pdo->prepare("SELECT etapa_atual, inscricao_completa FROM negocios WHERE id = ?");
    $stmtProgresso->execute([$negocio_id]);
    $progresso = $stmtProgresso->fetch(PDO::FETCH_ASSOC);

    if ($modo === 'cadastro') {
        // Modo Cadastro: Atualiza etapa para 10 se for menor que isso
        $etapaAtualNoBanco = (int)($progresso['etapa_atual'] ?? 1);
        
        if ($etapaAtualNoBanco < 10) {
            $stmtUpdate = $pdo->prepare("
                UPDATE negocios 
                SET etapa_atual = 10, updated_at = NOW() 
                WHERE id = ? AND empreendedor_id = ?
            ");
            $stmtUpdate->execute([$negocio_id, $_SESSION['user_id']]);
        }

        // Cadastro: vai para confirmação final (que é a etapa 10)
        header("Location: /negocios/confirmacao.php?id=" . $negocio_id);
        exit;
        
    } else {
        // Modo Edição (Voltou aqui só para alterar um arquivo)
        $_SESSION['sucesso'] = "Documentação atualizada com sucesso!";
        
        if (!empty($progresso['inscricao_completa'])) {
            // Se já completou tudo (já passou da confirmação antes), volta pra lá
            header("Location: /negocios/confirmacao.php?id=" . $negocio_id);
            exit;
        } else {
            // Como a etapa 9 é a última, e ele já estava editando, o mais lógico
            // é mandar para a etapa onde ele tinha parado no geral, ou confirmação.
            $rotas_etapas = [
                1 => '/negocios/etapa1_dados_negocio.php',
                2 => '/negocios/etapa2_fundadores.php',
                3 => '/negocios/etapa3_eixo_tematico.php',
                4 => '/negocios/etapa4_ods.php',    
                5 => '/negocios/etapa5_apresentacao.php',
                6 => '/negocios/etapa6_financeiro.php',
                7 => '/negocios/etapa7_impacto.php',
                8 => '/negocios/etapa8_visao.php',
                9 => '/negocios/etapa9_documentacao.php',
                10 => '/negocios/confirmacao.php'
            ];

            $etapaParada = (int)($progresso['etapa_atual'] ?? 10);
            
            if (isset($rotas_etapas[$etapaParada])) {
                header("Location: " . $rotas_etapas[$etapaParada] . "?id=" . $negocio_id);
            } else {
                header("Location: /empreendedores/meus-negocios.php");
            }
            exit;
        }
    }

} catch (PDOException $e) {
    // Trata erro de salvar a etapa 9
    error_log("Erro ao salvar documentação (Etapa 9) do negócio $negocio_id: " . $e->getMessage());
    $_SESSION['erro'] = "Erro ao salvar a documentação. Tente novamente."; // Ajustado de erro_etapa9 para o padrão $_SESSION['erro']
    
    // Resolve pra onde jogar em caso de erro
    $modo = $_POST['modo'] ?? 'cadastro';
    $redirectUrl = ($modo === 'editar') 
        ? "/negocios/editar_etapa9.php?id=" . $negocio_id 
        : "/negocios/etapa9_documentacao.php?id=" . $negocio_id;
        
    header("Location: " . $redirectUrl);
    exit;
}
?>
