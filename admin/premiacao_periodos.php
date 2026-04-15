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

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die('Erro ao conectar ao banco: ' . $e->getMessage());
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
                SELECT id, nome, ano, data_inicio_inscricoes, data_fim_inscricoes, data_inicio_votacao, data_fim_votacao
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

            if ($tipoFase === 'inscricoes') {
                if (!empty($edicao['data_inicio_inscricoes']) && $inicioObj < new DateTime($edicao['data_inicio_inscricoes'])) {
                    throw new Exception('A fase de inscrições não pode começar antes do início das inscrições da edição.');
                }

                if (!empty($edicao['data_fim_inscricoes']) && $fimObj > new DateTime($edicao['data_fim_inscricoes'])) {
                    throw new Exception('A fase de inscrições não pode terminar após o fim das inscrições da edição.');
                }
            }

            if (in_array($tipoFase, ['classificatoria', 'final', 'resultado'], true)) {
                if (!empty($edicao['data_inicio_votacao']) && $inicioObj < new DateTime($edicao['data_inicio_votacao'])) {
                    throw new Exception('Esta fase não pode começar antes do início da votação da edição.');
                }

                if (!empty($edicao['data_fim_votacao']) && $fimObj > new DateTime($edicao['data_fim_votacao'])) {
                    throw new Exception('Esta fase não pode terminar após o fim da votação da edição.');
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
                    SET
                        premiacao_id = ?,
                        tipo_fase = ?,
                        rodada = ?,
                        ordem_exibicao = ?,
                        nome = ?,
                        slug = ?,
                        descricao = ?,
                        data_inicio = ?,
                        data_fim = ?,
                        permite_voto_popular = ?,
                        permite_avaliacao_tecnica = ?,
                        permite_juri_final = ?,
                        qtd_classificados_popular = ?,
                        qtd_classificados_tecnica = ?,
                        qtd_classificados_final = ?,
                        criterio_desempate = ?,
                        status = ?,
                        updated_at = NOW()
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
                        premiacao_id,
                        tipo_fase,
                        rodada,
                        ordem_exibicao,
                        nome,
                        slug,
                        descricao,
                        data_inicio,
                        data_fim,
                        permite_voto_popular,
                        permite_avaliacao_tecnica,
                        permite_juri_final,
                        qtd_classificados_popular,
                        qtd_classificados_tecnica,
                        qtd_classificados_final,
                        criterio_desempate,
                        status,
                        created_at,
                        updated_at
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
    SELECT id, nome, ano, slug, status, data_inicio_inscricoes, data_fim_inscricoes, data_inicio_votacao, data_fim_votacao
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

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color:#1E3425;">Premiação - Períodos</h4>
            <small style="color:#6c8070;">Gerencie as rodadas e regras da edição selecionada.</small>
        </div>
    </div>

    <?php if ($mensagem !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= h($mensagem) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= h($erro) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Selecionar edição</h5>
        </div>
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label for="premiacao_id" class="form-label">Edição da premiação</label>
                    <select name="premiacao_id" id="premiacao_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Selecione</option>
                        <?php foreach ($premiacoes as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === (int)$edicaoSelecionada ? 'selected' : '' ?>>
                                <?= h($p['nome']) ?> - <?= (int)$p['ano'] ?> - <?= h($p['status']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if ($premiacaoAtual): ?>
                <div class="row mt-3 g-2">
                    <div class="col-md-3">
                        <div class="border rounded p-3 bg-light h-100">
                            <small class="text-muted d-block">Inscrições</small>
                            <strong><?= dataBr($premiacaoAtual['data_inicio_inscricoes']) ?></strong><br>
                            <span>até <?= dataBr($premiacaoAtual['data_fim_inscricoes']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 bg-light h-100">
                            <small class="text-muted d-block">Votação</small>
                            <strong><?= dataBr($premiacaoAtual['data_inicio_votacao']) ?></strong><br>
                            <span>até <?= dataBr($premiacaoAtual['data_fim_votacao']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><?= (int)$faseEdicao['id'] > 0 ? 'Editar fase' : 'Nova fase' ?></h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="acao" value="salvar_fase">
                        <input type="hidden" name="fase_id" value="<?= (int)$faseEdicao['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label">Edição</label>
                            <select name="premiacao_id" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach ($premiacoes as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === (int)$faseEdicao['premiacao_id'] ? 'selected' : '' ?>>
                                        <?= h($p['nome']) ?> - <?= (int)$p['ano'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-7">
                                <label class="form-label">Tipo da fase</label>
                                <select name="tipo_fase" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="inscricoes" <?= ($faseEdicao['tipo_fase'] ?? '') === 'inscricoes' ? 'selected' : '' ?>>Inscrições</option>
                                    <option value="triagem_documental" <?= ($faseEdicao['tipo_fase'] ?? '') === 'triagem_documental' ? 'selected' : '' ?>>Triagem documental</option>
                                    <option value="classificatoria" <?= ($faseEdicao['tipo_fase'] ?? '') === 'classificatoria' ? 'selected' : '' ?>>Classificatória</option>
                                    <option value="final" <?= ($faseEdicao['tipo_fase'] ?? '') === 'final' ? 'selected' : '' ?>>Fase final</option>
                                    <option value="resultado" <?= ($faseEdicao['tipo_fase'] ?? '') === 'resultado' ? 'selected' : '' ?>>Resultado</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Rodada</label>
                                <input type="number" name="rodada" class="form-control" min="0" value="<?= h((string)($faseEdicao['rodada'] ?? '')) ?>" placeholder="0, 1, 2, 3">
                            </div>
                        </div>

                        <div class="row g-2 mt-1">
                            <div class="col-md-8">
                                <label class="form-label">Nome da fase</label>
                                <input type="text" name="nome" class="form-control" required value="<?= h($faseEdicao['nome']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ordem</label>
                                <input type="number" name="ordem_exibicao" class="form-control" min="0" value="<?= h((string)($faseEdicao['ordem_exibicao'] ?? 0)) ?>">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" required value="<?= h($faseEdicao['slug']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" class="form-control" rows="3"><?= h($faseEdicao['descricao']) ?></textarea>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Data/hora de início</label>
                                <input type="datetime-local" name="data_inicio" class="form-control" required value="<?= formatDatetimeLocal($faseEdicao['data_inicio']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Data/hora de fim</label>
                                <input type="datetime-local" name="data_fim" class="form-control" required value="<?= formatDatetimeLocal($faseEdicao['data_fim']) ?>">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label d-block">Permissões da fase</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="permite_voto_popular" id="permite_voto_popular" value="1" <?= (int)($faseEdicao['permite_voto_popular'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="permite_voto_popular">Permite voto popular</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="permite_avaliacao_tecnica" id="permite_avaliacao_tecnica" value="1" <?= (int)($faseEdicao['permite_avaliacao_tecnica'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="permite_avaliacao_tecnica">Permite avaliação técnica</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permite_juri_final" id="permite_juri_final" value="1" <?= (int)($faseEdicao['permite_juri_final'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="permite_juri_final">Permite júri final</label>
                            </div>
                        </div>

                        <div class="row g-2 mt-1">
                            <div class="col-md-4">
                                <label class="form-label">Qtd. popular</label>
                                <input type="number" name="qtd_classificados_popular" class="form-control" min="0" value="<?= h((string)($faseEdicao['qtd_classificados_popular'] ?? 0)) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Qtd. técnica</label>
                                <input type="number" name="qtd_classificados_tecnica" class="form-control" min="0" value="<?= h((string)($faseEdicao['qtd_classificados_tecnica'] ?? 0)) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Qtd. final</label>
                                <input type="number" name="qtd_classificados_final" class="form-control" min="0" value="<?= h((string)($faseEdicao['qtd_classificados_final'] ?? 0)) ?>">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Critério de desempate</label>
                            <select name="criterio_desempate" class="form-select">
                                <option value="">Selecione</option>
                                <option value="nota_tecnica" <?= ($faseEdicao['criterio_desempate'] ?? '') === 'nota_tecnica' ? 'selected' : '' ?>>Maior nota técnica</option>
                                <option value="voto_popular" <?= ($faseEdicao['criterio_desempate'] ?? '') === 'voto_popular' ? 'selected' : '' ?>>Maior voto popular</option>
                                <option value="data_inscricao" <?= ($faseEdicao['criterio_desempate'] ?? '') === 'data_inscricao' ? 'selected' : '' ?>>Inscrição mais antiga</option>
                                <option value="decisao_admin" <?= ($faseEdicao['criterio_desempate'] ?? '') === 'decisao_admin' ? 'selected' : '' ?>>Decisão administrativa</option>
                            </select>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Status base</label>
                            <select name="status" class="form-select">
                                <option value="rascunho" <?= ($faseEdicao['status'] ?? '') === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                                <option value="agendada" <?= ($faseEdicao['status'] ?? '') === 'agendada' ? 'selected' : '' ?>>Agendada</option>
                                <option value="em_andamento" <?= ($faseEdicao['status'] ?? '') === 'em_andamento' ? 'selected' : '' ?>>Em andamento</option>
                                <option value="encerrada" <?= ($faseEdicao['status'] ?? '') === 'encerrada' ? 'selected' : '' ?>>Encerrada</option>
                                <option value="apurada" <?= ($faseEdicao['status'] ?? '') === 'apurada' ? 'selected' : '' ?>>Apurada</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <?= (int)$faseEdicao['id'] > 0 ? 'Salvar alterações' : 'Cadastrar fase' ?>
                            </button>

                            <?php if ((int)$faseEdicao['id'] > 0): ?>
                                <a href="premiacao_periodos.php?premiacao_id=<?= (int)$faseEdicao['premiacao_id'] ?>" class="btn btn-outline-secondary">
                                    Cancelar edição
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Fases cadastradas</h5>
                </div>
                <div class="card-body">
                    <?php if (!$premiacaoAtual): ?>
                        <div class="alert alert-light border mb-0">Cadastre uma edição da premiação antes de configurar as fases.</div>
                    <?php elseif (empty($fases)): ?>
                        <div class="alert alert-light border mb-0">Nenhuma fase cadastrada para esta edição.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Fase</th>
                                        <th>Período</th>
                                        <th>Regras</th>
                                        <th>Status</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fases as $fase): ?>
                                        <?php $statusAuto = calcularStatusAutomatico($fase['data_inicio'], $fase['data_fim'], $fase['status']); ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?= h($fase['nome']) ?></div>
                                                <div class="text-muted small">
                                                    <?= h(labelTipoFase((string)$fase['tipo_fase'])) ?>
                                                    <?php if (!empty($fase['rodada'])): ?> · Rodada <?= (int)$fase['rodada'] ?><?php endif; ?>
                                                    · <?= h($fase['slug']) ?>
                                                </div>
                                                <?php if (!empty($fase['descricao'])): ?>
                                                    <div class="text-muted small mt-1"><?= nl2br(h($fase['descricao'])) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div><strong>Início:</strong> <?= dataBr($fase['data_inicio']) ?></div>
                                                <div><strong>Fim:</strong> <?= dataBr($fase['data_fim']) ?></div>
                                            </td>
                                            <td class="small">
                                                Ordem: <?= (int)$fase['ordem_exibicao'] ?><br>
                                                Popular: <?= (int)$fase['qtd_classificados_popular'] ?><br>
                                                Técnica: <?= (int)$fase['qtd_classificados_tecnica'] ?><br>
                                                Final: <?= (int)$fase['qtd_classificados_final'] ?><br>
                                                Desempate: <?= h((string)($fase['criterio_desempate'] ?: '—')) ?><br>
                                                <?= (int)$fase['permite_voto_popular'] === 1 ? 'Voto popular' : 'Sem voto popular' ?> /
                                                <?= (int)$fase['permite_avaliacao_tecnica'] === 1 ? 'Avaliação técnica' : 'Sem avaliação técnica' ?> /
                                                <?= (int)$fase['permite_juri_final'] === 1 ? 'Júri final' : 'Sem júri' ?>
                                            </td>
                                            <td>
                                                <div><?= h(labelStatus($statusAuto)) ?></div>
                                                <div class="text-muted small">Salvo: <?= h((string)$fase['status']) ?></div>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                                    <a href="premiacao_periodos.php?premiacao_id=<?= (int)$fase['premiacao_id'] ?>&editar=<?= (int)$fase['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        Editar
                                                    </a>

                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="acao" value="recalcular_status">
                                                        <input type="hidden" name="fase_id" value="<?= (int)$fase['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                            Recalcular status
                                                        </button>
                                                    </form>
                                                </div>
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