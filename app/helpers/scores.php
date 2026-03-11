<?php
// app/helpers/scores.php
// Funções específicas para cálculo e normalização de scores

function calcularScore(PDO $pdo, int $negocio_id, string $tipo_score): int {
    $stmt = $pdo->prepare("SELECT componente, peso FROM pesos_scores WHERE tipo_score=?");
    $stmt->execute([$tipo_score]);
    $pesos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $score = 0;
    foreach ($pesos as $p) {
        $componente = $p['componente'];
        $peso = (float)$p['peso'];

        $resposta = obterRespostaDoNegocio($pdo, $negocio_id, $componente);

        $stmt2 = $pdo->prepare("SELECT valor FROM lookup_scores WHERE componente=? AND opcao=?");
        $stmt2->execute([$componente, $resposta]);
        $valor = (int)($stmt2->fetchColumn() ?: 0);

        $score += $valor * $peso;
    }

    $penalty = calcularPenalidades($pdo, $negocio_id, $tipo_score);

    return max(0, min(100, round($score - $penalty)));
}

// =========================
// Normalizadores
// =========================

function normalizarEstagio(?string $categoria): string {
    if (!$categoria) return 'nao_informado';
    $map = [
        'Ideação' => 'ideacao',
        'Operação' => 'operacao',
        'Tração/Escala' => 'tracao',
        'Dinamizador' => 'dinamizador'
    ];
    return $map[$categoria] ?? 'nao_informado';
}

function normalizarReceita(?string $faixa): string {
    if (!$faixa) return 'nao_informado';
    if (strpos($faixa, 'Não houve faturamento') !== false) return 'sem_receita';
    if (strpos($faixa, 'Até R$ 100 mil') !== false) return 'ate_100k';
    if (strpos($faixa, 'R$ 100 mil – R$ 500 mil') !== false) return '100k_500k';
    if (strpos($faixa, 'R$ 500 mil – R$ 1 milhão') !== false) return '500k_1m';
    if (strpos($faixa, 'R$ 1 milhão – R$ 5 milhões') !== false) return '1m_5m';
    if (strpos($faixa, 'R$ 5 milhões – R$ 20 milhões') !== false) return '5m_20m';
    if (strpos($faixa, 'Acima de R$ 20 milhões') !== false) return 'acima_20m';
    return 'nao_informado';
}

function normalizarMargem(?string $margem): string {
    if (!$margem) return 'nao_informado';
    if (strpos($margem, 'Acima de 60%') !== false) return 'acima_60';
    if (strpos($margem, '40%') !== false) return '40_60';
    if (strpos($margem, '20%') !== false) return '20_40';
    if (strpos($margem, 'Menor que 20%') !== false) return 'abaixo_20';
    return 'nao_informado';
}

function normalizarCrescimento(?string $texto): string {
    if (!$texto) return 'nao_informado';
    if (strpos($texto, 'Crescimento acima de 100%') !== false) return 'acima_100';
    if (strpos($texto, 'Crescimento entre 50% e 100%') !== false) return '50_100';
    if (strpos($texto, 'Crescimento de até 50%') !== false) return 'ate_50';
    if (strpos($texto, 'Estável') !== false || strpos($texto, 'retração') !== false) return 'estagnado';
    return 'nao_informado';
}

function normalizarModeloReceita($fontes): string {
    if (!$fontes || !is_array($fontes)) return 'nao_informado';
    if (in_array('Venda direta recorrente (assinaturas, mensalidades)', $fontes)) return 'recorrente_assinatura_contrato';
    if (in_array('Venda direta única (produto ou serviço)', $fontes)) return 'transacional_esporadico';
    if (in_array('Consultoria / mentoria / treinamento', $fontes)) return 'hibrido';
    if (in_array('Modelo ainda não definido', $fontes)) return 'nao_informado';
    return 'outro';
}

function normalizarCaptacao(?string $texto): string {
    if (!$texto) return 'nao_informado';
    if (stripos($texto, 'Série A') !== false || stripos($texto, 'seed') !== false) return 'vc_seed_serie';
    if (stripos($texto, 'anjo') !== false) return 'anjo';
    if (stripos($texto, 'bootstrapping') !== false) return 'bootstrapping';
    if (stripos($texto, 'Doações') !== false) return 'grants_donations_primary';
    if (stripos($texto, 'Premiações') !== false) return 'premiacao';
    if (stripos($texto, 'Não') !== false) return 'nunca_captou';
    return 'nao_informado';
}

function normalizarIntencionalidade(?string $texto): string {
    if (!$texto) return 'nao_informado';
    if (strpos($texto, 'integrado') !== false) return 'lucro_com_impacto_integrado';
    if (strpos($texto, 'prioridade principal') !== false) return 'missao_acima_lucro';
    if (strpos($texto, 'impacto secundário') !== false) return 'impacto_secundario';
    return 'nao_informado';
}

