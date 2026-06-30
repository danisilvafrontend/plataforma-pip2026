<?php
session_start();

require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$pdo = getPDO();
$user_id = $_SESSION['user_id'];
$negocio_id = (int) ($_POST['negocio_id'] ?? 0);

if (!$negocio_id) {
    die('Negócio inválido.');
}

$stmt = $pdo->prepare("SELECT id FROM negocios WHERE id = :id AND user_id = :user_id LIMIT 1");
$stmt->execute([
    'id' => $negocio_id,
    'user_id' => $user_id,
]);

if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    die('Acesso negado.');
}

$errors = [];

// Captura dos campos
$estagio_faturamento   = $_POST['estagio_faturamento'] ?? null;
$faixa_faturamento     = $_POST['faixa_faturamento'] ?? null;
$validacao_mercado     = $_POST['validacao_mercado'] ?? null;

$opcoes_validacao = ['demanda_crescente_validada', 'demanda_validada_local', 'demanda_em_validacao', 'demanda_nao_testada'];
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

if (is_array($fontes_receita)) {
    $fontes_receita = array_slice($fontes_receita, 0, 3);
}
$fontesJson = json_encode($fontes_receita);

if (in_array("Outro (especificar)", $fontes_receita)) {
    if ($fonte_outro === '') {
        $errors[] = "Você marcou 'Outro', mas não especificou a fonte.";
    } elseif (mb_strlen($fonte_outro) > 120) {
        $errors[] = "A fonte 'Outro' deve ter no máximo 120 caracteres.";
    }
}

if ($dependencia_proprios === "Não" && empty($previsao_proprios)) {
    $errors[] = "Se mais de 50% não vem de próprios, é necessário informar a previsão.";
}

function textoValido($texto) {
    $texto = trim($texto);
    return preg_match_all('/[a-zA-ZÀ-ÿ]/', $texto) >= 5;
}

if ($fonte_outro && !textoValido($fonte_outro)) {
    $errors[] = "O campo 'Outro' em Fontes de Receita deve conter texto válido.";
}

if ($modelo_monetizacao && !textoValido($modelo_monetizacao)) {
    $errors[] = "O campo 'Modelo de Monetização' deve conter texto válido.";
}

if (empty($estagio_faturamento)) {
    $errors[] = "Informe o estágio de faturamento.";
}
if (empty($faixa_faturamento)) {
    $errors[] = "Informe a faixa de faturamento.";
}
if (empty($validacao_mercado) || !in_array($validacao_mercado, $opcoes_validacao)) {
    $errors[] = "Informe como você avalia a demanda pelo seu produto ou serviço.";
}
if (empty($fontes_receita)) {
    $errors[] = "Selecione pelo menos uma fonte de receita.";
}
if (empty($margem_bruta)) {
    $errors[] = "Informe a margem bruta.";
}
if (empty($dependencia_proprios)) {
    $errors[] = "Informe a dependência de recursos próprios.";
}
if (empty($previsao_crescimento)) {
    $errors[] = "Informe a previsão de crescimento.";
}
if (empty($investimento_externo)) {
    $errors[] = "Informe se há investimento externo.";
}
if (empty($prioridade_estrategica)) {
    $errors[] = "Informe a prioridade estratégica nos próximos 6 meses.";
}
if (empty($pronto_investimento)) {
    $errors[] = "Informe se está pronto para receber investimento ou parceria.";
}
if (empty($faixa_investimento)) {
    $errors[] = "Informe a faixa de investimento ou apoio buscada.";
}

if (!empty($errors)) {
    $_SESSION['errors_etapa6'] = $errors;
    header("Location: /negocios/etapa6_financeiro.php?id=" . $negocio_id);
    exit;
}

unset($_SESSION['errors_etapa6']);

$stmt = $pdo->prepare("
    INSERT INTO negocio_financeiro (
    negocio_id, estagio_faturamento, faixa_faturamento, validacao_mercado,
    fontes_receita, fonte_outro, modelo_monetizacao,
    margem_bruta, dependencia_proprios, previsao_proprios,
    previsao_crescimento, investimento_externo,
    prioridade_estrategica, pronto_investimento, faixa_investimento,
    criado_em, atualizado_em
) VALUES (
    :negocio_id, :estagio_faturamento, :faixa_faturamento, :validacao_mercado,
    :fontes_receita, :fonte_outro, :modelo_monetizacao,
    :margem_bruta, :dependencia_proprios, :previsao_proprios,
    :previsao_crescimento, :investimento_externo,
    :prioridade_estrategica, :pronto_investimento, :faixa_investimento,
    NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    estagio_faturamento = VALUES(estagio_faturamento),
    faixa_faturamento = VALUES(faixa_faturamento),
    validacao_mercado = VALUES(validacao_mercado),
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
   'validacao_mercado'     => $validacao_mercado,
   'fontes_receita'        => $fontesJson,
   'fonte_outro'           => $fonte_outro ?: null,
   'modelo_monetizacao'    => $modelo_monetizacao,
   'margem_bruta'          => $margem_bruta,
   'dependencia_proprios'  => $dependencia_proprios,
   'previsao_proprios'     => $previsao_proprios ?: null,
   'previsao_crescimento'  => $previsao_crescimento,
   'investimento_externo'  => $investimento_externo,
   'prioridade_estrategica'=> $prioridade_estrategica,
   'pronto_investimento'   => $pronto_investimento,
   'faixa_investimento'    => $faixa_investimento,
];

try {
    $stmt->execute($params);

    try {
        $stmtScore = $pdo->prepare("
            INSERT INTO scores_negocios (negocio_id, score_investimento, atualizado_em)
            VALUES (:negocio_id, 0, NOW())
            ON DUPLICATE KEY UPDATE score_investimento=VALUES(score_investimento), atualizado_em=NOW()
        ");
        $stmtScore->execute(['negocio_id' => $negocio_id]);
    } catch (Throwable $e) {
        // opcional
    }

    $stmt = $pdo->prepare("UPDATE negocios SET etapa_atual = 7, updated_at = NOW() WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        'id' => $negocio_id,
        'user_id' => $user_id,
    ]);

    header("Location: /negocios/etapa7_impacto.php?id=" . $negocio_id);
    exit;
} catch (Throwable $e) {
    $_SESSION['errors_etapa6'] = ['Erro ao salvar os dados da etapa 6.'];
    header("Location: /negocios/etapa6_financeiro.php?id=" . $negocio_id);
    exit;
}
