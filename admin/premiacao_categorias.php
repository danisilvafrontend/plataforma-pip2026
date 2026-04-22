<?php
declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

$appBase = dirname(__DIR__) . '/app';

$config = require $appBase . '/config/db.php';

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;port=%s;charset=%s',
    $config['host'],
    $config['dbname'],
    $config['port'],
    $config['charset']
);

$opts = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $opts);
} catch (PDOException $e) {
    die('Erro na conexão com o banco: ' . $e->getMessage());
}


function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

const CATEGORIAS_NEGOCIO = [
    'Ideação',
    'Tração/Escala',
    'Operação',
    'Dinamizador',
];

$pageTitle = 'Premiação - Categorias';
$mensagem  = '';
$erro      = '';

$premiacaoid = (int)($_GET['premiacao_id'] ?? 0);

$premiacoes = $pdo->query("SELECT id, nome, ano FROM premiacoes ORDER BY ano DESC")->fetchAll();

if ($premiacaoid === 0 && !empty($premiacoes)) {
    foreach ($premiacoes as $p) {
        // tenta pegar a ativa
    }
    $premiacaoid = (int)$premiacoes[0]['id'];
}

$premiacaoAtual = null;
if ($premiacaoid > 0) {
    $s = $pdo->prepare("SELECT * FROM premiacoes WHERE id = ? LIMIT 1");
    $s->execute([$premiacaoid]);
    $premiacaoAtual = $s->fetch();
}

$catId = (int)($_GET['editar'] ?? 0);
$catForm = [
    'id'          => 0,
    'premiacaoid' => $premiacaoid,
    'nome'        => '',
    'slug'        => '',
    'ordem'       => 0,
    'ativo'       => 1,
];

