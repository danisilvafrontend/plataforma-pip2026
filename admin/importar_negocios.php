<?php
// /public_html/admin/importar_negocios.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

$possibleAppPaths = [
    __DIR__ . '/../app',
    __DIR__ . '/../../app',
    __DIR__ . '/app',
];
$appBase = null;
foreach ($possibleAppPaths as $p) if (is_dir($p)) { $appBase = realpath($p); break; }
if ($appBase === null) { die("Erro: pasta app não encontrada."); }

require_once $appBase . '/helpers/auth.php';
require_admin_login();
$config = require $appBase . '/config/db.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados.");
}

$errors = [];
$messages = [];
$empreendedorLegadoId = 1; // ID do Empreendedor Genérico (Mude se necessário)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo_csv'])) {
    $file = $_FILES['arquivo_csv'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Erro no upload. Código: " . $file['error'];
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $errors[] = "Por favor, envie um arquivo .csv";
        } else {
            $handle = fopen($file['tmp_name'], "r");
            
            if ($handle !== FALSE) {
                // Tenta detectar o delimitador (, ou ;)
                $firstLine = fgets($handle);
                $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
                rewind($handle); // Volta pro começo
                
                fgetcsv($handle, 1000, $delimiter); // Pula a linha do cabeçalho
                
                $linhasNovas = 0;
                $linhasAtualizadas = 0;
                $linhasErro = 0;
                $erroDb = ''; 

                               // PREPARAÇÃO DAS QUERIES
                                $stmtEmpreendedor = $pdo->prepare("SELECT id FROM empreendedores WHERE email = ? LIMIT 1");
                                $stmtCheck = $pdo->prepare("SELECT id FROM negocios WHERE nome_fantasia = ? AND cnpj_cpf = ? LIMIT 1");

                                // UPDATE: Força etapa_atual = 1, atualiza a categoria e demais campos
                                $stmtUpdate = $pdo->prepare("
                                    UPDATE negocios 
                                    SET razao_social = ?, categoria = ?, email_comercial = ?, data_fundacao = ?, etapa_atual = 1, updated_at = NOW() 
                                    WHERE id = ?
                                ");

                                // INSERT: Cria o negócio já na etapa_atual = 1
                                $stmtInsert = $pdo->prepare("
                                    INSERT INTO negocios (empreendedor_id, nome_fantasia, razao_social, categoria, cnpj_cpf, email_comercial, data_fundacao, formato_legal, setor, etapa_atual, created_at)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, 'A definir', 'A definir', 1, NOW())
                                ");


                        // (Foi removido o mapeamento de etapas $etapamap, pois todos vão para etapa 1)

                        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                            // Colunas: email, nome_negocio, razao_social, categoria, cnpj, data_fundacao
                            $emailcsv     = trim($data[0] ?? '');
                            $nomefantasia = trim($data[1] ?? '');
                            $razaosocial  = trim($data[2] ?? '');
                            $categoria    = trim($data[3] ?? ''); // Usando como Categoria corretamente
                            $cnpjbruto    = trim($data[4] ?? '');
                            $dataraw      = trim($data[5] ?? '');

                            // Limpa CNPJ/CPF: remove tudo que não for número
                            $cnpjcpf = preg_replace('/[^0-9]/', '', $cnpjbruto);

                            // Regras de fallback
                            if (empty($razaosocial)) {
                                $razaosocial = $nomefantasia;
                            }
                            if (empty($categoria)) {
                                $categoria = 'Outros';
                            }

                            // Tratamento da Data de Fundação
                            $datafundacao = null;
                            if (!empty($dataraw)) {
                                try {
                                    $dt = DateTime::createFromFormat('d/m/Y H:i', trim($dataraw));
                                    if (!$dt) $dt = DateTime::createFromFormat('Y-m-d H:i:s', trim($dataraw));
                                    if (!$dt) $dt = DateTime::createFromFormat('Y-m-d', substr(trim($dataraw), 0, 10));

                                    if ($dt) {
                                        $datafundacao = $dt->format('Y-m-d');
                                    }
                                } catch (Exception $e) {
                                    $datafundacao = null;
                                }
                            }

                            // Resolve o Empreendedor pelo E-mail
                            $empreendedorId = $empreendedorLegadoId;
                            if (!empty($emailcsv)) {
                                try {
                                    $stmtEmpreendedor->execute([$emailcsv]);
                                    $idFound = $stmtEmpreendedor->fetchColumn();
                                    if ($idFound) {
                                        $empreendedorId = (int)$idFound;
                                    }
                                } catch (PDOException $e) {
                                    // Erro silencioso, mantém o genérico
                                }
                            }

                            // Processamento do BD
                            if (!empty($nomefantasia) && !empty($cnpjcpf)) {
                                try {
                                    $stmtCheck->execute([$nomefantasia, $cnpjcpf]);
                                    $negocioExistente = $stmtCheck->fetchColumn();

                                    if ($negocioExistente) {
                                        // UPDATE (Não atualiza a etapaatual)
                                        $stmtUpdate->execute([
                                            $razaosocial,
                                            $categoria,
                                            $emailcsv,
                                            $datafundacao,
                                            $negocioExistente
                                        ]);
                                        $linhasAtualizadas++;
                                    } else {
                                        // INSERT (Força etapaatual = 1 na query lá em cima)
                                        $stmtInsert->execute([
                                            $empreendedorId,
                                            $nomefantasia,
                                            $razaosocial,
                                            $categoria,
                                            $cnpjcpf,
                                            $emailcsv,
                                            $datafundacao
                                        ]);
                                        $linhasNovas++;
                                    }
                                } catch (PDOException $e) {
                                    $linhasErro++;
                                    $erroDb = $e->getMessage();
                                }
                            } else {
                                // Se array filtrado não for vazio e faltou dado essencial
                                if (array_filter($data)) {
                                    $linhasErro++;
                                }
                            }
                        }
                        fclose($handle);


                // Mensagem final
                $msgFinal = "Importação concluída! $linhasNovas novos projetos adicionados. $linhasAtualizadas projetos existentes atualizados.";
                if ($linhasErro > 0) {
                    $msgFinal .= " ($linhasErro linhas ignoradas por erro ou falta de CNPJ.";
                    if ($erroDb) $msgFinal .= " Último Erro BD: $erroDb";
                    $msgFinal .= ")";
                }
                $messages[] = $msgFinal;

            } else {
                $errors[] = "Não foi possível ler o arquivo CSV.";
            }
        }
    }
}

