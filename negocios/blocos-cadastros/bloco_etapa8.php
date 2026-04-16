<?php
// bloco_etapa5.php - partial de visualização (read-only)
// Espera: $negocio, $negocio_id, $apresentacao, $galeria, $links
if (!isset($negocio) || !isset($negocio_id)) return;
$apresentacao = $apresentacao ?? [];
$galeria = is_array($galeria) ? $galeria : [];
$links = is_array($links) ? $links : [];

$links = json_decode($apresentacao['info_adicionais_links'] ?? '[]', true);
if (!is_array($links)) $links = [];

function desafio_valor(array $ap, string $campo): string {
    if (!isset($ap[$campo]) || $ap[$campo] === '' || $ap[$campo] === null) return '-';
    return htmlspecialchars((string)$ap[$campo]);
}

if (!function_exists('embedYouTube')) {
    function embedYouTube(string $url): string {
        $video_id = '';
        if (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $matches)) {
            $video_id = $matches[1];
        } elseif (preg_match('/youtube\.com\/.*v=([^\&\?\/]+)/', $url, $matches)) {
            $video_id = $matches[1];
        } elseif (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $url, $matches)) {
            $video_id = $matches[1];
        } elseif (preg_match('/youtube\.com\/shorts\/([^\&\?\/]+)/', $url, $matches)) {
            $video_id = $matches[1];
        }

        if (!empty($video_id)) {
            return "https://www.youtube.com/embed/" . $video_id;
        }

        return htmlspecialchars($url);
    }
}

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

