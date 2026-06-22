<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
$pageTitle = 'Etapa 5 — Apresentação do Negócio';
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

// ✅ CORRIGIDO: busca dados salvos para repopulação
$stmt = $pdo->prepare("SELECT * FROM negocio_apresentacao WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$apresentacao = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$galeriaExistente = json_decode($apresentacao['galeria_imagens'] ?? '[]', true);

$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container my-5 emp-inner">

    <?php
        $etapaAtual = 5;
        include __DIR__ . '/../app/views/partials/progress.php';
        include __DIR__ . '/../app/views/partials/intro_text_apresentacao_negocios.php';
    ?>

    <?php if (!empty($_SESSION['errors_etapa5'])): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-circle-fill mt-1"></i>
            <ul class="mb-0 ps-2">
                <?php foreach ($_SESSION['errors_etapa5'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors_etapa5']); ?>
    <?php endif; ?>

    <form action="/negocios/processar_etapa5.php" method="post" enctype="multipart/form-data">
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
                <?php if (!empty($apresentacao['logo_negocio'])): ?>
                    <div class="mb-2">
                        <img src="<?= htmlspecialchars($apresentacao['logo_negocio']) ?>" alt="Logo atual" style="max-height:80px;" class="border rounded p-1">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="remover_logo" value="1" id="removerLogo">
                            <label class="form-check-label small text-danger" for="removerLogo">Remover logo atual</label>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" name="logo_negocio" id="logo_negocio" class="form-control"
                    accept="image/png,image/jpeg,image/jpg,image/webp"
                    <?= empty($apresentacao['logo_negocio']) ? 'required' : '' ?>>
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
                    <small class="text-muted">(opcional)</small>
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
                <input type="text" name="frase_negocio" class="form-control" maxlength="120" required
                    value="<?= htmlspecialchars($apresentacao['frase_negocio'] ?? '') ?>">
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
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Vídeo Pitch — até 3 minutos (YouTube) <small>(opcional)</small>
                </label>
                <input type="url" name="video_pitch_url" class="form-control"
                    placeholder="Cole aqui a URL do YouTube"
                    pattern="^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[A-Za-z0-9_-]{11}$"
                    value="<?= htmlspecialchars($apresentacao['video_pitch_url'] ?? '') ?>"
                    >
                <div class="form-text">
                    Exemplo válido: https://www.youtube.com/watch?v=XXXXXXXXXXX<br>
                    Esse vídeo será sua apresentação na vitrine de negócios e para a premiação.
                </div>
            </div>

            <!-- PDF + Vídeo Institucional -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-fill lbl-pub me-1"></i> Apresentação institucional (PDF) <small class="text-muted">(opcional)</small>
                    </label>
                    <?php if (!empty($apresentacao['apresentacao_pdf'])): ?>
                        <div class="mb-2">
                            <a href="<?= htmlspecialchars($apresentacao['apresentacao_pdf']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Ver PDF atual
                            </a>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="remover_pdf" value="1" id="removerPdf">
                                <label class="form-check-label small text-danger" for="removerPdf">Remover PDF atual</label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="apresentacao_pdf" class="form-control" accept=".pdf">
                    <div class="form-text">
                        Upload de material explicativo sobre sua solução, trajetória e impacto.<br>
                        ⚠️ Máx. 5MB.
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-fill lbl-pub me-1"></i> Vídeo institucional (YouTube) <small class="text-muted">(opcional)</small>
                    </label>
                    <input type="url" name="apresentacao_video_url" class="form-control"
                        placeholder="Cole aqui a URL do YouTube"
                        pattern="^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[A-Za-z0-9_-]{11}$"
                        value="<?= htmlspecialchars($apresentacao['apresentacao_video_url'] ?? '') ?>">
                    <div class="form-text">Somente vídeos do YouTube são aceitos.</div>
                </div>
            </div>

            <!-- Galeria -->
            <div>
                <label class="form-label">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Galeria de imagens do negócio <small class="text-muted">(opcional)</small>
                </label>
                <?php if (!empty($galeriaExistente)): ?>
                    <div class="row g-2 mb-3">
                        <?php foreach ($galeriaExistente as $idx => $imgUrl): ?>
                        <div class="col-6 col-md-3 text-center">
                            <img src="<?= htmlspecialchars($imgUrl) ?>" class="img-thumbnail mb-1" style="max-height:100px;">
                            <div class="form-check d-flex justify-content-center">
                                <input class="form-check-input me-1" type="checkbox" name="remover_imagem[]" value="<?= $idx ?>">
                                <label class="form-check-label small text-danger">Remover</label>
                            </div>
                            <input type="file" name="substituir_imagem[<?= $idx ?>]" class="form-control form-control-sm mt-1" accept="image/*">
                            <div class="form-text">Substituir</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <input type="file" name="galeria_imagens[]" class="form-control" accept="image/*" multiple>
                <div class="form-text">Você pode adicionar até 10 imagens no total.</div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             SEÇÃO 4 — Inovação
        ═══════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-lightbulb"></i> Inovação e Modelo de Atuação</div>

            <div class="mb-3">
                <label class="form-label fw-bold">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Seu negócio incorpora inovação? <small class="text-muted">(opcional)</small>
                </label>
                <?php
                $inovacoes = [
                    'inovacao_tecnologica'   => ['1. Inovação Tecnológica',       'Uso de IA, Big Data, IoT, blockchain, plataformas digitais, biotecnologia ou tecnologias verdes.'],
                    'inovacao_produto'       => ['2. Inovação de Produto',         'Novo produto sustentável, materiais ecológicos, soluções regenerativas ou tecnologias de saúde/educação.'],
                    'inovacao_servico'       => ['3. Inovação de Serviço',         'Telemedicina, educação online inclusiva, plataformas de acesso a crédito ou serviços financeiros acessíveis.'],
                    'inovacao_modelo'        => ['4. Modelo de Negócio',           'Marketplace de impacto, economia compartilhada, assinaturas, pay-per-use, B2G.'],
                    'inovacao_social'        => ['5. Inovação Social',             'Inclusão produtiva, empoderamento, geração de renda, educação transformadora, participação cidadã.'],
                    'inovacao_ambiental'     => ['6. Inovação Ambiental',          'Economia circular, redução de emissões, agricultura regenerativa, energia renovável, gestão de resíduos.'],
                    'inovacao_cadeia_valor'  => ['7. Cadeia de Valor',             'Cadeias produtivas inclusivas, comércio justo, logística sustentável, produção descentralizada.'],
                    'inovacao_governanca'    => ['8. Governança',                  'Governança participativa, cooperativismo, propriedade compartilhada, gestão horizontal.'],
                    'inovacao_impacto'       => ['9. Inovação em Impacto',         'Novas métricas de impacto, modelos escaláveis, tecnologia para monitoramento socioambiental.'],
                    'inovacao_financiamento' => ['10. Financiamento',              'Blended finance, crowdfunding, finanças regenerativas, fundos comunitários, impact investing.'],
                ];
                foreach ($inovacoes as $name => [$titulo, $desc]):
                    $valSalvo = (int)($apresentacao[$name] ?? 0);
                ?>
                <div class="form-check mb-2">
                    <input class="form-check-input inovacao-tipo" type="checkbox"
                           name="<?= $name ?>" value="sim" id="chk_<?= $name ?>"
                           <?= $valSalvo == 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="chk_<?= $name ?>">
                        <strong><?= $titulo ?></strong> <span class="text-muted small">— <?= $desc ?></span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>

            <div id="bloco-descricao-inovacao" class="mb-3" style="display:none;">
                <label class="form-label">
                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Descreva brevemente as inovações marcadas *
                </label>
                <textarea name="descricao_inovacao" class="form-control" rows="3" maxlength="300"
                    placeholder="Detalhe como a inovação é aplicada no seu negócio..."><?= htmlspecialchars($apresentacao['descricao_inovacao'] ?? '') ?></textarea>
                <div class="form-text">Máx. 300 caracteres.</div>
            </div>

            <!-- Tipo solução / Modelo / Colaboradores -->
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <div class="card h-100"><div class="card-body">
                        <h6 class="card-title"><i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Tipo de solução *</h6>
                        <?php foreach (['Produto', 'Serviço', 'Produto e Serviço'] as $opt): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_solucao" value="<?= $opt ?>"
                                   <?= ($apresentacao['tipo_solucao'] ?? '') === $opt ? 'checked' : '' ?> required>
                            <label class="form-check-label"><?= $opt ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div></div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100"><div class="card-body">
                        <h6 class="card-title"><i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Modelo de negócio *</h6>
                        <?php foreach (['B2B' => 'B2B – Empresa para Empresa', 'B2C' => 'B2C – Empresa para Consumidor', 'C2C' => 'C2C – Consumidor para Consumidor', 'B2B2C' => 'B2B2C – Emp. para Emp. para Consumidor', 'B2G' => 'B2G – Empresa para Governo', 'B2N' => 'B2N – Empresa para Ongs/Fundações'] as $val => $label): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="<?= $val ?>"
                                   <?= ($apresentacao['modelo_negocio'] ?? '') === $val ? 'checked' : '' ?> required>
                            <label class="form-check-label"><?= $label ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div></div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100"><div class="card-body">
                        <h6 class="card-title"><i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Colaboradores *</h6>
                        <?php foreach (['Até 5', '6–20', '21–50', '51 ou mais'] as $opt): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="colaboradores" value="<?= $opt ?>"
                                   <?= ($apresentacao['colaboradores'] ?? '') === $opt ? 'checked' : '' ?> required>
                            <label class="form-check-label"><?= $opt ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div></div>
                </div>
            </div>

            <!-- ✅ NOVO: Replicabilidade -->
            <div class="form-group mb-3">
                <label class="form-label fw-semibold">
                    <i class="bi bi-eye-slash-fill lbl-priv me-1"></i>
                    Como você descreveria a replicabilidade do seu modelo de negócio?
                    <span class="text-danger">*</span>
                </label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="replicabilidade"
                           id="rep_digital" value="digital_escalavel" required
                           <?= ($apresentacao['replicabilidade'] ?? '') === 'digital_escalavel' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="rep_digital">
                        O modelo é totalmente digital e pode ser replicado sem adaptações significativas
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="replicabilidade"
                           id="rep_baixa" value="replicavel_baixa_adaptacao"
                           <?= ($apresentacao['replicabilidade'] ?? '') === 'replicavel_baixa_adaptacao' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="rep_baixa">
                        Pode ser replicado com pequenas adaptações locais
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="replicabilidade"
                           id="rep_alta" value="replicavel_alta_adaptacao"
                           <?= ($apresentacao['replicabilidade'] ?? '') === 'replicavel_alta_adaptacao' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="rep_alta">
                        Pode ser replicado, mas exige adaptações relevantes para cada contexto
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="replicabilidade"
                           id="rep_dificil" value="dificil_replicacao"
                           <?= ($apresentacao['replicabilidade'] ?? '') === 'dificil_replicacao' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="rep_dificil">
                        O modelo é muito específico e dificilmente replicável em outros contextos
                    </label>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════
             SEÇÃO 5 — Histórico e Desafios
        ═══════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-journal-text"></i> Histórico e Desafios do Negócio</div>

            <div class="mb-4">
                <label class="form-label">
                    <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Seu negócio já teve apoio de uma aceleradora ou programa de fomento? *
                </label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="apoio" value="nao" required
                           <?= ($apresentacao['apoio'] ?? 'nao') === 'nao' ? 'checked' : '' ?>>
                    <label class="form-check-label">Não</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="apoio" value="sim"
                           <?= ($apresentacao['apoio'] ?? '') === 'sim' ? 'checked' : '' ?>>
                    <label class="form-check-label">Sim. Quais?</label>
                </div>
                <input type="text" name="programas" class="form-control mt-2" maxlength="120"
                       value="<?= htmlspecialchars($apresentacao['programas'] ?? '') ?>">
                <div class="form-text">Até 120 caracteres <small class="text-muted">(opcional, preencher somente se "Sim")</small></div>
            </div>

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
            ]; ?>

            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Quais são hoje os principais desafios? * <small class="text-muted">(selecione pelo menos 1)</small>
                </label>
                <p class="text-muted" style="font-size:.85rem;">
                    <strong>Passo 1:</strong> Selecione os desafios que limitam o crescimento do seu negócio.<br>
                    <strong>Passo 2:</strong> Logo abaixo, classifique os itens selecionados.
                </p>

                <div class="row g-3">
                    <?php foreach ($gruposDesafios as $grupo => $itens): ?>
                    <div class="col-md-6">
                        <div class="card h-100 shadow-sm border-0 bg-light">
                            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                                <h6 class="mb-0 text-primary fw-bold"><?= $grupo ?></h6>
                            </div>
                            <div class="card-body pt-2">
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

                <div class="mt-4 p-4 border rounded bg-white shadow-sm" id="bloco-classificacao" style="display:none;">
                    <h5 class="text-dark"><i class="bi bi-list-ol me-2" style="color:#97A327;"></i> Passo 2: Classifique os selecionados</h5>
                    <p class="small text-muted mb-4">Dê uma nota para cada desafio escolhido. (<strong>5 = Maior desafio</strong> / <strong>1 = Menor desafio</strong>).</p>
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
             SEÇÃO 6 — Reconhecimentos
        ═══════════════════════════════════════════════════ -->
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-trophy"></i> Reconhecimentos e Visibilidade</div>
            <p style="font-size:.85rem;color:#6c8070;margin-bottom:1rem;">
                Compartilhe prêmios, matérias jornalísticas, artigos, eventos, parcerias institucionais ou outros destaques.
            </p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Texto adicional <small class="text-muted">(opcional)</small>
                    </label>
                    <textarea name="info_adicionais" class="form-control" rows="5" maxlength="3000"><?= htmlspecialchars($apresentacao['info_adicionais'] ?? '') ?></textarea>
                    <div class="form-text">Máx. 3000 caracteres.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Links externos <small class="text-muted">(opcional)</small>
                    </label>
                    <?php
                    $links = json_decode($apresentacao['info_adicionais_links'] ?? '[]', true);
                    if (!empty($links)):
                        foreach ($links as $link): ?>
                            <input type="url" name="info_adicionais_link[]" class="form-control mb-2" value="<?= htmlspecialchars($link) ?>">
                        <?php endforeach;
                    else: ?>
                        <input type="url" name="info_adicionais_link[]" class="form-control mb-2" placeholder="Cole aqui um link (YouTube, matéria, PDF hospedado)">
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addLinkField()">+ Adicionar outro link</button>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-between mt-4">
            <a href="/negocios/etapa4_ods.php?id=<?= $negocio_id ?>" class="btn-emp-outline">
                <i class="bi bi-arrow-left me-2"></i> Etapa Anterior
            </a>
            <button type="submit" class="btn-emp-primary">
                Avançar <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </form>
</div>

<script>
function addLinkField() {
    const container = document.querySelector('[name="info_adicionais_link[]"]').parentNode;
    const input = document.createElement('input');
    input.type = 'url';
    input.name = 'info_adicionais_link[]';
    input.className = 'form-control mb-2';
    input.placeholder = 'Outro link opcional';
    container.appendChild(input);
}

document.addEventListener("DOMContentLoaded", function() {
    const checkboxes         = document.querySelectorAll('.check-desafio');
    const blocoClassificacao = document.getElementById('bloco-classificacao');
    const listaRanking       = document.getElementById('lista-ranking');

    function atualizarRanking() {
        let selecionados = [];
        checkboxes.forEach(chk => {
            if (chk.checked) selecionados.push({ name: chk.dataset.name, label: chk.dataset.label, icon: chk.dataset.icon });
            else document.getElementById('input_real_' + chk.dataset.name).value = "0";
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
                    <div class="d-flex align-items-center pe-3">
                        <i class="bi ${item.icon} fs-4 text-primary me-3"></i>
                        <span class="fw-medium small lh-sm">${item.label}</span>
                    </div>
                    <div class="ms-auto flex-shrink-0" style="width:130px;">
                        <select class="form-select form-select-sm select-nota border-primary shadow-sm" data-target="${item.name}" required>
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
                document.getElementById('input_real_' + this.dataset.target).value = this.value;
            });
            if (select.value !== "") document.getElementById('input_real_' + select.dataset.target).value = select.value;
        });
    }

    checkboxes.forEach(chk => chk.addEventListener('change', atualizarRanking));
    atualizarRanking();
});
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>
