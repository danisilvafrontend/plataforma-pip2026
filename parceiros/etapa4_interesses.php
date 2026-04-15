<?php
session_start();

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

if (!isset($_SESSION['parceiro_id'])) {
    header("Location: login.php?msg=login_necessario");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// 1. Busca as ODS selecionadas
$stmt_ods = $pdo->prepare("SELECT ods_id FROM parceiro_ods WHERE parceiro_id = ?");
$stmt_ods->execute([$parceiro_id]);
$ods_salvas = $stmt_ods->fetchAll(PDO::FETCH_COLUMN) ?: [];

// 2. Busca todas as ODS do banco para renderizar na tela
$todas_ods = $pdo->query("SELECT * FROM ods ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. Busca os outros interesses na tabela parceiro_interesses
$stmt_int = $pdo->prepare("SELECT * FROM parceiro_interesses WHERE parceiro_id = ?");
$stmt_int->execute([$parceiro_id]);
$interesses = $stmt_int->fetch(PDO::FETCH_ASSOC) ?: [];

// Decodifica JSONs
$eixos_salvos = !empty($interesses['eixos_interesse']) ? json_decode($interesses['eixos_interesse'], true) : [];
$maturidade_salva = !empty($interesses['maturidade_negocios']) ? json_decode($interesses['maturidade_negocios'], true) : [];
$setores_salvos = !empty($interesses['setores_interesse']) ? json_decode($interesses['setores_interesse'], true) : [];
$perfil_salvo = !empty($interesses['perfil_impacto']) ? json_decode($interesses['perfil_impacto'], true) : [];

if (!is_array($eixos_salvos)) $eixos_salvos = [];
if (!is_array($maturidade_salva)) $maturidade_salva = [];
if (!is_array($setores_salvos)) $setores_salvos = [];
if (!is_array($perfil_salvo)) $perfil_salvo = [];

$alcance = $interesses['alcance_impacto'] ?? '';
$orcamento_anual = $interesses['orcamento_anual'] ?? '';
$tipo_relacionamento = $interesses['tipo_relacionamento'] ?? '';
$horizonte_engajamento = $interesses['horizonte_engajamento'] ?? '';

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container py-5 parceiro-step-shell">
    <div class="parceiro-step-top mb-4 mb-lg-5">
        <div class="parceiro-step-progress-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <span class="parceiro-step-kicker">Etapa 4 de 6</span>
                    <h1 class="parceiro-step-title mb-1">Perfil de Impacto</h1>
                    <p class="parceiro-step-subtitle mb-0">
                        Mapeie os temas, perfis e formatos de conexão que mais combinam com os interesses da sua organização.
                    </p>
                </div>
                <div class="parceiro-step-indicator">66%</div>
            </div>

            <div class="progress parceiro-step-progress" role="progressbar" aria-valuenow="66" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-primary" style="width: 66%;"></div>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <aside class="parceiro-step-aside">
                <div class="parceiro-step-aside-card">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-bullseye"></i>
                        Objetivo desta etapa
                    </div>
                    <ul class="parceiro-step-aside-list">
                        <li>Entender os temas e ODS com os quais sua organização mais se conecta.</li>
                        <li>Mapear estágio, setor, perfil e alcance dos negócios de interesse.</li>
                        <li>Melhorar o matchmaking com oportunidades e conexões futuras na plataforma.</li>
                    </ul>
                </div>

                <div class="parceiro-step-aside-card parceiro-step-aside-highlight">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-diagram-3-fill"></i>
                        Matchmaking mais assertivo
                    </div>
                    <p class="mb-0">
                        Quanto mais precisa for sua seleção, melhores serão os cruzamentos com negócios, programas e oportunidades compatíveis.
                    </p>
                </div>
            </aside>
        </div>

        <div class="col-lg-8">
            <div class="parceiro-step-card">
                <div class="parceiro-step-card-header">
                    <div>
                        <h2 class="parceiro-step-card-title mb-1">Mapeamento de interesses</h2>
                        <p class="parceiro-step-card-subtitle mb-0">
                            Essas respostas ajudam a conectar sua organização com negócios de impacto e oportunidades mais adequadas ao seu perfil.
                        </p>
                    </div>
                </div>

                <div class="parceiro-step-card-body">
                    <form method="POST" action="processar_etapa4.php">
                        <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">ODS de Interesse</h3>
                                <p class="parceiro-step-section-text">
                                    Quais Objetivos de Desenvolvimento Sustentável você mais se identifica ou gostaria de acompanhar?
                                </p>
                            </div>

                            <div class="row g-3">
                                <?php foreach ($todas_ods as $ods): 
                                    $checked = in_array($ods['id'], $ods_salvas) ? 'checked' : '';
                                ?>
                                    <div class="col-md-6">
                                        <div class="parceiro-choice-card parceiro-choice-card-ods">
                                            <div class="form-check parceiro-choice-check parceiro-choice-check-ods">
                                                <input
                                                    class="form-check-input parceiro-choice-input"
                                                    type="checkbox"
                                                    name="ods[]"
                                                    value="<?= $ods['id'] ?>"
                                                    id="ods_<?= $ods['id'] ?>"
                                                    <?= $checked ?>
                                                >
                                                <label class="form-check-label parceiro-choice-label parceiro-choice-label-ods" for="ods_<?= $ods['id'] ?>">
                                                    <?php if (!empty($ods['icone_url'])): ?>
                                                        <img
                                                            src="<?= htmlspecialchars($ods['icone_url']) ?>"
                                                            alt="ODS <?= $ods['n_ods'] ?>"
                                                            class="parceiro-ods-icon"
                                                        >
                                                    <?php endif; ?>
                                                    <span class="parceiro-choice-title parceiro-choice-title-ods">
                                                        ODS <?= $ods['n_ods'] ?> - <?= htmlspecialchars($ods['nome']) ?>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Capacidade financeira</h3>
                                <p class="parceiro-step-section-text">
                                    Isso permite um matchmaking mais assertivo com negócios que buscam investimentos ou apoios compatíveis.
                                </p>
                            </div>

                            <div class="row g-3">
                                <?php 
                                $opcoes_orcamento = [
                                    'Ate 100k' => 'Até R$ 100 mil',
                                    '100k a 500k' => 'R$ 100 mil – R$ 500 mil',
                                    '500k a 2M' => 'R$ 500 mil – R$ 2 milhões',
                                    'Acima de 2M' => 'Acima de R$ 2 milhões',
                                    'Sem aporte' => 'Não envolve aporte financeiro'
                                ];
                                foreach ($opcoes_orcamento as $val => $label): 
                                ?>
                                    <div class="col-md-6">
                                        <label class="parceiro-radio-card parceiro-radio-card-center">
                                            <input
                                                class="form-check-input parceiro-radio-input"
                                                type="radio"
                                                name="orcamento_anual"
                                                value="<?= $val ?>"
                                                id="orc_<?= md5($val) ?>"
                                                <?= ($orcamento_anual === $val) ? 'checked' : '' ?>
                                                required
                                            >
                                            <span class="parceiro-radio-content">
                                                <span class="parceiro-radio-title"><?= $label ?></span>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Tipo de relacionamento</h3>
                                <p class="parceiro-step-section-text">
                                    Defina o modelo de conexão desejado entre sua instituição e os negócios.
                                </p>
                            </div>

                            <div class="row g-3">
                                <?php 
                                $opcoes_relacionamento = [
                                    'Varios menores' => 'Apoiar vários negócios menores',
                                    'Poucos estrategicos' => 'Apoiar poucos negócios estratégicos',
                                    'Programa estruturado' => 'Construir programa estruturado (ex: Impact Chain)',
                                    'Investimento direto' => 'Investimento direto com participação societária',
                                    'Explorando' => 'Ainda explorando possibilidades'
                                ];
                                foreach ($opcoes_relacionamento as $val => $label): 
                                ?>
                                    <div class="col-md-6">
                                        <label class="parceiro-radio-card">
                                            <input
                                                class="form-check-input parceiro-radio-input"
                                                type="radio"
                                                name="tipo_relacionamento"
                                                value="<?= $val ?>"
                                                id="rel_<?= md5($val) ?>"
                                                <?= ($tipo_relacionamento === $val) ? 'checked' : '' ?>
                                                required
                                            >
                                            <span class="parceiro-radio-content">
                                                <span class="parceiro-radio-title"><?= $label ?></span>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Horizonte de engajamento</h3>
                                <p class="parceiro-step-section-text">
                                    Evita conectar demandas pontuais com quem busca apoios estruturantes de longo prazo.
                                </p>
                            </div>

                            <div class="row g-3">
                                <?php 
                                $opcoes_horizonte = [
                                    'Pontual' => 'Pontual (até 6 meses)',
                                    'Medio' => 'Médio prazo (1 ano)',
                                    'Longo' => 'Longo prazo (2+ anos)',
                                    'Projeto' => 'Projeto específico'
                                ];
                                foreach ($opcoes_horizonte as $val => $label): 
                                ?>
                                    <div class="col-md-6 col-xl-3">
                                        <label class="parceiro-radio-card parceiro-radio-card-center parceiro-radio-card-compact">
                                            <input
                                                class="form-check-input parceiro-radio-input"
                                                type="radio"
                                                name="horizonte_engajamento"
                                                value="<?= $val ?>"
                                                id="hor_<?= md5($val) ?>"
                                                <?= ($horizonte_engajamento === $val) ? 'checked' : '' ?>
                                                required
                                            >
                                            <span class="parceiro-radio-content">
                                                <span class="parceiro-radio-title"><?= $label ?></span>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Eixos temáticos adicionais</h3>
                                <p class="parceiro-step-section-text">
                                    Quais temas mais despertam seu interesse além das ODS?
                                </p>
                            </div>

                            <div class="row g-3">
                                <?php 
                                $eixos = [
                                    'Meio Ambiente e Clima', 'Água e Oceanos', 'Biodiversidade e Florestas',
                                    'Economia Circular', 'Energia Limpa', 'Segurança Alimentar', 'Saúde e Bem-Estar',
                                    'Educação', 'Igualdade de Gênero', 'Equidade Racial', 'Trabalho e Renda',
                                    'Cidades Sustentáveis', 'Inovação e Tecnologia', 'Inclusão Social',
                                    'Governança e Transparência', 'Parcerias e Investimento Social'
                                ];
                                foreach ($eixos as $eixo): 
                                    $checked = in_array($eixo, $eixos_salvos) ? 'checked' : '';
                                ?>
                                    <div class="col-md-6">
                                        <div class="parceiro-choice-card">
                                            <div class="form-check parceiro-choice-check">
                                                <input
                                                    class="form-check-input parceiro-choice-input"
                                                    type="checkbox"
                                                    name="eixos[]"
                                                    value="<?= htmlspecialchars($eixo) ?>"
                                                    id="eixo_<?= md5($eixo) ?>"
                                                    <?= $checked ?>
                                                >
                                                <label class="form-check-label parceiro-choice-label" for="eixo_<?= md5($eixo) ?>">
                                                    <span class="parceiro-choice-title"><?= $eixo ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Maturidade dos negócios</h3>
                                <p class="parceiro-step-section-text">
                                    Indique em quais estágios sua organização prefere acompanhar ou apoiar negócios.
                                </p>
                            </div>

                            <div class="row g-3">
                                <?php 
                                $maturidades = [
                                    'Ideação' => 'Ideação (começando agora)',
                                    'Operação' => 'Operação (modelo sendo testado)',
                                    'Tração/Escala' => 'Tração/Escala (já operando e expandindo)',
                                    'Dinamizador' => 'Dinamizador (impacto consolidado e ampliando alcance)'
                                ];
                                foreach ($maturidades as $val => $label): 
                                    $checked = in_array($val, $maturidade_salva) ? 'checked' : '';
                                ?>
                                    <div class="col-md-6">
                                        <div class="parceiro-choice-card">
                                            <div class="form-check parceiro-choice-check">
                                                <input
                                                    class="form-check-input parceiro-choice-input"
                                                    type="checkbox"
                                                    name="maturidade[]"
                                                    value="<?= htmlspecialchars($val) ?>"
                                                    id="mat_<?= md5($val) ?>"
                                                    <?= $checked ?>
                                                >
                                                <label class="form-check-label parceiro-choice-label" for="mat_<?= md5($val) ?>">
                                                    <span class="parceiro-choice-title"><?= $label ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="parceiro-step-section-head">
                                        <h3 class="parceiro-step-section-title">Setores / Indústrias</h3>
                                        <p class="parceiro-step-section-text">
                                            Selecione os setores com maior afinidade para conexão.
                                        </p>
                                    </div>

                                    <div class="row g-3">
                                        <?php 
                                        $setores = ['Tecnologia', 'Agronegócio sustentável', 'Saúde', 'Educação', 'Finanças de impacto', 'Energia', 'Moda sustentável', 'Alimentação', 'Construção civil', 'Cultura', 'ESG corporativo', 'Startups', 'Negócios sociais', 'Cooperativas', 'ONGs'];
                                        foreach ($setores as $setor): 
                                            $checked = in_array($setor, $setores_salvos) ? 'checked' : '';
                                        ?>
                                            <div class="col-12">
                                                <div class="parceiro-choice-card parceiro-choice-card-soft">
                                                    <div class="form-check parceiro-choice-check">
                                                        <input
                                                            class="form-check-input parceiro-choice-input"
                                                            type="checkbox"
                                                            name="setores[]"
                                                            value="<?= htmlspecialchars($setor) ?>"
                                                            id="set_<?= md5($setor) ?>"
                                                            <?= $checked ?>
                                                        >
                                                        <label class="form-check-label parceiro-choice-label" for="set_<?= md5($setor) ?>">
                                                            <span class="parceiro-choice-title"><?= $setor ?></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="parceiro-step-section-head">
                                        <h3 class="parceiro-step-section-title">Perfil de impacto desejado</h3>
                                        <p class="parceiro-step-section-text">
                                            Indique quais perfis de impacto fazem mais sentido para sua organização.
                                        </p>
                                    </div>

                                    <div class="row g-3">
                                        <?php 
                                        $perfis = ['Social', 'Ambiental', 'Social / Ambiental', 'Inovação tecnológica', 'Base comunitária', 'Liderado por mulheres', 'Liderado por jovens', 'Impacto regional/local', 'Impacto global'];
                                        foreach ($perfis as $perfil): 
                                            $checked = in_array($perfil, $perfil_salvo) ? 'checked' : '';
                                        ?>
                                            <div class="col-12">
                                                <div class="parceiro-choice-card parceiro-choice-card-soft">
                                                    <div class="form-check parceiro-choice-check">
                                                        <input
                                                            class="form-check-input parceiro-choice-input"
                                                            type="checkbox"
                                                            name="perfil[]"
                                                            value="<?= htmlspecialchars($perfil) ?>"
                                                            id="perf_<?= md5($perfil) ?>"
                                                            <?= $checked ?>
                                                        >
                                                        <label class="form-check-label parceiro-choice-label" for="perf_<?= md5($perfil) ?>">
                                                            <span class="parceiro-choice-title"><?= $perfil ?></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="parceiro-step-section">
                            <div class="parceiro-step-section-head">
                                <h3 class="parceiro-step-section-title">Alcance do impacto</h3>
                                <p class="parceiro-step-section-text">
                                    Você prefere apoiar causas locais, nacionais, globais ou atuar em todos os níveis?
                                </p>
                            </div>

                            <div class="row g-3">
                                <?php 
                                $alcance_opcoes = ['local' => 'Local', 'nacional' => 'Nacional', 'global' => 'Global', 'todos' => 'Todos os níveis'];
                                foreach ($alcance_opcoes as $val => $label): 
                                ?>
                                    <div class="col-md-6 col-xl-3">
                                        <label class="parceiro-radio-card parceiro-radio-card-center parceiro-radio-card-compact">
                                            <input
                                                class="form-check-input parceiro-radio-input"
                                                type="radio"
                                                name="alcance"
                                                value="<?= $val ?>"
                                                id="alc_<?= $val ?>"
                                                <?= ($alcance === $val) ? 'checked' : '' ?>
                                                required
                                            >
                                            <span class="parceiro-radio-content">
                                                <span class="parceiro-radio-title"><?= $label ?></span>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <div class="parceiro-step-actions">
                            <?php if (($_GET['from'] ?? '') === 'confirmacao'): ?>
                                <button type="submit" name="acao" value="confirmacao" class="btn btn-outline-primary">
                                    Salvar e voltar à revisão
                                </button>
                            <?php endif; ?>

                            <a href="etapa3_combinado.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Voltar
                            </a>

                            <button type="submit" class="btn-reg-submit">
                                Salvar e avançar
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>



<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
