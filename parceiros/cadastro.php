<?php
// Cadastro Parceiros /public_html/parceiros/cadastro.php
session_start();
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

require_once __DIR__ . '/../app/helpers/functions.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $razao_social        = trim($_POST['razao_social'] ?? '');
    $nome_fantasia       = trim($_POST['nome_fantasia'] ?? '');
    $cnpj                = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
    $rep_nome            = trim($_POST['rep_nome'] ?? '');
    $rep_cpf             = preg_replace('/[^0-9]/', '', $_POST['rep_cpf'] ?? '');
    $rep_data_nascimento = $_POST['rep_data_nascimento'] ?? '';
    $email_login         = trim($_POST['email_login'] ?? '');
    $senha               = $_POST['senha'] ?? '';
    $senha_confirmar     = $_POST['senha_confirmar'] ?? '';

    $idade = 0;
    if (!empty($rep_data_nascimento)) {
        $nascimento = new DateTime($rep_data_nascimento);
        $hoje       = new DateTime();
        $idade      = $hoje->diff($nascimento)->y;
    }

    if (empty($razao_social) || empty($nome_fantasia) || empty($cnpj) || empty($rep_nome) || empty($rep_cpf) || empty($rep_data_nascimento) || empty($email_login) || empty($senha)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif ($idade < 18) {
        $erro = "O representante legal deve ser maior de 18 anos para assinar a carta-acordo.";
    } elseif (!filter_var($email_login, FILTER_VALIDATE_EMAIL)) {
        $erro = "Formato de e-mail inválido.";
    } elseif ($senha !== $senha_confirmar) {
        $erro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 8) {
        $erro = "A senha deve ter pelo menos 8 caracteres.";
    } elseif (!isValidCPF($rep_cpf)) {
        $erro = "CPF do representante inválido.";
    } elseif (!isValidCNPJ($cnpj)) {
        $erro = "CNPJ inválido.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM parceiros WHERE email_login = ? OR cnpj = ? OR rep_cpf = ?");
            $stmt->execute([$email_login, $cnpj, $rep_cpf]);
            if ($stmt->fetch()) {
                $erro = "Já existe um Parceiro com este E-mail, CNPJ ou CPF do representante.";
            }

            if (!$erro) {
                $stmt = $pdo->prepare("SELECT id FROM empreendedores WHERE email = ? OR cpf = ?");
                $stmt->execute([$email_login, $rep_cpf]);
                if ($stmt->fetch()) {
                    $erro = "Este E-mail ou CPF já está cadastrado como Empreendedor.";
                }
            }

            if (!$erro) {
                $stmt = $pdo->prepare("SELECT id FROM sociedade_civil WHERE email = ? OR cpf = ?");
                $stmt->execute([$email_login, $rep_cpf]);
                if ($stmt->fetch()) {
                    $erro = "Este E-mail ou CPF já está cadastrado na Sociedade Civil.";
                }
            }

            if (!$erro) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                $sql = "INSERT INTO parceiros (
                    razao_social, nome_fantasia, cnpj,
                    rep_nome, rep_cpf, rep_data_nascimento, rep_email,
                    email_login, senha_hash,
                    etapa_atual, status, criado_em
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'em_cadastro', NOW())";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $razao_social, $nome_fantasia, $cnpj,
                    $rep_nome, $rep_cpf, $rep_data_nascimento, $email_login,
                    $email_login, $senha_hash
                ]);

                $novo_parceiro_id = $pdo->lastInsertId();

                $_SESSION['parceiro_id']   = $novo_parceiro_id;
                $_SESSION['parceiro_nome'] = $nome_fantasia;

                header("Location: etapa1_dados.php");
                exit;
            }
        } catch (PDOException $e) {
            $erro = "Erro no banco de dados: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container py-5 parceiro-reg-shell">
    <div class="parceiro-reg-hero text-center mb-4 mb-lg-5">
        <span class="parceiro-reg-kicker">Cadastro de Parceiros</span>
        <h1 class="parceiro-reg-page-title">Seja um Parceiro da Plataforma</h1>
        <p class="parceiro-reg-page-subtitle">
            Faça parte do nosso ecossistema e ajude a impulsionar negócios de impacto em todo o Brasil.
        </p>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <aside class="parceiro-reg-aside">
                <div class="parceiro-reg-aside-card">
                    <div class="parceiro-reg-aside-title">
                        <i class="bi bi-info-circle-fill"></i>
                        Antes de começar
                    </div>
                    <ul class="parceiro-reg-aside-list">
                        <li>Este é o primeiro passo do cadastro da instituição parceira.</li>
                        <li>O representante legal informado deve ser maior de 18 anos.</li>
                        <li>O CPF do representante e o e-mail de acesso não podem estar vinculados a outro perfil na plataforma.</li>
                        <li>Após esta etapa, você seguirá para o preenchimento dos dados complementares da parceria.</li>
                    </ul>
                </div>
                <div class="parceiro-reg-aside-card parceiro-reg-aside-highlight">
                    <div class="parceiro-reg-aside-title">
                        <i class="bi bi-pen-fill"></i>
                        Carta-acordo
                    </div>
                    <p class="mb-0">
                        O representante legal será a pessoa responsável por assinar a carta-acordo da parceria nas próximas etapas.
                    </p>
                </div>
            </aside>
        </div>

        <div class="col-lg-8">
            <div class="parceiro-reg-card">
                <div class="parceiro-reg-card-header">
                    <div>
                        <h2 class="parceiro-reg-card-title mb-1">Dados iniciais do parceiro</h2>
                        <p class="parceiro-reg-card-subtitle mb-0">
                            Preencha os dados da instituição, do representante legal e do acesso à plataforma.
                        </p>
                    </div>
                </div>

                <div class="parceiro-reg-card-body">
                    <?php if ($erro): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 parceiro-reg-alert" role="alert">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div><?= htmlspecialchars($erro) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" novalidate>
                        <!-- Campos hidden garantem envio dos campos readonly via POST -->
                        <input type="hidden" name="razao_social" id="razao_social_hidden">
                        <input type="hidden" name="rep_nome"     id="rep_nome_hidden">

                        <!-- ── Dados da Instituição ──────────────────── -->
                        <section class="parceiro-reg-section">
                            <div class="parceiro-reg-section-head">
                                <h3 class="parceiro-reg-section-title">Dados da Instituição</h3>
                                <p class="parceiro-reg-section-text">
                                    Informe os dados principais da organização parceira.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label parceiro-reg-label">CNPJ *</label>
                                    <div class="position-relative">
                                        <input type="text" name="cnpj" id="cnpj"
                                               class="form-control"
                                               placeholder="00.000.000/0000-00"
                                               maxlength="18" required
                                               value="<?= htmlspecialchars($_POST['cnpj'] ?? '') ?>">
                                        <span id="cnpjSpinner"
                                              class="position-absolute top-50 end-0 translate-middle-y me-2 d-none"
                                              style="color:#6c757d;">
                                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        </span>
                                        <span id="cnpjBadge"
                                              class="position-absolute top-50 end-0 translate-middle-y me-2 d-none"
                                              style="font-size:.75rem;"></span>
                                    </div>
                                    <div id="cnpjHelp" class="form-text parceiro-reg-help"></div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-reg-label">Razão Social *</label>
                                    <div class="position-relative">
                                        <input type="text" id="razao_social"
                                               class="form-control bg-light"
                                               placeholder="Preenchido automaticamente via CNPJ"
                                               readonly required>
                                    </div>
                                    <div id="razaoHelp" class="form-text parceiro-reg-help">
                                        <?php if (!empty($_POST['razao_social'])): ?>
                                            <span class="text-success"><i class="bi bi-lock-fill me-1"></i>Preenchido via Receita Federal.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label parceiro-reg-label">Nome Fantasia *</label>
                                    <input type="text" name="nome_fantasia" id="nome_fantasia"
                                           class="form-control" required
                                           value="<?= htmlspecialchars($_POST['nome_fantasia'] ?? '') ?>">
                                    <div id="nomeFantasiaHelp" class="form-text parceiro-reg-help"></div>
                                </div>
                            </div>
                        </section>

                        <!-- ── Representante Legal ───────────────────── -->
                        <section class="parceiro-reg-section">
                            <div class="parceiro-reg-section-head">
                                <h3 class="parceiro-reg-section-title">Representante Legal</h3>
                                <p class="parceiro-reg-section-text">
                                    Essa pessoa será responsável por representar a instituição e assinar a carta-acordo.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label parceiro-reg-label">CPF do Representante *</label>
                                    <div class="position-relative">
                                        <input type="text" name="rep_cpf" id="rep_cpf"
                                               class="form-control"
                                               placeholder="000.000.000-00"
                                               maxlength="14" required
                                               value="<?= htmlspecialchars($_POST['rep_cpf'] ?? '') ?>">
                                        <span id="cpfSpinner"
                                              class="position-absolute top-50 end-0 translate-middle-y me-2 d-none"
                                              style="color:#6c757d;">
                                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        </span>
                                        <span id="cpfBadge"
                                              class="position-absolute top-50 end-0 translate-middle-y me-2 d-none"
                                              style="font-size:.75rem;"></span>
                                    </div>
                                    <div class="form-text parceiro-reg-help">Um CPF só pode estar vinculado a um perfil na plataforma.</div>
                                    <div id="cpfHelp" class="form-text parceiro-reg-help"></div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label parceiro-reg-label">Data de Nascimento *</label>
                                    <input type="date" name="rep_data_nascimento" class="form-control"
                                           max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
                                           required
                                           value="<?= htmlspecialchars($_POST['rep_data_nascimento'] ?? '') ?>">
                                    <div class="form-text parceiro-reg-help">O representante deve ser maior de 18 anos.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label parceiro-reg-label">Nome Completo *</label>
                                    <input type="text" id="rep_nome"
                                           class="form-control bg-light"
                                           placeholder="Preenchido automaticamente via CPF"
                                           readonly required>
                                    <div id="repNomeHelp" class="form-text parceiro-reg-help">
                                        <?php if (!empty($_POST['rep_nome'])): ?>
                                            <span class="text-success"><i class="bi bi-lock-fill me-1"></i>Preenchido via Receita Federal.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- ── Acesso à Plataforma ───────────────────── -->
                        <section class="parceiro-reg-section">
                            <div class="parceiro-reg-section-head">
                                <h3 class="parceiro-reg-section-title">Acesso à Plataforma</h3>
                                <p class="parceiro-reg-section-text">
                                    Defina o e-mail e a senha que serão usados para acessar o painel do parceiro.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label parceiro-reg-label">E-mail de Acesso *</label>
                                    <input type="email" name="email_login" class="form-control"
                                           placeholder="contato@empresa.com.br" required
                                           value="<?= htmlspecialchars($_POST['email_login'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label parceiro-reg-label">Senha *</label>
                                    <input type="password" name="senha" class="form-control" required>
                                    <div class="form-text parceiro-reg-help">Mínimo de 8 caracteres.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label parceiro-reg-label">Confirmar Senha *</label>
                                    <input type="password" name="senha_confirmar" class="form-control" required>
                                </div>
                            </div>
                        </section>

                        <div class="parceiro-reg-actions">
                            <button type="submit" class="btn-reg-submit">
                                Começar Cadastro de Parceiro
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>

                        <div class="parceiro-reg-login text-center">
                            <span class="text-muted">Já é parceiro?</span>
                            <a href="login.php" class="fw-semibold">Faça login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {

  var PROXY = '/app/api/cpfcnpj_proxy.php';

  // ── Referências ──────────────────────────────────────────────────────────
  var cnpjInput     = document.getElementById('cnpj');
  var cnpjSpinner   = document.getElementById('cnpjSpinner');
  var cnpjBadge     = document.getElementById('cnpjBadge');
  var cnpjHelp      = document.getElementById('cnpjHelp');
  var razaoDisplay  = document.getElementById('razao_social');
  var razaoHidden   = document.getElementById('razao_social_hidden');
  var razaoHelp     = document.getElementById('razaoHelp');

  var cpfInput       = document.getElementById('rep_cpf');
  var cpfSpinner     = document.getElementById('cpfSpinner');
  var cpfBadge       = document.getElementById('cpfBadge');
  var cpfHelp        = document.getElementById('cpfHelp');
  var repNomeDisplay = document.getElementById('rep_nome');
  var repNomeHidden  = document.getElementById('rep_nome_hidden');
  var repNomeHelp    = document.getElementById('repNomeHelp');

  var cnpjDebounce = null;
  var cpfDebounce  = null;

  // ── Utilitários ──────────────────────────────────────────────────────────
  function onlyDigits(s) { return (s || '').replace(/\D/g, ''); }

  function formatCNPJ(d) {
    d = onlyDigits(d).slice(0, 14);
    return d.replace(/^(\d{2})(\d)/,          '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/,         '.$1/$2')
            .replace(/(\d{4})(\d)/,           '$1-$2')
            .slice(0, 18);
  }

  function formatCPF(d) {
    d = onlyDigits(d).slice(0, 11);
    return d.replace(/^(\d{3})(\d)/,          '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/,         '.$1-$2')
            .slice(0, 14);
  }

  function isValidCNPJ(c) {
    if (c.length !== 14 || /^(\d)\1{13}$/.test(c)) return false;
    var t = c.slice(0,12), s = 0, p = 5;
    for (var i = 0; i < 12; i++) { s += parseInt(t[i]) * p; p = p === 2 ? 9 : p - 1; }
    var r = s % 11;
    if (parseInt(c[12]) !== (r < 2 ? 0 : 11 - r)) return false;
    t = c.slice(0,13); s = 0; p = 6;
    for (var j = 0; j < 13; j++) { s += parseInt(t[j]) * p; p = p === 2 ? 9 : p - 1; }
    r = s % 11;
    return parseInt(c[13]) === (r < 2 ? 0 : 11 - r);
  }

  function isValidCPF(cpf) {
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
    var s = 0;
    for (var i = 0; i < 9; i++) s += parseInt(cpf[i]) * (10 - i);
    var r = s % 11, d1 = r < 2 ? 0 : 11 - r;
    if (d1 !== parseInt(cpf[9])) return false;
    s = 0;
    for (var j = 0; j < 10; j++) s += parseInt(cpf[j]) * (11 - j);
    r = s % 11;
    return (r < 2 ? 0 : 11 - r) === parseInt(cpf[10]);
  }

  // ── Estado CNPJ ──────────────────────────────────────────────────────────
  function cnpjSetLoading(on) {
    if (on) { cnpjSpinner.classList.remove('d-none'); cnpjBadge.classList.add('d-none'); }
    else    { cnpjSpinner.classList.add('d-none'); }
  }
  function cnpjSetSuccess(razao) {
    razaoDisplay.value = razao;
    razaoHidden.value  = razao;
    razaoDisplay.setAttribute('readonly', 'readonly');
    razaoDisplay.classList.add('bg-light');
    razaoHelp.innerHTML = '<span class="text-success"><i class="bi bi-lock-fill me-1"></i>Preenchido via Receita Federal. Não pode ser alterado.</span>';
    cnpjBadge.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
    cnpjBadge.classList.remove('d-none');
    cnpjHelp.innerHTML  = '<span class="text-success">CNPJ válido ✓</span>';
  }
  function cnpjSetError(msg) {
    razaoDisplay.value = '';
    razaoHidden.value  = '';
    razaoDisplay.removeAttribute('readonly');
    razaoDisplay.classList.remove('bg-light');
    razaoDisplay.setAttribute('placeholder', 'Digite a Razão Social');
    razaoHelp.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>' + msg + '</span>';
    cnpjBadge.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i>';
    cnpjBadge.classList.remove('d-none');
    cnpjHelp.innerHTML  = '';
  }
  function cnpjClear() {
    razaoDisplay.value = '';
    razaoHidden.value  = '';
    razaoDisplay.removeAttribute('readonly');
    razaoDisplay.classList.remove('bg-light');
    razaoDisplay.setAttribute('placeholder', 'Preenchido automaticamente via CNPJ');
    razaoHelp.innerHTML = '';
    cnpjBadge.classList.add('d-none');
    cnpjHelp.innerHTML  = '';
    cnpjSetLoading(false);
  }

  // ── Estado CPF ───────────────────────────────────────────────────────────
  function cpfSetLoading(on) {
    if (on) { cpfSpinner.classList.remove('d-none'); cpfBadge.classList.add('d-none'); }
    else    { cpfSpinner.classList.add('d-none'); }
  }
  function cpfSetSuccess(nome) {
    repNomeDisplay.value = nome;
    repNomeHidden.value  = nome;
    repNomeDisplay.setAttribute('readonly', 'readonly');
    repNomeDisplay.classList.add('bg-light');
    repNomeHelp.innerHTML = '<span class="text-success"><i class="bi bi-lock-fill me-1"></i>Preenchido via Receita Federal. Não pode ser alterado.</span>';
    cpfBadge.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
    cpfBadge.classList.remove('d-none');
    cpfHelp.innerHTML  = '<span class="text-success">CPF válido ✓</span>';
  }
  function cpfSetError(msg) {
    repNomeDisplay.value = '';
    repNomeHidden.value  = '';
    repNomeDisplay.removeAttribute('readonly');
    repNomeDisplay.classList.remove('bg-light');
    repNomeDisplay.setAttribute('placeholder', 'Digite o nome completo');
    repNomeHelp.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>' + msg + ' Preencha manualmente.</span>';
    cpfBadge.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i>';
    cpfBadge.classList.remove('d-none');
    cpfHelp.innerHTML  = '';
  }
  function cpfClear() {
    repNomeDisplay.value = '';
    repNomeHidden.value  = '';
    repNomeDisplay.removeAttribute('readonly');
    repNomeDisplay.classList.remove('bg-light');
    repNomeDisplay.setAttribute('placeholder', 'Preenchido automaticamente via CPF');
    repNomeHelp.innerHTML = '';
    cpfBadge.classList.add('d-none');
    cpfHelp.innerHTML  = '';
    cpfSetLoading(false);
  }

  // ── Sinc display → hidden ao editar manualmente ──────────────────────────
  razaoDisplay.addEventListener('input', function () { razaoHidden.value = razaoDisplay.value; });
  repNomeDisplay.addEventListener('input', function () { repNomeHidden.value = repNomeDisplay.value; });

  // ── Consulta CNPJ via proxy ───────────────────────────────────────────────
  function consultarCNPJ(digits) {
    cnpjSetLoading(true);
    cnpjHelp.innerHTML = '<span class="text-muted">Consultando Receita Federal…</span>';

    fetch(PROXY + '?tipo=cnpj&doc=' + digits)
      .then(function (res) { return res.json().then(function (d) { return { status: res.status, data: d }; }); })
      .then(function (result) {
        cnpjSetLoading(false);
        if (result.status !== 200) {
          cnpjSetError(result.data.erro || 'Erro ao consultar CNPJ.');
          return;
        }
        var razao = (result.data.razao_social || result.data.razao || result.data.nome || '').trim();
        if (razao) {
          cnpjSetSuccess(razao);
        } else {
          razaoDisplay.removeAttribute('readonly');
          razaoDisplay.classList.remove('bg-light');
          razaoDisplay.setAttribute('placeholder', 'Digite a Razão Social');
          razaoHelp.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Não foi possível obter a Razão Social automaticamente. Preencha manualmente.</span>';
          cnpjBadge.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
          cnpjBadge.classList.remove('d-none');
          cnpjHelp.innerHTML  = '<span class="text-success">CNPJ válido ✓</span>';
        }
      })
      .catch(function () {
        cnpjSetLoading(false);
        cnpjSetError('Falha na conexão. Verifique sua internet.');
      });
  }

  // ── Consulta CPF via proxy ────────────────────────────────────────────────
  function consultarCPF(digits) {
    cpfSetLoading(true);
    cpfHelp.innerHTML = '<span class="text-muted">Consultando Receita Federal…</span>';

    fetch(PROXY + '?tipo=cpf&doc=' + digits)
      .then(function (res) { return res.json().then(function (d) { return { status: res.status, data: d }; }); })
      .then(function (result) {
        cpfSetLoading(false);
        if (result.status !== 200) {
          cpfSetError(result.data.erro || 'Erro ao consultar CPF.');
          return;
        }
        var nome = (result.data.nome || '').trim();
        if (nome) cpfSetSuccess(nome);
        else      cpfSetError('Nome não retornado pela Receita Federal.');
      })
      .catch(function () {
        cpfSetLoading(false);
        cpfSetError('Falha na conexão. Verifique sua internet.');
      });
  }

  // ── Listeners CNPJ ───────────────────────────────────────────────────────
  cnpjInput.addEventListener('input', function () {
    cnpjInput.value = formatCNPJ(cnpjInput.value);
    var d = onlyDigits(cnpjInput.value);
    clearTimeout(cnpjDebounce);
    cnpjBadge.classList.add('d-none');
    cnpjHelp.innerHTML = '';

    if (d.length === 14) {
      if (!isValidCNPJ(d)) {
        cnpjBadge.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i>';
        cnpjBadge.classList.remove('d-none');
        cnpjHelp.innerHTML  = '<span class="text-danger">CNPJ inválido. Verifique os números.</span>';
        cnpjClear();
      } else {
        cnpjClear();
        cnpjHelp.innerHTML = '<span class="text-muted">Aguardando…</span>';
        cnpjDebounce = setTimeout(function () { consultarCNPJ(d); }, 600);
      }
    } else {
      cnpjClear();
    }
  });

  // paste: extrai dígitos, formata, dispara evento input normalmente
  cnpjInput.addEventListener('paste', function (e) {
    e.preventDefault();
    var texto = (e.clipboardData || window.clipboardData).getData('text');
    cnpjInput.value = formatCNPJ(texto);
    cnpjInput.dispatchEvent(new Event('input', { bubbles: true }));
  });

  // ── Listeners CPF ────────────────────────────────────────────────────────
  cpfInput.addEventListener('input', function () {
    cpfInput.value = formatCPF(cpfInput.value);
    var d = onlyDigits(cpfInput.value);
    clearTimeout(cpfDebounce);
    cpfBadge.classList.add('d-none');
    cpfHelp.innerHTML = '';

    if (d.length === 11) {
      if (!isValidCPF(d)) {
        cpfBadge.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i>';
        cpfBadge.classList.remove('d-none');
        cpfHelp.innerHTML  = '<span class="text-danger">CPF inválido. Verifique os números.</span>';
        cpfClear();
      } else {
        cpfClear();
        cpfHelp.innerHTML = '<span class="text-muted">Aguardando…</span>';
        cpfDebounce = setTimeout(function () { consultarCPF(d); }, 600);
      }
    } else {
      cpfClear();
    }
  });

  // paste: extrai dígitos, formata, dispara evento input normalmente
  cpfInput.addEventListener('paste', function (e) {
    e.preventDefault();
    var texto = (e.clipboardData || window.clipboardData).getData('text');
    cpfInput.value = formatCPF(texto);
    cpfInput.dispatchEvent(new Event('input', { bubbles: true }));
  });

})();
</script>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>