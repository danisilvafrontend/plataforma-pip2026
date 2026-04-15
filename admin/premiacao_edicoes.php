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

$pageTitle = 'Premiação - Edições';
$mensagem = '';
$erro = '';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function dataBr(?string $dt): string
{
    if (empty($dt) || $dt === '0000-00-00' || $dt === '0000-00-00 00:00:00') {
        return '—';
    }

    return date('d/m/Y H:i', strtotime($dt));
}

function formatDatetimeLocal(?string $value): string
{
    if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return '';
    }

    $ts = strtotime($value);
    if (!$ts) {
        return '';
    }

    return date('Y-m-d\TH:i', $ts);
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

    return '<span style="
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:6px 10px;
        border-radius:999px;
        background:' . $bg . ';
        color:' . $color . ';
        font-size:12px;
        font-weight:700;
    ">' . h($label) . '</span>';
}

$edicaoId = (int)($_GET['editar'] ?? 0);

$edicaoForm = [
    'id' => 0,
    'nome' => '',
    'slug' => '',
    'ano' => '',
    'regulamento_url' => '',
    'status' => 'planejada',
    'data_inicio_inscricoes' => '',
    'data_fim_inscricoes' => '',
    'data_inicio_votacao' => '',
    'data_fim_votacao' => '',
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
        $id                     = (int)($_POST['id'] ?? 0);
        $nome                   = trim($_POST['nome'] ?? '');
        $slug                   = trim($_POST['slug'] ?? '');
        $ano                    = (int)($_POST['ano'] ?? 0);
        $status                 = trim($_POST['status'] ?? 'planejada');
        $regulamento_url        = trim($_POST['regulamento_url'] ?? '');
        $dataInicioInscricoes   = trim($_POST['data_inicio_inscricoes'] ?? '');
        $dataFimInscricoes      = trim($_POST['data_fim_inscricoes'] ?? '');
        $dataInicioVotacao      = trim($_POST['data_inicio_votacao'] ?? '');
        $dataFimVotacao         = trim($_POST['data_fim_votacao'] ?? '');

        if ($nome === '') {
            throw new Exception('Informe o nome da edição.');
        }

        if ($slug === '') {
            throw new Exception('Informe o slug da edição.');
        }

        if ($ano < 2024 || $ano > 2100) {
            throw new Exception('Informe um ano válido.');
        }

        if ($dataFimInscricoes === '') {
            throw new Exception('Informe a data final das inscrições.');
        }

        if (!in_array($status, ['planejada', 'ativa', 'encerrada', 'inativa'], true)) {
            throw new Exception('Status inválido.');
        }

        if ($dataInicioInscricoes !== '' && strtotime($dataInicioInscricoes) > strtotime($dataFimInscricoes)) {
            throw new Exception('A data de início das inscrições não pode ser maior que a data final.');
        }

        if ($dataInicioVotacao !== '' && $dataFimVotacao !== '' && strtotime($dataInicioVotacao) > strtotime($dataFimVotacao)) {
            throw new Exception('A data de início da votação não pode ser maior que a data final.');
        }

        if ($dataInicioVotacao !== '' && strtotime($dataFimInscricoes) > strtotime($dataInicioVotacao)) {
            throw new Exception('O fim das inscrições deve ser anterior ao início da votação.');
        }

        $stmtSlug = $pdo->prepare("SELECT COUNT(*) FROM premiacoes WHERE slug = ? AND id <> ?");
        $stmtSlug->execute([$slug, $id]);
        if ((int)$stmtSlug->fetchColumn() > 0) {
            throw new Exception('Já existe uma edição com este slug.');
        }

        // $stmtAno = $pdo->prepare("SELECT COUNT(*) FROM premiacoes WHERE ano = ? AND id <> ?");
        // $stmtAno->execute([$ano, $id]);
        // if ((int)$stmtAno->fetchColumn() > 0) {
        //     throw new Exception('Já existe uma edição cadastrada para este ano.');
        // }

        if ($status === 'ativa') {
            $pdo->exec("UPDATE premiacoes SET status = 'inativa' WHERE status = 'ativa'");
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE premiacoes
                SET
                    nome = ?,
                    slug = ?,
                    ano = ?,
                    regulamento_url = ?,
                    status = ?,
                    data_inicio_inscricoes = ?,
                    data_fim_inscricoes = ?,
                    data_inicio_votacao = ?,
                    data_fim_votacao = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $nome,
                $slug,
                $ano,
                $regulamento_url !== '' ? $regulamento_url : null,
                $status,
                $dataInicioInscricoes !== '' ? date('Y-m-d H:i:s', strtotime($dataInicioInscricoes)) : null,
                date('Y-m-d H:i:s', strtotime($dataFimInscricoes)),
                $dataInicioVotacao !== '' ? date('Y-m-d H:i:s', strtotime($dataInicioVotacao)) : null,
                $dataFimVotacao !== '' ? date('Y-m-d H:i:s', strtotime($dataFimVotacao)) : null,
                $id
            ]);

            $mensagem = 'Edição da premiação atualizada com sucesso.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO premiacoes (
                    nome,
                    slug,
                    ano,
                    regulamento_url,
                    status,
                    data_inicio_inscricoes,
                    data_fim_inscricoes,
                    data_inicio_votacao,
                    data_fim_votacao,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $stmt->execute([
                $nome,
                $slug,
                $ano,
                $regulamento_url !== '' ? $regulamento_url : null,
                $status,
                $dataInicioInscricoes !== '' ? date('Y-m-d H:i:s', strtotime($dataInicioInscricoes)) : null,
                date('Y-m-d H:i:s', strtotime($dataFimInscricoes)),
                $dataInicioVotacao !== '' ? date('Y-m-d H:i:s', strtotime($dataInicioVotacao)) : null,
                $dataFimVotacao !== '' ? date('Y-m-d H:i:s', strtotime($dataFimVotacao)) : null,
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
            'data_inicio_inscricoes' => $_POST['data_inicio_inscricoes'] ?? '',
            'data_fim_inscricoes' => $_POST['data_fim_inscricoes'] ?? '',
            'data_inicio_votacao' => $_POST['data_inicio_votacao'] ?? '',
            'data_fim_votacao' => $_POST['data_fim_votacao'] ?? '',
        ];
    }
}