<div class="emp-review-card mb-4">
    <div class="emp-review-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="emp-review-card-title">
            <i class="bi bi-image me-1"></i> Apresentação do Negócio
            <span class="emp-review-step">(Etapa 8)</span>
        </div>

        <?php
        $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
        $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;

        if (!$ehAdmin && !$somenteLeitura):
        ?>
            <a href="/negocios/editar_etapa8.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn-emp-outline btn-sm">
                Editar
            </a>
        <?php endif; ?>
    </div>

    <div class="emp-review-card-body">
        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="emp-review-apresentacao-side">
                    <?php if (!empty($apresentacao['logo_negocio'])): ?>
                        <img src="<?= htmlspecialchars($apresentacao['logo_negocio']) ?>"
                             alt="<?= htmlspecialchars($negocio['nome_fantasia'] ?? 'Logo') ?>"
                             class="emp-review-logo-card"
                             loading="lazy">
                    <?php else: ?>
                        <div class="emp-review-logo-placeholder">
                            <i class="bi bi-building text-muted fs-1"></i>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($apresentacao['imagem_destaque'])): ?>
                        <div class="mb-3">
                            <div class="emp-review-label mb-1">
                                <i class="bi bi-eye text-secondary me-1"></i> Imagem de Capa
                            </div>
                            <img src="<?= htmlspecialchars($apresentacao['imagem_destaque']) ?>"
                                alt="Imagem de capa"
                                class="img-fluid rounded w-100"
                                style="aspect-ratio:16/9; object-fit:cover; border:2px solid #CDDE00;"
                                loading="lazy">
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($apresentacao['frase_negocio'])): ?>
                        <blockquote class="emp-review-quote">
                            <i class="bi bi-eye text-secondary me-1"></i>
                            <span><i class="bi bi-quote me-1"></i><?= htmlspecialchars($apresentacao['frase_negocio']) ?></span>
                        </blockquote>
                    <?php endif; ?>

                    <div class="emp-review-meta-list">
                        <?php if (!empty($apresentacao['modelo_negocio'])): ?>
                            <div class="emp-review-item">
                                <span class="emp-review-label">Modelo de Negócio</span>
                                <div class="emp-review-value">
                                    <?= htmlspecialchars($apresentacao['modelo_negocio']) ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($apresentacao['colaboradores'])): ?>
                            <div class="emp-review-item">
                                <span class="emp-review-label">Colaboradores</span>
                                <div class="emp-review-value">
                                    <?= nl2br(htmlspecialchars($apresentacao['colaboradores'])) ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($apresentacao['apoio'])): ?>
                            <div class="emp-review-item">
                                <span class="emp-review-label">Apoio / Parcerias</span>
                                <div class="emp-review-value">
                                    <?= nl2br(htmlspecialchars($apresentacao['apoio'])) ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($apresentacao['tipo_solucao'])): ?>
                            <div class="emp-review-item">
                                <span class="emp-review-label">Tipo de Solução</span>
                                <div class="emp-review-value">
                                    <?= htmlspecialchars($apresentacao['tipo_solucao']) ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($apresentacao['apresentacao_pdf'])): ?>
                        <a href="<?= htmlspecialchars($apresentacao['apresentacao_pdf']) ?>" target="_blank" class="emp-review-link-chip mt-3 w-100 justify-content-center">
                            <i class="bi bi-file-earmark-pdf"></i> Baixar PDF <i class="bi bi-eye text-secondary ms-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="row g-4">
                    <?php if (!empty($apresentacao['video_pitch_url'])): ?>
                        <div class="col-md-6">
                            <div class="emp-review-video-card">
                                <div class="emp-review-subblock-title principal mb-2">
                                    <i class="bi bi-play-circle me-1"></i> Pitch do Negócio <i class="bi bi-eye text-secondary ms-1"></i>
                                </div>
                                <a href="<?= htmlspecialchars($apresentacao['video_pitch_url']) ?>"
                                   class="emp-review-video-link"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <i class="bi bi-youtube fs-3 me-3"></i>
                                    <div>
                                        <strong class="d-block">Ver Vídeo Pitch</strong>
                                        <small>Assistir no YouTube</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($apresentacao['apresentacao_video_url'])): ?>
                        <div class="col-md-6">
                            <div class="emp-review-video-card">
                                <div class="emp-review-subblock-title principal mb-2">
                                    <i class="bi bi-camera-video me-1"></i> Institucional <i class="bi bi-eye text-secondary ms-1"></i>
                                </div>
                                <a href="<?= htmlspecialchars($apresentacao['apresentacao_video_url']) ?>"
                                   class="emp-review-video-link"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <i class="bi bi-youtube fs-3 me-3"></i>
                                    <div>
                                        <strong class="d-block">Ver Institucional</strong>
                                        <small>Assistir no YouTube</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($apresentacao['programas'])): ?>
                        <div class="col-12">
                            <div class="emp-review-item emp-review-boxed">
                                <span class="emp-review-label">Programas de apoio e Parcerias</span>
                                <div class="emp-review-value">
                                    <?= htmlspecialchars($apresentacao['programas']) ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-6">
                        <div class="emp-review-content-panel danger">
                            <h5><i class="bi bi-exclamation-triangle text-danger me-2"></i> Qual problema você resolve? <i class="bi bi-eye text-secondary ms-1"></i></h5>
                            <p class="mb-0 text-muted">
                                <?= !empty($apresentacao['problema_resolvido']) ? nl2br(htmlspecialchars($apresentacao['problema_resolvido'])) : '<em>Não informado</em>' ?>
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="emp-review-content-panel success">
                            <h5><i class="bi bi-check-circle text-success me-2"></i> Qual solução você oferece? <i class="bi bi-eye text-secondary ms-1"></i></h5>
                            <p class="mb-0 text-muted">
                                <?= !empty($apresentacao['solucao_oferecida']) ? nl2br(htmlspecialchars($apresentacao['solucao_oferecida'])) : '<em>Não informado</em>' ?>
                            </p>
                        </div>
                    </div>

                    <?php
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

                    $temInovacaoDetalhe = !empty($apresentacao['descricao_inovacao']) || count($inovacoesAtivas) > 0 || (!empty($apresentacao['inovacao']) && $apresentacao['inovacao'] === 'sim');
                    ?>

                    <?php if ($temInovacaoDetalhe): ?>
                        <div class="col-12">
                            <div class="emp-review-innovation-box">
                                <h5><i class="bi bi-rocket-takeoff me-1"></i> Inovação <i class="bi bi-eye text-secondary ms-1"></i></h5>

                                <?php if (count($inovacoesAtivas) > 0): ?>
                                    <div class="emp-review-links mb-2">
                                        <?php foreach ($inovacoesAtivas as $tag): ?>
                                            <span class="emp-review-link-chip success">
                                                <i class="bi bi-check-circle"></i><?= $tag ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif (!empty($apresentacao['inovacao'])): ?>
                                    <div class="mb-2"><strong>Inovação:</strong> <?= htmlspecialchars(ucfirst($apresentacao['inovacao'])) ?></div>
                                <?php endif; ?>

                                <?php if (!empty($apresentacao['descricao_inovacao'])): ?>
                                    <div class="emp-review-innovation-description">
                                        <strong>Descrição:</strong><br>
                                        <?= nl2br(htmlspecialchars($apresentacao['descricao_inovacao'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($apresentacao['info_adicionais'])): ?>
                        <div class="col-12">
                            <div class="emp-review-item emp-review-boxed">
                                <span class="emp-review-label">Informações Adicionais</span>
                                <div class="emp-review-value">
                                    <?= nl2br(htmlspecialchars($apresentacao['info_adicionais'])) ?>
                                    <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <div class="emp-review-subblock">
                            <div class="emp-review-subblock-title secondary">
                                <i class="bi bi-exclamation-triangle me-1"></i> Principais Desafios <i class="bi bi-eye-slash text-danger-emphasis ms-1"></i>
                            </div>

                            <div class="row">
                                <?php
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

                                usort($desafios_ordenados, function($a, $b) {
                                    return $b['ordem'] <=> $a['ordem'];
                                });

                                if (count($desafios_ordenados) > 0):
                                    foreach ($desafios_ordenados as $item):
                                        $classe = 'nivel-baixo';
                                        switch($item['valor']) {
                                            case 5: $classe = 'nivel-critico'; break;
                                            case 4: $classe = 'nivel-alto'; break;
                                            case 3: $classe = 'nivel-medio'; break;
                                            case 2: $classe = 'nivel-moderado'; break;
                                            case 1: $classe = 'nivel-baixo'; break;
                                        }
                                ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="emp-review-desafio-card <?= $classe ?>">
                                            <div class="emp-review-desafio-rank"><?= htmlspecialchars($item['valor']) ?></div>
                                            <div class="emp-review-desafio-text"><?= htmlspecialchars($item['label']) ?></div>
                                        </div>
                                    </div>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                    <div class="col-12">
                                        <div class="alert alert-secondary small py-2 mb-0">
                                            <i class="bi bi-info-circle me-1"></i> Nenhum desafio foi classificado.
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <?php if (!empty($galeria) && is_array($galeria)): ?>
                            <?php
                            $uid = preg_replace('/[^a-zA-Z0-9]/', '', (string)($negocioid ?? $negocio_id ?? '0'));
                            if ($uid === '') $uid = 'x' . mt_rand(1000, 9999);
                            ?>

                            <div class="emp-review-subblock mt-2">
                                <div class="emp-review-subblock-title secondary">
                                    <i class="bi bi-images me-1"></i> Galeria
                                    <span class="emp-review-count">(<?= count($galeria) ?>/10)</span>
                                    <i class="bi bi-eye text-secondary ms-1"></i>
                                </div>

                                <div class="emp-review-gallery-shell">
                                    <div class="jg emp-review-gallery"
                                         data-jg-uid="<?= $uid ?>"
                                         data-jg-target-h="150"
                                         data-jg-max-h="210">
                                        <?php foreach ($galeria as $i => $img): if (empty($img)) continue; ?>
                                            <a href="<?= htmlspecialchars($img) ?>">
                                                <img src="<?= htmlspecialchars($img) ?>" alt="Galeria <?= ($i+1) ?>" loading="lazy">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="jg-lightbox" id="jgLightbox-<?= $uid ?>" aria-hidden="true">
                                    <button type="button" class="jg-close" aria-label="Fechar">×</button>
                                    <button type="button" class="jg-btn jg-prev" aria-label="Anterior">‹</button>
                                    <img class="jg-full" alt="">
                                    <button type="button" class="jg-btn jg-next" aria-label="Próximo">›</button>
                                    <div class="jg-caption"></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mt-2 mb-0">
                                <i class="bi bi-images me-2"></i> Galeria vazia (adicione até 10 imagens)
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>