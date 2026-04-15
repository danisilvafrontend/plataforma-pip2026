<?php
declare(strict_types=1);
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

$negocio_id = (int)($_GET['id'] ?? 0);
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

if (!$negocio) {
    die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);
}
$stmt = $pdo->prepare("SELECT * FROM negocio_apresentacao WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$apresentacao = $stmt->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container py-4 emp-inner" style="max-width:1200px;">

    <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="emp-page-title mb-1">
                Editar: <?= htmlspecialchars($negocio['nome_fantasia'] ?? '') ?>
            </h1>
            <p class="emp-page-subtitle mb-0">Etapa 5 — Apresentação do Negócio</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <?php if (!empty($negocio['inscricao_completa'])): ?>
                <a href="/negocios/confirmacao.php?id=<?= (int)$negocio_id ?>" class="btn-emp-outline">
                    <i class="bi bi-card-checklist me-1"></i> Voltar à Revisão
                </a>
            <?php endif; ?>

            <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline">
                <i class="bi bi-arrow-left me-1"></i> Meus Negócios
            </a>
        </div>
    </div>

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

    <form action="/negocios/processar_etapa5.php" method="post" enctype="multipart/form-data" id="formEtapa5Edit">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="editar">

        <div class="row g-4">

            <!-- ════════════════════════════════════════════
                 COLUNA PRINCIPAL
            ════════════════════════════════════════════ -->
            <div class="col-12 col-lg-8">

                <!-- SEÇÃO 1 — Identidade Visual -->
                <div class="form-section">
                    <div class="form-section-title"><i class="bi bi-image"></i> Identidade Visual</div>

                    <!-- Logotipo -->
                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-eye-fill lbl-pub me-1"></i> Logotipo atual</label>
                        <?php if (!empty($apresentacao['logo_negocio'])): ?>
                            <div class="mb-2">
                                <img src="<?= htmlspecialchars($apresentacao['logo_negocio']) ?>" alt="Logo" style="max-height:100px; border-radius:8px;">
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="remover_logo" value="1" id="removerLogo">
                                <label class="form-check-label small" for="removerLogo">Remover logotipo atual</label>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small mb-2">Nenhum logotipo enviado</div>
                        <?php endif; ?>
                        <input type="file" id="logo_negocio_edit" name="logo_negocio"
                               class="form-control" accept="image/png,image/jpeg,image/jpg,image/webp">
                        <div class="form-text">⚠️ Máx. 50MB. Formatos aceitos: PNG, JPG, JPEG ou WebP.</div>
                    </div>

                    <!-- Imagem de destaque -->
                    <div>
                        <label class="form-label d-flex align-items-center gap-2">
                            <i class="bi bi-eye-fill lbl-pub me-1"></i> Imagem de Destaque
                            <span class="badge" style="background:#CDDE00;color:#1E3425;font-size:.7rem;">Capa da Vitrine</span>
                        </label>

                        <div class="d-flex align-items-start gap-3 p-3 rounded mb-3" style="background:#f0f4ed;border-left:4px solid #CDDE00;">
                            <i class="bi bi-layout-text-window-reverse fs-4 mt-1" style="color:#1E3425;flex-shrink:0;"></i>
                            <div>
                                <strong class="d-block mb-1" style="color:#1E3425;font-size:.9rem;">Esta será a capa do seu negócio na Vitrine Nacional</strong>
                                <span class="small" style="color:#6c8070;">Proporção recomendada: <strong>16:9</strong> (ex: 1280×720px). Máximo 5MB. Formatos: JPG, PNG ou WebP.</span>
                            </div>
                        </div>

                        <?php if (!empty($apresentacao['imagem_destaque'])): ?>
                        <div class="mb-3 position-relative" id="destaque-preview-atual" style="max-width:460px;">
                            <img src="<?= htmlspecialchars($apresentacao['imagem_destaque']) ?>" alt="Capa atual"
                                 class="img-fluid rounded w-100" style="aspect-ratio:16/9;object-fit:cover;border:2px solid #CDDE00;">
                            <span class="badge bg-success position-absolute top-0 start-0 m-2">
                                <i class="bi bi-check-circle me-1"></i> Capa atual
                            </span>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remover_imagem_destaque" value="1"
                                   id="removerImagemDestaqueEdit"
                                   onchange="
                                       document.getElementById('destaque-preview-atual').style.opacity = this.checked ? '0.35' : '1';
                                       document.getElementById('removerDestaqueEditLabel').textContent = this.checked ? 'Cancelar remoção' : 'Remover imagem de destaque atual';
                                   ">
                            <label class="form-check-label small text-danger" for="removerImagemDestaqueEdit" id="removerDestaqueEditLabel">
                                Remover imagem de destaque atual
                            </label>
                        </div>
                        <?php else: ?>
                        <p class="text-muted small mb-3"><i class="bi bi-exclamation-circle me-1"></i> Nenhuma imagem de destaque enviada ainda.</p>
                        <?php endif; ?>

                        <div id="uploadAreaDestaqueEdit" style="max-width:460px;">
                            <input type="file" name="imagem_destaque" id="imagemDestaqueEdit"
                                   accept="image/png,image/jpeg,image/jpg,image/webp" class="d-none">
                            <label for="imagemDestaqueEdit" id="uploadLabelDestaqueEdit"
                                   class="d-flex flex-column align-items-center justify-content-center p-4 rounded text-center"
                                   style="border:2px dashed #c8d4c0;background:#fafbf9;cursor:pointer;"
                                   onmouseover="this.style.borderColor='#CDDE00'"
                                   onmouseout="this.style.borderColor='#c8d4c0'">
                                <i class="bi bi-cloud-arrow-up-fill fs-2 mb-2" style="color:#1E3425;"></i>
                                <span class="fw-600 d-block" style="color:#1E3425;font-size:.92rem;font-weight:600;">
                                    <?= !empty($apresentacao['imagem_destaque']) ? 'Clique para substituir a imagem de capa' : 'Clique para selecionar a imagem de capa' ?>
                                </span>
                                <span class="small mt-1" style="color:#9aab9d;">JPG, PNG ou WebP · Máx. 5MB · Proporção 16:9 recomendada</span>
                            </label>
                            <div id="novoDestaquePreviewEdit" class="d-none mt-3" style="max-width:460px;">
                                <img id="novoDestaqueImgEdit" src="#" alt="Preview nova capa"
                                     class="img-fluid rounded w-100" style="aspect-ratio:16/9;object-fit:cover;border:2px solid #CDDE00;">
                                <p class="small mt-2" style="color:#5a8a62;">
                                    <i class="bi bi-check-circle me-1"></i> Nova imagem selecionada — será salva ao confirmar
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 2 — Apresentação -->
                <div class="form-section">
                    <div class="form-section-title"><i class="bi bi-chat-quote"></i> Apresentação do Negócio</div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-eye-fill lbl-pub me-1"></i> Frase do negócio</label>
                        <textarea name="frase_negocio" class="form-control" maxlength="120"><?= htmlspecialchars($apresentacao['frase_negocio'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-eye-fill lbl-pub me-1"></i> Qual problema você resolve? (até 200 caracteres)</label>
                            <textarea name="problema_resolvido" class="form-control" maxlength="200" rows="4" required><?= htmlspecialchars($apresentacao['problema_resolvido'] ?? '') ?></textarea>
                            <div class="form-text">Descreva a dor ou desafio do seu público-alvo.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-eye-fill lbl-pub me-1"></i> Qual solução você oferece? (até 200 caracteres)</label>
                            <textarea name="solucao_oferecida" class="form-control" maxlength="200" rows="4" required><?= htmlspecialchars($apresentacao['solucao_oferecida'] ?? '') ?></textarea>
                            <div class="form-text">Descreva como seu produto/serviço resolve o problema acima.</div>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 3 — Materiais e Mídia -->
                <div class="form-section">
                    <div class="form-section-title"><i class="bi bi-play-circle"></i> Materiais e Mídia</div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-eye-fill lbl-pub me-1"></i> Vídeo Pitch (YouTube)</label>
                        <input type="url" name="video_pitch_url" class="form-control"
                               value="<?= htmlspecialchars($apresentacao['video_pitch_url'] ?? '') ?>">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-eye-fill lbl-pub me-1"></i> Apresentação Institucional PDF</label>
                            <?php if (!empty($apresentacao['apresentacao_pdf'])): ?>
                                <div class="mb-1"><a href="<?= htmlspecialchars($apresentacao['apresentacao_pdf']) ?>" target="_blank" class="small">Ver PDF atual</a></div>
                            <?php else: ?>
                                <div class="text-muted small mb-1">Nenhum PDF enviado</div>
                            <?php endif; ?>
                            <input type="file" name="apresentacao_pdf" class="form-control" accept=".pdf">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-eye-fill lbl-pub me-1"></i> Vídeo Institucional (YouTube)</label>
                            <input type="url" name="apresentacao_video_url" class="form-control"
                                   value="<?= htmlspecialchars($apresentacao['apresentacao_video_url'] ?? '') ?>">
                            <div class="form-text">Somente vídeos do YouTube são aceitos.</div>
                        </div>
                    </div>

                    <!-- Galeria -->
                    <div>
                        <label class="form-label"><i class="bi bi-eye-fill lbl-pub me-1"></i> Galeria de imagens</label>
                        <?php
                        $galeria = json_decode($apresentacao['galeria_imagens'] ?? '[]', true);
                        if (!empty($galeria)): ?>
                        <div class="row g-2 mb-3">
                            <?php foreach ($galeria as $index => $img): ?>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center p-2">
                                        <img src="<?= htmlspecialchars($img) ?>" alt="Imagem" style="max-height:100px;" class="img-fluid rounded mb-2">
                                        <div class="form-check text-start">
                                            <input class="form-check-input" type="checkbox" name="remover_imagem[]" value="<?= $index ?>" id="remover<?= $index ?>">
                                            <label class="form-check-label small" for="remover<?= $index ?>">Remover</label>
                                        </div>
                                        <input type="file" name="substituir_imagem[<?= $index ?>]" class="form-control form-control-sm mt-1" accept="image/*">
                                        <div class="form-text">Substituir esta imagem</div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <div class="text-muted small mb-2">Nenhuma imagem enviada</div>
                        <?php endif; ?>
                        <div class="mt-2">
                            <label class="form-label small fw-semibold">Adicionar novas imagens (máx. 10 no total)</label>
                            <input type="file" name="galeria_imagens[]" class="form-control" accept="image/*" multiple>
                            <div class="form-text">Você pode adicionar novas imagens, mas o total não pode ultrapassar 10 por galeria.</div>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 4 — Inovação -->
                <div class="form-section">
                    <div class="form-section-title"><i class="bi bi-lightbulb"></i> Inovação e Modelo de Atuação</div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-eye-fill lbl-pub me-1"></i> Seu negócio incorpora inovação? Marque onde houver inovação real.
                        </label>
                        <div class="row row-cols-1 row-cols-md-2 g-3 mb-3">
                            <?php
                            $inovacoesEdit = [
                                'inovacao_tecnologica'  => ['1. Inovação Tecnológica',       'Uso de IA, Big Data, IoT, blockchain, plataformas digitais, biotecnologia ou tecnologias verdes.'],
                                'inovacao_produto'      => ['2. Inovação de Produto',         'Novo produto sustentável, materiais ecológicos, soluções regenerativas ou tecnologias de saúde/educação.'],
                                'inovacao_servico'      => ['3. Inovação de Serviço',         'Telemedicina, educação online inclusiva, plataformas de acesso a crédito ou serviços financeiros acessíveis.'],
                                'inovacao_modelo'       => ['4. Modelo de Negócio',           'Marketplace de impacto, economia compartilhada, assinaturas, pay-per-use, B2G.'],
                                'inovacao_social'       => ['5. Inovação Social',             'Inclusão produtiva, empoderamento, geração de renda, educação transformadora, participação cidadã.'],
                                'inovacao_ambiental'    => ['6. Inovação Ambiental',          'Economia circular, redução de emissões, agricultura regenerativa, energia renovável, gestão de resíduos.'],
                                'inovacao_cadeia_valor' => ['7. Cadeia de Valor',             'Cadeias produtivas inclusivas, comércio justo, logística sustentável, produção descentralizada.'],
                                'inovacao_governanca'   => ['8. Governança',                  'Governança participativa, cooperativismo, propriedade compartilhada, gestão horizontal.'],
                                'inovacao_impacto'      => ['9. Inovação em Impacto',         'Novas métricas de impacto, modelos escaláveis, tecnologia para monitoramento socioambiental.'],
                                'inovacao_financiamento'=> ['10. Financiamento',              'Blended finance, crowdfunding, finanças regenerativas, fundos comunitários, impact investing.'],
                            ];
                            foreach ($inovacoesEdit as $name => [$titulo, $desc]): ?>
                            <div class="col">
                                <div class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?= $titulo ?></strong>
                                            <div class="small text-muted lh-sm mt-1"><?= $desc ?></div>
                                        </div>
                                        <div class="ms-3 flex-shrink-0">
                                            <div class="form-check form-check-inline me-1">
                                                <input class="form-check-input inovacao-tipo" type="radio" name="<?= $name ?>" value="sim"
                                                       <?= !empty($apresentacao[$name]) ? 'checked' : '' ?>>
                                                <label class="form-check-label small">Sim</label>
                                            </div>
                                            <div class="form-check form-check-inline me-0">
                                                <input class="form-check-input inovacao-tipo" type="radio" name="<?= $name ?>" value="nao"
                                                       <?= empty($apresentacao[$name]) ? 'checked' : '' ?>>
                                                <label class="form-check-label small">Não</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div id="bloco-descricao-inovacao" class="mb-4 p-3 border rounded" style="background:rgba(205,222,0,.05);display:none;">
                        <label class="form-label">
                            <i class="bi bi-eye-fill lbl-pub me-1"></i> Descreva brevemente as inovações marcadas acima
                        </label>
                        <textarea name="descricao_inovacao" id="descricao_inovacao" class="form-control" rows="3" maxlength="300"
                                  placeholder="Detalhe como a inovação é aplicada no seu negócio..."><?= htmlspecialchars($apresentacao['descricao_inovacao'] ?? '') ?></textarea>
                        <div class="form-text">Foque no que é realmente novo ou estrutural no seu negócio (máx. 300 caracteres).</div>
                    </div>

                    <!-- Tipo solução / Modelo / Colaboradores -->
                    <div class="row">
                        <div class="col-md-12 mb-2">
                            <div class="card h-100"><div class="card-body">
                                <h5 class="card-title"><i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Tipo de solução</h5>
                                <?php $tipo = $apresentacao['tipo_solucao'] ?? ''; ?>
                                <?php foreach (['Produto', 'Serviço', 'Produto e Serviço'] as $opt): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_solucao" value="<?= $opt ?>"
                                           <?= ($tipo === $opt) ? 'checked' : '' ?> <?= $opt === 'Produto' ? 'required' : '' ?>>
                                    <label class="form-check-label"><?= $opt ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div></div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <div class="card h-100"><div class="card-body">
                                <h5 class="card-title"><i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Modelo de negócio</h5>
                                <?php $modelo = $apresentacao['modelo_negocio'] ?? ''; ?>
                                <?php foreach (['B2B' => 'B2B – Empresa para Empresa', 'B2C' => 'B2C – Empresa para Consumidor', 'C2C' => 'C2C – Consumidor para Consumidor', 'B2B2C' => 'B2B2C – Emp. para Emp. para Consumidor', 'B2G' => 'B2G – Empresa para Governo', 'B2N' => 'B2N – Empresa para Ongs/Fundações'] as $val => $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modelo_negocio" value="<?= $val ?>"
                                           <?= ($modelo === $val) ? 'checked' : '' ?> <?= $val === 'B2B' ? 'required' : '' ?>>
                                    <label class="form-check-label"><?= $label ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div></div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <div class="card h-100"><div class="card-body">
                                <h5 class="card-title"><i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Colaboradores</h5>
                                <?php $colab = $apresentacao['colaboradores'] ?? ''; ?>
                                <?php foreach (['Até 5', '6–20', '21–50', '51 ou mais'] as $opt): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="colaboradores" value="<?= $opt ?>"
                                           <?= ($colab === $opt) ? 'checked' : '' ?> <?= $opt === 'Até 5' ? 'required' : '' ?>>
                                    <label class="form-check-label"><?= $opt ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div></div>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 5 — Histórico e Desafios -->
                <div class="form-section">
                    <div class="form-section-title"><i class="bi bi-journal-text"></i> Histórico e Desafios do Negócio</div>

                    <div class="mb-4">
                        <label class="form-label"><i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Seu negócio já teve apoio de uma aceleradora ou programa de fomento?</label>
                        <?php $apoio = $apresentacao['apoio'] ?? ''; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="apoio" value="nao" <?= ($apoio === 'nao') ? 'checked' : '' ?> required>
                            <label class="form-check-label">Não</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="apoio" value="sim" <?= ($apoio === 'sim') ? 'checked' : '' ?>>
                            <label class="form-check-label">Sim. Quais?</label>
                        </div>
                        <input type="text" name="programas" class="form-control mt-2" maxlength="120"
                               value="<?= htmlspecialchars($apresentacao['programas'] ?? '') ?>">
                        <div class="form-text">Até 120 caracteres</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Quais são hoje os principais desafios para o desenvolvimento do seu negócio?
                        </label>
                        <p class="text-muted" style="font-size:.85rem;">
                            <strong>Passo 1:</strong> Selecione os desafios que limitam o crescimento do seu negócio.<br>
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
                        ]; ?>

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

                <!-- SEÇÃO 6 — Reconhecimentos -->
                <div class="form-section">
                    <div class="form-section-title"><i class="bi bi-trophy"></i> Reconhecimentos e Visibilidade</div>
                    <p style="font-size:.85rem;color:#6c8070;margin-bottom:1rem;">
                        Compartilhe prêmios, matérias jornalísticas, artigos, eventos, parcerias institucionais ou outros destaques que ajudam a evidenciar sua credibilidade e impacto.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-12 mb-2">
                            <label class="form-label"><i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Texto adicional</label>
                            <textarea name="info_adicionais" class="form-control" rows="5" maxlength="3000"><?= htmlspecialchars($apresentacao['info_adicionais'] ?? '') ?></textarea>
                            <div class="form-text">Máx. 3000 caracteres.</div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label"><i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Links externos</label>
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

                <!-- Botão inferior -->
                <div class="d-flex justify-content-end gap-2 mt-2 mb-5">
                    <a href="/negocios/editar_etapa4.php?id=<?= $negocio_id ?>" class="btn-emp-outline">
                        <i class="bi bi-arrow-left"></i> Etapa anterior
                    </a>
                    <button type="submit" class="btn-emp-primary">
                        <i class="bi bi-floppy me-1"></i> Salvar alterações
                    </button>
                </div>

            </div><!-- /col-lg-8 -->

            <!-- ════════════════════════════════════════════
                 COLUNA LATERAL
            ════════════════════════════════════════════ -->
            <div class="col-12 col-lg-4">

                <!-- Card: Legenda -->
                <div class="emp-card mb-3">
                    <div class="emp-card-header"><i class="bi bi-info-circle"></i> Legenda</div>
                    <div class="d-flex align-items-center gap-2 mb-2 small">
                        <i class="bi bi-eye-slash-fill lbl-priv"></i>
                        <span style="color:#4a5e4f;">Campo <strong>privado</strong> — somente interno</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 small">
                        <i class="bi bi-eye-fill lbl-pub"></i>
                        <span style="color:#4a5e4f;">Campo <strong>público</strong> — visível na vitrine</span>
                    </div>
                </div>

                <!-- Card: Orientações -->
                <div class="emp-card mb-3">
                    <div class="emp-card-header"><i class="bi bi-lightbulb"></i> Orientações</div>
                    <ul class="mb-0 ps-3" style="font-size:.82rem;color:#6c8070;line-height:1.7;">
                        <li>A frase do negócio e as imagens serão exibidas na <strong>vitrine pública</strong>.</li>
                        <li>Dados de inovação, modelo e desafios são <strong>internos</strong> e usados pela equipe avaliadora.</li>
                        <li>O vídeo pitch é obrigatório para a inscrição ser considerada completa.</li>
                        <li>Envie fotos de boa qualidade — elas impactam diretamente na avaliação visual.</li>
                    </ul>
                </div>

                <!-- Card: Salvar -->
                <div class="emp-card">
                    <div class="emp-card-header"><i class="bi bi-floppy-fill"></i> Salvar</div>
                    <p class="small mb-3" style="color:#9aab9d;">
                        Salve as alterações desta etapa. Os demais dados do negócio não serão afetados.
                    </p>
                    <button type="submit" class="btn-emp-primary w-100 justify-content-center mb-2">
                        <i class="bi bi-floppy me-2"></i> Salvar Alterações
                    </button>
                    <a href="/negocios/confirmacao.php?id=<?= $negocio_id ?>"
                       class="btn-emp-outline w-100 justify-content-center mb-2">
                        <i class="bi bi-card-checklist me-2"></i> Voltar à Revisão
                    </a>
                    <a href="/empreendedores/meus-negocios.php"
                       class="btn-emp-outline w-100 justify-content-center">
                        <i class="bi bi-arrow-left me-2"></i> Meus Negócios
                    </a>
                </div>

            </div><!-- /col-lg-4 -->
        </div><!-- /row -->
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
</script>

<script>
document.getElementById('logo_negocio_edit').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const validTypes = ['image/png','image/jpeg','image/jpg','image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Arquivo inválido! Apenas PNG, JPG, JPEG ou WebP são aceitos.');
            this.value = '';
        }
    }
});

