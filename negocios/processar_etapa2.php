<?php
declare(strict_types=1);
session_start();


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

try {
    if (!empty($errors)) {
        throw new Exception(implode("\n", $errors));
    }

    // Confirma se o negócio pertence ao usuário logado
    $stmt = $pdo->prepare("SELECT id, empreendedor_id FROM negocios WHERE id = ? AND empreendedor_id = ?");
    $stmt->execute([$negocio_id, $_SESSION['user_id']]);
    $negocio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$negocio) {
        throw new Exception("Negócio não encontrado ou sem permissão.");
    }

    $pdo->beginTransaction();

    /**
     * FUNDADOR PRINCIPAL
     */
    if (isset($_POST['fundador_principal']) && is_array($_POST['fundador_principal'])) {
        $f = $_POST['fundador_principal'];

        $fundador_id = (int)($f['id'] ?? 0);
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

        if (
            $nome === '' || $sobrenome === '' || $cpf === '' || $email === '' ||
            $celular === '' || $data_nasc === '' || $genero === '' ||
            $formacao === '' || $etnia === ''
        ) {
            $errors[] = "Todos os campos obrigatórios do fundador principal devem ser preenchidos.";
        }

        if ($cpf !== '' && !isValidCPF($cpf)) {
            $errors[] = "CPF inválido para o fundador principal.";
        }

        $cpfNumericoFundador = preg_replace('/\D+/', '', $cpf);

        // Não permite CPF duplicado dentro do mesmo negócio
        if ($cpfNumericoFundador !== '') {
            $stmtCpf = $pdo->prepare("
                SELECT id
                FROM negocio_fundadores
                WHERE negocio_id = ?
                  AND REPLACE(REPLACE(cpf, '.', ''), '-', '') = ?
                  AND id <> ?
                LIMIT 1
            ");
            $stmtCpf->execute([$negocio_id, $cpfNumericoFundador, $fundador_id]);
            if ($stmtCpf->fetch()) {
                $errors[] = "O CPF do fundador principal já está cadastrado neste negócio.";
            }
        }

        if (empty($errors)) {
            if ($fundador_id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE negocio_fundadores
                    SET nome = ?, sobrenome = ?, cpf = ?, email = ?, celular = ?,
                        data_nascimento = ?, genero = ?, formacao = ?, etnia = ?,
                        email_optin = ?, whatsapp_optin = ?, endereco_tipo = ?,
                        rua = ?, numero = ?, cep = ?, municipio = ?, estado = ?
                    WHERE id = ? AND negocio_id = ? AND tipo = 'principal'
                ");
                $stmt->execute([
                    $nome, $sobrenome, $cpf, $email, $celular, $data_nasc, $genero, $formacao, $etnia,
                    $email_optin, $whatsapp_optin, $endereco_tipo, $rua, $numero, $cep, $municipio, $estado,
                    $fundador_id, $negocio_id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO negocio_fundadores
                    (negocio_id, empreendedor_id, tipo, nome, sobrenome, cpf, email, celular, data_nascimento, genero, formacao, etnia, email_optin, whatsapp_optin, endereco_tipo, rua, numero, cep, municipio, estado)
                    VALUES (?, ?, 'principal', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $negocio_id,
                    $_SESSION['user_id'],
                    $nome, $sobrenome, $cpf, $email, $celular, $data_nasc, $genero, $formacao, $etnia,
                    $email_optin, $whatsapp_optin, $endereco_tipo, $rua, $numero, $cep, $municipio, $estado
                ]);
            }
        }
    }

    /**
     * COFUNDADORES
     * REGRA:
     * - Limpa todos os cofundadores do negócio
     * - Reinsere somente os que vieram preenchidos e não foram marcados para remoção
     * - CPF pode repetir em outros negócios, mas não dentro do mesmo negócio
     */
    if (isset($_POST['cofundador']) && is_array($_POST['cofundador'])) {
        $stmtDelete = $pdo->prepare("
            DELETE FROM negocio_fundadores
            WHERE negocio_id = ? AND tipo = 'cofundador'
        ");
        $stmtDelete->execute([$negocio_id]);

        $stmtInsert = $pdo->prepare("
            INSERT INTO negocio_fundadores
            (negocio_id, empreendedor_id, tipo, nome, sobrenome, cpf, email, celular, email_optin, whatsapp_optin)
            VALUES (?, ?, 'cofundador', ?, ?, ?, ?, ?, ?, ?)
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
                $whatsapp_optin
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

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getMessage() !== '') {
        $errors[] = $e->getMessage();
    }
}

if (!empty($errors)) {
    $_SESSION['errors_etapa2'] = $errors;

    if ($modo === 'editar') {
        $_SESSION['dados_post_etapa2'] = $_POST;
        header("Location: /negocios/editar_etapa2.php?id=" . $negocio_id);
    } else {
        $_SESSION['dados_post_etapa2'] = $_POST;
        header("Location: /negocios/etapa2_fundadores.php?id=" . $negocio_id);
    }
    exit;
}

// --------- Redirecionamento Inteligente ---------
$modo = $_POST['modo'] ?? 'cadastro';

// Busca como o negócio está AGORA no banco
$stmtProgresso = $pdo->prepare("SELECT etapa_atual, inscricao_completa FROM negocios WHERE id = ?");
$stmtProgresso->execute([$negocio_id]);
$progresso = $stmtProgresso->fetch(PDO::FETCH_ASSOC);

if ($modo === 'cadastro') {
    // Modo Cadastro: Atualiza a etapa somente se ainda não passou dela
    $etapaAtualNoBanco = (int)($progresso['etapa_atual'] ?? 1);

    if ($etapaAtualNoBanco < 3) {
        $stmtUpdate = $pdo->prepare("
            UPDATE negocios 
            SET etapa_atual = 3, updated_at = NOW() 
            WHERE id = ? AND empreendedor_id = ?
        ");
        $stmtUpdate->execute([$negocio_id, $_SESSION['user_id']]);
    }

    header("Location: /negocios/etapa3_eixo_tematico.php?id=" . $negocio_id);
    exit;

} else {
    if (!empty($progresso['inscricao_completa'])) {
        header("Location: /negocios/confirmacao.php?id=" . $negocio_id);
        exit;
    } else {
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