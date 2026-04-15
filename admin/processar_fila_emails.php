<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
set_time_limit(0);

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
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}

$lote = max(1, min(200, (int)($_GET['lote'] ?? $_POST['lote'] ?? 50)));
$tipo = trim((string)($_GET['tipo'] ?? $_POST['tipo'] ?? ''));

$where = ["status = 'pendente'"];
$params = [];

if ($tipo !== '') {
    $where[] = 'tipo_envio = ?';
    $params[] = $tipo;
}

$sql = "SELECT * FROM fila_emails WHERE " . implode(' AND ', $where) . " ORDER BY id ASC LIMIT {$lote}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$itens = $stmt->fetchAll();

$processados = 0;
$enviados = 0;
$falhas = 0;
$detalhes = [];

$markProcessing = $pdo->prepare("
    UPDATE fila_emails
    SET status = 'processando',
        tentativas = tentativas + 1
    WHERE id = ?
      AND status = 'pendente'
");

$markSent = $pdo->prepare("
    UPDATE fila_emails
    SET status = 'enviado',
        erro = NULL,
        processado_em = NOW(),
        enviado_em = NOW()
    WHERE id = ?
");

$markError = $pdo->prepare("
    UPDATE fila_emails
    SET status = 'erro',
        erro = ?,
        processado_em = NOW()
    WHERE id = ?
");

$markEmp = $pdo->prepare("
    UPDATE empreendedores
    SET notificacao_primeiro_acesso_enviada = 1,
        notificacao_primeiro_acesso_enviada_em = NOW()
    WHERE id = ?
");

foreach ($itens as $item) {
    $markProcessing->execute([(int)$item['id']]);

    if ($markProcessing->rowCount() === 0) {
        continue;
    }

    $processados++;

    try {
        $nome = trim((string)($item['nome'] ?? ''));
        if ($nome === '') {
            $nome = 'Empreendedor(a)';
        }

        $assunto = (string)$item['assunto'];
        $bodyHtml = (string)$item['corpo_html'];

        $rendered = render_email_from_db($assunto, $bodyHtml, [
            'nome' => $nome,
            'email' => (string)$item['email'],
            'link_painel' => get_base_url() . '/login.php',
            'link_admin' => get_base_url() . '/admin/gerenciar_notificacoes.php',
            'ano' => date('Y'),
        ]);

        $htmlFinal = $rendered['bodyHtml'] ?? $bodyHtml;
        $subjectFinal = $rendered['subject'] ?? $assunto;

        $bodyAlt = trim((string)($item['corpo_texto'] ?? ''));
        if ($bodyAlt === '') {
            $bodyAlt = trim(strip_tags($htmlFinal));
        }

        send_mail(
            (string)$item['email'],
            $nome,
            $subjectFinal,
            $htmlFinal,
            $bodyAlt
        );

        $markSent->execute([(int)$item['id']]);
        $markEmp->execute([(int)$item['empreendedor_id']]);
        $enviados++;

    } catch (Throwable $e) {
        $falhas++;
        $erro = mb_substr($e->getMessage(), 0, 1000);
        $markError->execute([$erro, (int)$item['id']]);
        $detalhes[] = (($item['email'] ?? 'sem email') . ' — ' . $erro);
    }
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Processar fila de e-mails</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 24px;
            background: #f6f8f6;
            color: #243328;
        }
        .box {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #dfe7e1;
            border-radius: 12px;
            padding: 24px;
        }
        .ok { color: #1f6f43; }
        .err { color: #a12c2f; }
        pre {
            white-space: pre-wrap;
            background: #fafafa;
            border: 1px solid #eee;
            padding: 12px;
            border-radius: 8px;
        }
        a.btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            margin-right: 8px;
            margin-top: 8px;
        }
        .btn-primary {
            background: #1D4F3A;
            color: #fff;
        }
        .btn-secondary {
            background: #e9efea;
            color: #243328;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>Processamento da fila concluído</h1>

        <p><strong>Itens selecionados:</strong> <?= count($itens) ?></p>
        <p><strong>Processados:</strong> <?= $processados ?></p>
        <p class="ok"><strong>Enviados:</strong> <?= $enviados ?></p>
        <p class="err"><strong>Falhas:</strong> <?= $falhas ?></p>

        <?php if (!empty($tipo)): ?>
            <p><strong>Tipo filtrado:</strong> <?= htmlspecialchars($tipo) ?></p>
        <?php endif; ?>

        <?php if (!empty($detalhes)): ?>
            <h3>Detalhes das falhas</h3>
            <pre><?= htmlspecialchars(implode("\n", $detalhes), ENT_QUOTES, 'UTF-8') ?></pre>
        <?php endif; ?>

        <div>
            <a class="btn btn-primary" href="gerenciar_notificacoes.php">Voltar para notificações</a>
            <a class="btn btn-secondary" href="processar_fila_emails.php?lote=50">Processar mais 50</a>
            <a class="btn btn-secondary" href="processar_fila_emails.php?lote=100">Processar mais 100</a>
        </div>
    </div>
</body>
</html>