<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
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

// Aceita ID via GET (de meus-negocios) OU sessão
$negocio_id = (int)($_GET['id'] ?? $_SESSION['negocio_id'] ?? 0);
if ($negocio_id === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}

// Define na sessão para usar no formulário
$_SESSION['negocio_id'] = $negocio_id;

// Busca dados do negócio e empreendedor
$stmt = $pdo->prepare("
    SELECT n.*, e.eh_fundador 
    FROM negocios n 
    JOIN empreendedores e ON n.empreendedor_id = e.id 
    WHERE n.id = ? AND n.empreendedor_id = ?
");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$negocio) {
    die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);
}

// Lógica: se empreendedor é fundador, não precisa cadastrar principal
$empreendedorEhFundador = (int)$negocio['eh_fundador'];
$permiteFundadorPrincipal = $empreendedorEhFundador === 0;

// Busca fundadores já cadastrados (CORRIGIDO: usa $negocio_id)
$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);  // ✅ $negocio_id
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>


<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h1 class="mb-4">Etapa 2: Fundadores</h1>

             <?php
                $etapaAtual = 2;
                include __DIR__ . '/../app/views/partials/progress.php';
                include __DIR__ . '/../app/views/partials/intro_text_fundadores.php';
            ?>
            
            <?php if (isset($_SESSION['errors_etapa2'])): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($_SESSION['errors_etapa2'] as $erro): ?>
                            <li><?= htmlspecialchars($erro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errors_etapa2']); ?>
            <?php endif; ?>

            <?php if ($empreendedorEhFundador): ?>
                <div class="alert alert-info mb-4">
                    <strong>Você já foi cadastrado como fundador principal.</strong> 
                    Preencha apenas cofundadores se necessário, ou clique em "Avançar sem cofundadores".
                </div>
            <?php endif; ?>

            <form action="/negocios/processar_etapa2.php" method="post">
                <input type="hidden" name="negocio_id" value="<?= (int)$_SESSION['negocio_id'] ?>">
                <input type="hidden" name="modo" value="cadastro">

                <?php if ($permiteFundadorPrincipal): ?>
                    <div class="card mb-4">
                    <div class="card-header"><h3>Fundador Principal</h3></div>
                    <div class="card-body">
                        <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Nome *</label>
                            <input type="text" name="fundador_principal[nome]" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Sobrenome *</label>
                            <input type="text" name="fundador_principal[sobrenome]" class="form-control" required>
                        </div>
                        </div>

                        <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> CPF *</label>
                            <input type="text" name="fundador_principal[cpf]" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> E-mail *</label>
                            <input type="email" name="fundador_principal[email]" class="form-control" required>
                            <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="fundador_principal[email_optin]" value="1">
                            <label class="form-check-label">Aceito receber atualizações via e-mail</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Celular *</label>
                            <input type="text" name="fundador_principal[celular]" class="form-control" required>
                            <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="fundador_principal[whatsapp_optin]" value="1">
                            <label class="form-check-label">Aceito receber novidades via WhatsApp</label>
                            </div>
                        </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Data de Nascimento *</label>
                                <input type="date" name="fundador_principal[data_nascimento]" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Gênero *</label>
                                <select name="fundador_principal[genero]" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Feminino">Feminino</option>
                                    <option value="Não Binário">Não Binário</option>
                                    <option value="Outros">Outros</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Formação *</label>
                                <select name="fundador_principal[formacao]" class="form-select" required>
                                <option value="">Selecione...</option>
                                <option value="Ensino Fundamental">Ensino Fundamental</option>
                                <option value="Ensino Médio Completo">Ensino Médio Completo</option>
                                <option value="Ensino Médio Incompleto">Ensino Médio Incompleto</option>
                                <option value="Ensino Superior Completo">Ensino Superior Completo</option>
                                <option value="Ensino Superior Incompleto">Ensino Superior Incompleto</option>
                                <option value="Pós-graduação">Pós-graduação</option>
                                <option value="Mestrado">Mestrado</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Etnia/Raça *</label>
                            <select name="fundador_principal[etnia]" class="form-select" required>
                                <option value="">Selecione...</option>
                                <option value="Branco">Branco(a)</option>
                                <option value="Preto">Preto(a)</option>
                                <option value="Pardo">Pardo(a)</option>
                                <option value="Amarelo">Amarelo(a)</option>
                                <option value="Indígena">Indígena</option>
                                <option value="Prefiro não responder">Prefiro não responder</option>
                            </select>
                        </div>

                        <!-- Endereço -->
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Endereço</label>
                            <div class="form-check">
                                <input class="form-check-input endereco-radio" type="radio" name="fundador_principal[endereco_tipo]" value="negocio" checked>
                                <label class="form-check-label">Usar mesmo endereço do negócio</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input endereco-radio" type="radio" name="fundador_principal[endereco_tipo]" value="residencial">
                                <label class="form-check-label">Cadastrar endereço residencial</label>
                            </div>
                        </div>

                        <div class="campos-residencial" style="display:none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                            <label class="form-label">Rua</label>
                            <input type="text" name="fundador_principal[rua]" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                            <label class="form-label">Número</label>
                            <input type="text" name="fundador_principal[numero]" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                            <label class="form-label">CEP</label>
                            <input type="text" name="fundador_principal[cep]" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Município</label>
                                <input type="text" name="fundador_principal[municipio]" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select">
                                    <option value="">Selecione...</option>
                                    <option value="AC">Acre (AC)</option>
                                    <option value="AL">Alagoas (AL)</option>
                                    <option value="AP">Amapá (AP)</option>
                                    <option value="AM">Amazonas (AM)</option>
                                    <option value="BA">Bahia (BA)</option>
                                    <option value="CE">Ceará (CE)</option>
                                    <option value="DF">Distrito Federal (DF)</option>
                                    <option value="ES">Espírito Santo (ES)</option>
                                    <option value="GO">Goiás (GO)</option>
                                    <option value="MA">Maranhão (MA)</option>
                                    <option value="MT">Mato Grosso (MT)</option>
                                    <option value="MS">Mato Grosso do Sul (MS)</option>
                                    <option value="MG">Minas Gerais (MG)</option>
                                    <option value="PA">Pará (PA)</option>
                                    <option value="PB">Paraíba (PB)</option>
                                    <option value="PR">Paraná (PR)</option>
                                    <option value="PE">Pernambuco (PE)</option>
                                    <option value="PI">Piauí (PI)</option>
                                    <option value="RJ">Rio de Janeiro (RJ)</option>
                                    <option value="RN">Rio Grande do Norte (RN)</option>
                                    <option value="RS">Rio Grande do Sul (RS)</option>
                                    <option value="RO">Rondônia (RO)</option>
                                    <option value="RR">Roraima (RR)</option>
                                    <option value="SC">Santa Catarina (SC)</option>
                                    <option value="SP">São Paulo (SP)</option>
                                    <option value="SE">Sergipe (SE)</option>
                                    <option value="TO">Tocantins (TO)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                <?php endif; ?>

                <!-- COFUNDADORES DINÂMICOS -->
                <div class="card mb-4">
                    <div class="card-header"><h3>Cofundadores</h3></div>
                        <div class="card-body" id="cofundadores-container">
                    <!-- Blocos de cofundadores serão adicionados aqui via JS -->
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-outline-secondary" id="add-cofundador">Adicionar Cofundador</button>
                        </div>
                    </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                    const container = document.getElementById('cofundadores-container');
                    const addBtn = document.getElementById('add-cofundador');
                    let count = 0;
                    const maxCofundadores = 4;

                    addBtn.addEventListener('click', function () {
    if (count >= maxCofundadores) {
        alert("Você pode adicionar no máximo 4 cofundadores.");
        return;
    }
    count++;
    const bloco = document.createElement('div');
    bloco.classList.add('card','mb-3','p-3');
    bloco.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Cofundador ${count}</h5>
            <button type="button" class="btn btn-sm btn-danger remove-cofundador">Cancelar</button>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Nome *</label>
                <input type="text" name="cofundador[${count}][nome]" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Sobrenome *</label>
                <input type="text" name="cofundador[${count}][sobrenome]" class="form-control" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> CPF *</label>
                <input type="text" name="cofundador[${count}][cpf]" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> E-mail *</label>
                <input type="email" name="cofundador[${count}][email]" class="form-control" required>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="cofundador[${count}][email_optin]" value="1">
                    <label class="form-check-label">Aceito receber atualizações via e-mail</label>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Celular *</label>
                <input type="text" name="cofundador[${count}][celular]" class="form-control" required>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="cofundador[${count}][whatsapp_optin]" value="1">
                    <label class="form-check-label">Aceito receber novidades via WhatsApp</label>
                </div>
            </div>
        </div>
    `;
    container.appendChild(bloco);

    // Botão de remover
    bloco.querySelector('.remove-cofundador').addEventListener('click', function () {
        bloco.remove();
        count--;
    });
});
                });
                </script>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="/negocios/editar_etapa1.php?id=<?= $_SESSION['negocio_id'] ?>" 
                    class="btn btn-secondary me-md-2">← Voltar</a>
                    <button type="submit" name="acao" value="salvar" class="btn btn-primary">Salvar e Avançar</button>
                    <button type="submit" name="acao" value="pular_cofundadores" class="btn btn-outline-primary">
                    Não tenho cofundadores, avançar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const radios = document.querySelectorAll('input[name="fundador_principal[endereco_tipo]"]');
        const camposResidencial = document.querySelector('.campos-residencial');

        radios.forEach(radio => {
            radio.addEventListener('change', function () {
            if (this.value === 'residencial') {
                camposResidencial.style.display = 'block';
            } else {
                camposResidencial.style.display = 'none';
            }
            });
        });
    });
</script>

<script>
// Validação CPF para fundadores/cofundadores - Etapa 2
document.addEventListener('DOMContentLoaded', function() {
  
  // Pega todos inputs CPF dos fundadores
  const cpfInputs = document.querySelectorAll('input[name*="cpf"]');
  
  cpfInputs.forEach(function(input) {
    // Máscara CPF
    IMask(input, {
      mask: '000.000.000-00',
      lazy: false
    });
    
    // Validação em blur
    input.addEventListener('blur', function() {
      const cpf = this.value.replace(/\D/g, '');
      if (cpf && cpf.length === 11) {
        if (!isValidCPF(cpf)) {
          this.classList.add('is-invalid');
          mostrarErro(this, 'CPF inválido');
        } else {
          this.classList.add('is-valid');
          limparErro(this);
        }
      } else if (cpf) {
        this.classList.add('is-invalid');
        mostrarErro(this, 'Digite um CPF completo (11 dígitos)');
      }
    });
    
    // Limpa em focus
    input.addEventListener('focus', function() {
      limparErro(this);
    });
  });
  
  // Bloqueia submit se inválido
  const form = document.querySelector('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      let invalido = false;
      cpfInputs.forEach(function(input) {
        const cpf = input.value.replace(/\D/g, '');
        if (cpf && cpf.length === 11 && !isValidCPF(cpf)) {
          invalido = true;
          input.classList.add('is-invalid');
          mostrarErro(input, 'CPF inválido');
        }
      });
      
      if (invalido) {
        e.preventDefault();
        cpfInputs[0].focus();
        alert('Corrija todos os CPFs inválidos antes de continuar.');
        return false;
      }
    });
  }
  
  // Funções (igual ao da etapa 1)
  function isValidCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;
    
    let sum = 0;
    for (let i = 0; i < 9; i++) sum += parseInt(cpf.charAt(i)) * (10 - i);
    let r = sum % 11;
    let d1 = (r < 2) ? 0 : 11 - r;
    if (d1 !== parseInt(cpf.charAt(9))) return false;
    
    sum = 0;
    for (let i = 0; i < 10; i++) sum += parseInt(cpf.charAt(i)) * (11 - i);
    r = sum % 11;
    let d2 = (r < 2) ? 0 : 11 - r;
    return d2 === parseInt(cpf.charAt(10));
  }
  
  function mostrarErro(input, msg) {
    let erro = input.parentNode.querySelector('.invalid-feedback');
    if (!erro) {
      erro = document.createElement('div');
      erro.className = 'invalid-feedback d-block';
      input.parentNode.appendChild(erro);
    }
    erro.textContent = msg;
  }
  
  function limparErro(input) {
    const erro = input.parentNode.querySelector('.invalid-feedback');
    if (erro) erro.remove();
    input.classList.remove('is-invalid', 'is-valid');
  }
});
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>
