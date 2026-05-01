<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$appBase = dirname(__DIR__);
$config = require $appBase . '/app/config/db.php';

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;port=%s;charset=%s',
    $config['host'],
    $config['dbname'],
    $config['port'],
    $config['charset']
);

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Erro ao conectar ao banco de dados.');
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizarStatusPremiacao(?string $status): ?string
{
    $status = trim((string)$status);

    return match ($status) {
        'em_triagem' => 'emtriagem',
        'classificada_fase_1' => 'classificadafase1',
        'classificada_fase_2' => 'classificadafase2',
        default => $status !== '' ? $status : null,
    };
}

function labelStatusPremiacao(?string $status): string
{
    $status = normalizarStatusPremiacao($status);

    return match ($status) {
        'rascunho' => 'Rascunho',
        'enviada' => 'Inscrição enviada',
        'emtriagem' => 'Em triagem',
        'elegivel' => 'Elegível',
        'inelegivel' => 'Inelegível',
        'classificadafase1' => 'Classificada Fase 1',
        'classificadafase2' => 'Classificada Fase 2',
        'finalista' => 'Finalista',
        'vencedora' => 'Vencedora',
        'eliminada' => 'Eliminada',
        default => 'Não informado',
    };
}

function badgePremiacao(?string $status): array
{
    $status = normalizarStatusPremiacao($status);

    return match ($status) {
        'enviada' => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'icon' => 'bi-send-check'],
        'emtriagem' => ['bg' => '#fff8e1', 'color' => '#f57f17', 'icon' => 'bi-hourglass-split'],
        'elegivel' => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => 'bi-check-circle-fill'],
        'inelegivel' => ['bg' => '#fdecea', 'color' => '#c62828', 'icon' => 'bi-x-circle-fill'],
        'classificadafase1' => ['bg' => '#e0f7fa', 'color' => '#006064', 'icon' => 'bi-1-circle-fill'],
        'classificadafase2' => ['bg' => '#e0f2f1', 'color' => '#00695c', 'icon' => 'bi-2-circle-fill'],
        'finalista' => ['bg' => '#ede7f6', 'color' => '#5e35b1', 'icon' => 'bi-award-fill'],
        'vencedora' => ['bg' => '#fff3cd', 'color' => '#856404', 'icon' => 'bi-trophy-fill'],
        'eliminada' => ['bg' => '#fdecea', 'color' => '#c62828', 'icon' => 'bi-slash-circle-fill'],
        'rascunho' => ['bg' => '#f5f5f5', 'color' => '#757575', 'icon' => 'bi-pencil-square'],
        default => ['bg' => '#f5f5f5', 'color' => '#757575', 'icon' => 'bi-info-circle'],
    };
}

function dataHoraBr(?string $value): string
{
    if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return '—';
    }

    $ts = strtotime($value);
    if (!$ts) {
        return '—';
    }

    return date('d/m/Y H:i', $ts);
}

