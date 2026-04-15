<?php
session_start();

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Verifica login
if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];
$mensagem = '';
$tipo_msg = '';

// PROCESSAMENTO DO FORMULÁRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ods = $_POST['ods'] ?? [];
    $eixos = $_POST['eixos'] ?? [];
    $maturidade = $_POST['maturidade'] ?? [];
    $setores = $_POST['setores'] ?? [];
    $perfil_impacto = $_POST['perfil'] ?? [];
    $alcance = $_POST['alcance'] ?? '';

    $orcamento_anual = $_POST['orcamento_anual'] ?? '';
    $tipo_relacionamento = $_POST['tipo_relacionamento'] ?? '';
    $horizonte_engajamento = $_POST['horizonte_engajamento'] ?? '';

    $eixos_json = json_encode($eixos, JSON_UNESCAPED_UNICODE);
    $maturidade_json = json_encode($maturidade, JSON_UNESCAPED_UNICODE);
    $setores_json = json_encode($setores, JSON_UNESCAPED_UNICODE);
    $perfil_json = json_encode($perfil_impacto, JSON_UNESCAPED_UNICODE);

    try {
        $pdo->beginTransaction();

        $sql_int = "
            INSERT INTO parceiro_interesses 
            (parceiro_id, eixos_interesse, maturidade_negocios, setores_interesse, perfil_impacto, alcance_impacto, orcamento_anual, tipo_relacionamento, horizonte_engajamento) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            eixos_interesse = VALUES(eixos_interesse), 
            maturidade_negocios = VALUES(maturidade_negocios), 
            setores_interesse = VALUES(setores_interesse), 
            perfil_impacto = VALUES(perfil_impacto), 
            alcance_impacto = VALUES(alcance_impacto),
            orcamento_anual = VALUES(orcamento_anual),
            tipo_relacionamento = VALUES(tipo_relacionamento),
            horizonte_engajamento = VALUES(horizonte_engajamento)
        ";
        $stmt = $pdo->prepare($sql_int);
        $stmt->execute([
            $parceiro_id,
            $eixos_json,
            $maturidade_json,
            $setores_json,
            $perfil_json,
            $alcance,
            $orcamento_anual,
            $tipo_relacionamento,
            $horizonte_engajamento
        ]);

        $pdo->prepare("DELETE FROM parceiro_ods WHERE parceiro_id = ?")->execute([$parceiro_id]);

        if (!empty($ods)) {
            $sql_ods = "INSERT INTO parceiro_ods (parceiro_id, ods_id) VALUES (?, ?)";
            $stmt_ods = $pdo->prepare($sql_ods);

            foreach ($ods as $ods_id) {
                $stmt_ods->execute([$parceiro_id, $ods_id]);
            }
        }

        $pdo->commit();
        $mensagem = "Preferências e interesses atualizados com sucesso! Seu radar de conexões foi ajustado.";
        $tipo_msg = "success";

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao atualizar interesses do parceiro: " . $e->getMessage());
        $mensagem = "Erro ao salvar seus interesses. Tente novamente mais tarde.";
        $tipo_msg = "danger";
    }
}

// BUSCA DADOS ATUAIS
$stmt_ods = $pdo->prepare("SELECT ods_id FROM parceiro_ods WHERE parceiro_id = ?");
$stmt_ods->execute([$parceiro_id]);
$ods_salvas = $stmt_ods->fetchAll(PDO::FETCH_COLUMN) ?: [];

