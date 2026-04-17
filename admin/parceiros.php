<?php
// /public_html/admin/parceiros.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../app/helpers/auth.php';
require_admin_login();

$config = require __DIR__ . '/../app/config/db.php';
$dsn    = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}";
$opts   = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $opts);
} catch (PDOException $e) {
    die('Erro na conexão com o banco: ' . $e->getMessage());
}

// Filtros
$f_nome   = $_GET['nome']   ?? '';
$f_cnpj   = $_GET['cnpj']   ?? '';
$f_status = $_GET['status'] ?? '';

$where  = [];
$params = [];

if ($f_nome !== '') {
    $where[]  = "(nome_fantasia LIKE ? OR razao_social LIKE ?)";
    $params[] = "%$f_nome%";
    $params[] = "%$f_nome%";
}
if ($f_cnpj !== '') {
    $where[]  = "cnpj LIKE ?";
    $params[] = "%$f_cnpj%";
}
if ($f_status !== '') {
    $where[]  = "status = ?";
    $params[] = $f_status;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Paginação
$limit  = 50;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM parceiros $whereSql");
$stmtCount->execute($params);
$totalRecords = (int)$stmtCount->fetchColumn();
$totalPages   = max(1, (int)ceil($totalRecords / $limit));

$sql  = "SELECT id, nome_fantasia, cnpj, rep_nome, rep_email, status, etapa_atual, criado_em, acordo_aceito
         FROM parceiros $whereSql ORDER BY criado_em DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$parceiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper de badge de status
function parceiroStatusBadge(string $s, ?int $etapa = null): string {
    $map = [
        'em_cadastro' => ['#fff3cd', '#856404'],
        'analise'     => ['rgba(149,188,204,.25)', '#3a6f82'],
        'ativo'       => ['#CDDE00', '#1E3425'],
        'inativo'     => ['#fde8ea', '#842029'],
    ];
    [$bg, $color] = $map[strtolower($s)] ?? ['#f0f0f0', '#6c757d'];
    $label = match(strtolower($s)) {
        'em_cadastro' => 'Em Cadastro' . ($etapa ? " (Etapa $etapa)" : ''),
        'analise'     => 'Em Análise',
        'ativo'       => 'Ativo',
        'inativo'     => 'Inativo',
        default       => ucfirst($s),
    };
    return "<span class=\"emp-badge\" style=\"background:$bg;color:$color;\">" . htmlspecialchars($label) . "</span>";
}

$pageTitle = 'Gestão de Parceiros';
include __DIR__ . '/../app/views/admin/header.php';
?>

<!-- ══════════════════════════════════
     Cabeçalho da página
═══════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
  <div>
    <h4 class="fw-bold mb-0" style="color:#1E3425;">Parceiros</h4>
    <small style="color:#6c8070; font-size:.82rem;">
      <?= $totalRecords ?> parceiro<?= $totalRecords !== 1 ? 's' : '' ?> encontrado<?= $totalRecords !== 1 ? 's' : '' ?>
    </small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="/admin/dashboard.php" class="hd-btn outline">
      <i class="bi bi-arrow-left"></i> Voltar
    </a>
  </div>
</div>

<!-- ══════════════════════════════════
     Filtros
═══════════════════════════════════ -->
<div class="filter-card card p-3 mb-4">
  <form method="GET" class="row g-2 align-items-end">
    <div class="col-12 col-sm-6 col-lg-4">
      <label class="form-label">Nome / Razão Social</label>
      <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" name="nome" class="form-control"
               placeholder="Buscar parceiro…" value="<?= htmlspecialchars($f_nome) ?>">
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <label class="form-label">CNPJ</label>
      <input type="text" name="cnpj" class="form-control"
             placeholder="00.000.000/0000-00" value="<?= htmlspecialchars($f_cnpj) ?>">
    </div>
    <div class="col-12 col-sm-6 col-lg-2">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="">Todos</option>
        <option value="em_cadastro" <?= $f_status === 'em_cadastro' ? 'selected' : '' ?>>Em Cadastro</option>
        <option value="analise"     <?= $f_status === 'analise'     ? 'selected' : '' ?>>Em Análise</option>
        <option value="ativo"       <?= $f_status === 'ativo'       ? 'selected' : '' ?>>Ativo</option>
        <option value="inativo"     <?= $f_status === 'inativo'     ? 'selected' : '' ?>>Inativo</option>
      </select>
    </div>
    <div class="col-12 col-sm-6 col-lg-3 d-flex gap-2">
      <button type="submit" class="hd-btn primary w-100">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
      <a href="/admin/parceiros.php" class="hd-btn outline">
        <i class="bi bi-x-lg"></i>
      </a>
    </div>
  </form>
</div>

<!-- ══════════════════════════════════
     Tabela
═══════════════════════════════════ -->
<div class="card section-card mb-4">
  <div class="table-responsive">
    <table class="emp-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Parceiro</th>
          <th>CNPJ</th>
          <th>Representante</th>
          <th>E-mail</th>
          <th>Status</th>
          <th>Acordo</th>
          <th>Cadastro</th>
          <th class="text-center">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($parceiros)): ?>
          <tr>
            <td colspan="9" class="text-center py-4" style="color:#9aab9d;">
              <i class="bi bi-handshake" style="font-size:1.8rem; opacity:.4; display:block; margin-bottom:.5rem;"></i>
              Nenhum parceiro encontrado para os filtros aplicados.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($parceiros as $i => $p): ?>
            <tr>
              <td style="color:#9aab9d; font-size:.8rem;"><?= $offset + $i + 1 ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="emp-avatar">
                    <?= strtoupper(mb_substr($p['nome_fantasia'] ?: $p['rep_nome'] ?: '?', 0, 1)) ?>
                  </div>
                  <div>
                    <div style="font-weight:600; color:#1E3425; font-size:.88rem;">
                      <?= htmlspecialchars($p['nome_fantasia'] ?: '—') ?>
                    </div>
                  </div>
                </div>
              </td>
              <td style="font-size:.84rem; color:#4a5e4f; font-family:monospace;">
                <?= htmlspecialchars($p['cnpj'] ?: '—') ?>
              </td>
              <td style="font-size:.85rem;">
                <?= htmlspecialchars($p['rep_nome'] ?: '—') ?>
              </td>
              <td style="font-size:.82rem; color:#6c8070;">
                <?= htmlspecialchars($p['rep_email'] ?: '—') ?>
              </td>
              <td>
                <?= parceiroStatusBadge($p['status'] ?? '', $p['etapa_atual'] ?? null) ?>
              </td>
              <td>
                <?php if ($p['acordo_aceito']): ?>
                  <span class="emp-badge" style="background:rgba(205,222,0,.2);color:#7a8500;">
                    <i class="bi bi-check-circle-fill me-1"></i>Aceito
                  </span>
                <?php else: ?>
                  <span class="emp-badge" style="background:#fde8ea;color:#842029;">
                    <i class="bi bi-x-circle-fill me-1"></i>Pendente
                  </span>
                <?php endif; ?>
              </td>
              <td style="font-size:.8rem; color:#9aab9d; white-space:nowrap;">
                <?= $p['criado_em'] ? date('d/m/Y', strtotime($p['criado_em'])) : '—' ?>
              </td>
              <td class="text-end text-nowrap">
                <a href="visualizar_parceiro.php?id=<?= (int)$p['id'] ?>"
                  class="btn btn-sm btn-outline-secondary"
                  title="Ver cadastro completo">
                    <i class="bi bi-eye"></i>
                </a>

                <?php if ((int)($p['acordo_aceito'] ?? 0) === 1): ?>
                    <a href="visualizar_carta_parceiro.php?id=<?= (int)$p['id'] ?>"
                      class="btn btn-sm btn-outline-success ms-1"
                      title="Ver Carta-Acordo">
                        <i class="bi bi-file-earmark-text"></i>
                    </a>

                    <button type="button"
                            class="btn btn-sm btn-outline-primary ms-1"
                            title="Alterar status"
                            onclick="openStatusModal(<?= (int)$p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nome_fantasia'] ?: $p['razao_social'] ?: 'Parceiro')) ?>', '<?= htmlspecialchars($p['status'] ?? '') ?>')">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                <?php else: ?>
                  <button type="button"
                          class="btn btn-sm btn-outline-warning ms-1"
                          title="Lembrar de assinar a carta-acordo"
                          onclick="abrirModalLembrete(<?= (int)$p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nome_fantasia'] ?: $p['razao_social'] ?: 'Parceiro')) ?>', '<?= htmlspecialchars(addslashes($p['rep_nome'] ?: '')) ?>', '<?= htmlspecialchars(addslashes($p['rep_email'] ?: '')) ?>')">
                      <i class="bi bi-envelope-exclamation"></i>
                  </button>
                  <button type="button"
                          class="btn btn-sm btn-outline-secondary ms-1"
                          title="Só é possível alterar o status após a assinatura da carta-acordo"
                          disabled>
                      <i class="bi bi-lock"></i>
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ══════════════════════════════════
     Paginação
