<?php
// session_start(); ← COMENTE ou DELETE (já foi iniciado no form)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config/db.php';
require __DIR__ . '/../app/helpers/auth.php';
require __DIR__ . '/../app/helpers/functions.php'; // valida_cpf, etc.

// Verificação de login MANUAL (igual etapa2_fundadores.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);



$errors     = [];
$negocio_id = (int)($_POST['negocio_id'] ?? 0);
$acao       = $_POST['acao'] ?? 'salvar';

if ($negocio_id === 0) {
    $errors[] = "Negócio inválido.";
}

// Busca negócio + empreendedor para checar se pode editar e se é fundador
if (empty($errors)) {
    $stmt = $pdo->prepare("
        SELECT n.*, e.eh_fundador
        FROM negocios n
        JOIN empreendedores e ON n.empreendedor_id = e.id
        WHERE n.id = ? AND n.empreendedor_id = ?
    ");
    $stmt->execute([$negocio_id, $_SESSION['user_id']]);
    $negocio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$negocio) {
        $errors[] = "Negócio não encontrado ou você não tem permissão.";
    } else {
        $empreendedorEhFundador   = (int)$negocio['eh_fundador'];
        $permiteFundadorPrincipal = $empreendedorEhFundador === 0;
    }
}

// Salvar / atualizar Fundador Principal (apenas se empreendedor NÃO é fundador)
if (empty($errors) && $permiteFundadorPrincipal && isset($_POST['fundador_principal']) && is_array($_POST['fundador_principal'])) {

    $f             = $_POST['fundador_principal'];
    $nome          = trim($f['nome'] ?? '');
    $sobrenome     = trim($f['sobrenome'] ?? '');
    $cpf           = trim($f['cpf'] ?? '');
    $email         = trim($f['email'] ?? '');
    $celular       = trim($f['celular'] ?? '');
    $data_nasc     = trim($f['data_nascimento'] ?? '');
    $genero        = trim($f['genero'] ?? '');
    $formacao      = trim($f['formacao'] ?? '');
    $etnia         = trim($f['etnia'] ?? '');
    $email_optin   = isset($f['email_optin']) ? 1 : 0;
    $whatsapp_optin= isset($f['whatsapp_optin']) ? 1 : 0;

    // Endereço - SEMPRE define (NULL se negocio)
    $endereco_tipo = $f['endereco_tipo'] ?? 'negocio';
    $rua           = ($endereco_tipo === 'residencial') ? trim($f['rua'] ?? '') : null;
    $numero        = ($endereco_tipo === 'residencial') ? trim($f['numero'] ?? '') : null;
    $cep           = ($endereco_tipo === 'residencial') ? trim($f['cep'] ?? '') : null;
    $municipio     = ($endereco_tipo === 'residencial') ? trim($f['municipio'] ?? '') : null;
    $estado        = ($endereco_tipo === 'residencial') ? trim($f['estado'] ?? '') : null;

    // Validação TODOS os obrigatórios
    if (empty($nome) || empty($sobrenome) || empty($cpf) || empty($email) || empty($celular) ||
        empty($data_nasc) || empty($genero) || empty($formacao) || empty($etnia)) {
        $errors[] = "Todos os campos obrigatórios do fundador principal devem ser preenchidos.";
    }

    // Validação CPF
    if (!empty($cpf) && !validar_cpf($cpf)) {
        $errors[] = "CPF inválido para o fundador principal.";
    }

    if (empty($errors)) {
        // PRIMEIRO: deleta o principal existente (garante 1 único)
        $stmt = $pdo->prepare("DELETE FROM negocio_fundadores WHERE negocio_id = ? AND tipo = 'principal'");
        $stmt->execute([$negocio_id]);

        // DEPOIS: insere/atualiza (sempre 1 registro)
        $stmt = $pdo->prepare("
            INSERT INTO negocio_fundadores (
                negocio_id, empreendedor_id, tipo, nome, sobrenome, cpf, email, celular,
                data_nascimento, genero, formacao, etnia, email_optin, whatsapp_optin,
                endereco_tipo, rua, numero, cep, municipio, estado
            ) VALUES (
                ?, ?, 'principal', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        $stmt->execute([
            $negocio_id,
            $_SESSION['user_id'],
            $nome, $sobrenome, $cpf, $email, $celular,
            $data_nasc, $genero, $formacao, $etnia,
            $email_optin, $whatsapp_optin,
            $endereco_tipo, $rua, $numero, $cep, $municipio, $estado
        ]);
    }
}


// Salvar / atualizar / remover Cofundadores (até 4)
if (empty($errors) && isset($_POST['cofundador']) && is_array($_POST['cofundador'])) {
    foreach ($_POST['cofundador'] as $i => $c) {
        if ($i > 4) {
            break;
        }

        $id            = isset($c['id']) ? (int)$c['id'] : null;
        $remover       = isset($c['remover']) && (int)$c['remover'] === 1;

        $nome          = trim($c['nome'] ?? '');
        $sobrenome     = trim($c['sobrenome'] ?? '');
        $cpf           = trim($c['cpf'] ?? '');
        $email         = trim($c['email'] ?? '');
        $celular       = trim($c['celular'] ?? '');
        $email_optin   = isset($c['email_optin']) ? 1 : 0;
        $whatsapp_optin= isset($c['whatsapp_optin']) ? 1 : 0;

        // Se marcou remover e o registro existe
        if ($remover && $id) {
            $stmt = $pdo->prepare("
                DELETE FROM negocio_fundadores
                WHERE id = ? AND negocio_id = ? AND tipo = 'cofundador'
            ");
            $stmt->execute([$id, $negocio_id]);
            continue;
        }

        // Se todos os campos estiverem vazios e não marcou remover, ignora
        if ($nome === '' && $sobrenome === '' && $cpf === '' && $email === '' && $celular === '') {
            continue;
        }

        // Validação de CPF, se preenchido
        if ($cpf !== '' && !validar_cpf($cpf)) {
            $errors[] = "CPF inválido para o cofundador " . ($i + 1) . ".";
            continue;
        }

        if ($id) {
            // UPDATE
            $stmt = $pdo->prepare("
                UPDATE negocio_fundadores
                SET nome = ?, sobrenome = ?, cpf = ?, email = ?, celular = ?,
                    email_optin = ?, whatsapp_optin = ?
                WHERE id = ? AND negocio_id = ? AND tipo = 'cofundador'
            ");
            $stmt->execute([
                $nome, $sobrenome, $cpf, $email, $celular,
                $email_optin, $whatsapp_optin,
                $id, $negocio_id
            ]);
        } else {
            // INSERT
            $stmt = $pdo->prepare("
                INSERT INTO negocio_fundadores
                    (negocio_id, empreendedor_id, tipo, nome, sobrenome, cpf, email, celular,
                     email_optin, whatsapp_optin)
                VALUES
                    (?, ?, 'cofundador', ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $negocio_id,
                $_SESSION['user_id'], // empreendedor logado
                $nome, $sobrenome, $cpf, $email, $celular,
                $email_optin, $whatsapp_optin
            ]);
        }
    }
}

// Se houve erros, volta para o editar_etapa2
if (!empty($errors)) {
    $_SESSION['errors_etapa2'] = $errors;
    header("Location: /negocios/editar_etapa2.php?id=" . $negocio_id);
    exit;
}

// Atualizar etapa do negócio (mantendo a mesma lógica da etapa de cadastro)
$stmt = $pdo->prepare("UPDATE negocios SET etapa_atual = 3 WHERE id = ?");
$stmt->execute([$negocio_id]);

// Redirecionamento pós-salvar
if ($acao === 'pular_cofundadores') {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}

header("Location: /empreendedores/meus-negocios.php");
exit;