if (isset($_GET['ok']) && $_GET['ok'] !== '') {
    $mensagem = trim($_GET['ok']);
}

$stmt = $pdo->query("
    SELECT
        id,
        nome,
        slug,
        ano,
        regulamento_url,
        status,
        data_inicio_inscricoes,
        data_fim_inscricoes,
        data_inicio_votacao,
        data_fim_votacao,
        created_at
    FROM premiacoes
    ORDER BY ano DESC, id DESC
");
$edicoes = $stmt->fetchAll();

require_once $appBase . '/views/admin/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-1">Premiação - Edições</h1>
            <p class="text-muted mb-0">Cadastre e gerencie as edições anuais da premiação.</p>
        </div>
    </div>

    <?php if ($mensagem !== ''): ?>
        <div class="alert alert-success"><?= h($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger"><?= h($erro) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h3 class="mb-0"><?= (int)$edicaoForm['id'] > 0 ? 'Editar edição' : 'Nova edição' ?></h3>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="id" value="<?= (int)$edicaoForm['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label">Nome da edição</label>
                            <input type="text" name="nome" class="form-control" value="<?= h($edicaoForm['nome']) ?>" placeholder="Ex.: Prêmio Impactos Positivos 2026" required>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="form-control" value="<?= h($edicaoForm['slug']) ?>" placeholder="premio-impactos-positivos-2026" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ano</label>
                                <input type="number" name="ano" class="form-control" min="2024" max="2100" value="<?= h((string)$edicaoForm['ano']) ?>" placeholder="2026" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="planejada" <?= ($edicaoForm['status'] ?? '') === 'planejada' ? 'selected' : '' ?>>Planejada</option>
                                <option value="ativa" <?= ($edicaoForm['status'] ?? '') === 'ativa' ? 'selected' : '' ?>>Ativa</option>
                                <option value="inativa" <?= ($edicaoForm['status'] ?? '') === 'inativa' ? 'selected' : '' ?>>Inativa</option>
                                <option value="encerrada" <?= ($edicaoForm['status'] ?? '') === 'encerrada' ? 'selected' : '' ?>>Encerrada</option>
                            </select>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">URL do regulamento</label>
                            <input type="url" name="regulamento_url" class="form-control" value="<?= h($edicaoForm['regulamento_url']) ?>" placeholder="https://...">
                        </div>

                        <hr>

                        <h5 class="mb-3">Inscrições</h5>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Início das inscrições</label>
                                <input type="datetime-local" name="data_inicio_inscricoes" class="form-control" value="<?= formatDatetimeLocal($edicaoForm['data_inicio_inscricoes']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fim das inscrições</label>
                                <input type="datetime-local" name="data_fim_inscricoes" class="form-control" value="<?= formatDatetimeLocal($edicaoForm['data_fim_inscricoes']) ?>" required>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">Votação</h5>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Início da votação</label>
                                <input type="datetime-local" name="data_inicio_votacao" class="form-control" value="<?= formatDatetimeLocal($edicaoForm['data_inicio_votacao']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fim da votação</label>
                                <input type="datetime-local" name="data_fim_votacao" class="form-control" value="<?= formatDatetimeLocal($edicaoForm['data_fim_votacao']) ?>">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="premiacao_edicoes.php" class="btn btn-outline-secondary">Limpar</a>
                            <button type="submit" class="btn btn-success">
                                <?= (int)$edicaoForm['id'] > 0 ? 'Salvar alterações' : 'Salvar edição' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h3 class="mb-0">Edições cadastradas</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($edicoes)): ?>
                        <div class="alert alert-light border mb-0">
                            Nenhuma edição cadastrada até o momento.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Edição</th>
                                        <th>Status</th>
                                        <th>Inscrições</th>
                                        <th>Votação</th>
                                        <th>Regulamento</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($edicoes as $ed): ?>
                                        <tr>
                                            <td><?= (int)$ed['id'] ?></td>
                                            <td>
                                                <div class="fw-semibold"><?= h($ed['nome']) ?></div>
                                                <div class="text-muted small">
                                                    Ano <?= (int)$ed['ano'] ?> · <?= h($ed['slug']) ?>
                                                </div>
                                            </td>
                                            <td><?= badgeStatusEdicao((string)$ed['status']) ?></td>
                                            <td class="small">
                                                <strong>Início:</strong> <?= dataBr($ed['data_inicio_inscricoes']) ?><br>
                                                <strong>Fim:</strong> <?= dataBr($ed['data_fim_inscricoes']) ?>
                                            </td>
                                            <td class="small">
                                                <strong>Início:</strong> <?= dataBr($ed['data_inicio_votacao']) ?><br>
                                                <strong>Fim:</strong> <?= dataBr($ed['data_fim_votacao']) ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($ed['regulamento_url'])): ?>
                                                    <a href="<?= h($ed['regulamento_url']) ?>" target="_blank" rel="noopener noreferrer">Abrir</a>
                                                <?php else: ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="premiacao_edicoes.php?editar=<?= (int)$ed['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    Editar
                                                </a>
                                                <a href="premiacao_periodos.php?premiacao_id=<?= (int)$ed['id'] ?>" class="btn btn-sm btn-outline-success">
                                                    Fases
                                                </a>
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
    </div>
</div>

<?php require_once $appBase . '/views/admin/footer.php'; ?>