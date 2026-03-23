<?php
// Exibir erros em desenvolvimento
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// CSRF simples
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

header('Content-Type: text/html; charset=utf-8');

$config = require_once __DIR__ . '/../app/config/db.php';

$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
    
    // FORÇA UTF-8MB4 em todas as variáveis de conexão
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET character_set_client = utf8mb4");
    $pdo->exec("SET character_set_connection = utf8mb4");
    $pdo->exec("SET character_set_results = utf8mb4");
    $pdo->exec("SET collation_connection = utf8mb4_unicode_ci");
    
} catch (PDOException $e) {
    die("Erro na conexão com o banco: " . $e->getMessage());
}

// Se formulário enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        die('Token CSRF inválido.');
    }

    // Captura dados do formulário
$data = [
    'nome' => trim($_POST['nome'] ?? ''),
    'sobrenome' => trim($_POST['sobrenome'] ?? ''),
    'cpf' => preg_replace('/\D/', '', $_POST['cpf'] ?? ''),
    'email' => trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL)), // <-- atualizado aqui
    'celular' => trim($_POST['celular'] ?? ''),
    'data_nascimento' => $_POST['data_nascimento'] ?? null,
    'genero' => $_POST['genero'] ?? null,
    'pais' => $_POST['pais'] ?? null,
    'estado' => trim($_POST['estado'] ?? null),
    'cidade' => trim($_POST['cidade'] ?? ''),
    'regiao' => trim($_POST['regiao'] ?? ''),
    'cargo' => trim($_POST['cargo'] ?? ''),
    'origem_conhecimento' => $_POST['origem_conhecimento'] ?? '',
    'consentimento_email' => isset($_POST['consentimento_email']) ? 1 : 0,
    'consentimento_whatsapp' => isset($_POST['consentimento_whatsapp']) ? 1 : 0,
    'termos_uso' => isset($_POST['termos_uso']) ? 1 : 0,
    'senha' => $_POST['senha'] ?? '',
    'senha_confirm' => $_POST['senha_confirm'] ?? '',
    'eh_fundador' => (($_POST['eh_fundador'] ?? 'Não') === 'Sim') ? 1 : 0,
    'formacao' => $_POST['formacao'] ?? null,
    'etnia' => $_POST['etnia'] ?? null,
];

// Validações básicas server-side
$erros = [];
if ($data['nome'] === '') $erros[] = 'Informe seu nome.';
if ($data['sobrenome'] === '') $erros[] = 'Informe seu sobrenome.';
if (!preg_match('/^\d{11}$/', $data['cpf'])) $erros[] = 'CPF inválido.';
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.'; // <-- continua aqui
if (strlen($data['senha']) < 8) $erros[] = 'A senha deve ter ao menos 8 caracteres.';
if ($data['senha'] !== $data['senha_confirm']) $erros[] = 'As senhas não coincidem.';
if (!$data['termos_uso']) $erros[] = 'É necessário concordar com os termos de uso.';

    // Se é fundador, formação e etnia tornam-se obrigatórios
    if ($data['eh_fundador'] === 'Sim') {
        if (empty($data['formacao'])) $erros[] = 'Informe sua formação.';
        if (empty($data['etnia'])) $erros[] = 'Informe sua etnia/raça.';
    } else {
        // Se não é fundador, não exigimos formação/etnia aqui
        $data['formacao'] = null;
        $data['etnia'] = null;
    }

    // Verifica se já existe CPF ou email
    $stmt = $pdo->prepare("SELECT id FROM empreendedores WHERE cpf = ? OR email = ? LIMIT 1");
    $stmt->execute([$data['cpf'], $data['email']]);
    if ($stmt->fetch()) {
        $erros[] = "Já existe um cadastro com este CPF ou e-mail.";
    }

    if (!empty($erros)) {
        $erro = implode('<br>', $erros);
    } else {
        // Monta INSERT com os campos disponíveis na sua tabela
        $sql = "INSERT INTO empreendedores 
            (nome, sobrenome, cpf, email, celular, data_nascimento, genero, cidade, estado, pais, regiao, cargo, origem_conhecimento, consentimento_email, consentimento_whatsapp, termos_uso, senha_hash, formacao, etnia, criado_em) 
            VALUES 
            (:nome, :sobrenome, :cpf, :email, :celular, :data_nascimento, :genero, :cidade, :estado, :pais, :regiao, :cargo, :origem_conhecimento, :consentimento_email, :consentimento_whatsapp, :termos_uso, :senha_hash, :formacao, :etnia, CURRENT_TIMESTAMP)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $data['nome'],
            ':sobrenome' => $data['sobrenome'],
            ':cpf' => $data['cpf'],
            ':email' => $data['email'],
            ':celular' => $data['celular'],
            ':data_nascimento' => $data['data_nascimento'] ?: null,
            ':genero' => $data['genero'] ?: null,
            ':cidade' => $data['cidade'] ?: null,
            ':estado' => $data['estado'] ?: null,
            ':pais' => $data['pais'] ?: null,
            ':regiao' => $data['regiao'] ?: null,
            ':cargo' => $data['cargo'] ?: null,
            ':origem_conhecimento' => $data['origem_conhecimento'] ?: null,
            ':consentimento_email' => $data['consentimento_email'],
            ':consentimento_whatsapp' => $data['consentimento_whatsapp'],
            ':termos_uso' => $data['termos_uso'],
            ':senha_hash' => password_hash($data['senha'], PASSWORD_DEFAULT),
            ':formacao' => $data['formacao'],
            ':etnia' => $data['etnia'],
        ]);

        // Salva na sessão
        $_SESSION['empreendedor_id']    = (int)$pdo->lastInsertId();
        $_SESSION['empreendedor_nome']  = $data['nome'];
        $_SESSION['empreendedor_email'] = $data['email'];
        $_SESSION['eh_fundador']        = $data['eh_fundador'];
        $_SESSION['formacao']           = $data['formacao'];
        $_SESSION['etnia']              = $data['etnia'];

        header("Location: /empreendedores/dashboard.php");
        exit;
    }
}

