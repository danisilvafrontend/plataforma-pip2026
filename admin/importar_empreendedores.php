<?php
// /public_html/admin/importar_empreendedores.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$possibleAppPaths = [
    __DIR__ . '/../app',
    __DIR__ . '/../../app',
    __DIR__ . '/app',
];

$appBase = null;
foreach ($possibleAppPaths as $p) {
    if (is_dir($p)) {
        $appBase = realpath($p);
        break;
    }
}

if ($appBase === null) {
    die("Erro: pasta app não encontrada.");
}

require_once $appBase . '/helpers/auth.php';
require_admin_login();

$config = require $appBase . '/config/db.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

$mensagem = '';

function gerarSenhaTemporaria(int $tamanho = 12): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    $max = strlen($chars) - 1;
    $senha = '';

    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $chars[random_int(0, $max)];
    }

    return $senha;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['arquivo_csv'])) {
            throw new Exception('Nenhum arquivo foi enviado.');
        }

        if (!isset($_FILES['arquivo_csv']['error']) || $_FILES['arquivo_csv']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo CSV.');
        }

        $arquivo = $_FILES['arquivo_csv']['tmp_name'];

        if (empty($arquivo) || !is_uploaded_file($arquivo)) {
            throw new Exception('Arquivo CSV inválido.');
        }

        $nomeArquivoOriginal = trim((string)($_FILES['arquivo_csv']['name'] ?? 'importacao_csv'));
        $origemImportacao = basename($nomeArquivoOriginal);
        if ($origemImportacao === '') {
            $origemImportacao = 'importacao_csv';
        }

        $handle = fopen($arquivo, 'r');
        if ($handle === false) {
            throw new Exception('Não foi possível abrir o arquivo CSV.');
        }

        $header = fgetcsv($handle, 1000, ';');
        if ($header === false) {
            fclose($handle);
            throw new Exception('O arquivo CSV está vazio ou inválido.');
        }

        if (isset($header[0])) {
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        }

        $inseridos = 0;
        $atualizados = 0;
        $negocios_vinculados = 0;

        $pdo->beginTransaction();

        try {
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($data) < 9) {
                    continue;
                }

                $nome          = trim((string)$data[0]);
                $sobrenome     = trim((string)$data[1]);
                $cpf_original  = trim((string)$data[2]);
                $email         = trim((string)$data[3]);
                $celular       = trim((string)$data[4]);
                $data_nasc_raw = trim((string)$data[5]);
                $genero        = trim((string)$data[6]);
                $cargo         = trim((string)$data[7]);
                $formacao      = trim((string)$data[8]);

                $cpf = preg_replace('/[^0-9]/', '', $cpf_original);
                if (empty($cpf)) {
                    continue;
                }

                $data_nascimento = null;
                if (!empty($data_nasc_raw)) {
                    $partes_data = explode(' ', $data_nasc_raw);
                    $d_m_y = explode('/', $partes_data[0]);

                    if (count($d_m_y) === 3) {
                        $dia = str_pad($d_m_y[0], 2, '0', STR_PAD_LEFT);
                        $mes = str_pad($d_m_y[1], 2, '0', STR_PAD_LEFT);
                        $ano = $d_m_y[2];
                        $data_nascimento = $ano . '-' . $mes . '-' . $dia;
                    }
                }

                $stmt_check = $pdo->prepare("
                    SELECT id
                    FROM empreendedores
                    WHERE cpf = ? OR email = ?
                    LIMIT 1
                ");
                $stmt_check->execute([$cpf, $email]);
                $row = $stmt_check->fetch();

                if ($row) {
                    $empreendedor_id = (int)$row['id'];

                    $stmt_upd = $pdo->prepare("
                        UPDATE empreendedores
                        SET
                            nome = ?,
                            sobrenome = ?,
                            email = ?,
                            celular = ?,
                            data_nascimento = ?,
                            genero = ?,
                            cargo = ?,
                            formacao = ?,
                            eh_fundador = ?,
                            origem_importacao = ?
                        WHERE id = ?
                    ");

                    $stmt_upd->execute([
                        $nome,
                        $sobrenome,
                        $email,
                        $celular,
                        $data_nascimento,
                        $genero,
                        $cargo,
                        $formacao,
                        1,
                        $origemImportacao,
                        $empreendedor_id
                    ]);

                    if ($stmt_upd->rowCount() > 0) {
                        $atualizados++;
                    }
                } else {
                    $senhaTemporaria = gerarSenhaTemporaria(12);
                    $senha_hash = password_hash($senhaTemporaria, PASSWORD_DEFAULT);

                    $stmt_ins = $pdo->prepare("
                        INSERT INTO empreendedores
                        (
                            nome,
                            sobrenome,
                            cpf,
                            email,
                            celular,
                            data_nascimento,
                            genero,
                            cargo,
                            formacao,
                            senha_hash,
                            eh_fundador,
                            primeiro_acesso_pendente,
                            notificacao_primeiro_acesso_enviada,
                            notificacao_primeiro_acesso_enviada_em,
                            origem_importacao
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt_ins->execute([
                        $nome,
                        $sobrenome,
                        $cpf,
                        $email,
                        $celular,
                        $data_nascimento,
                        $genero,
                        $cargo,
                        $formacao,
                        $senha_hash,
                        1,
                        1,
                        0,
                        null,
                        $origemImportacao
                    ]);

                    $empreendedor_id = (int)$pdo->lastInsertId();
                    $inseridos++;
                }

                if (!empty($empreendedor_id) && !empty($email)) {
                    $stmt_vinc = $pdo->prepare("
                        UPDATE negocios
                        SET empreendedor_id = ?
                        WHERE email_comercial = ?
                          AND empreendedor_id = 17
                    ");
                    $stmt_vinc->execute([$empreendedor_id, $email]);

                    $negocios_vinculados += $stmt_vinc->rowCount();
                }
            }

            $pdo->commit();

            fclose($handle);

            $mensagem = "
                <div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <strong>Importação concluída com sucesso.</strong><br>
                    Arquivo: " . htmlspecialchars($origemImportacao) . "<br>
                    Inseridos: {$inseridos}<br>
                    Atualizados: {$atualizados}<br>
                    Negócios vinculados: {$negocios_vinculados}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>
                </div>
            ";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            fclose($handle);

            $mensagem = "
                <div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <strong>Erro ao processar a importação:</strong><br>
                    " . htmlspecialchars($e->getMessage()) . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>
                </div>
            ";
        }
    } catch (Throwable $e) {
        $mensagem = "
            <div class='alert alert-danger alert-dismissible fade show' role='alert'>
                <strong>Erro no upload:</strong><br>
                " . htmlspecialchars($e->getMessage()) . "
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>
            </div>
        ";
    }
}

$pageTitle = "Importar Base de Empreendedores";
require_once $appBase . '/views/admin/header.php';
?>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Importar Empreendedores</h1>
        <a href="index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
        </a>
    </div>

    <?php if (!empty($mensagem)): ?>
        <div class="mb-4">
            <?= $mensagem ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-file-csv me-1"></i> Upload de Arquivo CSV
            </h6>
        </div>
        <div class="card-body">
            <p class="text-muted mb-2">
                Selecione o arquivo CSV de empreendedores. O sistema executará 3 ações em lote:
            </p>

            <ul class="text-muted mb-4">
                <li>Criará novas contas com senha aleatória hashada no banco.</li>
                <li>Atualizará dados de usuários já existentes, sem alterar a senha.</li>
                <li>Vinculará automaticamente os negócios atribuídos à conta legada ID 17, cruzando o e-mail da conta com o e-mail comercial do negócio.</li>
            </ul>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="arquivo_csv" class="form-label fw-bold">
                        Selecione o arquivo delimitado por ponto e vírgula (;)
                    </label>
                    <input type="file" name="arquivo_csv" id="arquivo_csv" class="form-control" accept=".csv" required>
                </div>

                <hr>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Processar Importação
                    </button>
                    <button type="reset" class="btn btn-light border">
                        Limpar
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require_once $appBase . '/views/admin/footer.php'; ?>