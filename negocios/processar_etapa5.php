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
    $_SESSION['errors_etapa5'][] = "Negócio inválido.";
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}
if (!isset($_SESSION['errors_etapa5'])) {
    $_SESSION['errors_etapa5'] = [];
}
// Busca dados atuais da apresentação
$stmt = $pdo->prepare("SELECT * FROM negocio_apresentacao WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$apresentacao = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// ====== Upload Logotipo ======
$logoUrl = $apresentacao['logo_negocio'] ?? null;

if (!empty($_POST['remover_logo'])) {
    $logoUrl = null;
}

if (!empty($_FILES['logo_negocio']['name'])) {
    $fileTmp  = $_FILES['logo_negocio']['tmp_name'];
    $fileSize = $_FILES['logo_negocio']['size'];
    $fileType = mime_content_type($fileTmp);

    if (in_array($fileType, ['image/png','image/jpeg','image/jpg','image/webp']) 
    && $fileSize <= 50 * 1024 * 1024) {
        $logoName = uniqid('logo_') . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES['logo_negocio']['name']);
        $targetLogo = __DIR__ . '/../uploads/negocios/logos/' . $logoName;
        if (move_uploaded_file($fileTmp, $targetLogo)) {
            $logoUrl = '/uploads/negocios/logos/' . $logoName;
        }
    }
}
// ====== Upload Imagem de Destaque (Capa Vitrine) ======
$imagemDestaqueUrl = $apresentacao['imagem_destaque'] ?? null;

if (!empty($_POST['remover_imagem_destaque'])) {
    $imagemDestaqueUrl = null;
}

if (!empty($_FILES['imagem_destaque']['name'])) {
    $fileTmp  = $_FILES['imagem_destaque']['tmp_name'];
    $fileSize = $_FILES['imagem_destaque']['size'];
    $fileType = mime_content_type($fileTmp);

    if (in_array($fileType, ['image/png','image/jpeg','image/jpg','image/webp'])
        && $fileSize <= 5 * 1024 * 1024) {
        $destName   = uniqid('capa_') . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES['imagem_destaque']['name']);
        $targetDest = __DIR__ . '/../uploads/negocios/capas/' . $destName;
        if (!is_dir(__DIR__ . '/../uploads/negocios/capas/')) {
            mkdir(__DIR__ . '/../uploads/negocios/capas/', 0755, true);
        }
        if (move_uploaded_file($fileTmp, $targetDest)) {
            $imagemDestaqueUrl = '/uploads/negocios/capas/' . $destName;
        }
    } else {
        $_SESSION['errors_etapa5'][] = "A imagem de destaque deve ser JPG, PNG ou WebP e ter no máximo 5MB.";
    }
}
// ====== Upload PDF ======
$pdfUrl = $apresentacao['apresentacao_pdf'] ?? null;

if (!empty($_POST['remover_pdf'])) {
    $pdfUrl = null;
}

if (!empty($_FILES['apresentacao_pdf']['name'])) {
    $fileTmp  = $_FILES['apresentacao_pdf']['tmp_name'];
    $fileSize = $_FILES['apresentacao_pdf']['size'];
    $fileType = mime_content_type($fileTmp);

    if (strpos($fileType, 'pdf') === false) {
        $_SESSION['errors_etapa5'][] = "O arquivo enviado não é um PDF válido.";
    } elseif ($fileSize > 5 * 1024 * 1024) {
        $_SESSION['errors_etapa5'][] = "O PDF excede 5MB.";
    } else {
        $pdfName = uniqid('pdf_') . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES['apresentacao_pdf']['name']);
        $targetPdf = __DIR__ . '/../uploads/negocios/documentos/' . $pdfName;
        if (move_uploaded_file($fileTmp, $targetPdf)) {
            $pdfUrl = '/uploads/negocios/documentos/' . $pdfName;
        }
    }
}

// ====== Galeria de imagens ======

// Carrega galeria atual do banco (se existir)
$galeriaAtual = json_decode($apresentacao['galeria_imagens'] ?? '[]', true);
if (!is_array($galeriaAtual)) {
    $galeriaAtual = [];
}

// Remover imagens selecionadas
if (!empty($_POST['remover_imagem'])) {
    foreach ($_POST['remover_imagem'] as $index) {
        unset($galeriaAtual[$index]);
    }
    $galeriaAtual = array_values($galeriaAtual); // reindexa
}

