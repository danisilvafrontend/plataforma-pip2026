<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/functions.php';

// Apenas empreendedores podem acessar
require_role(['empreendedor']);

// Conexão PDO
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$errors = [];
$modo = $_POST['modo'] ?? 'cadastro';
$id   = (int)($_POST['id'] ?? 0);

// --------- Entrada de dados ---------
$nome_fantasia   = trim($_POST['nome_fantasia'] ?? '');
$razao_social    = trim($_POST['razao_social'] ?? '');
$categoria       = trim($_POST['categoria'] ?? '');
$cnpj_cpf_raw    = $_POST['cnpj_cpf'] ?? '';
$cnpj_cpf        = preg_replace('/\D/', '', $cnpj_cpf_raw); // apenas dígitos
$formato_legal   = trim($_POST['formato_legal'] ?? '');
$formato_outros  = trim($_POST['formato_outros'] ?? '');
$email_comercial = trim($_POST['email_comercial'] ?? '');
$telefone_comercial = trim($_POST['telefone_comercial'] ?? '');
$data_fundacao   = $_POST['data_fundacao'] ?? null;
$setor           = trim($_POST['setor'] ?? '');
$setor_detalhe   = trim($_POST['setor_detalhe'] ?? '');
$rua             = trim($_POST['rua'] ?? '');
$numero          = trim($_POST['numero'] ?? '');
$complemento     = trim($_POST['complemento'] ?? '');
$cep             = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$municipio       = trim($_POST['municipio'] ?? '');
$estado          = trim($_POST['estado'] ?? '');
$pais            = 'Brasil';
$site            = trim($_POST['site'] ?? '');
$linkedin        = trim($_POST['linkedin'] ?? '');
$instagram       = trim($_POST['instagram'] ?? '');
$facebook        = trim($_POST['facebook'] ?? '');
$tiktok          = trim($_POST['tiktok'] ?? '');
$youtube         = trim($_POST['youtube'] ?? '');
$outros_links    = trim($_POST['outros_links'] ?? '');
$interesse_marketplace = $_POST['interesse_marketplace'] ?? null;

// --------- Validações ---------
$errors = [];

if ($nome_fantasia === '') {
    $errors[] = "Nome do negócio é obrigatório.";
}

// Razão Social: obrigatória APENAS quando não for Ideação com CPF
$cnpj_cpf_digits = strlen($cnpj_cpf);
$ideacaoComCpf   = ($categoria === 'Ideação' && $cnpj_cpf_digits === 11);

if (!$ideacaoComCpf && $razao_social === '') {
    $errors[] = "Razão social é obrigatória.";
}

if ($categoria === '') {
    $errors[] = "Selecione a categoria do empreendimento.";
}

// Validação Formato Legal
if ($formato_legal === '') {
    $errors[] = "Informe o formato legal.";
} elseif ($formato_legal === 'Outros') {
    if ($formato_outros === '') {
        $errors[] = "Informe o formato legal quando selecionar 'Outros'.";
    }
}

if ($data_fundacao === null || $data_fundacao === '') {
    $errors[] = "Informe a data de fundação.";
} else {
    $d = DateTime::createFromFormat('Y-m-d', $data_fundacao);
    $hoje = new DateTime('today');

    if (!$d || $d->format('Y-m-d') !== $data_fundacao) {
        $errors[] = "Data de fundação inválida.";
    } elseif ($d > $hoje) {
        $errors[] = "Data de fundação não pode ser futura.";
    }
}

if ($setor === '') {
    $errors[] = "Informe o setor.";
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
        if (empty($municipio)) $municipio = $dadosCep['localidade'] ?? $municipio;
        if (empty($estado)) $estado = $dadosCep['uf'] ?? $estado;
        if (empty($rua)) $rua = $dadosCep['logradouro'] ?? $rua;
    }
}

