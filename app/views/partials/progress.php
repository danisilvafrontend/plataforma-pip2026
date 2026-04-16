<?php
// /app/views/partials/progress.php
// Defina em cada etapa: $etapaAtual = número da etapa atual
$etapas = [
  1  => 'Dados do Negócio',
  2  => 'Fundadores',
  3  => 'Eixo Temático',
  4  => 'Conexão com os ODS',
  5  => 'Dados Financeiros',
  6  => 'Avaliação de Impacto',
  7  => 'Visão de Futuro',
  8  => 'Apresentação do Negócio',
  9  => 'Documentação',
  10 => 'Revisão e Confirmação',
];
$totalEtapas = count($etapas);
$pct = round((($etapaAtual - 1) / ($totalEtapas - 1)) * 100);
?>

<div class="ip-progress-wrap">

  <!-- Barra linear -->
  <div class="ip-progress-bar-track">
    <div class="ip-progress-bar-fill" style="width: <?= $pct ?>%;"></div>
  </div>

  <!-- Steps -->
  <ul class="ip-steps">
    <?php foreach ($etapas as $num => $titulo):
      $cls = '';
      if ($num < $etapaAtual)  $cls = 'completed';
      if ($num == $etapaAtual) $cls = 'active';
    ?>
      <li class="ip-step <?= $cls ?>">
        <div class="ip-step-dot">
          <?php if ($num < $etapaAtual): ?>
            <i class="bi bi-check-lg" style="font-size:.8rem;"></i>
          <?php else: ?>
            <?= $num ?>
          <?php endif; ?>
        </div>
        <span class="ip-step-label"><?= htmlspecialchars($titulo) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>

  <!-- Meta info -->
  <div class="ip-progress-meta">
    <span class="step-info">
      Etapa <?= $etapaAtual ?> de <?= $totalEtapas ?>
      &mdash; <?= htmlspecialchars($etapas[$etapaAtual]) ?>
    </span>
    <span class="step-pct"><?= $pct ?>% concluído</span>
  </div>

</div>