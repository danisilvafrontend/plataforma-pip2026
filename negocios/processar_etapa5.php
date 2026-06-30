<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/upload_helper.php';

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

$stmt = $pdo->prepare("SELECT id FROM negocios WHERE id = :id AND empreendedor_id = :user_id LIMIT 1");
$stmt->execute([
    'id' => $negocio_id,
    'user_id' => $user_id
]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$negocio) {
    die('Acesso negado.');
}

function textoValido($texto) {
    $texto = trim((string) $texto);
    return preg_match_all('/[a-zA-ZÀ-ÿ]/u', $texto) >= 5;
}

$_SESSION['errors_etapa5'] = [];

// Uploads
$logoUrl = null;
$imagemDestaqueUrl = null;
$pdfUrl = null;
$galeriaAtual = [];

$stmt = $pdo->prepare("SELECT logo_negocio, imagem_destaque, apresentacao_pdf, galeria_imagens FROM negocio_apresentacao WHERE negocio_id = :id LIMIT 1");
$stmt->execute(['id' => $negocio_id]);
$existente = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$logoUrl = $existente['logo_negocio'] ?? null;
$imagemDestaqueUrl = $existente['imagem_destaque'] ?? null;
$pdfUrl = $existente['apresentacao_pdf'] ?? null;

if (!empty($existente['galeria_imagens'])) {
    $decoded = json_decode($existente['galeria_imagens'], true);
    if (is_array($decoded)) {
        $galeriaAtual = $decoded;
    }
}

try {
    if (!empty($_FILES['logo_negocio']['name'])) {
        $logoUrl = uploadArquivo($_FILES['logo_negocio'], 'negocios/logos');
    }
    if (!empty($_FILES['imagem_destaque']['name'])) {
        $imagemDestaqueUrl = uploadArquivo($_FILES['imagem_destaque'], 'negocios/destaques');
    }
    if (!empty($_FILES['apresentacao_pdf']['name'])) {
        $pdfUrl = uploadArquivo($_FILES['apresentacao_pdf'], 'negocios/pdfs');
    }

    if (!empty($_FILES['galeria_imagens']['name'][0])) {
        foreach ($_FILES['galeria_imagens']['name'] as $i => $nome) {
            if (!$nome) continue;
            $file = [
                'name' => $_FILES['galeria_imagens']['name'][$i],
                'type' => $_FILES['galeria_imagens']['type'][$i],
                'tmp_name' => $_FILES['galeria_imagens']['tmp_name'][$i],
                'error' => $_FILES['galeria_imagens']['error'][$i],
                'size' => $_FILES['galeria_imagens']['size'][$i],
            ];
            $galeriaAtual[] = uploadArquivo($file, 'negocios/galeria');
        }
    }
} catch (Throwable $e) {
    $_SESSION['errors_etapa5'][] = 'Erro ao fazer upload de arquivos.';
}

// Campos de texto
$frase_negocio          = trim($_POST['frase_negocio'] ?? '');
$problema_resolvido     = trim($_POST['problema_resolvido'] ?? '');
$solucao_oferecida      = trim($_POST['solucao_oferecida'] ?? '');
$video_pitch_url        = trim($_POST['video_pitch_url'] ?? '');
$apresentacao_video     = trim($_POST['apresentacao_video_url'] ?? '');
$descricao_inovacao     = trim($_POST['descricao_inovacao'] ?? '');
$tipo_solucao           = $_POST['tipo_solucao'] ?? null;
$modelo_negocio         = trim($_POST['modelo_negocio'] ?? '');
$colaboradores          = $_POST['colaboradores'] !== '' ? (int) $_POST['colaboradores'] : null;
$replicabilidade        = $_POST['replicabilidade'] ?? null;
$nivel_tecnologia       = $_POST['nivel_tecnologia'] ?? null;

$opcoesReplicabilidade = ['digital_escalavel', 'replicavel_baixa_adaptacao', 'replicavel_alta_adaptacao', 'dificil_replicacao'];
$opcoes_nivel_tec = ['tecnologia_propria', 'tecnologia_adaptada', 'modelo_manual'];