$todas_ods = $pdo->query("SELECT * FROM ods ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

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

$opcoes_orcamento = [
    'Ate 100k' => 'Até R$ 100 mil',
    '100k a 500k' => 'R$ 100 mil – R$ 500 mil',
    '500k a 2M' => 'R$ 500 mil – R$ 2 milhões',
    'Acima de 2M' => 'Acima de R$ 2 milhões',
    'Sem aporte' => 'Não envolve aporte financeiro'
];

$opcoes_relacionamento = [
    'Varios menores' => 'Apoiar vários negócios menores',
    'Poucos estrategicos' => 'Apoiar poucos negócios estratégicos',
    'Programa estruturado' => 'Construir programa estruturado (ex: Impact Chain)',
    'Investimento direto' => 'Investimento direto com participação societária',
    'Explorando' => 'Ainda explorando possibilidades'
];

$opcoes_horizonte = [
    'Pontual' => 'Pontual (até 6 meses)',
    'Medio' => 'Médio prazo (1 ano)',
    'Longo' => 'Longo prazo (2+ anos)',
    'Projeto' => 'Projeto específico'
];

$lista_eixos = [
    'Meio Ambiente e Clima',
    'Água e Oceanos',
    'Biodiversidade e Florestas',
    'Economia Circular',
    'Energia Limpa',
    'Segurança Alimentar',
    'Saúde e Bem-Estar',
    'Educação',
    'Igualdade de Gênero',
    'Equidade Racial',
    'Trabalho e Renda',
    'Cidades Sustentáveis',
    'Inovação e Tecnologia',
    'Inclusão Social',
    'Governança e Transparência',
    'Parcerias e Investimento Social'
];

$lista_maturidades = [
    'Ideação' => 'Ideação (começando agora)',
    'Operação' => 'Operação (modelo sendo testado)',
    'Tração/Escala' => 'Tração/Escala (já operando e expandindo)',
    'Dinamizador' => 'Dinamizador (impacto consolidado e ampliando alcance)'
];

$lista_setores = [
    'Tecnologia',
    'Agronegócio sustentável',
    'Saúde',
    'Educação',
    'Finanças de impacto',
    'Energia',
    'Moda sustentável',
    'Alimentação',
    'Construção civil',
    'Cultura',
    'ESG corporativo',
    'Startups',
    'Negócios sociais',
    'Cooperativas',
    'ONGs'
];

$lista_perfis = [
    'Social',
    'Ambiental',
    'Social / Ambiental',
    'Inovação tecnológica',
    'Base comunitária',
    'Liderado por mulheres',
    'Liderado por jovens',
    'Impacto regional/local',
    'Impacto global'
];

$alcance_opcoes = [
    'local' => 'Local',
    'nacional' => 'Nacional',
    'global' => 'Global',
    'todos' => 'Todos os níveis'
];

$pageTitle = "Editar Interesses e Matchmaking";
include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-lg-3 col-md-4 mb-4 mb-md-0">
            <?php include __DIR__ . '/../app/views/parceiros/sidebar.php'; ?>
        </div>

        <!-- CONTEÚDO PRINCIPAL -->
        <div class="col-lg-9 col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1">Meus Interesses e Matchmaking</h2>
                    <p class="text-muted mb-0">Ajuste o foco do seu radar na Rede de Impacto.</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i> Voltar ao Painel
                </a>
            </div>

            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-<?= $tipo_msg ?> alert-dismissible fade show" role="alert">
                    <i class="bi <?= $tipo_msg === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                    <?= htmlspecialchars($mensagem) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- ODS -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-bullseye"></i> ODS de Interesse
                    </div>
                    <p class="form-section-desc">
                        Quais Objetivos de Desenvolvimento Sustentável (ODS) você mais se identifica ou gostaria de acompanhar?
                    </p>

                    <div class="row g-3">
                        <?php foreach ($todas_ods as $ods): 
                            $checked = in_array($ods['id'], $ods_salvas) ? 'checked' : '';
                        ?>
                            <div class="col-12 col-md-6">
                                <label class="match-card match-card-check">
                                    <input class="visually-hidden match-check" type="checkbox" name="ods[]" value="<?= $ods['id'] ?>" <?= $checked ?>>
                                    <div class="match-card-inner">
                                        <?php if (!empty($ods['icone_url'])): ?>
                                            <div class="match-card-icon">
                                                <img src="<?= htmlspecialchars($ods['icone_url']) ?>" alt="ODS <?= $ods['n_ods'] ?>">
                                            </div>
                                        <?php endif; ?>

                                        <div class="match-card-content">
                                            <div class="match-card-title">
                                                ODS <?= $ods['n_ods'] ?> - <?= htmlspecialchars($ods['nome']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ORÇAMENTO -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-cash-coin"></i> 1. Orçamento anual estimado
                    </div>
                    <p class="form-section-desc">
                        Isso permite um matchmaking mais assertivo com negócios que buscam investimentos compatíveis.
                    </p>

                    <div class="row g-3">
                        <?php foreach ($opcoes_orcamento as $val => $label): ?>
                            <div class="col-md-4 col-6">
                                <label class="match-card match-card-radio match-card-center">
                                    <input class="visually-hidden match-radio" type="radio" name="orcamento_anual" value="<?= htmlspecialchars($val) ?>" <?= ($orcamento_anual === $val) ? 'checked' : '' ?> required>
                                    <div class="match-card-inner">
                                        <div class="match-card-title"><?= htmlspecialchars($label) ?></div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- RELACIONAMENTO -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-diagram-3"></i> 2. Tipo de relacionamento preferido
                    </div>
                    <p class="form-section-desc">
                        Defina como sua organização prefere se conectar com os negócios de impacto.
                    </p>

                    <div class="row g-3">
                        <?php foreach ($opcoes_relacionamento as $val => $label): ?>
                            <div class="col-md-6">
                                <label class="match-card match-card-radio">
                                    <input class="visually-hidden match-radio" type="radio" name="tipo_relacionamento" value="<?= htmlspecialchars($val) ?>" <?= ($tipo_relacionamento === $val) ? 'checked' : '' ?> required>
                                    <div class="match-card-inner">
                                        <div class="match-card-content">
                                            <div class="match-card-title"><?= htmlspecialchars($label) ?></div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- HORIZONTE -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-calendar-range"></i> 3. Horizonte de engajamento
                    </div>
                    <p class="form-section-desc">
                        Informe o período desejado para o envolvimento com os negócios apoiados.
                    </p>

                    <div class="row g-3">
                        <?php foreach ($opcoes_horizonte as $val => $label): ?>
                            <div class="col-md-3 col-6">
                                <label class="match-card match-card-radio match-card-center">
                                    <input class="visually-hidden match-radio" type="radio" name="horizonte_engajamento" value="<?= htmlspecialchars($val) ?>" <?= ($horizonte_engajamento === $val) ? 'checked' : '' ?> required>
                                    <div class="match-card-inner">
                                        <div class="match-card-title"><?= htmlspecialchars($label) ?></div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- EIXOS -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-grid-3x3-gap"></i> Eixos Temáticos Adicionais
                    </div>
                    <p class="form-section-desc">
                        Selecione temas estratégicos que ajudam a refinar o seu radar de conexão.
                    </p>

                    <div class="row g-3">
                        <?php foreach ($lista_eixos as $eixo): 
                            $checked = in_array($eixo, $eixos_salvos) ? 'checked' : '';
                        ?>
                            <div class="col-md-4 col-sm-6">
                                <label class="match-card match-card-check">
                                    <input class="visually-hidden match-check" type="checkbox" name="eixos[]" value="<?= htmlspecialchars($eixo) ?>" <?= $checked ?>>
                                    <div class="match-card-inner">
                                        <div class="match-card-content">
                                            <div class="match-card-title"><?= htmlspecialchars($eixo) ?></div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- MATURIDADE -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-bar-chart-line"></i> Maturidade dos Negócios
                    </div>
                    <p class="form-section-desc">
                        Indique em quais estágios sua organização tem maior interesse de conexão.
                    </p>

                    <div class="row g-3">
                        <?php foreach ($lista_maturidades as $val => $label): 
                            $checked = in_array($val, $maturidade_salva) ? 'checked' : '';
                        ?>
                            <div class="col-md-6">
                                <label class="match-card match-card-check">
                                    <input class="visually-hidden match-check" type="checkbox" name="maturidade[]" value="<?= htmlspecialchars($val) ?>" <?= $checked ?>>
                                    <div class="match-card-inner">
                                        <div class="match-card-content">
                                            <div class="match-card-title"><?= htmlspecialchars($label) ?></div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SETORES E PERFIL -->
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="form-section h-100">
                            <div class="form-section-title">
                                <i class="bi bi-briefcase"></i> Setores / Indústrias
                            </div>
                            <p class="form-section-desc">
                                Marque os setores nos quais sua organização deseja atuar ou se aproximar.
                            </p>

                            <div class="match-check-list">
                                <?php foreach ($lista_setores as $setor): 
                                    $checked = in_array($setor, $setores_salvos) ? 'checked' : '';
                                ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="setores[]" value="<?= htmlspecialchars($setor) ?>" id="set_<?= md5($setor) ?>" <?= $checked ?>>
                                        <label class="form-check-label" for="set_<?= md5($setor) ?>">
                                            <?= htmlspecialchars($setor) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="form-section h-100">
                            <div class="form-section-title">
                                <i class="bi bi-stars"></i> Perfil de Impacto Desejado
                            </div>
                            <p class="form-section-desc">
                                Escolha os perfis de impacto com maior aderência à sua estratégia.
                            </p>

                            <div class="match-check-list">
                                <?php foreach ($lista_perfis as $perfil_item): 
                                    $checked = in_array($perfil_item, $perfil_salvo) ? 'checked' : '';
                                ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="perfil[]" value="<?= htmlspecialchars($perfil_item) ?>" id="perf_<?= md5($perfil_item) ?>" <?= $checked ?>>
                                        <label class="form-check-label" for="perf_<?= md5($perfil_item) ?>">
                                            <?= htmlspecialchars($perfil_item) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ALCANCE -->
                <div class="form-section mt-4">
                    <div class="form-section-title">
                        <i class="bi bi-globe-americas"></i> Alcance do Impacto
                    </div>
                    <p class="form-section-desc">
                        Defina o nível geográfico de impacto prioritário para suas conexões.
                    </p>

                    <div class="row g-3">
                        <?php foreach ($alcance_opcoes as $val => $label): ?>
                            <div class="col-md-3 col-6">
                                <label class="match-card match-card-radio match-card-center">
                                    <input class="visually-hidden match-radio" type="radio" name="alcance" value="<?= htmlspecialchars($val) ?>" <?= ($alcance === $val) ? 'checked' : '' ?> required>
                                    <div class="match-card-inner">
                                        <div class="match-card-title"><?= htmlspecialchars($label) ?></div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- AÇÕES -->
                <div class="match-form-actions">
                    <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold">
                        <i class="bi bi-floppy me-2"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.match-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const name = this.getAttribute('name');
            document.querySelectorAll('.match-radio[name="' + name + '"]').forEach(function (item) {
                const card = item.closest('.match-card');
                if (card) card.classList.remove('selected');
            });

            const currentCard = this.closest('.match-card');
            if (currentCard) currentCard.classList.add('selected');
        });

        if (radio.checked) {
            const currentCard = radio.closest('.match-card');
            if (currentCard) currentCard.classList.add('selected');
        }
    });

    document.querySelectorAll('.match-check').forEach(function (check) {
        check.addEventListener('change', function () {
            const currentCard = this.closest('.match-card');
            if (currentCard) {
                currentCard.classList.toggle('selected', this.checked);
            }
        });

        if (check.checked) {
            const currentCard = check.closest('.match-card');
            if (currentCard) currentCard.classList.add('selected');
        }
    });
});
</script>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>