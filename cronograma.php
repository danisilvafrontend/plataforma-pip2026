<?php
// /cronograma.php
declare(strict_types=1);

$pageTitle = 'Cronograma Oficial 2026 | Prêmio Impactos Positivos';

include __DIR__ . '/app/views/public/header_public.php';
?>

<!-- ═══════════════════════════════════════
     HERO — CRONOGRAMA
════════════════════════════════════════ -->
<section class="cron-hero">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-8">
        <span class="reg-update-badge mb-3 d-inline-flex">
          <i class="bi bi-calendar3-event-fill me-1"></i>
          Edição 2026
        </span>
        <h1 class="reg-hero-title mb-3">
          Cronograma Oficial<br>
          <span style="color:#CDDE00;">Prêmio Impactos Positivos 2026</span>
        </h1>
        <p class="reg-hero-sub mb-4">
          Acompanhe todas as datas e etapas do Prêmio — desde a abertura das inscrições até a cerimônia de premiação.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <a href="https://vitrine.impactospositivos.com" target="_blank" rel="noopener" class="btn-premiacao-primary">
            <i class="bi bi-pencil-square me-2"></i> Inscreva-se agora
          </a>
          <a href="regulamento-do-premio.php" class="btn-premiacao-outline">
            <i class="bi bi-file-text me-2"></i> Ver regulamento
          </a>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="d-flex flex-column gap-2">
          <div class="reg-hero-info-item">
            <i class="bi bi-flag" style="color:#CDDE00;"></i>
            Início: <strong>11 de maio de 2026</strong>
          </div>
          <div class="reg-hero-info-item">
            <i class="bi bi-trophy" style="color:#CDDE00;"></i>
            Premiação: <strong>24 de setembro de 2026</strong>
          </div>
          <div class="reg-hero-info-item">
            <i class="bi bi-grid-3x2-gap" style="color:#CDDE00;"></i>
            4 categorias · 3 fases de votação
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- /HERO -->


<!-- ═══════════════════════════════════════
     TIMELINE
