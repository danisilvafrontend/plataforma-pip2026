<?php
// /home/dscria59_dani/public_html/index.php
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
<section class="hero-home hero-home--nova">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <span class="hero-badge mb-3">Plataforma de Impacto no Brasil</span>
        <h1 class="hero-title mb-3">
          Conectamos negócios de impacto, parceiros e sociedade civil em uma plataforma de visibilidade, reconhecimento e transformação.
        </h1>
        <p class="hero-sub mb-4">
          A Impactos Positivos fortalece o ecossistema de impacto no Brasil ao reunir iniciativas transformadoras, organizações parceiras e pessoas que querem acompanhar, apoiar e ampliar resultados positivos nos territórios.
        </p>

        <div class="d-flex flex-wrap gap-2">
          <a href="/empreendedores/register.php" class="btn-hero-primary">Cadastrar negócio</a>
          <a href="/parceiros/cadastro.php" class="btn-hero-outline">Quero ser parceiro</a>
          <a href="vitrine_nacional.php" class="btn-hero-link">Explorar iniciativas</a>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="hero-panel-novo">
          <span class="hero-panel-kicker">Impactos Positivos</span>
          <ul class="hero-panel-list">
            <li>Visibilidade para iniciativas transformadoras</li>
            <li>Conexões entre negócios, parceiros e comunidade</li>
            <li>Reconhecimento e fortalecimento do ecossistema</li>
            <li>Atuação alinhada aos ODS</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="participar-home py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Cada perfil tem um papel na transformação</h2>
      <p class="section-sub mx-auto">
        A plataforma foi construída para públicos diferentes, com jornadas específicas e complementares dentro do ecossistema de impacto.
      </p>
    </div>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="participar-card participar-card--empreendedor">
          <span class="participar-tag">Empreendedores</span>
          <h3>Cadastre seu negócio de impacto</h3>
          <p>Apresente sua trajetória, seus diferenciais, ODS, impacto gerado e amplie sua visibilidade nacional.</p>
          <a href="/empreendedores/register.php" class="btn-participar">Criar conta de empreendedor</a>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="participar-card participar-card--parceiro">
          <span class="participar-tag">Parceiros</span>
          <h3>Conecte sua organização ao ecossistema</h3>
          <p>Fortaleça sua atuação institucional e aproxime sua marca de iniciativas que transformam territórios.</p>
          <a href="/parceiros/cadastro.php" class="btn-participar">Tornar-se parceiro</a>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="participar-card participar-card--sociedade">
          <span class="participar-tag">Sociedade civil</span>
          <h3>Acompanhe, descubra e participe</h3>
          <p>Conheça negócios e iniciativas inspiradoras, acompanhe histórias e participe do movimento de impacto positivo.</p>
          <a href="cadastro.php" class="btn-participar">Acessar comunidade</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== COMO FUNCIONA ===== -->
<section class="como-funciona-home py-5">
  <div class="container">

    <div class="text-center mb-5">
      <h2 class="section-title">Uma jornada simples para fazer parte</h2>
      <p class="section-sub mx-auto">
        Cada perfil tem um caminho direto dentro da plataforma. Veja como é fácil começar.
      </p>
    </div>

    <div class="row g-4">

      <!-- Empreendedor -->
      <div class="col-lg-4">
        <div class="como-bloco">
          <div class="como-bloco-header como-bloco-header--empreendedor">
            <i class="bi bi-rocket-takeoff"></i>
            <span>Empreendedor</span>
          </div>
          <ul class="como-bloco-steps">
            <li>
              <span class="como-num">1</span>
              <span>Crie sua conta na plataforma</span>
            </li>
            <li>
              <span class="como-num">2</span>
              <span>Preencha os dados do seu negócio de impacto</span>
            </li>
            <li>
              <span class="como-num">3</span>
              <span>Publique sua iniciativa e ganhe visibilidade nacional</span>
            </li>
          </ul>
          <a href="/empreendedores/register.php" class="btn-participar mt-auto">
            Cadastrar negócio
          </a>
        </div>
      </div>

      <!-- Parceiro -->
      <div class="col-lg-4">
        <div class="como-bloco como-bloco--destaque">
          <div class="como-bloco-header como-bloco-header--parceiro">
            <i class="bi bi-building"></i>
            <span>Parceiro</span>
          </div>
          <ul class="como-bloco-steps">
            <li>
              <span class="como-num como-num--parceiro">1</span>
              <span>Realize o cadastro institucional</span>
            </li>
            <li>
              <span class="como-num como-num--parceiro">2</span>
              <span>Apresente sua atuação e interesses</span>
            </li>
            <li>
              <span class="como-num como-num--parceiro">3</span>
              <span>Conecte-se ao ecossistema de impacto</span>
            </li>
          </ul>
          <a href="/parceiros/cadastro.php" class="btn-participar btn-participar--alt mt-auto">
            Tornar-se parceiro
          </a>
        </div>
      </div>

      <!-- Sociedade -->
      <div class="col-lg-4">
        <div class="como-bloco">
          <div class="como-bloco-header como-bloco-header--sociedade">
            <i class="bi bi-people"></i>
            <span>Sociedade civil</span>
          </div>
          <ul class="como-bloco-steps">
            <li>
              <span class="como-num">1</span>
              <span>Acesse a plataforma gratuitamente</span>
            </li>
            <li>
              <span class="como-num">2</span>
              <span>Descubra iniciativas e causas transformadoras</span>
            </li>
            <li>
              <span class="como-num">3</span>
              <span>Acompanhe e fortaleça o movimento de impacto</span>
            </li>
          </ul>
          <a href="/cadastro.php" class="btn-participar mt-auto">
            Acessar comunidade
          </a>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ════ BLOCO: VITRINE + PREMIAÇÃO ════ -->
