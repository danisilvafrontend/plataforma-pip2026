/**
 * Configurações globais e helpers para gráficos (Chart.js)
 */

// --- CORES OFICIAIS ODS (ONU) ---
const CORES_ODS = {
    1: '#E5243B',
    2: '#DDA63A',
    3: '#4C9F38',
    4: '#C5192D',
    5: '#FF3A21',
    6: '#26BDE2',
    7: '#FCC30B',
    8: '#A21942',
    9: '#FD6925',
    10: '#DD1367',
    11: '#FD9D24',
    12: '#BF8B2E',
    13: '#3F7E44',
    14: '#0A97D9',
    15: '#56C02B',
    16: '#00689D',
    17: '#19486A'
};

// --- PALETA PADRÃO DO SISTEMA ---
const PALETA_PADRAO = [
    '#026874', '#94A604', '#D2DE32', '#17a2b8', '#ffc107',
    '#dc3545', '#6610f2', '#fd7e14', '#20c997', '#6c757d',
    '#343a40', '#6f42c1', '#e83e8c', '#28a745'
];

/**
 * Retorna array de cores (repetindo se necessário)
 * @param {number} count Quantidade de cores necessárias
 */
function getColors(count) {
    const colors = [...PALETA_PADRAO];
    while (colors.length < count) {
        colors.push(...PALETA_PADRAO);
    }
    return colors.slice(0, count);
}

// Registra o plugin globalmente
Chart.register(ChartDataLabels);

// Configuração padrão para labels visíveis
const commonDataLabelsConfig = {
    color: '#000',
    anchor: 'end',
    align: 'top',
    offset: -20,
    font: {
        weight: 'bold',
        size: 11
    },
    formatter: (value) => value > 0 ? value : ''
};

/**
 * Cria gráfico de barras (vertical ou horizontal)
 */
function criarGraficoBarra(canvasId, labels, data, labelSerie = 'Total', horizontal = false, cores = null) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    const backgroundColors = cores || getColors(data.length);

    const labelConfig = { ...commonDataLabelsConfig };

    if (horizontal) {
        labelConfig.anchor = 'end';
        labelConfig.align = 'right';
        labelConfig.offset = 5;
    } else {
        labelConfig.anchor = 'end';
        labelConfig.align = 'end';
        labelConfig.offset = -20;
    }

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: labelSerie,
                data: data,
                backgroundColor: backgroundColors,
                borderRadius: 4,
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: horizontal
                    ? { top: 10, right: 40, left: 10, bottom: 10 }
                    : { top: 20, right: 30, left: 10, bottom: 10 }
            },
            plugins: {
                legend: { display: false },
                datalabels: labelConfig
            },
            scales: horizontal
                ? {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    },
                    y: {
                        ticks: { autoSkip: false }
                    }
                }
                : {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, autoSkip: false }
                    },
                    x: {
                        ticks: { autoSkip: false }
                    }
                }
        }
    });
}

/**
 * Cria gráfico de pizza ou donut
 */
function criarGraficoCircular(canvasId, labels, data, tipo = 'pie') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    return new Chart(ctx, {
        type: tipo,
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: getColors(data.length)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    color: '#fff',
                    font: { weight: 'bold' },
                    formatter: (value) => value > 0 ? value : ''
                }
            }
        }
    });
}

/**
 * Cria gráfico de ODS específico (com cores oficiais)
 */
function criarGraficoODS(canvasId, dadosODS) {
    const ctx = document.getElementById(canvasId);
    if (!ctx || !dadosODS || dadosODS.length === 0) return;

    const cores = dadosODS.map(d => CORES_ODS[d.id] || '#999');

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dadosODS.map(d => d.n_ods),
            datasets: [{
                label: 'Negócios',
                data: dadosODS.map(d => d.total),
                backgroundColor: cores,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: { top: 20, right: 30, left: 10, bottom: 10 }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (ctx) => {
                            const idx = ctx[0].dataIndex;
                            return dadosODS[idx].n_ods + ' - ' + dadosODS[idx].nome;
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    color: '#444',
                    font: {
                        weight: 'bold',
                        size: 12
                    },
                    formatter: (value) => value > 0 ? value : ''
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, autoSkip: false }
                },
                x: {
                    ticks: { autoSkip: false }
                }
            }
        }
    });
}

function criarGraficoRadar(canvasId, labels, data, label) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    return new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: data,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                pointBackgroundColor: 'rgba(54, 162, 235, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    suggestedMin: 0,
                    suggestedMax: 100
                }
            }
        }
    });
}

// Plugin para escrever texto no centro do gauge
const gaugeCenterText = {
    id: 'gaugeCenterText',
    afterDraw(chart) {
        const { ctx, chartArea } = chart;
        if (!chartArea) return;

        const centerX = (chartArea.left + chartArea.right) / 2;
        const centerY = (chartArea.top + chartArea.bottom) / 2;

        ctx.save();
        ctx.font = 'bold 16px Arial';
        ctx.fillStyle = '#000';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        const value = chart.config.data.datasets[0].data[0];
        ctx.fillText(value + '%', centerX, centerY);
        ctx.restore();
    }
};

function criarGauge(canvasId, valor, label, cor = 'rgba(54, 162, 235, 0.8)') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    valor = valor ? Math.round(valor) : 0;

    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [label],
            datasets: [{
                data: [valor, 100 - valor],
                backgroundColor: [cor, '#e0e0e0'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            circumference: 180,
            rotation: -90,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        },
        plugins: [gaugeCenterText]
    });
}