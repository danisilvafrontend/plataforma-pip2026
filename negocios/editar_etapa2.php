<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$pageTitle = 'Editar Etapa 2 — Fundadores';

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ID do negócio
$negocio_id = (int)($_GET['id'] ?? $_SESSION['negocio_id'] ?? 0);
if ($negocio_id === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}
$_SESSION['negocio_id'] = $negocio_id;

// Busca negócio + permissão
if (isset($_SESSION['admin_id'])) {
    $stmt = $pdo->prepare("
        SELECT n.*, e.eh_fundador
        FROM negocios n
        JOIN empreendedores e ON n.empreendedor_id = e.id
        WHERE n.id = ?
    ");
    $stmt->execute([$negocio_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT n.*, e.eh_fundador
        FROM negocios n
        JOIN empreendedores e ON n.empreendedor_id = e.id
        WHERE n.id = ? AND n.empreendedor_id = ?
    ");
    $stmt->execute([$negocio_id, $_SESSION['user_id']]);
}
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$negocio) {
    die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);
}

// Regras fundador
$empreendedorEhFundador   = (int)$negocio['eh_fundador'];
$permiteFundadorPrincipal = $empreendedorEhFundador === 0;

// Fundadores já cadastrados
$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fundadorPrincipal = null;
$cofundadores      = [];
foreach ($fundadoresExistentes as $f) {
    if (strtolower(trim($f['tipo'])) === 'principal') {
        $fundadorPrincipal = $f;
    } else {
        $cofundadores[] = $f;
    }
}

// Garante 4 cofundadores
$max_cofundadores = 4;
for ($i = count($cofundadores); $i < $max_cofundadores; $i++) {
    $cofundadores[] = [
        'id' => null,
        'nome' => '',
        'sobrenome' => '',
        'cpf' => '',
        'email' => '',
        'celular' => '',
        'email_optin' => 0,
        'whatsapp_optin' => 0
    ];
}

// Dados default do fundador principal
$fp = $fundadorPrincipal ?: [
    'id' => null,
    'nome' => '',
    'sobrenome' => '',
    'cpf' => '',
    'email' => '',
    'celular' => '',
    'data_nascimento' => '',
    'genero' => '',
    'formacao' => '',
    'etnia' => '',
    'email_optin' => 0,
    'whatsapp_optin' => 0,
    'endereco_tipo' => 'negocio',
    'rua' => '',
    'numero' => '',
    'cep' => '',
    'municipio' => '',
    'estado' => ''
];

$generos   = ['Masculino','Feminino','Não Binário','Outro','Prefiro não informar'];
$formacoes = ['Ensino Fundamental','Ensino Médio Completo','Ensino Médio Incompleto','Ensino Superior Completo','Ensino Superior Incompleto','Pós-graduação','Mestrado'];
$etnias    = ['Branco','Pardo','Preto','Amarelo','Indígena','Prefiro não responder'];

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container py-4 emp-inner">

    <!-- Cabeçalho -->
    <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="emp-page-title mb-1">Editar: <?= htmlspecialchars($negocio['nome_fantasia']) ?></h1>
            <p class="emp-page-subtitle mb-0">Etapa 2 — Fundadores</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (!empty($negocio['inscricao_completa'])): ?>
                <a href="/negocios/confirmacao.php?id=<?= $negocio_id ?>" class="btn-emp-outline">
                    <i class="bi bi-card-checklist me-1"></i> Voltar à Revisão
                </a>
            <?php endif; ?>
            <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline">
                <i class="bi bi-arrow-left me-1"></i> Meus Negócios
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h5>Erros encontrados:</h5>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="/negocios/processar_etapa2.php" method="POST" id="formEtapa2">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="editar">

        <div class="row g-4">
            <!-- COLUNA PRINCIPAL -->
            <div class="col-12 col-lg-8">

                <!-- FUNDADOR PRINCIPAL -->
                <?php if ($permiteFundadorPrincipal): ?>
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-person-badge-fill me-2"></i> Fundador Principal
                            </h5>
                            <span class="small text-muted">
                                <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Dados privados
                            </span>
                        </div>
                        <div class="card-body">
                            <input type="hidden"
                                name="fundador_principal[id]"
                                value="<?= (int)($fp['id'] ?? 0) ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Nome *</label>
                                    <input type="text" class="form-control"
                                           name="fundador_principal[nome]"
                                           value="<?= htmlspecialchars($fp['nome'] ?? '', ENT_QUOTES) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Sobrenome *</label>
                                    <input type="text" class="form-control"
                                           name="fundador_principal[sobrenome]"
                                           value="<?= htmlspecialchars($fp['sobrenome'] ?? '', ENT_QUOTES) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">CPF *</label>
                                    <input type="text" class="form-control cpf-input"
                                           name="fundador_principal[cpf]"
                                           value="<?= htmlspecialchars($fp['cpf'] ?? '', ENT_QUOTES) ?>" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">E-mail *</label>
                                    <input type="email" class="form-control"
                                           name="fundador_principal[email]"
                                           value="<?= htmlspecialchars($fp['email'] ?? '', ENT_QUOTES) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Celular *</label>
                                    <input type="text" class="form-control"
                                           name="fundador_principal[celular]"
                                           value="<?= htmlspecialchars($fp['celular'] ?? '', ENT_QUOTES) ?>"
                                           placeholder="(00) 00000-0000" required>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Data de Nascimento *</label>
                                    <input type="date" class="form-control"
                                           name="fundador_principal[data_nascimento]"
                                           value="<?= htmlspecialchars($fp['data_nascimento'] ?? '', ENT_QUOTES) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Gênero *</label>
                                    <select class="form-select" name="fundador_principal[genero]" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($generos as $g): ?>
                                            <option value="<?= $g ?>" <?= ($fp['genero'] ?? '') === $g ? 'selected' : '' ?>>
                                                <?= $g ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Formação *</label>
                                    <select class="form-select" name="fundador_principal[formacao]" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($formacoes as $f_opt): ?>
                                            <option value="<?= $f_opt ?>" <?= ($fp['formacao'] ?? '') === $f_opt ? 'selected' : '' ?>>
                                                <?= $f_opt ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Etnia/Raça *</label>
                                    <select class="form-select" name="fundador_principal[etnia]" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($etnias as $e): ?>
                                            <option value="<?= $e ?>" <?= ($fp['etnia'] ?? '') === $e ? 'selected' : '' ?>>
                                                <?= $e ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Comunicações</label>
                                    <div class="mt-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="fundador_principal[email_optin]" value="1"
                                                <?= ($fp['email_optin'] ?? 0) ? 'checked' : '' ?>>
                                            <label class="form-check-label small">
                                                Aceito receber comunicações por e-mail
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="fundador_principal[whatsapp_optin]" value="1"
                                                <?= ($fp['whatsapp_optin'] ?? 0) ? 'checked' : '' ?>>
                                            <label class="form-check-label small">
                                                Aceito receber comunicações por WhatsApp
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Endereço -->
                            <div class="mt-3">
                                <label class="form-label fw-bold">Endereço</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input endereco-radio" type="radio"
                                               name="fundador_principal[endereco_tipo]" value="negocio"
                                            <?= ($fp['endereco_tipo'] ?? 'negocio') === 'negocio' ? 'checked' : '' ?>>
                                        <label class="form-check-label small">Mesmo endereço do negócio</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input endereco-radio" type="radio"
                                               name="fundador_principal[endereco_tipo]" value="residencial"
                                            <?= ($fp['endereco_tipo'] ?? '') === 'residencial' ? 'checked' : '' ?>>
                                        <label class="form-check-label small">Endereço residencial próprio</label>
                                    </div>
                                </div>

                                <div id="endereco_residencial" class="mt-3 <?= ($fp['endereco_tipo'] ?? '') !== 'residencial' ? 'd-none' : '' ?>">
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label">Logradouro</label>
                                            <input type="text" class="form-control"
                                                   name="fundador_principal[rua]"
                                                   value="<?= htmlspecialchars($fp['rua'] ?? '', ENT_QUOTES) ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Nº</label>
                                            <input type="text" class="form-control"
                                                   name="fundador_principal[numero]"
                                                   value="<?= htmlspecialchars($fp['numero'] ?? '', ENT_QUOTES) ?>">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label">CEP</label>
                                            <input type="text" class="form-control"
                                                   name="fundador_principal[cep]"
                                                   value="<?= htmlspecialchars($fp['cep'] ?? '', ENT_QUOTES) ?>">
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-8">
                                            <label class="form-label">Município</label>
                                            <input type="text" class="form-control"
                                                   name="fundador_principal[municipio]"
                                                   value="<?= htmlspecialchars($fp['municipio'] ?? '', ENT_QUOTES) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">UF</label>
                                            <input type="text" class="form-control"
                                                   name="fundador_principal[estado]" maxlength="2"
                                                   value="<?= htmlspecialchars($fp['estado'] ?? '', ENT_QUOTES) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Você é o fundador principal!</strong>
                        Como você marcou que é fundador no seu cadastro, não é necessário preencher esta seção.
                    </div>
                <?php endif; ?>

                <!-- COFUNDADORES -->
                <h4 class="mb-3">
                    <i class="bi bi-people-fill me-2"></i>
                    Cofundadores <span class="text-muted small fw-normal">(opcional — até 4)</span>
                </h4>

                <?php foreach ($cofundadores as $index => $cofundador): ?>
                    <div class="card mb-3 <?= empty($cofundador['id']) ? 'border-dashed' : '' ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                Cofundador <?= $index + 1 ?>
                                <?php if (!empty($cofundador['id'])): ?>
                                    <span class="badge bg-success ms-2">Cadastrado</span>
                                <?php else: ?>
                                    <span class="text-muted small fw-normal ms-2">Vazio — preencha se necessário</span>
                                <?php endif; ?>
                            </h6>
                           <?php if (!empty($cofundador['id'])): ?>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="cofundador[<?= $index ?>][remover]" value="0">
                                    <input class="form-check-input" type="checkbox"
                                        name="cofundador[<?= $index ?>][remover]"
                                        value="1"
                                        id="remover_cofundador_<?= $index ?>">
                                    <label class="form-check-label text-danger small" for="remover_cofundador_<?= $index ?>">
                                        Remover
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                           <input type="hidden"
                            name="cofundador[<?= $index ?>][id]"
                            value="<?= (int)($cofundador['id'] ?? 0) ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nome</label>
                                    <input type="text" class="form-control"
                                           name="cofundador[<?= $index ?>][nome]"
                                           value="<?= htmlspecialchars($cofundador['nome'] ?? '', ENT_QUOTES) ?>"
                                           placeholder="Nome">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sobrenome</label>
                                    <input type="text" class="form-control"
                                           name="cofundador[<?= $index ?>][sobrenome]"
                                           value="<?= htmlspecialchars($cofundador['sobrenome'] ?? '', ENT_QUOTES) ?>"
                                           placeholder="Sobrenome">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">CPF</label>
                                    <input type="text" class="form-control cpf-input"
                                           name="cofundador[<?= $index ?>][cpf]"
                                           value="<?= htmlspecialchars($cofundador['cpf'] ?? '', ENT_QUOTES) ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">E-mail</label>
                                    <input type="email" class="form-control"
                                           name="cofundador[<?= $index ?>][email]"
                                           value="<?= htmlspecialchars($cofundador['email'] ?? '', ENT_QUOTES) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Celular</label>
                                    <input type="text" class="form-control"
                                           name="cofundador[<?= $index ?>][celular]"
                                           value="<?= htmlspecialchars($cofundador['celular'] ?? '', ENT_QUOTES) ?>"
                                           placeholder="(00) 00000-0000">
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox"
                                               name="cofundador[<?= $index ?>][email_optin]" value="1"
                                            <?= ($cofundador['email_optin'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label small">
                                            Aceito receber atualizações via e-mail
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox"
                                               name="cofundador[<?= $index ?>][whatsapp_optin]" value="1"
                                            <?= ($cofundador['whatsapp_optin'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label small">
                                            Aceito receber novidades via WhatsApp
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex gap-3 mt-4 pt-3 border-top">
                    <button type="submit" class="btn-emp-primary">
                        <i class="bi bi-floppy me-2"></i> Salvar Alterações
                    </button>
                    <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline">
                        Cancelar
                    </a>
                </div>

            </div><!-- /col-lg-8 -->

            <!-- COLUNA LATERAL -->
            <div class="col-12 col-lg-4">
                <div class="emp-card mb-4">
                    <div class="emp-card-header"><i class="bi bi-info-circle"></i> Legenda</div>
                    <div class="d-flex align-items-center gap-2 mb-2 small">
                        <i class="bi bi-eye-slash text-danger-emphasis"></i>
                        <span style="color:#4a5e4f;">Campo <strong>privado</strong> — somente interno</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 small">
                        <i class="bi bi-eye text-secondary"></i>
                        <span style="color:#4a5e4f;">Campo <strong>público</strong> — visível na vitrine</span>
                    </div>
                </div>

                <div class="emp-card">
                    <div class="emp-card-header"><i class="bi bi-floppy-fill"></i> Salvar</div>
                    <p class="small mb-3" style="color:#9aab9d;">
                        Salve as alterações dos fundadores. Os demais dados do negócio não serão afetados.
                    </p>
                    <button type="submit" class="btn-emp-primary w-100 justify-content-center mb-2">
                        <i class="bi bi-floppy me-2"></i> Salvar Alterações
                    </button>
                    <?php if (!empty($negocio['inscricao_completa'])): ?>
                        <a href="/negocios/confirmacao.php?id=<?= $negocio_id ?>"
                           class="btn-emp-outline w-100 justify-content-center mb-2">
                            <i class="bi bi-card-checklist me-2"></i> Voltar à Revisão
                        </a>
                    <?php endif; ?>
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
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('input[name="fundador_principal[endereco_tipo]"]');
    const enderecoDiv = document.getElementById('endereco_residencial');
    if (radios.length && enderecoDiv) {
        radios.forEach(r => {
            r.addEventListener('change', function () {
                enderecoDiv.classList.toggle('d-none', this.value !== 'residencial');
            });
        });
    }
});
</script>

<style>
.border-dashed { border: 2px dashed #dee2e6 !important; }
</style>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>