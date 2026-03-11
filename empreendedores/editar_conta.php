<?php
// /public_html/empreendedores/editar_conta.php
declare(strict_types=1);
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$config = require __DIR__ . '/../app/config/db.php';

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
} catch (PDOException $e) {
    die('Erro na conexão com o banco: ' . $e->getMessage());
}

// Busca dados atuais do empreendedor
$stmt = $pdo->prepare("SELECT 
    nome, sobrenome, cpf, email, celular, data_nascimento, genero, pais, estado, cidade, regiao, cargo,
    eh_fundador, formacao, etnia
FROM empreendedores WHERE id = ?");
$stmt->execute([$_SESSION['empreendedor_id']]);
$empreendedor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empreendedor) {
    die("Empreendedor não encontrado.");
}

// Se formulário enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome   = trim($_POST['nome'] ?? '');
    $sobrenome = trim($_POST['sobrenome'] ?? '');
    $cpf    = trim($_POST['cpf'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $celular = trim($_POST['celular'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? null;
    $genero = $_POST['genero'] ?? '';
    $pais   = $_POST['pais'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $regiao = $_POST['regiao'] ?? '';
    $cargo  = $_POST['cargo'] ?? '';
    $senha  = $_POST['senha'] ?? '';
    $eh_fundador = ($_POST['eh_fundador'] ?? '0') === '1' ? 1 : 0;
    $formacao   = $_POST['formacao'] ?? null;
    $etnia      = $_POST['etnia'] ?? null;

    $forcarLimpeza = [];

    if ($pais === 'Brasil') {
        $regiao = null;
        $forcarLimpeza[] = 'regiao';
    } else {
        $estado = null;
        $cidade = null;
        $forcarLimpeza[] = 'estado';
        $forcarLimpeza[] = 'cidade';
    }

    $campos = [];
    $params = [];

    if ($nome !== '') { $campos[] = "nome = ?"; $params[] = $nome; }
    if ($sobrenome !== '') { $campos[] = "sobrenome = ?"; $params[] = $sobrenome; }
    if ($cpf !== '') { $campos[] = "cpf = ?"; $params[] = $cpf; }
    if ($email !== '') { $campos[] = "email = ?"; $params[] = $email; }
    if ($celular !== '') { $campos[] = "celular = ?"; $params[] = $celular; }
    if (!empty($data_nascimento)) { $campos[] = "data_nascimento = ?"; $params[] = $data_nascimento; }
    if ($genero !== '') { $campos[] = "genero = ?"; $params[] = $genero; }
    if ($cargo !== '') { $campos[] = "cargo = ?"; $params[] = $cargo; }
    if ($pais !== '') { $campos[] = "pais = ?"; $params[] = $pais; }
    if ($estado !== '') { $campos[] = "estado = ?"; $params[] = $estado; }
    if ($cidade !== '') { $campos[] = "cidade = ?"; $params[] = $cidade; }
    if ($regiao !== '') { $campos[] = "regiao = ?"; $params[] = $regiao; }

    // Novos campos
    $campos[] = "eh_fundador = ?"; $params[] = $eh_fundador;
    $campos[] = "formacao = ?";   $params[] = $formacao;
    $campos[] = "etnia = ?";      $params[] = $etnia;

    if ($senha !== '') {
        $campos[] = "senha_hash = ?"; $params[] = password_hash($senha, PASSWORD_DEFAULT);
    }

    foreach ($forcarLimpeza as $campo) {
        $campos[] = "$campo = ?";
        $params[] = null;
    }

    if (!empty($campos)) {
        $sql = "UPDATE empreendedores SET " . implode(", ", $campos) . " WHERE id = ?";
        $params[] = $_SESSION['empreendedor_id'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($nome !== '') { $_SESSION['empreendedor_nome'] = $nome; }
        if ($email !== '') { $_SESSION['empreendedor_email'] = $email; }
        $_SESSION['eh_fundador'] = $eh_fundador;

        $_SESSION['flash_message'] = "Dados atualizados com sucesso!";
        header("Location: /empreendedores/dashboard.php");
        exit;
    }
}

include __DIR__ . '/../app/views/empreendedor/header.php';
?>


<div class="container my-5">
  <h2>Editar Conta</h2>

  <?php if (!empty($msg)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="post">

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" class="form-control" 
            value="<?= htmlspecialchars($empreendedor['nome'] ?? '', ENT_QUOTES) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Sobrenome</label>
            <input type="text" name="sobrenome" class="form-control" 
                value="<?= htmlspecialchars($empreendedor['sobrenome'] ?? '', ENT_QUOTES) ?>" required>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">CPF</label>
            <input type="text" name="cpf" class="form-control" 
                value="<?= htmlspecialchars($empreendedor['cpf'] ?? '', ENT_QUOTES) ?>" placeholder="000.000.000-00">
        </div>
        <div class="col-md-6">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" 
                value="<?= htmlspecialchars($empreendedor['email'] ?? '', ENT_QUOTES) ?>" required>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Celular</label>
            <input type="text" name="celular" class="form-control" 
                value="<?= htmlspecialchars($empreendedor['celular'] ?? '', ENT_QUOTES) ?>" placeholder="(11) 90000-0000">
        </div>
        <div class="col-md-4">
            <label class="form-label">Data de nascimento</label>
            <input type="date" name="data_nascimento" class="form-control" 
                value="<?= htmlspecialchars($empreendedor['data_nascimento'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Gênero</label>
            <select name="genero" class="form-select">
                <option value="">Selecione</option>
                <option <?= ($empreendedor['genero'] ?? '') === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                <option <?= ($empreendedor['genero'] ?? '') === 'Feminino' ? 'selected' : '' ?>>Feminino</option>
                <option <?= ($empreendedor['genero'] ?? '') === 'Não Binário' ? 'selected' : '' ?>>Não Binário</option>
                <option <?= ($empreendedor['genero'] ?? '') === 'Outros' ? 'selected' : '' ?>>Outros</option>
            </select>
        </div>
    </div>

  <div class="mb-3">
    <label class="form-label">País</label>
    <select name="pais" id="pais" class="form-select" required>
        <option value="">Selecione</option>

        <optgroup label="América do Norte">
            <option value="Estados Unidos" <?= ($empreendedor['pais'] ?? '') === 'Estados Unidos' ? 'selected' : '' ?>>Estados Unidos</option>
            <option value="Canadá" <?= ($empreendedor['pais'] ?? '') === 'Canadá' ? 'selected' : '' ?>>Canadá</option>
            <option value="México" <?= ($empreendedor['pais'] ?? '') === 'México' ? 'selected' : '' ?>>México</option>
        </optgroup>

        <optgroup label="América Central">
            <option value="Costa Rica" <?= ($empreendedor['pais'] ?? '') === 'Costa Rica' ? 'selected' : '' ?>>Costa Rica</option>
            <option value="Panamá" <?= ($empreendedor['pais'] ?? '') === 'Panamá' ? 'selected' : '' ?>>Panamá</option>
            <option value="Guatemala" <?= ($empreendedor['pais'] ?? '') === 'Guatemala' ? 'selected' : '' ?>>Guatemala</option>
            <option value="Honduras" <?= ($empreendedor['pais'] ?? '') === 'Honduras' ? 'selected' : '' ?>>Honduras</option>
            <option value="El Salvador" <?= ($empreendedor['pais'] ?? '') === 'El Salvador' ? 'selected' : '' ?>>El Salvador</option>
            <option value="Nicarágua" <?= ($empreendedor['pais'] ?? '') === 'Nicarágua' ? 'selected' : '' ?>>Nicarágua</option>
            <option value="Belize" <?= ($empreendedor['pais'] ?? '') === 'Belize' ? 'selected' : '' ?>>Belize</option>
        </optgroup>

        <optgroup label="América do Sul">
            <option value="Brasil" <?= ($empreendedor['pais'] ?? '') === 'Brasil' ? 'selected' : '' ?>>Brasil</option>
            <option value="Argentina" <?= ($empreendedor['pais'] ?? '') === 'Argentina' ? 'selected' : '' ?>>Argentina</option>
            <option value="Chile" <?= ($empreendedor['pais'] ?? '') === 'Chile' ? 'selected' : '' ?>>Chile</option>
            <option value="Uruguai" <?= ($empreendedor['pais'] ?? '') === 'Uruguai' ? 'selected' : '' ?>>Uruguai</option>
            <option value="Paraguai" <?= ($empreendedor['pais'] ?? '') === 'Paraguai' ? 'selected' : '' ?>>Paraguai</option>
            <option value="Bolívia" <?= ($empreendedor['pais'] ?? '') === 'Bolívia' ? 'selected' : '' ?>>Bolívia</option>
            <option value="Peru" <?= ($empreendedor['pais'] ?? '') === 'Peru' ? 'selected' : '' ?>>Peru</option>
            <option value="Colômbia" <?= ($empreendedor['pais'] ?? '') === 'Colômbia' ? 'selected' : '' ?>>Colômbia</option>
            <option value="Equador" <?= ($empreendedor['pais'] ?? '') === 'Equador' ? 'selected' : '' ?>>Equador</option>
            <option value="Venezuela" <?= ($empreendedor['pais'] ?? '') === 'Venezuela' ? 'selected' : '' ?>>Venezuela</option>
        </optgroup>

        <optgroup label="Europa">
            <option value="Portugal" <?= ($empreendedor['pais'] ?? '') === 'Portugal' ? 'selected' : '' ?>>Portugal</option>
            <option value="Espanha" <?= ($empreendedor['pais'] ?? '') === 'Espanha' ? 'selected' : '' ?>>Espanha</option>
            <option value="França" <?= ($empreendedor['pais'] ?? '') === 'França' ? 'selected' : '' ?>>França</option>
            <option value="Alemanha" <?= ($empreendedor['pais'] ?? '') === 'Alemanha' ? 'selected' : '' ?>>Alemanha</option>
            <option value="Itália" <?= ($empreendedor['pais'] ?? '') === 'Itália' ? 'selected' : '' ?>>Itália</option>
            <option value="Reino Unido" <?= ($empreendedor['pais'] ?? '') === 'Reino Unido' ? 'selected' : '' ?>>Reino Unido</option>
            <option value="Irlanda" <?= ($empreendedor['pais'] ?? '') === 'Irlanda' ? 'selected' : '' ?>>Irlanda</option>
            <option value="Países Baixos" <?= ($empreendedor['pais'] ?? '') === 'Países Baixos' ? 'selected' : '' ?>>Países Baixos</option>
            <option value="Bélgica" <?= ($empreendedor['pais'] ?? '') === 'Bélgica' ? 'selected' : '' ?>>Bélgica</option>
            <option value="Suíça" <?= ($empreendedor['pais'] ?? '') === 'Suíça' ? 'selected' : '' ?>>Suíça</option>
            <option value="Suécia" <?= ($empreendedor['pais'] ?? '') === 'Suécia' ? 'selected' : '' ?>>Suécia</option>
            <option value="Noruega" <?= ($empreendedor['pais'] ?? '') === 'Noruega' ? 'selected' : '' ?>>Noruega</option>
            <option value="Dinamarca" <?= ($empreendedor['pais'] ?? '') === 'Dinamarca' ? 'selected' : '' ?>>Dinamarca</option>
            <option value="Polônia" <?= ($empreendedor['pais'] ?? '') === 'Polônia' ? 'selected' : '' ?>>Polônia</option>
            <option value="Grécia" <?= ($empreendedor['pais'] ?? '') === 'Grécia' ? 'selected' : '' ?>>Grécia</option>
        </optgroup>

        <optgroup label="Ásia">
            <option value="China" <?= ($empreendedor['pais'] ?? '') === 'China' ? 'selected' : '' ?>>China</option>
            <option value="Japão" <?= ($empreendedor['pais'] ?? '') === 'Japão' ? 'selected' : '' ?>>Japão</option>
            <option value="Índia" <?= ($empreendedor['pais'] ?? '') === 'Índia' ? 'selected' : '' ?>>Índia</option>
            <option value="Coreia do Sul" <?= ($empreendedor['pais'] ?? '') === 'Coreia do Sul' ? 'selected' : '' ?>>Coreia do Sul</option>
            <option value="Singapura" <?= ($empreendedor['pais'] ?? '') === 'Singapura' ? 'selected' : '' ?>>Singapura</option>
            <option value="Israel" <?= ($empreendedor['pais'] ?? '') === 'Israel' ? 'selected' : '' ?>>Israel</option>
            <option value="Turquia" <?= ($empreendedor['pais'] ?? '') === 'Turquia' ? 'selected' : '' ?>>Turquia</option>
        </optgroup>

        <optgroup label="África">
            <option value="África do Sul" <?= ($empreendedor['pais'] ?? '') === 'África do Sul' ? 'selected' : '' ?>>África do Sul</option>
            <option value="Nigéria" <?= ($empreendedor['pais'] ?? '') === 'Nigéria' ? 'selected' : '' ?>>Nigéria</option>
            <option value="Egito" <?= ($empreendedor['pais'] ?? '') === 'Egito' ? 'selected' : '' ?>>Egito</option>
            <option value="Quênia" <?= ($empreendedor['pais'] ?? '') === 'Quênia' ? 'selected' : '' ?>>Quênia</option>
            <option value="Marrocos" <?= ($empreendedor['pais'] ?? '') === 'Marrocos' ? 'selected' : '' ?>>Marrocos</option>
        </optgroup>

        <optgroup label="Oceania">
            <option value="Austrália" <?= ($empreendedor['pais'] ?? '') === 'Austrália' ? 'selected' : '' ?>>Austrália</option>
            <option value="Nova Zelândia" <?= ($empreendedor['pais'] ?? '') === 'Nova Zelândia' ? 'selected' : '' ?>>Nova Zelândia</option>
        </optgroup>

        <optgroup label="Outro">
            <option value="Outro" <?= ($empreendedor['pais'] ?? '') === 'Outro' ? 'selected' : '' ?>>Outro</option>
        </optgroup>
        </select>
    </div>
    <div class="mb-3" id="estado-wrapper">
        <label class="form-label">Estado</label>
        <select id="estado" name="estado" class="form-select">
            <option value="">Selecione o estado</option>
            <?php
            $estados = [
            "Acre","Alagoas","Amapá","Amazonas","Bahia","Ceará","Distrito Federal","Espírito Santo",
            "Goiás","Maranhão","Mato Grosso","Mato Grosso do Sul","Minas Gerais","Pará","Paraíba",
            "Paraná","Pernambuco","Piauí","Rio de Janeiro","Rio Grande do Norte","Rio Grande do Sul",
            "Rondônia","Roraima","Santa Catarina","São Paulo","Sergipe","Tocantins"
            ];
            foreach ($estados as $estado) {
                $selected = ($empreendedor['estado'] ?? '') === $estado ? 'selected' : '';
                echo "<option value=\"$estado\" $selected>$estado</option>";
            }
            ?>
        </select>
    </div>


    <div class="mb-3" id="cidade-wrapper">
        <label class="form-label">Cidade</label>
        <input type="text" name="cidade" class="form-control" 
           value="<?= htmlspecialchars($empreendedor['cidade'] ?? '', ENT_QUOTES) ?>">
    </div>

    <div class="mb-3 d-none" id="regiao-wrapper">
        <label class="form-label">Região/Província</label>
        <input type="text" name="regiao" class="form-control" 
           value="<?= htmlspecialchars($empreendedor['regiao'] ?? '', ENT_QUOTES) ?>">
    </div>
<script>
document.getElementById('pais').addEventListener('change', function() {
  const estadoWrapper = document.getElementById('estado-wrapper');
  const cidadeWrapper = document.getElementById('cidade-wrapper');
  const regiaoWrapper = document.getElementById('regiao-wrapper');

  if (this.value === 'Brasil') {
    estadoWrapper.classList.remove('d-none');
    cidadeWrapper.classList.remove('d-none');
    regiaoWrapper.classList.add('d-none');
  } else if (this.value) {
    estadoWrapper.classList.add('d-none');
    cidadeWrapper.classList.add('d-none');
    regiaoWrapper.classList.remove('d-none');
  } else {
    // nenhum país selecionado
    estadoWrapper.classList.add('d-none');
    cidadeWrapper.classList.add('d-none');
    regiaoWrapper.classList.add('d-none');
  }
});
</script>

    <div class="mb-3">
        <label class="form-label">Cargo na organização</label>
        <input type="text" name="cargo" class="form-control" 
           value="<?= htmlspecialchars($empreendedor['cargo'] ?? '', ENT_QUOTES) ?>">
    </div>

     <!-- Fundador + Formação + Etnia (condicional) -->
    <div class="mb-3">
      <label class="form-label">Você é fundador?</label>
      <select name="eh_fundador" id="eh_fundador" class="form-select" required>
        <option value="0" <?= (int)($empreendedor['eh_fundador'] ?? 0) === 0 ? 'selected' : '' ?>>Não</option>
        <option value="1" <?= (int)($empreendedor['eh_fundador'] ?? 0) === 1 ? 'selected' : '' ?>>Sim</option>
      </select>
    </div>

    <div id="fundador-extra" class="<?= (int)($empreendedor['eh_fundador'] ?? 0) === 1 ? '' : 'd-none' ?>">
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Formação</label>
          <select name="formacao" class="form-select">
            <option value="">Selecione</option>
            <?php
              $formacoes = [
                "Ensino Fundamental",
                "Ensino Médio Completo",
                "Ensino Médio Incompleto",
                "Ensino Superior Completo",
                "Ensino Superior Incompleto",
                "Pós-graduação",
                "Mestrado"
              ];
              $formacaoAtual = $empreendedor['formacao'] ?? '';
              foreach ($formacoes as $f) {
                  $sel = ($formacaoAtual === $f) ? 'selected' : '';
                  echo "<option value=\"$f\" $sel>$f</option>";
              }
            ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Etnia/Raça</label>
          <select name="etnia" class="form-select">
            <option value="">Selecione</option>
            <?php
              $etnias = [
                "Branco(a)",
                "Preto(a)",
                "Pardo(a)",
                "Amarelo(a)",
                "Indígena",
                "Prefiro não responder"
              ];
              $etniaAtual = $empreendedor['etnia'] ?? '';
              foreach ($etnias as $e) {
                  $sel = ($etniaAtual === $e) ? 'selected' : '';
                  echo "<option value=\"$e\" $sel>$e</option>";
              }
            ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Senha -->
    <div class="mb-3">
      <label class="form-label">Nova senha (opcional)</label>
      <input type="password" name="senha" class="form-control">
      <small class="text-muted">Deixe em branco se não quiser alterar.</small>
    </div>

    <button type="submit" class="btn btn-primary">Salvar alterações</button>
  </form>
</div>

<script>
  // País → alterna estado/cidade/região
  document.getElementById('pais').addEventListener('change', function() {
    const estadoWrapper = document.getElementById('estado-wrapper');
    const cidadeWrapper = document.getElementById('cidade-wrapper');
    const regiaoWrapper = document.getElementById('regiao-wrapper');

    if (this.value === 'Brasil') {
      estadoWrapper.classList.remove('d-none');
      cidadeWrapper.classList.remove('d-none');
      regiaoWrapper.classList.add('d-none');
    } else if (this.value) {
      estadoWrapper.classList.add('d-none');
      cidadeWrapper.classList.add('d-none');
      regiaoWrapper.classList.remove('d-none');
    } else {
      estadoWrapper.classList.add('d-none');
      cidadeWrapper.classList.add('d-none');
      regiaoWrapper.classList.add('d-none');
    }
  });

  // Fundador → alterna formação/etnia
  document.getElementById('eh_fundador').addEventListener('change', function() {
    const extra = document.getElementById('fundador-extra');
    if (this.value === '1') {   // agora compara com "1"
      extra.classList.remove('d-none');
    } else {
      extra.classList.add('d-none');
      // Opcional: limpar valores quando não fundador
      const selects = extra.querySelectorAll('select');
      selects.forEach(s => s.value = '');
    }
  });
</script>

<?php
include __DIR__ . '/../app/views/empreendedor/footer.php';
?>
