<?php
session_start();

require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/relatorios_helper.php';

require_admin_login();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config/db.php';

function mysqlSuportaJsonTableU(PDO $pdo): bool
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

function agregarJsonU(array $linhas, string $campoJson): array
{
    $contagem = [];
    foreach ($linhas as $linha) {
        $json = $linha[$campoJson] ?? null;
        if (empty($json)) continue;
        $itens = json_decode($json, true);
        if (!is_array($itens)) continue;
        foreach ($itens as $item) {
            $item = trim((string)$item);
            if ($item === '') continue;
            $contagem[$item] = ($contagem[$item] ?? 0) + 1;
        }
    }
    arsort($contagem);
    $resultado = [];
    foreach ($contagem as $nome => $total) {
        $resultado[] = ['nome' => $nome, 'total' => (int)$total];
    }
    return $resultado;
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("SET NAMES utf8mb4");

    $suportaJson = mysqlSuportaJsonTableU($pdo);

    $kpiEmp        = (int)$pdo->query("SELECT COUNT(*) FROM empreendedores WHERE status = 'ativo'")->fetchColumn();
    $kpiEmpTotal   = (int)$pdo->query("SELECT COUNT(*) FROM empreendedores")->fetchColumn();
    $kpiSoc        = (int)$pdo->query("SELECT COUNT(*) FROM sociedade_civil")->fetchColumn();
    $kpiPar        = (int)$pdo->query("SELECT COUNT(*) FROM parceiros")->fetchColumn();
    $kpiTotal      = $kpiEmp + $kpiSoc + $kpiPar;
    $kpiParCompleto = (int)$pdo->query("SELECT COUNT(*) FROM parceiros WHERE etapa_atual >= 6")->fetchColumn();

    $empEstado = $pdo->query("
        SELECT estado, COUNT(*) AS total
        FROM empreendedores
        WHERE status = 'ativo'
          AND estado IS NOT NULL AND estado != ''
        GROUP BY estado ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $empGenero = $pdo->query("
        SELECT COALESCE(genero, 'Não informado') AS genero, COUNT(*) AS total
        FROM empreendedores
        WHERE status = 'ativo'
        GROUP BY genero ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $empEtnia = $pdo->query("
        SELECT etnia, COUNT(*) AS total
        FROM empreendedores
        WHERE status = 'ativo'
          AND eh_fundador = 1
          AND etnia IS NOT NULL AND etnia != ''
        GROUP BY etnia ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $empFormacao = $pdo->query("
        SELECT formacao, COUNT(*) AS total
        FROM empreendedores
        WHERE status = 'ativo'
          AND eh_fundador = 1
          AND formacao IS NOT NULL AND formacao != ''
        GROUP BY formacao ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $empOrigem = $pdo->query("
        SELECT COALESCE(origem_conhecimento, 'Não informado') AS origem_conhecimento, COUNT(*) AS total
        FROM empreendedores
        WHERE status = 'ativo'
          AND origem_conhecimento IS NOT NULL AND origem_conhecimento != ''
        GROUP BY origem_conhecimento ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $empFundador = $pdo->query("
        SELECT
            SUM(eh_fundador = 1) AS fundadores,
            SUM(eh_fundador = 0) AS nao_fundadores
        FROM empreendedores
        WHERE status = 'ativo'
    ")->fetch(PDO::FETCH_ASSOC);

    $empGrupoVulneravel = $pdo->query("
        SELECT grupo_vulneravel AS nome, COUNT(*) AS total
        FROM empreendedores
        WHERE status = 'ativo'
          AND eh_fundador = 1
          AND grupo_vulneravel IS NOT NULL AND grupo_vulneravel != ''
        GROUP BY grupo_vulneravel ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $empPorMes = $pdo->query("
        SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes, COUNT(*) AS total
        FROM empreendedores
        WHERE status = 'ativo'
          AND criado_em >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY mes ORDER BY mes ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $socEstado = $pdo->query("
        SELECT estado, COUNT(*) AS total
        FROM sociedade_civil
        WHERE estado IS NOT NULL AND estado != ''
        GROUP BY estado ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $socProfissao = $pdo->query("
        SELECT COALESCE(profissao, 'Não informado') AS profissao, COUNT(*) AS total
        FROM sociedade_civil
        WHERE profissao IS NOT NULL AND profissao != ''
        GROUP BY profissao ORDER BY total DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    $socOrganizacao = $pdo->query("
        SELECT organizacao, COUNT(*) AS total
        FROM sociedade_civil
        WHERE organizacao IS NOT NULL AND organizacao != ''
        GROUP BY organizacao ORDER BY total DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    $socPorMes = $pdo->query("
        SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes, COUNT(*) AS total
        FROM sociedade_civil
        WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY mes ORDER BY mes ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if ($suportaJson) {
        $socIdentificacoes = $pdo->query("
            SELECT jt.item AS nome, COUNT(*) AS total
            FROM sociedade_civil sc
            JOIN JSON_TABLE(sc.identificacoes, '\$[*]' COLUMNS (item VARCHAR(255) PATH '\$')) AS jt
            WHERE sc.identificacoes IS NOT NULL
            GROUP BY jt.item ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $socMotivacoes = $pdo->query("
            SELECT jt.item AS nome, COUNT(*) AS total
            FROM sociedade_civil sc
            JOIN JSON_TABLE(sc.motivacoes, '\$[*]' COLUMNS (item VARCHAR(255) PATH '\$')) AS jt
            WHERE sc.motivacoes IS NOT NULL
            GROUP BY jt.item ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $socInteresses = $pdo->query("
            SELECT jt.item AS nome, COUNT(*) AS total
            FROM sociedade_civil sc
            JOIN JSON_TABLE(sc.interesses, '\$[*]' COLUMNS (item VARCHAR(255) PATH '\$')) AS jt
            WHERE sc.interesses IS NOT NULL
            GROUP BY jt.item ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $socODS = $pdo->query("
            SELECT jt.item AS nome, COUNT(*) AS total
            FROM sociedade_civil sc
            JOIN JSON_TABLE(sc.ods, '\$[*]' COLUMNS (item VARCHAR(255) PATH '\$')) AS jt
            WHERE sc.ods IS NOT NULL
            GROUP BY jt.item ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $socEngajamento = $pdo->query("
            SELECT jt.item AS nome, COUNT(*) AS total
            FROM sociedade_civil sc
            JOIN JSON_TABLE(sc.engajamento, '\$[*]' COLUMNS (item VARCHAR(255) PATH '\$')) AS jt
            WHERE sc.engajamento IS NOT NULL
            GROUP BY jt.item ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $socSetores = $pdo->query("
            SELECT jt.item AS nome, COUNT(*) AS total
            FROM sociedade_civil sc
            JOIN JSON_TABLE(sc.setores, '\$[*]' COLUMNS (item VARCHAR(255) PATH '\$')) AS jt
            WHERE sc.setores IS NOT NULL
            GROUP BY jt.item ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $rawSoc = $pdo->query("SELECT identificacoes, motivacoes, interesses, ods, engajamento, setores FROM sociedade_civil")->fetchAll(PDO::FETCH_ASSOC);
        $socIdentificacoes = agregarJsonU($rawSoc, 'identificacoes');
        $socMotivacoes     = agregarJsonU($rawSoc, 'motivacoes');
        $socInteresses     = agregarJsonU($rawSoc, 'interesses');
        $socODS            = agregarJsonU($rawSoc, 'ods');
        $socEngajamento    = agregarJsonU($rawSoc, 'engajamento');
        $socSetores        = agregarJsonU($rawSoc, 'setores');
    }

    $parEstado = $pdo->query("
        SELECT estado, COUNT(*) AS total
        FROM parceiros
        WHERE estado IS NOT NULL AND estado != ''
        GROUP BY estado ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $parEtapa = $pdo->query("
        SELECT etapa_atual, COUNT(*) AS total
        FROM parceiros
        GROUP BY etapa_atual ORDER BY etapa_atual ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $parPorMes = $pdo->query("
        SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes, COUNT(*) AS total
        FROM parceiros
        WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY mes ORDER BY mes ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if ($suportaJson) {
        $parTipos = $pdo->query("
            SELECT jt.item AS nome, COUNT(*) AS total
            FROM parceiro_contrato pc
            JOIN JSON_TABLE(pc.tipos_parceria, '\$[*]' COLUMNS (item VARCHAR(255) PATH '\$')) AS jt
            WHERE pc.tipos_parceria IS NOT NULL
            GROUP BY jt.item ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $parNatureza = $pdo->query("
            SELECT jt.item AS nome, COUNT(*) AS total
            FROM parceiro_contrato pc
            JOIN JSON_TABLE(pc.natureza_parceria, '\$[*]' COLUMNS (item VARCHAR(255) PATH '\$')) AS jt
            WHERE pc.natureza_parceria IS NOT NULL
            GROUP BY jt.item ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $parEixos = $pdo->query("
            SELECT jt.item AS nome, COUNT(*) AS total
            FROM parceiro_interesses pi
            JOIN JSON_TABLE(pi.eixos_interesse, '\$[*]' COLUMNS (item VARCHAR(255) PATH '\$')) AS jt
            WHERE pi.eixos_interesse IS NOT NULL
            GROUP BY jt.item ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $parPerfilImpacto = $pdo->query("
            SELECT jt.item AS nome, COUNT(*) AS total
            FROM parceiro_interesses pi
            JOIN JSON_TABLE(pi.perfil_impacto, '\$[*]' COLUMNS (item VARCHAR(255) PATH '\$')) AS jt
            WHERE pi.perfil_impacto IS NOT NULL
            GROUP BY jt.item ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $parSetores = $pdo->query("
            SELECT jt.item AS nome, COUNT(*) AS total
            FROM parceiro_interesses pi
            JOIN JSON_TABLE(pi.setores_interesse, '\$[*]' COLUMNS (item VARCHAR(255) PATH '\$')) AS jt
            WHERE pi.setores_interesse IS NOT NULL
            GROUP BY jt.item ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $rawPc = $pdo->query("SELECT tipos_parceria, natureza_parceria FROM parceiro_contrato")->fetchAll(PDO::FETCH_ASSOC);
        $rawPi = $pdo->query("SELECT eixos_interesse, perfil_impacto, setores_interesse FROM parceiro_interesses")->fetchAll(PDO::FETCH_ASSOC);
        $parTipos         = agregarJsonU($rawPc, 'tipos_parceria');
        $parNatureza      = agregarJsonU($rawPc, 'natureza_parceria');
        $parEixos         = agregarJsonU($rawPi, 'eixos_interesse');
        $parPerfilImpacto = agregarJsonU($rawPi, 'perfil_impacto');
        $parSetores       = agregarJsonU($rawPi, 'setores_interesse');
    }

    $parODS = $pdo->query("
        SELECT o.n_ods, o.nome, COUNT(po.parceiro_id) AS total
        FROM parceiro_ods po
        JOIN ods o ON o.id = po.ods_id
        GROUP BY o.id, o.n_ods, o.nome
        ORDER BY o.id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $parAlcance = $pdo->query("
        SELECT alcance_impacto AS nome, COUNT(*) AS total
        FROM parceiro_interesses
        WHERE alcance_impacto IS NOT NULL AND alcance_impacto != ''
        GROUP BY alcance_impacto ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao gerar relatórios de usuários: " . $e->getMessage());
}

$labEmpEstado   = extrairLabels($empEstado, 'estado');
$totEmpEstado   = extrairTotais($empEstado);
$labEmpGenero   = extrairLabels($empGenero, 'genero');
$totEmpGenero   = extrairTotais($empGenero);
$labEmpEtnia    = extrairLabels($empEtnia, 'etnia');
$totEmpEtnia    = extrairTotais($empEtnia);
$labEmpFormacao = extrairLabels($empFormacao, 'formacao');
$totEmpFormacao = extrairTotais($empFormacao);
$labEmpOrigem   = extrairLabels($empOrigem, 'origem_conhecimento');
$totEmpOrigem   = extrairTotais($empOrigem);
$labEmpMes      = array_column($empPorMes, 'mes');
$totEmpMes      = array_column($empPorMes, 'total');
$labEmpGrupo    = extrairLabels($empGrupoVulneravel, 'nome');
$totEmpGrupo    = extrairTotais($empGrupoVulneravel);

$labSocEstado = extrairLabels($socEstado, 'estado');
$totSocEstado = extrairTotais($socEstado);
$labSocProf   = extrairLabels($socProfissao, 'profissao');
$totSocProf   = extrairTotais($socProfissao);
$labSocOrg    = extrairLabels($socOrganizacao, 'organizacao');
$totSocOrg    = extrairTotais($socOrganizacao);
$labSocMes    = array_column($socPorMes, 'mes');
$totSocMes    = array_column($socPorMes, 'total');

$topSocIdent = prepararTopDados($socIdentificacoes, 8, true);
$topSocMotiv = prepararTopDados($socMotivacoes, 8, true);
$topSocInt   = prepararTopDados($socInteresses, 8, true);
$topSocODS   = prepararTopDados($socODS, 8, true);
$topSocEng   = prepararTopDados($socEngajamento, 8, true);
$topSocSet   = prepararTopDados($socSetores, 8, true);

$labSocIdent = extrairLabelsLimitados($topSocIdent, 'nome', 60);
$totSocIdent = extrairTotais($topSocIdent);
$labSocMotiv = extrairLabelsLimitados($topSocMotiv, 'nome', 60);
$totSocMotiv = extrairTotais($topSocMotiv);
$labSocInt   = extrairLabelsLimitados($topSocInt, 'nome', 60);
$totSocInt   = extrairTotais($topSocInt);
$labSocODS   = extrairLabelsLimitados($topSocODS, 'nome', 60);
$totSocODS   = extrairTotais($topSocODS);
$labSocEng   = extrairLabelsLimitados($topSocEng, 'nome', 60);
$totSocEng   = extrairTotais($topSocEng);
$labSocSet   = extrairLabelsLimitados($topSocSet, 'nome', 60);
$totSocSet   = extrairTotais($topSocSet);

$tabSocIdent = montarTabelaPercentual($topSocIdent);
$tabSocMotiv = montarTabelaPercentual($topSocMotiv);
$tabSocInt   = montarTabelaPercentual($topSocInt);
$tabSocEng   = montarTabelaPercentual($topSocEng);
$tabSocSet   = montarTabelaPercentual($topSocSet);

$labParEstado  = extrairLabels($parEstado, 'estado');
$totParEstado  = extrairTotais($parEstado);
$labParMes     = array_column($parPorMes, 'mes');
$totParMes     = array_column($parPorMes, 'total');
$labParAlcance = extrairLabels($parAlcance, 'nome');
$totParAlcance = extrairTotais($parAlcance);

$topParTipos = prepararTopDados($parTipos, 8, true);
$topParNat   = prepararTopDados($parNatureza, 8, true);
$topParEixos = prepararTopDados($parEixos, 8, true);
$topParPerf  = prepararTopDados($parPerfilImpacto, 8, true);
$topParSet   = prepararTopDados($parSetores, 8, true);

$labParTipos = extrairLabelsLimitados($topParTipos, 'nome', 60);
$totParTipos = extrairTotais($topParTipos);
$labParNat   = extrairLabelsLimitados($topParNat, 'nome', 60);
$totParNat   = extrairTotais($topParNat);
$labParEixos = extrairLabelsLimitados($topParEixos, 'nome', 60);
$totParEixos = extrairTotais($topParEixos);
$labParPerf  = extrairLabelsLimitados($topParPerf, 'nome', 60);
$totParPerf  = extrairTotais($topParPerf);
$labParSet   = extrairLabelsLimitados($topParSet, 'nome', 60);
$totParSet   = extrairTotais($topParSet);

$tabParTipos = montarTabelaPercentual($topParTipos);
$tabParNat   = montarTabelaPercentual($topParNat);
$tabParEixos = montarTabelaPercentual($topParEixos);
$tabParPerf  = montarTabelaPercentual($topParPerf);
$tabParSet   = montarTabelaPercentual($topParSet);

$etapaLabels = [];
$etapaTotais = [];
foreach ($parEtapa as $row) {
    $n = (int)$row['etapa_atual'];
    $etapaLabels[] = "Etapa $n" . ($n >= 6 ? ' ✅' : '');
    $etapaTotais[] = (int)$row['total'];
}

include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="container py-0 px-3">

    <!-- PAGE HEADER -->
    <div class="rpt-page-header mb-0">
        <div class="d-flex justify-content-between align-items-start" style="position:relative;z-index:2">
            <div>
                <span class="rpt-badge mb-2">
                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/></svg>
                    Painel de Relatórios
                </span>
                <h1>Relatórios de Usuários</h1>
                <p>Painel consolidado dos cadastros de Empreendedores, Sociedade Civil e Parceiros.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap" style="z-index:2">
                <a href="#sec-empreendedores" class="btn btn-sm btn-outline-success">🚀 Empreendedores</a>
                <a href="#sec-sociedade"      class="btn btn-sm btn-outline-primary">🤝 Sociedade Civil</a>
                <a href="#sec-parceiros"      class="btn btn-sm btn-outline-warning text-dark">🏢 Parceiros</a>
            </div>
        </div>
    </div>

    <!-- KPI STRIP -->
    <div class="kpi-strip mb-4">
        <div class="kpi-card">
            <div class="kpi-icon teal">👥</div>
            <div>
                <div class="kpi-label">Total de usuários</div>
                <div class="kpi-value teal"><?= $kpiTotal ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green">🚀</div>
            <div>
                <div class="kpi-label">Empreendedores ativos</div>
                <div class="kpi-value green"><?= $kpiEmp ?></div>
                <div class="kpi-label" style="font-size:0.7rem;opacity:.65;margin-top:2px"><?= $kpiEmpTotal ?> cadastrados no total</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon blue">🤝</div>
            <div>
                <div class="kpi-label">Sociedade Civil</div>
                <div class="kpi-value blue"><?= $kpiSoc ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon amber">🏢</div>
            <div>
                <div class="kpi-label">Parceiros</div>
                <div class="kpi-value amber"><?= $kpiPar ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon purple">✅</div>
            <div>
                <div class="kpi-label">Parceiros completos</div>
                <div class="kpi-value purple"><?= $kpiParCompleto ?></div>
            </div>
        </div>
    </div>

    <!-- Distribuição geral -->
    <div class="rpt-section">
        <div class="rpt-section-header">
            <div class="section-dot accent"></div>
            <h2>Distribuição geral de usuários</h2>
        </div>
        <div class="grid-2">
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar"></div>
                    <h5>Proporção por tipo de usuário</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:280px;">
                        <canvas id="graficoDistribuicaoGeral"></canvas>
                    </div>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar green"></div>
                    <h5>Fundadores vs. não-fundadores (empreendedores ativos)</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:280px;">
                        <canvas id="graficoFundadores"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SEÇÃO: EMPREENDEDORES
         ============================================================ -->
    <div class="rpt-section" id="sec-empreendedores">
        <div class="rpt-section-header">
            <div class="section-dot green"></div>
            <h2>🚀 Empreendedores</h2>
            <span class="badge bg-success ms-2" style="font-size:.75rem;font-weight:500;letter-spacing:.5px">
                ✅ Exibindo apenas cadastros ativos
            </span>
            <span class="badge bg-secondary ms-2" style="font-size:.72rem;font-weight:400">
                Total cadastrado: <?= $kpiEmpTotal ?>
            </span>
        </div>

        <!-- Crescimento mensal -->
        <div class="chart-card mb-4">
            <div class="chart-card-header">
                <div class="accent-bar green"></div>
                <h5>Novos cadastros ativos por mês (últimos 12 meses)</h5>
            </div>
            <div class="chart-card-body">
                <div class="chart-wrap" style="height:240px;">
                    <canvas id="graficoEmpMes"></canvas>
                </div>
            </div>
        </div>

        <!-- Empreendedores por estado — linha inteira, barra horizontal compacta -->
        <div class="chart-card mb-4">
            <div class="chart-card-header">
                <div class="accent-bar green"></div>
                <h5>Empreendedores ativos por estado</h5>
            </div>
            <div class="chart-card-body">
                <div class="chart-wrap" id="wrap-graficoEmpEstado">
                    <canvas id="graficoEmpEstado"></canvas>
                </div>
            </div>
        </div>

        <div class="grid-2">

            <!-- Por gênero -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar teal"></div>
                    <h5>Identidade de gênero</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:300px;">
                        <canvas id="graficoEmpGenero"></canvas>
                    </div>
                </div>
            </div>

            <!-- Etnia (fundadores ativos) -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar amber"></div>
                    <h5>Etnia/raça dos fundadores</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoEmpEtnia">
                        <canvas id="graficoEmpEtnia"></canvas>
                    </div>
                </div>
            </div>

            <!-- Formação (fundadores ativos) -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar blue"></div>
                    <h5>Formação acadêmica dos fundadores</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoEmpFormacao">
                        <canvas id="graficoEmpFormacao"></canvas>
                    </div>
                </div>
            </div>

            <!-- Origem de conhecimento -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar purple"></div>
                    <h5>Como ficou sabendo da plataforma</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:300px;">
                        <canvas id="graficoEmpOrigem"></canvas>
                    </div>
                </div>
            </div>

            <!-- Grupo vulnerável (fundadores ativos) -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar"></div>
                    <h5>Grupo vulnerável (fundadores)</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoEmpGrupo">
                        <canvas id="graficoEmpGrupo"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ============================================================
         SEÇÃO: SOCIEDADE CIVIL
         ============================================================ -->
    <div class="rpt-section" id="sec-sociedade">
        <div class="rpt-section-header">
            <div class="section-dot blue"></div>
            <h2>🤝 Sociedade Civil</h2>
        </div>

        <div class="chart-card mb-4">
            <div class="chart-card-header">
                <div class="accent-bar blue"></div>
                <h5>Novos cadastros por mês (últimos 12 meses)</h5>
            </div>
            <div class="chart-card-body">
                <div class="chart-wrap" style="height:240px;">
                    <canvas id="graficoSocMes"></canvas>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar blue"></div>
                    <h5>Membros por estado</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoSocEstado">
                        <canvas id="graficoSocEstado"></canvas>
                    </div>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar teal"></div>
                    <h5>Top profissões</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoSocProf">
                        <canvas id="graficoSocProf"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-2 mt-0">
            <?php
            $cardsSoc = [
                ['canvas' => 'graficoSocIdent', 'title' => 'Identificações',        'tabela' => $tabSocIdent, 'cor' => ''],
                ['canvas' => 'graficoSocMotiv', 'title' => 'Motivações',            'tabela' => $tabSocMotiv, 'cor' => 'blue'],
                ['canvas' => 'graficoSocInt',   'title' => 'Interesses temáticos',  'tabela' => $tabSocInt,   'cor' => 'green'],
                ['canvas' => 'graficoSocEng',   'title' => 'Formas de engajamento', 'tabela' => $tabSocEng,   'cor' => 'amber'],
                ['canvas' => 'graficoSocSet',   'title' => 'Setores de interesse',  'tabela' => $tabSocSet,   'cor' => 'purple'],
                ['canvas' => 'graficoSocODS',   'title' => 'ODS de interesse',      'tabela' => [],           'cor' => 'teal'],
            ];
            foreach ($cardsSoc as $c):
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
                            <thead><tr><th>Item</th><th style="width:50px;text-align:right">Total</th><th style="width:120px;text-align:right">%</th></tr></thead>
                            <tbody>
                            <?php foreach ($c['tabela'] as $linha): ?>
                                <tr>
                                    <td><?= htmlspecialchars($linha['nome']) ?></td>
                                    <td class="td-num"><?= (int)$linha['total'] ?></td>
                                    <td>
                                        <div class="pct-bar-wrap">
                                            <div class="pct-bar"><div class="pct-bar-fill" style="width:<?= min(100, round($linha['percentual'] / $maxPct * 100)) ?>%"></div></div>
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
         SEÇÃO: PARCEIROS
         ============================================================ -->
    <div class="rpt-section" id="sec-parceiros" style="margin-bottom: 3rem;">
        <div class="rpt-section-header">
            <div class="section-dot amber"></div>
            <h2>🏢 Parceiros</h2>
        </div>

        <div class="chart-card mb-4">
            <div class="chart-card-header">
                <div class="accent-bar amber"></div>
                <h5>Novos parceiros por mês (últimos 12 meses)</h5>
            </div>
            <div class="chart-card-body">
                <div class="chart-wrap" style="height:240px;">
                    <canvas id="graficoParMes"></canvas>
                </div>
            </div>
        </div>

        <div class="grid-3">
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar amber"></div>
                    <h5>Parceiros por estado</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" id="wrap-graficoParEstado">
                        <canvas id="graficoParEstado"></canvas>
                    </div>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar green"></div>
                    <h5>Progresso do cadastro (etapas)</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:280px;">
                        <canvas id="graficoParEtapa"></canvas>
                    </div>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-card-header">
                    <div class="accent-bar blue"></div>
                    <h5>Alcance do impacto pretendido</h5>
                </div>
                <div class="chart-card-body">
                    <div class="chart-wrap" style="height:280px;">
                        <canvas id="graficoParAlcance"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-card mt-0">
            <div class="chart-card-header">
                <div class="accent-bar green"></div>
                <h5>ODS de interesse dos parceiros</h5>
            </div>
            <div class="chart-card-body">
                <div class="chart-wrap" style="height:320px;">
                    <canvas id="graficoParODS"></canvas>
                </div>
            </div>
        </div>

        <div class="grid-2 mt-0">
            <?php
            $cardsPar = [
                ['canvas' => 'graficoParTipos', 'title' => 'Tipos de parceria',    'tabela' => $tabParTipos, 'cor' => 'amber'],
                ['canvas' => 'graficoParNat',   'title' => 'Natureza da parceria', 'tabela' => $tabParNat,   'cor' => 'teal'],
                ['canvas' => 'graficoParEixos', 'title' => 'Eixos de interesse',   'tabela' => $tabParEixos, 'cor' => 'blue'],
                ['canvas' => 'graficoParPerf',  'title' => 'Perfil de impacto',    'tabela' => $tabParPerf,  'cor' => 'green'],
                ['canvas' => 'graficoParSet',   'title' => 'Setores de interesse', 'tabela' => $tabParSet,   'cor' => 'purple'],
            ];
            foreach ($cardsPar as $c):
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
                            <thead><tr><th>Item</th><th style="width:50px;text-align:right">Total</th><th style="width:120px;text-align:right">%</th></tr></thead>
                            <tbody>
                            <?php foreach ($c['tabela'] as $linha): ?>
                                <tr>
                                    <td><?= htmlspecialchars($linha['nome']) ?></td>
                                    <td class="td-num"><?= (int)$linha['total'] ?></td>
                                    <td>
                                        <div class="pct-bar-wrap">
                                            <div class="pct-bar"><div class="pct-bar-fill" style="width:<?= min(100, round($linha['percentual'] / $maxPct * 100)) ?>%"></div></div>
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

</div><!-- /container -->

<script>
// Altura para gráficos horizontais genéricos (outros)
function alturaHorizontal(n, minH = 220, porItem = 44) {
    return Math.max(minH, n * porItem);
}
// Altura compacta exclusiva do gráfico de estados de empreendedores
function alturaEstadoEmp(n) {
    return Math.max(300, n * 28);
}
function setAltura(id, h) {
    const el = document.getElementById(id);
    if (el) el.style.height = h + 'px';
}

document.addEventListener('DOMContentLoaded', function () {

    const distLabels = ['Empreendedores ativos', 'Sociedade Civil', 'Parceiros'];
    const distTotais = [<?= $kpiEmp ?>, <?= $kpiSoc ?>, <?= $kpiPar ?>];

    const labEmpEstado   = <?= json_encode($labEmpEstado,   JSON_UNESCAPED_UNICODE) ?>;
    const totEmpEstado   = <?= json_encode($totEmpEstado) ?>;
    const labEmpGenero   = <?= json_encode($labEmpGenero,   JSON_UNESCAPED_UNICODE) ?>;
    const totEmpGenero   = <?= json_encode($totEmpGenero) ?>;
    const labEmpEtnia    = <?= json_encode($labEmpEtnia,    JSON_UNESCAPED_UNICODE) ?>;
    const totEmpEtnia    = <?= json_encode($totEmpEtnia) ?>;
    const labEmpFormacao = <?= json_encode($labEmpFormacao, JSON_UNESCAPED_UNICODE) ?>;
    const totEmpFormacao = <?= json_encode($totEmpFormacao) ?>;
    const labEmpOrigem   = <?= json_encode($labEmpOrigem,   JSON_UNESCAPED_UNICODE) ?>;
    const totEmpOrigem   = <?= json_encode($totEmpOrigem) ?>;
    const labEmpMes      = <?= json_encode($labEmpMes) ?>;
    const totEmpMes      = <?= json_encode(array_map('intval', $totEmpMes)) ?>;
    const labEmpGrupo    = <?= json_encode($labEmpGrupo,    JSON_UNESCAPED_UNICODE) ?>;
    const totEmpGrupo    = <?= json_encode($totEmpGrupo) ?>;
    const fundadores     = <?= (int)($empFundador['fundadores']     ?? 0) ?>;
    const naoFundadores  = <?= (int)($empFundador['nao_fundadores'] ?? 0) ?>;

    const labSocEstado = <?= json_encode($labSocEstado, JSON_UNESCAPED_UNICODE) ?>;
    const totSocEstado = <?= json_encode($totSocEstado) ?>;
    const labSocProf   = <?= json_encode($labSocProf,   JSON_UNESCAPED_UNICODE) ?>;
    const totSocProf   = <?= json_encode($totSocProf) ?>;
    const labSocMes    = <?= json_encode($labSocMes) ?>;
    const totSocMes    = <?= json_encode(array_map('intval', $totSocMes)) ?>;
    const labSocIdent  = <?= json_encode($labSocIdent,  JSON_UNESCAPED_UNICODE) ?>;
    const totSocIdent  = <?= json_encode($totSocIdent) ?>;
    const labSocMotiv  = <?= json_encode($labSocMotiv,  JSON_UNESCAPED_UNICODE) ?>;
    const totSocMotiv  = <?= json_encode($totSocMotiv) ?>;
    const labSocInt    = <?= json_encode($labSocInt,    JSON_UNESCAPED_UNICODE) ?>;
    const totSocInt    = <?= json_encode($totSocInt) ?>;
    const labSocEng    = <?= json_encode($labSocEng,    JSON_UNESCAPED_UNICODE) ?>;
    const totSocEng    = <?= json_encode($totSocEng) ?>;
    const labSocSet    = <?= json_encode($labSocSet,    JSON_UNESCAPED_UNICODE) ?>;
    const totSocSet    = <?= json_encode($totSocSet) ?>;
    const labSocODS    = <?= json_encode($labSocODS,    JSON_UNESCAPED_UNICODE) ?>;
    const totSocODS    = <?= json_encode($totSocODS) ?>;

    const labParEstado  = <?= json_encode($labParEstado,  JSON_UNESCAPED_UNICODE) ?>;
    const totParEstado  = <?= json_encode($totParEstado) ?>;
    const labParMes     = <?= json_encode($labParMes) ?>;
    const totParMes     = <?= json_encode(array_map('intval', $totParMes)) ?>;
    const labParEtapa   = <?= json_encode($etapaLabels,   JSON_UNESCAPED_UNICODE) ?>;
    const totParEtapa   = <?= json_encode($etapaTotais) ?>;
    const labParAlcance = <?= json_encode($labParAlcance, JSON_UNESCAPED_UNICODE) ?>;
    const totParAlcance = <?= json_encode($totParAlcance) ?>;
    const labParTipos   = <?= json_encode($labParTipos,   JSON_UNESCAPED_UNICODE) ?>;
    const totParTipos   = <?= json_encode($totParTipos) ?>;
    const labParNat     = <?= json_encode($labParNat,     JSON_UNESCAPED_UNICODE) ?>;
    const totParNat     = <?= json_encode($totParNat) ?>;
    const labParEixos   = <?= json_encode($labParEixos,   JSON_UNESCAPED_UNICODE) ?>;
    const totParEixos   = <?= json_encode($totParEixos) ?>;
    const labParPerf    = <?= json_encode($labParPerf,    JSON_UNESCAPED_UNICODE) ?>;
    const totParPerf    = <?= json_encode($totParPerf) ?>;
    const labParSet     = <?= json_encode($labParSet,     JSON_UNESCAPED_UNICODE) ?>;
    const totParSet     = <?= json_encode($totParSet) ?>;
    const dataParODS    = <?= json_encode($parODS,        JSON_UNESCAPED_UNICODE) ?>;

    // Altura do gráfico de estados (horizontal compacto)
    setAltura('wrap-graficoEmpEstado', alturaEstadoEmp(labEmpEstado.length));

    // Demais gráficos horizontais genéricos
    [
        { id: 'wrap-graficoEmpEtnia',    n: labEmpEtnia.length    },
        { id: 'wrap-graficoEmpFormacao', n: labEmpFormacao.length  },
        { id: 'wrap-graficoEmpGrupo',    n: labEmpGrupo.length    },
        { id: 'wrap-graficoSocEstado',   n: labSocEstado.length   },
        { id: 'wrap-graficoSocProf',     n: labSocProf.length     },
        { id: 'wrap-graficoSocIdent',    n: labSocIdent.length    },
        { id: 'wrap-graficoSocMotiv',    n: labSocMotiv.length    },
        { id: 'wrap-graficoSocInt',      n: labSocInt.length      },
        { id: 'wrap-graficoSocEng',      n: labSocEng.length      },
        { id: 'wrap-graficoSocSet',      n: labSocSet.length      },
        { id: 'wrap-graficoSocODS',      n: labSocODS.length      },
        { id: 'wrap-graficoParEstado',   n: labParEstado.length   },
        { id: 'wrap-graficoParTipos',    n: labParTipos.length    },
        { id: 'wrap-graficoParNat',      n: labParNat.length      },
        { id: 'wrap-graficoParEixos',    n: labParEixos.length    },
        { id: 'wrap-graficoParPerf',     n: labParPerf.length     },
        { id: 'wrap-graficoParSet',      n: labParSet.length      },
    ].forEach(({ id, n }) => setAltura(id, alturaHorizontal(n)));

    criarGraficoCircular('graficoDistribuicaoGeral', distLabels, distTotais, 'doughnut');
    criarGraficoCircular('graficoFundadores',
        ['Fundadores', 'Não-fundadores'],
        [fundadores, naoFundadores],
        'doughnut'
    );

    criarGraficoLinha('graficoEmpMes', labEmpMes, totEmpMes, 'Ativos cadastrados', '#1a8a4a');

    // Estado: horizontal compacto (linha inteira)
    criarGraficoBarraHorizontalCompacto('graficoEmpEstado', labEmpEstado, totEmpEstado, 'Ativos');

    criarGraficoCircular('graficoEmpGenero',  labEmpGenero,  totEmpGenero,  'pie');
    criarGraficoBarra   ('graficoEmpEtnia',    labEmpEtnia,   totEmpEtnia,   'Fundadores ativos', true);
    criarGraficoBarra   ('graficoEmpFormacao', labEmpFormacao,totEmpFormacao,'Fundadores ativos', true);
    criarGraficoCircular('graficoEmpOrigem',  labEmpOrigem,  totEmpOrigem,  'doughnut');
    criarGraficoBarra   ('graficoEmpGrupo',    labEmpGrupo,   totEmpGrupo,   'Fundadores ativos', true);

    criarGraficoLinha  ('graficoSocMes',    labSocMes,   totSocMes,   'Novos cadastros', '#0369a1');
    criarGraficoBarra  ('graficoSocEstado', labSocEstado,totSocEstado,'Membros', true);
    criarGraficoBarra  ('graficoSocProf',   labSocProf,  totSocProf,  'Membros', true);
    criarGraficoBarra  ('graficoSocIdent',  labSocIdent, totSocIdent, 'Ocorrências', true);
    criarGraficoBarra  ('graficoSocMotiv',  labSocMotiv, totSocMotiv, 'Ocorrências', true);
    criarGraficoBarra  ('graficoSocInt',    labSocInt,   totSocInt,   'Ocorrências', true);
    criarGraficoBarra  ('graficoSocEng',    labSocEng,   totSocEng,   'Ocorrências', true);
    criarGraficoBarra  ('graficoSocSet',    labSocSet,   totSocSet,   'Ocorrências', true);
    criarGraficoBarra  ('graficoSocODS',    labSocODS,   totSocODS,   'Ocorrências', true);

    criarGraficoLinha  ('graficoParMes',     labParMes,    totParMes,    'Novos parceiros', '#c07a00');
    criarGraficoBarra  ('graficoParEstado',  labParEstado, totParEstado, 'Parceiros', true);
    criarGraficoBarra  ('graficoParEtapa',   labParEtapa,  totParEtapa,  'Parceiros', false);
    criarGraficoCircular('graficoParAlcance',labParAlcance,totParAlcance,'doughnut');
    criarGraficoODS    ('graficoParODS',     dataParODS);
    criarGraficoBarra  ('graficoParTipos',   labParTipos,  totParTipos,  'Parceiros', true);
    criarGraficoBarra  ('graficoParNat',     labParNat,    totParNat,    'Parceiros', true);
    criarGraficoBarra  ('graficoParEixos',   labParEixos,  totParEixos,  'Ocorrências', true);
    criarGraficoBarra  ('graficoParPerf',    labParPerf,   totParPerf,   'Ocorrências', true);
    criarGraficoBarra  ('graficoParSet',     labParSet,    totParSet,    'Ocorrências', true);
});

function criarGraficoLinha(canvasId, labels, data, label, cor) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label,
                data,
                borderColor: cor,
                backgroundColor: cor + '22',
                borderWidth: 2.5,
                pointRadius: 4,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { ticks: { maxRotation: 45, minRotation: 30 } }
            }
        }
    });
}

// Barra horizontal compacta — para o gráfico de estados (linha inteira)
function criarGraficoBarraHorizontalCompacto(canvasId, labels, data, label) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    const cores = [
        '#1a8a4a','#2563eb','#d97706','#7c3aed','#0e7490',
        '#be185d','#16a34a','#ea580c','#0284c7','#9333ea',
        '#15803d','#b45309','#1d4ed8','#db2777','#0891b2',
        '#65a30d','#c2410c','#6d28d9','#047857','#dc2626',
        '#0369a1','#a16207','#7e22ce','#166534','#b91c1c'
    ];
    const bgColors = labels.map((_, i) => cores[i % cores.length]);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label,
                data,
                backgroundColor: bgColors,
                borderRadius: 4,
                barThickness: 18
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.parsed.x + ' empreendedores'
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0, font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,.06)' }
                },
                y: {
                    ticks: { font: { size: 12 } },
                    grid: { display: false }
                }
            },
            layout: { padding: { right: 8 } }
        }
    });
}
</script>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>