════════════════════════════════════════ -->
<section class="py-5">
  <div class="container">

    <!-- Legenda de status -->
    <div class="cron-legenda d-flex flex-wrap gap-3 align-items-center mb-5">
      <span class="cron-status-badge cron-status-badge--ativo"><i class="bi bi-circle-fill me-1"></i>Em andamento</span>
      <span class="cron-status-badge cron-status-badge--futuro"><i class="bi bi-circle me-1"></i>Em breve</span>
      <span class="cron-status-badge cron-status-badge--concluido"><i class="bi bi-check-circle-fill me-1"></i>Concluído</span>
    </div>

    <?php
    // Determina status de cada etapa com base na data atual
    $hoje = new DateTime();

    $etapas = [
      [
        'icone'     => 'bi-rocket-takeoff-fill',
        'cor'       => '#CDDE00',
        'cor_bg'    => '#1E3425',
        'titulo'    => 'Abertura das Inscrições',
        'data'      => '11 de maio de 2026',
        'inicio'    => '2026-05-11',
        'fim'       => '2026-07-24',
        'descricao' => 'Lançamento oficial do Prêmio Impactos Positivos 2026! Negócios e projetos já podem se inscrever gratuitamente na plataforma, escolhendo uma das quatro categorias: <strong>Ideação, Tração, Operação</strong> e <strong>Dinamizador do Ecossistema</strong>.',
        'detalhe'   => 'Acesse a plataforma, complete 100% do perfil do seu negócio e finalize a inscrição.',
        'cta_label' => 'Inscreva-se agora',
        'cta_url'   => 'https://vitrine.impactospositivos.com',
        'cta_ext'   => true,
      ],
      [
        'icone'     => 'bi-clock-history',
        'cor'       => '#fff',
        'cor_bg'    => '#7a8e7c',
        'titulo'    => 'Encerramento das Inscrições',
        'data'      => '24 de julho de 2026',
        'inicio'    => '2026-07-24',
        'fim'       => '2026-07-24',
        'descricao' => 'As inscrições se encerram às <strong>23h59</strong>. Após o fechamento, a plataforma exibirá a vitrine pública com todos os negócios inscritos com perfil 100% completo — só os completos entram na disputa!',
        'detalhe'   => '<strong>⚠️ Atenção:</strong> inscrições incompletas não serão consideradas. Revise seu perfil antes do prazo.',
        'cta_label' => null,
        'cta_url'   => null,
        'cta_ext'   => false,
      ],
      [
        'icone'     => 'bi-search',
        'cor'       => '#1E3425',
        'cor_bg'    => '#95BCCC',
        'titulo'    => 'Avaliação Cadastral — 1ª Triagem',
        'data'      => '25 a 29 de julho de 2026',
        'inicio'    => '2026-07-25',
        'fim'       => '2026-07-29',
        'descricao' => 'Todos os inscritos passam por análise cadastral. Verifica-se a conformidade com os princípios da premiação. Não serão aceitas inscrições de empresas com registros de impactos negativos ambientais, sociais ou de governança <strong>(ESG)</strong>.',
        'detalhe'   => null,
        'cta_label' => null,
        'cta_url'   => null,
        'cta_ext'   => false,
      ],
      [
        'icone'     => 'bi-hand-index-thumb-fill',
        'cor'       => '#CDDE00',
        'cor_bg'    => '#1E3425',
        'titulo'    => 'Votação Popular — Fase 1 (Classificatória 1)',
        'data'      => '30 de julho a 14 de agosto de 2026',
        'inicio'    => '2026-07-30',
        'fim'       => '2026-08-14',
        'descricao' => 'A votação popular começa! Qualquer pessoa da sociedade brasileira pode acessar a plataforma e votar. Ao mesmo tempo, a <strong>Bancada Técnica</strong> realiza sua avaliação com base nos critérios definidos no regulamento.',
        'detalhe'   => null,
        'resultado' => [
          'label' => 'TOP 20 por categoria',
          'itens' => [
            '<span class="cron-pool-badge cron-pool-badge--popular"><i class="bi bi-people-fill me-1"></i>10 mais votados — Voto Popular</span>',
            '<span class="cron-pool-badge cron-pool-badge--tecnico"><i class="bi bi-patch-check-fill me-1"></i>10 selecionados — Bancada Técnica</span>',
          ]
        ],
        'cta_label' => 'Ver regulamento de votação',
        'cta_url'   => 'regulamento-do-premio.php#sec-votacao',
        'cta_ext'   => false,
      ],
      [
        'icone'     => 'bi-shield-check',
        'cor'       => '#1E3425',
        'cor_bg'    => '#95BCCC',
        'titulo'    => 'Verificação e Auditoria — 2ª Triagem',
        'data'      => '15 a 23 de agosto de 2026',
        'inicio'    => '2026-08-15',
        'fim'       => '2026-08-23',
        'descricao' => 'Os 20 finalistas de cada categoria passam por segunda análise cadastral — os critérios ESG são reaplicados. Além disso, todos os votos computados são <strong>auditados</strong> para garantir a transparência e legitimidade da votação popular.',
        'detalhe'   => null,
        'cta_label' => null,
        'cta_url'   => null,
        'cta_ext'   => false,
      ],
      [
        'icone'     => 'bi-hand-index-thumb-fill',
        'cor'       => '#CDDE00',
        'cor_bg'    => '#1E3425',
        'titulo'    => 'Votação Popular — Fase 2 (Classificatória 2)',
        'data'      => '24 de agosto a 4 de setembro de 2026',
        'inicio'    => '2026-08-24',
        'fim'       => '2026-09-04',
        'descricao' => 'Segunda rodada de votação com os <strong>20 semifinalistas</strong> de cada categoria. A sociedade civil vota novamente e a Bancada Técnica realiza nova avaliação. Mobilize sua rede e mostre o impacto do seu negócio!',
        'detalhe'   => null,
        'resultado' => [
          'label' => 'TOP 6 por categoria',
          'itens' => [
            '<span class="cron-pool-badge cron-pool-badge--popular"><i class="bi bi-people-fill me-1"></i>3 mais votados — Voto Popular</span>',
            '<span class="cron-pool-badge cron-pool-badge--tecnico"><i class="bi bi-patch-check-fill me-1"></i>3 selecionados — Bancada Técnica</span>',
          ]
        ],
        'cta_label' => null,
        'cta_url'   => null,
        'cta_ext'   => false,
      ],
      [
        'icone'     => 'bi-stars',
        'cor'       => '#1E3425',
        'cor_bg'    => '#CDDE00',
        'titulo'    => 'Fase Final — Voto Popular + Júri',
        'data'      => '7 a 18 de setembro de 2026',
        'inicio'    => '2026-09-07',
        'fim'       => '2026-09-18',
        'descricao' => 'A fase decisiva! Os <strong>6 finalistas</strong> de cada categoria disputam o voto popular e o voto do Júri simultaneamente. Cada jurado vota em 1 negócio por categoria.',
        'detalhe'   => null,
        'resultado' => [
          'label' => 'Como o vencedor é definido — 5 pontos totais',
          'itens' => [
            '<i class="bi bi-people-fill me-1" style="color:#1d4ed8;"></i><strong>Voto popular</strong> (Fase 3): 1 ponto',
            '<i class="bi bi-stars me-1" style="color:#854d0e;"></i><strong>Votos do Júri</strong>: até 4 pontos adicionais',
            '<i class="bi bi-trophy-fill me-1" style="color:#97A327;"></i>O negócio com maior soma de pontos <strong>vence sua categoria</strong>',
          ]
        ],
        'cta_label' => null,
        'cta_url'   => null,
        'cta_ext'   => false,
      ],
      [
        'icone'     => 'bi-trophy-fill',
        'cor'       => '#CDDE00',
        'cor_bg'    => '#1E3425',
        'titulo'    => 'Encontro Impactos Positivos 2026 — Cerimônia de Premiação',
        'data'      => '24 de setembro de 2026',
        'inicio'    => '2026-09-24',
        'fim'       => '2026-09-24',
        'descricao' => 'O grande momento! Realizamos a cerimônia de encerramento com a <strong>entrega dos prêmios</strong> aos vencedores de cada categoria. O evento conta com painéis e palestras dos apoiadores e conselheiros da premiação.',
        'detalhe'   => '⭐ Esta é a edição 2026 do Prêmio Impactos Positivos. Faça parte da história!',
        'cta_label' => 'Saiba mais sobre as categorias',
        'cta_url'   => 'regulamento-do-premio.php#sec-categorias',
        'cta_ext'   => false,
      ],
    ];

    $total = count($etapas);
    foreach ($etapas as $i => $e):
      $inicio = new DateTime($e['inicio']);
      $fim    = new DateTime($e['fim']);

      if ($hoje >= $inicio && $hoje <= $fim) {
        $status = 'ativo';
      } elseif ($hoje > $fim) {
        $status = 'concluido';
      } else {
        $status = 'futuro';
      }

      $isLast   = ($i === $total - 1);
      $isVotacao = in_array($i, [3, 5, 6]); // fases de votação
    ?>

    <div class="cron-item cron-item--<?= $status ?>">
      <!-- Coluna esquerda: ícone + linha -->
      <div class="cron-track" aria-hidden="true">
        <div class="cron-icon-wrap" style="background:<?= htmlspecialchars($e['cor_bg']) ?>;color:<?= htmlspecialchars($e['cor']) ?>;">
          <i class="bi <?= htmlspecialchars($e['icone']) ?>"></i>
        </div>
        <?php if (!$isLast): ?>
          <div class="cron-line cron-line--<?= $status ?>"></div>
        <?php endif; ?>
      </div>

      <!-- Coluna direita: conteúdo -->
      <div class="cron-content">
        <div class="cron-card cron-card--<?= $status ?>">

          <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
            <div>
              <span class="cron-data-label">
                <i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($e['data']) ?>
              </span>
              <span class="cron-status-badge cron-status-badge--<?= $status ?> ms-2">
                <?php if ($status === 'ativo'): ?><i class="bi bi-circle-fill me-1"></i>Em andamento
                <?php elseif ($status === 'concluido'): ?><i class="bi bi-check-circle-fill me-1"></i>Concluído
                <?php else: ?><i class="bi bi-circle me-1"></i>Em breve
                <?php endif; ?>
              </span>
            </div>
          </div>

          <h3 class="cron-titulo"><?= htmlspecialchars($e['titulo']) ?></h3>

          <p class="cron-descricao"><?= $e['descricao'] ?></p>

          <?php if (!empty($e['detalhe'])): ?>
            <div class="cron-detalhe"><?= $e['detalhe'] ?></div>
          <?php endif; ?>

          <?php if (!empty($e['resultado'])): ?>
            <div class="cron-resultado">
              <div class="cron-resultado-label">
                <i class="bi bi-trophy me-2"></i><?= htmlspecialchars($e['resultado']['label']) ?>
              </div>
              <ul class="cron-resultado-lista">
                <?php foreach ($e['resultado']['itens'] as $item): ?>
                  <li><?= $item ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if (!empty($e['cta_label']) && !empty($e['cta_url'])): ?>
            <div class="mt-3">
              <a
                href="<?= htmlspecialchars($e['cta_url']) ?>"
                <?= $e['cta_ext'] ? 'target="_blank" rel="noopener"' : '' ?>
                class="cron-cta-link">
                <?= htmlspecialchars($e['cta_label']) ?> <i class="bi bi-arrow-right ms-1"></i>
              </a>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <?php endforeach; ?>

    <!-- CTA final -->
    <div class="cta-box mt-5">
      <div class="row align-items-center g-3">
        <div class="col-md-8">
          <h3 class="cta-title mb-1"><i class="bi bi-trophy-fill me-2"></i>Pronto para se inscrever?</h3>
          <p class="cta-sub mb-0">Inscrições abertas de 11/05 a 24/07/2026. Faça parte do Prêmio Impactos Positivos 2026!</p>
        </div>
        <div class="col-md-4 text-md-end">
          <a href="https://vitrine.impactospositivos.com" target="_blank" rel="noopener" class="btn-cta-parceiro">
            <i class="bi bi-pencil-square me-2"></i> Inscreva-se agora
          </a>
        </div>
      </div>
    </div>

  </div>
</section>
<!-- /TIMELINE -->

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>
