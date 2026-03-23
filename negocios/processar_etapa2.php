<?php
declare(strict_types=1);
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/helpers/auth.php';
require_role(['empreendedor']);

// Inclui funções auxiliares (onde está isValidCPF)
require_once __DIR__ . '/../app/helpers/functions.php';

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$errors = [];
$negocio_id = (int)($_POST['negocio_id'] ?? 0);
$acao       = $_POST['acao'] ?? 'salvar';
$modo       = $_POST['modo'] ?? 'cadastro';

if ($negocio_id === 0) {
    $errors[] = "Negócio inválido.";
}

/**
 * Fundador Principal
 */
if (isset($_POST['fundador_principal']) && is_array($_POST['fundador_principal'])) {
    $f = $_POST['fundador_principal'];

    $nome       = trim($f['nome'] ?? '');
    $sobrenome  = trim($f['sobrenome'] ?? '');
    $cpf        = trim($f['cpf'] ?? '');
    $email      = trim($f['email'] ?? '');
    $celular    = trim($f['celular'] ?? '');
    $data_nasc  = trim($f['data_nascimento'] ?? '');
    $genero     = trim($f['genero'] ?? '');
    $formacao   = trim($f['formacao'] ?? '');
    $etnia      = trim($f['etnia'] ?? '');
    $email_optin    = isset($f['email_optin']) ? 1 : 0;
    $whatsapp_optin = isset($f['whatsapp_optin']) ? 1 : 0;

    $endereco_tipo = $f['endereco_tipo'] ?? 'negocio';
    $rua       = ($endereco_tipo === 'residencial') ? trim($f['rua'] ?? '') : null;
    $numero    = ($endereco_tipo === 'residencial') ? trim($f['numero'] ?? '') : null;
    $cep       = ($endereco_tipo === 'residencial') ? trim($f['cep'] ?? '') : null;
    $municipio = ($endereco_tipo === 'residencial') ? trim($f['municipio'] ?? '') : null;
    $estado    = ($endereco_tipo === 'residencial') ? trim($f['estado'] ?? '') : null;

    if ($nome === '' || $sobrenome === '' || $cpf === '' || $email === '' || $celular === '' || $data_nasc === '' || $genero === '' || $formacao === '' || $etnia === '') {
        $errors[] = "Todos os campos obrigatórios do fundador principal devem ser preenchidos.";
    }

    if ($cpf !== '' && !isValidCPF($cpf)) {
        $errors[] = "CPF inválido para o fundador principal.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO negocio_fundadores 
            (negocio_id, empreendedor_id, tipo, nome, sobrenome, cpf, email, celular, data_nascimento, genero, formacao, etnia, email_optin, whatsapp_optin, endereco_tipo, rua, numero, cep, municipio, estado)
            VALUES (?, ?, 'principal', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                nome=VALUES(nome), sobrenome=VALUES(sobrenome), cpf=VALUES(cpf), email=VALUES(email), celular=VALUES(celular),
                data_nascimento=VALUES(data_nascimento), genero=VALUES(genero), formacao=VALUES(formacao), etnia=VALUES(etnia),
                email_optin=VALUES(email_optin), whatsapp_optin=VALUES(whatsapp_optin),
                endereco_tipo=VALUES(endereco_tipo), rua=VALUES(rua), numero=VALUES(numero), cep=VALUES(cep), municipio=VALUES(municipio), estado=VALUES(estado)
        ");
        $stmt->execute([
            $negocio_id,
            $_SESSION['user_id'],
            $nome, $sobrenome, $cpf, $email, $celular, $data_nasc, $genero, $formacao, $etnia,
            $email_optin, $whatsapp_optin, $endereco_tipo, $rua, $numero, $cep, $municipio, $estado
        ]);
    }
}

/**
 * Cofundadores
 */
if (isset($_POST['cofundador']) && is_array($_POST['cofundador'])) {
    foreach ($_POST['cofundador'] as $i => $c) {
        if ($i > 4) break;

        $id        = isset($c['id']) ? (int)$c['id'] : null;
        $remover   = isset($c['remover']) && (int)$c['remover'] === 1;
        $nome      = trim($c['nome'] ?? '');
        $sobrenome = trim($c['sobrenome'] ?? '');
        $cpf       = trim($c['cpf'] ?? '');
        $email     = trim($c['email'] ?? '');
        $celular   = trim($c['celular'] ?? '');
        $email_optin    = isset($c['email_optin']) ? 1 : 0;
        $whatsapp_optin = isset($c['whatsapp_optin']) ? 1 : 0;

        if ($remover && $id) {
            $stmt = $pdo->prepare("DELETE FROM negocio_fundadores WHERE id=? AND negocio_id=? AND tipo='cofundador'");
            $stmt->execute([$id, $negocio_id]);
            continue;
        }

        if ($nome === '' && $sobrenome === '' && $cpf === '' && $email === '' && $celular === '') {
            continue;
        }

        if ($cpf !== '' && !isValidCPF($cpf)) {
            $errors[] = "CPF inválido para cofundador " . ($i+1);
            continue;
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE negocio_fundadores SET nome=?, sobrenome=?, cpf=?, email=?, celular=?, email_optin=?, whatsapp_optin=? WHERE id=? AND negocio_id=? AND tipo='cofundador'");
            $stmt->execute([$nome, $sobrenome, $cpf, $email, $celular, $email_optin, $whatsapp_optin, $id, $negocio_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO negocio_fundadores (negocio_id, empreendedor_id, tipo, nome, sobrenome, cpf, email, celular, email_optin, whatsapp_optin) VALUES (?, ?, 'cofundador', ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$negocio_id, $_SESSION['user_id'], $nome, $sobrenome, $cpf, $email, $celular, $email_optin, $whatsapp_optin]);
        }
    }
}

if (!empty($errors)) {
  $_SESSION['errors_etapa2'] = $errors;
  
  // Verifica se veio de edição ou cadastro
  $modo = $_POST['modo'] ?? 'cadastro';
  
  if ($modo === 'editar') {
    // MODO EDIÇÃO: volta para editar_etapa2 (fluxo atual)
    $_SESSION['dados_post_etapa2'] = $_POST;
    header("Location: /negocios/editar_etapa2.php?id=" . $negocio_id);
  } else {
    // MODO CADASTRO: volta para etapa2_fundadores
    $_SESSION['dados_post_etapa2'] = $_POST;
    header("Location: /negocios/etapa2_fundadores.php?id=" . $negocio_id);
  }
  exit;
}

// --------- Redirecionamento Inteligente ---------
$modo = $_POST['modo'] ?? 'cadastro';

// PRIMEIRO: Busca como o negócio está AGORA no banco
$stmtProgresso = $pdo->prepare("SELECT etapa_atual, inscricao_completa FROM negocios WHERE id = ?");
$stmtProgresso->execute([$negocio_id]);
$progresso = $stmtProgresso->fetch(PDO::FETCH_ASSOC);

if ($modo === 'cadastro') {
    // Modo Cadastro: Atualiza a etapa (neste caso da 2 para a 3) SOMENTE SE ainda não passou por ela
    $etapaAtualNoBanco = (int)($progresso['etapa_atual'] ?? 1);
    
    if ($etapaAtualNoBanco < 3) {
        $stmtUpdate = $pdo->prepare("
            UPDATE negocios 
            SET etapa_atual = 3, updated_at = NOW() 
            WHERE id = ? AND empreendedor_id = ?
        ");
        $stmtUpdate->execute([$negocio_id, $_SESSION['user_id']]);
    }

    // Avança para a PRÓXIMA etapa
    header("Location: /negocios/etapa3_eixo_tematico.php?id=" . $negocio_id);
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

