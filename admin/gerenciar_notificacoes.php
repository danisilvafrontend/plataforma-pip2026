<?php
// /public_html/admin/gerenciar_notificacoes.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$possibleAppPaths = [
    __DIR__ . '/../app',
    __DIR__ . '/../../app',
    __DIR__ . '/app',
];

$appBase = null;
foreach ($possibleAppPaths as $p) {
    if (is_dir($p)) {
        $appBase = realpath($p);
        break;
    }
}

if ($appBase === null) {
    die('Erro: pasta app não encontrada.');
}

require_once $appBase . '/helpers/auth.php';
require_once $appBase . '/helpers/mail.php';
require_once $appBase . '/helpers/render.php';

require_admin_login();

$config = require $appBase . '/config/db.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}

$pageTitle = 'Gerenciar notificações';
$mensagem = '';
$filtro = trim($_GET['filtro'] ?? 'pendentes');
$busca = trim($_GET['busca'] ?? '');
$origemSelecionada = trim($_GET['origem_importacao'] ?? $_POST['origem_importacao'] ?? '');

function montarEmailPrimeiroAcesso(): string
{
    return '
    <div style="font-family: Arial, Helvetica, sans-serif; color: #243328; line-height: 1.6; max-width: 640px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e7ece8; border-radius: 10px; overflow: hidden;">

        <div style="padding: 32px 28px 10px 28px; text-align: center; border-bottom: 1px solid #e7ece8;">
            <h1 style="margin: 0; color: #1D3427; font-size: 28px; line-height: 1.2;">
                Não perca sua vitrine na Impactos Positivos
            </h1>
            <p style="margin: 14px 0 0 0; color: #5B6B63; font-size: 16px;">
                Agora, falta só um passo para continuar com a gente em uma plataforma ainda mais completa.
            </p>
        </div>

        <div style="padding: 28px;">
            <p style="margin: 0 0 18px 0; font-size: 16px; color: #31443A;">
                Olá, <strong>{{nome}}</strong>,
            </p>

            <p style="margin: 0 0 18px 0; font-size: 16px; color: #31443A;">
                Você já faz parte da vitrine de negócios da <strong>Impactos Positivos</strong>.
            </p>

            <p style="margin: 0 0 18px 0; font-size: 16px; color: #31443A;">
                Este ano, estamos evoluindo para uma plataforma mais completa, pensada para ampliar a visibilidade dos negócios, gerar conexões e criar novas oportunidades para quem está transformando a economia.
            </p>

            <p style="margin: 0 0 22px 0; font-size: 16px; color: #31443A;">
                Para seguir com a gente, falta apenas um passo:
            </p>

            <div style="text-align: center; margin: 0 0 28px 0;">
                <a href="{{link_painel}}" style="background-color: #1D4F3A; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-size: 16px; font-weight: bold; display: inline-block;">
                    Atualizar cadastro
                </a>
            </div>

            <p style="margin: 0 0 14px 0; font-size: 16px; color: #31443A;">
                O processo é simples e gratuito:
            </p>

            <ul style="padding-left: 20px; margin: 0 0 24px 0; color: #31443A; font-size: 15px;">
                <li style="margin-bottom: 8px;">Acesse a plataforma.</li>
                <li style="margin-bottom: 8px;">Defina sua nova senha <small>(Link <strong>"Esqueceu a Senha?"</strong>)</small></li>
                <li style="margin-bottom: 8px;">Preencha seus dados na nova plataforma.</li>
                <li style="margin-bottom: 8px;">Complete o perfil do seu negócio.</li>
            </ul>

            <div style="text-align: center; margin: 0 0 28px 0;">
                <a href="{{link_painel}}" style="color: #0B6B74; font-size: 15px; font-weight: bold; text-decoration: none;">
                    Atualizar cadastro
                </a>
            </div>

            <p style="margin: 0 0 18px 0; font-size: 16px; color: #31443A;">
                Ao atualizar seu cadastro, seu negócio segue ativo e preparado para aproveitar tudo o que estamos construindo para 2026.
            </p>

            <p style="margin: 0 0 18px 0; font-size: 15px; color: #31443A;">
                Se precisar de apoio, nosso time está disponível para te ajudar.
                Fale com o PIP pelo WhatsApp:
                <a href="https://api.whatsapp.com/send?phone=551123673170&text=Ol%C3%A1%2C%20seja%20bem-vindo%21%20Em%20que%20podemos%20ajudar%3F" style="color: #0B6B74; font-weight: bold; text-decoration: none;" target="_blank">
                    clique aqui para conversar
                </a>.
            </p>

            <p style="margin: 0 0 12px 0; font-size: 15px; color: #31443A;">
                Juntos, ampliamos o que o mundo tem de melhor.
            </p>

            <p style="margin: 0; font-size: 15px; color: #31443A;">
                <strong>Equipe Impactos Positivos</strong>
            </p>
        </div>
    </div>
    ';
}

