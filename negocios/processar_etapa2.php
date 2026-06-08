<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /empreendedores/meus-negocios.php');
    exit;
}

function isValidCPF(string $cpf): bool {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    $soma = 0;
    for ($i = 0; $i < 9; $i++) $soma += (int)$cpf[$i] * (10 - $i);
    $r = $soma % 11;
    $d1 = $r < 2 ? 0 : 11 - $r;
    if ($d1 !== (int)$cpf[9]) return false;
    $soma = 0;
    for ($i = 0; $i < 10; $i++) $soma += (int)$cpf[$i] * (11 - $i);
    $r = $soma % 11;
    $d2 = $r < 2 ? 0 : 11 - $r;
    return $d2 === (int)$cpf[10];
}

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'], $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id = (int)($_POST['negocio_id'] ?? 0);
$modo       = $_POST['modo'] ?? 'cadastro';
$acao       = $_POST['acao'] ?? 'salvar';
$fundador_id = (int)($_POST['fundador_id'] ?? 0);

$stmtVerif = $pdo->prepare("SELECT id FROM negocios WHERE id = ? AND empreendedor_id = ?");
$stmtVerif->execute([$negocio_id, $_SESSION['user_id']]);
if (!$stmtVerif->fetch()) {
    header('Location: /empreendedores/meus-negocios.php');
    exit;
}

