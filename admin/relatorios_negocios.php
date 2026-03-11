<?php
session_start();
require_once __DIR__ . '/../app/helpers/auth.php';

// só permite admin, superadmin ou juri
require_admin_login();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config/db.php';
// Conexão PDO
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("SET NAMES utf8mb4");

    // --- QUERY 1: Por Categoria ---
    $sqlCat = "SELECT categoria, COUNT(*) as total FROM negocios n
               WHERE n.inscricao_completa = 1 
               AND n.categoria IS NOT NULL AND n.categoria != '' 
               GROUP BY n.categoria 
               ORDER BY total DESC";
    $stmtCat = $pdo->query($sqlCat);
    $dataCategoria = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    // --- QUERY 2: Por Estado (UF) ---
     $sqlUF = "SELECT estado, COUNT(*) as total 
              FROM negocios 
              WHERE inscricao_completa = 1 
              AND estado IS NOT NULL AND estado != '' 
              GROUP BY estado ORDER BY total DESC";
    $stmtUF = $pdo->query($sqlUF);
    $dataUF = $stmtUF->fetchAll(PDO::FETCH_ASSOC);

    // --- QUERY 3: Por Eixo Temático ---
    $sqlEixo = "SELECT et.nome AS eixo_tematico, COUNT(n.id) as total 
                FROM negocios n
                JOIN eixos_tematicos et ON n.eixo_principal_id = et.id
                WHERE n.inscricao_completa = 1 
                GROUP BY et.nome 
                ORDER BY total DESC";
    $stmtEixo = $pdo->query($sqlEixo);
    $dataEixo = $stmtEixo->fetchAll(PDO::FETCH_ASSOC);

    // --- QUERY 4: Por ODS Prioritário ---
    $sqlODS = "SELECT o.id, o.n_ods, o.nome, COUNT(n.id) as total 
               FROM negocios n
               JOIN ods o ON n.ods_prioritaria_id = o.id
               WHERE n.inscricao_completa = 1 
               GROUP BY o.id, o.n_ods, o.nome
               ORDER BY o.id ASC";
    $stmtODS = $pdo->query($sqlODS);
    $dataODS = $stmtODS->fetchAll(PDO::FETCH_ASSOC);

    // --- QUERY 5: Por Modelo de Negócio (B2B, B2C...) ---
    $sqlModelo = "SELECT na.modelo_negocio, COUNT(n.id) as total 
                  FROM negocios n
                  JOIN negocio_apresentacao na ON n.id = na.negocio_id
                  WHERE n.inscricao_completa = 1 
                  AND na.modelo_negocio IS NOT NULL AND na.modelo_negocio != '' 
                  GROUP BY na.modelo_negocio 
                  ORDER BY total DESC";
    $stmtModelo = $pdo->query($sqlModelo);
    $dataModelo = $stmtModelo->fetchAll(PDO::FETCH_ASSOC);

    // --- QUERY 6: Médias de Scores (somente negócios concluídos) ---
    $sqlScores = "SELECT 
                    AVG(score_impacto) as impacto_medio,
                    AVG(score_investimento) as investimento_medio,
                    AVG(score_escala) as escala_medio,
                    AVG(score_geral) as geral_medio
                FROM scores_negocios s
                JOIN negocios n ON s.negocio_id = n.id
                WHERE n.inscricao_completa = 1";
    $stmtScores = $pdo->query($sqlScores);
    $dataScores = $stmtScores->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao gerar relatórios: " . $e->getMessage());
}

