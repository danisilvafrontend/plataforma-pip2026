<?php
// ── DEVE SER A PRIMEIRA COISA DO ARQUIVO ──
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // obrigatório em HTTPS/produção

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// resto do código...
if (isset($_SESSION['user_id'])) {
    header("Location: /empreendedores/dashboard.php");
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$pageTitle = 'Criar Conta — Impactos Positivos';

$config = require_once __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
    $config['user'], $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

$erros = [];
$data  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) die('Token CSRF inválido.');

    $data = [
        'nome'                    => trim($_POST['nome'] ?? ''),
        'sobrenome'               => trim($_POST['sobrenome'] ?? ''),
        'cpf'                     => preg_replace('/\D/', '', $_POST['cpf'] ?? ''),
        'email'                   => trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL)),
        'celular'                 => trim($_POST['celular'] ?? ''),
        'data_nascimento'         => $_POST['data_nascimento'] ?? null,
        'genero'                  => $_POST['genero'] ?? null,
        'pais'                    => $_POST['pais'] ?? null,
        'estado'                  => trim($_POST['estado'] ?? ''),
        'cidade'                  => trim($_POST['cidade'] ?? ''),
        'regiao'                  => trim($_POST['regiao'] ?? ''),
        'cargo'                   => trim($_POST['cargo'] ?? ''),
        'origem_conhecimento'     => $_POST['origem_conhecimento'] ?? '',
        'consentimento_email'     => isset($_POST['consentimento_email']) ? 1 : 0,
        'consentimento_whatsapp'  => isset($_POST['consentimento_whatsapp']) ? 1 : 0,
        'termos_uso'              => isset($_POST['termos_uso']) ? 1 : 0,
        'senha'                   => $_POST['senha'] ?? '',
        'senha_confirm'           => $_POST['senha_confirm'] ?? '',
        'eh_fundador'             => (($_POST['eh_fundador'] ?? 'Não') === 'Sim') ? 1 : 0,
        'formacao'                => $_POST['formacao'] ?? null,
        'etnia'                   => $_POST['etnia'] ?? null,
    ];

    if ($data['nome'] === '')                              $erros[] = 'Informe seu nome.';
    if ($data['sobrenome'] === '')                         $erros[] = 'Informe seu sobrenome.';
    if (!preg_match('/^\d{11}$/', $data['cpf']))           $erros[] = 'CPF inválido.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if (strlen($data['senha']) < 8)                        $erros[] = 'A senha deve ter ao menos 8 caracteres.';
    if ($data['senha'] !== $data['senha_confirm'])          $erros[] = 'As senhas não coincidem.';
    if (!$data['termos_uso'])                              $erros[] = 'É necessário concordar com os termos de uso.';
    if ($data['eh_fundador'] && empty($data['formacao']))  $erros[] = 'Informe sua formação.';
    if ($data['eh_fundador'] && empty($data['etnia']))     $erros[] = 'Informe sua etnia/raça.';
    if (!$data['eh_fundador']) { $data['formacao'] = null; $data['etnia'] = null; }

    if (empty($erros)) {
        $stmt = $pdo->prepare("SELECT id FROM empreendedores WHERE cpf = ? OR email = ? LIMIT 1");
        $stmt->execute([$data['cpf'], $data['email']]);
        if ($stmt->fetch()) $erros[] = 'Já existe um cadastro com este CPF ou e-mail.';
    }

    if (empty($erros)) {
        $pdo->prepare("INSERT INTO empreendedores
            (nome, sobrenome, cpf, email, celular, data_nascimento, genero, cidade, estado, pais,
             regiao, cargo, origem_conhecimento, consentimento_email, consentimento_whatsapp,
             termos_uso, senha_hash, formacao, etnia, criado_em)
            VALUES
            (:nome,:sobrenome,:cpf,:email,:celular,:data_nascimento,:genero,:cidade,:estado,:pais,
             :regiao,:cargo,:origem_conhecimento,:consentimento_email,:consentimento_whatsapp,
             :termos_uso,:senha_hash,:formacao,:etnia,CURRENT_TIMESTAMP)")
        ->execute([
            ':nome'                   => $data['nome'],
            ':sobrenome'              => $data['sobrenome'],
            ':cpf'                    => $data['cpf'],
            ':email'                  => $data['email'],
            ':celular'                => $data['celular'],
            ':data_nascimento'        => $data['data_nascimento'] ?: null,
            ':genero'                 => $data['genero'] ?: null,
            ':cidade'                 => $data['cidade'] ?: null,
            ':estado'                 => $data['estado'] ?: null,
            ':pais'                   => $data['pais'] ?: null,
            ':regiao'                 => $data['regiao'] ?: null,
            ':cargo'                  => $data['cargo'] ?: null,
            ':origem_conhecimento'    => $data['origem_conhecimento'] ?: null,
            ':consentimento_email'    => $data['consentimento_email'],
            ':consentimento_whatsapp' => $data['consentimento_whatsapp'],
            ':termos_uso'             => $data['termos_uso'],
            ':senha_hash'             => password_hash($data['senha'], PASSWORD_DEFAULT),
            ':formacao'               => $data['formacao'],
            ':etnia'                  => $data['etnia'],
        ]);

        $_SESSION['empreendedor_id']    = (int)$pdo->lastInsertId();
        $_SESSION['empreendedor_nome']  = $data['nome'];
        $_SESSION['empreendedor_email'] = $data['email'];
        $_SESSION['eh_fundador']        = $data['eh_fundador'];

        header("Location: /empreendedores/dashboard.php");
        exit;
    }
}