// Validação CPF/CNPJ conforme categoria
if ($categoria === 'Ideação') {
    if ($cnpj_cpf === '') {
        $errors[] = "Informe CPF ou CNPJ.";
    } else {
        if (strlen($cnpj_cpf) === 11) {
            if (!isValidCPF($cnpj_cpf)) {
                $errors[] = "CPF inválido. Verifique os dígitos.";
            }
            // CPF em Ideação: Razão Social não é obrigatória (já tratado acima)
        } elseif (strlen($cnpj_cpf) === 14) {
            if (!isValidCNPJ($cnpj_cpf)) {
                $errors[] = "CNPJ inválido. Verifique os dígitos.";
            }
            // CNPJ em Ideação: Razão Social obrigatória
            if ($razao_social === '') {
                $errors[] = "Razão social é obrigatória quando informar CNPJ.";
            }
        } else {
            $errors[] = "Informe CPF (11 dígitos) ou CNPJ (14 dígitos).";
        }
    }
} else {
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

// Validações adicionais de URL simples (se preenchidas)
$urls = ['site' => $site, 'linkedin' => $linkedin, 'instagram' => $instagram, 'facebook' => $facebook, 'tiktok' => $tiktok, 'youtube' => $youtube, 'outros_links' => $outros_links];
foreach ($urls as $k => $u) {
    if ($u !== '' && !filter_var($u, FILTER_VALIDATE_URL)) {
        $errors[] = "URL inválida em {$k}. Use http:// ou https://";
    }
}
if ($modo === 'cadastro') {
    if (empty($interesse_marketplace) || !in_array($interesse_marketplace, ['Sim', 'Não'])) {
        $errors[] = "Por favor, responda se tem interesse no futuro marketplace.";
    }
}
if (!empty($errors)) {
    $_SESSION['errors_etapa1'] = $errors;
    $redirect = ($modo === 'cadastro')
        ? "/negocios/etapa1_dados_negocio.php"
        : "/negocios/editar_etapa1.php?id=" . $id;
    header("Location: $redirect");
    exit;
}


// --------- INSERT ---------
try {
        if ($modo === 'cadastro') {
        $sql = "INSERT INTO negocios (
                    empreendedor_id, nome_fantasia, razao_social, categoria, cnpj_cpf,
                    formato_legal, formato_outros, email_comercial, telefone_comercial, data_fundacao, setor,
                    rua, numero, complemento, cep, municipio, estado, pais,
                    site, linkedin, instagram, facebook, tiktok, youtube, outros_links, interesse_marketplace,
                    etapa_atual, inscricao_completa
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'], $nome_fantasia, $razao_social, $categoria, $cnpj_cpf,
            $formato_legal, $formato_outros, $email_comercial, $telefone_comercial, $data_fundacao, $setor,
            $rua, $numero, $complemento, $cep, $municipio, $estado, $pais,
            $site, $linkedin, $instagram, $facebook, $tiktok, $youtube, $outros_links, $interesse_marketplace,
            1, 0
        ]);

        
        $id = (int)$pdo->lastInsertId();
        $_SESSION['negocio_id'] = $id;
        $pdo->prepare("UPDATE negocios SET etapa_atual = 2 WHERE id = ?")->execute([$id]);
        
        } else {
        $sql = "UPDATE negocios SET 
                    nome_fantasia=?, razao_social=?, categoria=?, cnpj_cpf=?,
                    formato_legal=?, formato_outros=?, email_comercial=?, telefone_comercial=?, data_fundacao=?, setor=?,
                    rua=?, numero=?, complemento=?, cep=?, municipio=?, estado=?, pais=?,
                    site=?, linkedin=?, instagram=?, facebook=?, tiktok=?, youtube=?, outros_links=?, interesse_marketplace=?
                WHERE id=? AND empreendedor_id=?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nome_fantasia, $razao_social, $categoria, $cnpj_cpf,
            $formato_legal, $formato_outros, $email_comercial, $telefone_comercial, $data_fundacao, $setor,
            $rua, $numero, $complemento, $cep, $municipio, $estado, $pais,
            $site, $linkedin, $instagram, $facebook, $tiktok, $youtube, $outros_links, $interesse_marketplace,
            $id, $_SESSION['user_id']
        ]);
    }



    // --------- Score Investimento (parcial: estágio) ---------
   // Normaliza categoria para opcao do lookup
    switch ($categoria) {
        case 'Ideação':        $opcao = 'ideacao'; break;
        case 'Operação':       $opcao = 'operacao'; break;
        case 'Tração/Escala':  $opcao = 'tracao'; break;
        case 'Dinamizador':    $opcao = 'dinamizador'; break;
        default:               $opcao = 'nao_informado';
    }

    // Busca peso
    $stmt = $pdo->prepare("SELECT peso FROM pesos_scores WHERE tipo_score='INVESTIMENTO' AND componente='estagio'");
    $stmt->execute();
    $pesoEstagio = (float)$stmt->fetchColumn();

    // Busca valor normalizado
    $stmt = $pdo->prepare("SELECT valor FROM lookup_scores WHERE componente='estagio' AND opcao=?");
    $stmt->execute([$opcao]);
    $valorEstagio = (int)($stmt->fetchColumn() ?: 0);

    // Calcula score parcial
    $scoreInvestimento = round($valorEstagio * $pesoEstagio);

    // Salva no banco
    $stmt = $pdo->prepare("
        INSERT INTO scores_negocios (negocio_id, score_investimento, atualizado_em)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE score_investimento=VALUES(score_investimento), atualizado_em=NOW()
    ");
    $stmt->execute([$id, $scoreInvestimento]);

// --------- Redirecionamento Inteligente ---------
$modo = $_POST['modo'] ?? 'cadastro';

// Busca o status de andamento do negócio
$stmtProgresso = $pdo->prepare("SELECT etapa_atual, inscricao_completa FROM negocios WHERE id = ?");
$stmtProgresso->execute([$id]);
$progresso = $stmtProgresso->fetch(PDO::FETCH_ASSOC);

if ($modo === 'cadastro') {
    header("Location: /negocios/etapa2_fundadores.php?id=" . $id);
    exit;
} else {
    if (!empty($progresso['inscricao_completa'])) {
        header("Location: /negocios/confirmacao.php?id=" . $id);
        exit;
    }

    $etapaParada = (int)($progresso['etapa_atual'] ?? 1);

    if ($etapaParada <= 1) {
        $pdo->prepare("UPDATE negocios SET etapa_atual = 2 WHERE id = ?")
            ->execute([$id]);
        $etapaParada = 2;
    }

    $rotas_etapas = [
        1  => '/negocios/etapa1_dados_negocio.php',
        2  => '/negocios/etapa2_fundadores.php',
        3  => '/negocios/etapa3_eixo_tematico.php',
        4  => '/negocios/etapa4_ods.php',
        5  => '/negocios/etapa5_apresentacao.php',
        6  => '/negocios/etapa6_financeiro.php',
        7  => '/negocios/etapa7_impacto.php',
        8  => '/negocios/etapa8_visao.php',
        9  => '/negocios/etapa9_documentacao.php',
        10 => '/negocios/confirmacao.php',
    ];

    if (isset($rotas_etapas[$etapaParada])) {
        header("Location: " . $rotas_etapas[$etapaParada] . "?id=" . $id);
    } else {
        header("Location: /empreendedores/meus-negocios.php");
    }
    exit;
}



} catch (PDOException $e) {
    $_SESSION['errors_etapa1'] = ["Erro ao salvar: " . $e->getMessage()];
    $redirect = ($modo === 'cadastro')
        ? "/negocios/etapa1_dados_negocio.php"
        : "/negocios/editar_etapa1.php?id=" . $id;
    header("Location: $redirect");
    exit;
}