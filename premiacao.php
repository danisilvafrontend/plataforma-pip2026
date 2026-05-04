<?php
// premiacao.php — Página pública da Premiação IP 2026
require_once __DIR__ . '/app/config/database.php';

$pageTitle = 'Premiação Impactos Positivos 2026';

// Busca a premiação ativa/mais recente para exibir dados dinâmicos
try {
    $stmt = $pdo->query("
        SELECT p.*, pr.ano, pr.nome AS premiacao_nome
        FROM premiacoes pr
        LEFT JOIN fases p ON p.premiacao_id = pr.id AND p.status = 'ativa'
        WHERE pr.status = 'ativa'
        ORDER BY pr.ano DESC, p.id DESC
        LIMIT 1
    ");
    $premiacaoInfo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    $premiacaoInfo = [];
}

// Busca categorias disponíveis
try {
    $cats = $pdo->query("SELECT DISTINCT categoria FROM negocios WHERE status = 'aprovado' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $cats = [];
}

require_once __DIR__ . '/app/views/public/header_public.php';
?>

<!-- ══════════════════════════════════════════
     HERO — PREMIAÇÃO
══════════════════════════════════════════ -->
<section class="premiacao-page-hero py-5">
  <div class="container">
    <div class="row align-items-center g-5">

      <!-- Texto -->
      <div class="col-12 col-lg-6">
        <span class="section-kicker section-kicker--accent mb-3">
          <i class="bi bi-trophy-fill me-1"></i> Premiação IP 2026
        </span>
        <h1 class="premiacao-page-title mt-2">
          O prêmio que <span class="text-verde-accent">reconhece</span><br>quem transforma
        </h1>
        <p class="premiacao-page-sub mt-3">
          A Premiação Impactos Positivos celebra negócios e iniciativas que geram mudança real — nas comunidades, no meio ambiente e na economia. Candidate-se, vote e conheça os finalistas.
        </p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="/empreendedores/dashboard.php" class="btn-premiacao-primary">
            <i class="bi bi-rocket-takeoff-fill me-2"></i> Inscreva seu negócio
          </a>
          <a href="#como-funciona" class="btn-premiacao-outline">
            <i class="bi bi-info-circle me-2"></i> Como funciona
          </a>
        </div>
      </div>

      <!-- Cards de destaque -->
      <div class="col-12 col-lg-6">
        <div class="premiacao-hero-stats">

          <div class="phs-card phs-card--primary">
            <div class="phs-icon"><i class="bi bi-calendar2-check-fill"></i></div>
            <div>
              <strong>Inscrições abertas</strong>
              <span>Candidature seu negócio agora</span>
            </div>
          </div>

          <div class="phs-card">
            <div class="phs-icon phs-icon--secondary"><i class="bi bi-people-fill"></i></div>
            <div>
              <strong>Votação popular</strong>
              <span>A sociedade civil decide os semifinalistas</span>
            </div>
          </div>

          <div class="phs-card">
            <div class="phs-icon phs-icon--gold"><i class="bi bi-star-fill"></i></div>
            <div>
              <strong>Júri especializado</strong>
              <span>Especialistas escolhem os vencedores finais</span>
            </div>
          </div>

          <div class="phs-card phs-card--accent">
            <div class="phs-icon phs-icon--dark"><i class="bi bi-award-fill"></i></div>
            <div>
              <strong>Premiação pública</strong>
              <span>Cerimônia de encerramento presencial</span>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════
     COMO FUNCIONA
══════════════════════════════════════════ -->
<section id="como-funciona" class="premiacao-como-section">
  <div class="container">

    <div class="text-center mb-5">
      <span class="section-kicker section-kicker--outline mb-3">
        <i class="bi bi-diagram-3 me-1"></i> Etapas
      </span>
      <h2 class="section-title mt-2">Como funciona a Premiação?</h2>
      <p class="section-sub mx-auto" style="max-width:520px;">
        O processo é transparente, em quatro fases — da inscrição à cerimônia final.
      </p>
    </div>

    <div class="premiacao-etapas">

      <div class="prem-etapa">
        <div class="prem-etapa-num">1</div>
        <div class="prem-etapa-icon"><i class="bi bi-pencil-square"></i></div>
        <h5>Inscrição</h5>
        <p>O empreendedor cadastra seu negócio na plataforma, preenchendo dados sobre impacto, inovação e proposta de valor.</p>
      </div>

      <div class="prem-etapa-seta"><i class="bi bi-arrow-right"></i></div>

      <div class="prem-etapa">
        <div class="prem-etapa-num">2</div>
        <div class="prem-etapa-icon prem-etapa-icon--secondary"><i class="bi bi-people"></i></div>
        <h5>Votação Popular</h5>
        <p>A sociedade civil vota nos negócios inscritos. Os mais votados avançam para a fase semifinal e final.</p>
      </div>

      <div class="prem-etapa-seta"><i class="bi bi-arrow-right"></i></div>

      <div class="prem-etapa">
        <div class="prem-etapa-num">3</div>
        <div class="prem-etapa-icon prem-etapa-icon--gold"><i class="bi bi-people-fill"></i></div>
        <h5>Avaliação do Júri</h5>
        <p>Um júri formado por especialistas em impacto social, ESG e inovação avalia e vota nos finalistas de cada categoria.</p>
      </div>

      <div class="prem-etapa-seta"><i class="bi bi-arrow-right"></i></div>

      <div class="prem-etapa">
        <div class="prem-etapa-num">4</div>
        <div class="prem-etapa-icon prem-etapa-icon--accent"><i class="bi bi-trophy-fill"></i></div>
        <h5>Cerimônia</h5>
        <p>Os vencedores são anunciados em uma cerimônia presencial com parceiros, investidores e líderes do setor.</p>
      </div>

    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════
     CATEGORIAS
══════════════════════════════════════════ -->
<section class="premiacao-categorias-section">
  <div class="container">

    <div class="row g-4 align-items-center mb-5">
      <div class="col-12 col-md-7">
        <span class="section-kicker section-kicker--accent mb-3">
          <i class="bi bi-grid-1x2 me-1"></i> Categorias
        </span>
        <h2 class="section-title mt-2">Áreas de impacto reconhecidas</h2>
        <p class="section-sub">
          A premiação abraça negócios de diferentes setores — o que une todos é o compromisso com impacto positivo real.
        </p>
      </div>
      <div class="col-12 col-md-5 text-md-end">
        <a href="/vitrine_nacional.php" class="btn-premiacao-primary">
          <i class="bi bi-grid me-2"></i> Ver Vitrine Nacional
        </a>
      </div>
    </div>

    <div class="premiacao-cats-grid">

      <div class="prem-cat-card">
        <div class="prem-cat-icon" style="background:#e8f5e9; color:#2e7d32;"><i class="bi bi-tree-fill"></i></div>
        <h6>Meio Ambiente</h6>
        <p>Soluções para conservação, regeneração e uso sustentável dos recursos naturais.</p>
      </div>

      <div class="prem-cat-card">
        <div class="prem-cat-icon" style="background:#e3f2fd; color:#1565c0;"><i class="bi bi-heart-pulse-fill"></i></div>
        <h6>Saúde & Bem-estar</h6>
        <p>Inovações que ampliam o acesso a saúde de qualidade para populações vulneráveis.</p>
      </div>

      <div class="prem-cat-card">
        <div class="prem-cat-icon" style="background:#fff8e1; color:#f57f17;"><i class="bi bi-lightbulb-fill"></i></div>
        <h6>Educação</h6>
        <p>Iniciativas que transformam trajetórias por meio do acesso ao conhecimento.</p>
      </div>

      <div class="prem-cat-card">
        <div class="prem-cat-icon" style="background:#f3e5f5; color:#6a1b9a;"><i class="bi bi-gender-ambiguous"></i></div>
        <h6>Diversidade & Inclusão</h6>
        <p>Negócios que promovem equidade de gênero, raça e oportunidades para todos.</p>
      </div>

      <div class="prem-cat-card">
        <div class="prem-cat-icon" style="background:#fce4ec; color:#c62828;"><i class="bi bi-house-heart-fill"></i></div>
        <h6>Comunidade</h6>
        <p>Projetos com impacto direto no desenvolvimento local e geração de renda.</p>
      </div>

      <div class="prem-cat-card">
        <div class="prem-cat-icon" style="background:#e0f7fa; color:#00838f;"><i class="bi bi-cpu-fill"></i></div>
        <h6>Tecnologia & Inovação</h6>
        <p>Soluções digitais e tecnológicas aplicadas a desafios sociais e ambientais.</p>
      </div>

    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════
     POR QUE PARTICIPAR
══════════════════════════════════════════ -->
<section class="premiacao-por-que-section">
  <div class="container">
    <div class="premiacao-por-que-inner">
      <div class="ppq-grafismo"></div>

      <div class="row g-5 align-items-center position-relative">
        <div class="col-12 col-lg-5">
          <span class="section-kicker section-kicker--claro mb-3">
            <i class="bi bi-star-fill me-1"></i> Benefícios
          </span>
          <h2 class="mt-2" style="font-size:clamp(1.6rem,3vw,2.2rem); font-weight:800; color:#fff; line-height:1.2;">
            Por que inscrever seu negócio?
          </h2>
          <p style="color:rgba(255,255,255,.75); font-size:.97rem; line-height:1.75; max-width:420px;">
            Além do reconhecimento, participar da premiação abre portas para visibilidade, networking e oportunidades de crescimento.
          </p>
          <a href="/empreendedores/dashboard.php" class="btn-premiacao-primary mt-3">
            <i class="bi bi-rocket-takeoff-fill me-2"></i> Inscreva-se agora
          </a>
        </div>

        <div class="col-12 col-lg-7">
          <div class="ppq-beneficios">

            <div class="ppq-item">
              <div class="ppq-item-icon"><i class="bi bi-megaphone-fill"></i></div>
              <div>
                <strong>Visibilidade nacional</strong>
                <span>Seu negócio na Vitrine Nacional e nas redes oficiais do IP.</span>
              </div>
            </div>

            <div class="ppq-item">
              <div class="ppq-item-icon"><i class="bi bi-diagram-3-fill"></i></div>
              <div>
                <strong>Networking qualificado</strong>
                <span>Conexão com investidores, parceiros e líderes de impacto.</span>
              </div>
            </div>

            <div class="ppq-item">
              <div class="ppq-item-icon"><i class="bi bi-patch-check-fill"></i></div>
              <div>
                <strong>Credibilidade de marca</strong>
                <span>Ser finalista ou vencedor é um diferencial competitivo real.</span>
              </div>
            </div>

            <div class="ppq-item">
              <div class="ppq-item-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
              <div>
                <strong>Análise de impacto</strong>
                <span>Acesso a dados e métricas sobre alcance e votação do seu negócio.</span>
              </div>
            </div>

            <div class="ppq-item">
              <div class="ppq-item-icon"><i class="bi bi-trophy-fill"></i></div>
              <div>
                <strong>Premiação presencial</strong>
                <span>Cerimônia exclusiva com entrega de troféu e cobertura de mídia.</span>
              </div>
            </div>

            <div class="ppq-item">
              <div class="ppq-item-icon"><i class="bi bi-globe2"></i></div>
              <div>
                <strong>Impacto que inspira</strong>
                <span>Sua história motiva outros empreendedores a gerar mudança.</span>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════
     CTA FINAL
══════════════════════════════════════════ -->
<section class="py-5">
  <div class="container">
    <div class="premiacao-cta-final">
      <div class="pcf-grafismo"></div>
      <div class="row g-4 align-items-center position-relative">
        <div class="col-12 col-md-8">
          <h3 class="fw-800" style="color:#1E3425; font-size:clamp(1.4rem,2.5vw,2rem); font-weight:800;">Pronto para fazer parte da Premiação IP 2026?</h3>
          <p style="color:#6c8070; font-size:.97rem; margin-bottom:0;">Inscreva seu negócio, vote nos seus favoritos ou indique um parceiro. A mudança começa com você.</p>
        </div>
        <div class="col-12 col-md-4 text-md-end d-flex flex-wrap gap-3 justify-content-md-end">
          <a href="/empreendedores/dashboard.php" class="btn-premiacao-primary">
            <i class="bi bi-rocket-takeoff-fill me-2"></i> Inscreva-se
          </a>
          <a href="/vitrine_nacional.php" class="btn-premiacao-outline" style="color:#1E3425 !important; border-color:rgba(30,52,37,.3);">
            <i class="bi bi-grid me-2"></i> Ver negócios
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/app/views/public/footer_public.php'; ?>
