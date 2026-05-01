<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

$appBase = dirname(__DIR__) . '/app';
$config  = require $appBase . '/config/db.php';

$dsn  = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s',
    $config['host'], $config['dbname'], $config['port'], $config['charset']);
$opts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $opts);
} catch (PDOException $e) {
    die('Erro na conexão com o banco: ' . $e->getMessage());
}


$pageTitle = 'Premiação - Edições';
$mensagem = '';
$erro = '';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function badgeStatusEdicao(string $status): string
{
    $map = [
        'planejada' => ['#fff3cd', '#856404', 'Planejada'],
        'ativa'     => ['#CDDE00', '#1E3425', 'Ativa'],
        'encerrada' => ['#fde8ea', '#842029', 'Encerrada'],
        'inativa'   => ['#e2e3e5', '#41464b', 'Inativa'],
    ];

    [$bg, $color, $label] = $map[$status] ?? ['#e2e3e5', '#41464b', ucfirst($status)];

    return '<span style="display:inline-flex;align-items:center;padding:.25rem .65rem;border-radius:999px;background:' . h($bg) . ';color:' . h($color) . ';font-size:.78rem;font-weight:700;">' . h($label) . '</span>';
}

$edicaoId = (int)($_GET['editar'] ?? 0);

$edicaoForm = [
    'id' => 0,
    'nome' => '',
    'slug' => '',
    'ano' => '',
    'regulamento_url' => '',
    'status' => 'planejada',
];

if ($edicaoId > 0) {
    $stmtEd = $pdo->prepare("SELECT * FROM premiacoes WHERE id = ? LIMIT 1");
    $stmtEd->execute([$edicaoId]);
    $edicaoEncontrada = $stmtEd->fetch();
    if ($edicaoEncontrada) {
        $edicaoForm = $edicaoEncontrada;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $ano = (int)($_POST['ano'] ?? 0);
        $status = trim($_POST['status'] ?? 'planejada');
        $regulamento_url = trim($_POST['regulamento_url'] ?? '');

        if ($nome === '') {
            throw new Exception('Informe o nome da edição.');
        }

        if ($slug === '') {
            throw new Exception('Informe o slug da edição.');
        }

        if ($ano < 2024 || $ano > 2100) {
            throw new Exception('Informe um ano válido.');
        }

        if (!in_array($status, ['planejada', 'ativa', 'encerrada', 'inativa'], true)) {
            throw new Exception('Status inválido.');
        }

        $stmtSlug = $pdo->prepare("SELECT COUNT(*) FROM premiacoes WHERE slug = ? AND id <> ?");
        $stmtSlug->execute([$slug, $id]);
        if ((int)$stmtSlug->fetchColumn() > 0) {
            throw new Exception('Já existe uma edição com este slug.');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE premiacoes
                SET nome = ?, slug = ?, ano = ?, regulamento_url = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $nome,
                $slug,
                $ano,
                $regulamento_url !== '' ? $regulamento_url : null,
                $status,
                $id
            ]);

            $mensagem = 'Edição da premiação atualizada com sucesso.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO premiacoes (
                    nome, slug, ano, regulamento_url, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $nome,
                $slug,
                $ano,
                $regulamento_url !== '' ? $regulamento_url : null,
                $status
            ]);

            $mensagem = 'Edição da premiação cadastrada com sucesso.';
        }

        header('Location: premiacao_edicoes.php?ok=' . urlencode($mensagem));
        exit;
    } catch (Throwable $e) {
        $erro = $e->getMessage();

        $edicaoForm = [
            'id' => (int)($_POST['id'] ?? 0),
            'nome' => $_POST['nome'] ?? '',
            'slug' => $_POST['slug'] ?? '',
            'ano' => $_POST['ano'] ?? '',
            'regulamento_url' => $_POST['regulamento_url'] ?? '',
            'status' => $_POST['status'] ?? 'planejada',
        ];
    }
}

if (isset($_GET['ok']) && $_GET['ok'] !== '') {
    $mensagem = trim($_GET['ok']);
}

$stmt = $pdo->query("
    SELECT id, nome, slug, ano, regulamento_url, status, created_at
    FROM premiacoes
    ORDER BY ano DESC, id DESC
");
$edicoes = $stmt->fetchAll();

require_once $appBase . '/views/admin/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h3 mb-1">Premiação - Edições</h1>
            <p class="text-muted mb-0">Cadastre e gerencie os dados institucionais das edições da premiação.</p>
        </div>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-success"><?= h($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= h($erro) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <strong><?= (int)$edicaoForm['id'] > 0 ? 'Editar edição' : 'Nova edição' ?></strong>
        </div>
        <div class="card-body">
            <form method="post" action="premiacao_edicoes.php">
                <input type="hidden" name="id" value="<?= (int)$edicaoForm['id'] ?>">

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Nome da edição</label>
                        <input type="text" name="nome" class="form-control" required
                               value="<?= h($edicaoForm['nome'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" required
                               value="<?= h($edicaoForm['slug'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Ano</label>
                        <input type="number" name="ano" class="form-control" min="2024" max="2100" required
                               value="<?= h((string)($edicaoForm['ano'] ?? '')) ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <?php
                            $statusAtual = (string)($edicaoForm['status'] ?? 'planejada');
                            foreach (['planejada', 'ativa', 'encerrada', 'inativa'] as $statusItem):
                            ?>
                                <option value="<?= h($statusItem) ?>" <?= $statusAtual === $statusItem ? 'selected' : '' ?>>
                                    <?= ucfirst($statusItem) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">URL do regulamento</label>
                        <input type="url" name="regulamento_url" class="form-control"
                               value="<?= h($edicaoForm['regulamento_url'] ?? '') ?>">
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-dark">
                        <?= (int)$edicaoForm['id'] > 0 ? 'Salvar alterações' : 'Cadastrar edição' ?>
                    </button>

                    <?php if ((int)$edicaoForm['id'] > 0): ?>
                        <a href="premiacao_edicoes.php" class="btn btn-outline-secondary">Cancelar edição</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <strong>Edições cadastradas</strong>
        </div>
        <div class="card-body">
            <?php if (empty($edicoes)): ?>
                <p class="text-muted mb-0">Nenhuma edição cadastrada.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Edição</th>
                                <th>Status</th>
                                <th>Regulamento</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($edicoes as $ed): ?>
                            <tr>
                                <td><?= (int)$ed['id'] ?></td>
                                <td>
                                    <strong><?= h($ed['nome']) ?></strong><br>
                                    <small class="text-muted">Ano <?= (int)$ed['ano'] ?> · <?= h($ed['slug']) ?></small>
                                </td>
                                <td><?= badgeStatusEdicao((string)$ed['status']) ?></td>
                                <td>
                                    <?php if (!empty($ed['regulamento_url'])): ?>
                                        <a href="<?= h($ed['regulamento_url']) ?>" target="_blank" rel="noopener noreferrer">Abrir</a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="d-flex gap-2 flex-wrap">
                                    <a href="premiacao_edicoes.php?editar=<?= (int)$ed['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <a href="premiacao_periodos.php?premiacao_id=<?= (int)$ed['id'] ?>" class="btn btn-sm btn-outline-dark">Editar fases</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once $appBase . '/views/admin/footer.php'; ?>