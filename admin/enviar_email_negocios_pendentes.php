<?php
// /public_html/admin/enviar_email_negocios_pendentes.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/mail.php';
require_once __DIR__ . '/../app/helpers/render.php';

require_admin_login();

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$etapas_nomes = [
    1 => 'Dados do Negócio',  2 => 'Fundadores',
    3 => 'Eixo Temático',     4 => 'ODS',
    5 => 'Apresentação',      6 => 'Financeiro',
    7 => 'Impacto',           8 => 'Visão de Futuro',
    9 => 'Documentação',      10 => 'Revisão Final',
];

// Filtro de etapa
$filtro_etapa = $_GET['etapa'] ?? $_POST['etapa_filtro'] ?? '';

$where  = ["n.inscricao_completa = 0"];
$params = [];
if ($filtro_etapa !== '' && is_numeric($filtro_etapa)) {
    $where[]  = "n.etapa_atual = ?";
    $params[] = (int)$filtro_etapa;
}

$sql = "SELECT e.nome, e.email, n.nome_fantasia, n.etapa_atual
        FROM negocios n
        JOIN empreendedores e ON n.empreendedor_id = e.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY n.etapa_atual ASC, n.nome_fantasia ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contagem por etapa para o selector
$stmtEtapas = $pdo->query("
    SELECT etapa_atual, COUNT(*) as total
    FROM negocios
    WHERE inscricao_completa = 0
    GROUP BY etapa_atual
    ORDER BY etapa_atual ASC
");
$totais_por_etapa = $stmtEtapas->fetchAll(PDO::FETCH_KEY_PAIR);

// Template
$stmtTpl = $pdo->prepare("SELECT subject, body_html FROM email_templates WHERE slug = 'negocios_pendentes'");
$stmtTpl->execute();
$template = $stmtTpl->fetch();

$msg   = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    $subject  = trim($_POST['subject']   ?? $template['subject']   ?? '');
    $bodyHtml = trim($_POST['body_html'] ?? $template['body_html'] ?? '');

    if (empty($pendentes)) {
        $error = 'Nenhum destinatário encontrado para o filtro selecionado.';
    } else {
        $enviados = 0;
        foreach ($pendentes as $p) {
            $etapa_nome = $etapas_nomes[(int)$p['etapa_atual']] ?? "Etapa {$p['etapa_atual']}";

            $rendered = render_email_from_db($subject, $bodyHtml, [
                'nome'          => $p['nome'],
                'nome_fantasia' => $p['nome_fantasia'],
                'etapa_atual'   => $p['etapa_atual'],
                'etapa_nome'    => $etapa_nome,
                'link_cadastro' => get_base_url() . '/empreendedores/meus-negocios.php',
                'ano'           => date('Y'),
            ]);

            $bodyAlt = strip_tags($rendered['bodyHtml']);

            if (send_mail($p['email'], $p['nome'], $rendered['subject'], $rendered['bodyHtml'], $bodyAlt)) {
                $enviados++;
            }
        }

        $msg = "✅ {$enviados} e-mail(s) enviado(s) com sucesso" .
               ($filtro_etapa !== '' ? " para negócios na Etapa {$filtro_etapa}" : " para todos os pendentes") . ".";
    }
}

