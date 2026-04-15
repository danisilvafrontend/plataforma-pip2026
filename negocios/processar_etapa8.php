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

$negocio_id = (int)($_POST['negocio_id'] ?? 0);
if ($negocio_id === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}
function textoValido($texto) {
    $texto = trim($texto);
    return preg_match_all('/[a-zA-ZÀ-ÿ]/', $texto) >= 5;
}

// Campos simples
$visao_estrategica = $_POST['visao_estrategica'] ?? '';
$visao_outro       = $_POST['visao_outro'] ?? '';
$sustentabilidade  = $_POST['sustentabilidade'] ?? '';
$escala            = $_POST['escala'] ?? '';

// Arrays (checkboxes)
$apoios = $_POST['apoios'] ?? [];
$apoio_outro = $_POST['apoio_outro'] ?? '';

$areas = $_POST['areas'] ?? [];
$area_outro = $_POST['area_outro'] ?? '';

$temas = $_POST['temas'] ?? [];
$tema_outro = $_POST['tema_outro'] ?? '';

// Validações
if ($visao_outro && !textoValido($visao_outro)) {
    $_SESSION['errors_etapa8'][] = "O campo 'Outro' em Visão Estratégica deve conter texto válido.";
}
if ($apoio_outro && !textoValido($apoio_outro)) {
    $_SESSION['errors_etapa8'][] = "O campo 'Outro' em Apoio Financeiro/Estratégico deve conter texto válido.";
}
if ($area_outro && !textoValido($area_outro)) {
    $_SESSION['errors_etapa8'][] = "O campo 'Outro' em Áreas a Fortalecer deve conter texto válido.";
}
if ($tema_outro && !textoValido($tema_outro)) {
    $_SESSION['errors_etapa8'][] = "O campo 'Outro' em Temas de Aprendizado deve conter texto válido.";
}