include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Relatórios de Negócios</h1>
        <div>
            <a href="/admin/dashboard.php" class="btn btn-secondary btn-sm">Voltar</a>
        </div>
    </div>

    <!-- Linha 1: Categoria e Estado -->
    <div class="row mb-4">
        <!-- Gráfico de Categorias (Barras Horizontais) -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Negócios por Categoria</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartCategoria"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de Estados (Mapa/Barras) -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Distribuição por Estado</h6>
                </div>
                <div class="card-body">
                   <canvas id="chartEstado"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Linha 2: Eixos e Modelos -->
    <div class="row mb-4">
        <!-- Eixo Temático (Pizza) -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Eixo Temático</h6>
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chartEixo"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modelo de Negócio (Doughnut) -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Modelo de Negócio</h6>
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chartModelo"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Linha 2: Eixos e Modelos -->
    <div class="row mb-4">
        <!-- ODS (Barras Verticais) -->
        <div class="col-lg-12 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">ODS Prioritária</h6>
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chartODS"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Linha 3: Scores Médios -->
    <div class="row mb-4">
        <div class="col-lg-12 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Scores Médios</h6>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <canvas id="chartScores"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Linha 3: Scores Médios -->
    <div class="row mb-4">
        <div class="col-lg-3 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Impacto</h6>
                </div>
                <div class="card-body">
                    <canvas id="gaugeImpacto"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Investimento</h6>
                </div>
                <div class="card-body">
                    <canvas id="gaugeInvestimento"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Escala</h6>
                </div>
                <div class="card-body">
                    <canvas id="gaugeEscala"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Score Geral</h6>
                </div>
                <div class="card-body">
                    <canvas id="gaugeGeral"></canvas>
                </div>
            </div>
        </div>
    </div>
    
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Dados vindos do PHP
    const dadosCategoria = <?= json_encode($dataCategoria ?? []) ?>;
    const dadosUF = <?= json_encode($dataUF ?? []) ?>;
    const dadosEixo = <?= json_encode($dataEixo ?? []) ?>;
    const dadosODS = <?= json_encode($dataODS ?? []) ?>;
    const dadosModelo = <?= json_encode($dataModelo ?? []) ?>;
    const dadosScores = <?= json_encode($dataScores ?? []) ?>;

    // 1. Categoria (Horizontal)
    if (dadosCategoria.length > 0) {
        criarGraficoBarra(
            'chartCategoria',
            dadosCategoria.map(d => d.categoria),
            dadosCategoria.map(d => d.total),
            'Negócios',
            true
        );
    }

    // 2. Estados (Vertical)
    if (dadosUF.length > 0) {
        criarGraficoBarra(
            'chartEstado',
            dadosUF.map(d => d.estado),
            dadosUF.map(d => d.total),
            'Negócios',
            false
        );
    }

    // 3. Eixo (Pizza)
    if (dadosEixo.length > 0) {
        criarGraficoCircular(
            'chartEixo',
            dadosEixo.map(d => d.eixo_tematico),
            dadosEixo.map(d => d.total),
            'pie'
        );
    }

    // 4. ODS (Customizado)
    if (dadosODS.length > 0) {
        criarGraficoODS('chartODS', dadosODS);
    }

    // 5. Modelo (Donut)
    if (dadosModelo.length > 0) {
        criarGraficoCircular(
            'chartModelo',
            dadosModelo.map(d => d.modelo_negocio),
            dadosModelo.map(d => d.total),
            'doughnut'
        );
    }

    // 6. Scores Médios (Radar)
    if (dadosScores) {
        criarGraficoBarra(
            'chartScores',
            ['Impacto', 'Investimento', 'Escala', 'Geral'],
            [
                dadosScores.impacto_medio ?? 0,
                dadosScores.investimento_medio ?? 0,
                dadosScores.escala_medio ?? 0,
                dadosScores.geral_medio ?? 0
            ],
            'Média dos Scores',
            true // Horizontal
        );
    }

    // 6. Scores Médios (Gauge)
    criarGauge('gaugeImpacto', dadosScores.impacto_medio ?? 0, 'Impacto', 'rgba(75, 192, 192, 0.8)');
    criarGauge('gaugeInvestimento', dadosScores.investimento_medio ?? 0, 'Investimento', 'rgba(54, 162, 235, 0.8)');
    criarGauge('gaugeEscala', dadosScores.escala_medio ?? 0, 'Escala', 'rgba(255, 159, 64, 0.8)');
    criarGauge('gaugeGeral', dadosScores.geral_medio ?? 0, 'Geral', 'rgba(153, 102, 255, 0.8)');
});



</script>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>