$pageTitle = 'Notificar Negócios Pendentes';
include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0" style="color:#1E3425;">Notificar Negócios Pendentes</h4>
    <small style="color:#6c8070;">Envie lembretes por etapa ou para todos os cadastros incompletos</small>
  </div>
  <a href="/admin/negocios.php" class="hd-btn outline">
    <i class="bi bi-arrow-left"></i> Voltar
  </a>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row g-4">

  <!-- Coluna principal: formulário -->
  <div class="col-12 col-lg-8">
    <div class="card section-card p-4 mb-4">

      <!-- Filtro por etapa -->
      <form method="GET" class="mb-4">
        <label class="form-label fw-semibold" style="color:#1E3425;">
          <i class="bi bi-funnel me-1"></i> Filtrar destinatários por etapa
        </label>
        <div class="d-flex gap-2 flex-wrap">
          <select name="etapa" class="form-select" style="max-width:280px;">
            <option value="">Todos os pendentes (<?= array_sum($totais_por_etapa) ?>)</option>
            <?php foreach ($etapas_nomes as $num => $nome): ?>
              <?php $total = $totais_por_etapa[$num] ?? 0; if ($total === 0) continue; ?>
              <option value="<?= $num ?>" <?= (string)$filtro_etapa === (string)$num ? 'selected' : '' ?>>
                Etapa <?= $num ?>: <?= $nome ?> (<?= $total ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="hd-btn outline">
            <i class="bi bi-filter"></i> Aplicar
          </button>
          <?php if ($filtro_etapa !== ''): ?>
            <a href="/admin/enviar_email_negocios_pendentes.php" class="hd-btn outline">
              <i class="bi bi-x-lg"></i> Limpar
            </a>
          <?php endif; ?>
        </div>
      </form>

      <!-- Destinatários encontrados -->
      <?php if (!empty($pendentes)): ?>
        <div class="p-3 rounded mb-4" style="background:#f7f9f5; border:1px solid #e6ece1;">
          <div class="fw-semibold mb-2" style="color:#1E3425; font-size:.9rem;">
            <i class="bi bi-people me-1"></i>
            <?= count($pendentes) ?> destinatário(s) selecionado(s)
            <?= $filtro_etapa !== '' ? "— Etapa {$filtro_etapa}: " . ($etapas_nomes[(int)$filtro_etapa] ?? '') : '— Todos os pendentes' ?>
          </div>
          <div style="max-height:140px; overflow-y:auto;">
            <?php foreach ($pendentes as $p): ?>
              <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size:.82rem;">
                <span style="color:#4a5e4f;">
                  <i class="bi bi-person me-1"></i><?= htmlspecialchars($p['nome']) ?>
                  — <em><?= htmlspecialchars($p['nome_fantasia']) ?></em>
                </span>
                <span class="text-muted"><?= htmlspecialchars($p['email']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="alert alert-info">
          <i class="bi bi-info-circle me-2"></i>
          Nenhum negócio pendente encontrado para o filtro selecionado.
        </div>
      <?php endif; ?>

      <!-- Formulário de envio -->
      <?php if (!empty($pendentes) && $template): ?>
        <form method="POST">
          <input type="hidden" name="etapa_filtro" value="<?= htmlspecialchars($filtro_etapa) ?>">

          <div class="mb-3">
            <label class="form-label fw-semibold">Assunto</label>
            <input type="text" name="subject" class="form-control"
                   value="<?= htmlspecialchars($template['subject']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Mensagem</label>
            <textarea name="body_html" class="form-control wysiwyg"
                      rows="10"><?= htmlspecialchars($template['body_html']) ?></textarea>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button type="submit" name="enviar" value="1" class="hd-btn primary"
                    onclick="return confirm('Confirma o envio para <?= count($pendentes) ?> destinatário(s)?')">
              <i class="bi bi-send me-1"></i> Enviar para <?= count($pendentes) ?> negócio(s)
            </button>
          </div>
        </form>
      <?php elseif (!$template): ?>
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Template <code>negocios_pendentes</code> não encontrado no banco de dados.
        </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- Coluna lateral: variáveis + resumo por etapa -->
  <div class="col-12 col-lg-4">

    <!-- Resumo por etapa -->
    <div class="card section-card p-4 mb-3">
      <div class="fw-semibold mb-3" style="color:#1E3425;">
        <i class="bi bi-bar-chart me-1"></i> Pendentes por Etapa
      </div>
      <?php foreach ($etapas_nomes as $num => $nome): ?>
        <?php $total = $totais_por_etapa[$num] ?? 0; if ($total === 0) continue; ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <a href="?etapa=<?= $num ?>" class="neg-sort-link" style="font-size:.84rem;">
            Etapa <?= $num ?>: <?= $nome ?>
          </a>
          <span class="emp-badge" style="background:<?= $num <= 3 ? '#fde8ea' : ($num <= 6 ? '#fff3cd' : 'rgba(205,222,0,.2)') ?>;
                                        color:<?= $num <= 3 ? '#842029' : ($num <= 6 ? '#856404' : '#7a8500') ?>;">
            <?= $total ?>
          </span>
        </div>
      <?php endforeach; ?>
      <?php if (empty($totais_por_etapa)): ?>
        <p class="text-muted small mb-0">Nenhum negócio pendente.</p>
      <?php endif; ?>
    </div>

    <!-- Variáveis disponíveis -->
    <div class="card section-card p-4">
      <div class="fw-semibold mb-3" style="color:#1E3425;">
        <i class="bi bi-code-slash me-1"></i> Variáveis do Template
      </div>
      <ul class="list-unstyled mb-0" style="font-size:.82rem;">
        <li class="mb-2"><code>{{nome}}</code> — Nome do empreendedor</li>
        <li class="mb-2"><code>{{nome_fantasia}}</code> — Nome do negócio</li>
        <li class="mb-2"><code>{{etapa_atual}}</code> — Número da etapa</li>
        <li class="mb-2"><code>{{etapa_nome}}</code> — Nome da etapa</li>
        <li class="mb-2"><code>{{link_cadastro}}</code> — Link para meus negócios</li>
        <li class="mb-0"><code>{{ano}}</code> — Ano atual</li>
      </ul>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>