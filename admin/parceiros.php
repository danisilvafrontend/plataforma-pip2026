<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Filtros
$f_nome = $_GET['nome'] ?? '';
$f_cnpj = $_GET['cnpj'] ?? '';
$f_status = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($f_nome !== '') {
    $where[] = "(nome_fantasia LIKE ? OR razao_social LIKE ?)";
    $params[] = "%$f_nome%";
    $params[] = "%$f_nome%";
}
if ($f_cnpj !== '') {
    $where[] = "cnpj LIKE ?";
    $params[] = "%$f_cnpj%";
}
if ($f_status !== '') {
    $where[] = "status = ?";
    $params[] = $f_status;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = "WHERE " . implode(' AND ', $where);
}

// Paginação
$limit = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Total para paginação
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM parceiros $whereSql");
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Busca final
$sql = "SELECT id, nome_fantasia, cnpj, rep_nome, rep_email, status, etapa_atual, criado_em 
        FROM parceiros $whereSql 
        ORDER BY criado_em DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$parceiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mapeamento de Status
$status_badges = [
    'em_cadastro' => '<span class="badge bg-secondary">Em Cadastro (Etapa %s)</span>',
    'analise' => '<span class="badge bg-warning text-dark">Em Análise</span>',
    'ativo' => '<span class="badge bg-success">Ativo</span>',
    'inativo' => '<span class="badge bg-danger">Inativo</span>'
];

$pageTitle = 'Gestão de Parceiros';
include __DIR__ . '/../app/views/admin/header.php';
?>

<!-- Modal para mudar status via AJAX ou formulário -->
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
            <p>Alterando status de: <strong id="modal_parceiro_nome"></strong></p>
            
            <div class="mb-3">
                <label class="form-label">Novo Status</label>
                <select class="form-select" name="novo_status" required>
                    <option value="analise">Em Análise (Aguardando)</option>
                    <option value="ativo">Aprovar Parceiro (Ativo)</option>
                    <option value="inativo">Inativar Parceiro</option>
                </select>
                <div class="form-text mt-2 text-muted">
                    <i class="bi bi-info-circle"></i> Ao marcar como <strong>Ativo</strong>, o parceiro receberá acesso às funcionalidades e à Rede de Impacto no painel dele.
                </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar Status</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="card shadow-sm mb-4 border-0">
  <div class="card-body">
    <form method="GET" class="row g-3">
      <div class="col-md-4">
        <label class="form-label text-muted small">Buscar por Nome / Razão</label>
        <input type="text" name="nome" class="form-control form-control-sm" value="<?=htmlspecialchars($f_nome)?>" placeholder=\"Ex: Instituto XYZ\">
      </div>
      <div class="col-md-3">
        <label class="form-label text-muted small">Buscar por CNPJ</label>
        <input type="text" name="cnpj" class="form-control form-control-sm" value="<?=htmlspecialchars($f_cnpj)?>">
      </div>
      <div class="col-md-3">
        <label class="form-label text-muted small">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Todos</option>
          <option value="em_cadastro" <?= $f_status === 'em_cadastro' ? 'selected' : '' ?>>Em Cadastro</option>
          <option value="analise" <?= $f_status === 'analise' ? 'selected' : '' ?>>Em Análise</option>
          <option value="ativo" <?= $f_status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
          <option value="inativo" <?= $f_status === 'inativo' ? 'selected' : '' ?>>Inativo</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i> Filtrar</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Parceiro</th>
          <th>CNPJ</th>
          <th>Representante</th>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($parceiros)): ?>
            <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum parceiro encontrado.</td></tr>
        <?php else: ?>
            <?php foreach($parceiros as $p): ?>
                <?php 
                $badge = $status_badges[$p['status']] ?? '';
                if ($p['status'] === 'em_cadastro') {
                    $badge = sprintf($badge, $p['etapa_atual']);
                }
                ?>
                <tr>
                    <td class="text-muted small">#<?= $p['id'] ?></td>
                    <td class="fw-bold text-primary"><?= htmlspecialchars($p['nome_fantasia']) ?></td>
                    <td><?= htmlspecialchars($p['cnpj']) ?></td>
                    <td>
                        <?= htmlspecialchars($p['rep_nome']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($p['rep_email']) ?></small>
                    </td>
                    <td><?= $badge ?></td>
                    <td class="text-end">
                        
                        <!-- Botão de ver Contrato / Visualizar -->
                        <a href="visualizar_parceiro.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver Cadastro Completo">
                            <i class="bi bi-eye"></i>
                        </a>
                        
                        <!-- Botão de Alterar Status -->
                        <button type="button" class="btn btn-sm btn-outline-primary ms-1" 
                                onclick="openStatusModal(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nome_fantasia'])) ?>')" 
                                title="Aprovar/Alterar Status">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <?php if ($totalPages > 1): ?>
  <div class="card-footer bg-white py-3">
    <nav>
      <ul class="pagination pagination-sm justify-content-center mb-0">
        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&nome=<?= urlencode($f_nome) ?>&cnpj=<?= urlencode($f_cnpj) ?>&status=<?= urlencode($f_status) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>

<!-- Scripts locais -->
<script>
function openStatusModal(id, nome) {
    document.getElementById('modal_parceiro_id').value = id;
    document.getElementById('modal_parceiro_nome').innerText = nome;
    var myModal = new bootstrap.Modal(document.getElementById('statusModal'));
    myModal.show();
}
</script>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>
