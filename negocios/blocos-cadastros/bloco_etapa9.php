<?php
// bloco_etapa9.php - Visualização da Etapa 9 (Documentação)
// Espera: $negocio, $negocio_id, $docs (array de negocio_documentos)

if (!isset($negocio) || !isset($negocio_id)) return;

// Se $docs vier como false do fetch, normaliza para []
$docs = is_array($docs) ? $docs : [];
$nomeTrab = !empty($docs['certidao_trabalhista_path']) 
    ? basename(parse_url($docs['certidao_trabalhista_path'], PHP_URL_PATH))
    : 'Não enviado';
$nomeAmb = !empty($docs['certidao_ambiental_path']) 
    ? basename(parse_url($docs['certidao_ambiental_path'], PHP_URL_PATH))
    : 'Não enviado';
?>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>
      <i class="bi bi-file-earmark-lock-fill me-1"></i> 
      Documentação Legal - Etapa 9 
      <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
    </strong>
    <?php 
        $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
        $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;
        
        if (!$ehAdmin && !$somenteLeitura && !empty($docs)): 
    ?>
        <a href="/negocios/editar_etapa9.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" 
           class="btn btn-sm btn-outline-primary">
           <i class="bi bi-pencil"></i> Editar
        </a>
    <?php endif; ?>
  </div>

  <div class="card-body">
    <?php if (empty($docs['certidao_trabalhista_path']) && empty($docs['certidao_ambiental_path'])): ?>
      <div class="alert alert-warning text-center">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-4 text-warning"></i>
        <strong>Documentação pendente</strong><br>
        Certidões trabalhista e ambiental ainda não foram enviadas.
      </div>
    <?php else: ?>
      <div class="row g-4">
        
        <!-- Certidão Trabalhista -->
        <div class="col-md-6">
          <div class="d-flex align-items-start mb-3">
            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
              <i class="bi bi-briefcase-fill fs-6"></i>
            </div>
            <div>
              <h6 class="fw-bold mb-1">Certidão Negativa de Débitos Trabalhistas</h6>
              <div class="badge bg-success mb-1">CNDT - Enviada</div>
              <small class="text-muted">
                <?= date('d/m/Y H:i', strtotime($docs['data_envio'] ?? 'now')) ?>
              </small>
            </div>
          </div>
          <div class="mb-2">
            <a href="<?= htmlspecialchars($docs['certidao_trabalhista_path']) ?>" 
               target="_blank" 
               class="btn btn-sm btn-outline-success d-inline-flex align-items-center">
              <i class="bi bi-eye me-1"></i>
              Visualizar PDF
            </a>
            <?php if ($ehAdmin): ?>
              <a href="<?= htmlspecialchars($docs['certidao_trabalhista_path']) ?>" 
                 download 
                 class="btn btn-sm btn-outline-secondary ms-1">
                <i class="bi bi-download me-1"></i> Download
              </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Certidão Ambiental -->
        <div class="col-md-6">
          <div class="d-flex align-items-start mb-3">
            <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
              <i class="bi bi-leaf-fill fs-6"></i>
            </div>
            <div>
              <h6 class="fw-bold mb-1">Certidão de Regularidade Ambiental</h6>
              <div class="badge bg-info mb-1">Ambiental - Enviada</div>
              <small class="text-muted">
                <?= date('d/m/Y H:i', strtotime($docs['data_envio'] ?? 'now')) ?>
              </small>
            </div>
          </div>
          <div class="mb-2">
            <a href="<?= htmlspecialchars($docs['certidao_ambiental_path']) ?>" 
               target="_blank" 
               class="btn btn-sm btn-outline-info d-inline-flex align-items-center">
              <i class="bi bi-eye me-1"></i> 
              Visualizar PDF
            </a>
            <?php if ($ehAdmin): ?>
              <a href="<?= htmlspecialchars($docs['certidao_ambiental_path']) ?>" 
                 download 
                 class="btn btn-sm btn-outline-secondary ms-1">
                <i class="bi bi-download me-1"></i> Download
              </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Infos extras para admin -->
        <?php if ($ehAdmin): ?>
        <div class="col-12">
          <div class="row">
            <div class="col-md-6">
              <small class="text-muted">
                <i class="bi bi-calendar-check me-1"></i>
                Última atualização: <?= date('d/m/Y H:i', strtotime($docs['data_atualizacao'] ?? 'now')) ?>
              </small>
            </div>
            <div class="col-md-6 text-end">
              <span class="badge <?= empty($docs['certidao_trabalhista_path']) ? 'bg-warning' : 'bg-success' ?>">
                Trabalhista: <?= empty($docs['certidao_trabalhista_path']) ? 'Pendente' : 'OK' ?>
              </span>
              <span class="badge ms-1 <?= empty($docs['certidao_ambiental_path']) ? 'bg-warning' : 'bg-success' ?>">
                Ambiental: <?= empty($docs['certidao_ambiental_path']) ? 'Pendente' : 'OK' ?>
              </span>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div>
    <?php endif; ?>
  </div>
</div>
