<?php
// /public_html/empreendedores/editar_conta.php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$pageTitle = 'Meus Dados — Impactos Positivos';

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'], $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$stmt = $pdo->prepare("SELECT nome, sobrenome, cpf, email, celular, data_nascimento, genero,
    pais, estado, cidade, regiao, cargo, eh_fundador, formacao, etnia
    FROM empreendedores WHERE id = ?");
$stmt->execute([$_SESSION['empreendedor_id']]);
$empreendedor = $stmt->fetch();
if (!$empreendedor) die("Empreendedor não encontrado.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome            = trim($_POST['nome'] ?? '');
    $sobrenome       = trim($_POST['sobrenome'] ?? '');
    $cpf             = trim($_POST['cpf'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $celular         = trim($_POST['celular'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? null;
    $genero          = $_POST['genero'] ?? '';
    $pais            = $_POST['pais'] ?? '';
    $estado          = $_POST['estado'] ?? '';
    $cidade          = $_POST['cidade'] ?? '';
    $regiao          = $_POST['regiao'] ?? '';
    $cargo           = $_POST['cargo'] ?? '';
    $senha           = $_POST['senha'] ?? '';
    $eh_fundador     = ($_POST['eh_fundador'] ?? '0') === '1' ? 1 : 0;
    $formacao        = $_POST['formacao'] ?? null;
    $etnia           = $_POST['etnia'] ?? null;
    $forcarLimpeza   = [];

    if ($pais === 'Brasil') { $regiao = null; $forcarLimpeza[] = 'regiao'; }
    else { $estado = null; $cidade = null; $forcarLimpeza[] = 'estado'; $forcarLimpeza[] = 'cidade'; }

    $campos = []; $params = [];
    if ($nome !== '')              { $campos[] = "nome = ?";             $params[] = $nome; }
    if ($sobrenome !== '')         { $campos[] = "sobrenome = ?";        $params[] = $sobrenome; }
    if ($cpf !== '')               { $campos[] = "cpf = ?";              $params[] = $cpf; }
    if ($email !== '')             { $campos[] = "email = ?";            $params[] = $email; }
    if ($celular !== '')           { $campos[] = "celular = ?";          $params[] = $celular; }
    if (!empty($data_nascimento))  { $campos[] = "data_nascimento = ?";  $params[] = $data_nascimento; }
    if ($genero !== '')            { $campos[] = "genero = ?";           $params[] = $genero; }
    if ($cargo !== '')             { $campos[] = "cargo = ?";            $params[] = $cargo; }
    if ($pais !== '')              { $campos[] = "pais = ?";             $params[] = $pais; }
    if ($estado !== '')            { $campos[] = "estado = ?";           $params[] = $estado; }
    if ($cidade !== '')            { $campos[] = "cidade = ?";           $params[] = $cidade; }
    if ($regiao !== '')            { $campos[] = "regiao = ?";           $params[] = $regiao; }
    $campos[] = "eh_fundador = ?"; $params[] = $eh_fundador;
    $campos[] = "formacao = ?";    $params[] = $formacao;
    $campos[] = "etnia = ?";       $params[] = $etnia;
    if ($senha !== '') { $campos[] = "senha_hash = ?"; $params[] = password_hash($senha, PASSWORD_DEFAULT); }
    foreach ($forcarLimpeza as $c) { $campos[] = "$c = ?"; $params[] = null; }

    if (!empty($campos)) {
        $params[] = $_SESSION['empreendedor_id'];
        $pdo->prepare("UPDATE empreendedores SET " . implode(", ", $campos) . " WHERE id = ?")->execute($params);
        if ($nome !== '')  $_SESSION['empreendedor_nome']  = $nome;
        if ($email !== '') $_SESSION['empreendedor_email'] = $email;
        $_SESSION['eh_fundador']    = $eh_fundador;
        $_SESSION['flash_message']  = "Dados atualizados com sucesso!";
        header("Location: /empreendedores/editar_conta.php");
        exit;
    }
}

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<?php if (!empty($_SESSION['flash_message'])): ?>
  <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    <?= htmlspecialchars($_SESSION['flash_message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<!-- Título -->
<div class="mb-4">
  <h1 class="emp-page-title mb-1"><i class="bi bi-person-vcard me-2"></i>Meus Dados</h1>
  <p class="emp-page-subtitle mb-0">Mantenha suas informações pessoais sempre atualizadas</p>
</div>

<form method="post">
<div class="row g-4">

  <!-- ── Coluna principal ── -->
  <div class="col-12 col-lg-8">

    <!-- Dados Pessoais -->
    <div class="emp-card mb-4">
      <div class="emp-card-header">
        <i class="bi bi-person-fill"></i> Dados Pessoais
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-600">Nome</label>
          <input type="text" name="nome" class="form-control"
                 value="<?= htmlspecialchars($empreendedor['nome'] ?? '', ENT_QUOTES) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-600">Sobrenome</label>
          <input type="text" name="sobrenome" class="form-control"
                 value="<?= htmlspecialchars($empreendedor['sobrenome'] ?? '', ENT_QUOTES) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-600">CPF</label>
          <input type="text" name="cpf" class="form-control"
                 value="<?= htmlspecialchars($empreendedor['cpf'] ?? '', ENT_QUOTES) ?>"
                 placeholder="000.000.000-00">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-600">E-mail</label>
          <input type="email" name="email" class="form-control"
                 value="<?= htmlspecialchars($empreendedor['email'] ?? '', ENT_QUOTES) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600">Celular</label>
          <input type="text" name="celular" class="form-control"
                 value="<?= htmlspecialchars($empreendedor['celular'] ?? '', ENT_QUOTES) ?>"
                 placeholder="(11) 90000-0000">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600">Data de nascimento</label>
          <input type="date" name="data_nascimento" class="form-control"
                 value="<?= htmlspecialchars($empreendedor['data_nascimento'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600">Gênero</label>
          <select name="genero" class="form-select">
            <option value="">Selecione</option>
            <?php foreach (['Masculino','Feminino','Não Binário','Outros'] as $g): ?>
              <option <?= ($empreendedor['genero'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Localização -->
    <div class="emp-card mb-4">
      <div class="emp-card-header">
        <i class="bi bi-geo-alt-fill"></i> Localização
      </div>

      <div class="row g-3">
        <div class="col-12">
          <label class="form-label fw-600">País</label>
          <select name="pais" id="pais" class="form-select" required>
            <option value="">Selecione</option>
            <optgroup label="América do Sul">
              <?php foreach (['Brasil','Argentina','Chile','Uruguai','Paraguai','Bolívia','Peru','Colômbia','Equador','Venezuela'] as $p): ?>
                <option value="<?= $p ?>" <?= ($empreendedor['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="América do Norte">
              <?php foreach (['Estados Unidos','Canadá','México'] as $p): ?>
                <option value="<?= $p ?>" <?= ($empreendedor['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="América Central">
              <?php foreach (['Costa Rica','Panamá','Guatemala','Honduras','El Salvador','Nicarágua','Belize'] as $p): ?>
                <option value="<?= $p ?>" <?= ($empreendedor['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="Europa">
              <?php foreach (['Portugal','Espanha','França','Alemanha','Itália','Reino Unido','Irlanda','Países Baixos','Bélgica','Suíça','Suécia','Noruega','Dinamarca','Polônia','Grécia'] as $p): ?>
                <option value="<?= $p ?>" <?= ($empreendedor['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="Ásia">
              <?php foreach (['China','Japão','Índia','Coreia do Sul','Singapura','Israel','Turquia'] as $p): ?>
                <option value="<?= $p ?>" <?= ($empreendedor['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="África">
              <?php foreach (['África do Sul','Nigéria','Egito','Quênia','Marrocos'] as $p): ?>
                <option value="<?= $p ?>" <?= ($empreendedor['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="Oceania">
              <?php foreach (['Austrália','Nova Zelândia'] as $p): ?>
                <option value="<?= $p ?>" <?= ($empreendedor['pais'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="Outro">
              <option value="Outro" <?= ($empreendedor['pais'] ?? '') === 'Outro' ? 'selected' : '' ?>>Outro</option>
            </optgroup>
          </select>
        </div>

        <!-- Brasil -->
        <div class="col-md-6 <?= ($empreendedor['pais'] ?? '') !== 'Brasil' ? 'd-none' : '' ?>" id="estado-wrapper">
          <label class="form-label fw-600">Estado</label>
          <select name="estado" id="estado" class="form-select">
            <option value="">Selecione</option>
            <?php foreach (["Acre","Alagoas","Amapá","Amazonas","Bahia","Ceará","Distrito Federal","Espírito Santo","Goiás","Maranhão","Mato Grosso","Mato Grosso do Sul","Minas Gerais","Pará","Paraíba","Paraná","Pernambuco","Piauí","Rio de Janeiro","Rio Grande do Norte","Rio Grande do Sul","Rondônia","Roraima","Santa Catarina","São Paulo","Sergipe","Tocantins"] as $uf): ?>
              <option <?= ($empreendedor['estado'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6 <?= ($empreendedor['pais'] ?? '') !== 'Brasil' ? 'd-none' : '' ?>" id="cidade-wrapper">
          <label class="form-label fw-600">Cidade</label>
          <input type="text" name="cidade" class="form-control"
                 value="<?= htmlspecialchars($empreendedor['cidade'] ?? '', ENT_QUOTES) ?>">
        </div>

        <!-- Internacional -->
        <div class="col-12 <?= (empty($empreendedor['pais']) || $empreendedor['pais'] === 'Brasil') ? 'd-none' : '' ?>" id="regiao-wrapper">
          <label class="form-label fw-600">Região / Província</label>
          <input type="text" name="regiao" class="form-control"
                 value="<?= htmlspecialchars($empreendedor['regiao'] ?? '', ENT_QUOTES) ?>">
        </div>
      </div>
    </div>

    <!-- Cargo e Perfil -->
    <div class="emp-card mb-4">
      <div class="emp-card-header">
        <i class="bi bi-briefcase-fill"></i> Cargo e Perfil
      </div>

      <div class="row g-3">
        <div class="col-12">
          <label class="form-label fw-600">Cargo na organização</label>
          <input type="text" name="cargo" class="form-control"
                 value="<?= htmlspecialchars($empreendedor['cargo'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-600">Você é fundador?</label>
          <select name="eh_fundador" id="eh_fundador" class="form-select" required>
            <option value="0" <?= (int)($empreendedor['eh_fundador'] ?? 0) === 0 ? 'selected' : '' ?>>Não</option>
            <option value="1" <?= (int)($empreendedor['eh_fundador'] ?? 0) === 1 ? 'selected' : '' ?>>Sim</option>
          </select>
        </div>

        <!-- Extra fundador -->
        <div class="col-md-4 <?= (int)($empreendedor['eh_fundador'] ?? 0) !== 1 ? 'd-none' : '' ?>" id="formacao-wrapper">
          <label class="form-label fw-600">Formação</label>
          <select name="formacao" class="form-select">
            <option value="">Selecione</option>
            <?php foreach (["Ensino Fundamental","Ensino Médio Completo","Ensino Médio Incompleto","Ensino Superior Completo","Ensino Superior Incompleto","Pós-graduação","Mestrado"] as $f): ?>
              <option <?= ($empreendedor['formacao'] ?? '') === $f ? 'selected' : '' ?>><?= $f ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4 <?= (int)($empreendedor['eh_fundador'] ?? 0) !== 1 ? 'd-none' : '' ?>" id="etnia-wrapper">
          <label class="form-label fw-600">Etnia / Raça</label>
          <select name="etnia" class="form-select">
            <option value="">Selecione</option>
            <?php foreach (["Branco(a)","Preto(a)","Pardo(a)","Amarelo(a)","Indígena","Prefiro não responder"] as $e): ?>
              <option <?= ($empreendedor['etnia'] ?? '') === $e ? 'selected' : '' ?>><?= $e ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

  </div>

  <!-- ── Coluna lateral ── -->
  <div class="col-12 col-lg-4">

    <!-- Segurança -->
    <div class="emp-card mb-4">
      <div class="emp-card-header">
        <i class="bi bi-shield-lock-fill"></i> Segurança
      </div>
      <label class="form-label fw-600">Nova senha</label>
      <input type="password" name="senha" class="form-control mb-1">
      <small class="text-muted">Deixe em branco para não alterar.</small>
    </div>

    <!-- Ações -->
    <div class="emp-card">
      <div class="emp-card-header">
        <i class="bi bi-floppy-fill"></i> Salvar
      </div>
      <p class="small text-muted mb-3">Revise as informações antes de salvar.</p>
      <button type="submit" class="btn-emp-primary w-100 justify-content-center">
        <i class="bi bi-floppy me-2"></i> Salvar Alterações
      </button>
      <a href="/empreendedores/dashboard.php" class="btn-emp-outline w-100 justify-content-center mt-2">
        <i class="bi bi-arrow-left me-2"></i> Voltar ao Dashboard
      </a>
    </div>

  </div>
</div>
</form>

<script>
  // País → estado/cidade/região
  document.getElementById('pais').addEventListener('change', function () {
    const brasil = this.value === 'Brasil';
    const outro  = this.value !== '' && !brasil;
    document.getElementById('estado-wrapper').classList.toggle('d-none', !brasil);
    document.getElementById('cidade-wrapper').classList.toggle('d-none', !brasil);
    document.getElementById('regiao-wrapper').classList.toggle('d-none', !outro);
  });

  // Fundador → formação/etnia
  document.getElementById('eh_fundador').addEventListener('change', function () {
    const isFundador = this.value === '1';
    document.getElementById('formacao-wrapper').classList.toggle('d-none', !isFundador);
    document.getElementById('etnia-wrapper').classList.toggle('d-none', !isFundador);
    if (!isFundador) {
      document.querySelector('[name="formacao"]').value = '';
      document.querySelector('[name="etnia"]').value = '';
    }
  });
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>