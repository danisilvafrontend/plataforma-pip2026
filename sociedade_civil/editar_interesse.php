<?php
session_start();

if (empty($_SESSION['logado']) || $_SESSION['usuario_tipo'] !== 'sociedade_civil') {
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

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM sociedade_civil WHERE id = ?");
$stmt->execute([$usuarioId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Usuário não encontrado.');
}

function jsonParaArray($valor): array
{
    if (empty($valor)) {
        return [];
    }

    $array = json_decode($valor, true);
    return is_array($array) ? $array : [];
}

$interessesSalvos = jsonParaArray($user['interesses'] ?? null);
$odsSalvos = jsonParaArray($user['ods'] ?? null);
$maturidadeSalva = jsonParaArray($user['maturidade'] ?? null);
$setoresSalvos = jsonParaArray($user['setores'] ?? null);
$perfilImpactoSalvo = jsonParaArray($user['perfil_impacto'] ?? null);
$alcanceSalvo = '';

if (!empty($user['alcance'])) {
    $alcanceDecodificado = json_decode($user['alcance'], true);
    if (is_string($alcanceDecodificado)) {
        $alcanceSalvo = $alcanceDecodificado;
    }
}

$temasColuna1 = [
    'Meio Ambiente e Clima',
    'Água e Oceanos',
    'Biodiversidade e Florestas',
    'Economia Circular',
    'Energia Limpa',
    'Segurança Alimentar',
    'Saúde e Bem-Estar',
    'Educação',
];

$temasColuna2 = [
    'Igualdade de Gênero',
    'Equidade Racial',
    'Trabalho e Renda',
    'Cidades Sustentáveis',
    'Inovação e Tecnologia',
    'Inclusão Social',
    'Governança e Transparência',
    'Parcerias e Investimento Social',
];

$maturidadesColuna1 = [
    'Ideação' => 'Ideação (começando agora)',
    'Operação' => 'Operação (modelo sendo testado)',
];

$maturidadesColuna2 = [
    'Tração / Escala' => 'Tração / Escala (já operando e expandindo)',
    'Dinamizador' => 'Dinamizador (impacto consolidado e ampliando alcance)',
];

$setoresColuna1 = [
    'Tecnologia',
    'Agronegócio sustentável',
    'Saúde',
    'Educação',
    'Finanças de impacto',
    'Energia',
    'Moda sustentável',
    'Alimentação',
];

$setoresColuna2 = [
    'Construção civil',
    'Cultura',
    'ESG corporativo',
    'Startups',
    'Negócios sociais',
    'Cooperativas',
    'ONGs',
];

$perfilImpactoColuna1 = [
    'Social',
    'Ambiental',
    'Social + Ambiental',
    'Inovação tecnológica',
    'Base comunitária',
];

$perfilImpactoColuna2 = [
    'Liderado por mulheres',
    'Liderado por jovens',
    'Impacto regional / local',
    'Impacto global',
];

$opcoesAlcance = ['Local', 'Nacional', 'Global', 'Todos'];

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $interesses = $_POST['interesses'] ?? [];
    $ods = $_POST['ods'] ?? [];
    $maturidade = $_POST['maturidade'] ?? [];
    $setores = $_POST['setores'] ?? [];
    $perfilImpacto = $_POST['perfil_impacto'] ?? [];
    $alcance = trim($_POST['alcance'] ?? '');

    $interesses = is_array($interesses) ? array_values($interesses) : [];
    $ods = is_array($ods) ? array_values($ods) : [];
    $maturidade = is_array($maturidade) ? array_values($maturidade) : [];
    $setores = is_array($setores) ? array_values($setores) : [];
    $perfilImpacto = is_array($perfilImpacto) ? array_values($perfilImpacto) : [];

    if ($alcance !== '' && !in_array($alcance, $opcoesAlcance, true)) {
        $erros[] = 'Selecione um alcance válido.';
    }

    if (empty($erros)) {
        $sql = "UPDATE sociedade_civil
                SET interesses = ?,
                    ods = ?,
                    maturidade = ?,
                    setores = ?,
                    perfil_impacto = ?,
                    alcance = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            json_encode(array_values($interesses), JSON_UNESCAPED_UNICODE),
            json_encode(array_values($ods), JSON_UNESCAPED_UNICODE),
            json_encode(array_values($maturidade), JSON_UNESCAPED_UNICODE),
            json_encode(array_values($setores), JSON_UNESCAPED_UNICODE),
            json_encode(array_values($perfilImpacto), JSON_UNESCAPED_UNICODE),
            $alcance !== '' ? json_encode($alcance, JSON_UNESCAPED_UNICODE) : null,
            $usuarioId
        ]);

        header("Location: minha_conta.php?msg=sucesso");
        exit;
    }

    $interessesSalvos = $interesses;
    $odsSalvos = $ods;
    $maturidadeSalva = $maturidade;
    $setoresSalvos = $setores;
    $perfilImpactoSalvo = $perfilImpacto;
    $alcanceSalvo = $alcance;
}

