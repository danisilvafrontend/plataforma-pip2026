<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'], $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id = (int)($_GET['id'] ?? 0);
if (!$negocio_id) {
    header('Location: /empreendedores/meus-negocios.php');
    exit;
}

$stmtNeg = $pdo->prepare("
    SELECT n.*, e.eh_fundador
    FROM negocios n
    JOIN empreendedores e ON n.empreendedor_id = e.id
    WHERE n.id = ? AND n.empreendedor_id = ?
");
$stmtNeg->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmtNeg->fetch(PDO::FETCH_ASSOC);

if (!$negocio) {
    header('Location: /empreendedores/meus-negocios.php');
    exit;
}

$empreendedorEhFundador   = (int)$negocio['eh_fundador'];
$permiteFundadorPrincipal = $empreendedorEhFundador === 0;

$stmtFund = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmtFund->execute([$negocio_id]);
$todosFundadores = $stmtFund->fetchAll(PDO::FETCH_ASSOC);

$fp = [
    'id'             => 0,
    'nome'           => '',
    'sobrenome'      => '',
    'cpf'            => '',
    'email'          => '',
    'celular'        => '',
    'data_nascimento'=> '',
    'genero'         => '',
    'formacao'       => '',
    'etnia'          => '',
    'orientacao_sexual' => '',
    'grupo_vulneravel'  => '',
    'email_optin'    => 0,
    'whatsapp_optin' => 0,
    'endereco_tipo'  => 'negocio',
    'rua'            => '',
    'numero'         => '',
    'cep'            => '',
    'municipio'      => '',
    'estado'         => ''
];
$cofundadores = [];

foreach ($todosFundadores as $fund) {
    if ($fund['tipo'] === 'principal') {
        $fp = array_merge($fp, $fund);
    } elseif ($fund['tipo'] === 'cofundador') {
        $cofundadores[] = $fund;
    }
}

$estados = ['AC'=>'Acre','AL'=>'Alagoas','AP'=>'Amapá','AM'=>'Amazonas','BA'=>'Bahia','CE'=>'Ceará','DF'=>'Distrito Federal','ES'=>'Espírito Santo','GO'=>'Goiás','MA'=>'Maranhão','MT'=>'Mato Grosso','MS'=>'Mato Grosso do Sul','MG'=>'Minas Gerais','PA'=>'Pará','PB'=>'Paraíba','PR'=>'Paraná','PE'=>'Pernambuco','PI'=>'Piauí','RJ'=>'Rio de Janeiro','RN'=>'Rio Grande do Norte','RS'=>'Rio Grande do Sul','RO'=>'Rondônia','RR'=>'Roraima','SC'=>'Santa Catarina','SP'=>'São Paulo','SE'=>'Sergipe','TO'=>'Tocantins'];
$formacoes = ['Ensino Fundamental','Ensino Médio Completo','Ensino Médio Incompleto','Ensino Superior Completo','Ensino Superior Incompleto','Pós-graduação','Mestrado'];
$etnias    = ['Branco','Pardo','Preto','Amarelo','Indígena','Prefiro não responder'];

$pageTitle = 'Editar Etapa 2 — Fundadores';
include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<?php if (!empty($_SESSION['errors_etapa2'])): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4">
    <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Corrija os erros:</h6>
    <ul class="mb-0 ps-3 small">
        <?php foreach ($_SESSION['errors_etapa2'] as $erro): ?>
            <li><?= htmlspecialchars($erro) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['errors_etapa2']); ?>
<?php endif; ?>

