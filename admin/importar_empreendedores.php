<?php
// /public_html/admin/importar_empreendedores.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

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

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['arquivo_csv'])) {
    $arquivo = $_FILES['arquivo_csv']['tmp_name'];

    if (($handle = fopen($arquivo, "r")) !== FALSE) {
        
        $header = fgetcsv($handle, 1000, ";");
        // Remove BOM e caracteres invisíveis do cabeçalho
        $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);

        $inseridos = 0;
        $atualizados = 0;
        $negocios_vinculados = 0;

        $pdo->beginTransaction();

        try {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                if (count($data) < 9) continue;

                $nome           = trim($data[0]);
                $sobrenome      = trim($data[1]);
                $cpf_original   = trim($data[2]);
                $email          = trim($data[3]);
                $celular        = trim($data[4]);
                $data_nasc_raw  = trim($data[5]);
                $genero         = trim($data[6]);
                $cargo          = trim($data[7]);
                $formacao       = trim($data[8]);

                $cpf = preg_replace('/[^0-9]/', '', $cpf_original);
                if (empty($cpf)) continue; 

                // Formata Data de 25/04/1976 00:00 para YYYY-MM-DD
                $data_nascimento = null;
                if (!empty($data_nasc_raw)) {
                    $partes_data = explode(' ', $data_nasc_raw);
                    $d_m_y = explode('/', $partes_data[0]);
                    if (count($d_m_y) == 3) {
                        $data_nascimento = $d_m_y[2] . '-' . $d_m_y[1] . '-' . $d_m_y[0];
                    }
                }

                // Verifica se Empreendedor já existe
                $stmt_check = $pdo->prepare("SELECT id FROM empreendedores WHERE cpf = ? OR email = ?");
                $stmt_check->execute([$cpf, $email]);
                $row = $stmt_check->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                // Atualiza dados na tabela empreendedores
                $empreendedor_id = $row['id'];
                // Adicionado eh_fundador=? na query
                $stmt_upd = $pdo->prepare("UPDATE empreendedores SET nome=?, sobrenome=?, email=?, celular=?, data_nascimento=?, genero=?, cargo=?, formacao=?, eh_fundador=? WHERE id=?");
                
                // Adicionado o valor 1 na lista de execução, antes do ID
                $stmt_upd->execute([$nome, $sobrenome, $email, $celular, $data_nascimento, $genero, $cargo, $formacao, 1, $empreendedor_id]);
                
                if ($stmt_upd->rowCount() > 0) $atualizados++;
            } else {
                // Insere novo empreendedor
                $senha_hash = password_hash('Mudar@1234', PASSWORD_DEFAULT);
                
                // Adicionado eh_fundador nas colunas e mais um ? nos VALUES
                $stmt_ins = $pdo->prepare("INSERT INTO empreendedores (nome, sobrenome, cpf, email, celular, data_nascimento, genero, cargo, formacao, senha_hash, eh_fundador) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                // Adicionado o valor 1 no final da lista
                $stmt_ins->execute([$nome, $sobrenome, $cpf, $email, $celular, $data_nascimento, $genero, $cargo, $formacao, $senha_hash, 1]);
                
                $empreendedor_id = $pdo->lastInsertId();
                $inseridos++;
            }


                // Vincular negócios do ID 17 para este empreendedor utilizando o E-MAIL
                // Coluna atualizada para 'email_comercial'
                if ($empreendedor_id && !empty($email)) {
                    $stmt_vinc = $pdo->prepare("UPDATE negocios SET empreendedor_id = ? WHERE email_comercial = ? AND empreendedor_id = 17");
                    $stmt_vinc->execute([$empreendedor_id, $email]);
                    
                    $negocios_vinculados += $stmt_vinc->rowCount();
                }
            }

            $pdo->commit();
            $mensagem = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <i class='fas fa-check-circle me-1'></i> Importação concluída com sucesso!<br>
                            <strong>Empreendedores Inseridos:</strong> {$inseridos}<br>
                            <strong>Empreendedores Atualizados:</strong> {$atualizados}<br>
                            <strong>Negócios Vinculados:</strong> {$negocios_vinculados}
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                         </div>";

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            <i class='fas fa-exclamation-triangle me-1'></i> Erro durante a importação: " . htmlspecialchars($e->getMessage()) . "
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                         </div>";
        }
        fclose($handle);
    } else {
        $mensagem = "<div class='alert alert-danger'>Erro ao abrir o arquivo CSV.</div>";
    }
}

$pageTitle = "Importar Base de Empreendedores";
require_once $appBase . '/views/admin/header.php';
?>


<!-- Conteúdo Principal -->
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
            <p class="text-muted mb-4">
                Selecione o arquivo CSV de empreendedores. O sistema executará 3 ações em lote:
                <ul>
                    <li>Criará novas contas (com senha padrão <code>Mudar@1234</code>);</li>
                    <li>Atualizará dados de usuários já existentes (sem alterar a senha);</li>
                    <li>Vinculará automaticamente os negócios atribuídos à conta legada (ID 17) para estes usuários, cruzando o E-mail da conta com o <strong>E-mail Comercial</strong> do negócio.</li>
                </ul>
            </p>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="arquivo_csv" class="form-label fw-bold">Selecione o arquivo delimitado por ponto e vírgula (;)</label>
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
