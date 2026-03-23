<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

$negocio_id = (int)($_POST['negocio_id'] ?? 0);
if ($negocio_id === 0) {
    $_SESSION['errors_etapa7'][] = "Negócio inválido.";
    header("Location: /negocios/etapa7_impacto.php?id=" . $negocio_id);
    exit;
}

// Captura dos campos
$intencionalidade   = $_POST['intencionalidade'] ?? null;
$tipo_impacto       = $_POST['tipo_impacto'] ?? null;
$beneficiarios      = $_POST['beneficiarios'] ?? [];
$beneficiario_outro = trim($_POST['beneficiario_outro'] ?? '');
$alcance            = $_POST['alcance'] ?? null;
$metricas           = $_POST['metricas'] ?? [];
$metrica_outro      = trim($_POST['metrica_outro'] ?? '');
$medicao            = $_POST['medicao'] ?? null;
$formas_medicao     = $_POST['formas_medicao'] ?? [];
$forma_outro        = trim($_POST['forma_outro'] ?? '');
$reporte            = $_POST['reporte'] ?? null;
$resultados         = $_POST['resultados'] ?? null;
$proximos_passos    = $_POST['proximos_passos'] ?? null;

// Limite de beneficiários e métricas
$beneficiarios = array_slice($beneficiarios, 0, 3);
$metricas = array_slice($metricas, 0, 8);
$formas_medicao = array_slice($formas_medicao, 0, 4);

// Links externos (até 4)
$links = $_POST['resultados_link'] ?? [];
$links = array_filter($links, fn($l) => !empty(trim($l)));
$links = array_slice($links, 0, 4);

// PDFs existentes
$stmt = $pdo->prepare("SELECT resultados_pdfs FROM negocio_impacto WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$impactoExistente = $stmt->fetch(PDO::FETCH_ASSOC);
$existentes = json_decode($impactoExistente['resultados_pdfs'] ?? '[]', true);

// Inicializa array final
$pdfs = [];

// Upload de novos PDFs (até 4, cada ≤ 5MB)
if (!empty($_FILES['resultados_pdf']['name'][0])) {
    $uploadDir = __DIR__ . '/../uploads/negocios/documentos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    foreach ($_FILES['resultados_pdf']['name'] as $index => $name) {
        if ($index >= 4) break;
        $tmpName = $_FILES['resultados_pdf']['tmp_name'][$index];
        $size = $_FILES['resultados_pdf']['size'][$index];
        $error = $_FILES['resultados_pdf']['error'][$index];

        if ($error === UPLOAD_ERR_OK && $size <= 5 * 1024 * 1024) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            if (strtolower($ext) === 'pdf') {
                $newName = uniqid("impacto_", true) . ".pdf";
                $destPath = $uploadDir . $newName;
                if (move_uploaded_file($tmpName, $destPath)) {
                    $pdfs[] = "uploads/negocios/documentos/" . $newName;
                }
            }
        }
    }
}

// Mantém existentes que não foram removidos
$remover = $_POST['remover_pdf'] ?? [];
foreach ($existentes as $pdf) {
    if (!in_array($pdf, $remover)) {
        $pdfs[] = $pdf;
    }
}

// Limita a 4 PDFs
$pdfs = array_slice($pdfs, 0, 4);

// Limita proximos_passos a 1000 caracteres
if ($proximos_passos && strlen($proximos_passos) > 1000) {
    $proximos_passos = substr($proximos_passos, 0, 1000);
}

// ===== Validações extras =====
if (in_array("Outro", $beneficiarios)) {
    if ($beneficiario_outro === '') {
        $_SESSION['errors_etapa7'][] = "Você marcou 'Outro' em beneficiários, mas não especificou.";
    } elseif (mb_strlen($beneficiario_outro) > 120) {
        $_SESSION['errors_etapa7'][] = "O campo 'Outro' em beneficiários deve ter no máximo 120 caracteres.";
    }
}

if (in_array("Outro", $metricas)) {
    if ($metrica_outro === '') {
        $_SESSION['errors_etapa7'][] = "Você marcou 'Outro' em métricas, mas não especificou.";
    } elseif (mb_strlen($metrica_outro) > 120) {
        $_SESSION['errors_etapa7'][] = "O campo 'Outro' em métricas deve ter no máximo 120 caracteres.";
    }
}