// inclui header público
include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h2 class="mb-0">Cadastro Responsável pelo Negócio</h2>
          <h4>Sobre você</h4>
          <p>Queremos conhecer quem está por trás desta inscrição.</p>
          <p>Nesta etapa, reuniremos informações da pessoa responsável por inscrever o negócio ou projeto no Prêmio Impactos Positivos. Esses dados são fundamentais para que possamos manter contato durante o processo de inscrição, envio de atualizações e eventuais oportunidades futuras relacionadas à sua iniciativa.</p>
          <p>Se você também for o(a) fundador(a) ou principal responsável pelo negócio, suas respostas ajudarão a entender o perfil da liderança por trás da solução — algo essencial para parceiros, jurados e investidores que valorizam empreendedores com propósito, visão e compromisso com o impacto.</p>
          <p><strong>Preencha com atenção e, se preferir, marque as opções de consentimento para receber novidades da Plataforma Impactos Positivos por e-mail ou WhatsApp.</strong></p>
        </div>
        <div class="card-body">
          <form method="post" action="/empreendedores/store.php"  id="cadastroForm"  novalidate>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Nome *</label>
                <input type="text" name="nome" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Sobrenome *</label>
                <input type="text" name="sobrenome" class="form-control" required>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-6">
              <label class="form-label">CPF *</label>
              <input type="text" name="cpf" class="form-control" placeholder="000.000.000-00" required>
              </div>
              <div class="col-md-6">
              <label class="form-label">E-mail *</label>
              <input type="email" name="email" class="form-control" required>
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Celular *</label>
                <input type="text" name="celular" class="form-control" placeholder="(11) 90000-0000" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Data de nascimento *</label>
                <input type="date" name="data_nascimento" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Gênero *</label>
                <select name="genero" class="form-select" required>
                  <option value="">Selecione</option>
                  <option>Masculino</option>
                  <option>Feminino</option>
                  <option>Não Binário</option>
                  <option>Outros</option>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">País *</label>
              <select id="pais" name="pais" class="form-select" required>
                <option value="" selected>Selecione</option>

                <optgroup label="América do Norte">
                  <option value="Estados Unidos">Estados Unidos</option>
                  <option value="Canadá">Canadá</option>
                  <option value="México">México</option>
                </optgroup>

                <optgroup label="América Central">
                  <option value="Costa Rica">Costa Rica</option>
                  <option value="Panamá">Panamá</option>
                  <option value="Guatemala">Guatemala</option>
                  <option value="Honduras">Honduras</option>
                  <option value="El Salvador">El Salvador</option>
                  <option value="Nicarágua">Nicarágua</option>
                  <option value="Belize">Belize</option>
                </optgroup>

                <optgroup label="América do Sul">
                  <option value="Brasil" selected>Brasil</option>
                  <option value="Argentina">Argentina</option>
                  <option value="Chile">Chile</option>
                  <option value="Uruguai">Uruguai</option>
                  <option value="Paraguai">Paraguai</option>
                  <option value="Bolívia">Bolívia</option>
                  <option value="Peru">Peru</option>
                  <option value="Colômbia">Colômbia</option>
                  <option value="Equador">Equador</option>
                  <option value="Venezuela">Venezuela</option>
                </optgroup>

                <optgroup label="Europa">
                  <option value="Portugal">Portugal</option>
                  <option value="Espanha">Espanha</option>
                  <option value="França">França</option>
                  <option value="Alemanha">Alemanha</option>
                  <option value="Itália">Itália</option>
                  <option value="Reino Unido">Reino Unido</option>
                  <option value="Irlanda">Irlanda</option>
                  <option value="Países Baixos">Países Baixos</option>
                  <option value="Bélgica">Bélgica</option>
                  <option value="Suíça">Suíça</option>
                  <option value="Suécia">Suécia</option>
                  <option value="Noruega">Noruega</option>
                  <option value="Dinamarca">Dinamarca</option>
                  <option value="Polônia">Polônia</option>
                  <option value="Grécia">Grécia</option>
                </optgroup>

                <optgroup label="Ásia">
                  <option value="China">China</option>
                  <option value="Japão">Japão</option>
                  <option value="Índia">Índia</option>
                  <option value="Coreia do Sul">Coreia do Sul</option>
                  <option value="Singapura">Singapura</option>
                  <option value="Israel">Israel</option>
                  <option value="Turquia">Turquia</option>
                </optgroup>

                <optgroup label="África">
                  <option value="África do Sul">África do Sul</option>
                  <option value="Nigéria">Nigéria</option>
                  <option value="Egito">Egito</option>
                  <option value="Quênia">Quênia</option>
                  <option value="Marrocos">Marrocos</option>
                </optgroup>

                <optgroup label="Oceania">
                  <option value="Austrália">Austrália</option>
                  <option value="Nova Zelândia">Nova Zelândia</option>
                </optgroup>

                <optgroup label="Outro">
                  <option value="Outro">Outro</option>
                </optgroup>
              </select>
            </div>

            <div class="mb-3" id="estado-wrapper">
                <label class="form-label">Estado *</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Selecione o estado</option>
                    <option value="Acre">Acre</option>
                    <option value="Alagoas">Alagoas</option>
                    <option value="Amapá">Amapá</option>
                    <option value="Amazonas">Amazonas</option>
                    <option value="Bahia">Bahia</option>
                    <option value="Ceará">Ceará</option>
                    <option value="Distrito Federal">Distrito Federal</option>
                    <option value="Espírito Santo">Espírito Santo</option>
                    <option value="Goiás">Goiás</option>
                    <option value="Maranhão">Maranhão</option>
                    <option value="Mato Grosso">Mato Grosso</option>
                    <option value="Mato Grosso do Sul">Mato Grosso do Sul</option>
                    <option value="Minas Gerais">Minas Gerais</option>
                    <option value="Pará">Pará</option>
                    <option value="Paraíba">Paraíba</option>
                    <option value="Paraná">Paraná</option>
                    <option value="Pernambuco">Pernambuco</option>
                    <option value="Piauí">Piauí</option>
                    <option value="Rio de Janeiro">Rio de Janeiro</option>
                    <option value="Rio Grande do Norte">Rio Grande do Norte</option>
                    <option value="Rio Grande do Sul">Rio Grande do Sul</option>
                    <option value="Rondônia">Rondônia</option>
                    <option value="Roraima">Roraima</option>
                    <option value="Santa Catarina">Santa Catarina</option>
                    <option value="São Paulo">São Paulo</option>
                    <option value="Sergipe">Sergipe</option>
                    <option value="Tocantins">Tocantins</option>
                </select>
            </div>

            <div class="mb-3" id="cidade-wrapper">
                <label class="form-label">Cidade *</label>
                <input type="text" id="cidade" name="cidade" class="form-control" placeholder="Digite sua cidade">
            </div>

            <div class="mb-3 d-none" id="regiao-wrapper">
                <label class="form-label">Região/Província *</label>
                <input type="text" name="regiao" class="form-control" placeholder="Digite sua região/província">
            </div>

            <div class="mb-3">
              <label class="form-label">Cargo na organização *</label>
              <input type="text" name="cargo" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Como ficou sabendo do Prêmio? *</label>
              <select name="origem_conhecimento" class="form-select" required>
                <option value="">Selecione</option>
                <option>Redes sociais</option>
                <option>Mídia</option>
                <option>Newsletter</option>
                <option>Evento</option>
                <option>Indicação</option>
                <option>Sebrae/ENImpacto</option>
                <option>Site Impactos Positivos</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Você é o fundador? *</label>              
              <small class="text-muted">Se houver mais de uma pessoa fundadora, você poderá adicionar até 5 cofundadores/as no cadastro de negócios de impacto.</small>
              <select name="eh_fundador" id="eh_fundador" class="form-select" required>
                <option value="">Selecione</option>
                <option value="Sim">Sim</option>
                <option value="Não">Não</option>
              </select>
            </div>

            <div id="fundador-extra" class="border rounded p-3 mb-3 d-none">
              <div class="mb-3">
                <label class="form-label">Formação *</label>
                <select name="formacao" id="formacao" class="form-select">
                  <option value="">Selecione</option>
                  <option>Ensino Fundamental Incompleto</option>
                  <option>Ensino Fundamental Completo</option>
                  <option>Ensino Médio Incompleto</option>
                  <option>Ensino Médio Completo</option>
                  <option>Ensino Técnico</option>
                  <option>Ensino Superior Incompleto</option>
                  <option>Ensino Superior Completo</option>
                  <option>Pós-graduação Lato Sensu (Especialização/MBA)</option>
                  <option>Mestrado</option>
                  <option>Doutorado</option>
                  <option>Pós-doutorado</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label">Etnia/Raça *</label>
                <select name="etnia" id="etnia" class="form-select">
                  <option value="">Selecione</option>
                  <option>Branco(a)</option>
                  <option>Preto(a)</option>
                  <option>Pardo(a)</option>
                  <option>Amarelo(a)</option>
                  <option>Indígena</option>
                  <option>Prefiro não responder</option>
                </select>
              </div>
            </div>


            <div class="mb-3 form-check">
              <input type="checkbox" name="consentimento_email" value="1" class="form-check-input" id="consentEmail">
              <label class="form-check-label" for="consentEmail">Aceito receber atualizações por e-mail</label>
            </div>

            <div class="mb-3 form-check">
              <input type="checkbox" name="consentimento_whatsapp" value="1" class="form-check-input" id="consentWhats">
              <label class="form-check-label" for="consentWhats">Aceito receber novidades por WhatsApp</label>
            </div>


            <div class="mb-3 form-check">
              <input type="checkbox" name="termos_uso" value="1" class="form-check-input" id="termosUso" required>
              <label class="form-check-label" for="termosUso">
                Concordo com os <a href="/termos-uso.php" target="_blank">Termos de Uso</a> e a 
                <a href="/politica-privacidade.php" target="_blank">Política de Privacidade</a>
              </label>
            </div>

            <div class="mb-3">
              <label class="form-label">Senha *</label>
              <input type="password" name="senha" class="form-control" required minlength="8">
            </div>

            <div class="mb-3">
              <label class="form-label">Confirmar senha *</label>
              <input type="password" name="senha_confirm" class="form-control" required minlength="8">
            </div>

            <button type="submit" class="btn btn-success">Criar cadastro</button>
          </form>
          
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const form = document.getElementById('cadastroForm');
const ehFundador = document.getElementById('eh_fundador');
const fundadorExtra = document.getElementById('fundador-extra');
const formacao = document.getElementById('formacao');
const etnia = document.getElementById('etnia');