const inputDestaqueEdit = document.getElementById('imagemDestaqueEdit');
if (inputDestaqueEdit) {
    inputDestaqueEdit.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const tipos = ['image/png','image/jpeg','image/jpg','image/webp'];
        if (!tipos.includes(file.type)) { alert('Arquivo inválido! Apenas PNG, JPG, JPEG ou WebP são aceitos.'); this.value = ''; return; }
        if (file.size > 5 * 1024 * 1024) { alert('A imagem de destaque deve ter no máximo 5MB.'); this.value = ''; return; }
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('novoDestaqueImgEdit').src = e.target.result;
            document.getElementById('novoDestaquePreviewEdit').classList.remove('d-none');
            document.getElementById('uploadLabelDestaqueEdit').style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
}
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const radiosInovacao = document.querySelectorAll('.inovacao-tipo');
    const blocoDescricao  = document.getElementById('bloco-descricao-inovacao');
    const campoDescricao  = document.getElementById('descricao_inovacao');

    function checarInovacoes() {
        let temSim = false;
        radiosInovacao.forEach(r => { if (r.checked && r.value === 'sim') temSim = true; });
        if (temSim) {
            blocoDescricao.style.display = 'block';
            campoDescricao.setAttribute('required', 'required');
        } else {
            blocoDescricao.style.display = 'none';
            campoDescricao.removeAttribute('required');
            campoDescricao.value = '';
        }
    }
    radiosInovacao.forEach(r => r.addEventListener('change', checarInovacoes));
    checarInovacoes();
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const camposTexto = document.querySelectorAll("input[type='text'], textarea");

    function validarTexto(campo) {
        const letras = (campo.value.match(/[a-zA-ZÀ-ÿ]/g) || []).length;
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
    const checkboxes       = document.querySelectorAll('.check-desafio');
    const blocoClassificacao = document.getElementById('bloco-classificacao');
    const listaRanking     = document.getElementById('lista-ranking');

    function atualizarRanking() {
        let selecionados = [];
        checkboxes.forEach(chk => {
            if (chk.checked) {
                selecionados.push({ name: chk.getAttribute('data-name'), label: chk.getAttribute('data-label'), icon: chk.getAttribute('data-icon') });
            } else {
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