if (in_array("Outro", $formas_medicao)) {
    if ($forma_outro === '') {
        $_SESSION['errors_etapa7'][] = "Você marcou 'Outro' em formas de medição, mas não especificou.";
    } elseif (mb_strlen($forma_outro) > 120) {
        $_SESSION['errors_etapa7'][] = "O campo 'Outro' em formas de medição deve ter no máximo 120 caracteres.";
    }
}
// VALIDAR TEXTOS
function textoValido($texto) {
    $texto = trim($texto);
    // Pelo menos 5 letras no total, não precisa ser consecutivas
    return preg_match_all('/[a-zA-ZÀ-ÿ]/', $texto) >= 5;
}
// Validações de texto
if ($beneficiario_outro && !textoValido($beneficiario_outro)) {
    $_SESSION['errors_etapa7'][] = "O campo 'Outro' em Beneficiários deve conter texto válido.";
}

if ($metrica_outro && !textoValido($metrica_outro)) {
    $_SESSION['errors_etapa7'][] = "O campo 'Outro' em Métricas deve conter texto válido.";
}

if ($forma_outro && !textoValido($forma_outro)) {
    $_SESSION['errors_etapa7'][] = "O campo 'Outro' em Formas de Medição deve conter texto válido.";
}

if ($resultados && !textoValido($resultados)) {
    $_SESSION['errors_etapa7'][] = "O campo 'Resultados' deve conter texto válido.";
}

if ($proximos_passos && !textoValido($proximos_passos)) {
    $_SESSION['errors_etapa7'][] = "O campo 'Próximos Passos' deve conter texto válido.";
}

// Se houver erros, volta para etapa
if (!empty($_SESSION['errors_etapa7'])) {
    header("Location: /negocios/etapa7_impacto.php?id=" . $negocio_id);
    exit;
}

