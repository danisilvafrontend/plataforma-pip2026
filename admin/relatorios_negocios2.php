<?php
session_start();

require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/relatorios_helper.php';

// só permite admin, superadmin ou juri
require_admin_login();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config/db.php';

function mysqlSuportaJsonTable(PDO $pdo): bool
{
    try {
        $pdo->query("
            SELECT *
            FROM JSON_TABLE(
                '[\"teste\"]',
                '$[*]' COLUMNS (
                    valor VARCHAR(50) PATH '$'
                )
            ) AS jt
            LIMIT 1
        ");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function agregarJsonEmPhp(array $linhas, string $campoJson): array
{
    $contagem = [];

    foreach ($linhas as $linha) {
        $json = $linha[$campoJson] ?? null;

        if (empty($json)) {
            continue;
        }

        $itens = json_decode($json, true);

        if (!is_array($itens)) {
            continue;
        }

        foreach ($itens as $item) {
            $item = trim((string)$item);

            if ($item === '') {
                continue;
            }

            if (!isset($contagem[$item])) {
                $contagem[$item] = 0;
            }

            $contagem[$item]++;
        }
    }

    arsort($contagem);

    $resultado = [];
    foreach ($contagem as $nome => $total) {
        $resultado[] = [
            'nome' => $nome,
            'total' => (int)$total
        ];
    }

    return $resultado;
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
    $pdo = new PDO(
        $dsn,
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec("SET NAMES utf8mb4");

    $suportaJsonTable = mysqlSuportaJsonTable($pdo);

    // =========================================================
    // KPIs gerais
    // =========================================================
    $sqlKpis = "
        SELECT
            COUNT(*) AS total_negocios,
            SUM(CASE WHEN inscricao_completa = 1 THEN 1 ELSE 0 END) AS total_concluidos,
            SUM(CASE WHEN inscricao_completa = 0 THEN 1 ELSE 0 END) AS total_em_andamento
        FROM negocios
    ";
    $stmtKpis = $pdo->query($sqlKpis);
    $dataKpis = $stmtKpis->fetch(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 1: Por categoria
    // =========================================================
    $sqlCat = "
        SELECT 
            n.categoria,
            COUNT(*) AS total
        FROM negocios n
        WHERE n.inscricao_completa = 1
          AND n.categoria IS NOT NULL
          AND n.categoria != ''
        GROUP BY n.categoria
        ORDER BY total DESC
    ";
    $stmtCat = $pdo->query($sqlCat);
    $dataCategoria = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 2: Por estado (UF)
    // =========================================================
    $sqlUF = "
        SELECT 
            n.estado,
            COUNT(*) AS total
        FROM negocios n
        WHERE n.inscricao_completa = 1
          AND n.estado IS NOT NULL
          AND n.estado != ''
        GROUP BY n.estado
        ORDER BY total DESC
    ";
    $stmtUF = $pdo->query($sqlUF);
    $dataUF = $stmtUF->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 3: Por eixo temático
    // =========================================================
    $sqlEixo = "
        SELECT 
            et.nome AS eixo_tematico,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN eixos_tematicos et ON et.id = n.eixo_principal_id
        WHERE n.inscricao_completa = 1
        GROUP BY et.nome
        ORDER BY total DESC
    ";
    $stmtEixo = $pdo->query($sqlEixo);
    $dataEixo = $stmtEixo->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 4: Por ODS prioritária
    // =========================================================
    $sqlODS = "
        SELECT 
            o.id,
            o.n_ods,
            o.nome,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN ods o ON o.id = n.ods_prioritaria_id
        WHERE n.inscricao_completa = 1
        GROUP BY o.id, o.n_ods, o.nome
        ORDER BY o.id ASC
    ";
    $stmtODS = $pdo->query($sqlODS);
    $dataODS = $stmtODS->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 5: Modelo de negócio
    // =========================================================
    $sqlModelo = "
        SELECT 
            na.modelo_negocio,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN negocio_apresentacao na ON na.negocio_id = n.id
        WHERE n.inscricao_completa = 1
          AND na.modelo_negocio IS NOT NULL
          AND na.modelo_negocio != ''
        GROUP BY na.modelo_negocio
        ORDER BY total DESC
    ";
    $stmtModelo = $pdo->query($sqlModelo);
    $dataModelo = $stmtModelo->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 6: Médias de scores
    // =========================================================
    $sqlScores = "
        SELECT
            AVG(s.score_impacto) AS impacto_medio,
            AVG(s.score_investimento) AS investimento_medio,
            AVG(s.score_escala) AS escala_medio,
            AVG(s.score_geral) AS geral_medio
        FROM scores_negocios s
        JOIN negocios n ON n.id = s.negocio_id
        WHERE n.inscricao_completa = 1
    ";
    $stmtScores = $pdo->query($sqlScores);
    $dataScores = $stmtScores->fetch(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 7: Estágio de faturamento
    // =========================================================
    $sqlEstagioFaturamento = "
        SELECT
            nf.estagio_faturamento,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN negocio_financeiro nf ON nf.negocio_id = n.id
        WHERE n.inscricao_completa = 1
          AND nf.estagio_faturamento IS NOT NULL
          AND nf.estagio_faturamento != ''
        GROUP BY nf.estagio_faturamento
        ORDER BY total DESC
    ";
    $stmtEstagioFaturamento = $pdo->query($sqlEstagioFaturamento);
    $dataEstagioFaturamento = $stmtEstagioFaturamento->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 8: Faixa de faturamento
    // =========================================================
    $sqlFaixaFaturamento = "
        SELECT
            nf.faixa_faturamento,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN negocio_financeiro nf ON nf.negocio_id = n.id
        WHERE n.inscricao_completa = 1
          AND nf.faixa_faturamento IS NOT NULL
          AND nf.faixa_faturamento != ''
        GROUP BY nf.faixa_faturamento
        ORDER BY CASE nf.faixa_faturamento
            WHEN 'Não houve faturamento ainda' THEN 1
            WHEN 'Até R$ 100 mil' THEN 2
            WHEN 'R$ 100 mil – R$ 500 mil' THEN 3
            WHEN 'R$ 500 mil – R$ 1 milhão' THEN 4
            WHEN 'R$ 1 milhão – R$ 5 milhões' THEN 5
            WHEN 'R$ 5 milhões – R$ 20 milhões' THEN 6
            WHEN 'Acima de R$ 20 milhões' THEN 7
            ELSE 99
        END
    ";
    $stmtFaixaFaturamento = $pdo->query($sqlFaixaFaturamento);
    $dataFaixaFaturamento = $stmtFaixaFaturamento->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 9: Investimento externo
    // =========================================================
    $sqlInvestimentoExterno = "
        SELECT
            nf.investimento_externo,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN negocio_financeiro nf ON nf.negocio_id = n.id
        WHERE n.inscricao_completa = 1
          AND nf.investimento_externo IS NOT NULL
          AND nf.investimento_externo != ''
        GROUP BY nf.investimento_externo
        ORDER BY total DESC
    ";
    $stmtInvestimentoExterno = $pdo->query($sqlInvestimentoExterno);
    $dataInvestimentoExterno = $stmtInvestimentoExterno->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 10: Pronto para investimento
    // =========================================================
    $sqlProntoInvestimento = "
        SELECT
            nf.pronto_investimento,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN negocio_financeiro nf ON nf.negocio_id = n.id
        WHERE n.inscricao_completa = 1
          AND nf.pronto_investimento IS NOT NULL
          AND nf.pronto_investimento != ''
        GROUP BY nf.pronto_investimento
        ORDER BY total DESC
    ";
    $stmtProntoInvestimento = $pdo->query($sqlProntoInvestimento);
    $dataProntoInvestimento = $stmtProntoInvestimento->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 11: Faixa de investimento buscada
    // =========================================================
    $sqlFaixaInvestimento = "
        SELECT
            nf.faixa_investimento,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN negocio_financeiro nf ON nf.negocio_id = n.id
        WHERE n.inscricao_completa = 1
          AND nf.faixa_investimento IS NOT NULL
          AND nf.faixa_investimento != ''
        GROUP BY nf.faixa_investimento
        ORDER BY total DESC
    ";
    $stmtFaixaInvestimento = $pdo->query($sqlFaixaInvestimento);
    $dataFaixaInvestimento = $stmtFaixaInvestimento->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 12: Tipo de impacto
    // =========================================================
    $sqlTipoImpacto = "
        SELECT
            ni.tipo_impacto,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN negocio_impacto ni ON ni.negocio_id = n.id
        WHERE n.inscricao_completa = 1
          AND ni.tipo_impacto IS NOT NULL
          AND ni.tipo_impacto != ''
        GROUP BY ni.tipo_impacto
        ORDER BY total DESC
    ";
    $stmtTipoImpacto = $pdo->query($sqlTipoImpacto);
    $dataTipoImpacto = $stmtTipoImpacto->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 13: Alcance do impacto
    // =========================================================
    $sqlAlcance = "
        SELECT
            ni.alcance,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN negocio_impacto ni ON ni.negocio_id = n.id
        WHERE n.inscricao_completa = 1
          AND ni.alcance IS NOT NULL
          AND ni.alcance != ''
        GROUP BY ni.alcance
        ORDER BY CASE ni.alcance
            WHEN '1 a 50' THEN 1
            WHEN '51 a 100' THEN 2
            WHEN '101 a 200' THEN 3
            WHEN '201 a 500' THEN 4
            WHEN 'Acima de 500' THEN 5
            ELSE 99
        END
    ";
    $stmtAlcance = $pdo->query($sqlAlcance);
    $dataAlcance = $stmtAlcance->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 14: Reporte de impacto
    // =========================================================
    $sqlReporte = "
        SELECT
            ni.reporte,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN negocio_impacto ni ON ni.negocio_id = n.id
        WHERE n.inscricao_completa = 1
          AND ni.reporte IS NOT NULL
          AND ni.reporte != ''
        GROUP BY ni.reporte
        ORDER BY total DESC
    ";
    $stmtReporte = $pdo->query($sqlReporte);
    $dataReporte = $stmtReporte->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 15: Escala pretendida
    // =========================================================
    $sqlEscala = "
        SELECT
            nv.escala,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN negocio_visao nv ON nv.negocio_id = n.id
        WHERE n.inscricao_completa = 1
          AND nv.escala IS NOT NULL
          AND nv.escala != ''
        GROUP BY nv.escala
        ORDER BY total DESC
    ";
    $stmtEscala = $pdo->query($sqlEscala);
    $dataEscala = $stmtEscala->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 16: Sustentabilidade / potencial de crescimento
    // =========================================================
    $sqlSustentabilidade = "
        SELECT
            nv.sustentabilidade,
            COUNT(n.id) AS total
        FROM negocios n
        JOIN negocio_visao nv ON nv.negocio_id = n.id
        WHERE n.inscricao_completa = 1
          AND nv.sustentabilidade IS NOT NULL
          AND nv.sustentabilidade != ''
        GROUP BY nv.sustentabilidade
        ORDER BY total DESC
    ";
    $stmtSustentabilidade = $pdo->query($sqlSustentabilidade);
    $dataSustentabilidade = $stmtSustentabilidade->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // QUERY 17: Distribuição de score geral
    // =========================================================
    $sqlFaixasScoreGeral = "
        SELECT
            CASE
                WHEN s.score_geral IS NULL THEN 'Não calculado'
                WHEN s.score_geral < 40 THEN '0 a 39'
                WHEN s.score_geral < 60 THEN '40 a 59'
                WHEN s.score_geral < 80 THEN '60 a 79'
                ELSE '80 a 100'
            END AS faixa_score,
            COUNT(n.id) AS total
        FROM negocios n
        LEFT JOIN scores_negocios s ON s.negocio_id = n.id
        WHERE n.inscricao_completa = 1
        GROUP BY faixa_score
        ORDER BY CASE faixa_score
            WHEN 'Não calculado' THEN 1
            WHEN '0 a 39' THEN 2
            WHEN '40 a 59' THEN 3
            WHEN '60 a 79' THEN 4
            WHEN '80 a 100' THEN 5
            ELSE 99
        END
    ";
    $stmtFaixasScoreGeral = $pdo->query($sqlFaixasScoreGeral);
    $dataFaixasScoreGeral = $stmtFaixasScoreGeral->fetchAll(PDO::FETCH_ASSOC);

    // =========================================================
    // CAMPOS JSON - modo híbrido
    // =========================================================
    if ($suportaJsonTable) {
        $sqlFontesReceita = "
            SELECT jt.fonte AS nome, COUNT(*) AS total
            FROM negocios n
            JOIN negocio_financeiro nf ON nf.negocio_id = n.id
            JOIN JSON_TABLE(
                nf.fontes_receita,
                '$[*]' COLUMNS (
                    fonte VARCHAR(255) PATH '$'
                )
            ) AS jt
            WHERE n.inscricao_completa = 1
              AND nf.fontes_receita IS NOT NULL
              AND nf.fontes_receita != ''
            GROUP BY jt.fonte
            ORDER BY total DESC, jt.fonte ASC
        ";
        $dataFontesReceita = $pdo->query($sqlFontesReceita)->fetchAll(PDO::FETCH_ASSOC);

        $sqlBeneficiarios = "
            SELECT jt.beneficiario AS nome, COUNT(*) AS total
            FROM negocios n
            JOIN negocio_impacto ni ON ni.negocio_id = n.id
            JOIN JSON_TABLE(
                ni.beneficiarios,
                '$[*]' COLUMNS (
                    beneficiario VARCHAR(255) PATH '$'
                )
            ) AS jt
            WHERE n.inscricao_completa = 1
              AND ni.beneficiarios IS NOT NULL
              AND ni.beneficiarios != ''
            GROUP BY jt.beneficiario
            ORDER BY total DESC, jt.beneficiario ASC
        ";
        $dataBeneficiarios = $pdo->query($sqlBeneficiarios)->fetchAll(PDO::FETCH_ASSOC);

        $sqlMetricas = "
            SELECT jt.metrica AS nome, COUNT(*) AS total
            FROM negocios n
            JOIN negocio_impacto ni ON ni.negocio_id = n.id
            JOIN JSON_TABLE(
                ni.metricas,
                '$[*]' COLUMNS (
                    metrica VARCHAR(255) PATH '$'
                )
            ) AS jt
            WHERE n.inscricao_completa = 1
              AND ni.metricas IS NOT NULL
              AND ni.metricas != ''
            GROUP BY jt.metrica
            ORDER BY total DESC, jt.metrica ASC
        ";
        $dataMetricas = $pdo->query($sqlMetricas)->fetchAll(PDO::FETCH_ASSOC);

        $sqlFormasMedicao = "
            SELECT jt.forma AS nome, COUNT(*) AS total
            FROM negocios n
            JOIN negocio_impacto ni ON ni.negocio_id = n.id
            JOIN JSON_TABLE(
                ni.formas_medicao,
                '$[*]' COLUMNS (
                    forma VARCHAR(255) PATH '$'
                )
            ) AS jt
            WHERE n.inscricao_completa = 1
              AND ni.formas_medicao IS NOT NULL
              AND ni.formas_medicao != ''
            GROUP BY jt.forma
            ORDER BY total DESC, jt.forma ASC
        ";
        $dataFormasMedicao = $pdo->query($sqlFormasMedicao)->fetchAll(PDO::FETCH_ASSOC);

        $sqlApoios = "
            SELECT jt.apoio AS nome, COUNT(*) AS total
            FROM negocios n
            JOIN negocio_visao nv ON nv.negocio_id = n.id
            JOIN JSON_TABLE(
                nv.apoios,
                '$[*]' COLUMNS (
                    apoio VARCHAR(255) PATH '$'
                )
            ) AS jt
            WHERE n.inscricao_completa = 1
              AND nv.apoios IS NOT NULL
              AND nv.apoios != ''
            GROUP BY jt.apoio
            ORDER BY total DESC, jt.apoio ASC
        ";
        $dataApoios = $pdo->query($sqlApoios)->fetchAll(PDO::FETCH_ASSOC);

        $sqlAreas = "
            SELECT jt.area AS nome, COUNT(*) AS total
            FROM negocios n
            JOIN negocio_visao nv ON nv.negocio_id = n.id
            JOIN JSON_TABLE(
                nv.areas,
                '$[*]' COLUMNS (
                    area VARCHAR(255) PATH '$'
                )
            ) AS jt
            WHERE n.inscricao_completa = 1
              AND nv.areas IS NOT NULL
              AND nv.areas != ''
            GROUP BY jt.area
            ORDER BY total DESC, jt.area ASC
        ";
        $dataAreas = $pdo->query($sqlAreas)->fetchAll(PDO::FETCH_ASSOC);

        $sqlTemas = "
            SELECT jt.tema AS nome, COUNT(*) AS total
            FROM negocios n
            JOIN negocio_visao nv ON nv.negocio_id = n.id
            JOIN JSON_TABLE(
                nv.temas,
                '$[*]' COLUMNS (
                    tema VARCHAR(255) PATH '$'
                )
            ) AS jt
            WHERE n.inscricao_completa = 1
              AND nv.temas IS NOT NULL
              AND nv.temas != ''
            GROUP BY jt.tema
            ORDER BY total DESC, jt.tema ASC
        ";
        $dataTemas = $pdo->query($sqlTemas)->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sqlBaseFinanceiro = "
            SELECT nf.fontes_receita
            FROM negocios n
            JOIN negocio_financeiro nf ON nf.negocio_id = n.id
            WHERE n.inscricao_completa = 1
        ";
        $linhasFinanceiro = $pdo->query($sqlBaseFinanceiro)->fetchAll(PDO::FETCH_ASSOC);
        $dataFontesReceita = agregarJsonEmPhp($linhasFinanceiro, 'fontes_receita');

        $sqlBaseImpacto = "
            SELECT ni.beneficiarios, ni.metricas, ni.formas_medicao
            FROM negocios n
            JOIN negocio_impacto ni ON ni.negocio_id = n.id
            WHERE n.inscricao_completa = 1
        ";
        $linhasImpacto = $pdo->query($sqlBaseImpacto)->fetchAll(PDO::FETCH_ASSOC);
        $dataBeneficiarios = agregarJsonEmPhp($linhasImpacto, 'beneficiarios');
        $dataMetricas = agregarJsonEmPhp($linhasImpacto, 'metricas');
        $dataFormasMedicao = agregarJsonEmPhp($linhasImpacto, 'formas_medicao');

        $sqlBaseVisao = "
            SELECT nv.apoios, nv.areas, nv.temas
            FROM negocios n
            JOIN negocio_visao nv ON nv.negocio_id = n.id
            WHERE n.inscricao_completa = 1
        ";
        $linhasVisao = $pdo->query($sqlBaseVisao)->fetchAll(PDO::FETCH_ASSOC);
        $dataApoios = agregarJsonEmPhp($linhasVisao, 'apoios');
        $dataAreas = agregarJsonEmPhp($linhasVisao, 'areas');
        $dataTemas = agregarJsonEmPhp($linhasVisao, 'temas');
    }
} catch (PDOException $e) {
    die("Erro ao gerar relatórios: " . $e->getMessage());
}

$totalNegocios = (int)($dataKpis['total_negocios'] ?? 0);
$totalConcluidos = (int)($dataKpis['total_concluidos'] ?? 0);
$taxaConclusao = $totalNegocios > 0 ? round(($totalConcluidos / $totalNegocios) * 100) : 0;

$labelsCategoria = extrairLabels($dataCategoria, 'categoria');
$totaisCategoria = extrairTotais($dataCategoria);

$labelsUF = extrairLabels($dataUF, 'estado');
$totaisUF = extrairTotais($dataUF);

$labelsEixo = extrairLabels($dataEixo, 'eixo_tematico');
$totaisEixo = extrairTotais($dataEixo);

$labelsModelo = extrairLabels($dataModelo, 'modelo_negocio');
$totaisModelo = extrairTotais($dataModelo);

$labelsEstagioFaturamento = extrairLabels($dataEstagioFaturamento, 'estagio_faturamento');
$totaisEstagioFaturamento = extrairTotais($dataEstagioFaturamento);

$labelsFaixaFaturamento = extrairLabels($dataFaixaFaturamento, 'faixa_faturamento');
$totaisFaixaFaturamento = extrairTotais($dataFaixaFaturamento);

$labelsInvestimentoExterno = extrairLabels($dataInvestimentoExterno, 'investimento_externo');
$totaisInvestimentoExterno = extrairTotais($dataInvestimentoExterno);

$labelsProntoInvestimento = extrairLabels($dataProntoInvestimento, 'pronto_investimento');
$totaisProntoInvestimento = extrairTotais($dataProntoInvestimento);

$labelsFaixaInvestimento = extrairLabels($dataFaixaInvestimento, 'faixa_investimento');
$totaisFaixaInvestimento = extrairTotais($dataFaixaInvestimento);

$labelsTipoImpacto = extrairLabels($dataTipoImpacto, 'tipo_impacto');
$totaisTipoImpacto = extrairTotais($dataTipoImpacto);

$labelsAlcance = extrairLabels($dataAlcance, 'alcance');
$totaisAlcance = extrairTotais($dataAlcance);

$labelsReporte = extrairLabels($dataReporte, 'reporte');
$totaisReporte = extrairTotais($dataReporte);

$labelsEscala = extrairLabels($dataEscala, 'escala');
$totaisEscala = extrairTotais($dataEscala);

$labelsSustentabilidade = extrairLabels($dataSustentabilidade, 'sustentabilidade');
$totaisSustentabilidade = extrairTotais($dataSustentabilidade);

$labelsFaixasScore = extrairLabels($dataFaixasScoreGeral, 'faixa_score');
$totaisFaixasScore = extrairTotais($dataFaixasScoreGeral);

$mediaImpacto = round((float)($dataScores['impacto_medio'] ?? 0));
$mediaInvestimento = round((float)($dataScores['investimento_medio'] ?? 0));
$mediaEscala = round((float)($dataScores['escala_medio'] ?? 0));
$mediaGeral = round((float)($dataScores['geral_medio'] ?? 0));

$topFontesReceita = prepararTopDados($dataFontesReceita, 8, true);
$topBeneficiarios = prepararTopDados($dataBeneficiarios, 8, true);
$topMetricas = prepararTopDados($dataMetricas, 8, true);
$topFormasMedicao = prepararTopDados($dataFormasMedicao, 8, true);
$topApoios = prepararTopDados($dataApoios, 8, true);
$topAreas = prepararTopDados($dataAreas, 8, true);
$topTemas = prepararTopDados($dataTemas, 8, true);

$labelsFontesReceita = extrairLabelsLimitados($topFontesReceita, 'nome', 60);
$totaisFontesReceita = extrairTotais($topFontesReceita);

$labelsBeneficiarios = extrairLabelsLimitados($topBeneficiarios, 'nome', 60);
$totaisBeneficiarios = extrairTotais($topBeneficiarios);

$labelsMetricas = extrairLabelsLimitados($topMetricas, 'nome', 60);
$totaisMetricas = extrairTotais($topMetricas);

$labelsFormasMedicao = extrairLabelsLimitados($topFormasMedicao, 'nome', 60);
$totaisFormasMedicao = extrairTotais($topFormasMedicao);

$labelsApoios = extrairLabelsLimitados($topApoios, 'nome', 60);
$totaisApoios = extrairTotais($topApoios);

$labelsAreas = extrairLabelsLimitados($topAreas, 'nome', 60);
$totaisAreas = extrairTotais($topAreas);

$labelsTemas = extrairLabelsLimitados($topTemas, 'nome', 60);
$totaisTemas = extrairTotais($topTemas);

$tabelaFontesReceita = montarTabelaPercentual($topFontesReceita);
$tabelaBeneficiarios = montarTabelaPercentual($topBeneficiarios);
$tabelaMetricas = montarTabelaPercentual($topMetricas);
$tabelaFormasMedicao = montarTabelaPercentual($topFormasMedicao);
$tabelaApoios = montarTabelaPercentual($topApoios);
$tabelaAreas = montarTabelaPercentual($topAreas);
$tabelaTemas = montarTabelaPercentual($topTemas);


include __DIR__ . '/../app/views/admin/header.php';
?>



<div class="container-fluid py-0 px-3">

    <!-- ============================================================
         PAGE HEADER
         ============================================================ -->
    <div class="rpt-page-header mb-0">
        <div class="d-flex justify-content-between align-items-start" style="position:relative;z-index:2">
            <div>
                <span class="rpt-badge mb-2">
                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1z"/></svg>
                    Painel de Relatórios
                </span>
                <h1>Relatórios de Negócios</h1>
                <p>Painel consolidado dos negócios inscritos e concluídos.</p>
            </div>
        </div>
    </div>

    <!-- ============================================================
         KPI STRIP
         ============================================================ -->
    <div class="kpi-strip mb-4">
        <div class="kpi-card">
            <div class="kpi-icon teal">🏢</div>
            <div>
                <div class="kpi-label">Total de negócios</div>
                <div class="kpi-value teal"><?= (int)($dataKpis['total_negocios'] ?? 0) ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green">✅</div>
            <div>
                <div class="kpi-label">Inscrições concluídas</div>
                <div class="kpi-value green"><?= (int)($dataKpis['total_concluidos'] ?? 0) ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon amber">⏳</div>
            <div>
                <div class="kpi-label">Em andamento</div>
                <div class="kpi-value amber"><?= (int)($dataKpis['total_em_andamento'] ?? 0) ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon blue">📊</div>
            <div>
                <div class="kpi-label">Taxa de conclusão</div>
                <div class="kpi-value blue"><?= $taxaConclusao ?>%</div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SCORES (GAUGES)
         ============================================================ -->
    <div class="rpt-section">
        <div class="rpt-section-header">
            <div class="section-dot accent"></div>
            <h2>Scores médios — negócios concluídos</h2>
        </div>
        <div class="gauge-strip">
            <?php
            $gauges = [
                ['id' => 'gaugeImpacto',      'label' => 'Score de Impacto',      'color' => '#1a8a4a'],
                ['id' => 'gaugeInvestimento',  'label' => 'Score de Investimento', 'color' => '#0369a1'],
                ['id' => 'gaugeEscala',        'label' => 'Score de Escala',       'color' => '#c07a00'],
                ['id' => 'gaugeGeral',         'label' => 'Score Geral',           'color' => '#6f42c1'],
            ];
            foreach ($gauges as $g): ?>
                <div class="gauge-card">
                    <h6><?= $g['label'] ?></h6>
                    <div style="height:140px;"><canvas id="<?= $g['id'] ?>"></canvas></div>
                    <small>Média dos negócios concluídos</small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ============================================================
         PERFIL DO NEGÓCIO
         ============================================================ -->
    <div class="rpt-section">
        <div class="rpt-section-header">
            <div class="section-dot"></div>
            <h2>Perfil dos negócios</h2>
        </div>
        <div class="grid-2">

            <!-- Categoria -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar"></div>
                    <h5>Negócios por categoria</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoCategoria">
                        <canvas id="graficoCategoria"></canvas>
                    </div>
                </div>
            </div>

            <!-- Modelo de negócio -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar green"></div>
                    <h5>Modelo de negócio</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:320px;">
                        <canvas id="graficoModelo"></canvas>
                    </div>
                </div>
            </div>

            <!-- UF -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar blue"></div>
                    <h5>Negócios por estado</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoUF">
                        <canvas id="graficoUF"></canvas>
                    </div>
                </div>
            </div>

            <!-- Eixo temático -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar purple"></div>
                    <h5>Eixo temático</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoEixo">
                        <canvas id="graficoEixo"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ODS (full width) -->
    <div class="rpt-section">
        <div class="rpt-section-header">
            <div class="section-dot accent"></div>
            <h2>ODS prioritária</h2>
        </div>
        <div class="chart-card">
            <div class="chart-card-header">
                <div class="accent-bar green"></div>
                <h5>Distribuição por Objetivo de Desenvolvimento Sustentável</h5>
            </div>
            <div class="chart-card-body">
                <div class="chart-wrap" style="height:340px;">
                    <canvas id="graficoODS"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SCORES & FATURAMENTO
         ============================================================ -->
    <div class="rpt-section">
        <div class="rpt-section-header">
            <div class="section-dot blue"></div>
            <h2>Scores e faturamento</h2>
        </div>
        <div class="grid-2">

            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar blue"></div>
                    <h5>Faixas de score geral</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:280px;">
                        <canvas id="graficoFaixaScore"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar amber"></div>
                    <h5>Estágio de faturamento</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoEstagioFaturamento">
                        <canvas id="graficoEstagioFaturamento"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar amber"></div>
                    <h5>Faixa de faturamento</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoFaixaFaturamento">
                        <canvas id="graficoFaixaFaturamento"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ============================================================
         INVESTIMENTO
         ============================================================ -->
    <div class="rpt-section">
        <div class="rpt-section-header">
            <div class="section-dot purple"></div>
            <h2>Investimento</h2>
        </div>
        <div class="grid-3">

            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar purple"></div>
                    <h5>Investimento externo</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:280px;">
                        <canvas id="graficoInvestimentoExterno"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar purple"></div>
                    <h5>Prontidão para investimento</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:280px;">
                        <canvas id="graficoProntoInvestimento"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar purple"></div>
                    <h5>Faixa de investimento buscada</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoFaixaInvestimento">
                        <canvas id="graficoFaixaInvestimento"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ============================================================
         IMPACTO
         ============================================================ -->
    <div class="rpt-section">
        <div class="rpt-section-header">
            <div class="section-dot accent"></div>
            <h2>Impacto e escala</h2>
        </div>
        <div class="grid-3">

            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar green"></div>
                    <h5>Tipo de impacto</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:280px;">
                        <canvas id="graficoTipoImpacto"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar"></div>
                    <h5>Alcance</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:280px;">
                        <canvas id="graficoAlcance"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar blue"></div>
                    <h5>Reporte de impacto</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:280px;">
                        <canvas id="graficoReporte"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Escala + Sustentabilidade -->
    <div class="rpt-section">
        <div class="grid-2">

            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar"></div>
                    <h5>Escala pretendida</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoEscala">
                        <canvas id="graficoEscala"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar green"></div>
                    <h5>Sustentabilidade do modelo</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoSustentabilidade">
                        <canvas id="graficoSustentabilidade"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ============================================================
         MODELO & MÉTRICAS
         ============================================================ -->
    <div class="rpt-section">
        <div class="rpt-section-header">
            <div class="section-dot blue"></div>
            <h2>Receita e métricas de impacto</h2>
        </div>
        <div class="grid-2">

            <?php
            $cardsComTabela = [
                ['canvas' => 'graficoFontesReceita',  'title' => 'Fontes de receita',       'tabela' => $tabelaFontesReceita,  'cor' => ''],
                ['canvas' => 'graficoBeneficiarios',   'title' => 'Beneficiários priorizados','tabela' => $tabelaBeneficiarios,  'cor' => 'blue'],
                ['canvas' => 'graficoMetricas',        'title' => 'Métricas acompanhadas',   'tabela' => $tabelaMetricas,       'cor' => 'green'],
                ['canvas' => 'graficoFormasMedicao',   'title' => 'Formas de medição',        'tabela' => $tabelaFormasMedicao,  'cor' => 'amber'],
            ];
            foreach ($cardsComTabela as $c):
                $maxPct = !empty($c['tabela']) ? max(array_column($c['tabela'], 'percentual')) : 100;
                $maxPct = $maxPct > 0 ? $maxPct : 100;
            ?>
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar <?= $c['cor'] ?>"></div>
                    <h5><?= $c['title'] ?></h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-<?= $c['canvas'] ?>">
                        <canvas id="<?= $c['canvas'] ?>"></canvas>
                    </div>
                    <?php if (!empty($c['tabela'])): ?>
                    <div class="rpt-table-wrap">
                        <table class="rpt-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th style="width:50px;text-align:right">Total</th>
                                    <th style="width:120px;text-align:right">%</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($c['tabela'] as $linha): ?>
                                <tr>
                                    <td><?= htmlspecialchars($linha['nome']) ?></td>
                                    <td class="td-num"><?= (int)$linha['total'] ?></td>
                                    <td>
                                        <div class="pct-bar-wrap">
                                            <div class="pct-bar">
                                                <div class="pct-bar-fill" style="width:<?= min(100, round($linha['percentual'] / $maxPct * 100)) ?>%"></div>
                                            </div>
                                            <span class="td-pct" style="min-width:38px"><?= number_format((float)$linha['percentual'], 1, ',', '.') ?>%</span>
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
            <?php endforeach; ?>

        </div>
    </div>

    <!-- ============================================================
         APOIOS, ÁREAS E APRENDIZADOS
         ============================================================ -->
    <div class="rpt-section" style="margin-bottom: 3rem;">
        <div class="rpt-section-header">
            <div class="section-dot purple"></div>
            <h2>Apoios, fortalecimento e aprendizado</h2>
        </div>
        <div class="grid-3">

            <?php
            $cardsApoio = [
                ['canvas' => 'graficoApoios', 'title' => 'Apoios buscados',     'tabela' => $tabelaApoios, 'cor' => 'purple'],
                ['canvas' => 'graficoAreas',  'title' => 'Áreas a fortalecer',  'tabela' => $tabelaAreas,  'cor' => 'blue'],
                ['canvas' => 'graficoTemas',  'title' => 'Temas de aprendizado','tabela' => $tabelaTemas,  'cor' => 'green'],
            ];
            foreach ($cardsApoio as $c):
                $maxPct = !empty($c['tabela']) ? max(array_column($c['tabela'], 'percentual')) : 100;
                $maxPct = $maxPct > 0 ? $maxPct : 100;
            ?>
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar <?= $c['cor'] ?>"></div>
                    <h5><?= $c['title'] ?></h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-<?= $c['canvas'] ?>">
                        <canvas id="<?= $c['canvas'] ?>"></canvas>
                    </div>
                    <?php if (!empty($c['tabela'])): ?>
                    <div class="rpt-table-wrap">
                        <table class="rpt-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th style="width:50px;text-align:right">Total</th>
                                    <th style="width:120px;text-align:right">%</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($c['tabela'] as $linha): ?>
                                <tr>
                                    <td><?= htmlspecialchars($linha['nome']) ?></td>
                                    <td class="td-num"><?= (int)$linha['total'] ?></td>
                                    <td>
                                        <div class="pct-bar-wrap">
                                            <div class="pct-bar">
                                                <div class="pct-bar-fill" style="width:<?= min(100, round($linha['percentual'] / $maxPct * 100)) ?>%"></div>
                                            </div>
                                            <span class="td-pct" style="min-width:38px"><?= number_format((float)$linha['percentual'], 1, ',', '.') ?>%</span>
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
            <?php endforeach; ?>

        </div>
    </div>

</div><!-- /container-fluid -->

<script>
/**
 * Calcula altura dinâmica para gráficos de barras horizontais
 * baseada no número de itens (evita labels cortadas em datasets grandes)
 */
function alturaHorizontal(numItens, minH = 220, porItem = 42) {
    return Math.max(minH, numItens * porItem);
}

function setAltura(wrapId, altura) {
    const el = document.getElementById(wrapId);
    if (el) el.style.height = altura + 'px';
}

document.addEventListener('DOMContentLoaded', function () {

    // ---- Dados do PHP ----
    const labelsCategoria           = <?= json_encode($labelsCategoria, JSON_UNESCAPED_UNICODE) ?>;
    const totaisCategoria           = <?= json_encode($totaisCategoria) ?>;
    const labelsUF                  = <?= json_encode($labelsUF, JSON_UNESCAPED_UNICODE) ?>;
    const totaisUF                  = <?= json_encode($totaisUF) ?>;
    const labelsEixo                = <?= json_encode($labelsEixo, JSON_UNESCAPED_UNICODE) ?>;
    const totaisEixo                = <?= json_encode($totaisEixo) ?>;
    const labelsModelo              = <?= json_encode($labelsModelo, JSON_UNESCAPED_UNICODE) ?>;
    const totaisModelo              = <?= json_encode($totaisModelo) ?>;
    const labelsFaixasScore         = <?= json_encode($labelsFaixasScore, JSON_UNESCAPED_UNICODE) ?>;
    const totaisFaixasScore         = <?= json_encode($totaisFaixasScore) ?>;
    const labelsEstagioFaturamento  = <?= json_encode($labelsEstagioFaturamento, JSON_UNESCAPED_UNICODE) ?>;
    const totaisEstagioFaturamento  = <?= json_encode($totaisEstagioFaturamento) ?>;
    const labelsFaixaFaturamento    = <?= json_encode($labelsFaixaFaturamento, JSON_UNESCAPED_UNICODE) ?>;
    const totaisFaixaFaturamento    = <?= json_encode($totaisFaixaFaturamento) ?>;
    const labelsInvestimentoExterno = <?= json_encode($labelsInvestimentoExterno, JSON_UNESCAPED_UNICODE) ?>;
    const totaisInvestimentoExterno = <?= json_encode($totaisInvestimentoExterno) ?>;
    const labelsProntoInvestimento  = <?= json_encode($labelsProntoInvestimento, JSON_UNESCAPED_UNICODE) ?>;
    const totaisProntoInvestimento  = <?= json_encode($totaisProntoInvestimento) ?>;
    const labelsFaixaInvestimento   = <?= json_encode($labelsFaixaInvestimento, JSON_UNESCAPED_UNICODE) ?>;
    const totaisFaixaInvestimento   = <?= json_encode($totaisFaixaInvestimento) ?>;
    const labelsTipoImpacto         = <?= json_encode($labelsTipoImpacto, JSON_UNESCAPED_UNICODE) ?>;
    const totaisTipoImpacto         = <?= json_encode($totaisTipoImpacto) ?>;
    const labelsAlcance             = <?= json_encode($labelsAlcance, JSON_UNESCAPED_UNICODE) ?>;
    const totaisAlcance             = <?= json_encode($totaisAlcance) ?>;
    const labelsReporte             = <?= json_encode($labelsReporte, JSON_UNESCAPED_UNICODE) ?>;
    const totaisReporte             = <?= json_encode($totaisReporte) ?>;
    const labelsEscala              = <?= json_encode($labelsEscala, JSON_UNESCAPED_UNICODE) ?>;
    const totaisEscala              = <?= json_encode($totaisEscala) ?>;
    const labelsSustentabilidade    = <?= json_encode($labelsSustentabilidade, JSON_UNESCAPED_UNICODE) ?>;
    const totaisSustentabilidade    = <?= json_encode($totaisSustentabilidade) ?>;
    const labelsFontesReceita       = <?= json_encode($labelsFontesReceita, JSON_UNESCAPED_UNICODE) ?>;
    const totaisFontesReceita       = <?= json_encode($totaisFontesReceita) ?>;
    const labelsBeneficiarios       = <?= json_encode($labelsBeneficiarios, JSON_UNESCAPED_UNICODE) ?>;
    const totaisBeneficiarios       = <?= json_encode($totaisBeneficiarios) ?>;
    const labelsMetricas            = <?= json_encode($labelsMetricas, JSON_UNESCAPED_UNICODE) ?>;
    const totaisMetricas            = <?= json_encode($totaisMetricas) ?>;
    const labelsFormasMedicao       = <?= json_encode($labelsFormasMedicao, JSON_UNESCAPED_UNICODE) ?>;
    const totaisFormasMedicao       = <?= json_encode($totaisFormasMedicao) ?>;
    const labelsApoios              = <?= json_encode($labelsApoios, JSON_UNESCAPED_UNICODE) ?>;
    const totaisApoios              = <?= json_encode($totaisApoios) ?>;
    const labelsAreas               = <?= json_encode($labelsAreas, JSON_UNESCAPED_UNICODE) ?>;
    const totaisAreas               = <?= json_encode($totaisAreas) ?>;
    const labelsTemas               = <?= json_encode($labelsTemas, JSON_UNESCAPED_UNICODE) ?>;
    const totaisTemas               = <?= json_encode($totaisTemas) ?>;
    const dataODS                   = <?= json_encode($dataODS, JSON_UNESCAPED_UNICODE) ?>;

    // ---- Ajuste de alturas dinâmicas (gráficos horizontais) ----
    const horizontais = [
        { wrap: 'wrap-graficoCategoria',          n: labelsCategoria.length,          vert: true  },
        { wrap: 'wrap-graficoUF',                 n: labelsUF.length,                 vert: false },
        { wrap: 'wrap-graficoEixo',               n: labelsEixo.length,               vert: false },
        { wrap: 'wrap-graficoEstagioFaturamento', n: labelsEstagioFaturamento.length,  vert: false },
        { wrap: 'wrap-graficoFaixaFaturamento',   n: labelsFaixaFaturamento.length,    vert: false },
        { wrap: 'wrap-graficoFaixaInvestimento',  n: labelsFaixaInvestimento.length,   vert: false },
        { wrap: 'wrap-graficoEscala',             n: labelsEscala.length,              vert: false },
        { wrap: 'wrap-graficoSustentabilidade',   n: labelsSustentabilidade.length,    vert: false },
        { wrap: 'wrap-graficoFontesReceita',      n: labelsFontesReceita.length,       vert: false },
        { wrap: 'wrap-graficoBeneficiarios',      n: labelsBeneficiarios.length,       vert: false },
        { wrap: 'wrap-graficoMetricas',           n: labelsMetricas.length,            vert: false },
        { wrap: 'wrap-graficoFormasMedicao',      n: labelsFormasMedicao.length,       vert: false },
        { wrap: 'wrap-graficoApoios',             n: labelsApoios.length,              vert: false },
        { wrap: 'wrap-graficoAreas',              n: labelsAreas.length,               vert: false },
        { wrap: 'wrap-graficoTemas',              n: labelsTemas.length,               vert: false },
    ];
    horizontais.forEach(({ wrap, n, vert }) => {
        const minH  = vert ? 260 : 220;
        const pItem = vert ? 38  : 44;
        setAltura(wrap, alturaHorizontal(n, minH, pItem));
    });

    // ---- Gauges ----
    criarGauge('gaugeImpacto',     <?= json_encode($mediaImpacto) ?>,     'Impacto',      '#1a8a4a');
    criarGauge('gaugeInvestimento',<?= json_encode($mediaInvestimento) ?>, 'Investimento', '#0369a1');
    criarGauge('gaugeEscala',      <?= json_encode($mediaEscala) ?>,       'Escala',       '#c07a00');
    criarGauge('gaugeGeral',       <?= json_encode($mediaGeral) ?>,        'Geral',        '#6f42c1');

    // ---- Gráficos ----
    criarGraficoBarra('graficoCategoria',          labelsCategoria,          totaisCategoria,          'Negócios',    false);
    criarGraficoBarra('graficoUF',                 labelsUF,                 totaisUF,                 'Negócios',    true);
    criarGraficoBarra('graficoEixo',               labelsEixo,               totaisEixo,               'Negócios',    true);
    criarGraficoODS(  'graficoODS',                dataODS);
    criarGraficoCircular('graficoModelo',           labelsModelo,             totaisModelo,             'doughnut');

    criarGraficoBarra('graficoFaixaScore',          labelsFaixasScore,        totaisFaixasScore,        'Negócios',    false);
    criarGraficoBarra('graficoEstagioFaturamento',  labelsEstagioFaturamento, totaisEstagioFaturamento, 'Negócios',    true);
    criarGraficoBarra('graficoFaixaFaturamento',    labelsFaixaFaturamento,   totaisFaixaFaturamento,   'Negócios',    true);

    criarGraficoCircular('graficoInvestimentoExterno', labelsInvestimentoExterno, totaisInvestimentoExterno, 'pie');
    criarGraficoCircular('graficoProntoInvestimento',  labelsProntoInvestimento,  totaisProntoInvestimento,  'doughnut');
    criarGraficoBarra('graficoFaixaInvestimento',   labelsFaixaInvestimento,  totaisFaixaInvestimento,  'Negócios',    true);

    criarGraficoCircular('graficoTipoImpacto', labelsTipoImpacto, totaisTipoImpacto, 'doughnut');
    criarGraficoBarra('graficoAlcance',        labelsAlcance,     totaisAlcance,     'Negócios',    false);
    criarGraficoCircular('graficoReporte',     labelsReporte,     totaisReporte,     'pie');

    criarGraficoBarra('graficoEscala',           labelsEscala,           totaisEscala,           'Negócios',    true);
    criarGraficoBarra('graficoSustentabilidade', labelsSustentabilidade, totaisSustentabilidade, 'Negócios',    true);

    criarGraficoBarra('graficoFontesReceita', labelsFontesReceita, totaisFontesReceita, 'Ocorrências', true);
    criarGraficoBarra('graficoBeneficiarios', labelsBeneficiarios, totaisBeneficiarios, 'Ocorrências', true);
    criarGraficoBarra('graficoMetricas',      labelsMetricas,      totaisMetricas,      'Ocorrências', true);
    criarGraficoBarra('graficoFormasMedicao', labelsFormasMedicao, totaisFormasMedicao, 'Ocorrências', true);
    criarGraficoBarra('graficoApoios',        labelsApoios,        totaisApoios,        'Ocorrências', true);
    criarGraficoBarra('graficoAreas',         labelsAreas,         totaisAreas,         'Ocorrências', true);
    criarGraficoBarra('graficoTemas',         labelsTemas,         totaisTemas,         'Ocorrências', true);
});
</script>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>