function normalizarTipoImpacto(?string $texto): string {
    if (!$texto) return 'nao_informado';
    if (strpos($texto, 'sistêmico') !== false) return 'sistemico';
    if (strpos($texto, 'cadeia') !== false) return 'cadeia';
    if (strpos($texto, 'direto') !== false) return 'direto';
    if (strpos($texto, 'indireto') !== false) return 'indireto';
    return 'nao_informado';
}

function normalizarAlcance(?string $texto): string {
    if (!$texto) return 'nao_informado';
    if ($texto === 'Acima de 500') return 'mais_500';
    if ($texto === '201 a 500') return '201_500';
    if ($texto === '101 a 200') return '101_200';
    if ($texto === '51 a 100') return '51_100';
    if ($texto === '1 a 50') return '1_50';
    return 'nao_informado';
}

function normalizarMensuracao(?string $texto): string {
    if (!$texto) return 'nao_informado';
    if (strpos($texto, 'auditoria') !== false) return 'auditoria_framework';
    if (strpos($texto, 'internamente') !== false) return 'dashboard_interno';
    if (strpos($texto, 'indicadores definidos') !== false) return 'indicadores_definidos';
    if (strpos($texto, 'não temos indicadores') !== false) return 'nao_mede';
    return 'nao_informado';
}

function normalizarEvidencias(array $dados): string {
    if (empty($dados)) return 'vazio';
    if (!empty($dados['resultados']) && (!empty($dados['resultados_links']) || !empty($dados['resultados_pdfs']))) {
        return 'documentado_com_links';
    }
    if (!empty($dados['resultados'])) return 'parcial';
    return 'vazio';
}

function normalizarVisao(?string $texto): string {
    if (!$texto) return 'nao_informado';
    if (strlen($texto) > 50) return 'clara_mensuravel';
    if (!empty($texto)) return 'clara_sem_metricas';
    return 'vazio';
}

function normalizarEscala(?string $texto): string {
    if (!$texto) return 'nao_informado';
    if (strpos($texto, 'internacional') !== false) return 'internacional';
    if (strpos($texto, 'nacional') !== false) return 'nacional';
    if (strpos($texto, 'local') !== false) return 'local';
    if (strpos($texto, 'territorial') !== false) return 'regional';
    return 'nao_informado';
}

function normalizarReplicabilidade($areas): string {
    if (!$areas || !is_array($areas)) return 'nao_informado';
    if (in_array('Desenvolvimento de tecnologia ou produto', $areas)) return 'digital_escalavel';
    if (in_array('Expansão comercial e abertura de mercado', $areas)) return 'baixa_adaptacao';
    return 'alta_adaptacao';
}

function normalizarEstrutura($areas): string {
    if (!$areas || !is_array($areas)) return 'nao_informado';
    if (in_array('Reforço da estrutura operacional (equipamentos, logística etc.)', $areas)) return 'time_processos_kpis';
    if (in_array('Formação de equipe e qualificação técnica', $areas)) return 'time_pequeno_organizado';
    return 'informal';
}

function normalizarTecnologia($temas): string {
    if (!$temas || !is_array($temas)) return 'nao_informado';
    if (in_array('Tecnologia e inovação aplicada ao impacto', $temas)) return 'propria';
    return 'manual';
}

function normalizarDemanda(?string $texto): string {
    if (!$texto) return 'nao_informado';
    if (strpos($texto, 'Alta sustentabilidade') !== false) return 'validado_crescente';
    if (strpos($texto, 'Moderada') !== false) return 'validado_local';
    return 'nicho_limitado';
}

function normalizarParcerias($apoios): string {
    if (!$apoios || !is_array($apoios)) return 'nao_informado';
    if (in_array('Parcerias corporativas ou estratégicas', $apoios)) return 'nacionais_internacionais';
    if (in_array('Investimento Anjo', $apoios)) return 'locais';
    return 'nenhuma';
}

function normalizarInternacionalizacao(?string $texto): string {
    if (!$texto) return 'nao_informado';
    if (strpos($texto, 'internacional') !== false) return 'ja_opera_fora';
    return 'sem_intencao';
}

