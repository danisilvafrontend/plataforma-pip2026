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

$errors = []; // Inicializa o array de erros localmente

$negocio_id = (int)($_POST['negocio_id'] ?? 0);
if ($negocio_id === 0) {
    $errors[] = "Negócio inválido.";
    $_SESSION['errors_etapa6'] = $errors;
    header("Location: /negocios/etapa6_financeiro.php?id=" . $negocio_id);
    exit;
}

// Captura dos campos
$estagio_faturamento   = $_POST['estagio_faturamento'] ?? null;
$faixa_faturamento     = $_POST['faixa_faturamento'] ?? null;
$fontes_receita        = $_POST['fontes_receita'] ?? [];
$fonte_outro           = trim($_POST['fonte_outro'] ?? '');
$modelo_monetizacao    = $_POST['modelo_monetizacao'] ?? null;
$margem_bruta          = $_POST['margem_bruta'] ?? null;
$dependencia_proprios  = $_POST['dependencia_proprios'] ?? null;
$previsao_proprios     = $_POST['previsao_proprios'] ?? null;
$previsao_crescimento  = $_POST['previsao_crescimento'] ?? null;
$investimento_externo  = $_POST['investimento_externo'] ?? null;
$prioridade_estrategica = $_POST['prioridade_estrategica'] ?? null;
$pronto_investimento    = $_POST['pronto_investimento'] ?? null;
$faixa_investimento     = $_POST['faixa_investimento'] ?? null;

// Limita fontes de receita a no máximo 3
if (is_array($fontes_receita)) {
    $fontes_receita = array_slice($fontes_receita, 0, 3);
}
$fontesJson = json_encode($fontes_receita);

// ===== Validações extras =====

// Se "Outro (especificar)" foi marcado, fonte_outro é obrigatório
if (in_array("Outro (especificar)", $fontes_receita)) {
    if ($fonte_outro === '') {
        $errors[] = "Você marcou 'Outro', mas não especificou a fonte.";
    } elseif (mb_strlen($fonte_outro) > 120) {
        $errors[] = "A fonte 'Outro' deve ter no máximo 120 caracteres.";
    }
}

// Se dependencia_proprios = "Não", previsao_proprios é obrigatório
if ($dependencia_proprios === "Não" && empty($previsao_proprios)) {
    $errors[] = "Se mais de 50% não vem de próprios, é necessário informar a previsão.";
}

function textoValido($texto) {
    $texto = trim($texto);
    // Pelo menos 5 letras no total, não precisa ser consecutivas
    return preg_match_all('/[a-zA-ZÀ-ÿ]/', $texto) >= 5;
}

// Validações de texto
if ($fonte_outro && !textoValido($fonte_outro)) {
    $errors[] = "O campo 'Outro' em Fontes de Receita deve conter texto válido.";
}

if ($modelo_monetizacao && !textoValido($modelo_monetizacao)) {
    $errors[] = "O campo 'Modelo de Monetização' deve conter texto válido.";
}

// Se houver erros, salva na sessão e volta para a etapa
if (!empty($errors)) {
    $_SESSION['errors_etapa6'] = $errors;
    if (($_POST['modo'] ?? 'cadastro') === 'editar') {
        header("Location: /negocios/editar_etapa6.php?id=" . $negocio_id);
    } else {
        header("Location: /negocios/etapa6_financeiro.php?id=" . $negocio_id);
    }
    exit;
}

// Se chegou aqui, não tem erros. Limpa a sessão de erros antigos para garantir
unset($_SESSION['errors_etapa6']);

