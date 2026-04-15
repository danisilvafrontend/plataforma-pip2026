<?php
// /home/dscria59_dani/public_html/index.php
declare(strict_types=1);

$pageTitle   = 'Impactos Positivos — Home';
$extraFooter = '<script>console.log("Home carregada");</script>';

$config = require __DIR__ . '/app/config/db.php';
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $sqlNegocios = "
        SELECT n.id, n.nome_fantasia, n.categoria, n.municipio, n.estado,
          a.frase_negocio, a.logo_negocio, a.imagem_destaque,
          o.icone_url,
          e.nome AS eixo_tematico_nome
        FROM negocios n
        LEFT JOIN negocio_apresentacao a ON a.negocio_id = n.id
        LEFT JOIN ods o ON o.id = n.ods_prioritaria_id
        LEFT JOIN eixos_tematicos e ON e.id = n.eixo_principal_id
        WHERE n.publicado_vitrine = 1
        ORDER BY RAND()
        LIMIT 9";
    $negociosDestaque = $pdo->query($sqlNegocios)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $negociosDestaque = [];
    error_log("Erro ao buscar negócios: " . $e->getMessage());
}

$sqlParceiros = "
    SELECT 
        p.id,
        p.nome_fantasia,
        c.logo_url,
        pp.perfil_publicado
    FROM parceiros p
    LEFT JOIN parceiro_contrato c ON c.parceiro_id = p.id
    LEFT JOIN parceiros_perfil pp ON pp.parceiro_id = p.id
    WHERE p.status = 'ativo'
      AND p.acordo_aceito = 1
    ORDER BY p.nome_fantasia ASC
";
$parceirosGrid = $pdo->query($sqlParceiros)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/app/views/public/header_public.php';
?>

<!-- ═══════════════════════════════════════════════
     HERO
════════════════════════════════════════════════ -->
<section class="hero-home">
  <div class="container">
    <div class="row align-items-center g-5">

      <div class="col-lg-6">
        <span class="hero-badge">
          <i class="bi bi-stars me-1"></i> Plataforma de Impacto Social
        </span>
        <h1 class="hero-title mt-3">
          Conectando <span class="hero-highlight">negócios, parceiros e sociedade</span> por um futuro mais sustentável
        </h1>
        <p class="hero-sub mt-3">
          A Impactos Positivos é uma plataforma que fortalece o ecossistema de impacto no Brasil, dando visibilidade a iniciativas transformadoras e ampliando conexões entre empreendedores, parceiros e comunidade.
        </p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="/empreendedores/register.php" class="btn-hero-primary">
            <i class="bi bi-rocket-takeoff-fill me-2"></i> Faça sua Inscrição
          </a>
          <a href="/parceiros/cadastro.php" class="btn-hero-outline">
            <i class="bi bi-diagram-3-fill me-2"></i> Seja um Parceiro
          </a>
        </div>
      </div>

      <div class="col-lg-6 d-none d-lg-flex justify-content-center">
        <div class="hero-stats-card">
          <div class="hero-stat">
            <i class="bi bi-briefcase-fill"></i>
            <div>
              <strong>Negócios de Impacto</strong>
              <span>cadastrados na plataforma</span>
            </div>
          </div>
          <div class="hero-stat">
            <i class="bi bi-people-fill"></i>
            <div>
              <strong>Parceiros e Apoiadores</strong>
              <span>que acreditam no impacto</span>
            </div>
          </div>
          <div class="hero-stat">
            <i class="bi bi-globe-americas"></i>
            <div>
              <strong>Estados Representados</strong>
              <span>em todo o Brasil</span>
            </div>
          </div>
          <div class="hero-stat">
            <i class="bi bi-award-fill"></i>
            <div>
              <strong>Prêmio Nacional</strong>
              <span>reconhecendo quem transforma</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════
     COMO FUNCIONA
════════════════════════════════════════════════ -->
<section class="section-como-funciona py-5">
  <div class="container">
    <div class="section-header text-center mb-5">
      <h2 class="section-title">Como funciona</h2>
      <p class="section-sub">Em poucos passos, seu negócio ganha visibilidade nacional</p>
    </div>
    <div class="row g-4 justify-content-center">
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="como-card">
          <div class="como-step">1</div>
          <i class="bi bi-pencil-square como-icon"></i>
          <h5>Cadastre-se</h5>
          <p>Crie sua conta de empreendedor e preencha os dados do seu negócio de impacto.</p>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="como-card">
          <div class="como-step">2</div>
          <i class="bi bi-card-checklist como-icon"></i>
          <h5>Complete o Perfil</h5>
          <p>Adicione apresentação, impacto social, ODS, eixo temático e muito mais.</p>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="como-card">
          <div class="como-step">3</div>
          <i class="bi bi-megaphone-fill como-icon"></i>
          <h5>Apareça na Vitrine</h5>
          <p>Seu negócio fica visível para parceiros, investidores e toda a comunidade.</p>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="como-card">
          <div class="como-step">4</div>
          <i class="bi bi-trophy-fill como-icon"></i>
          <h5>Concorra ao Prêmio</h5>
          <p>Participe do Prêmio Impactos Positivos e seja reconhecido nacionalmente.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════
     SOBRE O MOVIMENTO