function obterRespostaDoNegocio(PDO $pdo, int $negocio_id, string $componente): string {
    switch ($componente) {
        // Score Investimento
        case 'estagio':
            $stmt = $pdo->prepare("SELECT categoria FROM negocios WHERE id=?");
            $stmt->execute([$negocio_id]);
            return normalizarEstagio($stmt->fetchColumn());

        case 'receita':
            $stmt = $pdo->prepare("SELECT faixa_faturamento FROM negocio_financeiro WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            return normalizarReceita($stmt->fetchColumn());

        case 'margem_bruta':
            $stmt = $pdo->prepare("SELECT margem_bruta FROM negocio_financeiro WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            return normalizarMargem($stmt->fetchColumn());

        case 'crescimento':
            $stmt = $pdo->prepare("SELECT previsao_crescimento FROM negocio_financeiro WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            return normalizarCrescimento($stmt->fetchColumn());

        case 'modelo_receita':
            $stmt = $pdo->prepare("SELECT fontes_receita FROM negocio_financeiro WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            $raw = $stmt->fetchColumn();
            $fontes = json_decode($raw ?? '[]', true);
            if (!is_array($fontes)) $fontes = [];
            return normalizarModeloReceita($fontes);

        case 'captacao_previa':
            $stmt = $pdo->prepare("SELECT investimento_externo FROM negocio_financeiro WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            return normalizarCaptacao($stmt->fetchColumn());

        // Score Impacto
        case 'intencionalidade':
            $stmt = $pdo->prepare("SELECT intencionalidade FROM negocio_impacto WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            return normalizarIntencionalidade($stmt->fetchColumn());

        case 'tipo_impacto':
            $stmt = $pdo->prepare("SELECT tipo_impacto FROM negocio_impacto WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            return normalizarTipoImpacto($stmt->fetchColumn());

        case 'alcance':
            $stmt = $pdo->prepare("SELECT alcance FROM negocio_impacto WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            return normalizarAlcance($stmt->fetchColumn());

        case 'mensuracao':
            $stmt = $pdo->prepare("SELECT medicao FROM negocio_impacto WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            return normalizarMensuracao($stmt->fetchColumn());

        case 'evidencias':
            $stmt = $pdo->prepare("SELECT resultados, resultados_links, resultados_pdfs FROM negocio_impacto WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            $dados = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$dados) $dados = [];
            return normalizarEvidencias($dados);

        case 'visao_5anos':
            $stmt = $pdo->prepare("SELECT proximos_passos FROM negocio_impacto WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            return normalizarVisao($stmt->fetchColumn());

        // Score Escala
        case 'ambicao_geografica':
            $stmt = $pdo->prepare("SELECT escala FROM negocio_visao WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            return normalizarEscala($stmt->fetchColumn());

        case 'replicabilidade':
            $stmt = $pdo->prepare("SELECT areas FROM negocio_visao WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            $raw = $stmt->fetchColumn();
            $areas = json_decode($raw ?? '[]', true);
            if (!is_array($areas)) $areas = [];
            return normalizarReplicabilidade($areas);

        case 'estrutura_operacional':
            $stmt = $pdo->prepare("SELECT areas FROM negocio_visao WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            $raw = $stmt->fetchColumn();
            $areas = json_decode($raw ?? '[]', true);
            if (!is_array($areas)) $areas = [];
            return normalizarEstrutura($areas);

        case 'tecnologia':
            $stmt = $pdo->prepare("SELECT temas FROM negocio_visao WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            $raw = $stmt->fetchColumn();
            $temas = json_decode($raw ?? '[]', true);
            if (!is_array($temas)) $temas = [];
            return normalizarTecnologia($temas);

        case 'demanda_mercado':
            $stmt = $pdo->prepare("SELECT sustentabilidade FROM negocio_visao WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            return normalizarDemanda($stmt->fetchColumn());

        case 'parcerias_estrategicas':
            $stmt = $pdo->prepare("SELECT apoios FROM negocio_visao WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            $raw = $stmt->fetchColumn();
            $apoios = json_decode($raw ?? '[]', true);
            if (!is_array($apoios)) $apoios = [];
            return normalizarParcerias($apoios);

        case 'internacionalizacao':
            $stmt = $pdo->prepare("SELECT escala FROM negocio_visao WHERE negocio_id=?");
            $stmt->execute([$negocio_id]);
            return normalizarInternacionalizacao($stmt->fetchColumn());

        default:
            return 'nao_informado';
    }
}

function calcularPenalidades(PDO $pdo, int $negocio_id, string $tipo_score): int {
    $penalty = 0;

    if ($tipo_score === 'INVESTIMENTO') {
        $stmt = $pdo->prepare("SELECT faixa_faturamento, margem_bruta FROM negocio_financeiro WHERE negocio_id=?");
        $stmt->execute([$negocio_id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if (($dados['faixa_faturamento'] ?? '') === 'Não houve faturamento ainda') {
            $penalty += 10;
        }
        if (($dados['margem_bruta'] ?? '') === 'Menor que 20%') {
            $penalty += 5;
        }
    }

    if ($tipo_score === 'IMPACTO') {
        $stmt = $pdo->prepare("SELECT medicao, resultados FROM negocio_impacto WHERE negocio_id=?");
        $stmt->execute([$negocio_id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if (($dados['medicao'] ?? '') === 'Ainda não medimos e não temos indicadores') {
            $penalty += 10;
        }
        if (empty($dados['resultados'])) {
            $penalty += 5;
        }
    }

    if ($tipo_score === 'ESCALA') {
        $stmt = $pdo->prepare("SELECT escala FROM negocio_visao WHERE negocio_id=?");
        $stmt->execute([$negocio_id]);
        $escala = $stmt->fetchColumn();
        if ($escala === 'Manter o modelo atual como negócio de nicho ou territorial') {
            $penalty += 5;
        }
    }

    return $penalty;
}