$stmtOds = $pdo->query("
    SELECT id, n_ods, nome, icone_url,
           CAST(REPLACE(n_ods, 'ODS ', '') AS UNSIGNED) AS numero_ods
    FROM ods
    ORDER BY CAST(REPLACE(n_ods, 'ODS ', '') AS UNSIGNED) ASC
");
$todasOds = $stmtOds->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="minha-conta-page py-4 py-lg-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-12 col-lg-4 col-xl-3">
                <?php
                $nomeCompletoSidebar = trim(($user['nome'] ?? '') . ' ' . ($user['sobrenome'] ?? ''));
                $iniciaisSidebar = strtoupper(
                    mb_substr($user['nome'] ?? '', 0, 1) .
                    mb_substr($user['sobrenome'] ?? '', 0, 1)
                );
                if (trim($iniciaisSidebar) === '') {
                    $iniciaisSidebar = 'SC';
                }
                $emailSidebar = $user['email'] ?? '';
                $tipoContaSidebar = 'Sociedade Civil';
                $menuAtivoSidebar = 'meus-dados';

                include __DIR__ . '/../app/views/sociedade/sidebar.php';
                ?>
            </div>

            <div class="col-12 col-lg-8 col-xl-9">
                <section class="conta-main-card">
                    <div class="conta-main-header">
                        <div>
                            <h2>Editar interesses e perfil</h2>
                            <p>Atualize os temas, ODS, maturidade, setores e preferências de impacto do seu cadastro.</p>
                        </div>

                        <a href="minha_conta.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>Voltar
                        </a>
                    </div>

                    <div class="conta-main-body">
                        <?php if (!empty($erros)): ?>
                            <div class="alert alert-danger rounded-4">
                                <div class="fw-semibold mb-2">Encontramos alguns pontos para corrigir:</div>
                                <ul class="mb-0">
                                    <?php foreach ($erros as $erro): ?>
                                        <li><?= htmlspecialchars($erro) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="" method="post">
                            <div class="conta-section">
                                <h3>Temas de interesse</h3>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="cadastro-check-grid">
                                            <?php foreach ($temasColuna1 as $tema): ?>
                                                <label class="cadastro-check-card">
                                                    <input class="form-check-input" type="checkbox" name="interesses[]" value="<?= htmlspecialchars($tema) ?>"
                                                        <?= in_array($tema, $interessesSalvos, true) ? 'checked' : '' ?>>
                                                    <span><?= htmlspecialchars($tema) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="cadastro-check-grid">
                                            <?php foreach ($temasColuna2 as $tema): ?>
                                                <label class="cadastro-check-card">
                                                    <input class="form-check-input" type="checkbox" name="interesses[]" value="<?= htmlspecialchars($tema) ?>"
                                                        <?= in_array($tema, $interessesSalvos, true) ? 'checked' : '' ?>>
                                                    <span><?= htmlspecialchars($tema) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="conta-section">
                                <h3>ODS de interesse</h3>
                                <div class="row g-3">
                                    <?php foreach ($todasOds as $ods): ?>
                                        <div class="col-12 col-md-6">
                                            <label class="cadastro-ods-card">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    name="ods[]"
                                                    value="<?= (int)$ods['id'] ?>"
                                                    <?= in_array((string)$ods['id'], array_map('strval', $odsSalvos), true) ? 'checked' : '' ?>
                                                >

                                                <span class="cadastro-ods-card__content">
                                                    <?php if (!empty($ods['icone_url'])): ?>
                                                        <img
                                                            src="<?= htmlspecialchars($ods['icone_url']) ?>"
                                                            alt="ODS <?= (int)$ods['numero_ods'] ?>"
                                                            class="cadastro-ods-card__icon"
                                                        >
                                                    <?php endif; ?>

                                                    <span class="cadastro-ods-card__text">
                                                        <strong>ODS <?= (int)$ods['numero_ods'] ?> - <?= htmlspecialchars($ods['nome']) ?></strong>
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="conta-section">
                                <h3>Estágio de maturidade</h3>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="cadastro-check-grid">
                                            <?php foreach ($maturidadesColuna1 as $valor => $label): ?>
                                                <label class="cadastro-check-card">
                                                    <input class="form-check-input" type="checkbox" name="maturidade[]" value="<?= htmlspecialchars($valor) ?>"
                                                        <?= in_array($valor, $maturidadeSalva, true) ? 'checked' : '' ?>>
                                                    <span><?= htmlspecialchars($label) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="cadastro-check-grid">
                                            <?php foreach ($maturidadesColuna2 as $valor => $label): ?>
                                                <label class="cadastro-check-card">
                                                    <input class="form-check-input" type="checkbox" name="maturidade[]" value="<?= htmlspecialchars($valor) ?>"
                                                        <?= in_array($valor, $maturidadeSalva, true) ? 'checked' : '' ?>>
                                                    <span><?= htmlspecialchars($label) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="conta-section">
                                <h3>Setores de interesse</h3>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="cadastro-check-grid">
                                            <?php foreach ($setoresColuna1 as $setor): ?>
                                                <label class="cadastro-check-card">
                                                    <input class="form-check-input" type="checkbox" name="setores[]" value="<?= htmlspecialchars($setor) ?>"
                                                        <?= in_array($setor, $setoresSalvos, true) ? 'checked' : '' ?>>
                                                    <span><?= htmlspecialchars($setor) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="cadastro-check-grid">
                                            <?php foreach ($setoresColuna2 as $setor): ?>
                                                <label class="cadastro-check-card">
                                                    <input class="form-check-input" type="checkbox" name="setores[]" value="<?= htmlspecialchars($setor) ?>"
                                                        <?= in_array($setor, $setoresSalvos, true) ? 'checked' : '' ?>>
                                                    <span><?= htmlspecialchars($setor) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="conta-section">
                                <h3>Perfil de impacto</h3>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="cadastro-check-grid">
                                            <?php foreach ($perfilImpactoColuna1 as $item): ?>
                                                <label class="cadastro-check-card">
                                                    <input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="<?= htmlspecialchars($item) ?>"
                                                        <?= in_array($item, $perfilImpactoSalvo, true) ? 'checked' : '' ?>>
                                                    <span><?= htmlspecialchars($item) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="cadastro-check-grid">
                                            <?php foreach ($perfilImpactoColuna2 as $item): ?>
                                                <label class="cadastro-check-card">
                                                    <input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="<?= htmlspecialchars($item) ?>"
                                                        <?= in_array($item, $perfilImpactoSalvo, true) ? 'checked' : '' ?>>
                                                    <span><?= htmlspecialchars($item) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="conta-section">
                                <h3>Alcance preferido</h3>
                                <div class="cadastro-radio-grid">
                                    <?php foreach ($opcoesAlcance as $opcao): ?>
                                        <label class="cadastro-radio-card">
                                            <input class="form-check-input" type="radio" name="alcance" value="<?= htmlspecialchars($opcao) ?>"
                                                <?= $alcanceSalvo === $opcao ? 'checked' : '' ?>>
                                            <span><?= htmlspecialchars($opcao) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end mt-4">
                                <a href="editar_conta.php" class="btn btn-light">Voltar para dados pessoais</a>
                                <button type="submit" class="btn btn-primary px-4">Salvar alterações</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>