<div class="container mt-4 mb-5" style="max-width:900px;">

    <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-3">
        <div>
            <h1 class="emp-page-title mb-1">Editar Etapa 2 — Fundadores</h1>
            <p class="emp-page-subtitle mb-0"><?= htmlspecialchars($negocio['nome_fantasia']) ?></p>
        </div>
        <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline">
            <i class="bi bi-arrow-left me-1"></i> Meus Negócios
        </a>
    </div>

    <?php
        $etapaAtual = 2;
        include __DIR__ . '/../app/views/partials/progress.php';
    ?>

    <?php if ($empreendedorEhFundador): ?>
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Você já foi cadastrado como fundador principal.</strong>
            Edite apenas cofundadores se necessário.
        </div>
    <?php endif; ?>

    <form action="/negocios/processar_etapa2.php" method="post" id="formEtapa2">
        <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
        <input type="hidden" name="modo" value="edicao">
        <input type="hidden" name="fundador_id" value="<?= (int)$fp['id'] ?>">

        <?php if ($permiteFundadorPrincipal): ?>
        <!-- ── Fundador Principal ── -->
        <div class="emp-card mb-4">
            <div class="emp-card-header">
                <i class="bi bi-person-badge-fill"></i> Fundador Principal
            </div>
            <div class="p-3">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nome *</label>
                        <input type="text" class="form-control" name="fundador_principal[nome]"
                               value="<?= htmlspecialchars($fp['nome']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Sobrenome *</label>
                        <input type="text" class="form-control" name="fundador_principal[sobrenome]"
                               value="<?= htmlspecialchars($fp['sobrenome']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">CPF *</label>
                        <input type="text" class="form-control cpf-input" name="fundador_principal[cpf]"
                               value="<?= htmlspecialchars($fp['cpf']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">E-mail *</label>
                        <input type="email" class="form-control" name="fundador_principal[email]"
                               value="<?= htmlspecialchars($fp['email']) ?>" required>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox"
                                   name="fundador_principal[email_optin]" value="1"
                                <?= $fp['email_optin'] ? 'checked' : '' ?>>
                            <label class="form-check-label small">Aceito receber atualizações via e-mail</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Celular *</label>
                        <input type="text" class="form-control" name="fundador_principal[celular]"
                               value="<?= htmlspecialchars($fp['celular']) ?>" required>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox"
                                   name="fundador_principal[whatsapp_optin]" value="1"
                                <?= $fp['whatsapp_optin'] ? 'checked' : '' ?>>
                            <label class="form-check-label small">Aceito receber novidades via WhatsApp</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Data de Nascimento *</label>
                        <input type="date" class="form-control" name="fundador_principal[data_nascimento]"
                               value="<?= htmlspecialchars($fp['data_nascimento']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Gênero *</label>
                        <select class="form-select" name="fundador_principal[genero]" required>
                            <option value="">Selecione...</option>
                            <?php foreach (['Masculino','Feminino','Não Binário','Outros'] as $g): ?>
                                <option value="<?= $g ?>" <?= $fp['genero'] === $g ? 'selected' : '' ?>>
                                    <?= $g ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Formação *</label>
                        <select class="form-select" name="fundador_principal[formacao]" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($formacoes as $fm): ?>
                                <option value="<?= $fm ?>" <?= ($fp['formacao'] ?? '') === $fm ? 'selected' : '' ?>>
                                    <?= $fm ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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

                                <!-- ── Orientação Sexual ── -->
                                <div class="col-12">
                                  <label class="form-label fw-bold">Qual sua orientação sexual? *</label>
                                  <?php
                                  $oriOpts  = ['Heterossexual','Homossexual','Bissexual','Assexual','Prefiro não responder'];
                                  $oriValDB = $fp['orientacao_sexual'] ?? '';
                                  $oriEhOutra = $oriValDB !== '' && !in_array($oriValDB, $oriOpts);
                                  ?>
                                  <div class="d-flex flex-wrap gap-3 mt-1">
                                    <?php foreach ($oriOpts as $ori): ?>
                                      <div class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="fundador_principal[orientacao_sexual]"
                                               value="<?= $ori ?>"
                                               <?= $oriValDB === $ori ? 'checked' : '' ?> required>
                                        <label class="form-check-label"><?= $ori ?></label>
                                      </div>
                                    <?php endforeach; ?>
                                    <div class="form-check">
                                      <input class="form-check-input" type="radio"
                                             name="fundador_principal[orientacao_sexual]"
                                             value="Outra" id="fp_ori_outra"
                                             <?= $oriEhOutra ? 'checked' : '' ?>
                                             onchange="document.getElementById('fp_ori_outra_texto').classList.remove('d-none')">
                                      <label class="form-check-label" for="fp_ori_outra">Outra. Qual?</label>
                                    </div>
                                  </div>
                                  <div id="fp_ori_outra_texto" class="mt-2 <?= $oriEhOutra ? '' : 'd-none' ?>" style="max-width:320px;">
                                    <input type="text" name="fundador_principal[orientacao_sexual_outra]"
                                           class="form-control form-control-sm"
                                           placeholder="Digite sua orientação sexual"
                                           value="<?= $oriEhOutra ? htmlspecialchars($oriValDB) : '' ?>">
                                  </div>
                                </div>

                                <!-- ── Grupo Vulnerável ── -->
                                <div class="col-12">
                                  <label class="form-label fw-bold">Você pertence a algum desses grupos? *</label>
                                  <div class="d-flex flex-wrap gap-3 mt-1">
                                    <?php foreach (['Pessoa com deficiência','Pessoa refugiada','Não'] as $grp): ?>
                                      <div class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="fundador_principal[grupo_vulneravel]"
                                               value="<?= $grp ?>"
                                               <?= ($fp['grupo_vulneravel'] ?? '') === $grp ? 'checked' : '' ?> required>
                                        <label class="form-check-label"><?= $grp ?></label>
                                      </div>
                                    <?php endforeach; ?>
                                  </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Comunicações</label>
                                    <div class="mt-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="fundador_principal[email_optin]" value="1"
                                                <?= ($fp['email_optin'] ?? 0) ? 'checked' : '' ?>>
                                            <label class="form-check-label small">
                                                Aceito receber atualizações por e-mail
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="fundador_principal[whatsapp_optin]" value="1"
                                                <?= ($fp['whatsapp_optin'] ?? 0) ? 'checked' : '' ?>>
                                            <label class="form-check-label small">
                                                Aceito receber novidades via WhatsApp
                                            </label>
                                        </div>
                                    </div>
                                </div>
                    </div>

                    <!-- Endereço -->
                    <div class="col-12">
                        <label class="form-label fw-bold">Endereço</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input endereco-radio" type="radio"
                                       name="fundador_principal[endereco_tipo]" value="negocio"
                                    <?= ($fp['endereco_tipo'] ?? 'negocio') === 'negocio' ? 'checked' : '' ?>>
                                <label class="form-check-label small">Usar endereço do negócio</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input endereco-radio" type="radio"
                                       name="fundador_principal[endereco_tipo]" value="residencial"
                                    <?= ($fp['endereco_tipo'] ?? '') === 'residencial' ? 'checked' : '' ?>>
                                <label class="form-check-label small">Cadastrar endereço residencial</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 campos-residencial <?= ($fp['endereco_tipo'] ?? 'negocio') === 'residencial' ? '' : 'd-none' ?>">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">CEP</label>
                                <input type="text" class="form-control" name="fundador_principal[cep]"
                                       value="<?= htmlspecialchars($fp['cep'] ?? '') ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Rua</label>
                                <input type="text" class="form-control" name="fundador_principal[rua]"
                                       value="<?= htmlspecialchars($fp['rua'] ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Número</label>
                                <input type="text" class="form-control" name="fundador_principal[numero]"
                                       value="<?= htmlspecialchars($fp['numero'] ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Complemento</label>
                                <input type="text" class="form-control" name="fundador_principal[complemento]"
                                       value="<?= htmlspecialchars($fp['complemento'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Município</label>
                                <input type="text" class="form-control" name="fundador_principal[municipio]"
                                       value="<?= htmlspecialchars($fp['municipio'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Estado</label>
                                <select class="form-select" name="fundador_principal[estado]">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($estados as $uf => $nome): ?>
                                        <option value="<?= $uf ?>" <?= ($fp['estado'] ?? '') === $uf ? 'selected' : '' ?>>
                                            <?= $nome ?> (<?= $uf ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Cofundadores ── -->
        <div class="emp-card mb-4">
            <div class="emp-card-header">
                <i class="bi bi-people-fill"></i> Cofundadores
                <span class="ms-auto small" style="color:#9aab9d; font-weight:400;">Máximo 4</span>
            </div>
            <div class="p-3">

                <?php foreach ($cofundadores as $idx => $cf): ?>
                <div class="emp-card mb-3 p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="fw-bold" style="color:#1E3425;">
                            <i class="bi bi-person-fill me-2"></i>
                            Cofundador <?= $idx + 1 ?>
                        </span>
                        <div class="form-check form-switch ms-auto">
                            <input class="form-check-input" type="checkbox"
                                   name="cofundador[<?= $idx ?>][remover]" value="1"
                                   id="remover_cf_<?= $idx ?>">
                            <label class="form-check-label small text-danger" for="remover_cf_<?= $idx ?>">
                                Remover
                            </label>
                        </div>
                    </div>
                    <input type="hidden" name="cofundador[<?= $idx ?>][id]" value="<?= (int)$cf['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nome *</label>
                            <input type="text" class="form-control"
                                   name="cofundador[<?= $idx ?>][nome]"
                                   value="<?= htmlspecialchars($cf['nome']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sobrenome *</label>
                            <input type="text" class="form-control"
                                   name="cofundador[<?= $idx ?>][sobrenome]"
                                   value="<?= htmlspecialchars($cf['sobrenome']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">CPF *</label>
                            <input type="text" class="form-control cpf-input"
                                   name="cofundador[<?= $idx ?>][cpf]"
                                   value="<?= htmlspecialchars($cf['cpf']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">E-mail *</label>
                            <input type="email" class="form-control"
                                   name="cofundador[<?= $idx ?>][email]"
                                   value="<?= htmlspecialchars($cf['email']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Celular *</label>
                            <input type="text" class="form-control"
                                   name="cofundador[<?= $idx ?>][celular]"
                                   value="<?= htmlspecialchars($cf['celular']) ?>" required>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Novos cofundadores via JS -->
                <div id="cofundadores-container"></div>
                <button type="button" class="btn-emp-outline mt-2" id="add-cofundador">
                    <i class="bi bi-plus-lg me-2"></i> Adicionar Cofundador
                </button>
            </div>
        </div>

        <!-- Ações -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-5">
            <a href="/negocios/editar_etapa1.php?id=<?= $negocio_id ?>" class="btn-emp-outline">
                <i class="bi bi-arrow-left me-1"></i> Voltar
            </a>
            <button type="submit" name="acao" value="salvar" class="btn-emp-primary">
                <i class="bi bi-floppy me-2"></i> Salvar Alterações
            </button>
        </div>

    </form>
</div>

<script>
// Toggle endereço residencial
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.endereco-radio').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const campos = this.closest('.col-12').nextElementSibling;
            if (campos && campos.classList.contains('campos-residencial')) {
                campos.classList.toggle('d-none', this.value !== 'residencial');
            }
        });
    });
});

