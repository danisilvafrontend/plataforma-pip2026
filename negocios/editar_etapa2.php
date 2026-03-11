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

// Busca dados do negócio e valida permissão
if (isset($_SESSION['admin_id'])) {
    // Admin pode editar qualquer negócio
    $stmt = $pdo->prepare("
        SELECT n.*, e.eh_fundador 
        FROM negocios n 
        JOIN empreendedores e ON n.empreendedor_id = e.id 
        WHERE n.id = ?
    ");
    $stmt->execute([$negocio_id]);
} else {
    // Empreendedor só pode editar os próprios negócios
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

// Lógica: se empreendedor é fundador, não precisa cadastrar principal
$empreendedorEhFundador = (int)$negocio['eh_fundador'];
$permiteFundadorPrincipal = $empreendedorEhFundador === 0;

// Busca fundadores já cadastrados
$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separa principal e cofundadores PARA EDIÇÃO
$fundadorPrincipal = null;
$cofundadores = [];

foreach ($fundadoresExistentes as $f) {
    if (strtolower(trim($f['tipo'])) === 'principal') {
        $fundadorPrincipal = $f;
    } else {
        $cofundadores[] = $f;
    }
}

// Garante 4 slots de cofundadores (preenche com vazios para edição)
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

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-4">Editar Etapa 2 - Fundadores</h1>
        <a href="/empreendedores/meus-negocios.php" class="btn btn-secondary">← Voltar aos negócios</a>
    </div>
    
    <?php
    include __DIR__ . '/../app/views/partials/intro_text_fundadores.php';
    ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h5>Erros encontrados:</h5>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>Dados Fundador e Cofundadores  <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> </h3>
        </div>
        <div class="card-body">
            
            <form action="/negocios/processar_etapa2.php" method="POST" id="formEtapa2">
                <input type="hidden" name="negocio_id" value="<?php echo $negocio_id; ?>">
                <input type="hidden" name="modo" value="editar">

                <?php if ($permiteFundadorPrincipal): ?>
                    <!-- FUNDADOR PRINCIPAL     -->
                    <div class="fundador-principal mb-5 p-4 border rounded">
                        <h4 class="mb-4">
                            <i class="fas fa-crown text-warning"></i> 
                            Fundador Principal 
                        </h4>

                        <?php 
                        $fp_data = $fundadorPrincipal ?: [
                            'nome' => '', 'sobrenome' => '', 'cpf' => '', 'email' => '', 'celular' => '',
                            'data_nascimento' => '', 'genero' => '', 'formacao' => '', 'etnia' => '',
                            'email_optin' => 0, 'whatsapp_optin' => 0, 'endereco_tipo' => 'negocio',
                            'rua' => '', 'numero' => '', 'cep' => '', 'municipio' => '', 'estado' => ''
                        ];
                        ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nome Completo *</label>
                                <div class="row">
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="fundador_principal[nome]" 
                                               value="<?php echo htmlspecialchars($fp_data['nome'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-4">
                                        <input type="text" class="form-control" name="fundador_principal[sobrenome]" 
                                               value="<?php echo htmlspecialchars($fp_data['sobrenome'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">CPF *</label>
                                <input type="text" class="form-control cpf-mask" name="fundador_principal[cpf]" 
                                       value="<?php echo htmlspecialchars($fp_data['cpf'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Email *</label>
                                <input type="email" class="form-control" name="fundador_principal[email]" 
                                       value="<?php echo htmlspecialchars($fp_data['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Celular *</label>
                                <input type="text" class="form-control tel-mask" name="fundador_principal[celular]" 
                                       value="<?php echo htmlspecialchars($fp_data['celular'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Data de Nascimento *</label>
                                <input type="date" class="form-control" name="fundador_principal[data_nascimento]" 
                                       value="<?php echo htmlspecialchars($fp_data['data_nascimento'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Gênero *</label>
                               <select class="form-select" name="fundador_principal[genero]" required>
                                    <option value="">Selecione...</option>
                                    <?php 
                                    $generos = ['Masculino', 'Feminino', 'Não Binário', 'Outro', 'Prefiro não informar'];
                                    foreach($generos as $g) {
                                        $selected = ($fp_data['genero'] ?? '') == $g ? 'selected' : '';
                                        echo "<option value='$g' $selected>$g</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Formação Acadêmica *</label>
                                <input type="text" class="form-control" name="fundador_principal[formacao]" 
                                       value="<?php echo htmlspecialchars($fp_data['formacao'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Etnia/Raça *</label>
                                <select class="form-select" name="fundador_principal[etnia]" required>
                                    <option value="">Selecione...</option>
                                    <?php 
                                    $etnias = ['Branco', 'Pardo', 'Preto', 'Amarelo', 'Indígena'];
                                    foreach($etnias as $e) {
                                        $selected = ($fp_data['etnia'] ?? '') == $e ? 'selected' : '';
                                        echo "<option value='$e' $selected>$e</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- ENDEREÇO DO FUNDADOR PRINCIPAL -->
                        <div class="mt-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Tipo de Endereço</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="fundador_principal[endereco_tipo]" 
                                               id="end_negocio" value="negocio" <?php echo ($fp_data['endereco_tipo'] ?? 'negocio') == 'negocio' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="end_negocio">
                                            Mesmo endereço do negócio
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="fundador_principal[endereco_tipo]" 
                                               id="end_residencial" value="residencial" <?php echo ($fp_data['endereco_tipo'] ?? '') == 'residencial' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="end_residencial">
                                            Endereço residencial próprio
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos de endereço residencial (condicional) -->
                        <div id="endereco_residencial" style="display:<?php echo ($fp_data['endereco_tipo'] ?? '') == 'residencial' ? 'block' : 'none'; ?>;">
                            <hr>
                            <h6 class="text-muted">Endereço Residencial</h6>
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label>Logradouro</label>
                                    <input type="text" class="form-control" name="fundador_principal[rua]" 
                                           value="<?php echo htmlspecialchars($fp_data['rua'] ?? ''); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label>Nº</label>
                                    <input type="text" class="form-control" name="fundador_principal[numero]" 
                                           value="<?php echo htmlspecialchars($fp_data['numero'] ?? ''); ?>">
                                </div>
                                <div class="col-md-5">
                                    <label>CEP</label>
                                    <input type="text" class="form-control cep-mask" name="fundador_principal[cep]" 
                                           value="<?php echo htmlspecialchars($fp_data['cep'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-8">
                                    <label>Município</label>
                                    <input type="text" class="form-control" name="fundador_principal[municipio]" 
                                           value="<?php echo htmlspecialchars($fp_data['municipio'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label>UF</label>
                                    <input type="text" class="form-control" maxlength="2" name="fundador_principal[estado]" 
                                           value="<?php echo htmlspecialchars($fp_data['estado'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- OPT-INs -->
                        <div class="row g-3 mt-4">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fundador_principal[email_optin]" 
                                           id="fp_email_optin" <?php echo ($fp_data['email_optin'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="fp_email_optin">
                                        Aceito receber comunicações por email
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fundador_principal[whatsapp_optin]" 
                                           id="fp_whatsapp_optin" <?php echo ($fp_data['whatsapp_optin'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="fp_whatsapp_optin">
                                        Aceito receber comunicações por WhatsApp
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-5">

                <?php else: ?>
                    <div class="alert alert-success mb-5">
                        <i class="fas fa-check-circle"></i>
                        <strong>Você é o fundador principal!</strong><br>
                        Como você marcou que é fundador no seu cadastro de empreendedor, não precisa preencher esta seção.
                    </div>
                <?php endif; ?>

                <!-- ====================== -->
                <!-- COFUNDADORES           -->
                <!-- ====================== -->
                <h4 class="mb-4">
                    <i class="fas fa-users text-primary"></i> 
                    Cofundadores (opcional - até 4 pessoas)
                </h4>

                <?php foreach ($cofundadores as $index => $cofundador): ?>
                    <!-- CORREÇÃO AQUI: Verifica se o cofundador tem ID para definir a borda, em vez de usar contagem do array pai -->
                    <div class="cofundador-card card mb-4 <?php echo empty($cofundador['id']) ? 'border-dashed' : ''; ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Cofundador <?= $index + 1 ?></h5>
                            <?php if (!empty($cofundador['id'])): ?>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="cofundador[<?= $index ?>][remover]" value="1">
                                    <label class="form-check-label text-danger small">Remover</label>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="cofundador[<?= $index ?>][id]" value="<?= htmlspecialchars($cofundador['id'] ?? ''); ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nome Completo</label>
                                    <div class="row">
                                        <div class="col-8">
                                            <input type="text" class="form-control" name="cofundador[<?= $index ?>][nome]" 
                                                   value="<?= htmlspecialchars($cofundador['nome'] ?? ''); ?>" placeholder="Nome">
                                        </div>
                                        <div class="col-4">
                                            <input type="text" class="form-control" name="cofundador[<?= $index ?>][sobrenome]" 
                                                   value="<?= htmlspecialchars($cofundador['sobrenome'] ?? ''); ?>" placeholder="Sobrenome">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CPF</label>
                                    <input type="text" class="form-control cpf-mask" name="cofundador[<?= $index ?>][cpf]" 
                                           value="<?= htmlspecialchars($cofundador['cpf'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="cofundador[<?= $index ?>][email]" 
                                           value="<?= htmlspecialchars($cofundador['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Telefone</label>
                                    <input type="text" class="form-control tel-mask" name="cofundador[<?= $index ?>][celular]" 
                                           value="<?= htmlspecialchars($cofundador['celular'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="cofundador[<?= $index ?>][email_optin]" 
                                           id="cof_<?= $index ?>_email" <?= ($cofundador['email_optin'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cof_<?= $index ?>_email">Aceito receber atualizações via e-mail</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="cofundador[<?= $index ?>][whatsapp_optin]" 
                                           id="cof_<?= $index ?>_whatsapp" <?= ($cofundador['whatsapp_optin'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cof_<?= $index ?>_whatsapp">Aceito receber novidades via WhatsApp</label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex gap-3 mt-5 pt-4 border-top">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save me-2"></i> Salvar Alterações
                    </button>
                    <a href="/empreendedores/meus-negocios.php" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                </div>
                
            </form>
        </div><!-- fecha card-body -->
    </div><!-- fecha card -->
</div><!-- fecha container -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle endereço residencial
    const radios = document.querySelectorAll('input[name="fundador_principal[endereco_tipo]"]');
    const enderecoDiv = document.getElementById('endereco_residencial');

    if(radios.length > 0 && enderecoDiv) {
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                enderecoDiv.style.display = this.value === 'residencial' ? 'block' : 'none';
            });
        });
    }
});
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>