$stmt = $pdo->prepare("
    SELECT
        pi.id,
        pi.premiacao_id,
        pi.negocio_id,
        pi.categoria,
        pi.status,
        pi.aceite_regulamento,
        pi.aceite_veracidade,
        pi.deseja_participar,
        pi.observacoes_admin,
        pi.enviado_em,
        pi.created_at,
        p.nome AS premiacao_nome,
        p.ano AS premiacao_ano,
        p.status AS premiacao_status,
        n.nome_fantasia,
        n.status_vitrine,
        n.publicado_vitrine
    FROM premiacao_inscricoes pi
    INNER JOIN premiacoes p
        ON p.id = pi.premiacao_id
    INNER JOIN negocios n
        ON n.id = pi.negocio_id
    WHERE pi.empreendedor_id = ?
    ORDER BY p.ano DESC, pi.created_at DESC, pi.id DESC
");
$stmt->execute([$_SESSION['user_id']]);
$inscricoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container py-4">
  <div class="premiacoes-page-header">
    <h1 class="premiacoes-page-title">Minhas inscrições na premiação</h1>
    <p class="premiacoes-page-subtitle">
      Acompanhe o andamento dos negócios inscritos nas edições da premiação.
    </p>
  </div>

  <?php if (empty($inscricoes)): ?>
    <div class="premiacoes-empty">
      <div class="premiacoes-empty-icon">
        <i class="bi bi-trophy"></i>
      </div>
      <div class="premiacoes-empty-title">Nenhuma inscrição encontrada</div>
      <div class="premiacoes-empty-text">
        Você ainda não possui negócios inscritos em nenhuma edição da premiação.
      </div>
      <a href="/empreendedores/meus-negocios.php" class="btn-emp-primary">
        <i class="bi bi-briefcase me-1"></i> Ir para Meus Negócios
      </a>
    </div>
  <?php else: ?>
    <div class="premiacoes-list-head">
      <div>Negócio</div>
      <div>Premiação</div>
      <div>Status</div>
      <div>Envio</div>
      <div>Detalhes</div>
    </div>

    <div class="premiacoes-list">
      <?php foreach ($inscricoes as $item): ?>
        <?php
          $statusNormalizado = normalizarStatusPremiacao($item['status'] ?? null);
          $badge = badgePremiacao($statusNormalizado);
        ?>
        <div class="premiacao-row-card">
          <div class="premiacao-row-grid">
            <div>
              <div class="premiacao-col-label">Negócio</div>
              <div class="premiacao-negocio-nome">
                <?= h($item['nome_fantasia']) ?>
              </div>
              <div class="premiacao-meta">
                <strong>Categoria:</strong> <?= h($item['categoria'] ?: '—') ?><br>
                <strong>ID da inscrição:</strong> #<?= (int)$item['id'] ?>
              </div>
            </div>

            <div>
              <div class="premiacao-col-label">Premiação</div>
              <div class="premiacao-edicao">
                <?= h($item['premiacao_nome']) ?>
                <?= !empty($item['premiacao_ano']) ? ' ' . h((string)$item['premiacao_ano']) : '' ?>
              </div>
              <div class="premiacao-meta">
                <strong>Edição:</strong> <?= h((string)($item['premiacao_ano'] ?: '—')) ?>
              </div>
            </div>

            <div>
              <div class="premiacao-col-label">Status</div>
              <span class="premiacao-badge"
                    style="background: <?= h($badge['bg']) ?>; color: <?= h($badge['color']) ?>;">
                <i class="bi <?= h($badge['icon']) ?>"></i>
                <?= h(labelStatusPremiacao($statusNormalizado)) ?>
              </span>
            </div>

            <div>
              <div class="premiacao-col-label">Envio</div>
              <div class="premiacao-info-stack">
                <div class="premiacao-meta">
                  <strong>Enviado em:</strong><br>
                  <?= h(dataHoraBr($item['enviado_em'])) ?>
                </div>
                <div class="premiacao-meta">
                  <strong>Registro:</strong><br>
                  <?= h(dataHoraBr($item['created_at'])) ?>
                </div>
              </div>
            </div>

            <div>
              <div class="premiacao-col-label">Detalhes</div>
              <div class="premiacao-info-stack">
                <div class="premiacao-meta">
                  <strong>Regulamento:</strong>
                  <?= (int)$item['aceite_regulamento'] === 1 ? 'Aceito' : 'Não aceito' ?>
                </div>
                <div class="premiacao-meta">
                  <strong>Veracidade:</strong>
                  <?= (int)$item['aceite_veracidade'] === 1 ? 'Confirmada' : 'Não confirmada' ?>
                </div>
                <div class="premiacao-meta">
                  <strong>Participação:</strong>
                  <?= (int)$item['deseja_participar'] === 1 ? 'Confirmada' : 'Rascunho' ?>
                </div>
              </div>
            </div>
          </div>

          <?php if (!empty($item['observacoes_admin'])): ?>
            <div class="premiacao-observacao">
              <strong>Observações da equipe:</strong>
              <?= nl2br(h($item['observacoes_admin'])) ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>