// Exibe ou oculta os campos extras se for fundador
ehFundador.addEventListener('change', function () {
  if (this.value === 'Sim') {
    fundadorExtra.classList.remove('d-none');
  } else {
    fundadorExtra.classList.add('d-none');
    formacao.value = '';
    etnia.value = '';
  }
});

form.addEventListener('submit', function (e) {
  let erros = [];

  const nome = this.nome.value.trim();
  const sobrenome = this.sobrenome.value.trim();
  const cpf = this.cpf.value.replace(/\D/g, '');
  const email = this.email.value.trim();
  const senha = this.senha.value;
  const senhaConfirm = this.senha_confirm.value;
  const ehFund = ehFundador.value;

  // Nome e sobrenome
  if (!nome) erros.push("Informe seu nome.");
  if (!sobrenome) erros.push("Informe seu sobrenome.");

  // CPF
  if (!validarCPF(cpf)) erros.push("CPF inválido.");

  // Email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) erros.push("E-mail inválido.");

  // Senha
  if (senha.length < 8) erros.push("A senha deve ter ao menos 8 caracteres.");
  if (senha !== senhaConfirm) erros.push("As senhas não coincidem.");

  // Fundador → formação e etnia obrigatórios
  if (ehFund === 'Sim') {
    if (!formacao.value) erros.push("Informe sua formação.");
    if (!etnia.value) erros.push("Informe sua etnia/raça.");
  }

  if (erros.length > 0) {
    e.preventDefault();
    alert("Corrija os erros:\n" + erros.join("\n"));
  }
});

// Função de validação de CPF
function validarCPF(cpf) {
  if (!cpf || cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

  let soma = 0, resto;
  for (let i = 1; i <= 9; i++) soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
  resto = (soma * 10) % 11;
  if (resto === 10 || resto === 11) resto = 0;
  if (resto !== parseInt(cpf.substring(9, 10))) return false;

  soma = 0;
  for (let i = 1; i <= 10; i++) soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
  resto = (soma * 10) % 11;
  if (resto === 10 || resto === 11) resto = 0;
  if (resto !== parseInt(cpf.substring(10, 11))) return false;

  return true;
}
</script>

<?php
// inclui footer público
include __DIR__ . '/../app/views/public/footer_public.php';
?>
