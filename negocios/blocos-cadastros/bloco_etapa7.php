<?php
// bloco_etapa7.php - Visualização da Etapa 7 (Avaliação de Impact)
// Espera: $negocio, $negocio_id, $impacto (array do negocio_impacto)

if (!isset($negocio) || !isset($negocio_id)) return;

// Se $impacto vier como false do fetch, normaliza para []
$impacto = is_array($impacto) ? $impacto : [];

// Helpers do _shared.php
$beneficiarios = impacto_beneficiarios($impacto);
$metricas      = impacto_metricas($impacto);
$formas        = impacto_formas_medicao($impacto);
$links         = impacto_links($impacto);
$pdfs          = impacto_pdfs($impacto);
?>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong><i class="bi bi-bar-chart-line me-1"></i> Avaliação de Impacto - Etapa 7</strong>
    <?php 
            $ehAdmin = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
            $somenteLeitura = isset($somenteLeitura) && $somenteLeitura === true;
            
            if (!$ehAdmin && !$somenteLeitura): 
            ?>
                <a href="/negocios/editar_etapa7.php?id=<?= $negocio_id ?? $negocio['id'] ?? 0 ?>" class="btn btn-sm btn-outline-primary">Editar</a>
        <?php endif; ?>
  </div>

  <div class="card-body">
    <?php if (empty(array_filter($impacto))): ?>
      <div class="alert alert-info text-center">
        <i class="bi bi-info-circle-fill me-2 fs-4"></i>
        Nenhuma informação de impacto cadastrada ainda.
      </div>
    <?php else: ?>
      <div class="row">
        <div class="col-md-6">
          <h5><i class="bi bi-lightbulb-fill text-success me-1"></i> Intencionalidade <i class="bi bi-eye text-secondary me-1"></i></h5>
          <p class="mb-1"><span class="small-muted">Qual das opções melhor representa a relação entre geração de receita e missão do seu negócio?</span></p>
          <?= !empty($impacto['intencionalidade']) ? '<div class="alert alert-light">'.nl2br(e($impacto['intencionalidade'])).'</div>' : '<div class="alert alert-secondary text-center">Não informado</div>'; ?>
        </div>

        <div class="col-md-6">
          <h5><i class="bi bi-diagram-3-fill text-info me-1"></i> Tipo de Impacto <i class="bi bi-eye text-secondary me-1"></i></h5>
          <p class="mb-1"><span class="small-muted">Como você classificaria o tipo de impacto que seu negócio gera hoje?</span></p>
          <?= !empty($impacto['tipo_impacto']) ? '<div class="alert alert-light">'.nl2br(e($impacto['tipo_impacto'])).'</div>' : '<div class="alert alert-secondary text-center">Não informado</div>'; ?>
        </div>

        <div class="col-md-6">
          <h5><i class="bi bi-people-fill text-primary me-1"></i> Beneficiários  <i class="bi bi-eye text-secondary me-1"></i></h5>
          <p class="mb-1"><span class="small-muted">Quem são os principais grupos beneficiados pelo seu negócio?</span></p>
          <?= render_badges($beneficiarios, 'primary') ?>
          <?php if (!empty($impacto['beneficiario_outro'])): ?>
            <div class="mt-2 small-muted">Outro: <?= e($impacto['beneficiario_outro']) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <h5><i class="bi bi-geo-alt-fill text-warning me-1"></i> Alcance <i class="bi bi-eye text-secondary me-1"></i></h5>
          <p class="mb-1"><span class="small-muted">Beneficiários diretos nos últimos 2 anos</span></p>
          <?= !empty($impacto['alcance']) ? '<div class="alert alert-light">'.nl2br(e($impacto['alcance'])).'</div>' : '<div class="alert alert-secondary text-center">Não informado</div>'; ?>
        </div>

        <div class="col-md-6">
          <h5><i class="bi bi-graph-up-arrow text-success me-1"></i> Métricas <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></h5>
          <p class="mb-1"><span class="small-muted">Métricas e indicadores utilizados para mensurar o impacto</span></p>
          <?= render_badges($metricas, 'success') ?>
          <?php if (!empty($impacto['metrica_outro'])): ?>
            <div class="mt-2 small-muted">Outra: <?= e($impacto['metrica_outro']) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <h5><i class="bi bi-clipboard-check text-info me-1"></i> Medição <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></h5>
          <p class="mb-1">A empresa mede seu impacto socioambiental?</p>
          <?= !empty($impacto['medicao']) ? '<div class="alert alert-light">'.nl2br(e($impacto['medicao'])).'</div>' : '<div class="alert alert-secondary text-center">Não informado</div>'; ?>          
          <p class="mb-1">Como o impacto é medido hoje?</p>
          <div class="alert alert-light"><?= render_badges($formas, 'secondary') ?></div>
          <?php if (!empty($impacto['forma_outro'])): ?>
            <div class="alert alert-light">Outra: <?= e($impacto['forma_outro']) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <h5><i class="bi bi-journal-text text-primary me-1"></i> Reporte <i class="bi bi-eye-slash text-danger-emphasis me-1"></i></h5>
          <p class="mb-1"><span class="small-muted">Tipo de reporte ou prestação de contas do impacto</span></p>
          <?= !empty($impacto['reporte']) ? '<div class="alert alert-light">'.nl2br(e($impacto['reporte'])).'</div>' : '<div class="alert alert-secondary text-center">Não informado</div>'; ?>

           <h5><i class="bi bi-link-45deg text-success me-1"></i> Links de Resultados <i class="bi bi-eye text-secondary me-1"></i></h5>
            <?php if (!empty($links)): ?>
                <ul>
                <?php foreach ($links as $link): ?>
                    <li><a href="<?= attr($link) ?>" target="_blank"><?= e($link) ?></a></li>
                <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <span class="small-muted">Nenhum link informado</span>
            <?php endif; ?>

            <h5><i class="bi bi-file-earmark-pdf text-danger me-1"></i> PDFs de Resultados <i class="bi bi-eye text-secondary me-1"></i></h5>
            <?php if (!empty($pdfs)): ?>
                <ul>
                <?php foreach ($pdfs as $pdf): ?>
                    <li><a href="/<?= attr($pdf) ?>" target="_blank">Abrir PDF</a></li>
                <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <span class="small-muted">Nenhum PDF enviado</span>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
          <h5><i class="bi bi-bar-chart-fill text-danger me-1"></i> Resultados <i class="bi bi-eye text-secondary me-1"></i></h5>
          <p class="mb-1"><span class="small-muted">Resultados de impacto mais relevantes alcançados até hoje</span></p>
          <?= !empty($impacto['resultados']) ? '<div class="alert alert-light">'.nl2br(e($impacto['resultados'])).'</div>' : '<div class="alert alert-secondary text-center">Não informado</div>'; ?>
        </div>

        
        <div class="col-12">
          <h5><i class="bi bi-forward-fill text-warning me-1"></i> Próximos Passos <i class="bi bi-eye text-secondary me-1"></i></h5>
          <?= !empty($impacto['proximos_passos']) ? '<div class="alert alert-light border">'.nl2br(e($impacto['proximos_passos'])).'</div>' : '<div class="alert alert-secondary text-center">Não informado</div>'; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>