// Substituir imagens específicas
if (!empty($_FILES['substituir_imagem']['name'])) {
    foreach ($_FILES['substituir_imagem']['name'] as $index => $name) {
        if (!empty($name)) {
            $fileTmp  = $_FILES['substituir_imagem']['tmp_name'][$index];
            $fileSize = $_FILES['substituir_imagem']['size'][$index];
            $fileType = mime_content_type($fileTmp);

            if (in_array($fileType, ['image/png','image/jpeg','image/jpg']) && $fileSize <= 50*1024*1024) {
                $imgName = uniqid('img_') . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $name);
                $targetImg = __DIR__ . '/../uploads/negocios/galeria/' . $imgName;
                if (move_uploaded_file($fileTmp, $targetImg)) {
                    $galeriaAtual[$index] = '/uploads/negocios/galeria/' . $imgName;
                }
            }
        }
    }
}

// Adicionar novas imagens (sem ultrapassar 10 no total)
if (!empty($_FILES['galeria_imagens']['name'][0])) {
    foreach ($_FILES['galeria_imagens']['name'] as $index => $name) {
        if (count($galeriaAtual) >= 10) break; // não exceder 10
        $fileTmp  = $_FILES['galeria_imagens']['tmp_name'][$index];
        $fileSize = $_FILES['galeria_imagens']['size'][$index];
        $fileType = mime_content_type($fileTmp);

        if (in_array($fileType, ['image/png','image/jpeg','image/jpg']) && $fileSize <= 50*1024*1024) {
            $imgName = uniqid('img_') . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $name);
            $targetImg = __DIR__ . '/../uploads/negocios/galeria/' . $imgName;
            if (move_uploaded_file($fileTmp, $targetImg)) {
                $galeriaAtual[] = '/uploads/negocios/galeria/' . $imgName;
            }
        }
    }
}

// Atualiza parâmetro para salvar no banco
$params['galeria'] = json_encode($galeriaAtual);