// Insert/Update
$stmt = $pdo->prepare("
    INSERT INTO negocio_impacto (
        negocio_id, intencionalidade, tipo_impacto, beneficiarios, beneficiario_outro,
        alcance, metricas, metrica_outro, medicao, formas_medicao, forma_outro,
        reporte, resultados, resultados_links, resultados_pdfs, proximos_passos,
        criado_em, atualizado_em
    ) VALUES (
        :negocio_id, :intencionalidade, :tipo_impacto, :beneficiarios, :beneficiario_outro,
        :alcance, :metricas, :metrica_outro, :medicao, :formas_medicao, :forma_outro,
        :reporte, :resultados, :resultados_links, :resultados_pdfs, :proximos_passos,
        NOW(), NOW()
    )
    ON DUPLICATE KEY UPDATE
        intencionalidade = VALUES(intencionalidade),
        tipo_impacto = VALUES(tipo_impacto),
        beneficiarios = VALUES(beneficiarios),
        beneficiario_outro = VALUES(beneficiario_outro),
        alcance = VALUES(alcance),
        metricas = VALUES(metricas),
        metrica_outro = VALUES(metrica_outro),
        medicao = VALUES(medicao),
        formas_medicao = VALUES(formas_medicao),
        forma_outro = VALUES(forma_outro),
        reporte = VALUES(reporte),
        resultados = VALUES(resultados),
        resultados_links = VALUES(resultados_links),
        resultados_pdfs = VALUES(resultados_pdfs),
        proximos_passos = VALUES(proximos_passos),
        atualizado_em = NOW()
");

$params = [
    'negocio_id'        => $negocio_id,
    'intencionalidade'  => $intencionalidade,
    'tipo_impacto'      => $tipo_impacto,
    'beneficiarios'     => json_encode($beneficiarios),
    'beneficiario_outro'=> $beneficiario_outro,
    'alcance'           => $alcance,
    'metricas'          => json_encode($metricas),
    'metrica_outro'     => $metrica_outro,
    'medicao'           => $medicao,
    'formas_medicao'    => json_encode($formas_medicao),
    'forma_outro'       => $forma_outro,
    'reporte'           => $reporte,
    'resultados'        => $resultados,
    'resultados_links'  => json_encode($links),
    'resultados_pdfs'   => json_encode($pdfs),
    'proximos_passos'   => $proximos_passos
];

$stmt->execute($params);

// ==========================
// Cálculo do Score Impacto
// ==========================
$stmt = $pdo->prepare("SELECT componente, peso FROM pesos_scores WHERE tipo_score='IMPACTO'");
$stmt->execute();
$pesos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$scoreImpacto = 0;
foreach ($pesos as $p) {
    $componente = $p['componente'];
    $peso = (float)$p['peso'];

    // Normaliza respostas para opções do lookup
    switch ($componente) {
        case 'intencionalidade':
            if (strpos($intencionalidade, 'integrado') !== false) $opcao = 'lucro_com_impacto_integrado';
            elseif (strpos($intencionalidade, 'prioridade') !== false) $opcao = 'missao_acima_lucro';
            else $opcao = 'impacto_secundario';
            break;

        case 'tipo_impacto':
            if (strpos($tipo_impacto, 'sistêmico') !== false) $opcao = 'sistemico';
            elseif (strpos($tipo_impacto, 'cadeia') !== false) $opcao = 'cadeia';
            elseif (strpos($tipo_impacto, 'direto') !== false) $opcao = 'direto';
            else $opcao = 'indireto';
            break;

        case 'alcance':
            if ($alcance === 'Acima de 500') $opcao = 'mais_500';
            elseif ($alcance === '201 a 500') $opcao = '201_500';
            elseif ($alcance === '101 a 200') $opcao = '101_200';
            elseif ($alcance === '51 a 100') $opcao = '51_100';
            else $opcao = '1_50';
            break;

        case 'mensuracao':
            if ($medicao && strpos($medicao, 'auditoria') !== false) $opcao = 'auditoria_framework';
            elseif (in_array("Ferramentas e frameworks reconhecidos (ex: GRI, IRIS+, SDG Compass, GIIRS, SROI etc.)", $formas_medicao)) $opcao = 'framework_reconhecido';
            elseif (in_array("Relatórios internos manuais ou dashboards próprios", $formas_medicao)) $opcao = 'dashboard_interno';
            elseif (in_array("Não fazemos medição formal ainda", $formas_medicao)) $opcao = 'nao_mede';
            else $opcao = 'relatorio_manual';
            break;

        case 'evidencias':
            if (!empty($resultados) && (!empty($links) || !empty($pdfs))) $opcao = 'documentado_com_links';
            elseif (!empty($resultados)) $opcao = 'parcial';
            elseif (empty($resultados)) $opcao = 'vazio';
            else $opcao = 'narrativa';
            break;

        case 'visao_5anos':
            if (!empty($proximos_passos) && strlen($proximos_passos) > 50) $opcao = 'clara_mensuravel';
            elseif (!empty($proximos_passos)) $opcao = 'clara_sem_metricas';
            else $opcao = 'vazio';
            break;

        default:
            $opcao = 'nao_informado';
    }

    // Busca valor normalizado
    $stmt2 = $pdo->prepare("SELECT valor FROM lookup_scores WHERE componente=? AND opcao=?");
    $stmt2->execute([$componente, $opcao]);
    $valor = (int)($stmt2->fetchColumn() ?: 0);

    $scoreImpacto += $valor * $peso;
}

// Penalidades
$penalty = 0;
if ($opcao === 'nao_mede' && $opcao === 'vazio') {
    $penalty += 10;
}

$scoreImpacto = max(0, min(100, round($scoreImpacto - $penalty)));

// Salva no banco
$stmt = $pdo->prepare("
    INSERT INTO scores_negocios (negocio_id, score_impacto, atualizado_em)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE score_impacto=VALUES(score_impacto), atualizado_em=NOW()
");
$stmt->execute([$negocio_id, $scoreImpacto]);

// --------- Redirecionamento Inteligente ---------
$modo = $_POST['modo'] ?? 'cadastro';

// PRIMEIRO: Busca como o negócio está AGORA no banco
$stmtProgresso = $pdo->prepare("SELECT etapa_atual, inscricao_completa FROM negocios WHERE id = ?");
$stmtProgresso->execute([$negocio_id]);
$progresso = $stmtProgresso->fetch(PDO::FETCH_ASSOC);

if ($modo === 'cadastro') {
    // Modo Cadastro: Atualiza a etapa (neste caso da 2 para a 3) SOMENTE SE ainda não passou por ela
    $etapaAtualNoBanco = (int)($progresso['etapa_atual'] ?? 1);
    
    if ($etapaAtualNoBanco < 8) {
        $stmtUpdate = $pdo->prepare("
            UPDATE negocios 
            SET etapa_atual = 8, updated_at = NOW() 
            WHERE id = ? AND empreendedor_id = ?
        ");
        $stmtUpdate->execute([$negocio_id, $_SESSION['user_id']]);
    }

    // Avança para a PRÓXIMA etapa
    header("Location: /negocios/etapa8_visao.php?id=" . $negocio_id);
    exit;
    
} else {
    // Modo Edição: Para onde enviamos o usuário agora?
    
    if (!empty($progresso['inscricao_completa'])) {
        // Já completou tudo = volta para confirmação
        header("Location: /negocios/confirmacao.php?id=" . $negocio_id);
        exit;
    } else {
        // Ainda em andamento = volta para a etapa onde parou
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

        $etapaParada = (int)($progresso['etapa_atual'] ?? 1);
        
        if (isset($rotas_etapas[$etapaParada])) {
            header("Location: " . $rotas_etapas[$etapaParada] . "?id=" . $negocio_id);
        } else {
            header("Location: /empreendedores/meus-negocios.php");
        }
        exit;
    }
}
