<?php
// /public_html/empreendedores/minhas_inscricoes_premiacao.php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$pageTitle = 'Minhas Inscrições na Premiação — Impactos Positivos';

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function labelStatusPremiacao(?string $status): string
{
    return match ($status) {
        'rascunho' => 'Rascunho',
        'enviada' => 'Inscrição enviada',
        'em_triagem' => 'Em triagem',
        'elegivel' => 'Elegível',
        'inelegivel' => 'Inelegível',
        'classificada_fase_1' => 'Classificada Fase 1',
        'classificada_fase_2' => 'Classificada Fase 2',
        'finalista' => 'Finalista',
        'vencedora' => 'Vencedora',
        'eliminada' => 'Eliminada',
        default => 'Não informado',
    };
}

function badgePremiacao(?string $status): array
{
    return match ($status) {
        'enviada' => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'icon' => 'bi-send-check'],
        'em_triagem' => ['bg' => '#fff8e1', 'color' => '#f57f17', 'icon' => 'bi-hourglass-split'],
        'elegivel' => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => 'bi-check-circle-fill'],
        'inelegivel' => ['bg' => '#fdecea', 'color' => '#c62828', 'icon' => 'bi-x-circle-fill'],
        'classificada_fase_1' => ['bg' => '#e0f7fa', 'color' => '#006064', 'icon' => 'bi-1-circle-fill'],
        'classificada_fase_2' => ['bg' => '#e0f2f1', 'color' => '#00695c', 'icon' => 'bi-2-circle-fill'],
        'finalista' => ['bg' => '#ede7f6', 'color' => '#5e35b1', 'icon' => 'bi-award-fill'],
        'vencedora' => ['bg' => '#fff3cd', 'color' => '#856404', 'icon' => 'bi-trophy-fill'],
        'eliminada' => ['bg' => '#fdecea', 'color' => '#c62828', 'icon' => 'bi-slash-circle-fill'],
        'rascunho' => ['bg' => '#f5f5f5', 'color' => '#757575', 'icon' => 'bi-pencil-square'],
        default => ['bg' => '#f5f5f5', 'color' => '#757575', 'icon' => 'bi-info-circle'],
    };
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
        n.publicado_vitrine,
        a.logo_negocio,
        a.imagem_destaque
    FROM premiacao_inscricoes pi
    INNER JOIN premiacoes p ON p.id = pi.premiacao_id
    INNER JOIN negocios n ON n.id = pi.negocio_id
    LEFT JOIN negocio_apresentacao a ON a.negocio_id = n.id
    WHERE pi.empreendedor_id = ?
    ORDER BY p.ano DESC, pi.created_at DESC, pi.id DESC
");
$stmt->execute([$_SESSION['user_id']]);
$inscricoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h1 class="emp-page-title mb-1"><i class="bi bi-trophy me-2"></i>Minhas Inscrições na Premiação</h1>
    <p class="emp-page-subtitle mb-0">Acompanhe o andamento dos negócios inscritos nas edições da premiação.</p>
  </div>
  <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline">
    <i class="bi bi-briefcase me-1"></i> Voltar para Meus Negócios
  </a>
</div>

<?php if (empty($inscricoes)): ?>

  <div class="emp-card text-center py-5">
    <i class="bi bi-trophy" style="font-size:3rem; color:#c8d4c0;"></i>
    <h5 class="mt-3 mb-1" style="color:#1E3425;">Nenhuma inscrição encontrada</h5>
    <p class="text-muted small mb-4">
      Você ainda não possui negócios inscritos em nenhuma edição da premiação.
    </p>
    <a href="/empreendedores/meus-negocios.php" class="btn-emp-primary">
      <i class="bi bi-arrow-left me-1"></i> Ir para Meus Negócios
    </a>
  </div>

<?php else: ?>

  <div class="row g-4">
    <?php foreach ($inscricoes as $item): ?>
      <?php $badge = badgePremiacao($item['status'] ?? null); ?>

      <div class="col-12 col-md-6 col-xl-4">
        <div class="emp-negocio-card">

          <div class="emp-negocio-capa">
            <?php if (!empty($item['imagem_destaque'])): ?>
              <img src="<?= htmlspecialchars($item['imagem_destaque']) ?>" alt="Capa do negócio">
            <?php elseif (!empty($item['logo_negocio'])): ?>
              <img src="<?= htmlspecialchars($item['logo_negocio']) ?>"
                   alt="Logo do negócio"
                   style="object-fit:contain; padding:1rem; background:#f0f4ed;">
            <?php else: ?>
              <div class="emp-negocio-capa-placeholder">
                <i class="bi bi-trophy"></i>
              </div>
            <?php endif; ?>

            <span class="emp-negocio-vitrine-badge"
                  style="background:<?= $badge['bg'] ?>; color:<?= $badge['color'] ?>;">
              <i class="bi <?= $badge['icon'] ?> me-1"></i><?= htmlspecialchars(labelStatusPremiacao($item['status'] ?? null)) ?>
            </span>
          </div>

          <div class="emp-negocio-body">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
              <h5 class="emp-negocio-nome"><?= htmlspecialchars($item['nome_fantasia']) ?></h5>
              <span class="emp-badge-ativo flex-shrink-0">
                <?= (int)$item['premiacao_ano'] ?>
              </span>
            </div>

            <p class="emp-negocio-categoria mb-2">
              <i class="bi bi-tag me-1"></i><?= htmlspecialchars($item['categoria'] ?? '—') ?>
            </p>

            <div class="small mb-2" style="color:#1E3425;">
              <strong>Edição:</strong> <?= htmlspecialchars($item['premiacao_nome']) ?>
            </div>

            <div class="small mb-2" style="color:#6c8070;">
              <strong>Status da edição:</strong> <?= htmlspecialchars($item['premiacao_status']) ?>
            </div>

            <?php if (!empty($item['enviado_em'])): ?>
              <div class="small mb-2 text-muted">
                <strong>Enviado em:</strong> <?= date('d/m/Y H:i', strtotime($item['enviado_em'])) ?>
              </div>
            <?php endif; ?>

            <div class="small mb-3 text-muted">
              <strong>Aceites:</strong>
              <?= (int)$item['aceite_regulamento'] === 1 ? 'Regulamento ok' : 'Regulamento pendente' ?> ·
              <?= (int)$item['aceite_veracidade'] === 1 ? 'Veracidade ok' : 'Veracidade pendente' ?>
            </div>

            <?php if (!empty($item['observacoes_admin'])): ?>
              <div class="p-3 rounded mb-3" style="background:#f7f9f5; border:1px solid #e6ece1;">
                <div class="small fw-semibold mb-1" style="color:#1E3425;">
                  <i class="bi bi-chat-left-text me-1"></i> Observações da equipe
                </div>
                <div class="small text-muted">
                  <?= nl2br(htmlspecialchars($item['observacoes_admin'])) ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="emp-negocio-acoes">
              <a href="/negocios/confirmacao.php?id=<?= (int)$item['negocio_id'] ?>" class="btn-emp-outline flex-1">
                <i class="bi bi-card-checklist me-1"></i> Ver Negócio
              </a>

              <?php if ((int)$item['publicado_vitrine'] === 1): ?>
                <a href="/negocio.php?id=<?= (int)$item['negocio_id'] ?>" target="_blank" class="btn-emp-primary flex-1">
                  <i class="bi bi-eye me-1"></i> Ver na Vitrine
                </a>
              <?php else: ?>
                <a href="/empreendedores/meus-negocios.php" class="btn-emp-primary flex-1">
                  <i class="bi bi-pencil-square me-1"></i> Gerenciar
                </a>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>