if (!empty($_SESSION['errors_etapa8'])) {
    header("Location: /negocios/etapa8_visao.php?id=" . $negocio_id);
    exit;
}
// Verifica se já existe registro
$stmt = $pdo->prepare("SELECT id FROM negocio_visao WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$existe = $stmt->fetchColumn();

if ($existe) {
    // Update
    $stmt = $pdo->prepare("UPDATE negocio_visao SET 
        visao_estrategica = :visao_estrategica,
        visao_outro       = :visao_outro,
        sustentabilidade  = :sustentabilidade,
        escala            = :escala,
        apoios            = :apoios,
        apoio_outro       = :apoio_outro,
        areas             = :areas,
        area_outro        = :area_outro,
        temas             = :temas,
        tema_outro        = :tema_outro,
        atualizado_em     = NOW()
        WHERE negocio_id  = :negocio_id
    ");
} else {
    // Insert
    $stmt = $pdo->prepare("INSERT INTO negocio_visao (
        negocio_id, visao_estrategica, visao_outro, sustentabilidade, escala,
        apoios, apoio_outro, areas, area_outro, temas, tema_outro, criado_em, atualizado_em
    ) VALUES (
        :negocio_id, :visao_estrategica, :visao_outro, :sustentabilidade, :escala,
        :apoios, :apoio_outro, :areas, :area_outro, :temas, :tema_outro, NOW(), NOW()
    )");
}

$params = [
    'negocio_id'       => $negocio_id,
    'visao_estrategica'=> $visao_estrategica,
    'visao_outro'      => $visao_outro,
    'sustentabilidade' => $sustentabilidade,
    'escala'           => $escala,
    'apoios'           => json_encode($apoios),
    'apoio_outro'      => $apoio_outro,
    'areas'            => json_encode($areas),
    'area_outro'       => $area_outro,
    'temas'            => json_encode($temas),
    'tema_outro'       => $tema_outro
];

$stmt->execute($params);

// ==========================
// Cálculo do Score Escala
// ==========================
$stmt = $pdo->prepare("SELECT componente, peso FROM pesos_scores WHERE tipo_score='ESCALA'");
$stmt->execute();
$pesos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$scoreEscala = 0;
foreach ($pesos as $p) {
    $componente = $p['componente'];
    $peso = (float)$p['peso'];

    // Normaliza respostas para opções do lookup
    switch ($componente) {
        case 'ambicao_geografica':
            if ($escala === 'Escalar internacionalmente (expandir o modelo para fora do país)') $opcao = 'internacional';
            elseif ($escala === 'Escalar nacionalmente (atuar em novas regiões/mercados do Brasil)') $opcao = 'nacional';
            elseif ($escala === 'Escalar localmente (mais profundidade e alcance na mesma região)') $opcao = 'local';
            elseif ($escala === 'Manter o modelo atual como negócio de nicho ou territorial') $opcao = 'regional';
            else $opcao = 'nao_informado';
            break;

        case 'replicabilidade':
            if (in_array('Desenvolvimento de tecnologia ou produto', $areas)) $opcao = 'digital_escalavel';
            elseif (in_array('Expansão comercial e abertura de mercado', $areas)) $opcao = 'baixa_adaptacao';
            else $opcao = 'alta_adaptacao';
            break;

        case 'estrutura_operacional':
            if (in_array('Reforço da estrutura operacional (equipamentos, logística etc.)', $areas)) $opcao = 'time_processos_kpis';
            elseif (in_array('Formação de equipe e qualificação técnica', $areas)) $opcao = 'time_pequeno_organizado';
            else $opcao = 'informal';
            break;

        case 'tecnologia':
            if (in_array('Tecnologia e inovação aplicada ao impacto', $temas)) $opcao = 'propria';
            else $opcao = 'manual';
            break;

        case 'demanda_mercado':
            if ($sustentabilidade && strpos($sustentabilidade, 'Alta sustentabilidade') !== false) $opcao = 'validado_crescente';
            elseif ($sustentabilidade && strpos($sustentabilidade, 'Moderada') !== false) $opcao = 'validado_local';
            else $opcao = 'nicho_limitado';
            break;

        case 'parcerias_estrategicas':
            if (in_array('Parcerias corporativas ou estratégicas', $apoios)) $opcao = 'nacionais_internacionais';
            elseif (in_array('Investimento Anjo', $apoios)) $opcao = 'locais';
            else $opcao = 'nenhuma';
            break;

        case 'internacionalizacao':
            if ($escala === 'Escalar internacionalmente (expandir o modelo para fora do país)') $opcao = 'ja_opera_fora';
            else $opcao = 'sem_intencao';
            break;

        default:
            $opcao = 'nao_informado';
    }

    // Busca valor normalizado
    $stmt2 = $pdo->prepare("SELECT valor FROM lookup_scores WHERE componente=? AND opcao=?");
    $stmt2->execute([$componente, $opcao]);
    $valor = (int)($stmt2->fetchColumn() ?: 0);

    $scoreEscala += $valor * $peso;
}

// Penalidades
$penalty = 0;
if ($escala === 'Escalar localmente (mais profundidade e alcance na mesma região)' && !in_array('Desenvolvimento de tecnologia ou produto', $areas)) {
    $penalty += 10;
}

$scoreEscala = max(0, min(100, round($scoreEscala - $penalty)));

// Salva no banco
$stmt = $pdo->prepare("
    INSERT INTO scores_negocios (negocio_id, score_escala, atualizado_em)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE score_escala=VALUES(score_escala), atualizado_em=NOW()
");
$stmt->execute([$negocio_id, $scoreEscala]);

// --------- Redirecionamento Inteligente ---------
$modo = $_POST['modo'] ?? 'cadastro';

// PRIMEIRO: Busca como o negócio está AGORA no banco
$stmtProgresso = $pdo->prepare("SELECT etapa_atual, inscricao_completa FROM negocios WHERE id = ?");
$stmtProgresso->execute([$negocio_id]);
$progresso = $stmtProgresso->fetch(PDO::FETCH_ASSOC);

if ($modo === 'cadastro') {
    // Modo Cadastro: Atualiza a etapa (neste caso da 2 para a 3) SOMENTE SE ainda não passou por ela
    $etapaAtualNoBanco = (int)($progresso['etapa_atual'] ?? 1);
    
    if ($etapaAtualNoBanco < 9) {
        $stmtUpdate = $pdo->prepare("
            UPDATE negocios 
            SET etapa_atual = 9, updated_at = NOW() 
            WHERE id = ? AND empreendedor_id = ?
        ");
        $stmtUpdate->execute([$negocio_id, $_SESSION['user_id']]);
    }

    // Avança para a PRÓXIMA etapa
    header("Location: /negocios/etapa9_documentacao.php?id=" . $negocio_id);
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