// Insert/Update
$stmt = $pdo->prepare("
    INSERT INTO negocio_financeiro (
    negocio_id, estagio_faturamento, faixa_faturamento,
    fontes_receita, fonte_outro, modelo_monetizacao,
    margem_bruta, dependencia_proprios, previsao_proprios,
    previsao_crescimento, investimento_externo,
    prioridade_estrategica, pronto_investimento, faixa_investimento,
    criado_em, atualizado_em
) VALUES (
    :negocio_id, :estagio_faturamento, :faixa_faturamento,
    :fontes_receita, :fonte_outro, :modelo_monetizacao,
    :margem_bruta, :dependencia_proprios, :previsao_proprios,
    :previsao_crescimento, :investimento_externo,
    :prioridade_estrategica, :pronto_investimento, :faixa_investimento,
    NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    estagio_faturamento = VALUES(estagio_faturamento),
    faixa_faturamento = VALUES(faixa_faturamento),
    fontes_receita = VALUES(fontes_receita),
    fonte_outro = VALUES(fonte_outro),
    modelo_monetizacao = VALUES(modelo_monetizacao),
    margem_bruta = VALUES(margem_bruta),
    dependencia_proprios = VALUES(dependencia_proprios),
    previsao_proprios = VALUES(previsao_proprios),
    previsao_crescimento = VALUES(previsao_crescimento),
    investimento_externo = VALUES(investimento_externo),
    prioridade_estrategica = VALUES(prioridade_estrategica),
    pronto_investimento = VALUES(pronto_investimento),
    faixa_investimento = VALUES(faixa_investimento),
    atualizado_em = NOW()
");

$params = [
   'negocio_id'            => $negocio_id,
    'estagio_faturamento'   => $estagio_faturamento,
    'faixa_faturamento'     => $faixa_faturamento,
    'fontes_receita'        => $fontesJson,
    'fonte_outro'           => $fonte_outro,
    'modelo_monetizacao'    => $modelo_monetizacao,
    'margem_bruta'          => $margem_bruta,
    'dependencia_proprios'  => $dependencia_proprios,
    'previsao_proprios'     => $previsao_proprios,
    'previsao_crescimento'  => $previsao_crescimento,
    'investimento_externo'  => $investimento_externo,
    'prioridade_estrategica'=> $prioridade_estrategica,
    'pronto_investimento'   => $pronto_investimento,
    'faixa_investimento'    => $faixa_investimento
];

$stmt->execute($params);

// ==========================
// Cálculo do Score Investimento
// ==========================
$stmt = $pdo->prepare("SELECT componente, peso FROM pesos_scores WHERE tipo_score='INVESTIMENTO'");
$stmt->execute();
$pesos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$scoreInvestimento = 0;
foreach ($pesos as $p) {
    $componente = $p['componente'];
    $peso = (float)$p['peso'];

    // Normaliza respostas para opções do lookup
    switch ($componente) {
        case 'estagio':
            // Usa estagio_faturamento para mapear
            if (strpos($estagio_faturamento, 'sem faturamento') !== false) $opcao = 'ideacao';
            elseif (strpos($estagio_faturamento, 'break-even') !== false) $opcao = 'operacao';
            elseif (strpos($estagio_faturamento, 'lucro') !== false) $opcao = 'escala';
            else $opcao = 'tracao';
            break;

        case 'receita':
            if ($faixa_faturamento === 'Não houve faturamento ainda') $opcao = 'sem_receita';
            elseif ($faixa_faturamento === 'Até R$ 100 mil') $opcao = 'ate_100k';
            elseif ($faixa_faturamento === 'R$ 100 mil – R$ 500 mil') $opcao = '100k_500k';
            elseif ($faixa_faturamento === 'R$ 500 mil – R$ 1 milhão') $opcao = '500k_1m';
            elseif (strpos($faixa_faturamento, 'Acima de 20 milhões') !== false) $opcao = 'acima_1m'; // ajuste conforme lookup
            else $opcao = 'nao_informado';
            break;

        case 'margem_bruta':
            if ($margem_bruta === 'Acima de 60%') $opcao = 'acima_60';
            elseif ($margem_bruta === 'Entre 40% e 60%') $opcao = '40_60';
            elseif ($margem_bruta === 'Entre 20% e 40%') $opcao = '20_40';
            elseif ($margem_bruta === 'Menor que 20%') $opcao = 'abaixo_20';
            elseif ($margem_bruta === 'Ainda não mensurada') $opcao = 'nao_informado';
            else $opcao = 'nao_informado';
            break;

        case 'crescimento':
            if ($previsao_crescimento === 'Crescimento acima de 100%') $opcao = 'acima_50';
            elseif ($previsao_crescimento === 'Crescimento entre 50% e 100%') $opcao = '20_50';
            elseif ($previsao_crescimento === 'Crescimento de até 50%') $opcao = 'abaixo_20';
            elseif ($previsao_crescimento === 'Estável ou retração esperada') $opcao = 'estagnado';
            else $opcao = 'nao_informado';
            break;

        case 'modelo_receita':
            if (in_array('Venda direta recorrente (assinaturas, mensalidades)', $fontes_receita)) $opcao = 'recorrente_assinatura_contrato';
            elseif (in_array('Venda direta única (produto ou serviço)', $fontes_receita)) $opcao = 'transacional_esporadico';
            elseif (in_array('Consultoria / mentoria / treinamento', $fontes_receita)) $opcao = 'hibrido';
            elseif (in_array('Modelo ainda não definido', $fontes_receita)) $opcao = 'nao_informado';
            else $opcao = 'b2b_estruturado'; // exemplo
            break;

        case 'captacao_previa':
            if ($investimento_externo === 'Sim, Série A ou superior') $opcao = 'vc_seed_serie';
            elseif ($investimento_externo === 'Sim, pré-seed / seed') $opcao = 'vc_seed_serie';
            elseif ($investimento_externo === 'Sim, investimento anjo') $opcao = 'anjo';
            elseif ($investimento_externo === 'Apenas recursos próprios (bootstrapping)') $opcao = 'bootstrapping';
            elseif ($investimento_externo === 'Não') $opcao = 'nunca_captou';
            elseif ($investimento_externo === 'Doações') $opcao = 'grants_donations_primary';
            else $opcao = 'nao_informado';
            break;

        case 'governanca':
            // Aqui você pode usar dependencia_proprios + etapa 1 (CNPJ/CPF) para inferir
            $opcao = 'formalizada_parcial'; // exemplo
            break;

        default:
            $opcao = 'nao_informado';
    }

    // Busca valor normalizado
    $stmt2 = $pdo->prepare("SELECT valor FROM lookup_scores WHERE componente=? AND opcao=?");
    $stmt2->execute([$componente, $opcao]);
    $valor = (int)($stmt2->fetchColumn() ?: 0);

    $scoreInvestimento += $valor * $peso;
}

// Penalidades
$penalty = 0;
if ($estagio_faturamento && strpos($estagio_faturamento, 'sem faturamento') !== false && $faixa_faturamento === 'Não houve faturamento ainda') {
    $penalty += 10;
}
if ($margem_bruta === 'Menor que 20%' && $faixa_faturamento === 'R$ 500 mil – R$ 1 milhão') {
    $penalty += 5;
}

$scoreInvestimento = max(0, min(100, round($scoreInvestimento - $penalty)));

// Salva no banco
$stmt = $pdo->prepare("
    INSERT INTO scores_negocios (negocio_id, score_investimento, atualizado_em)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE score_investimento=VALUES(score_investimento), atualizado_em=NOW()
");
$stmt->execute([$negocio_id, $scoreInvestimento]);

$modo = $_POST['modo'] ?? 'cadastro';

if ($modo === 'cadastro') {
    // Atualiza etapa e vai para etapa 7
    $stmt = $pdo->prepare("UPDATE negocios 
        SET etapa_atual = 7, updated_at = NOW() 
        WHERE id = ? AND empreendedor_id = ?");
    $stmt->execute([$negocio_id, $_SESSION['user_id']]);

    header("Location: /negocios/etapa7_impacto.php?id=" . $negocio_id);
    exit;
} else {
    // Edição: volta para Meus Negócios
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}
