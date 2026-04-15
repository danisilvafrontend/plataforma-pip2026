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

$stmt = $pdo->prepare("SELECT * FROM sociedade_civil WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Usuário não encontrado.");
}

$nomeCompleto = trim(($user['nome'] ?? '') . ' ' . ($user['sobrenome'] ?? ''));
$iniciais = strtoupper(
    mb_substr($user['nome'] ?? '', 0, 1) .
    mb_substr($user['sobrenome'] ?? '', 0, 1)
);

if (trim($iniciais) === '') {
    $iniciais = 'SC';
}

$localizacao = trim(($user['cidade'] ?? '') . ' - ' . ($user['estado'] ?? ''));
if ($localizacao === '-' || $localizacao === '') {
    $localizacao = 'Não informada';
}
$nomeCompleto = trim(($user['nome'] ?? '') . ' ' . ($user['sobrenome'] ?? ''));
$iniciais = strtoupper(
    mb_substr($user['nome'] ?? '', 0, 1) .
    mb_substr($user['sobrenome'] ?? '', 0, 1)
);

if (trim($iniciais) === '') {
    $iniciais = 'SC';
}

$nomeCompletoSidebar = $nomeCompleto;
$emailSidebar = $user['email'] ?? '';
$iniciaisSidebar = $iniciais;
$tipoContaSidebar = 'Sociedade Civil';
$menuAtivoSidebar = 'meus-dados';

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="minha-conta-page py-4 py-lg-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-12 col-lg-4 col-xl-3">
                <?php include __DIR__ . '/../app/views/sociedade/sidebar.php'; ?>
            </div>

            <div class="col-12 col-lg-8 col-xl-9">
                <section class="conta-main-card">
                    <div class="conta-main-header">
                        <div>
                            <h2>Minha conta</h2>
                            <p>Gerencie seus dados pessoais e acompanhe as informações do seu perfil.</p>
                        </div>

                        <a href="editar_conta.php" class="btn btn-primary">
                            <i class="bi bi-pencil-square me-2"></i>Editar dados
                        </a>
                    </div>

                    <div class="conta-main-body">
                        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'sucesso'): ?>
                            <div class="alert alert-success conta-alert-sucesso">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Dados atualizados com sucesso!
                            </div>
                        <?php endif; ?>

                        <div class="row g-2 conta-resumo-cards">
                            <div class="col-12 col-md-4">
                                <div class="conta-resumo-card">
                                    <span class="conta-resumo-label">E-mail</span>
                                    <div class="conta-resumo-value"><?= htmlspecialchars($user['email']) ?></div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="conta-resumo-card">
                                    <span class="conta-resumo-label">Localização</span>
                                    <div class="conta-resumo-value"><?= htmlspecialchars($localizacao) ?></div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="conta-resumo-card">
                                    <span class="conta-resumo-label">Profissão</span>
                                    <div class="conta-resumo-value"><?= htmlspecialchars($user['profissao'] ?? 'Não informada') ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="conta-section">
                            <h3>Informações pessoais</h3>

                            <div class="conta-info-grid">
                                <div class="conta-info-item">
                                    <span class="conta-info-label">Nome completo</span>
                                    <div class="conta-info-value"><?= htmlspecialchars($nomeCompleto) ?></div>
                                </div>

                                <div class="conta-info-item">
                                    <span class="conta-info-label">CPF</span>
                                    <div class="conta-info-value"><?= htmlspecialchars($user['cpf']) ?></div>
                                </div>

                                <div class="conta-info-item">
                                    <span class="conta-info-label">Data de nascimento</span>
                                    <div class="conta-info-value">
                                        <?= !empty($user['data_nascimento']) ? htmlspecialchars(date('d/m/Y', strtotime($user['data_nascimento']))) : 'Não informada' ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="conta-section">
                            <h3>Contato</h3>

                            <div class="conta-info-grid">
                                <div class="conta-info-item">
                                    <span class="conta-info-label">Celular / WhatsApp</span>
                                    <div class="conta-info-value"><?= htmlspecialchars($user['celular'] ?? 'Não informado') ?></div>
                                </div>

                                <div class="conta-info-item">
                                    <span class="conta-info-label">Cidade e estado</span>
                                    <div class="conta-info-value"><?= htmlspecialchars($localizacao) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="conta-section">
                            <h3>Perfil</h3>

                            <div class="conta-info-grid">
                                <div class="conta-info-item">
                                    <span class="conta-info-label">Tipo de conta</span>
                                    <div class="conta-info-value">Sociedade Civil</div>
                                </div>

                                <div class="conta-info-item">
                                    <span class="conta-info-label">Profissão / área de atuação</span>
                                    <div class="conta-info-value"><?= htmlspecialchars($user['profissao'] ?? 'Não informada') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>