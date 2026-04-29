<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /login.php"); exit; }

$pageTitle = 'Editar Etapa 1 – Dados do Negócio';
$config    = require __DIR__ . '/../app/config/db.php';
$pdo       = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'], $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: /empreendedores/meus-negocios.php"); exit; }

$stmt = $pdo->prepare("
    SELECT id, empreendedor_id, nome_fantasia, razao_social, categoria, cnpj_cpf,
           email_comercial, telefone_comercial, formato_legal, formato_outros,
           data_fundacao, setor, rua, numero AS numero, complemento, cep,
           municipio, estado, pais, site, linkedin, instagram, facebook,
           tiktok, youtube, outros_links, etapa_atual, inscricao_completa
    FROM negocios
    WHERE id = ? AND empreendedor_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$n = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$n) { header("Location: /empreendedores/meus-negocios.php"); exit; }

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<?php if (!empty($_SESSION['errors_etapa1'])): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4">
  <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Corrija os erros</h6>
  <ul class="mb-0 ps-3 small">
    <?php foreach ($_SESSION['errors_etapa1'] as $erro): ?>
      <li><?= htmlspecialchars($erro) ?></li>
    <?php endforeach; ?>
  </ul>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['errors_etapa1']); endif; ?>

<?php if (!empty($_SESSION['flash_message'])): ?>
<div class="alert alert-success alert-dismissible fade show mb-4">
  <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_message']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['flash_message']); endif; ?>

<!-- Cabeçalho da página -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h1 class="emp-page-title mb-1">Editar <?= htmlspecialchars($n['nome_fantasia']) ?></h1>
    <p class="emp-page-subtitle mb-0">Etapa 1 – Dados do Negócio</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if ($n['inscricao_completa']): ?>
      <a href="/negocios/confirmacao.php?id=<?= $n['id'] ?>" class="btn-emp-outline">
        <i class="bi bi-card-checklist me-1"></i> Voltar à Revisão
      </a>
    <?php endif; ?>
    <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline">
      <i class="bi bi-arrow-left me-1"></i> Meus Negócios
    </a>
  </div>
</div>

