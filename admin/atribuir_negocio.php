<?php
// /public_html/admin/atribuir_negocio.php
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

// CSRF
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];

$errors = [];
$messages = [];
$empreendedorLegadoId = 17; // Mesmo ID que você usou na importação

// -------------------------------------------------------------------
// AJAX: Busca empreendedores pelo nome (para o autocomplete)
// -------------------------------------------------------------------
if (isset($_GET['buscar_empreendedor'])) {
    $termo = '%' . trim($_GET['buscar_empreendedor']) . '%';
    $stmt = $pdo->prepare("SELECT id, nome, sobrenome, email FROM empreendedores 
                           WHERE (nome LIKE ? OR sobrenome LIKE ? OR email LIKE ?)
                           AND id != ?
                           ORDER BY nome ASC LIMIT 10");
    $stmt->execute([$termo, $termo, $termo, $empreendedorLegadoId]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// -------------------------------------------------------------------
// AJAX: Busca negócios do empreendedor legado (ainda não atribuídos)
// -------------------------------------------------------------------
if (isset($_GET['buscar_negocio'])) {
    $termo = '%' . trim($_GET['buscar_negocio']) . '%';
    $stmt = $pdo->prepare("SELECT id, nome_fantasia, cnpj_cpf, categoria FROM negocios 
                           WHERE empreendedor_id = ? AND nome_fantasia LIKE ?
                           ORDER BY nome_fantasia ASC LIMIT 15");
    $stmt->execute([$empreendedorLegadoId, $termo]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// -------------------------------------------------------------------
// POST: Salva a atribuição
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_csrf   = $_POST['csrf_token'] ?? '';
    $negocio_id    = (int)($_POST['negocio_id'] ?? 0);
    $empreendedor_id = (int)($_POST['empreendedor_id'] ?? 0);

    if (!hash_equals($csrf, $posted_csrf)) {
        $errors[] = "Requisição inválida.";
    } elseif ($negocio_id <= 0 || $empreendedor_id <= 0) {
        $errors[] = "Selecione um negócio e um empreendedor válidos.";
    } else {
        try {
            // Busca os nomes para a mensagem de confirmação
            $stmtN = $pdo->prepare("SELECT nome_fantasia FROM negocios WHERE id = ? LIMIT 1");
            $stmtN->execute([$negocio_id]);
            $nomeNegocio = $stmtN->fetchColumn();

            $stmtE = $pdo->prepare("SELECT CONCAT(nome, ' ', sobrenome) AS nome_completo FROM empreendedores WHERE id = ? LIMIT 1");
            $stmtE->execute([$empreendedor_id]);
            $nomeEmp = $stmtE->fetchColumn();

            // Atualiza o dono do negócio
            $stmtUpdate = $pdo->prepare("UPDATE negocios SET empreendedor_id = ? WHERE id = ?");
            $stmtUpdate->execute([$empreendedor_id, $negocio_id]);

            $messages[] = "✅ Negócio <strong>$nomeNegocio</strong> atribuído com sucesso para <strong>$nomeEmp</strong>!";
        } catch (PDOException $e) {
            $errors[] = "Erro ao atribuir: " . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------------
// Conta quantos negócios ainda estão no empreendedor legado
// -------------------------------------------------------------------
$stmtPendentes = $pdo->prepare("SELECT COUNT(*) FROM negocios WHERE empreendedor_id = ?");
$stmtPendentes->execute([$empreendedorLegadoId]);
$totalPendentes = $stmtPendentes->fetchColumn();

$pageTitle = "Atribuir Negócios a Empreendedores";
require_once $appBase . '/views/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-person-check"></i> Atribuir Negócios</h2>
        <span class="text-muted small">
            <i class="bi bi-hourglass-split text-warning"></i>
            <strong><?= $totalPendentes ?></strong> negócios ainda aguardando atribuição.
        </span>
    </div>
    <a href="/admin/negocios.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<?php if(!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $err): ?><li><?= $err ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php if(!empty($messages)): ?>
    <div class="alert alert-success"><ul class="mb-0"><?php foreach($messages as $msg): ?><li><?= $msg ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="" id="formAtribuir">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="negocio_id" id="negocio_id" value="">
            <input type="hidden" name="empreendedor_id" id="empreendedor_id" value="">

            <div class="row g-4">
                <!-- Coluna: Buscar Negócio -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        <i class="bi bi-briefcase text-primary me-1"></i>Negócio (da base legada)
                    </label>
                    <input type="text" id="buscaNegocio" class="form-control" 
                           placeholder="Digite o nome do negócio..." autocomplete="off">
                    <div id="listaNegocio" class="list-group mt-1 shadow-sm" style="position:absolute; z-index:999; min-width:300px;"></div>
                    
                    <!-- Card com dados do negócio selecionado -->
                    <div id="negocioSelecionado" class="card border-primary mt-2 d-none">
                        <div class="card-body py-2">
                            <p class="mb-0 fw-bold" id="negocioNome"></p>
                            <p class="mb-0 small text-muted" id="negocioCnpj"></p>
                            <p class="mb-0 small text-muted" id="negocioCategoria"></p>
                        </div>
                    </div>
                </div>

                <!-- Coluna: Buscar Empreendedor -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        <i class="bi bi-person text-success me-1"></i>Empreendedor Destino
                    </label>
                    <input type="text" id="buscaEmpreendedor" class="form-control" 
                           placeholder="Digite o nome ou e-mail..." autocomplete="off">
                    <div id="listaEmpreendedor" class="list-group mt-1 shadow-sm" style="position:absolute; z-index:999; min-width:300px;"></div>

                    <!-- Card com dados do empreendedor selecionado -->
                    <div id="empreendedorSelecionado" class="card border-success mt-2 d-none">
                        <div class="card-body py-2">
                            <p class="mb-0 fw-bold" id="empNome"></p>
                            <p class="mb-0 small text-muted" id="empEmail"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary px-5" id="btnAtribuir" disabled>
                    <i class="bi bi-link-45deg me-1"></i> Atribuir Negócio ao Empreendedor
                </button>
                <span class="text-muted small ms-3" id="statusSelecao">Selecione um negócio e um empreendedor para continuar.</span>
            </div>
        </form>
    </div>
</div>

<script>
// Controle de seleção
let negocioOk = false, empOk = false;

function verificaBotao() {
    const btn = document.getElementById('btnAtribuir');
    const status = document.getElementById('statusSelecao');
    if (negocioOk && empOk) {
        btn.disabled = false;
        status.textContent = '✅ Pronto! Clique para confirmar a atribuição.';
        status.className = 'text-success small ms-3 fw-bold';
    } else {
        btn.disabled = true;
        status.textContent = 'Selecione um negócio e um empreendedor para continuar.';
        status.className = 'text-muted small ms-3';
    }
}

// BUSCA NEGÓCIO (Autocomplete)
let timerNeg;
document.getElementById('buscaNegocio').addEventListener('input', function() {
    clearTimeout(timerNeg);
    const val = this.value.trim();
    if (val.length < 2) { document.getElementById('listaNegocio').innerHTML = ''; return; }
    timerNeg = setTimeout(() => {
        fetch('?buscar_negocio=' + encodeURIComponent(val))
            .then(r => r.json())
            .then(data => {
                const lista = document.getElementById('listaNegocio');
                lista.innerHTML = '';
                if (data.length === 0) {
                    lista.innerHTML = '<span class="list-group-item text-muted small">Nenhum negócio encontrado na base legada.</span>';
                    return;
                }
                data.forEach(n => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'list-group-item list-group-item-action py-2';
                    btn.innerHTML = `<strong>${n.nome_fantasia}</strong> <span class="badge bg-light text-dark ms-1">${n.categoria}</span><br><small class="text-muted">${n.cnpj_cpf}</small>`;
                    btn.addEventListener('click', () => {
                        document.getElementById('negocio_id').value = n.id;
                        document.getElementById('buscaNegocio').value = n.nome_fantasia;
                        document.getElementById('negocioNome').textContent = n.nome_fantasia;
                        document.getElementById('negocioCnpj').textContent = 'CNPJ/CPF: ' + n.cnpj_cpf;
                        document.getElementById('negocioCategoria').textContent = 'Categoria: ' + n.categoria;
                        document.getElementById('negocioSelecionado').classList.remove('d-none');
                        lista.innerHTML = '';
                        negocioOk = true;
                        verificaBotao();
                    });
                    lista.appendChild(btn);
                });
            });
    }, 300);
});

// BUSCA EMPREENDEDOR (Autocomplete)
let timerEmp;
document.getElementById('buscaEmpreendedor').addEventListener('input', function() {
    clearTimeout(timerEmp);
    const val = this.value.trim();
    if (val.length < 2) { document.getElementById('listaEmpreendedor').innerHTML = ''; return; }
    timerEmp = setTimeout(() => {
        fetch('?buscar_empreendedor=' + encodeURIComponent(val))
            .then(r => r.json())
            .then(data => {
                const lista = document.getElementById('listaEmpreendedor');
                lista.innerHTML = '';
                if (data.length === 0) {
                    lista.innerHTML = '<span class="list-group-item text-muted small">Nenhum empreendedor encontrado.</span>';
                    return;
                }
                data.forEach(e => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'list-group-item list-group-item-action py-2';
                    btn.innerHTML = `<strong>${e.nome} ${e.sobrenome}</strong><br><small class="text-muted">${e.email}</small>`;
                    btn.addEventListener('click', () => {
                        document.getElementById('empreendedor_id').value = e.id;
                        document.getElementById('buscaEmpreendedor').value = e.nome + ' ' + e.sobrenome;
                        document.getElementById('empNome').textContent = e.nome + ' ' + e.sobrenome;
                        document.getElementById('empEmail').textContent = e.email;
                        document.getElementById('empreendedorSelecionado').classList.remove('d-none');
                        lista.innerHTML = '';
                        empOk = true;
                        verificaBotao();
                    });
                    lista.appendChild(btn);
                });
            });
    }, 300);
});

// Fecha os dropdowns ao clicar fora
document.addEventListener('click', function(e) {
    if (!e.target.closest('#buscaNegocio')) document.getElementById('listaNegocio').innerHTML = '';
    if (!e.target.closest('#buscaEmpreendedor')) document.getElementById('listaEmpreendedor').innerHTML = '';
});
</script>

<?php require_once $appBase . '/views/admin/footer.php'; ?>