$stmtOrigens = $pdo->query("
    SELECT DISTINCT origem_importacao
    FROM empreendedores
    WHERE origem_importacao IS NOT NULL
      AND origem_importacao != ''
    ORDER BY origem_importacao ASC
");
$origensImportacao = $stmtOrigens->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'enviar_individual') {
            $empreendedorId = (int)($_POST['empreendedor_id'] ?? 0);

            if ($empreendedorId <= 0) {
                throw new Exception('Empreendedor inválido.');
            }

            $stmt = $pdo->prepare("
                SELECT
                    e.id,
                    e.nome,
                    e.sobrenome,
                    e.email,
                    e.primeiro_acesso_pendente,
                    e.notificacao_primeiro_acesso_enviada,
                    e.notificacao_primeiro_acesso_enviada_em,
                    e.origem_importacao,
                    COUNT(n.id) AS total_negocios
                FROM empreendedores e
                LEFT JOIN negocios n ON n.empreendedor_id = e.id
                WHERE e.id = ?
                  AND e.origem_importacao IS NOT NULL
                  AND e.origem_importacao != ''
                GROUP BY
                    e.id,
                    e.nome,
                    e.sobrenome,
                    e.email,
                    e.primeiro_acesso_pendente,
                    e.notificacao_primeiro_acesso_enviada,
                    e.notificacao_primeiro_acesso_enviada_em,
                    e.origem_importacao
                LIMIT 1
            ");
            $stmt->execute([$empreendedorId]);
            $emp = $stmt->fetch();

            if (!$emp) {
                throw new Exception('Empreendedor importado não encontrado.');
            }

            if ((int)$emp['primeiro_acesso_pendente'] !== 1) {
                throw new Exception('Este cadastro já está ativo e não pode receber nova notificação por esta tela.');
            }

            if (empty($emp['email'])) {
                throw new Exception('Empreendedor sem e-mail cadastrado.');
            }

            $nomeCompleto = trim(($emp['nome'] ?? '') . ' ' . ($emp['sobrenome'] ?? ''));
            if ($nomeCompleto === '') {
                $nomeCompleto = 'Empreendedor(a)';
            }

            $subject = 'Atualize seu cadastro e continue na vitrine da Impactos Positivos 2026';
            $bodyHtml = montarEmailPrimeiroAcesso();

            $rendered = render_email_from_db($subject, $bodyHtml, [
                'nome' => $nomeCompleto,
                'email' => $emp['email'],
                'link_painel' => get_base_url() . '/login.php',
                'link_admin' => get_base_url() . '/admin/gerenciar_notificacoes.php',
                'ano' => date('Y')
            ]);

            $bodyAlt = strip_tags($rendered['bodyHtml'] ?? $bodyHtml);

            send_mail(
                $emp['email'],
                $nomeCompleto,
                $rendered['subject'] ?? $subject,
                $rendered['bodyHtml'] ?? $bodyHtml,
                $bodyAlt
            );

            $stmtUpdate = $pdo->prepare("
                UPDATE empreendedores
                SET notificacao_primeiro_acesso_enviada = 1,
                    notificacao_primeiro_acesso_enviada_em = NOW()
                WHERE id = ?
            ");
            $stmtUpdate->execute([$emp['id']]);

            $mensagem = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <i class='bi bi-envelope-check me-1'></i>
                            E-mail enviado com sucesso para <strong>" . htmlspecialchars($emp['email']) . "</strong>.
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                         </div>";

        } elseif ($acao === 'enviar_lote') {
            $permitirReenvio = isset($_POST['permitir_reenvio']) ? 1 : 0;
            $origemSelecionada = trim($_POST['origem_importacao'] ?? '');

            if ($origemSelecionada === '') {
                throw new Exception('Selecione um arquivo de origem antes de enviar em lote.');
            }

            $sqlLote = "
                SELECT
                    e.id,
                    e.nome,
                    e.sobrenome,
                    e.email,
                    e.primeiro_acesso_pendente,
                    e.notificacao_primeiro_acesso_enviada,
                    e.notificacao_primeiro_acesso_enviada_em,
                    e.origem_importacao
                FROM empreendedores e
                WHERE e.origem_importacao IS NOT NULL
                  AND e.origem_importacao != ''
                  AND e.origem_importacao = ?
                  AND e.primeiro_acesso_pendente = 1
                  AND e.email IS NOT NULL
                  AND e.email != ''
            ";

            if (!$permitirReenvio) {
                $sqlLote .= " AND e.notificacao_primeiro_acesso_enviada = 0 ";
            }

            $sqlLote .= " ORDER BY e.id ASC ";

            $stmtLote = $pdo->prepare($sqlLote);
            $stmtLote->execute([$origemSelecionada]);
            $empreendedoresLote = $stmtLote->fetchAll();

            if (!$empreendedoresLote) {
                $mensagem = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                                Nenhum cadastro importado pendente encontrado para o arquivo <strong>" . htmlspecialchars($origemSelecionada) . "</strong>.
                                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                             </div>";
            } else {
                $enviados = 0;
                $falhas = 0;
                $errosDetalhados = [];
                $emailsFalharam = [];

                $stmtUpdate = $pdo->prepare("
                    UPDATE empreendedores
                    SET notificacao_primeiro_acesso_enviada = 1,
                        notificacao_primeiro_acesso_enviada_em = NOW()
                    WHERE id = ?
                ");

                foreach ($empreendedoresLote as $emp) {
                    try {
                        $nomeCompleto = trim(($emp['nome'] ?? '') . ' ' . ($emp['sobrenome'] ?? ''));
                        if ($nomeCompleto === '') {
                            $nomeCompleto = 'Empreendedor(a)';
                        }

                        $subject = 'Sua conta na Plataforma Impactos Positivos foi atualizada';
                        $bodyHtml = montarEmailPrimeiroAcesso();

                        $rendered = render_email_from_db($subject, $bodyHtml, [
                            'nome' => $nomeCompleto,
                            'email' => $emp['email'],
                            'link_painel' => get_base_url() . '/login.php',
                            'link_admin' => get_base_url() . '/admin/gerenciar_notificacoes.php',
                            'ano' => date('Y')
                        ]);

                        $bodyAlt = strip_tags($rendered['bodyHtml'] ?? $bodyHtml);

                        $enviado = send_mail(
                            $emp['email'],
                            $nomeCompleto,
                            $rendered['subject'] ?? $subject,
                            $rendered['bodyHtml'] ?? $bodyHtml,
                            $bodyAlt
                        );

                        if ($enviado !== true) {
                            throw new Exception('Falha ao enviar e-mail para ' . $emp['email']);
                        }

                        $stmtUpdate->execute([$emp['id']]);
                        $enviados++;

                    } catch (Throwable $mailError) {
                        $falhas++;
                        $emailsFalharam[] = $emp['email'] ?? 'sem email';
                        $errosDetalhados[] = ($emp['email'] ?? 'sem email') . ' - ' . $mailError->getMessage();
                    }
                }

                $mensagem = "
                    <div class='alert alert-success alert-dismissible fade show' role='alert'>
                        <i class='bi bi-send-check me-1'></i>
                        <strong>Envio em lote concluído.</strong><br>
                        <strong>Enviados:</strong> {$enviados}<br>
                        <strong>Falhas:</strong> {$falhas}
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>
                ";

                if (!empty($emailsFalharam)) {
                    $mensagem .= "
                        <div class='alert alert-warning mt-3'>
                            <strong>E-mails com falha (para reenviar depois):</strong><br>
                            " . nl2br(htmlspecialchars(implode("\n", $emailsFalharam))) . "
                        </div>
                    ";
                }

                if (!empty($errosDetalhados)) {
                    $mensagem .= "
                        <div class='alert alert-warning mt-3'>
                            <strong>Detalhes das falhas:</strong><br>
                            " . nl2br(htmlspecialchars(implode("\n", $errosDetalhados))) . "
                        </div>
                    ";
                }
            }
        }
    } catch (Throwable $e) {
        $mensagem = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <i class='bi bi-exclamation-triangle me-1'></i>
                        Erro: " . htmlspecialchars($e->getMessage()) . "
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                     </div>";
    }
}

$where = [];
$params = [];

$where[] = "e.origem_importacao IS NOT NULL";
$where[] = "e.origem_importacao != ''";

if ($filtro === 'pendentes') {
    $where[] = "e.primeiro_acesso_pendente = 1";
} elseif ($filtro === 'nao_notificados') {
    $where[] = "e.primeiro_acesso_pendente = 1";
    $where[] = "e.notificacao_primeiro_acesso_enviada = 0";
} elseif ($filtro === 'notificados') {
    $where[] = "e.notificacao_primeiro_acesso_enviada = 1";
} elseif ($filtro === 'ativos') {
    $where[] = "e.primeiro_acesso_pendente = 0";
}

if ($origemSelecionada !== '') {
    $where[] = "e.origem_importacao = ?";
    $params[] = $origemSelecionada;
}

if ($busca !== '') {
    $where[] = "(e.nome LIKE ? OR e.sobrenome LIKE ? OR e.email LIKE ? OR e.cpf LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$sql = "
    SELECT
        e.id,
        e.nome,
        e.sobrenome,
        e.email,
        e.cpf,
        e.celular,
        e.primeiro_acesso_pendente,
        e.notificacao_primeiro_acesso_enviada,
        e.notificacao_primeiro_acesso_enviada_em,
        e.origem_importacao,
        COUNT(n.id) AS total_negocios
    FROM empreendedores e
    LEFT JOIN negocios n ON n.empreendedor_id = e.id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY
        e.id,
        e.nome,
        e.sobrenome,
        e.email,
        e.cpf,
        e.celular,
        e.primeiro_acesso_pendente,
        e.notificacao_primeiro_acesso_enviada,
        e.notificacao_primeiro_acesso_enviada_em,
        e.origem_importacao
    ORDER BY e.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$empreendedores = $stmt->fetchAll();

$stmtResumo = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN origem_importacao IS NOT NULL AND origem_importacao != '' AND primeiro_acesso_pendente = 1 THEN 1 ELSE 0 END) AS pendentes,
        SUM(CASE WHEN origem_importacao IS NOT NULL AND origem_importacao != '' AND primeiro_acesso_pendente = 0 THEN 1 ELSE 0 END) AS ativos,
        SUM(CASE WHEN origem_importacao IS NOT NULL AND origem_importacao != '' AND notificacao_primeiro_acesso_enviada = 1 THEN 1 ELSE 0 END) AS notificados,
        SUM(CASE WHEN origem_importacao IS NOT NULL AND origem_importacao != '' AND primeiro_acesso_pendente = 1 AND notificacao_primeiro_acesso_enviada = 0 THEN 1 ELSE 0 END) AS nao_notificados
    FROM empreendedores
    WHERE origem_importacao IS NOT NULL
      AND origem_importacao != ''
");
$resumo = $stmtResumo->fetch();

require_once $appBase . '/views/admin/header.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color:#1E3425;">Gerenciar notificações</h4>
            <small style="color:#6c8070;">Tela exclusiva para cadastros importados.</small>
        </div>
    </div>

    <?= $mensagem ?>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm h-100">
                <div class="text-muted small mb-1">Importados</div>
                <div class="fs-3 fw-bold"><?= (int)($resumo['total'] ?? 0) ?></div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm h-100">
                <div class="text-muted small mb-1">Pendentes</div>
                <div class="fs-3 fw-bold text-warning"><?= (int)($resumo['pendentes'] ?? 0) ?></div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm h-100">
                <div class="text-muted small mb-1">Cadastros ativos</div>
                <div class="fs-3 fw-bold text-success"><?= (int)($resumo['ativos'] ?? 0) ?></div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card p-3 border-0 shadow-sm h-100">
                <div class="text-muted small mb-1">Pendentes não notificados</div>
                <div class="fs-3 fw-bold" style="color:#97A327;"><?= (int)($resumo['nao_notificados'] ?? 0) ?></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Envio em lote por arquivo</h5>
        </div>
        <div class="card-body">
            <form method="post" onsubmit="return confirm('Deseja iniciar o envio em lote para o arquivo selecionado?');">
                <input type="hidden" name="acao" value="enviar_lote">

                <p class="text-muted mb-3">
                    O envio em lote notificará apenas os <strong>cadastros importados com acesso pendente</strong> do arquivo selecionado.
                </p>

                <div class="mb-3">
                    <label class="form-label">Arquivo de origem</label>
                    <select name="origem_importacao" class="form-select" required>
                        <option value="">Selecione um arquivo</option>
                        <?php foreach ($origensImportacao as $origem): ?>
                            <option value="<?= htmlspecialchars($origem) ?>" <?= $origemSelecionada === $origem ? 'selected' : '' ?>>
                                <?= htmlspecialchars($origem) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="permitir_reenvio" id="permitirReenvio" value="1">
                    <label class="form-check-label" for="permitirReenvio">
                        Reenviar também para quem ainda não acessou a plataforma
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i> Enviar notificações do arquivo
                </button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Filtros</h5>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label">Status</label>
                    <select name="filtro" class="form-select">
                        <option value="pendentes" <?= $filtro === 'pendentes' ? 'selected' : '' ?>>Acesso pendente</option>
                        <option value="nao_notificados" <?= $filtro === 'nao_notificados' ? 'selected' : '' ?>>Pendentes não notificados</option>
                        <option value="notificados" <?= $filtro === 'notificados' ? 'selected' : '' ?>>Já notificados</option>
                        <option value="ativos" <?= $filtro === 'ativos' ? 'selected' : '' ?>>Cadastro ativo</option>
                        <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos os importados</option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Arquivo de origem</label>
                    <select name="origem_importacao" class="form-select">
                        <option value="">Todos os arquivos</option>
                        <?php foreach ($origensImportacao as $origem): ?>
                            <option value="<?= htmlspecialchars($origem) ?>" <?= $origemSelecionada === $origem ? 'selected' : '' ?>>
                                <?= htmlspecialchars($origem) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="busca" class="form-control" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome, email ou CPF">
                </div>

                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-funnel me-1"></i> Aplicar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Cadastros importados</h5>
        </div>
        <div class="card-body">
            <?php if (empty($empreendedores)): ?>
                <div class="alert alert-light border mb-0">Nenhum cadastro importado encontrado com os filtros informados.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Empreendedor</th>
                                <th>Contato</th>
                                <th>Negócios</th>
                                <th>Status</th>
                                <th>Notificação</th>
                                <th>Origem</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empreendedores as $emp): ?>
                                <?php
                                    $nomeCompleto = trim(($emp['nome'] ?? '') . ' ' . ($emp['sobrenome'] ?? ''));
                                    $pendente = ((int)$emp['primeiro_acesso_pendente'] === 1);
                                    $statusAcesso = $pendente ? 'Pendente' : 'Cadastro ativo';
                                    $notificado = ((int)$emp['notificacao_primeiro_acesso_enviada'] === 1);
                                ?>
                                <tr>
                                    <td><?= (int)$emp['id'] ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($nomeCompleto !== '' ? $nomeCompleto : 'Sem nome') ?></div>
                                        <div class="text-muted small">CPF: <?= htmlspecialchars($emp['cpf'] ?? '-') ?></div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($emp['email'] ?? '-') ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($emp['celular'] ?? '-') ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= (int)$emp['total_negocios'] ?> negócio(s)</span>
                                    </td>
                                    <td>
                                        <?php if ($pendente): ?>
                                            <span class="badge text-bg-warning"><?= $statusAcesso ?></span>
                                        <?php else: ?>
                                            <span class="badge text-bg-success"><?= $statusAcesso ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($notificado): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Enviado</span>
                                            <div class="text-muted small mt-1">
                                                <?= !empty($emp['notificacao_primeiro_acesso_enviada_em']) ? date('d/m/Y H:i', strtotime($emp['notificacao_primeiro_acesso_enviada_em'])) : '-' ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Não enviado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($emp['origem_importacao'] ?? '-') ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($pendente): ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Enviar notificação para <?= htmlspecialchars(addslashes($nomeCompleto)) ?>?');">
                                                <input type="hidden" name="acao" value="enviar_individual">
                                                <input type="hidden" name="empreendedor_id" value="<?= (int)$emp['id'] ?>">
                                                <input type="hidden" name="origem_importacao" value="<?= htmlspecialchars($origemSelecionada) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-envelope me-1"></i>
                                                    <?= $notificado ? 'Reenviar' : 'Enviar' ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">Sem ação</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once $appBase . '/views/admin/footer.php'; ?>