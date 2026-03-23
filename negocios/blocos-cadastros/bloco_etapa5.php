<?php
// bloco_etapa5.php - partial de visualização (read-only)
// Espera: $negocio, $negocio_id, $apresentacao, $galeria, $links
if (!isset($negocio) || !isset($negocio_id)) return;
$apresentacao = $apresentacao ?? [];
$galeria = is_array($galeria) ? $galeria : [];
$links = is_array($links) ? $links : [];

$links = json_decode($apresentacao['info_adicionais_links'] ?? '[]', true);
if (!is_array($links)) $links = [];

/**
 * Helper local para exibir valor do desafio (1-5) ou '-'
 */
function desafio_valor(array $ap, string $campo): string {
    if (!isset($ap[$campo]) || $ap[$campo] === '' || $ap[$campo] === null) return '-';
    return htmlspecialchars((string)$ap[$campo]);
}
/**
 * Transforma qualquer link do YouTube (watch, youtu.be, shorts) em link de Embed
 */
if (!function_exists('embedYouTube')) {
    function embedYouTube(string $url): string {
        $video_id = '';
        // Padrão 1: youtu.be/ID
        if (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $matches)) {
            $video_id = $matches[1];
        } 
        // Padrão 2: youtube.com/watch?v=ID
        elseif (preg_match('/youtube\.com\/.*v=([^\&\?\/]+)/', $url, $matches)) {
            $video_id = $matches[1];
        } 
        // Padrão 3: youtube.com/embed/ID
        elseif (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $url, $matches)) {
            $video_id = $matches[1];
        }
        // Padrão 4: youtube.com/shorts/ID
        elseif (preg_match('/youtube\.com\/shorts\/([^\&\?\/]+)/', $url, $matches)) {
            $video_id = $matches[1];
        }
        
        // Se achou um ID, retorna o link de embed formatado corretamente
        if (!empty($video_id)) {
            return "https://www.youtube.com/embed/" . $video_id;
        }
        
        // Se não conseguir converter, retorna a URL original (mas provável que não funcione no iframe)
        return htmlspecialchars($url);
    }
}

/**
 * Mapa legível dos 21 desafios (mesma ordem do formulário)
 */
$desafios_map = [
    "acessar_capital" => "Acessar capital",
    "fluxo_caixa" => "Manter fluxo de caixa",
    "melhorar_gestao" => "Melhorar gestão",
    "estruturar_equipe" => "Estruturar equipe",
    "falta_conselho_mentoria" => "Falta de conselho/mentoria estratégica",
    "escassez_tecnico" => "Escassez de pessoas com perfil técnico",
    "marketing_posicionamento" => "Marketing e posicionamento",
    "baixa_demanda_vendas" => "Baixa demanda / vendas",
    "falta_entendimento_publico" => "Falta de entendimento do público sobre impacto",
    "parcerias_networking" => "Desenvolver parcerias e networking",
    "acesso_mentoria_especializada" => "Acesso a mentoria especializada",
    "falta_entendimento_bancos" => "Falta de entendimento por bancos/instituições",
    "relacionamento_governo" => "Relacionamento com governo",
    "acesso_mercado_distribuicao" => "Acesso ao mercado / distribuição",
    "logistica_cara_ineficiente" => "Logística cara ou ineficiente",
    "baixa_capacidade_entrega" => "Baixa capacidade de entrega",
    "infraestrutura_limitada_cara" => "Infraestrutura limitada ou cara",
    "internacionalizacao" => "Internacionalização",
    "instabilidade_economica" => "Instabilidade econômica",
    "carga_tributaria_burocracia" => "Carga tributária e burocracia",
    "regulacao_desfavoravel" => "Regulação desfavorável"
];
?>