<form action="/negocios/processa_etapa1.php" method="post" id="formEtapa1">
  <input type="hidden" name="id"   value="<?= (int)$n['id'] ?>">
  <input type="hidden" name="modo" value="editar">

  <div class="row g-4">
    <!-- Coluna principal -->
    <div class="col-12 col-lg-8">

      <!-- Categoria visual -->
      <div class="emp-card mb-4">
        <div class="emp-card-header"><i class="bi bi-tag-fill"></i> Categoria</div>
        <div class="p-3">
          <div class="row g-3" id="categoriaRadios">
            <?php
            $categorias = [
              ['valor'=>'Ideação',       'img'=>'ideacao.png',       'sub'=>'Início da ideia',         'desc'=>'Negócio em fase de concepção, validação de hipótese, equipe reduzida, foco em prototipagem e testes iniciais.'],
              ['valor'=>'Operação',      'img'=>'operacao.png',      'sub'=>'Negócio em funcionamento', 'desc'=>'Empresa com produto/serviço no mercado, receitas regulares, processos operacionais estabelecidos, foco em eficiência.'],
              ['valor'=>'Tração/Escala', 'img'=>'tracao.png',        'sub'=>'Crescimento e escala',    'desc'=>'Negócio com tração comprovada, crescimento acelerado, busca por expansão de mercado, parcerias e investimento.'],
              ['valor'=>'Dinamizador',   'img'=>'dinamizadores.png', 'sub'=>'Apoio e fomento',         'desc'=>'Organizações que fomentam ecossistemas: aceleradoras, incubadoras, hubs e projetos de apoio a empreendedores.'],
            ];
            foreach ($categorias as $cat): ?>
            <div class="col-6 col-md-3">
              <label class="categoria-option emp-categoria-card <?= ($n['categoria'] ?? '') === $cat['valor'] ? 'selected' : '' ?>">
                <input type="radio" name="categoria" value="<?= $cat['valor'] ?>"
                       class="visually-hidden categoria-radio"
                       <?= ($n['categoria'] ?? '') === $cat['valor'] ? 'checked' : '' ?> required>
                <img src="/assets/images/icons/<?= $cat['img'] ?>"
                     class="d-block mx-auto mb-2" style="height:80px;width:80px;object-fit:cover;">
                <div class="fw-bold" style="font-size:.88rem;color:#1E3425;"><?= $cat['valor'] ?></div>
                <small style="color:#9aab9d;font-size:.75rem;"><?= $cat['sub'] ?></small>
                <div class="categoria-desc d-none"><?= $cat['desc'] ?></div>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
          <div id="categoriaDescricaoDinamica" class="mt-3 p-3 rounded"
               style="background:#f0f4ed;border:1px solid #d8e4d0;display:none;">
            <div id="categoriaTitulo" class="fw-bold mb-1" style="color:#1E3425;"></div>
            <div id="categoriaTexto"  class="small"        style="color:#6c8070;"></div>
          </div>
        </div>
      </div>

      <!-- Identificação -->
      <div class="emp-card mb-4">
        <div class="emp-card-header"><i class="bi bi-building"></i> Identificação</div>
        <div class="p-3">
          <div class="row g-3">        

            <div class="col-md-6">
              <label class="form-label fw-600">
                <i class="bi bi-eye-slash-fill lbl-priv me-1"></i>
                <span id="cnpjCpfLabel">CPF ou CNPJ</span>
              </label>
              <input type="text" name="cnpj_cpf" id="cnpj_cpf" class="form-control"
                     value="<?= htmlspecialchars($n['cnpj_cpf'] ?? '', ENT_QUOTES) ?>" required>
              <div class="invalid-feedback" id="cnpjCpfError"></div>
              <small id="cnpjCpfHelp" class="form-text" style="color:#9aab9d;"></small>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-600">
                <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Formato Legal
              </label>
              <select name="formato_legal" id="formato_legal" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach (['Organização da Sociedade Civil','MEI - Microempreendedor individual','Cooperativa','Sociedade Limitada','Sociedade Anônima','Empresa Individual de Responsabilidade Limitada','Outros'] as $f): ?>
                  <option <?= ($n['formato_legal'] ?? '') === $f ? 'selected' : '' ?>><?= $f ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-600">
                <i class="bi bi-eye text-secondary me-1"></i> Nome Fantasia
              </label>
              <input type="text" name="nome_fantasia" class="form-control"
                     value="<?= htmlspecialchars($n['nome_fantasia'] ?? '', ENT_QUOTES) ?>" required>
            </div>   

            <!-- Razão Social -->
            <div class="col-md-6">
              <label class="form-label fw-600">
                <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Razão Social *
              </label>
              <div class="position-relative">
                <input type="text" name="razao_social" id="razao_social"
                       class="form-control bg-light"
                       value="<?= htmlspecialchars($n['razao_social'] ?? '', ENT_QUOTES) ?>"
                       placeholder="Razão social conforme CNPJ"
                       readonly>
                <span id="razaoSocialSpinner"
                      class="position-absolute top-50 end-0 translate-middle-y me-2 d-none"
                      style="color:#6c757d;">
                  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                </span>
                <span id="razaoSocialBadge"
                      class="position-absolute top-50 end-0 translate-middle-y me-2 d-none"
                      style="font-size:.75rem;"></span>
              </div>
              <div id="razaoSocialHelp" class="form-text">
                <?php if (!empty($n['razao_social'])): ?>
                  <span class="text-success">
                    <i class="bi bi-lock-fill me-1"></i>Preenchido via Receita Federal. Para alterar, corrija o CNPJ.
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <div class="col-md-6 <?= ($n['formato_legal'] ?? '') !== 'Outros' ? 'd-none' : '' ?>" id="formatoOutrosWrapper">
              <label class="form-label fw-600">Qual formato?</label>
              <input type="text" name="formato_outros" id="formato_outros" class="form-control"
                     value="<?= htmlspecialchars($n['formato_outros'] ?? '', ENT_QUOTES) ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-600">
                <i class="bi bi-eye text-secondary me-1"></i> Data de Fundação
              </label>
              <input type="date" name="data_fundacao" class="form-control"
                     value="<?= htmlspecialchars($n['data_fundacao'] ?? '', ENT_QUOTES) ?>" required>
            </div>

          </div>
        </div>
      </div>

      <!-- Setor -->
      <div class="emp-card mb-4">
        <div class="emp-card-header"><i class="bi bi-diagram-3-fill"></i> Setor</div>
        <div class="p-3">
          <select name="setor" id="setor" class="form-select" required>
            <option value="">Selecione...</option>
            <optgroup label="Setor Primário">
              <?php foreach (['Agricultura','Pecuária','Pesca','Silvicultura','Extração vegetal','Mineração','Outro Primário'] as $s): ?>
                <option <?= ($n['setor'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="Setor Secundário">
              <?php foreach (['Indústrias alimentícias e bebidas','Indústria têxtil e vestuário','Indústria automobilística','Indústria química e petroquímica','Indústria farmacêutica','Indústria de papel e celulose','Indústria de cimento e construção civil','Siderurgia e metalurgia','Indústria de eletroeletrônicos e tecnologia','Energia','Outro Secundário'] as $s): ?>
                <option <?= ($n['setor'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="Setor Terciário">
              <?php foreach (['Comércio','Transporte e logística','Serviços financeiros','Educação','Saúde','Turismo','Tecnologia e serviços digitais','Serviços jurídicos e contábeis','Comunicação e marketing','Administração pública e serviços sociais','Serviços de limpeza, segurança e manutenção','Entretenimento e cultura','Outro Terciário'] as $s): ?>
                <option <?= ($n['setor'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </optgroup>
          </select>
        </div>
      </div>

      <!-- Contato Comercial -->
      <div class="emp-card mb-4">
        <div class="emp-card-header"><i class="bi bi-envelope-fill"></i> Contato Comercial</div>
        <div class="p-3">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-600">
                <i class="bi bi-eye text-secondary me-1"></i> E-mail Institucional
              </label>
              <input type="email" name="email_comercial" class="form-control"
                     value="<?= htmlspecialchars($n['email_comercial'] ?? '', ENT_QUOTES) ?>"
                     placeholder="contato@seunegocio.com.br">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-600">
                <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Telefone/WhatsApp
              </label>
              <input type="text" name="telefone_comercial" class="form-control"
                     value="<?= htmlspecialchars($n['telefone_comercial'] ?? '', ENT_QUOTES) ?>"
                     placeholder="(00) 00000-0000">
            </div>
          </div>
        </div>
      </div>

      <!-- Endereço -->
      <div class="emp-card mb-4">
        <div class="emp-card-header"><i class="bi bi-geo-alt-fill"></i> Endereço</div>
        <div class="p-3">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label fw-600">
                <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> CEP
              </label>
              <input type="text" name="cep" id="cep" class="form-control" maxlength="9"
                     value="<?= htmlspecialchars($n['cep'] ?? '', ENT_QUOTES) ?>" required>
            </div>
            <div class="col-md-5">
              <label class="form-label fw-600">
                <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Rua
              </label>
              <input type="text" name="rua" id="rua" class="form-control"
                     value="<?= htmlspecialchars($n['rua'] ?? '', ENT_QUOTES) ?>" required>
            </div>
            <div class="col-md-2">
              <label class="form-label fw-600">
                <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Número
              </label>
              <input type="text" name="numero" id="numero" class="form-control"
                     value="<?= htmlspecialchars($n['numero'] ?? '', ENT_QUOTES) ?>" required>
            </div>
            <div class="col-md-2">
              <label class="form-label fw-600">Complemento</label>
              <input type="text" name="complemento" class="form-control"
                     value="<?= htmlspecialchars($n['complemento'] ?? '', ENT_QUOTES) ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label fw-600">
                <i class="bi bi-eye text-secondary me-1"></i> Município
              </label>
              <input type="text" name="municipio" id="municipio" class="form-control"
                     value="<?= htmlspecialchars($n['municipio'] ?? '', ENT_QUOTES) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-600">
                <i class="bi bi-eye text-secondary me-1"></i> Estado
              </label>
              <input type="text" name="estado" id="estado" class="form-control"
                     value="<?= htmlspecialchars($n['estado'] ?? '', ENT_QUOTES) ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-600">País</label>
              <select name="pais" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach (['Brasil','Argentina','Chile','Uruguai','Paraguai','Bolívia','Peru','Colômbia','Equador','Venezuela','Estados Unidos','Canadá','México','Portugal','Espanha','França','Alemanha','Itália','Reino Unido','Outro'] as $p): ?>
                  <option <?= ($n['pais'] ?? 'Brasil') === $p ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Presença Digital -->
      <div class="emp-card mb-4">
        <div class="emp-card-header"><i class="bi bi-globe2"></i> Presença Digital</div>
        <div class="p-3">
          <div class="row g-3">
            <?php
            $redes = [
              ['name'=>'site',      'label'=>'Site',      'icon'=>'bi-globe',     'color'=>'#6c8070', 'ph'=>'https://www.seusite.com'],
              ['name'=>'linkedin',  'label'=>'LinkedIn',  'icon'=>'bi-linkedin',  'color'=>'#0077b5', 'ph'=>'https://linkedin.com/company/...'],
              ['name'=>'instagram', 'label'=>'Instagram', 'icon'=>'bi-instagram', 'color'=>'#e1306c', 'ph'=>'https://instagram.com/...'],
              ['name'=>'facebook',  'label'=>'Facebook',  'icon'=>'bi-facebook',  'color'=>'#1877f2', 'ph'=>'https://facebook.com/...'],
              ['name'=>'tiktok',    'label'=>'TikTok',    'icon'=>'bi-tiktok',    'color'=>'#010101', 'ph'=>'https://tiktok.com/...'],
              ['name'=>'youtube',   'label'=>'YouTube',   'icon'=>'bi-youtube',   'color'=>'#ff0000', 'ph'=>'https://youtube.com/...'],
            ];
            foreach ($redes as $r): ?>
            <div class="col-md-6">
              <label class="form-label fw-600">
                <i class="bi <?= $r['icon'] ?> me-1" style="color:<?= $r['color'] ?>;"></i> <?= $r['label'] ?>
              </label>
              <input type="url" name="<?= $r['name'] ?>" class="form-control"
                     value="<?= htmlspecialchars($n[$r['name']] ?? '', ENT_QUOTES) ?>"
                     placeholder="<?= $r['ph'] ?>">
            </div>
            <?php endforeach; ?>
            <div class="col-12">
              <label class="form-label fw-600">
                <i class="bi bi-link-45deg me-1"></i> Outros Links
              </label>
              <input type="text" name="outros_links" class="form-control"
                     value="<?= htmlspecialchars($n['outros_links'] ?? '', ENT_QUOTES) ?>"
                     placeholder="Outros links separados por vírgula">
            </div>
          </div>
        </div>
      </div>

    </div><!-- /col-lg-8 -->

    <!-- Coluna lateral -->
    <div class="col-12 col-lg-4">

      <!-- Legenda -->
      <div class="emp-card mb-4">
        <div class="emp-card-header"><i class="bi bi-info-circle"></i> Legenda</div>
        <div class="p-3">
          <div class="d-flex align-items-center gap-2 mb-2 small">
            <i class="bi bi-eye text-secondary"></i>
            <span style="color:#4a5e4f;">Campo <strong>público</strong> – visível na vitrine</span>
          </div>
          <div class="d-flex align-items-center gap-2 small">
            <i class="bi bi-eye-slash-fill lbl-priv"></i>
            <span style="color:#4a5e4f;">Campo <strong>privado</strong> – somente interno</span>
          </div>
        </div>
      </div>

      <!-- Ações -->
      <div class="emp-card">
        <div class="emp-card-header"><i class="bi bi-floppy-fill"></i> Salvar</div>
        <div class="p-3">
          <p class="small mb-3" style="color:#9aab9d;">
            Salve as alterações. O andamento do cadastro será preservado.
          </p>
          <button type="submit" class="btn-emp-primary w-100 justify-content-center mb-2">
            <i class="bi bi-floppy me-2"></i> Salvar Alterações
          </button>
          <?php if ($n['inscricao_completa']): ?>
          <a href="/negocios/confirmacao.php?id=<?= $n['id'] ?>" class="btn-emp-outline w-100 justify-content-center mb-2">
            <i class="bi bi-card-checklist me-2"></i> Voltar à Revisão
          </a>
          <?php endif; ?>
          <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline w-100 justify-content-center">
            <i class="bi bi-arrow-left me-2"></i> Meus Negócios
          </a>
        </div>
      </div>

    </div><!-- /col-lg-4 -->
  </div><!-- /row -->
</form>

<!-- CSS categoria-cards -->
<style>
.emp-categoria-card {
  display: flex; flex-direction: column; align-items: center; text-align: center;
  padding: .9rem .6rem; border-radius: 12px; border: 2px solid #e8ede5;
  background: #fff; cursor: pointer;
  transition: border-color .15s, box-shadow .15s, background .15s;
  height: 100%;
}
.emp-categoria-card:hover   { border-color: #CDDE00; background: #f9fbf2; }
.emp-categoria-card.selected {
  border-color: #CDDE00; background: #f4f9e4;
  box-shadow: 0 0 0 3px rgba(205,222,0,.2);
}
</style>

<script>
// ── Seleção visual de categoria ────────────────────────────────────────────
document.querySelectorAll('.categoria-option').forEach(function(label) {
  label.addEventListener('click', function() {
    document.querySelectorAll('.categoria-option').forEach(l => l.classList.remove('selected'));
    this.classList.add('selected');
    const titulo = this.querySelector('.fw-bold').textContent.trim();
    const texto  = this.querySelector('.categoria-desc').textContent.trim();
    document.getElementById('categoriaTitulo').textContent = titulo;
    document.getElementById('categoriaTexto').textContent  = texto;
    document.getElementById('categoriaDescricaoDinamica').style.display = 'block';
  });
});
// Dispara seleção da categoria já salva no banco
(function() {
  const checked = document.querySelector('input[name="categoria"]:checked');
  if (checked) checked.closest('.categoria-option')?.click();
})();

// ── Formato Legal – campo "Outros" ─────────────────────────────────────────
document.getElementById('formato_legal').addEventListener('change', function() {
  const w = document.getElementById('formatoOutrosWrapper');
  const i = document.getElementById('formato_outros');
  if (this.value === 'Outros') { w.classList.remove('d-none'); i.setAttribute('required','required'); }
  else                         { w.classList.add('d-none');    i.removeAttribute('required'); i.value = ''; }
});

// ── CEP autocomplete (ViaCEP) ──────────────────────────────────────────────
document.getElementById('cep').addEventListener('blur', function() {
  const cep = this.value.replace(/\D/g, '');
  if (cep.length !== 8) return;
  fetch('https://viacep.com.br/ws/' + cep + '/json/')
    .then(r => r.json())
    .then(d => {
      if (d.erro) return;
      document.getElementById('rua').value       = d.logradouro;
      document.getElementById('municipio').value = d.localidade;
      document.getElementById('estado').value    = d.uf;
    });
});

// ── CPF / CNPJ + Razão Social via API cpfcnpj.com.br ──────────────────────
(function() {


  const PROXY = '../app/api/cpfcnpj_proxy.php';

  const radios         = document.querySelectorAll('input[name="categoria"]');
  const labelCnpjCpf   = document.getElementById('cnpjCpfLabel');
  const help           = document.getElementById('cnpjCpfHelp');
  const cnpjCpfInput   = document.getElementById('cnpj_cpf');
  const cnpjCpfError   = document.getElementById('cnpjCpfError');
  const razaoInput     = document.getElementById('razao_social');
  const razaoSpinner   = document.getElementById('razaoSocialSpinner');
  const razaoBadge     = document.getElementById('razaoSocialBadge');
  const razaoHelp      = document.getElementById('razaoSocialHelp');
  let   debounceTimer  = null;

  // ── Utilitários ────────────────────────────────────────────────────────
  function onlyDigits(s) { return (s || '').replace(/\D/g, ''); }

  function setInvalid(msg) {
    cnpjCpfInput.classList.add('is-invalid');
    if (cnpjCpfError) cnpjCpfError.textContent = msg || 'Valor inválido.';
    cnpjCpfInput.setCustomValidity(msg || 'Inválido');
  }
  function clearInvalid() {
    cnpjCpfInput.classList.remove('is-invalid');
    if (cnpjCpfError) cnpjCpfError.textContent = '';
    cnpjCpfInput.setCustomValidity('');
  }

  // ── Validações ─────────────────────────────────────────────────────────
  function isValidCPF(cpf) {
    cpf = onlyDigits(cpf);
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
    let s = 0;
    for (let i = 0; i < 9; i++) s += parseInt(cpf[i]) * (10 - i);
    let r = s % 11, d1 = r < 2 ? 0 : 11 - r;
    if (d1 !== parseInt(cpf[9])) return false;
    s = 0;
    for (let i = 0; i < 10; i++) s += parseInt(cpf[i]) * (11 - i);
    r = s % 11;
    return (r < 2 ? 0 : 11 - r) === parseInt(cpf[10]);
  }

  function isValidCNPJ(cnpj) {
    cnpj = onlyDigits(cnpj);
    if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) return false;
    const p1 = [5,4,3,2,9,8,7,6,5,4,3,2];
    let s = 0;
    for (let i = 0; i < 12; i++) s += parseInt(cnpj[i]) * p1[i];
    let r = s % 11, d1 = r < 2 ? 0 : 11 - r;
    if (d1 !== parseInt(cnpj[12])) return false;
    const p2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
    s = 0;
    for (let i = 0; i < 13; i++) s += parseInt(cnpj[i]) * p2[i];
    r = s % 11;
    return (r < 2 ? 0 : 11 - r) === parseInt(cnpj[13]);
  }

  // ── Máscaras ───────────────────────────────────────────────────────────
  function formatCPF(d) {
    d = d.slice(0, 11);
    return d.replace(/^(\d{3})(\d)/,'$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/,'$1.$2.$3')
            .replace(/\.(\d{3})(\d)/,'.$1-$2')
            .slice(0, 14);
  }
  function formatCNPJ(d) {
    d = d.slice(0, 14);
    return d.replace(/^(\d{2})(\d)/,'$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/,'$1.$2.$3')
            .replace(/\.(\d{3})(\d)/,'.$1/$2')
            .replace(/(\d{4})(\d)/,'$1-$2')
            .slice(0, 18);
  }
  function maskAuto(v) {
    const d = onlyDigits(v);
    return d.length <= 11 ? formatCPF(d) : formatCNPJ(d);
  }

  // ── Estado do campo Razão Social ───────────────────────────────────────
  function razaoSetLoading(on) {
    if (on) { razaoSpinner.classList.remove('d-none'); razaoBadge.classList.add('d-none'); }
    else    { razaoSpinner.classList.add('d-none'); }
  }
  function razaoSetSuccess(nome) {
    razaoInput.value = nome;
    razaoInput.setAttribute('readonly','readonly');
    razaoInput.classList.add('bg-light');
    razaoBadge.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
    razaoBadge.classList.remove('d-none');
    razaoHelp.innerHTML  = '<span class="text-success"><i class="bi bi-lock-fill me-1"></i>Preenchido automaticamente via Receita Federal. Não pode ser alterado.</span>';
  }
  function razaoSetError(msg) {
    razaoInput.value = '';
    razaoInput.removeAttribute('readonly');
    razaoInput.classList.remove('bg-light');
    razaoBadge.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i>';
    razaoBadge.classList.remove('d-none');
    razaoHelp.innerHTML  = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>'
      + (msg || 'Não foi possível consultar o CNPJ.') + ' Verifique e tente novamente.</span>';
  }
  function razaoClear() {
    razaoInput.value = '';
    razaoInput.removeAttribute('readonly');
    razaoInput.classList.remove('bg-light');
    razaoBadge.classList.add('d-none');
    razaoHelp.innerHTML = '';
    razaoSetLoading(false);
  }

  // ── Consulta à API (Produto 5 = Dados Básicos CNPJ) ───────────────────
  function consultarCNPJ(digits) {
    razaoSetLoading(true);
    razaoHelp.innerHTML = '<span class="text-muted">Consultando Receita Federal…</span>';
    fetch(PROXY + '?tipo=cnpj&doc=' + cnpjDigits)
      .then(function(res) {
        razaoSetLoading(false);
        if (!res.ok) {
          if (res.status === 400 || res.status === 404) razaoSetError('CNPJ não encontrado na Receita Federal.');
          else if (res.status === 401 || res.status === 403) razaoSetError('Token inválido ou sem créditos.');
          else razaoSetError('Erro HTTP ' + res.status + '.');
          return null;
        }
        return res.json();
      })
      .then(function(data) {
        if (!data) return;
        const nome = (data.razao || data.razao_social || data.nome || '').trim();
        if (nome) razaoSetSuccess(nome);
        else      razaoSetError('Razão Social não retornada pela API.');
      })
      .catch(function(err) {
        razaoSetLoading(false);
        razaoSetError('Falha na conexão com a API.');
        console.error('[CNPJ API]', err);
      });
  }

  // ── Categoria selecionada ──────────────────────────────────────────────
  function getCategory() {
    const r = document.querySelector('input[name="categoria"]:checked');
    return r ? r.value : '';
  }

  function validateField(cat) {
    clearInvalid();
    const d = onlyDigits(cnpjCpfInput.value);
    if (cat === 'Ideação') {
      if      (d.length === 11) { if (!isValidCPF(d))  { setInvalid('CPF inválido.'); return false; } }
      else if (d.length === 14) { if (!isValidCNPJ(d)) { setInvalid('CNPJ inválido.'); return false; } }
      else { setInvalid('Informe CPF (11 dígitos) ou CNPJ (14 dígitos).'); return false; }
    } else {
      if (d.length !== 14)     { setInvalid('Informe um CNPJ com 14 dígitos.'); return false; }
      if (!isValidCNPJ(d))     { setInvalid('CNPJ inválido.'); return false; }
    }
    return true;
  }

  // ── Reage à mudança de categoria ───────────────────────────────────────
 function onCategoryChange() {
  const cat    = getCategory();
  const digits = onlyDigits(cnpjCpfInput.value);

  if (cat === 'Ideação') {
    labelCnpjCpf.textContent = 'CPF ou CNPJ';
    help.textContent = 'Na categoria Ideação você pode informar CPF (11 dígitos) ou CNPJ (14 dígitos).';
    cnpjCpfInput.removeAttribute('maxlength');
    cnpjCpfInput.setAttribute('placeholder', 'CPF ou CNPJ');
    cnpjCpfInput.value = maskAuto(cnpjCpfInput.value);

    // ✅ Se já tem CNPJ válido, mantém/consulta Razão Social — NÃO limpa
    if (digits.length === 14 && isValidCNPJ(digits)) {
      razaoInput.setAttribute('required', 'required');
      // Se já tem valor no campo, só garante readonly — sem re-consultar
      if (razaoInput.value.trim() !== '') {
        razaoInput.setAttribute('readonly', 'readonly');
        razaoInput.classList.add('bg-light');
        razaoBadge.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
        razaoBadge.classList.remove('d-none');
        razaoHelp.innerHTML = '<span class="text-success"><i class="bi bi-lock-fill me-1"></i>Preenchido automaticamente via Receita Federal. Não pode ser alterado.</span>';
      } else {
        // Sem valor salvo → consulta API
        razaoBadge.classList.add('d-none');
        razaoHelp.innerHTML = '<span class="text-muted">Aguardando…</span>';
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() { consultarCNPJ(digits); }, 600);
      }
    } else {
      // CPF ou campo vazio → limpa Razão Social e libera edição
      razaoInput.removeAttribute('required');
      razaoClear();
    }

  } else if (cat) {
    labelCnpjCpf.textContent = 'CNPJ';
    help.textContent = 'Para esta categoria, informe apenas um CNPJ válido (14 dígitos).';
    cnpjCpfInput.setAttribute('maxlength', '18');
    cnpjCpfInput.setAttribute('placeholder', '00.000.000/0000-00');
    cnpjCpfInput.value = formatCNPJ(onlyDigits(cnpjCpfInput.value));
    razaoInput.setAttribute('required', 'required');

    if (digits.length === 14 && isValidCNPJ(digits)) {
      if (razaoInput.value.trim() !== '') {
        razaoInput.setAttribute('readonly', 'readonly');
        razaoInput.classList.add('bg-light');
        razaoBadge.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
        razaoBadge.classList.remove('d-none');
        razaoHelp.innerHTML = '<span class="text-success"><i class="bi bi-lock-fill me-1"></i>Preenchido automaticamente via Receita Federal. Não pode ser alterado.</span>';
      } else {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() { consultarCNPJ(digits); }, 600);
      }
    } else {
      razaoClear();
    }
  }

  validateField(cat);
}

  // ── Input do CNPJ/CPF ─────────────────────────────────────────────────
  cnpjCpfInput.addEventListener('input', function() {
  const cat = getCategory();
  cnpjCpfInput.value = (cat && cat !== 'Ideação')
    ? formatCNPJ(onlyDigits(cnpjCpfInput.value))
    : maskAuto(cnpjCpfInput.value);
  clearInvalid();

  const d = onlyDigits(cnpjCpfInput.value);
  clearTimeout(debounceTimer);

  if (d.length === 14 && isValidCNPJ(d)) {
    // CNPJ válido em qualquer categoria → consulta Razão Social
    razaoInput.setAttribute('required', 'required');
    razaoBadge.classList.add('d-none');
    razaoHelp.innerHTML = '<span class="text-muted">Aguardando…</span>';
    debounceTimer = setTimeout(function() { consultarCNPJ(d); }, 600);
  } else if (cat === 'Ideação' && d.length === 11 && isValidCPF(d)) {
    // CPF válido em Ideação → Razão Social não obrigatória, limpa
    razaoInput.removeAttribute('required');
    razaoClear();
  } else {
    // Incompleto/inválido → limpa
    if (cat === 'Ideação') razaoInput.removeAttribute('required');
    razaoClear();
  }
});

  cnpjCpfInput.addEventListener('paste', function(e) {
    e.preventDefault();
    const paste = (e.clipboardData || window.clipboardData).getData('text');
    const cat   = getCategory();
    cnpjCpfInput.value = (cat && cat !== 'Ideação')
      ? formatCNPJ(onlyDigits(paste))
      : maskAuto(paste);
    cnpjCpfInput.dispatchEvent(new Event('input', { bubbles: true }));
  });

  cnpjCpfInput.addEventListener('blur',  function() { validateField(getCategory()); });
  cnpjCpfInput.addEventListener('focus', clearInvalid);

  radios.forEach(function(r) { r.addEventListener('change', onCategoryChange); });

  // ── Submit: remove máscara antes de enviar ────────────────────────────
  document.getElementById('formEtapa1').addEventListener('submit', function(ev) {
    const cat = getCategory();
    if (!validateField(cat)) {
      ev.preventDefault();
      cnpjCpfInput.focus();
    } else {
      cnpjCpfInput.value = onlyDigits(cnpjCpfInput.value);
    }
  });

  // ── Inicialização ─────────────────────────────────────────────────────
  // Aplica máscara e labels conforme dados já carregados do banco
  (function init() {
    onCategoryChange();

    const cat    = getCategory();
    const digits = onlyDigits(cnpjCpfInput.value);
    cnpjCpfInput.value = (cat && cat !== 'Ideação') ? formatCNPJ(digits) : maskAuto(cnpjCpfInput.value);

    // Se já há Razão Social salva → mostra badge sem re-consultar a API
    if (razaoInput.value.trim() !== '') {
      razaoInput.setAttribute('readonly', 'readonly');
      razaoInput.classList.add('bg-light');
      razaoBadge.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
      razaoBadge.classList.remove('d-none');
      // razaoHelp já vem preenchido pelo PHP
    }
    // Se não há Razão Social mas há CNPJ válido → consulta API
    else if (digits.length === 14 && isValidCNPJ(digits)) {
      consultarCNPJ(digits);
    }
  })();

})();
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>