try {
    $pdo->beginTransaction();
    $errors = [];

    /**
     * PULAR COFUNDADORES
     */
    if ($acao === 'pular_cofundadores') {
        $pdo->commit();
        header("Location: /negocios/etapa3_ideias.php?id={$negocio_id}");
        exit;
    }

    /**
     * FUNDADOR PRINCIPAL
     */
    $empreendedorEhFundador = (int)$pdo->query(
        "SELECT eh_fundador FROM empreendedores WHERE id = {$_SESSION['user_id']}"
    )->fetchColumn();

    if ($empreendedorEhFundador === 0 && isset($_POST['fundador_principal'])) {
        $f          = $_POST['fundador_principal'];
        $nome       = trim($f['nome'] ?? '');
        $sobrenome  = trim($f['sobrenome'] ?? '');
        $cpf        = trim($f['cpf'] ?? '');
        $email      = trim($f['email'] ?? '');
        $celular    = trim($f['celular'] ?? '');
        $data_nasc  = trim($f['data_nascimento'] ?? '');
        $genero     = trim($f['genero'] ?? '');
        $formacao   = trim($f['formacao'] ?? '');
        $etnia      = trim($f['etnia'] ?? '');
        $orientacao_sexual       = trim($f['orientacao_sexual'] ?? '');
        $orientacao_sexual_outra = trim($f['orientacao_sexual_outra'] ?? '');
        $grupo_vulneravel        = trim($f['grupo_vulneravel'] ?? '');
        if ($orientacao_sexual === 'Outra' && $orientacao_sexual_outra !== '') {
            $orientacao_sexual = 'Outra: ' . $orientacao_sexual_outra;
        }
        $email_optin    = isset($f['email_optin']) ? 1 : 0;
        $whatsapp_optin = isset($f['whatsapp_optin']) ? 1 : 0;
        $endereco_tipo  = trim($f['endereco_tipo'] ?? 'negocio');
        $rua       = trim($f['rua'] ?? '');
        $numero    = trim($f['numero'] ?? '');
        $cep       = trim($f['cep'] ?? '');
        $municipio = trim($f['municipio'] ?? '');
        $estado    = trim($f['estado'] ?? '');

        if ($cpf !== '' && !isValidCPF($cpf)) {
            $errors[] = 'CPF do fundador principal inválido.';
        }

        if (
            $nome === '' || $sobrenome === '' || $cpf === '' || $email === '' || $celular === '' ||
            $data_nasc === '' || $genero === '' ||
            $formacao === '' || $etnia === '' ||
            $orientacao_sexual === '' || $grupo_vulneravel === ''
        ) {
            $errors[] = 'Preencha todos os campos obrigatórios do fundador principal.';
        }

        $cpfNumericoFundador = preg_replace('/\D+/', '', $cpf);

        if (empty($errors)) {
            if ($fundador_id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE negocio_fundadores
                    SET nome = ?, sobrenome = ?, cpf = ?, email = ?, celular = ?,
                        data_nascimento = ?, genero = ?, formacao = ?, etnia = ?,
                        orientacao_sexual = ?, grupo_vulneravel = ?,
                        email_optin = ?, whatsapp_optin = ?, endereco_tipo = ?,
                        rua = ?, numero = ?, cep = ?, municipio = ?, estado = ?
                    WHERE id = ? AND negocio_id = ? AND tipo = 'principal'
                ");
                $stmt->execute([
                    $nome, $sobrenome, $cpf, $email, $celular, $data_nasc, $genero, $formacao, $etnia,
                    $orientacao_sexual, $grupo_vulneravel,
                    $email_optin, $whatsapp_optin, $endereco_tipo, $rua, $numero, $cep, $municipio, $estado,
                    $fundador_id, $negocio_id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO negocio_fundadores
                    (negocio_id, empreendedor_id, tipo, nome, sobrenome, cpf, email, celular, data_nascimento, genero, formacao, etnia, orientacao_sexual, grupo_vulneravel, email_optin, whatsapp_optin, endereco_tipo, rua, numero, cep, municipio, estado)
                    VALUES (?, ?, 'principal', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $negocio_id,
                    $_SESSION['user_id'],
                    $nome, $sobrenome, $cpf, $email, $celular, $data_nasc, $genero, $formacao, $etnia,
                    $orientacao_sexual, $grupo_vulneravel,
                    $email_optin, $whatsapp_optin, $endereco_tipo, $rua, $numero, $cep, $municipio, $estado
                ]);
            }
        }
    }

    /**
     * COFUNDADORES
     * REGRA:
     * - Limpa todos os cofundadores do negócio
     *   (ou só os novos, dependendo do modo — aqui optamos por DELETE + INSERT)
     */
    if (isset($_POST['cofundador']) && is_array($_POST['cofundador'])) {

        $pdo->prepare("DELETE FROM negocio_fundadores WHERE negocio_id = ? AND tipo = 'cofundador'")
            ->execute([$negocio_id]);

        $stmtInsert = $pdo->prepare("
            INSERT INTO negocio_fundadores
            (negocio_id, empreendedor_id, tipo, nome, sobrenome, cpf, email, celular, email_optin, whatsapp_optin, orientacao_sexual, grupo_vulneravel)
            VALUES (?, ?, 'cofundador', ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $cpfsJaInseridosNoMesmoNegocio = [];
        $cpfFundadorPrincipal = isset($cpfNumericoFundador) ? $cpfNumericoFundador : '';

        foreach ($_POST['cofundador'] as $i => $c) {
            if ($i > 4) {
                break;
            }

            $remover = isset($c['remover']) && (int)$c['remover'] === 1;
            if ($remover) {
                continue;
            }

            $nome       = trim($c['nome'] ?? '');
            $sobrenome  = trim($c['sobrenome'] ?? '');
            $cpf        = trim($c['cpf'] ?? '');
            $email      = trim($c['email'] ?? '');
            $celular    = trim($c['celular'] ?? '');
            $email_optin    = isset($c['email_optin']) ? 1 : 0;
            $whatsapp_optin = isset($c['whatsapp_optin']) ? 1 : 0;
            $cf_orientacao       = trim($c['orientacao_sexual'] ?? '');
            $cf_orientacao_outra = trim($c['orientacao_sexual_outra'] ?? '');
            $cf_grupo            = trim($c['grupo_vulneravel'] ?? '');
            if ($cf_orientacao === 'Outra' && $cf_orientacao_outra !== '') {
                $cf_orientacao = 'Outra: ' . $cf_orientacao_outra;
            }

            // Ignora linha totalmente vazia
            if ($nome === '' && $sobrenome === '' && $cpf === '' && $email === '' && $celular === '') {
                continue;
            }

            if ($cpf !== '' && !isValidCPF($cpf)) {
                $errors[] = "CPF inválido para cofundador " . ($i + 1) . ".";
                continue;
            }

            // Se começou a preencher, exige tudo
            if ($nome === '' || $sobrenome === '' || $cpf === '' || $email === '' || $celular === '') {
                $errors[] = "Preencha todos os campos do cofundador " . ($i + 1) . " ou remova-o.";
                continue;
            }

            $cpfNumerico = preg_replace('/\D+/', '', $cpf);

            // Não permite repetir o CPF do fundador principal no mesmo negócio
            if ($cpfFundadorPrincipal !== '' && $cpfNumerico === $cpfFundadorPrincipal) {
                $errors[] = "O CPF do cofundador " . ($i + 1) . " não pode ser igual ao CPF do fundador principal neste negócio.";
                continue;
            }

            // Não permite CPF repetido entre cofundadores do mesmo negócio
            if ($cpfNumerico !== '' && in_array($cpfNumerico, $cpfsJaInseridosNoMesmoNegocio, true)) {
                $errors[] = "O CPF do cofundador " . ($i + 1) . " está duplicado neste negócio.";
                continue;
            }

            $stmtInsert->execute([
                $negocio_id,
                $_SESSION['user_id'],
                $nome,
                $sobrenome,
                $cpf,
                $email,
                $celular,
                $email_optin,
                $whatsapp_optin,
                $cf_orientacao,
                $cf_grupo
            ]);

            if ($cpfNumerico !== '') {
                $cpfsJaInseridosNoMesmoNegocio[] = $cpfNumerico;
            }
        }
    }

    if (!empty($errors)) {
        throw new Exception(implode("\n", $errors));
    }

    $pdo->commit();

    $_SESSION['success_etapa2'] = true;

    if ($modo === 'edicao') {
        header("Location: /empreendedores/meus-negocios.php?salvo=1");
    } else {
        header("Location: /negocios/etapa3_ideias.php?id={$negocio_id}");
    }
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['errors_etapa2'] = explode("\n", $e->getMessage());

    if ($modo === 'edicao') {
        header("Location: /negocios/editar_etapa2.php?id={$negocio_id}");
    } else {
        header("Location: /negocios/etapa2_fundadores.php?id={$negocio_id}");
    }
    exit;
}