<?php
// Defina em cada etapa: $etapaAtual = número da etapa atual
$etapas = [
  1 => 'Dados do Negócio',
  2 => 'Fundadores',
  3 => 'Eixo Temático',
  4 => 'Conexão com os ODS',
  5 => 'Apresentação  do Negócio',
  6 => 'Dados Financeiro e Modelo de Receita',
  7 => 'Avaliação de Impacto',
  8 => 'Visão de Futuro',
  9 => 'Revisão e confirmação'
];
?>

<div class="progress-container mb-4">
  <ul class="progressbar">
    <?php foreach ($etapas as $num => $titulo): ?>
      <li class="<?= ($num == $etapaAtual) ? 'active' : (($num < $etapaAtual) ? 'completed' : '') ?>">
        <span class="step-number"><?= $num ?></span>
        <span class="step-title"><?= htmlspecialchars($titulo) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<style>
.progress-container {
  width: 100%;
}
.progressbar {
  display: flex;
  justify-content: space-between;
  list-style: none;
  padding: 0;
  margin: 0;
}
.progressbar li {
  text-align: center;
  flex: 1;
  position: relative;
}
.step-number {
  display: inline-block;
  width: 32px;
  height: 32px;
  line-height: 32px;
  border-radius: 50%;
  border: 2px solid #ccc;
  margin-bottom: 5px;
  background-color: #fff;
}
.step-title {
  font-size: 0.8rem;
  display: block;
}
.progressbar li.active .step-number {
  border-color: #0d6efd;
  background-color: #0d6efd;
  color: #fff;
  font-weight: bold;
}
.progressbar li.completed .step-number {
  border-color: #198754;
  background-color: #198754;
  color: #fff;
}
.progressbar li::after {
  content: '';
  position: absolute;
  width: 100%;
  height: 2px;
  background-color: #ccc;
  top: 16px;
  left: -50%;
  z-index: -1;
}
.progressbar li:first-child::after {
  content: none;
}
.progressbar li.completed::after {
  background-color: #198754;
}
.progressbar li.active::after {
  background-color: #0d6efd;
}
</style>
