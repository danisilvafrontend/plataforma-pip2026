<?php
// /premiacao/apurar_fase.php — Script de apuração (executado por admin)
// ================================================================
// REGRAS CORRIGIDAS:
// Fase 1: Top 10 popular + Top 10 técnica → 20 classificados
// Fase 2: Top 3 popular + Top 3 técnica → 6 finalistas
// Final: 1 voto popular (automático) + N votos do júri → vencedor
// ================================================================

declare(strict_types=1);
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/helpers/premiacao_auth.php';
$actor = premiacao_current_actor();

if (!$actor || $actor['contexto'] !== 'admin') {
    die('Acesso negado. Apenas administradores podem apurar.');
}

$config = require __DIR__ . '/../app/config/db.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'], $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('Erro na conexão: ' . $e->getMessage());
}

// ── Parâmetros ────────────────────────────────────────────────────────────────
$faseId = (int)($_GET['fase_id'] ?? $_POST['fase_id'] ?? 0);

if ($faseId <= 0) {
    die('Fase não especificada.');
}

// ── Carregar a fase ───────────────────────────────────────────────────────────
$stmtFase = $pdo->prepare("
    SELECT 
        pf.id, pf.premiacao_id, pf.tipo_fase, pf.rodada
    FROM premiacao_fases pf
    WHERE pf.id = ?
    LIMIT 1
");
$stmtFase->execute([$faseId]);
$fase = $stmtFase->fetch(PDO::FETCH_ASSOC);

if (!$fase) {
    die('Fase não encontrada.');
}

$premiacaoId = $fase['premiacao_id'];
$tipoFase = $fase['tipo_fase'];
$rodada = $fase['rodada'];

// ── Carregar categorias ────────────────────────────────────────────────────────
$stmtCats = $pdo->prepare("
    SELECT id, nome FROM premiacao_categorias 
    WHERE premiacao_id = ?
    ORDER BY ordem
");
$stmtCats->execute([$premiacaoId]);
$categorias = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

// ── LÓGICA DE APURAÇÃO ────────────────────────────────────────────────────────

if ($tipoFase === 'classificatoria' && $rodada == 1) {
    apurarFase1($pdo, $faseId, $premiacaoId, $categorias, $fase);
} elseif ($tipoFase === 'classificatoria' && $rodada == 2) {
    apurarFase2($pdo, $faseId, $premiacaoId, $categorias, $fase);
} elseif ($tipoFase === 'final') {
    apurarFaseFinal($pdo, $faseId, $premiacaoId, $categorias, $fase);
} else {
    die('Tipo de fase não reconhecido para apuração.');
}

// ── FUNÇÕES DE APURAÇÃO ───────────────────────────────────────────────────────

/**
 * Apura Fase 1
 * 
 * Regra:
 *   - Top 10 populares
 *   - Top 10 técnicas
 *   - Mesclar (remover duplicatas)
 *   - Completar até 20 com próximos
 */
function apurarFase1(
    PDO $pdo, 
    int $faseId, 
    int $premiacaoId, 
    array $categorias, 
    array $fase
): void {
    echo "Apurando Fase 1...\n";

    foreach ($categorias as $cat) {
        $categoriaId = (int)$cat['id'];
        $categoriaNome = $cat['nome'];

        echo "  Processando: $categoriaNome\n";

        // ── Limpar classificados anteriores desta fase/categoria ─────────────
        $stmtLimpa = $pdo->prepare("
            DELETE FROM premiacao_classificados_fase
            WHERE fase_id = ? AND categoria_id = ?
        ");
        $stmtLimpa->execute([$faseId, $categoriaId]);

        // ── 1. TOP 10 POPULARES ───────────────────────────────────────────────
        $stmtTop10Pop = $pdo->prepare("
            SELECT 
                inscricao_id,
                COUNT(*) as total_votos
            FROM premiacao_votos_populares
            WHERE fase_id = ? 
              AND categoria_id = ?
            GROUP BY inscricao_id
            ORDER BY total_votos DESC
            LIMIT 10
        ");
        $stmtTop10Pop->execute([$faseId, $categoriaId]);
        $top10Popular = $stmtTop10Pop->fetchAll(PDO::FETCH_ASSOC);

        echo "    Top 10 populares: " . count($top10Popular) . " inscrições\n";

        // ── 2. TOP 10 TÉCNICAS ────────────────────────────────────────────────
        $stmtTop10Tec = $pdo->prepare("
            SELECT 
                inscricao_id,
                COUNT(*) as total_votos
            FROM premiacao_votos_tecnicos
            WHERE fase_id = ? 
              AND categoria_id = ?
            GROUP BY inscricao_id
            ORDER BY total_votos DESC
            LIMIT 10
        ");
        $stmtTop10Tec->execute([$faseId, $categoriaId]);
        $top10Tecnica = $stmtTop10Tec->fetchAll(PDO::FETCH_ASSOC);

        echo "    Top 10 técnicas: " . count($top10Tecnica) . " inscrições\n";

        // ── 3. MESCLAR E REMOVER DUPLICATAS ──────────────────────────────────
        $mesclados = [];
        $indicesUsados = [];

        foreach ($top10Popular as $row) {
            $inscricaoId = (int)$row['inscricao_id'];
            if (!isset($mesclados[$inscricaoId])) {
                $mesclados[$inscricaoId] = [
                    'inscricao_id' => $inscricaoId,
                    'posicao_popular' => count($indicesUsados) + 1,
                    'origem' => 'popular'
                ];
                $indicesUsados[$inscricaoId] = true;
            }
        }

        foreach ($top10Tecnica as $row) {
            $inscricaoId = (int)$row['inscricao_id'];
            if (!isset($indicesUsados[$inscricaoId])) {
                $mesclados[$inscricaoId] = [
                    'inscricao_id' => $inscricaoId,
                    'posicao_tecnica' => count($indicesUsados) + 1,
                    'origem' => 'tecnica'
                ];
                $indicesUsados[$inscricaoId] = true;
            } else {
                $mesclados[$inscricaoId]['origem'] = 'ambos';
                $mesclados[$inscricaoId]['posicao_tecnica'] = count($indicesUsados) + 1;
            }
        }

        echo "    Após mescla: " . count($mesclados) . " inscrições (sem duplicatas)\n";

        // ── 4. COMPLETAR ATÉ 20 ──────────────────────────────────────────────
        $qtdFinal = 20;
        if (count($mesclados) < $qtdFinal) {
            $inscrioesJaUsadas = array_keys($indicesUsados);
            $placeholders = implode(',', array_fill(0, count($inscrioesJaUsadas), '?'));

            $stmtCompleta = $pdo->prepare("
                SELECT DISTINCT inscricao_id
                FROM premiacao_votos_populares
                WHERE fase_id = ? 
                  AND categoria_id = ?
                  AND inscricao_id NOT IN ($placeholders)
                ORDER BY inscricao_id
                LIMIT ?
            ");
            $params = [$faseId, $categoriaId, ...$inscrioesJaUsadas];
            $params[] = $qtdFinal - count($mesclados);
            $stmtCompleta->execute($params);
            $complemento = $stmtCompleta->fetchAll(PDO::FETCH_ASSOC);

            foreach ($complemento as $row) {
                $inscricaoId = (int)$row['inscricao_id'];
                $mesclados[$inscricaoId] = [
                    'inscricao_id' => $inscricaoId,
                    'origem' => 'repescagem'
                ];
            }
        }

        echo "    Final: " . count($mesclados) . " classificados\n";

        // ── Inserir classificados na tabela ──────────────────────────────────
        $stmtInsert = $pdo->prepare("
            INSERT INTO premiacao_classificados_fase
                (fase_id, premiacao_id, categoria, negocio_id, origem_classificacao, 
                 posicao_popular, posicao_tecnica, status, created_at)
            SELECT 
                ?, ?, pi.categoria, pi.negocio_id, ?,
                ?, ?, 'classificado', NOW()
            FROM premiacao_inscricoes pi
            WHERE pi.id = ?
        ");

        foreach ($mesclados as $item) {
            $inscricaoId = $item['inscricao_id'];
            $origem = $item['origem'];
            $posPopular = $item['posicao_popular'] ?? null;
            $posTecnica = $item['posicao_tecnica'] ?? null;

            $stmtInsert->execute([
                $faseId, $premiacaoId, $origem,
                $posPopular, $posTecnica, $inscricaoId
            ]);
        }

        echo "    ✓ Inscritos na tabela premiacao_classificados_fase\n";
    }

    echo "\n✅ Fase 1 apurada com sucesso!\n";
}

/**
 * Apura Fase 2
 * 
 * Regra (APENAS com classificados da Fase 1):
 *   - Top 3 populares (não Top 10!)
 *   - Top 3 técnicas
 *   - Mesclar (remover duplicatas)
 *   - Completar até 6 com próximos
 */
function apurarFase2(
    PDO $pdo, 
    int $faseId, 
    int $premiacaoId, 
    array $categorias, 
    array $fase
): void {
    echo "Apurando Fase 2...\n";

    $qtdPopular = 3;   // TOP 3 na Fase 2 (não 10!)
    $qtdTecnica = 3;   // TOP 3 na Fase 2
    $qtdFinal   = 6;   // Completar até 6

    // ── Buscar a Fase 1 para trazer apenas classificados dela ─────────────────
    $stmtFase1 = $pdo->prepare("
        SELECT id FROM premiacao_fases
        WHERE premiacao_id = ? AND tipo_fase = 'classificatoria' AND rodada = 1
        LIMIT 1
    ");
    $stmtFase1->execute([$premiacaoId]);
    $fase1Row = $stmtFase1->fetch(PDO::FETCH_ASSOC);

    if (!$fase1Row) {
        die('Fase 1 não encontrada. Execute a apuração da Fase 1 primeiro.');
    }

    $fase1Id = (int)$fase1Row['id'];

    foreach ($categorias as $cat) {
        $categoriaId = (int)$cat['id'];
        $categoriaNome = $cat['nome'];

        echo "  Processando: $categoriaNome\n";

        // ── Limpar classificados anteriores desta fase/categoria ─────────────
        $stmtLimpa = $pdo->prepare("
            DELETE FROM premiacao_classificados_fase
            WHERE fase_id = ? AND categoria_id = ?
        ");
        $stmtLimpa->execute([$faseId, $categoriaId]);

        // ── Buscar inscrições classificadas na Fase 1 para esta categoria ────
        $stmtInscFase1 = $pdo->prepare("
            SELECT DISTINCT inscricao_id
            FROM premiacao_classificados_fase
            WHERE fase_id = ? AND categoria_id = ?
        ");
        $stmtInscFase1->execute([$fase1Id, $categoriaId]);
        $inscricoesFase1 = $stmtInscFase1->fetchAll(PDO::FETCH_COLUMN);

        if (empty($inscricoesFase1)) {
            echo "    ⚠️  Nenhuma inscrição classificada na Fase 1 para esta categoria.\n";
            continue;
        }

        $placeholders = implode(',', array_fill(0, count($inscricoesFase1), '?'));

        // ── 1. TOP 3 POPULARES (apenas fase 1) ────────────────────────────────
        $stmtTop3Pop = $pdo->prepare("
            SELECT 
                inscricao_id,
                COUNT(*) as total_votos
            FROM premiacao_votos_populares
            WHERE fase_id = ? 
              AND categoria_id = ?
              AND inscricao_id IN ($placeholders)
            GROUP BY inscricao_id
            ORDER BY total_votos DESC
            LIMIT ?
        ");
        $params = [$faseId, $categoriaId, ...$inscricoesFase1, $qtdPopular];
        $stmtTop3Pop->execute($params);
        $top3Popular = $stmtTop3Pop->fetchAll(PDO::FETCH_ASSOC);

        echo "    Top 3 populares: " . count($top3Popular) . " inscrições\n";

        // ── 2. TOP 3 TÉCNICAS (apenas fase 1) ────────────────────────────────
        $stmtTop3Tec = $pdo->prepare("
            SELECT 
                inscricao_id,
                COUNT(*) as total_votos
            FROM premiacao_votos_tecnicos
            WHERE fase_id = ? 
              AND categoria_id = ?
              AND inscricao_id IN ($placeholders)
            GROUP BY inscricao_id
            ORDER BY total_votos DESC
            LIMIT ?
        ");
        $params = [$faseId, $categoriaId, ...$inscricoesFase1, $qtdTecnica];
        $stmtTop3Tec->execute($params);
        $top3Tecnica = $stmtTop3Tec->fetchAll(PDO::FETCH_ASSOC);

        echo "    Top 3 técnicas: " . count($top3Tecnica) . " inscrições\n";

        // ── 3. MESCLAR E REMOVER DUPLICATAS ──────────────────────────────────
        $mesclados = [];
        $indicesUsados = [];

        foreach ($top3Popular as $row) {
            $inscricaoId = (int)$row['inscricao_id'];
            if (!isset($mesclados[$inscricaoId])) {
                $mesclados[$inscricaoId] = [
                    'inscricao_id' => $inscricaoId,
                    'posicao_popular' => count($indicesUsados) + 1,
                    'origem' => 'popular'
                ];
                $indicesUsados[$inscricaoId] = true;
            }
        }

        foreach ($top3Tecnica as $row) {
            $inscricaoId = (int)$row['inscricao_id'];
            if (!isset($indicesUsados[$inscricaoId])) {
                $mesclados[$inscricaoId] = [
                    'inscricao_id' => $inscricaoId,
                    'posicao_tecnica' => count($indicesUsados) + 1,
                    'origem' => 'tecnica'
                ];
                $indicesUsados[$inscricaoId] = true;
            } else {
                $mesclados[$inscricaoId]['origem'] = 'ambos';
                $mesclados[$inscricaoId]['posicao_tecnica'] = count($indicesUsados) + 1;
            }
        }

        echo "    Após mescla: " . count($mesclados) . " inscrições\n";

        // ── 4. COMPLETAR ATÉ 6 ───────────────────────────────────────────────
        if (count($mesclados) < $qtdFinal) {
            $inscrioesJaUsadas = array_keys($indicesUsados);
            $placeholdersUsadas = implode(',', array_fill(0, count($inscrioesJaUsadas), '?'));

            $stmtCompleta = $pdo->prepare("
                SELECT DISTINCT inscricao_id
                FROM premiacao_classificados_fase
                WHERE fase_id = ? 
                  AND categoria_id = ?
                  AND inscricao_id NOT IN ($placeholdersUsadas)
                ORDER BY inscricao_id
                LIMIT ?
            ");
            $params = [$fase1Id, $categoriaId, ...$inscrioesJaUsadas];
            $params[] = $qtdFinal - count($mesclados);
            $stmtCompleta->execute($params);
            $complemento = $stmtCompleta->fetchAll(PDO::FETCH_ASSOC);

            foreach ($complemento as $row) {
                $inscricaoId = (int)$row['inscricao_id'];
                $mesclados[$inscricaoId] = [
                    'inscricao_id' => $inscricaoId,
                    'origem' => 'repescagem'
                ];
            }
        }

        echo "    Final: " . count($mesclados) . " finalistas\n";

        // ── Inserir na tabela ────────────────────────────────────────────────
        $stmtInsert = $pdo->prepare("
            INSERT INTO premiacao_classificados_fase
                (fase_id, premiacao_id, categoria, negocio_id, origem_classificacao, 
                 posicao_popular, posicao_tecnica, status, created_at)
            SELECT 
                ?, ?, pi.categoria, pi.negocio_id, ?,
                ?, ?, 'classificado', NOW()
            FROM premiacao_inscricoes pi
            WHERE pi.id = ?
        ");

        foreach ($mesclados as $item) {
            $inscricaoId = $item['inscricao_id'];
            $origem = $item['origem'];
            $posPopular = $item['posicao_popular'] ?? null;
            $posTecnica = $item['posicao_tecnica'] ?? null;

            $stmtInsert->execute([
                $faseId, $premiacaoId, $origem,
                $posPopular, $posTecnica, $inscricaoId
            ]);
        }
    }

    echo "\n✅ Fase 2 apurada com sucesso!\n";
}

/**
 * Apura Fase Final
 * 
 * Regra:
 *   - Voto Popular = 1 ponto AUTOMÁTICO para o vencedor popular
 *   - Votos do Júri = 1 ponto para cada jurado
 *   - Total = Vencedor (maior soma)
 */
function apurarFaseFinal(
    PDO $pdo, 
    int $faseId, 
    int $premiacaoId, 
    array $categorias, 
    array $fase
): void {
    echo "Apurando Fase Final (Vencedores)...\n";

    // ── Buscar Fase 2 para trazer os finalistas ────────────────────────────────
    $stmtFase2 = $pdo->prepare("
        SELECT id FROM premiacao_fases
        WHERE premiacao_id = ? AND tipo_fase = 'classificatoria' AND rodada = 2
        LIMIT 1
    ");
    $stmtFase2->execute([$premiacaoId]);
    $fase2Row = $stmtFase2->fetch(PDO::FETCH_ASSOC);

    if (!$fase2Row) {
        die('Fase 2 não encontrada. Execute a apuração da Fase 2 primeiro.');
    }

    $fase2Id = (int)$fase2Row['id'];

    foreach ($categorias as $cat) {
        $categoriaId = (int)$cat['id'];
        $categoriaNome = $cat['nome'];

        echo "  Processando: $categoriaNome\n";

        // ── Buscar finalistas (classificados na Fase 2) ──────────────────────
        $stmtFinalistas = $pdo->prepare("
            SELECT inscricao_id
            FROM premiacao_classificados_fase
            WHERE fase_id = ? AND categoria_id = ? AND status = 'classificado'
            ORDER BY posicao_popular DESC
        ");
        $stmtFinalistas->execute([$fase2Id, $categoriaId]);
        $finalistas = $stmtFinalistas->fetchAll(PDO::FETCH_COLUMN);

        if (empty($finalistas)) {
            echo "    ⚠️  Nenhum finalista para esta categoria.\n";
            continue;
        }

        echo "    Finalistas: " . count($finalistas) . "\n";

        // ── 1. ENCONTRAR VENCEDOR POPULAR (1º em votos) ──────────────────────
        $stmtVencedorPop = $pdo->prepare("
            SELECT inscricao_id, COUNT(*) as total_votos
            FROM premiacao_votos_populares
            WHERE fase_id = ? AND categoria_id = ?
            GROUP BY inscricao_id
            ORDER BY total_votos DESC
            LIMIT 1
        ");
        $stmtVencedorPop->execute([$faseId, $categoriaId]);
        $vencedorPopRow = $stmtVencedorPop->fetch(PDO::FETCH_ASSOC);
        
        $vencedorPopularId = $vencedorPopRow ? (int)$vencedorPopRow['inscricao_id'] : null;
        echo "    Vencedor popular: Inscrição #$vencedorPopularId\n";

        // ── 2. CONTAR VOTOS DO JÚRI PARA CADA FINALISTA ─────────────────────
        $votos = [];

        foreach ($finalistas as $inscricaoId) {
            $inscricaoId = (int)$inscricaoId;
            
            // Voto popular (1 ponto se for o vencedor)
            $votosPopulares = ($inscricaoId === $vencedorPopularId) ? 1 : 0;
            
            // Votos do júri
            $stmtVotosJuri = $pdo->prepare("
                SELECT COUNT(DISTINCT user_id) as total
                FROM premiacao_votos_juri
                WHERE fase_id = ? AND categoria_id = ? AND inscricao_id = ?
            ");
            $stmtVotosJuri->execute([$faseId, $categoriaId, $inscricaoId]);
            $votosJuriResult = $stmtVotosJuri->fetch(PDO::FETCH_ASSOC);
            $votosJuri = (int)($votosJuriResult['total'] ?? 0);
            
            $totalVotos = $votosPopulares + $votosJuri;
            
            $votos[$inscricaoId] = [
                'inscricao_id' => $inscricaoId,
                'votos_populares' => $votosPopulares,
                'votos_juri' => $votosJuri,
                'total_votos' => $totalVotos
            ];
        }

        // ── 3. ORDENAR E DEFINIR VENCEDOR ────────────────────────────────────
        usort($votos, function ($a, $b) {
            return $b['total_votos'] <=> $a['total_votos'];
        });

        if (!empty($votos)) {
            $vencedor = $votos[0];
            $vencedorId = $vencedor['inscricao_id'];

            echo "    Vencedor Final: Inscrição #$vencedorId (" .
                 $vencedor['votos_populares'] . " pop + " .
                 $vencedor['votos_juri'] . " júri = " .
                 $vencedor['total_votos'] . " votos)\n";

            // ── Marcar como vencedor na tabela ─────────────────────────────
            $stmtMarkVencedor = $pdo->prepare("
                UPDATE premiacao_classificados_fase
                SET status = 'vencedor'
                WHERE fase_id = ? AND categoria_id = ? AND inscricao_id = ?
            ");
            $stmtMarkVencedor->execute([$faseId, $categoriaId, $vencedorId]);

            // ── Também atualizar inscrição como vencedora ──────────────────
            $stmtMarkInscricao = $pdo->prepare("
                UPDATE premiacao_inscricoes
                SET status = 'vencedora'
                WHERE id = ?
            ");
            $stmtMarkInscricao->execute([$vencedorId]);
        }
    }

    echo "\n✅ Fase Final apurada com sucesso! Vencedores foram definidos.\n";
}

// ── Finalizar ─────────────────────────────────────────────────────────────────
echo "\n=== Apuração completada ===\n";