<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$pageTitle = 'Etapa 2 — Fundadores';

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'], $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id = (int)($_GET['id'] ?? $_SESSION['negocio_id'] ?? 0);
if (!$negocio_id) { header("Location: /empreendedores/meus-negocios.php"); exit; }
$_SESSION['negocio_id'] = $negocio_id;

$stmt = $pdo->prepare("
    SELECT n.*, e.eh_fundador
    FROM negocios n
    JOIN empreendedores e ON n.empreendedor_id = e.id
    WHERE n.id = ? AND n.empreendedor_id = ?
");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$negocio) { header("Location: /empreendedores/meus-negocios.php"); exit; }

$empreendedorEhFundador   = (int)$negocio['eh_fundador'];
$permiteFundadorPrincipal = $empreendedorEhFundador === 0;

$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$estados = ['AC'=>'Acre','AL'=>'Alagoas','AP'=>'Amapá','AM'=>'Amazonas','BA'=>'Bahia','CE'=>'Ceará','DF'=>'Distrito Federal','ES'=>'Espírito Santo','GO'=>'Goiás','MA'=>'Maranhão','MT'=>'Mato Grosso','MS'=>'Mato Grosso do Sul','MG'=>'Minas Gerais','PA'=>'Pará','PB'=>'Paraíba','PR'=>'Paraná','PE'=>'Pernambuco','PI'=>'Piauí','RJ'=>'Rio de Janeiro','RN'=>'Rio Grande do Norte','RS'=>'Rio Grande do Sul','RO'=>'Rondônia','RR'=>'Roraima','SC'=>'Santa Catarina','SP'=>'São Paulo','SE'=>'Sergipe','TO'=>'Tocantins'];

$formacoes = ['Ensino Fundamental','Ensino Médio Completo','Ensino Médio Incompleto','Ensino Superior Completo','Ensino Superior Incompleto','Pós-graduação','Mestrado'];
$etnias    = ['Branco(a)'=>'Branco','Preto(a)'=>'Preto','Pardo(a)'=>'Pardo','Amarelo(a)'=>'Amarelo','Indígena'=>'Indígena','Prefiro não responder'=>'Prefiro não responder'];

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

<div class="container mt-4 mb-5" style="max-width: 860px;">

<!-- Cabeçalho -->
<div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-3">
  <div>
    <h1 class="emp-page-title mb-1">Etapa 2 — Fundadores</h1>
    <p class="emp-page-subtitle mb-0"><?= htmlspecialchars($negocio['nome_fantasia']) ?></p>
  </div>
  <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline">
    <i class="bi bi-arrow-left me-1"></i> Meus Negócios
  </a>
</div>

<?php
  $etapaAtual = 2;
  include __DIR__ . '/../app/views/partials/progress.php';
  include __DIR__ . '/../app/views/partials/intro_text_fundadores.php';
?>

<?php if ($empreendedorEhFundador): ?>
  <div class="alert alert-info mb-4">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Você já foi cadastrado como fundador principal.</strong>
    Preencha apenas cofundadores se necessário, ou clique em "Não tenho cofundadores, avançar".
  </div>
<?php endif; ?>

<form action="/negocios/processar_etapa2.php" method="post" id="formEtapa2">
  <input type="hidden" name="negocio_id" value="<?= (int)$_SESSION['negocio_id'] ?>">
  <input type="hidden" name="modo" value="cadastro">

  <?php if ($permiteFundadorPrincipal): ?>
  <!-- ── Fundador Principal ── -->
  <div class="emp-card mb-4">
    <div class="emp-card-header">
      <i class="bi bi-person-badge-fill"></i> Fundador Principal
    </div>
    <div class="p-3">
      <div class="row g-3">

        <div class="col-md-6">
          <label class="form-label fw-600">
            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Nome *
          </label>
          <input type="text" name="fundador_principal[nome]" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-600">
            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Sobrenome *
          </label>
          <input type="text" name="fundador_principal[sobrenome]" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-600">
            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> CPF *
          </label>
          <input type="text" name="fundador_principal[cpf]" class="form-control cpf-input" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600">
            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> E-mail *
          </label>
          <input type="email" name="fundador_principal[email]" class="form-control" required>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="fundador_principal[email_optin]" value="1">
            <label class="form-check-label small">Aceito receber atualizações via e-mail</label>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600">
            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Celular *
          </label>
          <input type="text" name="fundador_principal[celular]" class="form-control" required>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="fundador_principal[whatsapp_optin]" value="1">
            <label class="form-check-label small">Aceito receber novidades via WhatsApp</label>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-600">
            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Data de Nascimento *
          </label>
          <input type="date" name="fundador_principal[data_nascimento]" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600">
            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Gênero *
          </label>
          <select name="fundador_principal[genero]" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach (['Masculino','Feminino','Não Binário','Outros'] as $g): ?>
              <option><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600">
            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Formação *
          </label>
          <select name="fundador_principal[formacao]" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach ($formacoes as $f): ?>
              <option><?= $f ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-600">
            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Etnia / Raça *
          </label>
          <select name="fundador_principal[etnia]" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach ($etnias as $label => $val): ?>
              <option value="<?= $val ?>"><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Endereço -->
        <div class="col-12">
          <label class="form-label fw-600">
            <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Endereço
          </label>
          <div class="d-flex gap-4">
            <div class="form-check">
              <input class="form-check-input endereco-radio" type="radio"
                     name="fundador_principal[endereco_tipo]" value="negocio" checked>
              <label class="form-check-label small">Usar endereço do negócio</label>
            </div>
            <div class="form-check">
              <input class="form-check-input endereco-radio" type="radio"
                     name="fundador_principal[endereco_tipo]" value="residencial">
              <label class="form-check-label small">Cadastrar endereço residencial</label>
            </div>
          </div>
        </div>

        <!-- Endereço residencial (oculto por padrão) -->
        <div class="col-12 campos-residencial d-none">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label fw-600">CEP</label>
              <input type="text" name="fundador_principal[cep]" class="form-control">
            </div>
            <div class="col-md-5">
              <label class="form-label fw-600">Rua</label>
              <input type="text" name="fundador_principal[rua]" class="form-control">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-600">Número</label>
              <input type="text" name="fundador_principal[numero]" class="form-control">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-600">Complemento</label>
              <input type="text" name="fundador_principal[complemento]" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Município</label>
              <input type="text" name="fundador_principal[municipio]" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">Estado</label>
              <select name="fundador_principal[estado]" class="form-select">
                <option value="">Selecione</option>
                <?php foreach ($estados as $uf => $nome): ?>
                  <option value="<?= $uf ?>"><?= $nome ?> (<?= $uf ?>)</option>
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
      <!-- Cofundadores existentes no banco -->
      <?php
      $cofundadoresExistentes = array_filter($fundadoresExistentes, fn($f) => $f['tipo'] === 'cofundador');
      foreach ($cofundadoresExistentes as $cf):
      ?>
        <div class="emp-cofundador-bloco emp-card mb-3 p-3">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="fw-bold" style="color:#1E3425;">
              <i class="bi bi-person-fill me-2"></i>
              <?= htmlspecialchars($cf['nome'] . ' ' . $cf['sobrenome']) ?>
            </span>
            <span class="emp-badge-ativo">Cadastrado</span>
          </div>
          <div class="row g-2 small text-muted">
            <div class="col-md-4"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($cf['email'] ?? '—') ?></div>
            <div class="col-md-4"><i class="bi bi-phone me-1"></i><?= htmlspecialchars($cf['celular'] ?? '—') ?></div>
            <div class="col-md-4"><i class="bi bi-person-vcard me-1"></i><?= htmlspecialchars($cf['etnia'] ?? '—') ?></div>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Container para novos cofundadores via JS -->
      <div id="cofundadores-container"></div>

      <button type="button" class="btn-emp-outline mt-2" id="add-cofundador">
        <i class="bi bi-plus-lg me-2"></i> Adicionar Cofundador
      </button>
    </div>
  </div>

  <!-- ── Ações ── -->
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-5">
    <a href="/negocios/editar_etapa1.php?id=<?= $_SESSION['negocio_id'] ?>" class="btn-emp-outline">
      <i class="bi bi-arrow-left me-1"></i> Voltar
    </a>
    <div class="d-flex gap-2 flex-wrap">
      <button type="submit" name="acao" value="pular_cofundadores" class="btn-emp-outline">
        Não tenho cofundadores, avançar
      </button>
      <button type="submit" name="acao" value="salvar" class="btn-emp-primary">
        <i class="bi bi-floppy me-2"></i> Salvar e Avançar
      </button>
    </div>
  </div>

</form>
      </div>
<script>
// ── Toggle endereço residencial ────────────────
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

// ── Cofundadores dinâmicos ─────────────────────
(function () {
  const container = document.getElementById('cofundadores-container');
  const addBtn    = document.getElementById('add-cofundador');
  let count = 0;
  const MAX = 4;

  const formacoes = <?= json_encode($formacoes) ?>;
  const etnias    = <?= json_encode(array_flip($etnias)) ?>; // label => value
  const estados   = <?= json_encode($estados) ?>;

  function buildOpts(map) {
    return Object.entries(map).map(([k,v]) =>
      `<option value="${v}">${k}</option>`
    ).join('');
  }
  function buildOptsSimple(arr) {
    return arr.map(v => `<option>${v}</option>`).join('');
  }
  function buildEstados(map) {
    return Object.entries(map).map(([uf,nome]) =>
      `<option value="${uf}">${nome} (${uf})</option>`
    ).join('');
  }

  addBtn.addEventListener('click', function () {
    if (count >= MAX) {
      alert('Você pode adicionar no máximo 4 cofundadores.');
      return;
    }
    count++;
    const bloco = document.createElement('div');
    bloco.className = 'emp-card mb-3 p-3 emp-cofundador-novo';
    bloco.innerHTML = `
      <div class="d-flex align-items-center justify-content-between mb-3">
        <span class="fw-bold" style="color:#1E3425;">
          <i class="bi bi-person-plus me-2"></i> Cofundador ${count}
        </span>
        <button type="button" class="btn-emp-icon text-danger remove-cofundador" title="Remover">
          <i class="bi bi-trash"></i>
        </button>
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-600"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Nome *</label>
          <input type="text" name="cofundador[${count}][nome]" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-600"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Sobrenome *</label>
          <input type="text" name="cofundador[${count}][sobrenome]" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> CPF *</label>
          <input type="text" name="cofundador[${count}][cpf]" class="form-control cpf-input" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> E-mail *</label>
          <input type="email" name="cofundador[${count}][email]" class="form-control" required>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="cofundador[${count}][email_optin]" value="1">
            <label class="form-check-label small">Aceito receber atualizações via e-mail</label>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Celular *</label>
          <input type="text" name="cofundador[${count}][celular]" class="form-control" required>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="cofundador[${count}][whatsapp_optin]" value="1">
            <label class="form-check-label small">Aceito receber novidades via WhatsApp</label>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Data de Nascimento *</label>
          <input type="date" name="cofundador[${count}][data_nascimento]" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Gênero *</label>
          <select name="cofundador[${count}][genero]" class="form-select" required>
            <option value="">Selecione</option>
            <option>Masculino</option><option>Feminino</option>
            <option>Não Binário</option><option>Outros</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Formação *</label>
          <select name="cofundador[${count}][formacao]" class="form-select" required>
            <option value="">Selecione</option>
            ${buildOptsSimple(formacoes)}
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-600"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Etnia / Raça *</label>
          <select name="cofundador[${count}][etnia]" class="form-select" required>
            <option value="">Selecione</option>
            ${buildOpts(etnias)}
          </select>
        </div>
      </div>
    `;
    container.appendChild(bloco);

    // Máscara CPF no novo campo
    bloco.querySelectorAll('.cpf-input').forEach(bindCpfMask);

    bloco.querySelector('.remove-cofundador').addEventListener('click', function () {
      bloco.remove();
      count--;
    });
  });

  // ── Validação e máscara CPF ────────────────
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
    input.addEventListener('input', function () {
      this.value = fmtCPF(this.value);
    });
    input.addEventListener('blur', function () {
      const d = this.value.replace(/\D/g,'');
      if (d && !isValidCPF(d)) {
        this.classList.add('is-invalid');
        let err = this.parentNode.querySelector('.invalid-feedback');
        if (!err) { err = document.createElement('div'); err.className='invalid-feedback'; this.parentNode.appendChild(err); }
        err.textContent = 'CPF inválido.';
      } else {
        this.classList.remove('is-invalid');
        const err = this.parentNode.querySelector('.invalid-feedback');
        if (err) err.remove();
      }
    });
    input.addEventListener('focus', function () {
      this.classList.remove('is-invalid','is-valid');
    });
  }

  // Aplica máscara nos CPFs já presentes no DOM
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

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>