if ($catId > 0) {
    $s = $pdo->prepare("SELECT * FROM premiacaocategorias WHERE id = ? LIMIT 1");
    $s->execute([$catId]);
    $found = $s->fetch();
    if ($found) {
        $catForm     = $found;
        $premiacaoid = (int)$found['premiacaoid'];
    }
}

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? 'salvar';

    if ($action === 'excluir') {
        $delId = (int)($_POST['del_id'] ?? 0);
        if ($delId > 0) {
            $pdo->prepare("DELETE FROM premiacaocategorias WHERE id = ?")->execute([$delId]);
            $premiacaoid = (int)($_POST['premiacao_id'] ?? $premiacaoid);
            header("Location: premiacao_categorias.php?premiacao_id={$premiacaoid}&ok=" . urlencode('Categoria excluída.'));
            exit;
        }
    }

    try {
        $id          = (int)($_POST['id'] ?? 0);
        $premiacaoid = (int)($_POST['premiacao_id'] ?? 0);
        $nome        = trim($_POST['nome'] ?? '');
        $slug        = trim($_POST['slug'] ?? '');
        $ordem       = (int)($_POST['ordem'] ?? 0);
        $ativo       = (int)($_POST['ativo'] ?? 1);

        if ($premiacaoid <= 0) throw new Exception('Selecione uma edição da premiação.');
        if ($nome === '')      throw new Exception('Informe o nome da categoria.');
        if ($slug === '')      throw new Exception('Informe o slug da categoria.');

        $sSlug = $pdo->prepare("SELECT COUNT(*) FROM premiacaocategorias WHERE slug = ? AND premiacaoid = ? AND id <> ?");
        $sSlug->execute([$slug, $premiacaoid, $id]);
        if ((int)$sSlug->fetchColumn() > 0) {
            throw new Exception('Já existe uma categoria com este slug nesta edição.');
        }

        if ($id > 0) {
            $pdo->prepare("
                UPDATE premiacaocategorias
                SET nome = ?, slug = ?, ordem = ?, ativo = ?, updatedat = NOW()
                WHERE id = ?
            ")->execute([$nome, $slug, $ordem, $ativo, $id]);
            $msg = 'Categoria atualizada com sucesso.';
        } else {
            $pdo->prepare("
                INSERT INTO premiacaocategorias
                    (premiacaoid, nome, slug, ordem, ativo, createdat, updatedat)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ")->execute([$premiacaoid, $nome, $slug, $ordem, $ativo]);
            $msg = 'Categoria cadastrada com sucesso.';
        }

        header("Location: premiacao_categorias.php?premiacao_id={$premiacaoid}&ok=" . urlencode($msg));
        exit;

    } catch (Throwable $e) {
        $erro = $e->getMessage();
        $catForm = [
            'id'          => (int)($_POST['id'] ?? 0),
            'premiacaoid' => (int)($_POST['premiacao_id'] ?? $premiacaoid),
            'nome'        => $_POST['nome'] ?? '',
            'slug'        => $_POST['slug'] ?? '',
            'ordem'       => (int)($_POST['ordem'] ?? 0),
            'ativo'       => (int)($_POST['ativo'] ?? 1),
        ];
        $premiacaoid = $catForm['premiacaoid'];
    }
}

if (isset($_GET['ok']) && $_GET['ok'] !== '') {
    $mensagem = trim($_GET['ok']);
}

$categorias = [];
if ($premiacaoid > 0) {
    $s = $pdo->prepare("
        SELECT * FROM premiacao_categorias
        WHERE premiacaoid = ?
        ORDER BY ordem ASC, nome ASC
    ");
    $s->execute([$premiacaoid]);
    $categorias = $s->fetchAll();
}

$nomesJaCadastrados = array_column($categorias, 'nome');

// Valores distintos em negocios.categoria ainda sem categoria cadastrada
$nomesNoBanco = $pdo->query("
    SELECT DISTINCT categoria FROM negocios
    WHERE categoria IS NOT NULL AND categoria <> ''
    ORDER BY categoria
")->fetchAll(PDO::FETCH_COLUMN);
$semCategoria = array_diff($nomesNoBanco, $nomesJaCadastrados);

require_once $appBase . '/views/admin/header.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-1">Premiação — Categorias</h1>
            <p class="text-muted mb-0">Defina as categorias de cada edição.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="premiacao_edicoes.php" class="btn btn-outline-secondary btn-sm">← Edições</a>
        </div>
    </div>

    <?php if ($mensagem !== ''): ?>
        <div class="alert alert-success"><?= h($mensagem) ?></div>
    <?php endif; ?>
    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger"><?= h($erro) ?></div>
    <?php endif; ?>

    <!-- Seletor de edição -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label mb-1">Edição da premiação</label>
                    <select name="premiacao_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($premiacoes as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $premiacaoid ? 'selected' : '' ?>>
                                <?= h($p['nome']) ?> (<?= (int)$p['ano'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">

        <!-- Formulário -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h3 class="mb-0"><?= (int)$catForm['id'] > 0 ? 'Editar categoria' : 'Nova categoria' ?></h3>
                </div>
                <div class="card-body">

                    <?php if ((int)$catForm['id'] === 0): ?>
                    <div class="mb-3 p-3 bg-light rounded border">
                        <p class="fw-semibold mb-2 small">Atalho — categorias dos negócios:</p>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach (CATEGORIAS_NEGOCIO as $i => $cn): ?>
                                <?php
                                    $jaCad = in_array($cn, $nomesJaCadastrados, true);
                                    $slugCn = strtolower(iconv('UTF-8','ASCII//TRANSLIT', $cn));
                                    $slugCn = preg_replace('/[^a-z0-9]+/', '-', $slugCn);
                                    $slugCn = trim($slugCn, '-');
                                ?>
                                <button type="button"
                                    class="btn btn-sm <?= $jaCad ? 'btn-success' : 'btn-outline-secondary' ?>"
                                    <?= $jaCad ? 'disabled title="Já cadastrada"' : '' ?>
                                    onclick="preencherForm('<?= h($cn) ?>', '<?= h($slugCn) ?>', <?= $i + 1 ?>)">
                                    <?= $jaCad ? '✓ ' : '' ?><?= h($cn) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="_action"      value="salvar">
                        <input type="hidden" name="id"           value="<?= (int)$catForm['id'] ?>">
                        <input type="hidden" name="premiacao_id" value="<?= $premiacaoid ?>">

                        <div class="mb-3">
                            <label class="form-label">Nome da categoria</label>
                            <input type="text" id="inputNome" name="nome" class="form-control"
                                value="<?= h($catForm['nome']) ?>"
                                placeholder="Ex.: Tração/Escala" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" id="inputSlug" name="slug" class="form-control"
                                value="<?= h($catForm['slug']) ?>"
                                placeholder="Ex.: tracao-escala" required>
                            <div class="form-text">Somente letras minúsculas, números e hífens.</div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Ordem de exibição</label>
                                <input type="number" name="ordem" class="form-control" min="0"
                                    value="<?= (int)$catForm['ordem'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="ativo" class="form-select">
                                    <option value="1" <?= (int)($catForm['ativo'] ?? 1) === 1 ? 'selected' : '' ?>>Ativa</option>
                                    <option value="0" <?= (int)($catForm['ativo'] ?? 1) === 0 ? 'selected' : '' ?>>Inativa</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="premiacao_categorias.php?premiacao_id=<?= $premiacaoid ?>" class="btn btn-outline-secondary">Limpar</a>
                            <button type="submit" class="btn btn-success">
                                <?= (int)$catForm['id'] > 0 ? 'Salvar alterações' : 'Salvar categoria' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Listagem -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Categorias cadastradas</h3>
                    <?php if ($premiacaoAtual): ?>
                        <span class="badge bg-secondary"><?= h($premiacaoAtual['nome']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($categorias)): ?>
                        <div class="alert alert-light border mb-0">
                            Nenhuma categoria cadastrada para esta edição.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nome</th>
                                        <th>Slug</th>
                                        <th>Ordem</th>
                                        <th>Ativo</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categorias as $cat): ?>
                                        <tr>
                                            <td><?= (int)$cat['id'] ?></td>
                                            <td class="fw-semibold"><?= h($cat['nome']) ?></td>
                                            <td><code><?= h($cat['slug']) ?></code></td>
                                            <td><?= (int)$cat['ordem'] ?></td>
                                            <td><?= (int)$cat['ativo'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></td>
                                            <td class="text-end">
                                                <a href="premiacao_categorias.php?premiacao_id=<?= $premiacaoid ?>&editar=<?= (int)$cat['id'] ?>"
                                                   class="btn btn-sm btn-outline-primary">Editar</a>
                                                <form method="post" class="d-inline"
                                                      onsubmit="return confirm('Excluir a categoria «<?= h($cat['nome']) ?>»?')">
                                                    <input type="hidden" name="_action"      value="excluir">
                                                    <input type="hidden" name="del_id"       value="<?= (int)$cat['id'] ?>">
                                                    <input type="hidden" name="premiacao_id" value="<?= $premiacaoid ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($semCategoria)): ?>
            <div class="alert alert-warning mt-3">
                <strong>⚠ Atenção:</strong> Valores em <code>negocios.categoria</code> sem categoria cadastrada nesta edição:
                <ul class="mb-0 mt-1">
                    <?php foreach ($semCategoria as $sc): ?>
                        <li><code><?= h($sc) ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function preencherForm(nome, slug, ordem) {
    document.getElementById('inputNome').value = nome;
    document.getElementById('inputSlug').value = slug;
    document.querySelector('[name="ordem"]').value = ordem;
    document.getElementById('inputNome').focus();
}

document.getElementById('inputNome').addEventListener('input', function () {
    const slugField = document.getElementById('inputSlug');
    if (slugField.dataset.manual === '1') return;
    slugField.value = this.value
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
});
document.getElementById('inputSlug').addEventListener('input', function () {
    this.dataset.manual = '1';
});
</script>

<?php require_once $appBase . '/views/admin/footer.php'; ?>