<div class="card mb-4">

    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-image me-1"></i> Apresentação do Negócio (Etapa 5)</strong>
        <?php 
            $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
            $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;
            
            if (!$ehAdmin && !$somenteLeitura): 
            ?>
                <a href="/negocios/editar_etapa5.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn btn-sm btn-outline-primary">Editar</a>
        <?php endif; ?>
    </div>
    
    <div class="card-body">
        <div class="row">
            <!-- Coluna esquerda: logo, frase e metadados -->
            <div class="col-lg-4 col-md-5 text-center">
                <?php if (!empty($apresentacao['logo_negocio'])): ?>
                <img src="<?= htmlspecialchars($apresentacao['logo_negocio']) ?>" alt="<?= htmlspecialchars($negocio['nome_fantasia'] ?? 'Logo') ?>" class="img-fluid apresentacao-logo rounded shadow mb-3" loading="lazy">
                <?php else: ?>
                    <div class="bg-light rounded p-4 mb-3">
                        <i class="bi bi-building text-muted fs-1"></i>
                    </div>
                <?php endif; ?>

                <?php if (!empty($apresentacao['frase_negocio'])): ?>
                     <i class="bi bi-eye text-secondary me-1"></i>
                    <blockquote class="apresentacao-quote fst-italic text-primary border-start border-4 ps-3">
                        <i class="bi bi-quote"></i> <?= htmlspecialchars($apresentacao['frase_negocio']) ?>
                    </blockquote>
                    
                <?php endif; ?>

                <div class="mt-3 text-start">
                    <?php if (!empty($apresentacao['modelo_negocio'])): ?>
                        <p class="mb-1"><strong>Modelo de Negócio</strong> <span class="small-muted"><?= htmlspecialchars($apresentacao['modelo_negocio']) ?></span> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <?php endif; ?>
                    <?php if (!empty($apresentacao['colaboradores'])): ?>
                        <p class="mb-1"><strong>Colaboradores</strong> <span class="small-muted"><?= nl2br(htmlspecialchars($apresentacao['colaboradores'])) ?></span> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <?php endif; ?>
                    <?php if (!empty($apresentacao['apoio'])): ?>
                        <p class="mb-1"><strong>Apoio / Parcerias</strong> <span class="small-muted"><?= nl2br(htmlspecialchars($apresentacao['apoio'])) ?></span> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <?php endif; ?>
                    <?php if (!empty($apresentacao['tipo_solucao'])): ?>
                        <p class="mb-1"><strong>Tipo solução:</strong> <span class="small-muted"><?= htmlspecialchars($apresentacao['tipo_solucao']) ?></span> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($apresentacao['apresentacao_pdf'])): ?>
                <a href="<?= htmlspecialchars($apresentacao['apresentacao_pdf']) ?>" target="_blank" class="btn btn-outline-danger btn-sm w-100 mt-3">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Baixar PDF  <i class="bi bi-eye text-secondary me-1"></i>
                </a>
                <?php endif; ?>
            </div>

            <!-- Coluna direita: vídeos, textos e desafios -->
            <div class="col-lg-8 col-md-7">
                <div class="row">
                                        <!-- Vídeos -->
                    <?php if (!empty($apresentacao['video_pitch_url'])): ?>
                        <div class="col-md-6 mb-4">
                            <h5 class="mb-2"><i class="bi bi-play-circle me-1"></i> Pitch do Negócio <i class="bi bi-eye text-secondary me-1"></i></h5>
                            <a href="<?= htmlspecialchars($apresentacao['video_pitch_url']) ?>" 
                               class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center py-2 shadow-sm" 
                               target="_blank"
                               rel="noopener noreferrer">
                                <i class="bi bi-youtube fs-3 me-3"></i>
                                <div class="text-start lh-sm">
                                    <span class="d-block fw-bold">Ver Vídeo Pitch</span>
                                    <small style="font-size: 0.75rem;">Assistir no YouTube</small>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($apresentacao['apresentacao_video_url'])): ?>
                        <div class="col-md-6 mb-4">
                            <h5 class="mb-2"><i class="bi bi-camera-video me-1"></i> Institucional <i class="bi bi-eye text-secondary me-1"></i></h5>
                            <a href="<?= htmlspecialchars($apresentacao['apresentacao_video_url']) ?>" 
                               class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center py-2 shadow-sm" 
                               target="_blank"
                               rel="noopener noreferrer">
                                <i class="bi bi-youtube fs-3 me-3"></i>
                                <div class="text-start lh-sm">
                                    <span class="d-block fw-bold">Ver Institucional</span>
                                    <small style="font-size: 0.75rem;">Assistir no YouTube</small>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>


                    <!-- Tipo solução / Programas / Inovação -->
                    <div class="col-12">
                        <div class="mb-2">
                            <?php if (!empty($apresentacao['programas'])): ?>
                                <p class="border-bottom border-light p-2"><strong>Programas de apoio e Parcerias:</strong> <?= htmlspecialchars($apresentacao['programas']) ?> <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Problema x Solução e Inovação -->
                    <div class="row mt-4 mb-4">
                        <!-- Problema -->
                        <div class="col-md-6 mb-3">
                            <h5><i class="bi bi-exclamation-triangle text-danger me-2"></i> Qual problema você resolve? <i class="bi bi-eye text-secondary me-1"></i></h5>
                            <div class="p-3 bg-light rounded border-start border-4 border-danger">
                                <p class="mb-0 text-muted">
                                    <?= !empty($apresentacao['problema_resolvido']) ? nl2br(htmlspecialchars($apresentacao['problema_resolvido'])) : '<em>Não informado</em>' ?>
                                </p>
                            </div>
                        </div>

                        <!-- Solução -->
                        <div class="col-md-6 mb-3">
                            <h5><i class="bi bi-check-circle text-success me-2"></i> Qual solução você oferece? <i class="bi bi-eye text-secondary me-1"></i></h5>
                            <div class="p-3 bg-light rounded border-start border-4 border-success">
                                <p class="mb-0 text-muted">
                                    <?= !empty($apresentacao['solucao_oferecida']) ? nl2br(htmlspecialchars($apresentacao['solucao_oferecida'])) : '<em>Não informado</em>' ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php
                    // Prepara as inovações selecionadas
                    $tiposInovacao = [
                        'inovacao_tecnologica' => 'Tecnológica',
                        'inovacao_produto' => 'Produto',
                        'inovacao_servico' => 'Serviço',
                        'inovacao_modelo' => 'Modelo de Negócio',
                        'inovacao_social' => 'Social',
                        'inovacao_ambiental' => 'Ambiental',
                        'inovacao_cadeia_valor' => 'Cadeia de Valor',
                        'inovacao_governanca' => 'Governança',
                        'inovacao_impacto' => 'Impacto',
                        'inovacao_financiamento' => 'Financiamento'
                    ];
                    
                    $inovacoesAtivas = [];
                    foreach ($tiposInovacao as $campo => $label) {
                        if (!empty($apresentacao[$campo])) {
                            $inovacoesAtivas[] = $label;
                        }
                    }
                    
                    // Verifica se há alguma inovação salva no novo formato, ou o antigo
                    $temInovacaoDetalhe = !empty($apresentacao['descricao_inovacao']) || count($inovacoesAtivas) > 0 || (!empty($apresentacao['inovacao']) && $apresentacao['inovacao'] === 'sim');
                    ?>

                    <?php if ($temInovacaoDetalhe): ?>
                        <div class="col-12 mb-4">
                            <h5><i class="bi bi-rocket-takeoff me-1"></i> Inovação <i class="bi bi-eye text-secondary me-1"></i></h5>
                            <div class="alert alert-success small mb-0">
                                <?php if (count($inovacoesAtivas) > 0): ?>
                                    <div class="mb-2">
                                        <strong>Tipos de inovação aplicadas:</strong><br>
                                        <?php foreach ($inovacoesAtivas as $tag): ?>
                                            <span class="badge bg-success text-white me-1 mb-1 fw-normal px-2 py-1"><i class="bi bi-check-circle me-1"></i><?= $tag ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif (!empty($apresentacao['inovacao'])): ?>
                                    <!-- Caso seja registro antigo que só tinha sim/não -->
                                    <div class="mb-2"><strong>Inovação:</strong> <?= htmlspecialchars(ucfirst($apresentacao['inovacao'])) ?></div>
                                <?php endif; ?>

                                <?php if (!empty($apresentacao['descricao_inovacao'])): ?>
                                    <div class="mt-2 pt-2 border-top border-success border-opacity-25">
                                        <strong>Descrição:</strong><br>
                                        <?= nl2br(htmlspecialchars($apresentacao['descricao_inovacao'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <!-- Fim row -->
        <div class="row mt-2 mb-2">
            <?php if (!empty($apresentacao['info_adicionais'])): ?>
                <div class="col-12">
                    <h5 class="mt-2 mb-1"><i class="bi bi-info-circle me-1"></i> Informações Adicionais <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></h5>
                    <p class="small-muted mb-0 border border-light rounded p-2"><?= nl2br(htmlspecialchars($apresentacao['info_adicionais'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
        <!-- Fim row Info Adicionais-->
         
        <!-- Desafios-->
        <div class="row mb-3 mb-2">
            <div class="col-12">
                <h5 class="mt-2 mb-2"><i class="bi bi-exclamation-triangle me-1"></i> Principais Desafios <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></h5>
                <div class="row">
                    <?php
                    // 1. Prepara um array apenas com os desafios que foram selecionados (valor > 0)
                    $desafios_ordenados = [];
                    foreach ($desafios_map as $key => $label) {
                        $campo = "desafio_" . $key;
                        $valor = (int)($apresentacao[$campo] ?? 0);
                        
                        if ($valor > 0) {
                            $desafios_ordenados[] = [
                                'label' => $label,
                                'valor' => $valor,
                                'ordem' => $valor
                            ];
                        }
                    }

                    // 2. Ordena o array em ordem decrescente (do maior desafio [5] para o menor [1])
                    usort($desafios_ordenados, function($a, $b) {
                        return $b['ordem'] <=> $a['ordem'];
                    });

                    // 3. Renderiza a lista de desafios (se houver)
                    if (count($desafios_ordenados) > 0):
                        foreach ($desafios_ordenados as $item):
                            // Determina a cor da barra baseada na gravidade (5 = vermelho, 4 = laranja, 3 = amarelo...)
                            $corBarra = 'bg-secondary';
                            $corTexto = 'text-dark';
                            switch($item['valor']) {
                                case 5: $corBarra = 'bg-danger'; $corTexto = 'text-white'; break;
                                case 4: $corBarra = 'bg-warning text-dark'; break;
                                case 3: $corBarra = 'bg-info text-dark'; break;
                                case 2: $corBarra = 'bg-primary opacity-75 text-white'; break;
                                case 1: $corBarra = 'bg-success opacity-75 text-white'; break;
                            }
                    ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex align-items-stretch border rounded h-100 overflow-hidden bg-white shadow-sm">
                                <!-- Aba de cor indicando a prioridade -->
                                <div class="<?= $corBarra ?> d-flex align-items-center justify-content-center px-3" style="min-width: 45px;">
                                    <span class="fw-bold fs-5 <?= $corTexto ?>"><?= htmlspecialchars($item['valor']) ?></span>
                                </div>
                                <!-- Texto do desafio -->
                                <div class="p-2 ms-2 d-flex align-items-center flex-grow-1">
                                    <span class="small fw-medium text-dark lh-sm"><?= htmlspecialchars($item['label']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <div class="col-12">
                            <div class="alert alert-secondary small py-2">
                                <i class="bi bi-info-circle me-1"></i> Nenhum desafio foi classificado.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
        <!-- Fim row - Desafios -->

        <div class="row">
            <!-- Galeria (mantém comportamento atual) -->
            <div class="row">
            <?php if (!empty($galeria) && is_array($galeria)): ?>
                <?php
                // UID estável por negócio (evita conflito se a página tiver mais de um bloco)
                $uid = preg_replace('/[^a-zA-Z0-9]/', '', (string)($negocioid ?? $negocio_id ?? '0'));
                if ($uid === '') $uid = 'x' . mt_rand(1000, 9999);
                ?>

                <hr class="my-4">
                <h5 class="mb-3"><i class="bi bi-images me-1"></i> Galeria (<?= count($galeria) ?>/10) <i class="bi bi-eye text-secondary me-1"></i></h5>

                <!-- Justified thumbnails -->
                <div class="jg"
                    data-jg-uid="<?= $uid ?>"
                    data-jg-target-h="150"
                    data-jg-max-h="210">
                <?php foreach ($galeria as $i => $img): if (empty($img)) continue; ?>
                    <a href="<?= htmlspecialchars($img) ?>">
                    <img src="<?= htmlspecialchars($img) ?>" alt="Galeria <?= ($i+1) ?>" loading="lazy">
                    </a>
                <?php endforeach; ?>
                </div>

                <!-- Lightbox overlay -->
                <div class="jg-lightbox" id="jgLightbox-<?= $uid ?>" aria-hidden="true">
                <button type="button" class="jg-close" aria-label="Fechar">×</button>
                <button type="button" class="jg-btn jg-prev" aria-label="Anterior">‹</button>
                <img class="jg-full" alt="">
                <button type="button" class="jg-btn jg-next" aria-label="Próximo">›</button>
                <div class="jg-caption"></div>
                </div>

            <?php else: ?>
                <div class="alert alert-info">
                <i class="bi bi-images me-2"></i> Galeria vazia (adicione até 10 imagens)
                </div>
            <?php endif; ?>
        </div>

        </div>
    </div>   <!-- Fim Card-body -->    
</div><!-- Fim Card -->