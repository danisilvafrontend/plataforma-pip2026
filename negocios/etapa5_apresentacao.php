<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: /negocios/meus-negocios.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$user_id = $_SESSION['user_id'];
$negocio_id = (int) $_GET['id'];

$pdo = getPDO();

// Valida se o negócio pertence ao usuário
$stmt = $pdo->prepare("SELECT * FROM negocios WHERE id = :id AND user_id = :user_id LIMIT 1");
$stmt->execute([
    'id' => $negocio_id,
    'user_id' => $user_id
]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$negocio) {
    die("Negócio não encontrado ou acesso negado.");
}

// Busca dados da apresentação
$stmt = $pdo->prepare("SELECT * FROM negocio_apresentacao WHERE negocio_id = :id LIMIT 1");
$stmt->execute(['id' => $negocio_id]);
$apresentacao = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$desafiosSelecionados = [];
if (!empty($apresentacao['desafios'])) {
    $decoded = json_decode($apresentacao['desafios'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $desafiosSelecionados = $decoded;
    }
}

$programasLista = [];
if (!empty($apresentacao['programas'])) {
    $decoded = json_decode($apresentacao['programas'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $programasLista = $decoded;
    }
}

$linksAdicionais = [];
if (!empty($apresentacao['info_adicionais_links'])) {
    $decoded = json_decode($apresentacao['info_adicionais_links'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $linksAdicionais = $decoded;
    }
}

$galeria = [];
if (!empty($apresentacao['galeria_imagens'])) {
    $decoded = json_decode($apresentacao['galeria_imagens'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $galeria = $decoded;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Etapa 5 — Apresentação do Negócio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/negocios.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">

                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h1 class="h3 mb-1">Etapa 5 — Apresentação do Negócio</h1>
                            <p class="text-muted mb-0">Conte sua história, diferencial, modelo e desafios.</p>
                        </div>
                        <a href="/negocios/meus-negocios.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                    </div>

                    <?php if (!empty($_SESSION['errors_etapa5'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($_SESSION['errors_etapa5'] as $erro): ?>
                                    <li><?= htmlspecialchars($erro) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['errors_etapa5']); ?>
                    <?php endif; ?>

                    <form action="/negocios/processar_etapa5.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">

                        <div class="form-section mb-4">
                            <div class="form-section-title"><i class="bi bi-megaphone"></i> Identidade e Mensagem</div>

                            <div class="mb-4">
                                <label for="frase_negocio" class="form-label">
                                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Frase de apresentação do negócio *
                                </label>
                                <input type="text" class="form-control" id="frase_negocio" name="frase_negocio" maxlength="160"
                                       value="<?= htmlspecialchars($apresentacao['frase_negocio'] ?? '') ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="problema_resolvido" class="form-label">
                                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Qual problema seu negócio resolve? *
                                </label>
                                <textarea class="form-control" id="problema_resolvido" name="problema_resolvido" rows="4" maxlength="1000" required><?= htmlspecialchars($apresentacao['problema_resolvido'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label for="solucao_oferecida" class="form-label">
                                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Como sua solução funciona? *
                                </label>
                                <textarea class="form-control" id="solucao_oferecida" name="solucao_oferecida" rows="4" maxlength="1000" required><?= htmlspecialchars($apresentacao['solucao_oferecida'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="form-section mb-4">
                            <div class="form-section-title"><i class="bi bi-images"></i> Materiais do Negócio</div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="logo_negocio" class="form-label">
                                        <i class="bi bi-eye-fill lbl-pub me-1"></i> Logo do negócio
                                    </label>
                                    <input type="file" class="form-control" id="logo_negocio" name="logo_negocio" accept="image/*">
                                    <?php if (!empty($apresentacao['logo_negocio'])): ?>
                                        <div class="mt-2"><img src="<?= htmlspecialchars($apresentacao['logo_negocio']) ?>" alt="Logo" class="img-fluid rounded border" style="max-height:120px;"></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label for="imagem_destaque" class="form-label">
                                        <i class="bi bi-eye-fill lbl-pub me-1"></i> Imagem de destaque
                                    </label>
                                    <input type="file" class="form-control" id="imagem_destaque" name="imagem_destaque" accept="image/*">
                                    <?php if (!empty($apresentacao['imagem_destaque'])): ?>
                                        <div class="mt-2"><img src="<?= htmlspecialchars($apresentacao['imagem_destaque']) ?>" alt="Imagem destaque" class="img-fluid rounded border" style="max-height:120px;"></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label for="galeria_imagens" class="form-label">
                                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Galeria de imagens
                                </label>
                                <input type="file" class="form-control" id="galeria_imagens" name="galeria_imagens[]" accept="image/*" multiple>
                                <?php if (!empty($galeria)): ?>
                                    <div class="row g-2 mt-2">
                                        <?php foreach ($galeria as $img): ?>
                                            <div class="col-6 col-md-3">
                                                <img src="<?= htmlspecialchars($img) ?>" class="img-fluid rounded border" alt="Imagem galeria">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="row g-4 mt-1">
                                <div class="col-md-6">
                                    <label for="video_pitch_url" class="form-label">
                                        <i class="bi bi-eye-fill lbl-pub me-1"></i> URL do vídeo pitch
                                    </label>
                                    <input type="url" class="form-control" id="video_pitch_url" name="video_pitch_url"
                                           value="<?= htmlspecialchars($apresentacao['video_pitch_url'] ?? '') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label for="apresentacao_video_url" class="form-label">
                                        <i class="bi bi-eye-fill lbl-pub me-1"></i> URL de vídeo adicional
                                    </label>
                                    <input type="url" class="form-control" id="apresentacao_video_url" name="apresentacao_video_url"
                                           value="<?= htmlspecialchars($apresentacao['apresentacao_video_url'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mt-4">
                                <label for="apresentacao_pdf" class="form-label">
                                    <i class="bi bi-eye-fill lbl-pub me-1"></i> PDF de apresentação
                                </label>
                                <input type="file" class="form-control" id="apresentacao_pdf" name="apresentacao_pdf" accept="application/pdf">
                                <?php if (!empty($apresentacao['apresentacao_pdf'])): ?>
                                    <div class="mt-2">
                                        <a href="<?= htmlspecialchars($apresentacao['apresentacao_pdf']) ?>" target="_blank" rel="noopener noreferrer">Ver PDF atual</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-section mb-4">
                            <div class="form-section-title"><i class="bi bi-lightbulb"></i> Inovação</div>

                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Seu negócio incorpora inovação? Marque onde houver inovação real. <small class="text-muted">(opcional)</small>
                                </label>
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <?php
                                    $inovacoes = [
                                        'inovacao_tecnologica'   => ['Inovação Tecnológica',           'IA, Big Data, IoT, blockchain, plataformas digitais, tecnologias verdes.'],
                                        'inovacao_produto'       => ['Inovação de Produto',             'Novo produto sustentável, materiais ecológicos, soluções regenerativas ou de saúde/educação inovadoras.'],
                                        'inovacao_servico'       => ['Inovação de Serviço',             'Telemedicina para populações remotas, Educação online inclusiva, Plataformas de acesso a crédito.'],
                                        'inovacao_modelo'        => ['Inovação em Modelo de Negócio',   'Marketplace de impacto, Economia compartilhada, Assinaturas acessíveis, Pay-per-use.'],
                                        'inovacao_social'        => ['Inovação Social',                 'Inclusão produtiva, Empoderamento de comunidades, Modelos de geração de renda.'],
                                        'inovacao_ambiental'     => ['Inovação Ambiental',              'Economia circular, Redução de emissões, Agricultura regenerativa, Energia renovável.'],
                                        'inovacao_cadeia_valor'  => ['Inovação na Cadeia de Valor',     'Cadeias produtivas inclusivas, Comércio justo, Logística sustentável.'],
                                        'inovacao_governanca'    => ['Inovação em Governança',          'Governança participativa, Cooperativismo moderno, Modelos de gestão horizontal.'],
                                        'inovacao_impacto'       => ['Inovação em Impacto',             'Novas métricas de impacto, Modelos de impacto escalável, Tecnologia para monitoramento socioambiental.'],
                                        'inovacao_financiamento' => ['Inovação em Financiamento',       'Microcrédito, crowdfunding, blended finance, fintechs sociais.'],
                                    ];
                                    foreach ($inovacoes as $campo => [$titulo, $desc]): ?>
                                        <div class="col">
                                            <div class="form-check border rounded p-3 h-100">
                                                <input class="form-check-input" type="checkbox" id="<?= $campo ?>" name="<?= $campo ?>" value="sim"
                                                       <?= !empty($apresentacao[$campo]) ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-semibold ms-1" for="<?= $campo ?>"><?= $titulo ?></label>
                                                <div class="small text-muted mt-1"><?= $desc ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-0">
                                <label for="descricao_inovacao" class="form-label">
                                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Descreva brevemente sua inovação
                                </label>
                                <textarea class="form-control" id="descricao_inovacao" name="descricao_inovacao" rows="4" maxlength="1000"><?= htmlspecialchars($apresentacao['descricao_inovacao'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="form-section mb-4">
                            <div class="form-section-title"><i class="bi bi-diagram-3"></i> Estrutura do Negócio</div>

                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label">
                                        <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Tipo de solução oferecida *
                                    </label>
                                    <?php foreach (['Produto','Serviço','Plataforma','Híbrido'] as $opt): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo_solucao" value="<?= $opt ?>"
                                                <?= ($apresentacao['tipo_solucao'] ?? '') === $opt ? 'checked' : '' ?> required>
                                            <label class="form-check-label"><?= $opt ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="col-md-4">
                                    <label for="modelo_negocio" class="form-label">
                                        <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Modelo de negócio *
                                    </label>
                                    <input type="text" class="form-control" id="modelo_negocio" name="modelo_negocio" maxlength="160"
                                           value="<?= htmlspecialchars($apresentacao['modelo_negocio'] ?? '') ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="colaboradores" class="form-label">
                                        <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Número de colaboradores
                                    </label>
                                    <input type="number" class="form-control" id="colaboradores" name="colaboradores" min="0" max="9999"
                                           value="<?= htmlspecialchars($apresentacao['colaboradores'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Como você descreveria a replicabilidade do seu modelo de negócio?
                                </label>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="replicabilidade"
                                           id="rep_digital" value="digital_escalavel"
                                           <?= ($apresentacao['replicabilidade'] ?? '') === 'digital_escalavel' ? 'checked' : '' ?>
                                           required>
                                    <label class="form-check-label" for="rep_digital">
                                        O modelo é digital e altamente escalável, com baixo custo marginal para replicação
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="replicabilidade"
                                           id="rep_baixa" value="replicavel_baixa_adaptacao"
                                           <?= ($apresentacao['replicabilidade'] ?? '') === 'replicavel_baixa_adaptacao' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="rep_baixa">
                                        O modelo pode ser replicado em outras regiões com baixa necessidade de adaptação
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="replicabilidade"
                                           id="rep_alta" value="replicavel_alta_adaptacao"
                                           <?= ($apresentacao['replicabilidade'] ?? '') === 'replicavel_alta_adaptacao' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="rep_alta">
                                        O modelo é replicável, mas exige adaptações operacionais ou contextuais significativas
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

                            <!-- Nível de Tecnologia (J) -->
                            <div class="mt-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Qual é o papel da tecnologia no seu modelo de negócio?
                                    <span class="text-danger">*</span>
                                </label>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="nivel_tecnologia"
                                           id="tec_propria" value="tecnologia_propria"
                                           <?= ($apresentacao['nivel_tecnologia'] ?? '') === 'tecnologia_propria' ? 'checked' : '' ?>
                                           required>
                                    <label class="form-check-label" for="tec_propria">
                                        Desenvolvemos tecnologia própria — plataforma, software ou sistema exclusivo do negócio
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="nivel_tecnologia"
                                           id="tec_adaptada" value="tecnologia_adaptada"
                                           <?= ($apresentacao['nivel_tecnologia'] ?? '') === 'tecnologia_adaptada' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tec_adaptada">
                                        Utilizamos tecnologias de terceiros adaptadas ao nosso modelo (ex: ferramentas SaaS, APIs, apps white-label)
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="nivel_tecnologia"
                                           id="tec_manual" value="modelo_manual"
                                           <?= ($apresentacao['nivel_tecnologia'] ?? '') === 'modelo_manual' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tec_manual">
                                        Nosso modelo opera principalmente de forma manual ou presencial, sem uso intensivo de tecnologia
                                    </label>
                                </div>
                            </div>
                        </div>

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
                                    <label class="form-check-label">Sim</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="programas" class="form-label">
                                    <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Quais programas ou aceleradoras? 
                                </label>
                                <textarea class="form-control" id="programas" name="programas" rows="3" maxlength="1000"><?= htmlspecialchars(is_array($programasLista) ? implode(', ', $programasLista) : '') ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Quais desafios seu negócio enfrenta atualmente?
                                </label>
                                <?php
                                $desafiosLista = [
                                    'capital' => 'Acesso a capital',
                                    'clientes' => 'Aquisição de clientes',
                                    'equipe' => 'Equipe / talentos',
                                    'operacao' => 'Estrutura operacional',
                                    'tecnologia' => 'Tecnologia / produto',
                                    'regulacao' => 'Regulação / burocracia',
                                    'impacto' => 'Medição de impacto',
                                    'marketing' => 'Marketing / posicionamento',
                                ];
                                ?>
                                <div class="row row-cols-1 row-cols-md-2 g-2">
                                    <?php foreach ($desafiosLista as $slug => $label): ?>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="desafios[]" value="<?= $slug ?>"
                                                       id="desafio_<?= $slug ?>" <?= in_array($slug, $desafiosSelecionados) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="desafio_<?= $slug ?>"><?= $label ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="info_adicionais" class="form-label">
                                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Informações adicionais relevantes
                                </label>
                                <textarea class="form-control" id="info_adicionais" name="info_adicionais" rows="4" maxlength="1000"><?= htmlspecialchars($apresentacao['info_adicionais'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-0">
                                <label for="info_adicionais_links" class="form-label">
                                    <i class="bi bi-eye-fill lbl-pub me-1"></i> Links adicionais (um por linha)
                                </label>
                                <textarea class="form-control" id="info_adicionais_links" name="info_adicionais_links" rows="4" maxlength="1500"><?= htmlspecialchars(is_array($linksAdicionais) ? implode("\n", $linksAdicionais) : '') ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="/negocios/etapa4_impacto.php?id=<?= $negocio_id ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Etapa anterior
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Salvar e continuar <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