include __DIR__ . '/../app/views/public/header_public.php';
?>

<!-- Hero do cadastro -->
<section style="background:linear-gradient(135deg,#1E3425 60%,#2d5038); padding:3rem 0 2rem;">
  <div class="container text-center text-white">
    <img src="/assets/images/impactos_positivos_branco.png" alt="Impactos Positivos" style="height:52px; margin-bottom:1.25rem;">
    <h1 style="font-size:2rem; font-weight:800; margin-bottom:.5rem;color:rgba(255,255,255,.75);">Crie sua conta</h1>
    <p style="color:rgba(255,255,255,.75); max-width:520px; margin:0 auto; font-size:.95rem;">
      Registre-se para inscrever seu negócio de impacto no Prêmio Impactos Positivos.
    </p>
  </div>
</section>

<div class="container" style="max-width:860px; margin-top:-1.5rem; padding-bottom:4rem;">

  <!-- Erros -->
  <?php if (!empty($erros)): ?>
    <div class="alert alert-danger rounded-3 mb-4 shadow-sm">
      <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Corrija os erros abaixo:</h6>
      <ul class="mb-0 ps-3 small">
        <?php foreach ($erros as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="/empreendedores/store.php" id="cadastroForm" novalidate>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">

    <!-- ── Apresentação ── -->
    <div class="reg-card mb-4">
      <div class="reg-card-header">
        <i class="bi bi-person-badge-fill"></i> Sobre você
      </div>
      <div class="reg-card-body">
        <p class="text-muted small mb-0">
          Queremos conhecer quem está por trás desta inscrição. Estas informações são fundamentais para contato
          durante o processo e eventuais oportunidades relacionadas à sua iniciativa.
          Se você for o(a) fundador(a), suas respostas ajudarão parceiros e investidores a conhecer a liderança
          por trás do negócio.
        </p>
      </div>
    </div>

    <!-- ── Dados Pessoais ── -->
    <div class="reg-card mb-4">
      <div class="reg-card-header">
        <i class="bi bi-person-fill"></i> Dados Pessoais
      </div>
      <div class="reg-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-600">Nome *</label>
            <input type="text" name="nome" class="form-control"
                   value="<?= htmlspecialchars($data['nome'] ?? '', ENT_QUOTES) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-600">Sobrenome *</label>
            <input type="text" name="sobrenome" class="form-control"
                   value="<?= htmlspecialchars($data['sobrenome'] ?? '', ENT_QUOTES) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-600">CPF *</label>
            <input type="text" name="cpf" class="form-control" placeholder="000.000.000-00"
                   value="<?= htmlspecialchars($data['cpf'] ?? '', ENT_QUOTES) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-600">E-mail *</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($data['email'] ?? '', ENT_QUOTES) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-600">Celular *</label>
            <input type="text" name="celular" class="form-control" placeholder="(11) 90000-0000"
                   value="<?= htmlspecialchars($data['celular'] ?? '', ENT_QUOTES) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-600">Data de nascimento *</label>
            <input type="date" name="data_nascimento" class="form-control"
                   value="<?= htmlspecialchars($data['data_nascimento'] ?? '', ENT_QUOTES) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-600">Gênero *</label>
            <select name="genero" class="form-select" required>
              <option value="">Selecione</option>
              <?php foreach (['Masculino','Feminino','Não Binário','Outros'] as $g): ?>
                <option <?= ($data['genero'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Localização ── -->
    <div class="reg-card mb-4">
      <div class="reg-card-header">
        <i class="bi bi-geo-alt-fill"></i> Localização
      </div>
      <div class="reg-card-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label fw-600">País *</label>
            <select id="pais" name="pais" class="form-select" required>
              <option value="">Selecione</option>
              <optgroup label="América do Sul">
                <?php foreach (['Brasil','Argentina','Chile','Uruguai','Paraguai','Bolívia','Peru','Colômbia','Equador','Venezuela'] as $p): ?>
                  <option value="<?= $p ?>" <?= ($data['pais'] ?? 'Brasil') === $p ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="América do Norte">
                <?php foreach (['Estados Unidos','Canadá','México'] as $p): ?>
                  <option value="<?= $p ?>" <?= ($data['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="América Central">
                <?php foreach (['Costa Rica','Panamá','Guatemala','Honduras','El Salvador','Nicarágua','Belize'] as $p): ?>
                  <option value="<?= $p ?>" <?= ($data['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="Europa">
                <?php foreach (['Portugal','Espanha','França','Alemanha','Itália','Reino Unido','Irlanda','Países Baixos','Bélgica','Suíça','Suécia','Noruega','Dinamarca','Polônia','Grécia'] as $p): ?>
                  <option value="<?= $p ?>" <?= ($data['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="Ásia">
                <?php foreach (['China','Japão','Índia','Coreia do Sul','Singapura','Israel','Turquia'] as $p): ?>
                  <option value="<?= $p ?>" <?= ($data['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="África">
                <?php foreach (['África do Sul','Nigéria','Egito','Quênia','Marrocos'] as $p): ?>
                  <option value="<?= $p ?>" <?= ($data['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="Oceania">
                <?php foreach (['Austrália','Nova Zelândia'] as $p): ?>
                  <option value="<?= $p ?>" <?= ($data['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="Outro">
                <option value="Outro" <?= ($data['pais'] ?? '') === 'Outro' ? 'selected' : '' ?>>Outro</option>
              </optgroup>
            </select>
          </div>
          <div class="col-md-6" id="estado-wrapper">
            <label class="form-label fw-600">Estado *</label>
            <select id="estado" name="estado" class="form-select">
              <option value="">Selecione</option>
              <?php foreach (["Acre","Alagoas","Amapá","Amazonas","Bahia","Ceará","Distrito Federal","Espírito Santo","Goiás","Maranhão","Mato Grosso","Mato Grosso do Sul","Minas Gerais","Pará","Paraíba","Paraná","Pernambuco","Piauí","Rio de Janeiro","Rio Grande do Norte","Rio Grande do Sul","Rondônia","Roraima","Santa Catarina","São Paulo","Sergipe","Tocantins"] as $uf): ?>
                <option <?= ($data['estado'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6" id="cidade-wrapper">
            <label class="form-label fw-600">Cidade *</label>
            <input type="text" name="cidade" class="form-control" placeholder="Digite sua cidade"
                   value="<?= htmlspecialchars($data['cidade'] ?? '', ENT_QUOTES) ?>">
          </div>
          <div class="col-12 d-none" id="regiao-wrapper">
            <label class="form-label fw-600">Região / Província *</label>
            <input type="text" name="regiao" class="form-control"
                   value="<?= htmlspecialchars($data['regiao'] ?? '', ENT_QUOTES) ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- ── Cargo e Perfil ── -->
    <div class="reg-card mb-4">
      <div class="reg-card-header">
        <i class="bi bi-briefcase-fill"></i> Cargo e Perfil
      </div>
      <div class="reg-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-600">Cargo na organização *</label>
            <input type="text" name="cargo" class="form-control"
                   value="<?= htmlspecialchars($data['cargo'] ?? '', ENT_QUOTES) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-600">Como ficou sabendo do Prêmio? *</label>
            <select name="origem_conhecimento" class="form-select" required>
              <option value="">Selecione</option>
              <?php foreach (['Redes sociais','Mídia','Newsletter','Evento','Indicação','Sebrae/ENImpacto','Site Impactos Positivos'] as $o): ?>
                <option <?= ($data['origem_conhecimento'] ?? '') === $o ? 'selected' : '' ?>><?= $o ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label fw-600">Você é o(a) fundador(a)? *</label>
            <small class="d-block text-muted mb-2">Se houver mais fundadores, você poderá adicioná-los no cadastro do negócio.</small>
            <select name="eh_fundador" id="eh_fundador" class="form-select" required>
              <option value="">Selecione</option>
              <option value="Sim" <?= ($data['eh_fundador'] ?? 0) == 1 ? 'selected' : '' ?>>Sim</option>
              <option value="Não" <?= isset($data['eh_fundador']) && $data['eh_fundador'] == 0 ? 'selected' : '' ?>>Não</option>
            </select>
          </div>

          <!-- Fundador extra -->
          <div class="col-md-6 d-none" id="formacao-wrapper">
            <label class="form-label fw-600">Formação *</label>
            <select name="formacao" id="formacao" class="form-select">
              <option value="">Selecione</option>
              <?php foreach (["Ensino Fundamental Incompleto","Ensino Fundamental Completo","Ensino Médio Incompleto","Ensino Médio Completo","Ensino Técnico","Ensino Superior Incompleto","Ensino Superior Completo","Pós-graduação Lato Sensu (Especialização/MBA)","Mestrado","Doutorado","Pós-doutorado"] as $f): ?>
                <option <?= ($data['formacao'] ?? '') === $f ? 'selected' : '' ?>><?= $f ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 d-none" id="etnia-wrapper">
            <label class="form-label fw-600">Etnia / Raça *</label>
            <select name="etnia" id="etnia" class="form-select">
              <option value="">Selecione</option>
              <?php foreach (["Branco(a)","Preto(a)","Pardo(a)","Amarelo(a)","Indígena","Prefiro não responder"] as $e): ?>
                <option <?= ($data['etnia'] ?? '') === $e ? 'selected' : '' ?>><?= $e ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Segurança ── -->
    <div class="reg-card mb-4">
      <div class="reg-card-header">
        <i class="bi bi-shield-lock-fill"></i> Segurança
      </div>
      <div class="reg-card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-600">Senha *</label>
            <input type="password" name="senha" class="form-control" required minlength="8">
            <small class="text-muted">Mínimo 8 caracteres.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-600">Confirmar senha *</label>
            <input type="password" name="senha_confirm" class="form-control" required minlength="8">
          </div>
        </div>
      </div>
    </div>

    <!-- ── Consentimento ── -->
    <div class="reg-card mb-4">
      <div class="reg-card-header">
        <i class="bi bi-bell-fill"></i> Comunicação e Termos
      </div>
      <div class="reg-card-body">
        <div class="reg-check-item mb-2">
          <input type="checkbox" name="consentimento_email" value="1"
                 class="form-check-input" id="consentEmail"
                 <?= !empty($data['consentimento_email']) ? 'checked' : '' ?>>
          <label for="consentEmail">
            <span class="fw-600 d-block">Atualizações por e-mail</span>
            <small class="text-muted">Aceito receber novidades da plataforma por e-mail.</small>
          </label>
        </div>
        <div class="reg-check-item mb-3">
          <input type="checkbox" name="consentimento_whatsapp" value="1"
                 class="form-check-input" id="consentWhats"
                 <?= !empty($data['consentimento_whatsapp']) ? 'checked' : '' ?>>
          <label for="consentWhats">
            <span class="fw-600 d-block">Atualizações por WhatsApp</span>
            <small class="text-muted">Aceito receber novidades da plataforma por WhatsApp.</small>
          </label>
        </div>
        <div class="reg-check-item reg-check-required">
          <input type="checkbox" name="termos_uso" value="1"
                 class="form-check-input" id="termosUso" required
                 <?= !empty($data['termos_uso']) ? 'checked' : '' ?>>
          <label for="termosUso">
            <span class="fw-600 d-block">Termos de Uso *</span>
            <small>Concordo com os
              <a href="https://impactospositivos.com/termos-de-uso/" target="_blank">Termos de Uso</a> e a
              <a href="https://impactospositivos.com/politica-de-privacidade/" target="_blank">Política de Privacidade</a>.
            </small>
          </label>
        </div>
      </div>
    </div>

    <!-- ── Ação ── -->
    <!-- Trecho final do form, substitua por: -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3"
     style="color:#212529;">
  <span class="small" style="color:#4a5e4f;">
    Já tem conta?
    <a href="/login.php" style="color:#1E3425; font-weight:700;">Faça login</a>
  </span>
  <button type="submit" class="btn-reg-submit">
    <i class="bi bi-person-check me-2"></i> Criar minha conta
  </button>
</div>

  </form>
</div>

<script>
const form       = document.getElementById('cadastroForm');
const ehFundador = document.getElementById('eh_fundador');
const formacao   = document.getElementById('formacao');
const etnia      = document.getElementById('etnia');
const paisSel    = document.getElementById('pais');

// Fundador toggle
ehFundador.addEventListener('change', function () {
  const isFund = this.value === 'Sim';
  document.getElementById('formacao-wrapper').classList.toggle('d-none', !isFund);
  document.getElementById('etnia-wrapper').classList.toggle('d-none', !isFund);
  if (!isFund) { formacao.value = ''; etnia.value = ''; }
});

// País toggle
paisSel.addEventListener('change', function () {
  const brasil = this.value === 'Brasil';
  const outro  = this.value !== '' && !brasil;
  document.getElementById('estado-wrapper').classList.toggle('d-none', !brasil);
  document.getElementById('cidade-wrapper').classList.toggle('d-none', !brasil);
  document.getElementById('regiao-wrapper').classList.toggle('d-none', !outro);
});

// Validação client-side
form.addEventListener('submit', function (e) {
  const erros = [];
  const cpf   = this.cpf.value.replace(/\D/g, '');
  if (!this.nome.value.trim())                            erros.push('Informe seu nome.');
  if (!this.sobrenome.value.trim())                       erros.push('Informe seu sobrenome.');
  if (!validarCPF(cpf))                                   erros.push('CPF inválido.');
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email.value)) erros.push('E-mail inválido.');
  if (this.senha.value.length < 8)                        erros.push('Senha: mínimo 8 caracteres.');
  if (this.senha.value !== this.senha_confirm.value)      erros.push('As senhas não coincidem.');
  if (ehFundador.value === 'Sim' && !formacao.value)      erros.push('Informe sua formação.');
  if (ehFundador.value === 'Sim' && !etnia.value)         erros.push('Informe sua etnia/raça.');
  if (erros.length) { e.preventDefault(); alert('Corrija os erros:\n\n' + erros.join('\n')); }
});

function validarCPF(cpf) {
  if (!cpf || cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
  let s = 0, r;
  for (let i = 1; i <= 9; i++) s += parseInt(cpf[i-1]) * (11 - i);
  r = (s * 10) % 11; if (r >= 10) r = 0;
  if (r !== parseInt(cpf[9])) return false;
  s = 0;
  for (let i = 1; i <= 10; i++) s += parseInt(cpf[i-1]) * (12 - i);
  r = (s * 10) % 11; if (r >= 10) r = 0;
  return r === parseInt(cpf[10]);
}
</script>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>