// Cofundadores dinâmicos
(function () {
    const container = document.getElementById('cofundadores-container');
    const addBtn    = document.getElementById('add-cofundador');
    let count = <?= count($cofundadores) ?>;
    const MAX = 4;
    const formacoes = <?= json_encode($formacoes) ?>;
    const etnias    = <?= json_encode($etnias) ?>;
    const estados   = <?= json_encode($estados) ?>;

    function buildOptsSimple(arr, sel) {
        return arr.map(v => `<option${v===sel?' selected':''}>${v}</option>`).join('');
    }
    function buildEstados(map, sel) {
        return Object.entries(map).map(([uf,nome]) =>
            `<option value="${uf}"${uf===sel?' selected':''}>${nome} (${uf})</option>`
        ).join('');
    }

    addBtn.addEventListener('click', function () {
        if (count >= MAX) {
            alert('Máximo de 4 cofundadores.');
            return;
        }
        count++;
        const bloco = document.createElement('div');
        bloco.className = 'emp-card mb-3 p-3';
        bloco.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="fw-bold" style="color:#1E3425;">
                    <i class="bi bi-person-plus me-2"></i> Cofundador ${count}
                </span>
                <button type="button" class="btn-emp-icon text-danger remove-cf">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nome *</label>
                    <input type="text" class="form-control" name="cofundador[${count}][nome]" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Sobrenome *</label>
                    <input type="text" class="form-control" name="cofundador[${count}][sobrenome]" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">CPF *</label>
                    <input type="text" class="form-control cpf-input" name="cofundador[${count}][cpf]" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">E-mail *</label>
                    <input type="email" class="form-control" name="cofundador[${count}][email]" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Celular *</label>
                    <input type="text" class="form-control" name="cofundador[${count}][celular]" required>
                </div>
                <div class="col-12">
                  <label class="form-label fw-bold">Qual sua orientação sexual? *</label>
                  <div class="d-flex flex-wrap gap-3">
                    ${['Heterossexual','Homossexual','Bissexual','Assexual','Prefiro não responder'].map(o =>
                      `<div class="form-check"><input class="form-check-input" type="radio" name="cofundador[${count}][orientacao_sexual]" value="${o}" required><label class="form-check-label">${o}</label></div>`
                    ).join('')}
                    <div class="form-check"><input class="form-check-input" type="radio" name="cofundador[${count}][orientacao_sexual]" value="Outra" id="cf_ori_outra_${count}" onchange="document.getElementById('cf_ori_texto_${count}').classList.remove('d-none')"><label class="form-check-label" for="cf_ori_outra_${count}">Outra. Qual?</label></div>
                  </div>
                  <div id="cf_ori_texto_${count}" class="mt-2 d-none" style="max-width:320px;">
                    <input type="text" name="cofundador[${count}][orientacao_sexual_outra]" class="form-control form-control-sm" placeholder="Digite sua orientação sexual">
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label fw-bold">Você pertence a algum desses grupos? *</label>
                  <div class="d-flex flex-wrap gap-3">
                    ${['Pessoa com deficiência','Pessoa refugiada','Não'].map(g =>
                      `<div class="form-check"><input class="form-check-input" type="radio" name="cofundador[${count}][grupo_vulneravel]" value="${g}" required><label class="form-check-label">${g}</label></div>`
                    ).join('')}
                  </div>
                </div>
            </div>
        `;
        container.appendChild(bloco);
        bloco.querySelectorAll('.cpf-input').forEach(bindCpfMask);
        bloco.querySelector('.remove-cf').addEventListener('click', () => { bloco.remove(); count--; });
    });

    function fmtCPF(v) {
        v = v.replace(/\D/g,'').slice(0,11);
        return v.replace(/(\d{3})(\d)/,'$1.$2')
                .replace(/(\d{3})(\d)/,'$1.$2')
                .replace(/(\d{3})(\d{1,2})$/,'$1-$2');
    }
    function isValidCPF(cpf) {
        cpf = cpf.replace(/\D/g,'');
        if (cpf.length!==11||/^(\d)\1{10}$/.test(cpf)) return false;
        let s=0,r;
        for(let i=0;i<9;i++) s+=parseInt(cpf[i])*(10-i);
        r=s%11; let d1=(r<2)?0:11-r;
        if(d1!==parseInt(cpf[9])) return false;
        s=0;
        for(let i=0;i<10;i++) s+=parseInt(cpf[i])*(11-i);
        r=s%11; let d2=(r<2)?0:11-r;
        return d2===parseInt(cpf[10]);
    }
    function bindCpfMask(input) {
        input.addEventListener('input', function () { this.value = fmtCPF(this.value); });
        input.addEventListener('blur', function () {
            const d = this.value.replace(/\D/g,'');
            if (d && !isValidCPF(d)) {
                this.classList.add('is-invalid');
                let err = this.parentNode.querySelector('.invalid-feedback');
                if (!err) { err = document.createElement('div'); err.className='invalid-feedback'; this.parentNode.appendChild(err); }
                err.textContent = 'CPF inválido.';
            } else {
                this.classList.remove('is-invalid','is-valid');
                const err = this.parentNode.querySelector('.invalid-feedback');
                if (err) err.remove();
            }
        });
    }
    document.querySelectorAll('.cpf-input').forEach(bindCpfMask);

    // Bloqueia submit se CPF inválido
    document.getElementById('formEtapa2').addEventListener('submit', function (e) {
        let invalido = false;
        document.querySelectorAll('.cpf-input').forEach(function (input) {
            const d = input.value.replace(/\D/g,'');
            if (d && !isValidCPF(d)) { invalido = true; input.classList.add('is-invalid'); }
        });
        if (invalido) {
            e.preventDefault();
            alert('Corrija todos os CPFs inválidos antes de continuar.');
        }
    });

})();
</script>

<style>
    .emp-cofundador-bloco input:disabled,
    .emp-cofundador-bloco select:disabled {
        background-color: #dee2e6 !important; }
</style>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>