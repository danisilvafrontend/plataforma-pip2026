<?php
// bloco_etapa8.php - Visualização da Etapa 8 (Visão de Futuro)
// Espera: $negocio, $negocio_id, $visao (array do negocio_visao)

if (!isset($negocio) || !isset($negocio_id)) return;

// Se $visao vier como false do fetch, normaliza para []
$visao = is_array($visao) ? $visao : [];

// Helpers do _shared.php
$apoios = decode_json_array($visao['apoios'] ?? '[]');
$areas  = decode_json_array($visao['areas'] ?? '[]');
$temas  = decode_json_array($visao['temas'] ?? '[]');
?>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong><i class="bi bi-eye-fill me-1"></i> Visão de Futuro - Etapa 8 <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></strong>
    <?php 
          $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
          $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;
          
          if (!$ehAdmin && !$somenteLeitura): 
          ?>
              <a href="/negocios/editar_etapa8.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn btn-sm btn-outline-primary">Editar</a>
      <?php endif; ?>
    
  </div>

  <div class="card-body">
    <?php if (empty(array_filter($visao))): ?>
      <div class="alert alert-info text-center">
        <i class="bi bi-info-circle-fill me-2 fs-4"></i>
        Nenhuma informação de visão cadastrada ainda.
      </div>
    <?php else: ?>
      <div class="row">
        <div class="col-md-6">
          <h5><i class="bi bi-lightbulb-fill text-success me-1"></i> Visão Estratégica</h5>
          <?= !empty($visao['visao_estrategica']) ? '<div class="alert alert-light">'.nl2br(e($visao['visao_estrategica'])).'</div>' : '<div class="alert alert-light text-center">Não informado</div>'; ?>
          <?php if (!empty($visao['visao_outro'])): ?>
            <div class="mt-2 small-muted">Outro: <?= e($visao['visao_outro']) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <h5><i class="bi bi-tree-fill text-success me-1"></i> Sustentabilidade</h5>
          <?= !empty($visao['sustentabilidade']) ? '<div class="alert alert-light">'.nl2br(e($visao['sustentabilidade'])).'</div>' : '<div class="alert alert-secondary text-center">Não informado</div>'; ?>
        </div>

        <div class="col-md-6">
          <h5><i class="bi bi-arrows-expand text-info me-1"></i> Escala</h5>
          <?= !empty($visao['escala']) ? '<div class="alert alert-light">'.nl2br(e($visao['escala'])).'</div>' : '<div class="alert alert-secondary text-center">Não informado</div>'; ?>
        </div>

        <div class="col-md-6">
          <h5><i class="bi bi-hand-thumbs-up-fill text-primary me-1"></i> Apoios</h5>
          <span class="small-muted">Apoio financeiro ou estratégico que você busca atualmente</span>
          <?= render_badges($apoios, 'primary') ?>
          <?php if (!empty($visao['apoio_outro'])): ?>
            <div class="mt-2 small-muted">Outro: <?= e($visao['apoio_outro']) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <h5><i class="bi bi-geo-alt-fill text-primary me-1"></i> Áreas de Atuação</h5>
          <span class="small-muted">Áreas do seu negócio você gostaria de fortalecer com apoio externo</span>
          <?= render_badges($areas, 'primary') ?>
          <?php if (!empty($visao['area_outro'])): ?>
            <div class="mt-2 small-muted">Outra: <?= e($visao['area_outro']) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <h5><i class="bi bi-bookmark-star-fill text-danger me-1"></i> Temas Prioritários</h5>
          <span class="small-muted">Temas você gostaria de aprender ou trocar com outros empreendedores/mentores</span>
          <?= render_badges($temas, 'danger') ?>
          <?php if (!empty($visao['tema_outro'])): ?>
            <div class="mt-2 small-muted">Outro: <?= e($visao['tema_outro']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>