════════════════════════════════════════════════ -->
<section class="py-5 section-movimento">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <span class="hero-badge mb-3">Nosso movimento</span>
        <h2 class="section-title mb-3">Mais do que uma plataforma, um ecossistema em movimento</h2>
        <p class="section-sub mb-3">
          A Impactos Positivos conecta negócios, parceiros e sociedade civil para fortalecer o ecossistema de impacto no Brasil.
        </p>
        <p class="text-muted mb-0">
          Nosso propósito é ampliar visibilidade, reconhecimento e colaboração entre iniciativas que geram transformação social, ambiental e econômica.
        </p>
      </div>
      <div class="col-lg-6">
        <div class="row g-3">
          <div class="col-sm-6">
            <div class="mini-pillars-card">
              <i class="bi bi-people-fill"></i>
              <h5>Conexões</h5>
              <p>Fortalecemos relações entre negócios, organizações e comunidade.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="mini-pillars-card">
              <i class="bi bi-lightbulb-fill"></i>
              <h5>Transformação</h5>
              <p>Valorizamos inovação com impacto real nos territórios.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="mini-pillars-card">
              <i class="bi bi-globe-americas"></i>
              <h5>ODS</h5>
              <p>Promovemos negócios alinhados aos Objetivos de Desenvolvimento Sustentável.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="mini-pillars-card">
              <i class="bi bi-heart-fill"></i>
              <h5>Impacto consciente</h5>
              <p>Reconhecemos iniciativas que unem propósito e sustentabilidade.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════
     Nossos princípios
════════════════════════════════════════════════ -->

<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Princípios que orientam nossa atuação</h2>
      <p class="section-sub">Tudo o que construímos parte de valores que fortalecem o ecossistema de impacto.</p>
    </div>

    <div class="row g-3 justify-content-center">
      <?php
      $principios = ['Educação', 'Transformação', 'Engajamento', 'Envolvimento', 'Colaboração', 'Sinergia'];
      foreach ($principios as $principio):
      ?>
        <div class="col-6 col-md-4 col-lg-2">
          <div class="principio-chip text-center"><?= htmlspecialchars($principio) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════
     Impacto reconhecido
════════════════════════════════════════════════ -->

<section class="py-5">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <h2 class="section-title mb-3">Reconhecimento para quem transforma o Brasil</h2>
        <p class="section-sub mb-3">
          O Prêmio Impactos Positivos valoriza iniciativas que contribuem para um futuro mais justo, sustentável e inclusivo.
        </p>
        <ul class="list-unstyled text-muted mb-0">
          <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i> Negócios de impacto</li>
          <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i> Ecossistemas de impacto</li>
          <li class="mb-2"><i class="bi bi-check2-circle text-success me-2"></i> Cidadãos e instituições inspiradoras</li>
          <li><i class="bi bi-check2-circle text-success me-2"></i> Comunicadores que ampliam visibilidade ao tema</li>
        </ul>
      </div>
      <div class="col-lg-5">
        <div class="cta-box h-100">
          <h3 class="cta-title">Conheça a premiação</h3>
          <p class="cta-sub mb-4">
            Descubra como a plataforma amplia o reconhecimento de quem gera impacto positivo no país.
          </p>
          <a href="https://impactospositivos.com/sobre-a-premiacao/" target="_blank" rel="noopener noreferrer" class="btn-cta-parceiro">
            <i class="bi bi-award-fill me-2"></i> Ver Premiação
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════
     VITRINE DE NEGÓCIOS
════════════════════════════════════════════════ -->
<!-- <?php if (!empty($negociosDestaque)): ?>
<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="hero-title mt-3">
          Conheça <span class="hero-highlight">Negócios de Impacto</span>
        </h2>
      </div>
      <a href="/vitrine_nacional.php" class="btn-vitrine-ver-todos">
        Ver Todos <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
    <?php include __DIR__ . '/app/views/public/grid_vitrine.php'; ?>
  </div>
</section>
<?php endif; ?> -->

<!-- ═══════════════════════════════════════════════
     CTA PARCEIROS
════════════════════════════════════════════════ -->
<section class="section-cta py-5">
  <div class="container">
    <div class="cta-box">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <h3 class="cta-title">Sua empresa acredita em impacto?</h3>
          <p class="cta-sub mb-0">
            Torne-se um Parceiro de Impacto e conecte sua marca a negócios que transformam comunidades em todo o Brasil.
          </p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="/parceiros/cadastro.php" class="btn-cta-parceiro">
            <i class="bi bi-diagram-3-fill me-2"></i> Quero ser Parceiro
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($parceirosGrid)): ?>
<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
      <div>
        <h2 class="section-title mb-1">Parceiros que fortalecem o ecossistema</h2>
        <p class="section-sub mb-0">Organizações que caminham com a Impactos Positivos na construção de conexões e transformação.</p>
      </div>

      <a href="/parceiros.php" class="btn-vitrine-ver-todos">
        Ver Perfis dos Parceiros <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <?php include __DIR__ . '/app/views/public/grid_parceiros.php'; ?>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>