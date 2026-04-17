<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id = (int)($_GET['id'] ?? $_SESSION['negocio_id'] ?? 0);
if ($negocio_id === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}
$_SESSION['negocio_id'] = $negocio_id;

$stmt = $pdo->prepare("
    SELECT n.*, e.eh_fundador 
    FROM negocios n 
    JOIN empreendedores e ON n.empreendedor_id = e.id 
    WHERE n.id = ? AND n.empreendedor_id = ?
");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$negocio) die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);

$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container my-5 emp-inner">

    <?php
        $etapaAtual = 8;
        include __DIR__ . '/../app/views/partials/progress.php';
        include __DIR__ . '/../app/views/partials/intro_text_apresentacao_negocios.php';
    ?>

    <?php if (!empty($_SESSION['errors_etapa8'])): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-circle-fill mt-1"></i>
            <ul class="mb-0 ps-2">
                <?php foreach ($_SESSION['errors_etapa8'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors_etapa8']); ?>
    <?php endif; ?>

    <form action="/negocios/processar_etapa8.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="cadastro">

        <!-- ══════════════════════════════════════════════════
             SEÇÃO 1 — Identidade Visual e Uploads Principais
        ═══════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-image"></i> Identidade Visual e Uploads Principais</div>

            <!-- Logotipo -->
            <div class="mb-4">
                <label class="form-label">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Logotipo do negócio *
                </label>
                <input type="file" name="logo_negocio" id="logo_negocio" class="form-control"
                    accept="image/png,image/jpeg,image/jpg,image/webp" required>
                <div class="form-text">
                    Envie o logotipo oficial da sua empresa/negócio.<br>
                    ⚠️ Máx. 50MB. Recomendação: imagem quadrada (ex.: 500×500px) em formato PNG, JPG, JPEG ou WebP.
                </div>
            </div>

            <!-- Imagem de Destaque -->
            <div class="mb-2">
                <label class="form-label d-flex align-items-center gap-2">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Imagem de Destaque
                    <span class="badge" style="background:#CDDE00;color:#1E3425;font-size:.7rem;">Capa da Vitrine</span>
                    <span class="badge" style="background:rgba(151,163,39,.15);color:#5c6318;font-size:.7rem;">Recomendado</span>
                </label>

                <div class="destaque-info-box mb-3">
                    <i class="bi bi-layout-text-window-reverse destaque-info-icon"></i>
                    <div>
                        <strong>Esta será a capa do seu negócio na Vitrine Nacional</strong>
                        <p class="mb-0 small">Escolha uma imagem que represente bem seu negócio — ela será exibida em destaque para todos os visitantes da plataforma. Proporção recomendada: <strong>16:9</strong> (ex: 1280×720px). Máximo 5MB.</p>
                    </div>
                </div>

                <?php if (!empty($apresentacao['imagem_destaque'])): ?>
                <div class="destaque-preview-wrap mb-3" id="destaque-preview-atual">
                    <img src="<?= htmlspecialchars($apresentacao['imagem_destaque']) ?>"
                         alt="Imagem de Destaque atual" class="destaque-preview-img">
                    <div class="destaque-preview-overlay">
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Capa atual</span>
                    </div>
                    <label class="destaque-remover-btn">
                        <input type="checkbox" name="remover_imagem_destaque" value="1" class="d-none" id="removerImagemDestaque">
                        <span id="removerDestaqueLabel"><i class="bi bi-trash me-1"></i> Remover capa</span>
                    </label>
                </div>
                <?php endif; ?>

                <div class="upload-area-destaque" id="uploadAreaDestaque">
                    <input type="file" name="imagem_destaque" id="imagemDestaque"
                           accept="image/png,image/jpeg,image/jpg,image/webp" class="d-none">
                    <label for="imagemDestaque" class="upload-label-destaque" id="uploadLabelDestaque">
                        <i class="bi bi-cloud-arrow-up-fill upload-icon-destaque"></i>
                        <span class="upload-text-main">Clique para selecionar a imagem de capa</span>
                        <span class="upload-text-sub">JPG, PNG ou WebP · Máx. 5MB · Proporção 16:9 recomendada</span>
                    </label>
                    <div id="novoDestaquePreview" class="d-none mt-3 text-center">
                        <img id="novoDestaqueImg" src="#" alt="Preview" class="destaque-preview-img">
                        <p class="small text-success mt-2"><i class="bi bi-check-circle me-1"></i> Nova imagem selecionada</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             SEÇÃO 2 — Apresentação do Negócio
        ═══════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-chat-quote"></i> Apresentação do Negócio</div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Descreva seu negócio em uma frase (até 120 caracteres) *
                </label>
                <input type="text" name="frase_negocio" class="form-control" maxlength="120" required>
                <div class="form-text">Exemplo: Plataforma que conecta pessoas, negócios e instituições...</div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-fill lbl-pub me-1"></i> Qual problema você resolve? (até 200 caracteres) *
                    </label>
                    <textarea name="problema_resolvido" class="form-control" maxlength="200" rows="4" required><?= htmlspecialchars($apresentacao['problema_resolvido'] ?? '') ?></textarea>
                    <div class="form-text">Descreva a dor ou desafio do seu público-alvo.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-fill lbl-pub me-1"></i> Qual solução você oferece? (até 200 caracteres) *
                    </label>
                    <textarea name="solucao_oferecida" class="form-control" maxlength="200" rows="4" required><?= htmlspecialchars($apresentacao['solucao_oferecida'] ?? '') ?></textarea>
                    <div class="form-text">Descreva como seu produto/serviço resolve o problema acima.</div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             SEÇÃO 3 — Materiais e Mídia
        ═══════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-play-circle"></i> Materiais e Mídia</div>

            <!-- Vídeo Pitch -->
            <div class="mb-4">
                <label class="form-label">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Vídeo Pitch — até 3 minutos (YouTube) *
                </label>
                <input type="url" name="video_pitch_url" class="form-control"
                    placeholder="Cole aqui a URL do YouTube"
                    pattern="^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[A-Za-z0-9_-]{11}$"
                    required>
                <div class="form-text">
                    Exemplo válido: https://www.youtube.com/watch?v=XXXXXXXXXXX<br>
                    Esse vídeo será sua apresentação na vitrine de negócios e para a premiação.
                </div>
            </div>

            <!-- PDF + Vídeo Institucional -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-fill lbl-pub me-1"></i> Apresentação institucional (PDF)
                    </label>
                    <input type="file" name="apresentacao_pdf" class="form-control" accept=".pdf">
                    <div class="form-text">
                        Upload de material explicativo sobre sua solução, trajetória e impacto.<br>
                        ⚠️ Máx. 5MB.
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-fill lbl-pub me-1"></i> Vídeo institucional (YouTube)
                    </label>
                    <input type="url" name="apresentacao_video_url" class="form-control"
                        placeholder="Cole aqui a URL do YouTube"
                        pattern="^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[A-Za-z0-9_-]{11}$">
                    <div class="form-text">Somente vídeos do YouTube são aceitos. Exemplo: https://www.youtube.com/watch?v=XXXXXXXXXXX</div>
                </div>
            </div>

            <!-- Galeria -->
            <div>
                <label class="form-label">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Galeria de imagens do negócio *
                </label>
                <input type="file" name="galeria_imagens[]" class="form-control" accept="image/*" multiple required>
                <div class="form-text">
                    Envie até 10 fotos (máx. 50MB cada). Mostre sua equipe, beneficiários, clientes, produto, operação ou local de atuação.<br>
                    ⚠️ Recomendação: envie apenas imagens essenciais. Muitas fotos podem deixar a página muito carregada.
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             SEÇÃO 4 — Inovação e Diferenciais
        ═══════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-lightbulb"></i> Inovação e Modelo de Atuação</div>

            <!-- Tipos de inovação -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Seu negócio incorpora inovação? Marque onde houver inovação real.
                </label>
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <?php
                    $inovacoes = [
                        'inovacao_tecnologica'  => ['Inovação Tecnológica',      'IA, Big Data, IoT, blockchain, plataformas digitais, tecnologias verdes.'],
                        'inovacao_produto'      => ['Inovação de Produto',        'Novo produto sustentável, materiais ecológicos, soluções regenerativas ou de saúde/educação inovadoras.'],
                        'inovacao_servico'      => ['Inovação de Serviço',        'Telemedicina para populações remotas, Educação online inclusiva, Plataformas de acesso a crédito, Serviços financeiros acessíveis.'],
                        'inovacao_modelo'       => ['Inovação em Modelo de Negócio', 'Marketplace de impacto, Economia compartilhada, Assinaturas acessíveis, Pay-per-use, Finanças inclusivas, Modelos B2G.'],
                        'inovacao_social'       => ['Inovação Social',            'Inclusão produtiva, Empoderamento de comunidades, Modelos de geração de renda, Educação transformadora, Democracia e participação cidadã.'],
                        'inovacao_ambiental'    => ['Inovação Ambiental',         'Economia circular, Redução de emissões, Agricultura regenerativa, Energia renovável, Gestão de resíduos, Conservação de biodiversidade.'],
                        'inovacao_cadeia_valor' => ['Inovação na Cadeia de Valor','Cadeias produtivas inclusivas, Comércio justo, Logística sustentável, Produção local descentralizada, Cadeias transparentes.'],
                        'inovacao_governanca'   => ['Inovação em Governança',     'Governança participativa, Cooperativismo moderno, Empresas de propriedade compartilhada, Modelos de gestão horizontal.'],
                        'inovacao_impacto'      => ['Inovação em Impacto',        'Novas métricas de impacto, Modelos de impacto escalável, Tecnologia para monitoramento socioambiental, Impacto em cadeia ou sistêmico.'],
                        'inovacao_financiamento'=> ['Inovação em Financiamento',  'Blended finance, Crowdfunding de impacto, Finanças regenerativas, Fundos comunitários, Impact investing.'],
                    ];
                    foreach ($inovacoes as $name => [$titulo, $desc]): ?>
                    <div class="col">
                        <div class="border rounded p-2 h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?= $titulo ?></strong>
                                    <div class="small text-muted"><?= $desc ?></div>
                                </div>
                                <div class="ms-2 flex-shrink-0">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input inovacao-tipo" type="radio" name="<?= $name ?>" value="sim">
                                        <label class="form-check-label small">Sim</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input inovacao-tipo" type="radio" name="<?= $name ?>" value="nao" checked>
                                        <label class="form-check-label small">Não</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Descrição condicional de inovação -->
            <div class="mb-3 mt-3" id="bloco-descricao-inovacao" style="display:none;">
                <label class="form-label">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Descreva brevemente as principais inovações do seu negócio (máx. 300 caracteres)
                </label>
                <textarea name="descricao_inovacao" id="descricao_inovacao" class="form-control" rows="3" maxlength="300"></textarea>
                <div class="form-text">Foque no que é realmente novo: tecnologia, forma de operar, modelo de negócio, impacto ou financiamento.</div>
            </div>

            <!-- Tipo solução / Modelo / Colaboradores -->
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Tipo de solução oferecida
                            </h5>
                            <?php foreach (['Produto', 'Serviço', 'Produto e Serviço'] as $opt): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_solucao" value="<?= $opt ?>" <?= $opt === 'Produto' ? 'required' : '' ?>>
                                <label class="form-check-label"><?= $opt ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Modelo de negócio
                            </h5>
                            <?php foreach (['B2B' => 'B2B – Empresa para Empresa', 'B2C' => 'B2C – Empresa para Consumidor', 'C2C' => 'C2C – Consumidor para Consumidor', 'B2B2C' => 'B2B2C – Empresa para Empresa para Consumidor', 'B2G' => 'B2G – Empresa para Governo', 'B2N' => 'B2N – Empresa para Ongs, Fundações, Associações'] as $val => $label): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modelo_negocio" value="<?= $val ?>" <?= $val === 'B2B' ? 'required' : '' ?>>
                                <label class="form-check-label"><?= $label ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Número de colaboradores
                            </h5>
                            <?php foreach (['Até 5', '6–20', '21–50', '51 ou mais'] as $opt): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="colaboradores" value="<?= $opt ?>" <?= $opt === 'Até 5' ? 'required' : '' ?>>
                                <label class="form-check-label"><?= $opt ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             SEÇÃO 5 — Histórico, Desafios e Reconhecimentos
        ═══════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-journal-text"></i> Histórico e Desafios do Negócio</div>

            <!-- Apoio -->
            <div class="mb-4">
                <label class="form-label">
                    <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Seu negócio já teve apoio de uma aceleradora ou programa de fomento?
                </label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="apoio" value="nao" required>
                    <label class="form-check-label">Não</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="apoio" value="sim">
                    <label class="form-check-label">Sim. Quais programas ou instituições apoiaram seu negócio? Até 120 caracteres</label>
                </div>
                <input type="text" name="programas" class="form-control mt-2" maxlength="120">
                <div class="form-text">Exemplos: aceleradoras, incubadoras, editais públicos, programas de impacto, universidades, ONGs, Sebrae, fundos etc.</div>
            </div>

            <!-- Desafios -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Quais são hoje os principais desafios para o desenvolvimento do seu negócio?
                </label>
                <p class="text-muted" style="font-size:.85rem;">
                    <strong>Passo 1:</strong> Selecione na lista abaixo os desafios que limitam o crescimento do seu negócio.<br>
                    <strong>Passo 2:</strong> Logo abaixo, classifique os itens selecionados.
                </p>

                <?php
                $gruposDesafios = [
                    "A. Finanças e Capital" => [
                        "Acessar investimento ou capital"                  => ["bi-cash-stack",       "desafio_acessar_capital"],
                        "Capital de giro / fluxo de caixa insuficiente"   => ["bi-bank",             "desafio_fluxo_caixa"],
                        "Estruturar modelo financeiro sustentável"         => ["bi-graph-up-arrow",   "desafio_melhorar_gestao"],
                        "Dificuldade de acessar crédito ou financiamento" => ["bi-credit-card",       "desafio_falta_entendimento_bancos"],
                    ],
                    "B. Mercado e Vendas" => [
                        "Baixa demanda ou volume de vendas"               => ["bi-cart-x",            "desafio_baixa_demanda_vendas"],
                        "Dificuldade de acessar novos mercados"           => ["bi-shop",              "desafio_acesso_mercado_distribuicao"],
                        "Marketing e reconhecimento de marca"             => ["bi-megaphone",         "desafio_marketing_posicionamento"],
                        "Dificuldade em comunicar o valor do impacto"     => ["bi-chat-left-dots",    "desafio_falta_entendimento_publico"],
                    ],
                    "C. Gestão e Estratégia" => [
                        "Falta de conselho/mentoria estratégica"          => ["bi-lightbulb",         "desafio_falta_conselho_mentoria"],
                        "Acesso a mentoria especializada"                 => ["bi-journal-check",     "desafio_acesso_mentoria_especializada"],
                        "Falta de tempo ou sobrecarga da liderança"       => ["bi-hourglass-split",   "desafio_baixa_capacidade_entrega"],
                    ],
                    "D. Equipe e Talentos" => [
                        "Estruturar ou expandir a equipe"                 => ["bi-people",            "desafio_estruturar_equipe"],
                        "Escassez de profissionais técnicos"              => ["bi-person-workspace",  "desafio_escassez_tecnico"],
                    ],
                    "E. Operação e Escala" => [
                        "Logística cara ou ineficiente"                   => ["bi-box-seam",          "desafio_logistica_cara_ineficiente"],
                        "Infraestrutura limitada ou cara"                 => ["bi-hammer",            "desafio_infraestrutura_limitada_cara"],
                        "Internacionalização"                             => ["bi-globe",             "desafio_internacionalizacao"],
                    ],
                    "F. Conexões e Ecossistema" => [
                        "Desenvolver parcerias e networking"              => ["bi-diagram-3",         "desafio_parcerias_networking"],
                        "Relacionamento com governo"                      => ["bi-building",          "desafio_relacionamento_governo"],
                    ],
                    "G. Ambiente e Contexto Econômico" => [
                        "Carga tributária e burocracia"                   => ["bi-file-earmark-text", "desafio_carga_tributaria_burocracia"],
                        "Regulação desfavorável"                          => ["bi-shield-exclamation","desafio_regulacao_desfavoravel"],
                        "Instabilidade econômica"                         => ["bi-graph-down",        "desafio_instabilidade_economica"],
                    ],
                ];
                ?>

                <!-- PASSO 1: SELEÇÃO -->
                <div class="row g-3">
                    <?php foreach ($gruposDesafios as $grupo => $itens): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0 bg-light">
                            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                                <h6 class="mb-0 text-primary"><?= $grupo ?></h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($itens as $label => [$icon, $name]):
                                    $valorAtual = (int)($apresentacao[$name] ?? 0);
                                    $isChecked  = $valorAtual > 0 ? 'checked' : '';
                                ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input check-desafio" type="checkbox"
                                        id="chk_<?= $name ?>"
                                        data-name="<?= $name ?>"
                                        data-label="<?= htmlspecialchars($label) ?>"
                                        data-icon="<?= $icon ?>"
                                        <?= $isChecked ?>>
                                    <label class="form-check-label small" for="chk_<?= $name ?>">
                                        <i class="bi <?= $icon ?> text-secondary me-1"></i> <?= $label ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- PASSO 2: CLASSIFICAÇÃO -->
                <div class="mt-4 p-4 border rounded bg-white shadow-sm" id="bloco-classificacao" style="display:none;">
                    <h5 class="text-dark"><i class="bi bi-list-ol me-2"></i> Passo 2: Classifique os selecionados</h5>
                    <p class="small text-muted mb-4">Dê uma nota para cada desafio escolhido. (5 = Maior desafio / 1 = Menor desafio).</p>
                    <div id="lista-ranking" class="row"></div>
                    <div id="hidden-inputs-desafios">
                        <?php foreach ($gruposDesafios as $grupo => $itens): ?>
                            <?php foreach ($itens as $label => [$icon, $name]): ?>
                                <input type="hidden" name="<?= $name ?>" id="input_real_<?= $name ?>" value="<?= (int)($apresentacao[$name] ?? 0) ?>">
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             SEÇÃO 6 — Reconhecimentos e Visibilidade
        ═══════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-trophy"></i> Reconhecimentos e Visibilidade</div>

            <p style="font-size:.85rem;color:#6c8070;margin-bottom:1rem;">
                Compartilhe prêmios, matérias jornalísticas, artigos, eventos, parcerias institucionais ou outros destaques que ajudam a evidenciar sua credibilidade e impacto.
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Texto adicional
                    </label>
                    <textarea name="info_adicionais" class="form-control" rows="5" maxlength="3000"
                              placeholder="Descreva informações adicionais relevantes..."></textarea>
                    <div class="form-text">Máx. 3000 caracteres.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Links externos
                    </label>
                    <div id="links-container">
                        <input type="url" name="info_adicionais_link[]" class="form-control mb-2"
                               placeholder="Cole aqui um link (YouTube, matéria, PDF hospedado)">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addLinkField()">+ Adicionar outro link</button>
                    <div class="form-text">
                        Você pode adicionar vários links externos (vídeos, matérias, PDFs hospedados).<br>
                        ⚠️ Somente links, não é permitido upload de arquivos grandes.
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões -->
        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="/negocios/editar_etapa4.php?id=<?= $negocio_id ?>" class="btn-emp-outline">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
            <button type="submit" class="btn-emp-primary">
                Salvar e Avançar <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </form>
</div>

<script>
function addLinkField() {
    const container = document.getElementById('links-container');
    const input = document.createElement('input');
    input.type = 'url';
    input.name = 'info_adicionais_link[]';
    input.className = 'form-control mb-2';
    input.placeholder = 'Outro link opcional';
    container.appendChild(input);
}
</script>

<script>
// ══════════════════════════════════════════════════
// Validação de uploads — frontend (etapa 8)
// ══════════════════════════════════════════════════

const MB = 1024 * 1024;

const uploadConfig = {
    logo_negocio:      { maxMB: 50, tipos: ['image/png','image/jpeg','image/jpg','image/webp'], maxFiles: 1 },
    imagemDestaque:    { maxMB: 5,  tipos: ['image/png','image/jpeg','image/jpg','image/webp'], maxFiles: 1 },
    apresentacao_pdf:  { maxMB: 5,  tipos: ['application/pdf'],                                 maxFiles: 1 },
    galeria_imagens:   { maxMB: 50, tipos: ['image/png','image/jpeg','image/jpg','image/webp','image/gif','image/bmp'], maxFiles: 10 },
};

function getFeedbackEl(input) {
    let el = input.parentElement.querySelector('.upload-feedback');
    if (!el) {
        el = document.createElement('div');
        el.className = 'upload-feedback mt-1';
        input.parentElement.appendChild(el);
    }
    return el;
}

function setErro(input, msg) {
    const el = getFeedbackEl(input);
    el.innerHTML = `<div class="alert alert-danger py-2 px-3 mb-0 small">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>${msg}
    </div>`;
    input.dataset.valid = 'false';
}

function setOk(input, msg) {
    const el = getFeedbackEl(input);
    el.innerHTML = `<div class="text-success small mt-1">
        <i class="bi bi-check-circle-fill me-1"></i>${msg}
    </div>`;
    input.dataset.valid = 'true';
}

function clearFeedback(input) {
    const el = getFeedbackEl(input);
    if (el) el.innerHTML = '';
    delete input.dataset.valid;
}

function formatBytes(bytes) {
    return (bytes / MB).toFixed(2) + ' MB';
}

function validarInput(input, cfg) {
    const files = Array.from(input.files);

    if (files.length === 0) {
        clearFeedback(input);
        return true;
    }

    if (files.length > cfg.maxFiles) {
        setErro(input, `Máximo de ${cfg.maxFiles} arquivo(s) permitido. Você selecionou ${files.length}.`);
        input.value = '';
        return false;
    }

    const erros = [];
    const validos = [];

    files.forEach(file => {
        const tipoOk    = cfg.tipos.includes(file.type);
        const tamanhoOk = file.size <= cfg.maxMB * MB;

        if (!tipoOk) {
            erros.push(`<strong>${file.name}</strong>: tipo não permitido (${file.type || 'desconhecido'}).`);
        } else if (!tamanhoOk) {
            erros.push(`<strong>${file.name}</strong>: ${formatBytes(file.size)} — limite é ${cfg.maxMB}MB.`);
        } else {
            validos.push(file.name);
        }
    });

    if (erros.length > 0) {
        setErro(input, erros.join('<br>'));
        input.value = '';
        return false;
    }

    const label = validos.length === 1
        ? `${validos[0]} (${formatBytes(files[0].size)})`
        : `${validos.length} arquivo(s) selecionado(s)`;
    setOk(input, label);
    return true;
}

document.addEventListener('DOMContentLoaded', function () {

    // Logo
    const logo = document.getElementById('logo_negocio');
    if (logo) logo.addEventListener('change', () => validarInput(logo, uploadConfig.logo_negocio));

    // Imagem de destaque
    const destaque = document.getElementById('imagemDestaque');
    if (destaque) {
        destaque.addEventListener('change', function () {
            const ok = validarInput(destaque, uploadConfig.imagemDestaque);
            if (!ok) return;

            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = e => {
                // Atualiza o preview
                document.getElementById('novoDestaqueImg').src = e.target.result;
                document.getElementById('novoDestaquePreview').classList.remove('d-none');

                // Mantém o label visível como "Trocar imagem" para permitir nova seleção
                const labelEl = document.getElementById('uploadLabelDestaque');
                labelEl.style.display = '';
                labelEl.innerHTML = `
                    <i class="bi bi-arrow-repeat upload-icon-destaque" style="font-size:2rem;"></i>
                    <span class="upload-text-main">Clique para trocar a imagem</span>
                    <span class="upload-text-sub">JPG, PNG ou WebP · Máx. 5MB · Proporção 16:9 recomendada</span>
                `;
            };
            reader.readAsDataURL(file);
        });

        // Remover capa
        const chkRemover = document.getElementById('removerImagemDestaque');
        if (chkRemover) {
            chkRemover.addEventListener('change', function () {
                const label = document.getElementById('removerDestaqueLabel');
                if (this.checked) {
                    label.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i> Cancelar remoção';
                    document.getElementById('destaque-preview-atual').style.opacity = '0.4';
                } else {
                    label.innerHTML = '<i class="bi bi-trash me-1"></i> Remover capa';
                    document.getElementById('destaque-preview-atual').style.opacity = '1';
                }
            });
        }
    }

    // PDF
    const pdf = document.querySelector('input[name="apresentacao_pdf"]');
    if (pdf) pdf.addEventListener('change', () => validarInput(pdf, uploadConfig.apresentacao_pdf));

    // Galeria
    const galeria = document.querySelector('input[name="galeria_imagens[]"]');
    if (galeria) galeria.addEventListener('change', () => validarInput(galeria, uploadConfig.galeria_imagens));

    // Bloqueia submit se houver erro
    document.querySelector('form').addEventListener('submit', function (e) {
        const inputs = [logo, destaque, pdf, galeria].filter(Boolean);
        let bloqueado = false;
        let primeiroErro = null;

        inputs.forEach(input => {
            // Re-valida no submit caso o usuário não tenha interagido
            if (input.files && input.files.length > 0 && !input.dataset.valid) {
                const cfg = input.id === 'logo_negocio'      ? uploadConfig.logo_negocio     :
                            input.id === 'imagemDestaque'     ? uploadConfig.imagemDestaque    :
                            input.name === 'apresentacao_pdf' ? uploadConfig.apresentacao_pdf  :
                            uploadConfig.galeria_imagens;
                validarInput(input, cfg);
            }

            if (input.dataset.valid === 'false') {
                bloqueado = true;
                if (!primeiroErro) primeiroErro = input;
            }
        });

        if (bloqueado) {
            e.preventDefault();
            if (primeiroErro) primeiroErro.scrollIntoView({ behavior: 'smooth', block: 'center' });

            let aviso = document.getElementById('upload-aviso-geral');
            if (!aviso) {
                aviso = document.createElement('div');
                aviso.id = 'upload-aviso-geral';
                aviso.className = 'alert alert-danger mb-3';
                aviso.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Corrija os erros nos arquivos antes de salvar.</strong>';
                document.querySelector('form').prepend(aviso);
            }
            aviso.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            const aviso = document.getElementById('upload-aviso-geral');
            if (aviso) aviso.remove();
        }
    }, true);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('.inovacao-tipo');
    const blocoDescricao = document.getElementById('bloco-descricao-inovacao');

    function atualizarDescricao() {
        let temAlgumSim = false;
        radios.forEach(r => { if (r.checked && r.value === 'sim') temAlgumSim = true; });
        if (temAlgumSim) {
            blocoDescricao.style.display = 'block';
            blocoDescricao.querySelector('textarea').setAttribute('required', 'required');
        } else {
            blocoDescricao.style.display = 'none';
            const txt = blocoDescricao.querySelector('textarea');
            txt.removeAttribute('required');
            txt.value = '';
        }
    }
    radios.forEach(r => r.addEventListener('change', atualizarDescricao));
    atualizarDescricao();
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const camposTexto = document.querySelectorAll("input[type='text'], textarea");

    function validarTexto(campo) {
        const regex = /[a-zA-ZÀ-ÿ]/g;
        const letras = (campo.value.match(regex) || []).length;
        if (campo.hasAttribute("required") || campo.value.trim() !== "") {
            campo.setCustomValidity(letras < 5 ? "Digite um texto válido (mínimo 5 letras reais)." : "");
        } else {
            campo.setCustomValidity("");
        }
    }

    camposTexto.forEach(campo => {
        campo.addEventListener("input",  () => validarTexto(campo));
        campo.addEventListener("blur",   () => { validarTexto(campo); campo.reportValidity(); });
    });

    document.querySelector("form").addEventListener("submit", function(e) {
        let valido = true;
        camposTexto.forEach(campo => {
            validarTexto(campo);
            if (!campo.checkValidity()) { campo.reportValidity(); valido = false; }
        });
        if (!valido) e.preventDefault();
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const checkboxes = document.querySelectorAll('.check-desafio');
    const blocoClassificacao = document.getElementById('bloco-classificacao');
    const listaRanking = document.getElementById('lista-ranking');

    function atualizarRanking() {
        let selecionados = [];
        checkboxes.forEach(chk => {
            if (chk.checked) {
                selecionados.push({
                    name:  chk.getAttribute('data-name'),
                    label: chk.getAttribute('data-label'),
                    icon:  chk.getAttribute('data-icon')
                });
            }
            if (!chk.checked) {
                document.getElementById('input_real_' + chk.getAttribute('data-name')).value = "0";
            }
        });

        blocoClassificacao.style.display = selecionados.length > 0 ? 'block' : 'none';
        listaRanking.innerHTML = '';

        selecionados.forEach(item => {
            let hiddenInput = document.getElementById('input_real_' + item.name);
            let valorSalvo  = hiddenInput.value > 0 ? hiddenInput.value : '';
            let card = document.createElement('div');
            card.className = "col-12 col-md-6 mb-3";
            card.innerHTML = `
                <div class="d-flex align-items-center justify-content-between p-3 border rounded bg-light h-100">
                    <div class="d-flex align-items-center pe-2">
                        <i class="bi ${item.icon} fs-4 text-primary me-3"></i>
                        <span class="fw-medium small">${item.label}</span>
                    </div>
                    <div class="ms-auto flex-shrink-0" style="width:140px;">
                        <select class="form-select form-select-sm select-nota" data-target="${item.name}" required>
                            <option value="">Selecione...</option>
                            <option value="5" ${valorSalvo=='5'?'selected':''}>5 (Maior)</option>
                            <option value="4" ${valorSalvo=='4'?'selected':''}>4</option>
                            <option value="3" ${valorSalvo=='3'?'selected':''}>3</option>
                            <option value="2" ${valorSalvo=='2'?'selected':''}>2</option>
                            <option value="1" ${valorSalvo=='1'?'selected':''}>1 (Menor)</option>
                        </select>
                    </div>
                </div>`;
            listaRanking.appendChild(card);
        });

        document.querySelectorAll('.select-nota').forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('input_real_' + this.getAttribute('data-target')).value = this.value;
            });
            if (select.value !== "") {
                document.getElementById('input_real_' + select.getAttribute('data-target')).value = select.value;
            }
        });
    }

    checkboxes.forEach(chk => chk.addEventListener('change', atualizarRanking));
    atualizarRanking();
});
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>