═══════════════════════════════════ -->
<?php if ($totalPages > 1): ?>
<nav class="d-flex justify-content-center mb-4">
  <ul class="pagination ip-pagination">
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
        <i class="bi bi-chevron-left"></i>
      </a>
    </li>
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
      <li class="page-item <?= $i === $page ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
          <?= $i ?>
        </a>
      </li>
    <?php endfor; ?>
    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
      <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
        <i class="bi bi-chevron-right"></i>
      </a>
    </li>
  </ul>
</nav>
<?php endif; ?>

<!-- MODAL LEMBRETE CARTA-ACORDO -->
<div class="modal fade" id="modalLembrete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none;">
            <form action="processar_lembrete_parceiro.php" method="POST">
                <input type="hidden" name="parceiro_id" id="lembrete_parceiro_id">

                <div class="modal-header" style="border-bottom:1px solid #f0f4ed;">
                    <h5 class="modal-title" style="color:#1E3425;">
                        <i class="bi bi-envelope-exclamation me-2" style="color:#CDDE00;"></i>
                        Enviar Lembrete — Carta-Acordo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- Info do parceiro -->
                    <div class="p-3 rounded mb-4" style="background:#f7f9f5; border:1px solid #e6ece1;">
                        <div class="small fw-semibold mb-1" style="color:#1E3425;">
                            <i class="bi bi-building me-1"></i> Parceiro
                        </div>
                        <div class="fw-bold" id="lembrete_nome_parceiro" style="color:#1E3425;"></div>
                        <div class="small text-muted mt-1">
                            <i class="bi bi-person me-1"></i>
                            <span id="lembrete_rep_nome"></span> —
                            <span id="lembrete_rep_email"></span>
                        </div>
                    </div>

                    <!-- Aviso -->
                    <div class="p-3 rounded mb-3" style="background:#fff8e1; border-left:4px solid #f59e0b;">
                        <p class="small mb-0" style="color:#856404;">
                            <i class="bi bi-info-circle me-1"></i>
                            Um e-mail será enviado ao representante lembrando-o de assinar a
                            <strong>carta-acordo</strong> e finalizar o cadastro para que a parceria
                            seja formalizada.
                        </p>
                    </div>

                    <!-- Mensagem personalizada opcional -->
                    <div class="mb-1">
                        <label class="form-label fw-semibold" style="font-size:.88rem; color:#1E3425;">
                            Mensagem adicional <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <textarea name="mensagem_extra" class="form-control" rows="3"
                                  maxlength="500"
                                  placeholder="Ex: Estamos aguardando sua assinatura para darmos início às ações previstas…"></textarea>
                        <div class="form-text">Máx. 500 caracteres. Será inserida no corpo do e-mail.</div>
                    </div>

                </div>

                <div class="modal-footer" style="border-top:1px solid #f0f4ed;">
                    <button type="button" class="btn-emp-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="hd-btn primary">
                        <i class="bi bi-send me-1"></i> Enviar lembrete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL STATUS PARCEIRO -->