$apoio                  = $_POST['apoio'] ?? 'nao';
$programas              = trim($_POST['programas'] ?? '');
$info_adicionais        = trim($_POST['info_adicionais'] ?? '');

// Aceita links tanto como array (editar_etapa5: name="info_adicionais_link[]")
// quanto como string textarea com quebras de linha (etapa5_apresentacao: name="info_adicionais_links")
if (!empty($_POST['info_adicionais_link']) && is_array($_POST['info_adicionais_link'])) {
    $linksArray = array_filter(array_map('trim', $_POST['info_adicionais_link']));
} else {
    $linksRaw   = trim($_POST['info_adicionais_links'] ?? '');
    $linksArray = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $linksRaw)));
}
$linksJson = json_encode(array_values($linksArray), JSON_UNESCAPED_UNICODE);

$desafios = $_POST['desafios'] ?? [];
if (!is_array($desafios)) $desafios = [];
$desafios = array_values(array_unique(array_slice($desafios, 0, 10)));

$inovacao_tecnologica   = ($_POST['inovacao_tecnologica']   ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_produto       = ($_POST['inovacao_produto']       ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_servico       = ($_POST['inovacao_servico']       ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_modelo        = ($_POST['inovacao_modelo']        ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_social        = ($_POST['inovacao_social']        ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_ambiental     = ($_POST['inovacao_ambiental']     ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_cadeia_valor  = ($_POST['inovacao_cadeia_valor']  ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_governanca    = ($_POST['inovacao_governanca']    ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_impacto       = ($_POST['inovacao_impacto']       ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_financiamento = ($_POST['inovacao_financiamento'] ?? 'nao') === 'sim' ? 1 : 0;

$inovacao = (
    $inovacao_tecnologica || $inovacao_produto    || $inovacao_servico  ||
    $inovacao_modelo      || $inovacao_social     || $inovacao_ambiental ||
    $inovacao_cadeia_valor|| $inovacao_governanca || $inovacao_impacto   ||
    $inovacao_financiamento
) ? 'sim' : 'nao';

// Validações
if ($frase_negocio === '' || !textoValido($frase_negocio)) {
    $_SESSION['errors_etapa5'][] = "Informe uma frase de apresentação válida.";
}
if ($problema_resolvido === '' || !textoValido($problema_resolvido)) {
    $_SESSION['errors_etapa5'][] = "Descreva validamente o problema que o negócio resolve.";
}
if ($solucao_oferecida === '' || !textoValido($solucao_oferecida)) {
    $_SESSION['errors_etapa5'][] = "Descreva validamente como sua solução funciona.";
}
if ($video_pitch_url && !filter_var($video_pitch_url, FILTER_VALIDATE_URL)) {
    $_SESSION['errors_etapa5'][] = "A URL do vídeo pitch é inválida.";
}
if ($apresentacao_video && !filter_var($apresentacao_video, FILTER_VALIDATE_URL)) {
    $_SESSION['errors_etapa5'][] = "A URL do vídeo adicional é inválida.";
}
if ($descricao_inovacao && !textoValido($descricao_inovacao)) {
    $_SESSION['errors_etapa5'][] = "A descrição da inovação deve conter texto válido.";
}
if (empty($tipo_solucao)) {
    $_SESSION['errors_etapa5'][] = "Informe o tipo de solução oferecida.";
}
if ($modelo_negocio === '' || !textoValido($modelo_negocio)) {
    $_SESSION['errors_etapa5'][] = "Informe validamente o modelo de negócio.";
}
if ($colaboradores !== null && ($colaboradores < 0 || $colaboradores > 9999)) {
    $_SESSION['errors_etapa5'][] = "Número de colaboradores inválido.";
}
if (empty($replicabilidade) || !in_array($replicabilidade, $opcoesReplicabilidade)) {
    $_SESSION['errors_etapa5'][] = "Informe a replicabilidade do modelo de negócio.";
}
if (empty($nivel_tecnologia) || !in_array($nivel_tecnologia, $opcoes_nivel_tec)) {
    $_SESSION['errors_etapa5'][] = "Informe o papel da tecnologia no seu modelo de negócio.";
}
if (!in_array($apoio, ['sim', 'nao'], true)) {
    $_SESSION['errors_etapa5'][] = "Informe corretamente se houve apoio de aceleradora ou programa.";
}
if ($apoio === 'sim' && $programas !== '' && !textoValido($programas)) {
    $_SESSION['errors_etapa5'][] = "Informe validamente os programas ou aceleradoras.";
}
if ($info_adicionais && !textoValido($info_adicionais)) {
    $_SESSION['errors_etapa5'][] = "As informações adicionais devem conter texto válido.";
}

foreach ($linksArray as $link) {
    if (!filter_var($link, FILTER_VALIDATE_URL)) {
        $_SESSION['errors_etapa5'][] = "Há link adicional inválido informado.";
        break;
    }
}

if (!empty($_SESSION['errors_etapa5'])) {
    $modo = $_POST['modo'] ?? 'cadastro';
    if ($modo === 'editar') {
        header("Location: /negocios/editar_etapa5.php?id=" . $negocio_id);
    } else {
        header("Location: /negocios/etapa5_apresentacao.php?id=" . $negocio_id);
    }
    exit;
}

$desafiosJson = json_encode($desafios, JSON_UNESCAPED_UNICODE);
$desafiosCols = "desafios";
$desafiosVals = ":desafios";
$desafiosUpdate = "desafios = VALUES(desafios)";
$valoresDesafios = ['desafios' => $desafiosJson];

$sql = "
    INSERT INTO negocio_apresentacao (
        negocio_id, logo_negocio, imagem_destaque, frase_negocio, problema_resolvido, solucao_oferecida,
        video_pitch_url, apresentacao_pdf, apresentacao_video_url,
        galeria_imagens, inovacao, descricao_inovacao,
        inovacao_tecnologica, inovacao_produto, inovacao_servico, inovacao_modelo,
        inovacao_social, inovacao_ambiental, inovacao_cadeia_valor,
        inovacao_governanca, inovacao_impacto, inovacao_financiamento,
        tipo_solucao, modelo_negocio, colaboradores, replicabilidade, nivel_tecnologia,
        apoio, programas,
        $desafiosCols,
        info_adicionais, info_adicionais_links,
        criado_em, atualizado_em
    ) VALUES (
        :negocio_id, :logo, :imagem_destaque, :frase, :problema_resolvido, :solucao_oferecida,
        :video_pitch, :pdf, :video_inst,
        :galeria, :inovacao, :desc_inovacao,
        :inovacao_tecnologica, :inovacao_produto, :inovacao_servico, :inovacao_modelo,
        :inovacao_social, :inovacao_ambiental, :inovacao_cadeia_valor,
        :inovacao_governanca, :inovacao_impacto, :inovacao_financiamento,
        :tipo_solucao, :modelo_negocio, :colaboradores, :replicabilidade, :nivel_tecnologia,
        :apoio, :programas,
        $desafiosVals,
        :info_adicionais, :links,
        NOW(), NOW()
    )
    ON DUPLICATE KEY UPDATE
        logo_negocio            = VALUES(logo_negocio),
        imagem_destaque         = VALUES(imagem_destaque),
        frase_negocio           = VALUES(frase_negocio),
        problema_resolvido      = VALUES(problema_resolvido),
        solucao_oferecida       = VALUES(solucao_oferecida),
        video_pitch_url         = VALUES(video_pitch_url),
        apresentacao_pdf        = VALUES(apresentacao_pdf),
        apresentacao_video_url  = VALUES(apresentacao_video_url),
        galeria_imagens         = VALUES(galeria_imagens),
        inovacao                = VALUES(inovacao),
        descricao_inovacao      = VALUES(descricao_inovacao),
        inovacao_tecnologica    = VALUES(inovacao_tecnologica),
        inovacao_produto        = VALUES(inovacao_produto),
        inovacao_servico        = VALUES(inovacao_servico),
        inovacao_modelo         = VALUES(inovacao_modelo),
        inovacao_social         = VALUES(inovacao_social),
        inovacao_ambiental      = VALUES(inovacao_ambiental),
        inovacao_cadeia_valor   = VALUES(inovacao_cadeia_valor),
        inovacao_governanca     = VALUES(inovacao_governanca),
        inovacao_impacto        = VALUES(inovacao_impacto),
        inovacao_financiamento  = VALUES(inovacao_financiamento),
        tipo_solucao            = VALUES(tipo_solucao),
        modelo_negocio          = VALUES(modelo_negocio),
        colaboradores           = VALUES(colaboradores),
        replicabilidade         = VALUES(replicabilidade),
        nivel_tecnologia        = VALUES(nivel_tecnologia),
        apoio                   = VALUES(apoio),
        programas               = VALUES(programas),
        $desafiosUpdate,
        info_adicionais         = VALUES(info_adicionais),
        info_adicionais_links   = VALUES(info_adicionais_links),
        atualizado_em           = NOW()
";

$stmt = $pdo->prepare($sql);

$params = [
    'negocio_id'             => $negocio_id,
    'logo'                   => $logoUrl,
    'imagem_destaque'        => $imagemDestaqueUrl,
    'frase'                  => $frase_negocio,
    'problema_resolvido'     => $problema_resolvido,
    'solucao_oferecida'      => $solucao_oferecida,
    'video_pitch'            => $video_pitch_url ?: null,
    'pdf'                    => $pdfUrl,
    'video_inst'             => $apresentacao_video,
    'galeria'                => json_encode($galeriaAtual),
    'inovacao'               => $inovacao,
    'desc_inovacao'          => $descricao_inovacao ?: null,
    'inovacao_tecnologica'   => $inovacao_tecnologica,
    'inovacao_produto'       => $inovacao_produto,
    'inovacao_servico'       => $inovacao_servico,
    'inovacao_modelo'        => $inovacao_modelo,
    'inovacao_social'        => $inovacao_social,
    'inovacao_ambiental'     => $inovacao_ambiental,
    'inovacao_cadeia_valor'  => $inovacao_cadeia_valor,
    'inovacao_governanca'    => $inovacao_governanca,
    'inovacao_impacto'       => $inovacao_impacto,
    'inovacao_financiamento' => $inovacao_financiamento,
    'tipo_solucao'           => $tipo_solucao,
    'modelo_negocio'         => $modelo_negocio,
    'colaboradores'          => $colaboradores,
    'replicabilidade'        => $replicabilidade,
    'nivel_tecnologia'       => $nivel_tecnologia,
    'apoio'                  => $apoio,
    'programas'              => $programas ?: null,
    'info_adicionais'        => $info_adicionais ?: null,
    'links'                  => $linksJson,
];

foreach ($valoresDesafios as $campo => $valor) {
    $params[$campo] = $valor;
}

try {
    $stmt->execute($params);

    try {
        $stmtScore = $pdo->prepare("
            INSERT INTO scores_negocios (negocio_id, score_impacto, atualizado_em)
            VALUES (:negocio_id, 0, NOW())
            ON DUPLICATE KEY UPDATE score_impacto = VALUES(score_impacto), atualizado_em = NOW()
        ");
        $stmtScore->execute(['negocio_id' => $negocio_id]);
    } catch (Throwable $e) {
        // score opcional
    }

    $stmt = $pdo->prepare("
        UPDATE negocios 
        SET etapa_atual = 6, updated_at = NOW() 
        WHERE id = :id AND empreendedor_id = :user_id
    ");
    $stmt->execute([
        'id' => $negocio_id,
        'user_id' => $user_id
    ]);

    $modo = $_POST['modo'] ?? 'cadastro';
    if ($modo === 'editar') {
        header("Location: /negocios/editar_etapa5.php?id=" . $negocio_id);
    } else {
        header("Location: /negocios/etapa6_financeiro.php?id=" . $negocio_id);
    }
    exit;
} catch (Throwable $e) {
    $_SESSION['errors_etapa5'][] = 'Erro ao salvar os dados da etapa 5.';
    $modo = $_POST['modo'] ?? 'cadastro';
    if ($modo === 'editar') {
        header("Location: /negocios/editar_etapa5.php?id=" . $negocio_id);
    } else {
        header("Location: /negocios/etapa5_apresentacao.php?id=" . $negocio_id);
    }
    exit;
}