$pageTitle = "Importar Base de Negócios";
require_once $appBase . '/views/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-file-earmark-spreadsheet"></i> Importar Negócios (Base 2023)</h2>
    <a href="/admin/negocios.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<?php if(!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if(!empty($messages)): ?>
    <div class="alert alert-success"><ul class="mb-0"><?php foreach($messages as $msg): ?><li><?= htmlspecialchars($msg) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-light"><strong>Estrutura da Planilha (.CSV)</strong></div>
    <div class="card-body">
        <p>A planilha deve conter <strong>exatamente 6 colunas</strong> na ordem abaixo. O sistema identificará se o CSV é separado por vírgula (,) ou ponto e vírgula (;).</p>
        <p class="text-primary fw-bold"><i class="bi bi-diagram-3"></i> Sistema de Projetos Múltiplos: A checagem de duplicidade é feita combinando <u>Nome Fantasia + Documento</u>.</p>
        <p class="text-success"><i class="bi bi-person-check"></i> Vínculo: O sistema tenta achar o Empreendedor verificando a 1ª coluna de Email na tabela <code>empreendedores</code>. O email também ficará salvo no perfil comercial do negócio.</p>
        
        <div class="table-responsive">
            <table class="table table-bordered table-sm text-center" style="font-size: 0.9rem;">
                <thead class="table-secondary">
                    <tr>
                        <th>Coluna 1 (E-mail)</th>
                        <th>Coluna 2 (Fantasia)</th>
                        <th>Coluna 3 (Razão)</th>
                        <th>Coluna 4 (categoria)</th>
                        <th>Coluna 5 (CNPJ)</th>
                        <th>Coluna 6 (Data)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="text-muted">
                        <td>contato@empresa.com</td>
                        <td>Padaria XYZ</td>
                        <td>XYZ Alimentos LTDA</td>
                        <td>Tração</td>
                        <td>12.345.678/0001-99</td>
                        <td>25/04/2023 14:00</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="arquivo_csv" class="form-label fw-bold">Selecione o arquivo CSV</label>
                <input class="form-control" type="file" id="arquivo_csv" name="arquivo_csv" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-upload me-1"></i> Iniciar Importação</button>
        </form>
    </div>
</div>

<?php require_once $appBase . '/views/admin/footer.php'; ?>
