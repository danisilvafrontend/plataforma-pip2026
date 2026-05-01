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


$pageTitle = 'Premiação - Períodos';
$mensagem = '';
$erro = '';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
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

function dataBr(?string $dt): string
{
    if (empty($dt) || $dt === '0000-00-00' || $dt === '0000-00-00 00:00:00') {
        return '—';
    }

    return date('d/m/Y H:i', strtotime($dt));
}

function calcularStatusAutomatico(string $inicio, string $fim, string $statusAtual = 'rascunho'): string
{
    if ($statusAtual === 'apurada') {
        return 'apurada';
    }

    if ($statusAtual === 'rascunho') {
        return 'rascunho';
    }

    $agora = new DateTime('now');
    $dataInicio = new DateTime($inicio);
    $dataFim = new DateTime($fim);

    if ($agora < $dataInicio) {
        return 'agendada';
    }

    if ($agora > $dataFim) {
        return 'encerrada';
    }

    return 'em_andamento';
}

function labelStatus(string $status): string
{
    return match ($status) {
        'rascunho' => 'Rascunho',
        'agendada' => 'Agendada',
        'em_andamento' => 'Em andamento',
        'encerrada' => 'Encerrada',
        'apurada' => 'Apurada',
        default => '-',
    };
}

function labelTipoFase(string $tipo): string
{
    return match ($tipo) {
        'inscricoes' => 'Inscrições',
        'triagem_documental' => 'Triagem documental',
        'classificatoria' => 'Classificatória',
        'final' => 'Fase final',
        'resultado' => 'Resultado',
        default => $tipo,
    };
}

