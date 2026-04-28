<?php
// /cron/atualizar_fases_premiacao.php
// ─────────────────────────────────────────────────────────────────────────────
// Cron para atualização automática de status das fases e edições da premiação.
//
// Regras das fases (premiacao_fases.status):
//   rascunho     → permanece rascunho (admin não publicou ainda)
//   apurada      → permanece apurada  (admin encerrou manualmente)
//   agendada     → NOW() < data_inicio
//   em_andamento → data_inicio <= NOW() <= data_fim
//   encerrada    → NOW() > data_fim
//
// Regras da edição (premiacoes.status):
//   planejada  → nenhuma fase ativa ainda
//   ativa      → ao menos uma fase em_andamento
//   encerrada  → todas as fases encerradas/apuradas
//
// Cronograma PIP 2026 (referência):
//   11/05 – 24/07  Lançamento / Inscrições
//   25/07 – 29/07  Avaliação dos Inscritos
//   30/07 – 14/08  Fase 1
//   15/08 – 23/08  Avaliação Votação  (automática — não é fase manual)
//   24/08 – 04/09  Fase 2
//   07/09 – 18/09  Fase 3
//   24/09          Encontro 2026
//
// Agendamento no servidor (crontab):
//   5 0 * * * php /caminho/para/cron/atualizar_fases_premiacao.php >> /var/log/pip_cron_premiacao.log 2>&1
//   (roda 1x por dia às 00h05 — suficiente pois as fases mudam em granularidade de dias)
// ─────────────────────────────────────────────────────────────────────────────
declare(strict_types=1);

// Proteção: só pode rodar via CLI ou com token de segurança via HTTP
if (PHP_SAPI !== 'cli') {
    $tokenEsperado = getenv('CRON_SECRET') ?: 'pip2026_cron_secret';
    $tokenRecebido = $_GET['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '';
    if (!hash_equals($tokenEsperado, $tokenRecebido)) {
        http_response_code(403);
        exit('Acesso negado.');
    }
}

require_once __DIR__ . '/../app/services/Database.php';

$inicio = microtime(true);
$log    = [];

function logMsg(string $msg): void
{
    global $log;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    $log[] = $line;
    echo $line . PHP_EOL;
}

try {
    $pdo = Database::getInstance();

    // ── 1. Atualiza status das FASES ─────────────────────────────────────────
    // Busca apenas fases que já foram publicadas (não são rascunho nem apuradas)
    // e que precisam de recálculo baseado em data.
    $stmtFases = $pdo->query("
        SELECT id, premiacao_id, nome, status, data_inicio, data_fim
        FROM premiacao_fases
        WHERE status NOT IN ('rascunho', 'apurada')
          AND data_inicio IS NOT NULL
          AND data_fim IS NOT NULL
    ");
    $fases = $stmtFases->fetchAll(PDO::FETCH_ASSOC);

    $atualizadasFases = 0;

    foreach ($fases as $fase) {
        $agora      = new DateTimeImmutable('now');
        $dataInicio = new DateTimeImmutable($fase['data_inicio']);
        $dataFim    = new DateTimeImmutable($fase['data_fim']);

        $novoStatus = match(true) {
            $agora < $dataInicio                        => 'agendada',
            $agora >= $dataInicio && $agora <= $dataFim => 'em_andamento',
            default                                     => 'encerrada',
        };

        if ($novoStatus !== $fase['status']) {
            $stmt = $pdo->prepare("
                UPDATE premiacao_fases
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$novoStatus, (int)$fase['id']]);
            $atualizadasFases++;
            logMsg("Fase #{$fase['id']} ({$fase['nome']}): {$fase['status']} → {$novoStatus}");
        }
    }

    logMsg("Fases verificadas: " . count($fases) . " | Atualizadas: {$atualizadasFases}");


    // ── 2. Atualiza status das EDIÇÕES (premiacoes) ──────────────────────────
    $stmtEdicoes = $pdo->query("
        SELECT id, nome, ano, status
        FROM premiacoes
        WHERE status IN ('planejada', 'ativa')
    ");
    $edicoes = $stmtEdicoes->fetchAll(PDO::FETCH_ASSOC);

    $atualizadasEdicoes = 0;

    foreach ($edicoes as $edicao) {
        $stmtContagem = $pdo->prepare("
            SELECT
                COUNT(*)                                        AS total,
                SUM(status = 'em_andamento')                   AS em_andamento,
                SUM(status IN ('encerrada', 'apurada'))        AS finalizadas
            FROM premiacao_fases
            WHERE premiacao_id = ?
              AND status != 'rascunho'
        ");
        $stmtContagem->execute([(int)$edicao['id']]);
        $contagem = $stmtContagem->fetch(PDO::FETCH_ASSOC);

        $total       = (int)($contagem['total']       ?? 0);
        $emAndamento = (int)($contagem['em_andamento'] ?? 0);
        $finalizadas = (int)($contagem['finalizadas']  ?? 0);

        $novoStatus = match(true) {
            $total === 0             => 'planejada',
            $emAndamento > 0        => 'ativa',
            $finalizadas === $total => 'encerrada',
            default                  => 'planejada',
        };

        if ($novoStatus !== $edicao['status']) {
            $stmt = $pdo->prepare("
                UPDATE premiacoes
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$novoStatus, (int)$edicao['id']]);
            $atualizadasEdicoes++;
            logMsg("Edição #{$edicao['id']} ({$edicao['nome']} {$edicao['ano']}): {$edicao['status']} → {$novoStatus}");
        }
    }

    logMsg("Edições verificadas: " . count($edicoes) . " | Atualizadas: {$atualizadasEdicoes}");

    $duracao = round((microtime(true) - $inicio) * 1000, 2);
    logMsg("Concluído em {$duracao}ms.");

} catch (Throwable $e) {
    $msg = "ERRO CRÍTICO: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine();
    logMsg($msg);
    error_log($msg);
    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
    }
    exit(1);
}