<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="processar_status_parceiro.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Alterar Status do Parceiro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="parceiro_id" id="modal_parceiro_id">

                    <p class="mb-3">
                        Alterando status de <strong id="modal_parceiro_nome">Parceiro</strong>
                    </p>

                    <div class="mb-3">
                        <label for="modal_novo_status" class="form-label">Novo status</label>
                        <select class="form-select" name="novo_status" id="modal_novo_status" required>
                            <option value="">Selecione</option>
                            <option value="analise">Em Análise</option>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>

                    <div class="small text-muted">
                        O status só pode ser alterado para parceiros com carta-acordo assinada.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>

<script>
function abrirModalLembrete(id, nome, repNome, repEmail) {
  document.getElementById('lembrete_parceiro_id').value   = id;
  document.getElementById('lembrete_nome_parceiro').textContent = nome;
  document.getElementById('lembrete_rep_nome').textContent  = repNome  || '—';
  document.getElementById('lembrete_rep_email').textContent = repEmail || '—';
  new bootstrap.Modal(document.getElementById('modalLembrete')).show();
}
function openStatusModal(id, nome, statusAtual = '') {
    document.getElementById('modal_parceiro_id').value = id;
    document.getElementById('modal_parceiro_nome').textContent = nome || 'Parceiro';

    const select = document.getElementById('modal_novo_status');
    if (select) {
        select.value = statusAtual || '';
    }

    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}
</script>