$edicaoSelecionada = (int)($_GET['premiacao_id'] ?? $_POST['premiacao_id'] ?? 0);
$faseEdicaoId = (int)($_GET['editar'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'salvar_fase') {
            $faseId = (int)($_POST['fase_id'] ?? 0);
            $premiacaoId = (int)($_POST['premiacao_id'] ?? 0);
            $tipoFase = trim($_POST['tipo_fase'] ?? '');
            $rodada = (int)($_POST['rodada'] ?? 0);
            $ordemExibicao = (int)($_POST['ordem_exibicao'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $dataInicio = trim($_POST['data_inicio'] ?? '');
            $dataFim = trim($_POST['data_fim'] ?? '');
            $permiteVotoPopular = isset($_POST['permite_voto_popular']) ? 1 : 0;
            $permiteAvaliacaoTecnica = isset($_POST['permite_avaliacao_tecnica']) ? 1 : 0;
            $permiteJuriFinal = isset($_POST['permite_juri_final']) ? 1 : 0;
            $qtdClassificadosPopular = (int)($_POST['qtd_classificados_popular'] ?? 0);
            $qtdClassificadosTecnica = (int)($_POST['qtd_classificados_tecnica'] ?? 0);
            $qtdClassificadosFinal = (int)($_POST['qtd_classificados_final'] ?? 0);
            $criterioDesempate = trim($_POST['criterio_desempate'] ?? '');
            $statusManual = trim($_POST['status'] ?? 'rascunho');

            if ($premiacaoId <= 0) {
                throw new Exception('Selecione uma edição da premiação.');
            }

            if ($tipoFase === '') {
                throw new Exception('Selecione o tipo da fase.');
            }

            if ($nome === '') {
                throw new Exception('Informe o nome da fase.');
            }

            if ($slug === '') {
                throw new Exception('Informe o slug da fase.');
            }

            if ($dataInicio === '' || $dataFim === '') {
                throw new Exception('Informe as datas de início e fim.');
            }

            $stmtEdicao = $pdo->prepare("
                SELECT id, nome, ano, slug, status
                FROM premiacoes
                WHERE id = ?
                LIMIT 1
            ");
            $stmtEdicao->execute([$premiacaoId]);
            $edicao = $stmtEdicao->fetch();

            if (!$edicao) {
                throw new Exception('Edição da premiação não encontrada.');
            }

            $inicioObj = new DateTime($dataInicio);
            $fimObj = new DateTime($dataFim);

            if ($fimObj < $inicioObj) {
                throw new Exception('A data/hora de fim não pode ser menor que a data/hora de início.');
            }

            $stmtFasesMesmaEdicao = $pdo->prepare("
                SELECT id, nome, ordem_exibicao, data_inicio, data_fim
                FROM premiacao_fases
                WHERE premiacao_id = ?
                  AND id <> ?
                ORDER BY ordem_exibicao ASC, data_inicio ASC, id ASC
            ");
            $stmtFasesMesmaEdicao->execute([$premiacaoId, $faseId]);
            $fasesMesmaEdicao = $stmtFasesMesmaEdicao->fetchAll();

            foreach ($fasesMesmaEdicao as $faseExistente) {
                $mesmaOrdem = (int)$faseExistente['ordem_exibicao'] === $ordemExibicao;
                if ($mesmaOrdem) {
                    throw new Exception('Já existe outra fase com esta ordem de exibição nesta edição.');
                }
            }

            if ($tipoFase === 'inscricoes') {
                $sqlConflito = "
                    SELECT pf.id, pf.nome, p.nome AS premiacao_nome
                    FROM premiacao_fases pf
                    INNER JOIN premiacoes p ON p.id = pf.premiacao_id
                    WHERE pf.tipo_fase = 'inscricoes'
                      AND pf.premiacao_id <> ?
                      AND pf.id <> ?
                      AND pf.status <> 'rascunho'
                      AND (
                            (? BETWEEN pf.data_inicio AND pf.data_fim)
                         OR (? BETWEEN pf.data_inicio AND pf.data_fim)
                         OR (pf.data_inicio BETWEEN ? AND ?)
                         OR (pf.data_fim BETWEEN ? AND ?)
                      )
                    LIMIT 1
                ";

                $stmtConflito = $pdo->prepare($sqlConflito);
                $stmtConflito->execute([
                    $premiacaoId,
                    $faseId,
                    $inicioObj->format('Y-m-d H:i:s'),
                    $fimObj->format('Y-m-d H:i:s'),
                    $inicioObj->format('Y-m-d H:i:s'),
                    $fimObj->format('Y-m-d H:i:s'),
                    $inicioObj->format('Y-m-d H:i:s'),
                    $fimObj->format('Y-m-d H:i:s'),
                ]);

                $conflito = $stmtConflito->fetch();
                if ($conflito) {
                    throw new Exception(
                        'Já existe uma fase de inscrições em conflito com este período: ' .
                        $conflito['premiacao_nome'] . ' / ' . $conflito['nome']
                    );
                }
            }

            if ($tipoFase === 'classificatoria') {
                if ($permiteVotoPopular !== 1 || $permiteAvaliacaoTecnica !== 1) {
                    throw new Exception('A fase classificatória deve habilitar voto popular e avaliação técnica ao mesmo tempo.');
                }

                if ($qtdClassificadosPopular <= 0 || $qtdClassificadosTecnica <= 0 || $qtdClassificadosFinal <= 0) {
                    throw new Exception('Informe as quantidades de classificados da fase classificatória.');
                }
            }

            if ($tipoFase === 'final') {
                if ($permiteVotoPopular !== 1 || $permiteJuriFinal !== 1) {
                    throw new Exception('A fase final deve habilitar voto popular e júri final.');
                }
            }

            $statusSalvar = calcularStatusAutomatico(
                $inicioObj->format('Y-m-d H:i:s'),
                $fimObj->format('Y-m-d H:i:s'),
                $statusManual
            );

            if ($faseId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE premiacao_fases
                    SET premiacao_id = ?, tipo_fase = ?, rodada = ?, ordem_exibicao = ?, nome = ?, slug = ?, descricao = ?,
                        data_inicio = ?, data_fim = ?, permite_voto_popular = ?, permite_avaliacao_tecnica = ?,
                        permite_juri_final = ?, qtd_classificados_popular = ?, qtd_classificados_tecnica = ?,
                        qtd_classificados_final = ?, criterio_desempate = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $premiacaoId,
                    $tipoFase,
                    $rodada > 0 ? $rodada : null,
                    $ordemExibicao,
                    $nome,
                    $slug,
                    $descricao !== '' ? $descricao : null,
                    $inicioObj->format('Y-m-d H:i:s'),
                    $fimObj->format('Y-m-d H:i:s'),
                    $permiteVotoPopular,
                    $permiteAvaliacaoTecnica,
                    $permiteJuriFinal,
                    $qtdClassificadosPopular,
                    $qtdClassificadosTecnica,
                    $qtdClassificadosFinal,
                    $criterioDesempate !== '' ? $criterioDesempate : null,
                    $statusSalvar,
                    $faseId
                ]);

                $mensagem = 'Fase atualizada com sucesso.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO premiacao_fases (
                        premiacao_id, tipo_fase, rodada, ordem_exibicao, nome, slug, descricao,
                        data_inicio, data_fim, permite_voto_popular, permite_avaliacao_tecnica,
                        permite_juri_final, qtd_classificados_popular, qtd_classificados_tecnica,
                        qtd_classificados_final, criterio_desempate, status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $premiacaoId,
                    $tipoFase,
                    $rodada > 0 ? $rodada : null,
                    $ordemExibicao,
                    $nome,
                    $slug,
                    $descricao !== '' ? $descricao : null,
                    $inicioObj->format('Y-m-d H:i:s'),
                    $fimObj->format('Y-m-d H:i:s'),
                    $permiteVotoPopular,
                    $permiteAvaliacaoTecnica,
                    $permiteJuriFinal,
                    $qtdClassificadosPopular,
                    $qtdClassificadosTecnica,
                    $qtdClassificadosFinal,
                    $criterioDesempate !== '' ? $criterioDesempate : null,
                    $statusSalvar
                ]);

                $mensagem = 'Fase cadastrada com sucesso.';
            }

            header('Location: premiacao_periodos.php?premiacao_id=' . $premiacaoId . '&ok=' . urlencode($mensagem));
            exit;
        }

        if ($acao === 'recalcular_status') {
            $faseId = (int)($_POST['fase_id'] ?? 0);

            $stmt = $pdo->prepare("SELECT * FROM premiacao_fases WHERE id = ? LIMIT 1");
            $stmt->execute([$faseId]);
            $fase = $stmt->fetch();

            if (!$fase) {
                throw new Exception('Fase não encontrada.');
            }

            $novoStatus = calcularStatusAutomatico($fase['data_inicio'], $fase['data_fim'], $fase['status']);

            $stmtUpdate = $pdo->prepare("UPDATE premiacao_fases SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$novoStatus, $faseId]);

            header('Location: premiacao_periodos.php?premiacao_id=' . (int)$fase['premiacao_id'] . '&ok=' . urlencode('Status da fase recalculado com sucesso.'));
            exit;
        }
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}

if (isset($_GET['ok']) && $_GET['ok'] !== '') {
    $mensagem = trim($_GET['ok']);
}

$stmtPremiacoes = $pdo->query("
    SELECT id, nome, ano, slug, status
    FROM premiacoes
    ORDER BY ano DESC, id DESC
");
$premiacoes = $stmtPremiacoes->fetchAll();

if ($edicaoSelecionada <= 0 && !empty($premiacoes)) {
    $edicaoSelecionada = (int)$premiacoes[0]['id'];
}

$premiacaoAtual = null;
foreach ($premiacoes as $premiacaoItem) {
    if ((int)$premiacaoItem['id'] === $edicaoSelecionada) {
        $premiacaoAtual = $premiacaoItem;
        break;
    }
}

$faseEdicao = [
    'id' => 0,
    'premiacao_id' => $edicaoSelecionada,
    'tipo_fase' => '',
    'rodada' => '',
    'ordem_exibicao' => 0,
    'nome' => '',
    'slug' => '',
    'descricao' => '',
    'data_inicio' => '',
    'data_fim' => '',
    'permite_voto_popular' => 0,
    'permite_avaliacao_tecnica' => 0,
    'permite_juri_final' => 0,
    'qtd_classificados_popular' => 0,
    'qtd_classificados_tecnica' => 0,
    'qtd_classificados_final' => 0,
    'criterio_desempate' => '',
    'status' => 'rascunho',
];

if ($faseEdicaoId > 0) {
    $stmtFase = $pdo->prepare("SELECT * FROM premiacao_fases WHERE id = ? LIMIT 1");
    $stmtFase->execute([$faseEdicaoId]);
    $faseEncontrada = $stmtFase->fetch();

    if ($faseEncontrada) {
        $faseEdicao = $faseEncontrada;
        $edicaoSelecionada = (int)$faseEncontrada['premiacao_id'];
    }
}

$fases = [];
if ($edicaoSelecionada > 0) {
    $stmtFases = $pdo->prepare("
        SELECT pf.*, p.nome AS premiacao_nome, p.ano AS premiacao_ano
        FROM premiacao_fases pf
        INNER JOIN premiacoes p ON p.id = pf.premiacao_id
        WHERE pf.premiacao_id = ?
        ORDER BY pf.ordem_exibicao ASC, pf.data_inicio ASC, pf.id ASC
    ");
    $stmtFases->execute([$edicaoSelecionada]);
    $fases = $stmtFases->fetchAll();
}

require_once $appBase . '/views/admin/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h3 mb-1">Premiação - Períodos</h1>
            <p class="text-muted mb-0">Gerencie o calendário operacional de cada edição por fases.</p>
        </div>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-success"><?= h($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= h($erro) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Edição</label>
                    <select name="premiacao_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Selecione</option>
                        <?php foreach ($premiacoes as $premiacao): ?>
                            <option value="<?= (int)$premiacao['id'] ?>" <?= (int)$premiacao['id'] === $edicaoSelecionada ? 'selected' : '' ?>>
                                <?= h($premiacao['nome']) ?> · <?= (int)$premiacao['ano'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($edicaoSelecionada > 0): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <strong><?= (int)$faseEdicao['id'] > 0 ? 'Editar fase' : 'Nova fase' ?></strong>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="acao" value="salvar_fase">
                    <input type="hidden" name="fase_id" value="<?= (int)$faseEdicao['id'] ?>">
                    <input type="hidden" name="premiacao_id" value="<?= (int)$edicaoSelecionada ?>">

                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Tipo de fase</label>
                            <select name="tipo_fase" class="form-select" required>
                                <?php
                                $tipos = [
                                    'inscricoes' => 'Inscrições',
                                    'triagem_documental' => 'Triagem documental',
                                    'classificatoria' => 'Classificatória',
                                    'final' => 'Fase final',
                                    'resultado' => 'Resultado',
                                ];
                                foreach ($tipos as $valor => $label):
                                ?>
                                    <option value="<?= h($valor) ?>" <?= ($faseEdicao['tipo_fase'] ?? '') === $valor ? 'selected' : '' ?>>
                                        <?= h($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Rodada</label>
                            <input type="number" name="rodada" class="form-control" min="0"
                                   value="<?= h((string)($faseEdicao['rodada'] ?? '')) ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Ordem</label>
                            <input type="number" name="ordem_exibicao" class="form-control" min="1" required
                                   value="<?= h((string)($faseEdicao['ordem_exibicao'] ?? 0)) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php
                                foreach (['rascunho', 'agendada', 'em_andamento', 'encerrada', 'apurada'] as $statusFase):
                                ?>
                                    <option value="<?= h($statusFase) ?>" <?= ($faseEdicao['status'] ?? 'rascunho') === $statusFase ? 'selected' : '' ?>>
                                        <?= h(labelStatus($statusFase)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" class="form-control" required
                                   value="<?= h($faseEdicao['nome'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" required
                                   value="<?= h($faseEdicao['slug'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Data/hora início</label>
                            <input type="datetime-local" name="data_inicio" class="form-control" required
                                   value="<?= h(formatDatetimeLocal($faseEdicao['data_inicio'] ?? '')) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Data/hora fim</label>
                            <input type="datetime-local" name="data_fim" class="form-control" required
                                   value="<?= h(formatDatetimeLocal($faseEdicao['data_fim'] ?? '')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" class="form-control" rows="3"><?= h($faseEdicao['descricao'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="permite_voto_popular" id="permite_voto_popular"
                                       <?= (int)($faseEdicao['permite_voto_popular'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="permite_voto_popular">Permite voto popular</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="permite_avaliacao_tecnica" id="permite_avaliacao_tecnica"
                                       <?= (int)($faseEdicao['permite_avaliacao_tecnica'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="permite_avaliacao_tecnica">Permite avaliação técnica</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="permite_juri_final" id="permite_juri_final"
                                       <?= (int)($faseEdicao['permite_juri_final'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="permite_juri_final">Permite júri final</label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Qtd. classificados popular</label>
                            <input type="number" name="qtd_classificados_popular" class="form-control" min="0"
                                   value="<?= h((string)($faseEdicao['qtd_classificados_popular'] ?? 0)) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Qtd. classificados técnica</label>
                            <input type="number" name="qtd_classificados_tecnica" class="form-control" min="0"
                                   value="<?= h((string)($faseEdicao['qtd_classificados_tecnica'] ?? 0)) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Qtd. classificados final</label>
                            <input type="number" name="qtd_classificados_final" class="form-control" min="0"
                                   value="<?= h((string)($faseEdicao['qtd_classificados_final'] ?? 0)) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Critério de desempate</label>
                            <input type="text" name="criterio_desempate" class="form-control"
                                   value="<?= h($faseEdicao['criterio_desempate'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-dark">
                            <?= (int)$faseEdicao['id'] > 0 ? 'Salvar alterações' : 'Cadastrar fase' ?>
                        </button>

                        <?php if ((int)$faseEdicao['id'] > 0): ?>
                            <a href="premiacao_periodos.php?premiacao_id=<?= (int)$edicaoSelecionada ?>" class="btn btn-outline-secondary">Cancelar edição</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <strong>Fases cadastradas</strong>
            </div>
            <div class="card-body">
                <?php if (empty($fases)): ?>
                    <p class="text-muted mb-0">Nenhuma fase cadastrada para esta edição.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Fase</th>
                                    <th>Período</th>
                                    <th>Regras</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($fases as $fase): ?>
                                <?php $statusAuto = calcularStatusAutomatico($fase['data_inicio'], $fase['data_fim'], $fase['status']); ?>
                                <tr>
                                    <td>
                                        <strong><?= h($fase['nome']) ?></strong><br>
                                        <small class="text-muted">
                                            <?= h(labelTipoFase((string)$fase['tipo_fase'])) ?>
                                            · Rodada <?= (int)($fase['rodada'] ?? 0) ?>
                                            · <?= h($fase['slug']) ?>
                                        </small>
                                        <?php if (!empty($fase['descricao'])): ?>
                                            <div class="small text-muted mt-1"><?= nl2br(h($fase['descricao'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>Início:</strong> <?= h(dataBr($fase['data_inicio'])) ?><br>
                                        <strong>Fim:</strong> <?= h(dataBr($fase['data_fim'])) ?>
                                    </td>
                                    <td>
                                        <div><strong>Ordem:</strong> <?= (int)$fase['ordem_exibicao'] ?></div>
                                        <div><strong>Popular:</strong> <?= (int)$fase['qtd_classificados_popular'] ?></div>
                                        <div><strong>Técnica:</strong> <?= (int)$fase['qtd_classificados_tecnica'] ?></div>
                                        <div><strong>Final:</strong> <?= (int)$fase['qtd_classificados_final'] ?></div>
                                        <div><strong>Desempate:</strong> <?= h((string)($fase['criterio_desempate'] ?: '—')) ?></div>
                                        <div class="small text-muted mt-1">
                                            <?= (int)$fase['permite_voto_popular'] === 1 ? 'Voto popular' : 'Sem voto popular' ?>
                                            /
                                            <?= (int)$fase['permite_avaliacao_tecnica'] === 1 ? 'Avaliação técnica' : 'Sem avaliação técnica' ?>
                                            /
                                            <?= (int)$fase['permite_juri_final'] === 1 ? 'Júri final' : 'Sem júri' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-secondary"><?= h(labelStatus($statusAuto)) ?></span><br>
                                        <small class="text-muted">Salvo: <?= h((string)$fase['status']) ?></small>
                                    </td>
                                    <td class="d-flex gap-2 flex-wrap">
                                        <a href="premiacao_periodos.php?premiacao_id=<?= (int)$edicaoSelecionada ?>&editar=<?= (int)$fase['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="acao" value="recalcular_status">
                                            <input type="hidden" name="fase_id" value="<?= (int)$fase['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-dark">Recalcular status</button>
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
    <?php endif; ?>
</div>

<?php require_once $appBase . '/views/admin/footer.php'; ?>