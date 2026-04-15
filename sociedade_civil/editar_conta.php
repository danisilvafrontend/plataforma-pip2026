<?php
session_start();

if (empty($_SESSION['logado']) || $_SESSION['usuario_tipo'] !== 'sociedade_civil') {
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

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
function emailExisteEmOutraTabela(PDO $pdo, string $email, int $usuarioId): bool
{
    $consultas = [
        [
            'sql' => "SELECT COUNT(*) FROM sociedade_civil WHERE email = ? AND id != ?",
            'params' => [$email, $usuarioId]
        ],
        [
            'sql' => "SELECT COUNT(*) FROM empreendedores WHERE email = ?",
            'params' => [$email]
        ],
        [
            'sql' => "SELECT COUNT(*) FROM users WHERE email = ?",
            'params' => [$email]
        ],
        [
            'sql' => "SELECT COUNT(*) 
                      FROM parceiros 
                      WHERE email_login = ? 
                         OR rep_email = ? 
                         OR op_email = ?",
            'params' => [$email, $email, $email]
        ],
    ];

    foreach ($consultas as $consulta) {
        $stmt = $pdo->prepare($consulta['sql']);
        $stmt->execute($consulta['params']);

        if ((int)$stmt->fetchColumn() > 0) {
            return true;
        }
    }

    return false;
}

$stmt = $pdo->prepare("SELECT * FROM sociedade_civil WHERE id = ?");
$stmt->execute([$usuarioId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Usuário não encontrado.');
}

$identificacoesSalvas = [];
if (!empty($user['identificacoes'])) {
    $identificacoesSalvas = json_decode($user['identificacoes'], true);
    if (!is_array($identificacoesSalvas)) {
        $identificacoesSalvas = [];
    }
}

$motivacoesSalvas = [];
if (!empty($user['motivacoes'])) {
    $motivacoesSalvas = json_decode($user['motivacoes'], true);
    if (!is_array($motivacoesSalvas)) {
        $motivacoesSalvas = [];
    }
}

$opcoesProfissao = ['Saúde', 'Educação', 'Tecnologia', 'Agronegócio', 'Serviços', 'Outro'];

$opcoesIdentificacoes = [
    'Sociedade civil' => 'Sociedade civil / cidadão(ã)',
    'Profissional' => 'Profissional (CLT, autônomo etc.)',
    'Estudante' => 'Estudante',
    'Voluntário' => 'Voluntário(a)',
    'Empreendedor' => 'Empreendedor(a)',
    'Investidor' => 'Investidor(a)',
    'Outro' => 'Outro',
];

$opcoesMotivacoes = [
    'Votar' => 'Quero votar no prêmio',
    'Conhecer' => 'Quero conhecer negócios de impacto',
    'Engajar' => 'Quero me engajar e participar',
    'Voluntariado' => 'Quero apoiar com voluntariado',
    'Investir' => 'Quero investir / doar',
    'Outro' => 'Outro',
];

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $sobrenome = trim($_POST['sobrenome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dataNascimento = trim($_POST['data_nascimento'] ?? '');
    $celular = trim($_POST['celular'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $profissao = trim($_POST['profissao'] ?? '');
    $organizacao = trim($_POST['organizacao'] ?? '');
    $emailAutorizacao = isset($_POST['email_autorizacao']) ? 1 : 0;
    $celularAutorizacao = isset($_POST['celular_autorizacao']) ? 1 : 0;
    $identificacoes = $_POST['identificacoes'] ?? [];
    $motivacoes = $_POST['motivacoes'] ?? [];

    if ($nome === '') {
        $erros[] = 'Informe o nome.';
    }

    if ($sobrenome === '') {
        $erros[] = 'Informe o sobrenome.';
    }

    if ($email === '') {
        $erros[] = 'Informe o e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    } elseif (emailExisteEmOutraTabela($pdo, $email, $usuarioId)) {
        $erros[] = 'Este e-mail já está cadastrado em outra conta.';
    }

    if ($dataNascimento === '') {
        $erros[] = 'Informe a data de nascimento.';
    }

    if ($celular === '') {
        $erros[] = 'Informe o celular / WhatsApp.';
    }

    if ($cidade === '') {
        $erros[] = 'Informe a cidade.';
    }

    if ($estado === '') {
        $erros[] = 'Informe o estado.';
    }

    if ($profissao === '') {
        $erros[] = 'Selecione a profissão / área de atuação.';
    }

    if (count($identificacoes) > 3) {
        $erros[] = 'Selecione no máximo 3 opções em "Você se identifica como".';
    }

    if (empty($erros)) {
        $sql = "UPDATE sociedade_civil
            SET nome = ?,
                sobrenome = ?,
                email = ?,
                data_nascimento = ?,
                celular = ?,
                cidade = ?,
                estado = ?,
                profissao = ?,
                organizacao = ?,
                email_autorizacao = ?,
                celular_autorizacao = ?,
                identificacoes = ?,
                motivacoes = ?
            WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nome,
            $sobrenome,
            $email,
            $dataNascimento,
            $celular,
            $cidade,
            $estado,
            $profissao,
            $organizacao,
            $emailAutorizacao,
            $celularAutorizacao,
            json_encode(array_values($identificacoes), JSON_UNESCAPED_UNICODE),
            json_encode(array_values($motivacoes), JSON_UNESCAPED_UNICODE),
            $usuarioId
        ]);

        header("Location: minha_conta.php?msg=sucesso");
        exit;
    }

    $user = array_merge($user, [
        'nome' => $nome,
        'sobrenome' => $sobrenome,
        'email' => $email,
        'data_nascimento' => $dataNascimento,
        'celular' => $celular,
        'cep' => $cep,
        'cidade' => $cidade,
        'estado' => $estado,
        'profissao' => $profissao,
        'organizacao' => $organizacao,
        'email_autorizacao' => $emailAutorizacao,
        'celular_autorizacao' => $celularAutorizacao,
    ]);

    $identificacoesSalvas = $identificacoes;
    $motivacoesSalvas = $motivacoes;
}

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="minha-conta-page py-4 py-lg-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-12 col-lg-4 col-xl-3">
                <?php
                $nomeCompletoSidebar = trim(($user['nome'] ?? '') . ' ' . ($user['sobrenome'] ?? ''));
                $iniciaisSidebar = strtoupper(
                    mb_substr($user['nome'] ?? '', 0, 1) .
                    mb_substr($user['sobrenome'] ?? '', 0, 1)
                );
                if (trim($iniciaisSidebar) === '') {
                    $iniciaisSidebar = 'SC';
                }
                $emailSidebar = $user['email'] ?? '';
                $tipoContaSidebar = 'Sociedade Civil';
                $menuAtivoSidebar = 'meus-dados';

                include __DIR__ . '/../app/views/sociedade/sidebar.php';
                ?>
            </div>

            <div class="col-12 col-lg-8 col-xl-9">
                <section class="conta-main-card">
                    <div class="conta-main-header">
                        <div>
                            <h2>Editar dados pessoais</h2>
                            <p>Atualize as informações da primeira etapa do seu cadastro.</p>
                        </div>

                        <a href="minha_conta.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>Voltar
                        </a>
                    </div>

                    <div class="conta-main-body">
                        <?php if (!empty($erros)): ?>
                            <div class="alert alert-danger rounded-4">
                                <div class="fw-semibold mb-2">Encontramos alguns pontos para corrigir:</div>
                                <ul class="mb-0">
                                    <?php foreach ($erros as $erro): ?>
                                        <li><?= htmlspecialchars($erro) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="" method="post">
                            <div class="conta-section">
                                <h3>Identificação</h3>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">CPF</label>
                                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['cpf'] ?? '') ?>" readonly>
                                        <div class="form-text">O CPF não pode ser alterado.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Data de nascimento <span class="text-danger">*</span></label>
                                        <input type="date" name="data_nascimento" class="form-control" value="<?= htmlspecialchars($user['data_nascimento'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="conta-section">
                                <h3>Informações pessoais</h3>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                                        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($user['nome'] ?? '') ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Sobrenome <span class="text-danger">*</span></label>
                                        <input type="text" name="sobrenome" class="form-control" value="<?= htmlspecialchars($user['sobrenome'] ?? '') ?>" required>
                                    </div>
                                </div>

                                <div class="row g-3 mt-1">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>

                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="email_autorizacao" value="1" id="checkEmail"
                                                <?= !empty($user['email_autorizacao']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="checkEmail">
                                                Aceito receber notificações por e-mail
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Celular / WhatsApp <span class="text-danger">*</span></label>
                                        <input type="text" name="celular" class="form-control" value="<?= htmlspecialchars($user['celular'] ?? '') ?>" required>

                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="celular_autorizacao" value="1" id="checkWhats"
                                                <?= !empty($user['celular_autorizacao']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="checkWhats">
                                                Aceito receber notificações por WhatsApp
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mt-1">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">CEP</label>
                                        <input type="text" name="cep" class="form-control" value="<?= htmlspecialchars($user['cep'] ?? '') ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Cidade <span class="text-danger">*</span></label>
                                        <input type="text" name="cidade" class="form-control" value="<?= htmlspecialchars($user['cidade'] ?? '') ?>" required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                                        <input type="text" name="estado" class="form-control" value="<?= htmlspecialchars($user['estado'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="conta-section">
                                <h3>Seu perfil profissional</h3>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Profissão / Área de atuação <span class="text-danger">*</span></label>
                                        <select name="profissao" class="form-select" required>
                                            <option value="">Selecione...</option>
                                            <?php foreach ($opcoesProfissao as $opcao): ?>
                                                <option value="<?= htmlspecialchars($opcao) ?>" <?= (($user['profissao'] ?? '') === $opcao) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($opcao) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Organização onde trabalha <span class="text-muted">(opcional)</span></label>
                                        <input type="text" name="organizacao" class="form-control" value="<?= htmlspecialchars($user['organizacao'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="row g-4 mt-1">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Você se identifica como <span class="text-muted">(até 3 escolhas)</span></label>

                                        <div class="cadastro-check-grid">
                                            <?php foreach ($opcoesIdentificacoes as $valor => $label): ?>
                                                <label class="cadastro-check-card">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="identificacoes[]"
                                                        value="<?= htmlspecialchars($valor) ?>"
                                                        <?= in_array($valor, $identificacoesSalvas, true) ? 'checked' : '' ?>
                                                    >
                                                    <span><?= htmlspecialchars($label) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">O que te trouxe até aqui hoje?</label>

                                        <div class="cadastro-check-grid">
                                            <?php foreach ($opcoesMotivacoes as $valor => $label): ?>
                                                <label class="cadastro-check-card">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="motivacoes[]"
                                                        value="<?= htmlspecialchars($valor) ?>"
                                                        <?= in_array($valor, $motivacoesSalvas, true) ? 'checked' : '' ?>
                                                    >
                                                    <span><?= htmlspecialchars($label) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end mt-4">
                                <a href="minha_conta.php" class="btn btn-light">Cancelar</a>
                                <a href="editar_interesse.php" class="btn btn-outline-secondary">Editar interesses e perfil</a>
                                <button type="submit" class="btn btn-primary px-4">Salvar alterações</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>