<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: /negocios/meus-negocios.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$user_id = $_SESSION['user_id'];
$negocio_id = (int) $_GET['id'];
$pdo = getPDO();

$stmt = $pdo->prepare("SELECT id FROM negocios WHERE id = :id AND user_id = :user_id LIMIT 1");
$stmt->execute([
    'id' => $negocio_id,
    'user_id' => $user_id,
]);

if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    die('Negócio não encontrado ou acesso negado.');
}

$stmt = $pdo->prepare("SELECT * FROM negocio_visao WHERE negocio_id = :id LIMIT 1");
$stmt->execute(['id' => $negocio_id]);
$visao = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$apoios = json_decode($visao['apoios'] ?? '[]', true);
if (!is_array($apoios)) $apoios = [];

$areas = json_decode($visao['areas'] ?? '[]', true);
if (!is_array($areas)) $areas = [];

$temas = json_decode($visao['temas'] ?? '[]', true);
if (!is_array($temas)) $temas = [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Etapa 8 — Visão de Futuro</title>
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

                    <div class="mb-4">
                        <h1 class="h3 mb-1">Etapa 8 — Visão de Futuro</h1>
                        <p class="emp-page-subtitle mb-0">Planejamento estratégico, escala e apoio para os próximos anos.</p>
                    </div>

                    <?php if (!empty($_SESSION['errors_etapa8'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($_SESSION['errors_etapa8'] as $erro): ?>
                                    <li><?= htmlspecialchars($erro) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['errors_etapa8']); ?>
                    <?php endif; ?>

                    <form action="/negocios/processar_etapa8.php" method="POST">
                        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">

                        <div class="form-section mb-4">
                            <div class="form-section-title"><i class="bi bi-bullseye"></i> Visão estratégica</div>

                            <div class="mb-4">
                                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Qual visão melhor representa seu negócio para os próximos anos? *</label>
                                <?php
                                $visoes = [
                                    "Consolidar a operação e ganhar eficiência",
                                    "Expandir atuação regionalmente",
                                    "Expandir nacionalmente com novos mercados",
                                    "Crescer em escala nacional como referência em impacto",
                                    "Internacionalizar a atuação",
                                    "Ainda em definição",
                                ];
                                foreach ($visoes as $v): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="visao_estrategica" value="<?= $v ?>"
                                            id="<?= md5($v) ?>" <?= ($visao['visao_estrategica'] ?? '') === $v ? 'checked' : '' ?> required>
                                        <label class="form-check-label" for="<?= md5($v) ?>"><?= $v ?></label>
                                    </div>
                                <?php endforeach; ?>
                                <input type="text" name="visao_outro" class="form-control mt-2"
                                       value="<?= htmlspecialchars($visao['visao_outro'] ?? '') ?>"
                                       placeholder="Se marcou 'Ainda em definição', complemente aqui" maxlength="120">
                            </div>

                            <div class="mb-0">
                                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Como pretende sustentar esse crescimento ou consolidação? *</label>
                                <?php
                                $sustentabilidades = [
                                    "Reinvestimento da própria operação",
                                    "Captação de investimento",
                                    "Parcerias estratégicas",
                                    "Editais / convênios / contratos públicos",
                                    "Expansão comercial e novos canais",
                                    "Modelo híbrido",
                                ];
                                foreach ($sustentabilidades as $s): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="sustentabilidade" value="<?= $s ?>"
                                            id="<?= md5($s) ?>" <?= ($visao['sustentabilidade'] ?? '') === $s ? 'checked' : '' ?> required>
                                        <label class="form-check-label" for="<?= md5($s) ?>"><?= $s ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-section mb-4">
                            <div class="form-section-title"><i class="bi bi-arrows-angle-expand"></i> Escala e apoio buscado</div>

                            <div class="mb-4">
                                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Qual é a sua ambição de escala nos próximos anos? *</label>
                                <select name="escala" class="form-select" required>
                                    <option value="" <?= empty($visao['escala']) ? 'selected' : '' ?>>Selecione uma opção</option>
                                    <?php
                                    $opcoesEscala = [
                                        "Escalar localmente (mais profundidade e alcance na mesma região)",
                                        "Escalar nacionalmente (atuar em novas regiões/mercados do Brasil)",
                                        "Escalar internacionalmente (expandir o modelo para fora do país)",
                                        "Manter o modelo atual como negócio de nicho ou territorial",
                                        "Ainda em definição"
                                    ];
                                    foreach ($opcoesEscala as $op) {
                                        $sel = ($visao['escala'] ?? '') === $op ? 'selected' : '';
                                        echo "<option value=\"$op\" $sel>$op</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Parcerias Estratégicas (L) -->
                            <div class="mb-4">
                                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Seu negócio possui parcerias estratégicas ativas hoje? *</label>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="parcerias_ativas"
                                           id="parc_nacionais" value="parcerias_nacionais_internacionais"
                                           <?= ($visao['parcerias_ativas'] ?? '') === 'parcerias_nacionais_internacionais' ? 'checked' : '' ?>
                                           required>
                                    <label class="form-check-label" for="parc_nacionais">
                                        Sim — parcerias nacionais ou internacionais formalizadas (contratos, MoUs, acordos institucionais)
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="parcerias_ativas"
                                           id="parc_locais" value="parcerias_locais"
                                           <?= ($visao['parcerias_ativas'] ?? '') === 'parcerias_locais' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="parc_locais">
                                        Sim — parcerias locais ou regionais formalizadas (prefeituras, entidades, fornecedores estratégicos)
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="parcerias_ativas"
                                           id="parc_informais" value="parcerias_informais"
                                           <?= ($visao['parcerias_ativas'] ?? '') === 'parcerias_informais' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="parc_informais">
                                        Parcerias informais em andamento — relacionamentos sem formalização ainda
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="parcerias_ativas"
                                           id="parc_sem" value="sem_parcerias"
                                           <?= ($visao['parcerias_ativas'] ?? '') === 'sem_parcerias' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="parc_sem">
                                        Não temos parcerias estratégicas ativas no momento
                                    </label>
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Qual o tipo de apoio financeiro ou estratégico que você busca atualmente? *</label>
                                <?php
                                $apoiosLista = [
                                    "Investimento Anjo","Venture Capital (VC)","Parcerias corporativas ou estratégicas",
                                    "Editais públicos e subsídios","Financiamento bancário ou crédito com impacto",
                                    "Doações filantrópicas ou blended finance","Outro"
                                ];
                                foreach ($apoiosLista as $a): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="apoios[]" value="<?= $a ?>"
                                            id="<?= md5($a) ?>" <?= in_array($a, $apoios) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="<?= md5($a) ?>"><?= $a ?></label>
                                    </div>
                                <?php endforeach; ?>
                                <input type="text" name="apoio_outro" class="form-control mt-2"
                                       value="<?= htmlspecialchars($visao['apoio_outro'] ?? '') ?>"
                                       placeholder="Se marcou 'Outro', especifique aqui" maxlength="120">
                            </div>
                        </div>

                        <div class="form-section mb-4">
                            <div class="form-section-title"><i class="bi bi-tools"></i> Áreas a fortalecer</div>

                            <div class="mb-0">
                                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Quais áreas do seu negócio você gostaria de fortalecer com apoio externo? *</label>
                                <?php
                                $areasLista = [
                                    "Capital de giro ou fluxo de caixa","Expansão comercial e abertura de mercado",
                                    "Desenvolvimento de tecnologia ou produto","Reforço da estrutura operacional (equipamentos, logística etc.)",
                                    "Formação de equipe e qualificação técnica","Comunicação estratégica e branding",
                                    "Medição e gestão do impacto socioambiental","Governança e profissionalização da gestão","Outro"
                                ];
                                foreach ($areasLista as $ar): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="areas[]" value="<?= $ar ?>"
                                            id="<?= md5($ar) ?>" <?= in_array($ar, $areas) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="<?= md5($ar) ?>"><?= $ar ?></label>
                                    </div>
                                <?php endforeach; ?>
                                <input type="text" name="area_outro" class="form-control mt-2"
                                       value="<?= htmlspecialchars($visao['area_outro'] ?? '') ?>"
                                       placeholder="Se marcou 'Outro', especifique aqui" maxlength="120">
                            </div>
                        </div>

                        <div class="form-section mb-4">
                            <div class="form-section-title"><i class="bi bi-stars"></i> Temas de interesse</div>

                            <div class="mb-0">
                                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Em quais temas você gostaria de receber mais apoio, conteúdo ou conexão? *</label>
                                <?php
                                $temasLista = [
                                    "Captação de recursos e pitch",
                                    "Expansão e modelos de escala",
                                    "Vendas e estratégia comercial",
                                    "Marketing e posicionamento",
                                    "Tecnologia e produto digital",
                                    "Gestão financeira",
                                    "Governança e estrutura organizacional",
                                    "Medição de impacto e ESG",
                                    "Parcerias e conexões estratégicas",
                                    "Outro"
                                ];
                                foreach ($temasLista as $t): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="temas[]" value="<?= $t ?>"
                                            id="<?= md5($t) ?>" <?= in_array($t, $temas) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="<?= md5($t) ?>"><?= $t ?></label>
                                    </div>
                                <?php endforeach; ?>
                                <input type="text" name="tema_outro" class="form-control mt-2"
                                       value="<?= htmlspecialchars($visao['tema_outro'] ?? '') ?>"
                                       placeholder="Se marcou 'Outro', especifique aqui" maxlength="120">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="/negocios/etapa7_impacto.php?id=<?= $negocio_id ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Etapa anterior
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Salvar e concluir <i class="bi bi-check2-circle"></i>
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
