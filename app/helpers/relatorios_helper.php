<?php

function extrairLabels(array $dados, string $campo): array
{
    return array_map(fn($item) => $item[$campo], $dados);
}

function extrairTotais(array $dados): array
{
    return array_map(fn($item) => (int)$item['total'], $dados);
}

function limitarTexto(string $texto, int $max = 55): string
{
    $texto = trim($texto);

    if (mb_strlen($texto) <= $max) {
        return $texto;
    }

    return mb_substr($texto, 0, $max - 3) . '...';
}

function prepararTopDados(array $dados, int $limite = 8, bool $agruparOutros = true): array
{
    $dados = array_values($dados);

    if (count($dados) <= $limite) {
        return $dados;
    }

    $top = array_slice($dados, 0, $limite);

    if ($agruparOutros) {
        $restante = array_slice($dados, $limite);
        $totalOutros = array_sum(array_map(fn($item) => (int)$item['total'], $restante));

        if ($totalOutros > 0) {
            $top[] = [
                'nome' => 'Outros',
                'total' => $totalOutros
            ];
        }
    }

    return $top;
}

function extrairLabelsLimitados(array $dados, string $campo, int $maxChars = 55): array
{
    return array_map(function ($item) use ($campo, $maxChars) {
        return limitarTexto((string)$item[$campo], $maxChars);
    }, $dados);
}

function montarTabelaPercentual(array $dados): array
{
    $total = array_sum(array_map(fn($item) => (int)$item['total'], $dados));

    return array_map(function ($item) use ($total) {
        $valor = (int)$item['total'];
        $percentual = $total > 0 ? round(($valor / $total) * 100, 1) : 0;

        return [
            'nome' => $item['nome'] ?? '',
            'total' => $valor,
            'percentual' => $percentual
        ];
    }, $dados);
}

function renderTabelaResumo(array $linhas): void
{
    if (empty($linhas)) {
        echo '<p class="text-muted small mt-3 mb-0">Sem dados disponíveis.</p>';
        return;
    }

    echo '<div class="table-responsive mt-3">';
    echo '<table class="table table-sm table-striped align-middle mb-0">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Item</th>';
    echo '<th class="text-center">Total</th>';
    echo '<th class="text-center">%</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($linhas as $linha) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($linha['nome']) . '</td>';
        echo '<td class="text-center">' . (int)$linha['total'] . '</td>';
        echo '<td class="text-center">' . number_format((float)$linha['percentual'], 1, ',', '.') . '%</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}