// ====== Captura dos campos ======
$frase_negocio       = $_POST['frase_negocio'] ?? null;
$problema_resolvido  = $_POST['problema_resolvido'] ?? null;
$solucao_oferecida   = $_POST['solucao_oferecida'] ?? null;
$video_pitch_url     = $_POST['video_pitch_url'] ?? null;
$apresentacao_video  = $_POST['apresentacao_video_url'] ?? null;
$inovacao            = $_POST['inovacao'] ?? null;
$descricao_inovacao  = $_POST['descricao_inovacao'] ?? null;
$tipo_solucao        = $_POST['tipo_solucao'] ?? null;
$modelo_negocio      = $_POST['modelo_negocio'] ?? null;
$colaboradores       = $_POST['colaboradores'] ?? null;
$apoio               = $_POST['apoio'] ?? null;
$programas           = $_POST['programas'] ?? null;
$info_adicionais     = $_POST['info_adicionais'] ?? null;
$links               = $_POST['info_adicionais_link'] ?? [];
$linksJson           = json_encode(array_filter($links));
$inovacao_tecnologica   = ($_POST['inovacao_tecnologica'] ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_produto       = ($_POST['inovacao_produto'] ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_servico       = ($_POST['inovacao_servico'] ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_modelo        = ($_POST['inovacao_modelo'] ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_social        = ($_POST['inovacao_social'] ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_ambiental     = ($_POST['inovacao_ambiental'] ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_cadeia_valor  = ($_POST['inovacao_cadeia_valor'] ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_governanca    = ($_POST['inovacao_governanca'] ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_impacto       = ($_POST['inovacao_impacto'] ?? 'nao') === 'sim' ? 1 : 0;
$inovacao_financiamento = ($_POST['inovacao_financiamento'] ?? 'nao') === 'sim' ? 1 : 0;

$descricaoinovacao = $_POST['descricaoinovacao'] ?? null;

// flag geral (mantém campo antigo se quiser)
$inovacao = (
    $inovacao_tecnologica   ||
    $inovacao_produto       ||
    $inovacao_servico       ||
    $inovacao_modelo        ||
    $inovacao_social        ||
    $inovacao_ambiental     ||
    $inovacao_cadeia_valor  ||
    $inovacao_governanca    ||
    $inovacao_impacto       ||
    $inovacao_financiamento
) ? 'sim' : 'nao';

// ====== Desafios ======
$desafios = [
    "acessar_capital","fluxo_caixa","melhorar_gestao","estruturar_equipe",
    "falta_conselho_mentoria","escassez_tecnico","marketing_posicionamento",
    "baixa_demanda_vendas","falta_entendimento_publico","parcerias_networking",
    "acesso_mentoria_especializada","falta_entendimento_bancos","relacionamento_governo",
    "acesso_mercado_distribuicao","logistica_cara_ineficiente","baixa_capacidade_entrega",
    "infraestrutura_limitada_cara","internacionalizacao","instabilidade_economica",
    "carga_tributaria_burocracia","regulacao_desfavoravel"
];
$valoresDesafios = [];
foreach ($desafios as $d) {
    $campo = "desafio_" . $d;
    $valoresDesafios[$campo] = isset($_POST[$campo]) ? (int)$_POST[$campo] : 0;
}
// ====== Insert/Update ======
$stmt = $pdo->prepare("
    INSERT INTO negocio_apresentacao (
        negocio_id, logo_negocio, imagem_destaque, frase_negocio, problema_resolvido, solucao_oferecida,
        video_pitch_url, apresentacao_pdf, apresentacao_video_url,
        galeria_imagens, inovacao, descricao_inovacao, inovacao_tecnologica,
        inovacao_produto, inovacao_servico, inovacao_modelo,
        inovacao_social, inovacao_ambiental, inovacao_cadeia_valor,
        inovacao_governanca, inovacao_impacto, inovacao_financiamento,
        tipo_solucao, modelo_negocio, colaboradores,
        apoio, programas,
        " . implode(",", array_keys($valoresDesafios)) . ",
        info_adicionais, info_adicionais_links,
        criado_em, atualizado_em
    ) VALUES (
        :negocio_id, :logo, :imagem_destaque, :frase, :problema_resolvido, :solucao_oferecida,
        :video_pitch, :pdf, :video_inst,
        :galeria, :inovacao, :desc_inovacao, :inovacao_tecnologica, :inovacao_produto,
        :inovacao_servico, :inovacao_modelo,
        :inovacao_social, :inovacao_ambiental, :inovacao_cadeia_valor,
        :inovacao_governanca, :inovacao_impacto, :inovacao_financiamento,
        :tipo_solucao, :modelo_negocio, :colaboradores,
        :apoio, :programas,
        " . implode(",", array_map(fn($d)=>":".$d, array_keys($valoresDesafios))) . ",
        :info_adicionais, :links,
        NOW(), NOW()
    )
    ON DUPLICATE KEY UPDATE
        logo_negocio = VALUES(logo_negocio),
        imagem_destaque = VALUES(imagem_destaque),
        frase_negocio = VALUES(frase_negocio),
        problema_resolvido = VALUES(problema_resolvido),
        solucao_oferecida = VALUES(solucao_oferecida),
        video_pitch_url = VALUES(video_pitch_url),
        apresentacao_pdf = VALUES(apresentacao_pdf),
        apresentacao_video_url = VALUES(apresentacao_video_url),
        galeria_imagens = VALUES(galeria_imagens),
        inovacao = VALUES(inovacao),
        descricao_inovacao = VALUES(descricao_inovacao),
        inovacao_tecnologica = VALUES(inovacao_tecnologica),
        inovacao_produto = VALUES(inovacao_produto),
        inovacao_servico = VALUES(inovacao_servico),
        inovacao_modelo = VALUES(inovacao_modelo),
        inovacao_social = VALUES(inovacao_social),
        inovacao_ambiental = VALUES(inovacao_ambiental),
        inovacao_cadeia_valor = VALUES(inovacao_cadeia_valor),
        inovacao_governanca = VALUES(inovacao_governanca),
        inovacao_impacto = VALUES(inovacao_impacto),
        inovacao_financiamento = VALUES(inovacao_financiamento),
        tipo_solucao = VALUES(tipo_solucao),
        modelo_negocio = VALUES(modelo_negocio),
        colaboradores = VALUES(colaboradores),
        apoio = VALUES(apoio),
        programas = VALUES(programas),
        info_adicionais = VALUES(info_adicionais),
        info_adicionais_links = VALUES(info_adicionais_links),
        " . implode(",", array_map(fn($d)=>"$d = VALUES($d)", array_keys($valoresDesafios))) . ",
        atualizado_em = NOW()
");

$params = [
    'negocio_id'         => $negocio_id,
    'imagem_destaque'    => $imagemDestaqueUrl,
    'logo'               => $logoUrl,
    'frase'              => $frase_negocio,
    'problema_resolvido' => $problema_resolvido,
    'solucao_oferecida'  => $solucao_oferecida,
    'video_pitch'        => $video_pitch_url,
    'pdf'                => $pdfUrl,
    'video_inst'         => $apresentacao_video,
    'galeria'            => json_encode($galeriaAtual),
    'inovacao'           => $inovacao,
    'desc_inovacao'      => $descricao_inovacao,
    'inovacao_tecnologica' => $inovacao_tecnologica,
    'inovacao_produto'   => $inovacao_produto,
    'inovacao_servico'  => $inovacao_servico,
    'inovacao_modelo'   => $inovacao_modelo,
    'inovacao_social'   => $inovacao_social,
    'inovacao_ambiental'    => $inovacao_ambiental,
    'inovacao_cadeia_valor' => $inovacao_cadeia_valor,
    'inovacao_governanca'   => $inovacao_governanca,
    'inovacao_impacto'  => $inovacao_impacto,
    'inovacao_financiamento'    => $inovacao_financiamento,
    'tipo_solucao'       => $tipo_solucao,
    'modelo_negocio'     => $modelo_negocio,
    'colaboradores'      => $colaboradores,
    'apoio'              => $apoio,
    'programas'          => $programas,
    'info_adicionais'    => $info_adicionais,
    'links'              => $linksJson
];


// adiciona os desafios ao array de parâmetros
foreach ($valoresDesafios as $campo => $valor) {
    $params[$campo] = $valor;
}


// executa o insert/update
$stmt->execute($params);


function textoValido($texto) {
    $texto = trim($texto);
    $letrasEncontradas = preg_match_all('/[a-zA-ZÀ-ÿ]/', $texto);
    error_log("Texto: " . $texto . ", Letras encontradas: " . $letrasEncontradas); // ADICIONADO
    return $letrasEncontradas >= 5;
}
// Validações de texto
if (empty($frase_negocio) || !textoValido($frase_negocio)) {
    error_log("Erro: Frase do Negócio inválida: " . $frase_negocio); // ADICIONADO
    $_SESSION['errors_etapa5'][] = "O campo 'Frase do Negócio' deve conter texto válido.";
}

if (empty($problema_resolvido) || !textoValido($problema_resolvido)) {
    $_SESSION['errors_etapa5'][] = "O campo 'Qual problema você resolve?' deve conter texto válido.";
}

if (empty($solucao_oferecida) || !textoValido($solucao_oferecida)) {
    $_SESSION['errors_etapa5'][] = "O campo 'Qual solução você oferece?' deve conter texto válido.";
}


$temInovacao = ($inovacao === 'sim');

if ($temInovacao && empty($descricao_inovacao)) {
    $_SESSION['errors_etapa5'][] = 'Descreva brevemente as principais inovações do seu negócio.';
} elseif (!empty($descricao_inovacao) && !textoValido($descricao_inovacao)) {
    $_SESSION['errors_etapa5'][] = 'A descrição da inovação deve conter texto válido.';
}

if (!empty($programas) && !textoValido($programas)) {
    $_SESSION['errors_etapa5'][] = "O campo 'Programas de Fomento' deve conter texto válido.";
}

if (!empty($info_adicionais) && !textoValido($info_adicionais)) {
    $_SESSION['errors_etapa5'][] = "O campo 'Informações Adicionais' deve conter texto válido.";
}

// Se houver erros, volta para etapa
if (!empty($_SESSION['errors_etapa5'])) {
    header("Location: /negocios/etapa5_apresentacao.php?id=" . $negocio_id);
    exit;
}
// ==========================
// Cálculo do Score Impacto (parcial)
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
            $opcao = ($inovacao === 'sim') ? 'lucro_com_impacto_integrado' : 'impacto_secundario';
            break;
        case 'evidencias':
            $opcao = (!empty($info_adicionais) || !empty($links)) ? 'documentado_com_links' : 'vazio';
            break;
        default:
            $opcao = 'nao_informado'; // componentes que ainda não estão nesta etapa
    }

    // Busca valor normalizado
    $stmt2 = $pdo->prepare("SELECT valor FROM lookup_scores WHERE componente=? AND opcao=?");
    $stmt2->execute([$componente, $opcao]);
    $valor = (int)($stmt2->fetchColumn() ?: 0);

    $scoreImpacto += $valor * $peso;
}

// Penalidades (exemplo simples)
$penalty = 0;
if ($inovacao === 'nao' && empty($info_adicionais)) {
    $penalty += 5;
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
    
    if ($etapaAtualNoBanco < 6) {
        $stmtUpdate = $pdo->prepare("
            UPDATE negocios 
            SET etapa_atual = 6, updated_at = NOW() 
            WHERE id = ? AND empreendedor_id = ?
        ");
        $stmtUpdate->execute([$negocio_id, $_SESSION['user_id']]);
    }

    // Avança para a PRÓXIMA etapa
    header("Location: /negocios/etapa6_financeiro.php?id=" . $negocio_id);
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