<section class="vitrine-home py-5">
  <div class="container">

    <!-- Cabeçalho da seção -->
    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
      <div>
        <span class="section-kicker section-kicker--accent">Ecossistema de impacto</span>
        <h2 class="section-title mt-2 mb-1">Conheça as iniciativas cadastradas</h2>
        <p class="section-sub mb-0">
          Negócios reais que geram transformação social, ambiental e econômica em todo o Brasil.
        </p>
      </div>
      <a href="/vitrine.php" class="btn-vitrine-ver-todos">
        Ver todas as iniciativas <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
    <?php if (!empty($negociosDestaque)): ?>
    <!-- Grid de iniciativas -->
      <?php include __DIR__ . '/app/views/public/grid_vitrine.php'; ?>
    <?php endif; ?>

    <!-- Chamada discreta para a premiação -->
    <div class="premiacao-chamada mt-5">
      <div class="premiacao-chamada-inner">
        <div class="premiacao-chamada-icon">
          <i class="bi bi-trophy-fill"></i>
        </div>
        <div class="premiacao-chamada-texto">
          <strong>Prêmio Impactos Positivos <?= date('Y') ?> — Inscrições abertas</strong>
          <span>Negócios cadastrados na plataforma podem se inscrever e concorrer ao reconhecimento nacional.</span>
        </div>
        <a href="premiacao.php" class="premiacao-chamada-btn">
          Saiba mais <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>

  </div>
</section>
<!-- ════ FIM: VITRINE + PREMIAÇÃO ════ -->


<!-- ════ BLOCO: PARCEIROS ════ -->
<?php if (!empty($parceirosGrid)): ?>
<section class="parceiros-home py-5">
  <div class="container">

    <div class="parceiros-header mb-4">
      <!-- Linha 1: kicker + título + botão -->
      <div class="parceiros-header-top">
        <div>
          <span class="section-kicker section-kicker--accent">Rede de parceiros</span>
          <h2 class="section-title mt-2 mb-0">Organizações que fortalecem o ecossistema</h2>
        </div>
        <a href="/parceiros.php" class="btn-vitrine-ver-todos flex-shrink-0">
          Ver perfis dos parceiros <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
      <!-- Linha 2: parágrafo, sem disputar espaço com o botão -->
      <p class="section-sub mt-2 mb-0">
        Empresas, institutos e organizações que acreditam no poder das iniciativas de impacto positivo e caminham junto com a plataforma para ampliar conexões, visibilidade e transformação real nos territórios.
      </p>
    </div>

    <?php include __DIR__ . '/app/views/public/grid_parceiros.php'; ?>

  </div>
</section>
<?php endif; ?>
<!-- ════ FIM: PARCEIROS ════ -->

<!-- ════ BLOCO: CTA FINAL ════ -->
<section class="cta-final-home py-5">
  <div class="container">
    <div class="cta-final-inner">

      <div class="cta-final-grafismo" aria-hidden="true"></div>

      <div class="text-center position-relative">
        <span class="section-kicker section-kicker--claro mb-3 d-inline-block">
          Próximo passo
        </span>
        <h2 class="cta-final-title mb-3">
          Escolha como fazer parte do movimento
        </h2>
        <p class="cta-final-sub mx-auto mb-5">
          Cada ação dentro da plataforma fortalece o ecossistema de impacto positivo no Brasil. Qual é o seu papel?
        </p>

        <div class="cta-final-cards">

          <div class="cta-final-card">
            <i class="bi bi-buildings-fill cta-final-card-icon"></i>
            <h4>Sou parceiro</h4>
            <p>Conecte sua organização a iniciativas transformadoras.</p>
            <a href="/parceiros/cadastro.php" class="btn-participar">
              Ser parceiro <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>

          <div class="cta-final-card cta-final-card--destaque">
            <!-- <div class="cta-final-card-badge">Mais popular</div> -->            
            <i class="bi bi-rocket-takeoff-fill cta-final-card-icon"></i>
            <h4>Sou empreendedor</h4>
            <p>Cadastre seu negócio e ganhe visibilidade nacional.</p>
            <a href="/empreendedores/register.php" class="btn-premiacao-primary">
              Criar conta <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>

          <div class="cta-final-card">
            <i class="bi bi-people-fill cta-final-card-icon"></i>
            <h4>Quero acompanhar</h4>
            <p>Explore iniciativas e vote no Prêmio Impactos Positivos.</p>
            <a href="vitrine_nacional.php" class="btn-participar btn-participar--sociedade">
              Explorar vitrine <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>

        </div>
      </div>

    </div>
  </div>
</section>
<!-- ════ FIM: CTA FINAL ════ -->

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>