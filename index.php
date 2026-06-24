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
        LIMIT 6";
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
     HERO — Vídeo YouTube
════════════════════════════════════════════════ -->
<section class="hero-video-home">

  <div class="hero-video-overlay" aria-hidden="true"></div>

  <div class="hero-video-bg" aria-hidden="true">
    <video 
      class="hero-bg-video" 
      autoplay 
      muted 
      loop 
      playsinline 
      preload="auto"
      poster="/assets/image/encontro2026.webp">
      <source src="/assets/video/encontro_2022.mp4" type="video/mp4">
      Seu navegador não suporta vídeo em HTML5.
    </video>
  </div>

  <div class="hero-video-content">
    <span class="hero-video-hashtag">#ImpactosPositivos</span>

    <h1 class="hero-video-title">
      Juntos, ampliamos o que o mundo tem de melhor.
    </h1>

    <p class="hero-video-sub">
      Conectamos negócios, parceiros e pessoas que estão transformando a economia por meio do Impacto Positivo
    </p>

    <div class="hero-video-btns">
      <a href="/empreendedores/register.php" class="hero-btn hero-btn--yellow">
        CADASTRAR MEU NEGÓCIO <i class="bi bi-chevron-right"></i>
      </a>
      <a href="/parceiros/cadastro.php" class="hero-btn hero-btn--outline-white">
        QUERO SER PARCEIRO <i class="bi bi-chevron-right"></i>
      </a>
    </div>
  </div>

</section>

<!-- ═══════════════════════════════════════════════
     FAIXA DE URGÊNCIA — Premiação 2026
     Exibida logo abaixo do Hero
════════════════════════════════════════════════ -->
<div class="pip-urgency-bar" role="banner" aria-label="Alerta de prazo — Premiação 2026">
  <div class="pip-urgency-inner">

    <div class="pip-urgency-left">
      <span class="pip-urgency-fire">🏆</span>
      <div class="pip-urgency-text">
        <strong>Prêmio Impactos Positivos 2026</strong>
        <span>As inscrições encerram em breve. Garanta a sua vaga!</span>
      </div>
    </div>

    <div class="pip-urgency-center" aria-live="polite" aria-label="Contador regressivo">
      <div class="pip-countdown">
        <div class="pip-countdown-unit">
          <span class="pip-countdown-num" id="pip-cd-days">--</span>
          <span class="pip-countdown-label">dias</span>
        </div>
        <span class="pip-countdown-sep">:</span>
        <div class="pip-countdown-unit">
          <span class="pip-countdown-num" id="pip-cd-hours">--</span>
          <span class="pip-countdown-label">horas</span>
        </div>
        <span class="pip-countdown-sep">:</span>
        <div class="pip-countdown-unit">
          <span class="pip-countdown-num" id="pip-cd-mins">--</span>
          <span class="pip-countdown-label">min</span>
        </div>
        <span class="pip-countdown-sep">:</span>
        <div class="pip-countdown-unit">
          <span class="pip-countdown-num" id="pip-cd-secs">--</span>
          <span class="pip-countdown-label">seg</span>
        </div>
      </div>
    </div>

    <div class="pip-urgency-right">
      <a href="/premiacao.php" class="pip-urgency-btn pip-urgency-btn--primary">
        Inscrever-me agora <i class="bi bi-arrow-right ms-1"></i>
      </a>
      <a href="/premiacao.php#como-funciona" class="pip-urgency-btn pip-urgency-btn--ghost">
        Como funciona
      </a>
    </div>

  </div>
</div>

<style>
/* ── Faixa de Urgência — Premiação ── */
.pip-urgency-bar {
  background: linear-gradient(90deg, #0c4e54 0%, #01696f 60%, #0a7a40 100%);
  color: #fff;
  padding: 0.85rem 1.5rem;
  position: relative;
  overflow: hidden;
}
.pip-urgency-bar::before {
  content: '';
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    -45deg,
    transparent,
    transparent 18px,
    rgba(255,255,255,0.03) 18px,
    rgba(255,255,255,0.03) 36px
  );
  pointer-events: none;
}
.pip-urgency-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.25rem;
  flex-wrap: wrap;
  position: relative;
  z-index: 1;
}
.pip-urgency-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1 1 220px;
}
.pip-urgency-fire {
  font-size: 1.6rem;
  flex-shrink: 0;
  filter: drop-shadow(0 0 6px rgba(255,200,0,0.5));
}
.pip-urgency-text {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}
.pip-urgency-text strong {
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.01em;
  line-height: 1.2;
}
.pip-urgency-text span {
  font-size: 0.78rem;
  opacity: 0.85;
  line-height: 1.3;
}

/* Countdown */
.pip-urgency-center {
  flex-shrink: 0;
}
.pip-countdown {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  background: rgba(0,0,0,0.22);
  border-radius: 8px;
  padding: 0.4rem 0.85rem;
}
.pip-countdown-unit {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 38px;
}
.pip-countdown-num {
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.02em;
}
.pip-countdown-label {
  font-size: 0.6rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  opacity: 0.7;
  margin-top: 2px;
}
.pip-countdown-sep {
  font-size: 1.3rem;
  font-weight: 800;
  opacity: 0.5;
  align-self: flex-start;
  margin-top: 2px;
  line-height: 1.1;
}

/* Botões */
.pip-urgency-right {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-shrink: 0;
  flex-wrap: wrap;
}
.pip-urgency-btn {
  display: inline-flex;
  align-items: center;
  font-size: 0.82rem;
  font-weight: 700;
  padding: 0.5rem 1.1rem;
  border-radius: 6px;
  text-decoration: none;
  white-space: nowrap;
  transition: all 0.18s;
  letter-spacing: 0.01em;
}
.pip-urgency-btn--primary {
  background: #f5c842;
  color: #0c4e54;
}
.pip-urgency-btn--primary:hover {
  background: #ffd900;
  color: #0c4e54;
  transform: translateY(-1px);
}
.pip-urgency-btn--ghost {
  background: rgba(255,255,255,0.12);
  color: #fff;
  border: 1px solid rgba(255,255,255,0.3);
}
.pip-urgency-btn--ghost:hover {
  background: rgba(255,255,255,0.22);
  color: #fff;
}

@media (max-width: 768px) {
  .pip-urgency-inner { flex-direction: column; align-items: flex-start; gap: 0.85rem; }
  .pip-urgency-center { width: 100%; display: flex; justify-content: center; }
  .pip-urgency-right { width: 100%; justify-content: center; }
  .pip-countdown-num { font-size: 1.25rem; }
}
@media (max-width: 480px) {
  .pip-urgency-bar { padding: 0.85rem 1rem; }
  .pip-urgency-btn { font-size: 0.78rem; padding: 0.45rem 0.85rem; }
}
</style>

<script>
(function () {
  // Ajuste a data de encerramento conforme necessário
  var deadline = new Date('2026-07-31T23:59:59-03:00').getTime();

  function pad(n) { return String(n).padStart(2, '0'); }

  function tick() {
    var now  = Date.now();
    var diff = deadline - now;

    if (diff <= 0) {
      document.getElementById('pip-cd-days').textContent  = '00';
      document.getElementById('pip-cd-hours').textContent = '00';
      document.getElementById('pip-cd-mins').textContent  = '00';
      document.getElementById('pip-cd-secs').textContent  = '00';
      return;
    }

    var days  = Math.floor(diff / 86400000);
    var hours = Math.floor((diff % 86400000) / 3600000);
    var mins  = Math.floor((diff % 3600000)  / 60000);
    var secs  = Math.floor((diff % 60000)    / 1000);

    document.getElementById('pip-cd-days').textContent  = pad(days);
    document.getElementById('pip-cd-hours').textContent = pad(hours);
    document.getElementById('pip-cd-mins').textContent  = pad(mins);
    document.getElementById('pip-cd-secs').textContent  = pad(secs);
  }

  tick();
  setInterval(tick, 1000);
})();
</script>

<!-- ════════════════════════════════════════════════
     SEÇÃO — Apoio Institucional
════════════════════════════════════════════════ -->
<section class="apoio-institucional">
  <div class="apoio-institucional__inner">

    <h2 class="apoio-institucional__titulo">
      Apoio Institucional
    </h2>

    <ul class="apoio-institucional__grid">

      <!-- Logo 1 -->
      <li class="apoio-institucional__item">
        <img
          src="/assets/images/apoio/mdic.png"
          alt="Ministério do Desenvolvimento, Indústria, Comércio e Serviços — Governo Federal"
          class="apoio-institucional__logo"
          loading="lazy"
          width="200"
          height="100"
        >
      </li>

      <!-- Logo 2 -->
      <li class="apoio-institucional__item">
        <img
          src="/assets/images/apoio/enimpacto.webp"
          alt="ENIMPACTO — Estratégia Nacional de Economia de Impacto"
          class="apoio-institucional__logo"
          loading="lazy"
          width="200"
          height="100"
        >
      </li>

      <!-- Logo 3 -->
      <li class="apoio-institucional__item">
        <img
          src="/assets/images/apoio/cadimpacto.png"
          alt="CADIMPACTO — Cadastro Nacional de Empreendimentos de Impacto"
          class="apoio-institucional__logo"
          loading="lazy"
          width="200"
          height="100"
        >
      </li>

      <!-- Logo 4 -->
      <li class="apoio-institucional__item">
        <img
          src="/assets/images/apoio/yunus.webp"
          alt="Yunus Negócios Sociais Brasil"
          class="apoio-institucional__logo"
          loading="lazy"
          width="200"
          height="100"
        >
      </li>

      <!-- Logo 5 -->
      <li class="apoio-institucional__item">
        <img
          src="/assets/images/apoio/capitalismo-consciente.webp"
          alt="Capitalismo Consciente Brasil"
          class="apoio-institucional__logo"
          loading="lazy"
          width="200"
          height="100"
        >
      </li>

      <!-- Logo 6 -->
      <li class="apoio-institucional__item">
        <img
          src="/assets/images/apoio/alianca.webp"
          alt="Aliança pelos Investimentos e Negócios de Impacto"
          class="apoio-institucional__logo"
          loading="lazy"
          width="200"
          height="100"
        >
      </li>

    </ul>
  </div>
</section>


<!-- ════ BLOCO: VITRINE + PREMIAÇÃO ════ -->
<section class="vitrine-home py-5">
  <div class="container">

    <!-- Cabeçalho da seção -->
    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
      <div>
        <span class="section-kicker section-kicker--accent">Ecossistema de impacto</span>
        <h2 class="section-title mt-2 mb-1">Vitrine de Negócios de Impacto</h2>
        <p class="section-sub mb-0">
          Negócios reais que geram transformação social, ambiental e econômica em todo o Brasil.
        </p>
      </div>
      <a href="/vitrine_de_impacto.php" class="btn-vitrine-ver-todos">
        Ver todos os negócios <i class="bi bi-arrow-right ms-1"></i>
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


<!-- ════════════════════════════════════════════════
     SEÇÃO DESTAQUE — Prêmio Impactos Positivos 2026
     Posicionada antes do footer para máxima conversão
════════════════════════════════════════════════ -->
<section class="pip-award-section" aria-label="Prêmio Impactos Positivos 2026">
  <div class="pip-award-bg-deco" aria-hidden="true"></div>
  <div class="container pip-award-inner">

    <!-- Coluna de texto -->
    <div class="pip-award-copy">
      <span class="pip-award-kicker">
        <i class="bi bi-trophy-fill me-1"></i> Premiação 2026
      </span>
      <h2 class="pip-award-title">
        Seu negócio merece<br>reconhecimento nacional.
      </h2>
      <p class="pip-award-sub">
        O <strong>Prêmio Impactos Positivos 2026</strong> celebra iniciativas que provam que é possível gerar impacto real — social, ambiental e econômico — transformando territórios e vidas em todo o Brasil.
      </p>

      <ul class="pip-award-bullets">
        <li><i class="bi bi-check-circle-fill"></i> Reconhecimento e visibilidade nacional</li>
        <li><i class="bi bi-check-circle-fill"></i> Avaliação por bancas especializadas</li>
        <li><i class="bi bi-check-circle-fill"></i> Gratuito para negócios cadastrados na plataforma</li>
        <li><i class="bi bi-check-circle-fill"></i> Inscrições encerram em <strong>julho de 2026</strong></li>
      </ul>

      <div class="pip-award-btns">
        <a href="/premiacao.php" class="pip-award-btn pip-award-btn--primary">
          <i class="bi bi-trophy me-1"></i> Quero me inscrever
        </a>
        <a href="/premiacao.php#criterios" class="pip-award-btn pip-award-btn--outline">
          Ver critérios de avaliação
        </a>
        <a href="/empreendedores/register.php" class="pip-award-btn pip-award-btn--ghost">
          Ainda não tenho conta <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>

    <!-- Coluna visual — card contagem regressiva -->
    <div class="pip-award-card">
      <div class="pip-award-card-header">
        <i class="bi bi-hourglass-split pip-award-card-icon"></i>
        <span>Inscrições encerram em</span>
      </div>
      <div class="pip-award-countdown" aria-live="polite">
        <div class="pip-award-cd-unit">
          <span class="pip-award-cd-num" id="pip-awd-days">--</span>
          <span class="pip-award-cd-lbl">dias</span>
        </div>
        <span class="pip-award-cd-sep">:</span>
        <div class="pip-award-cd-unit">
          <span class="pip-award-cd-num" id="pip-awd-hours">--</span>
          <span class="pip-award-cd-lbl">horas</span>
        </div>
        <span class="pip-award-cd-sep">:</span>
        <div class="pip-award-cd-unit">
          <span class="pip-award-cd-num" id="pip-awd-mins">--</span>
          <span class="pip-award-cd-lbl">min</span>
        </div>
        <span class="pip-award-cd-sep">:</span>
        <div class="pip-award-cd-unit">
          <span class="pip-award-cd-num" id="pip-awd-secs">--</span>
          <span class="pip-award-cd-lbl">seg</span>
        </div>
      </div>
      <div class="pip-award-card-divider"></div>
      <p class="pip-award-card-note">
        Negócios já cadastrados na plataforma têm acesso direto à inscrição.
      </p>
      <a href="/premiacao.php" class="pip-award-card-cta">
        Acessar página da premiação <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

  </div>
</section>

<style>
/* ── Seção Prêmio Impactos Positivos 2026 ── */
.pip-award-section {
  background: linear-gradient(135deg, #072e32 0%, #0c4e54 45%, #093d28 100%);
  color: #fff;
  padding: 5rem 1.5rem;
  position: relative;
  overflow: hidden;
}
.pip-award-bg-deco {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse 60% 50% at 80% 20%, rgba(245,200,66,0.08) 0%, transparent 70%),
    radial-gradient(ellipse 40% 60% at 10% 80%, rgba(1,105,111,0.35) 0%, transparent 60%),
    repeating-linear-gradient(
      -45deg,
      transparent, transparent 30px,
      rgba(255,255,255,0.015) 30px, rgba(255,255,255,0.015) 60px
    );
  pointer-events: none;
}
.pip-award-inner {
  display: flex;
  align-items: center;
  gap: 4rem;
  position: relative;
  z-index: 1;
  flex-wrap: wrap;
}
.pip-award-copy {
  flex: 1 1 380px;
}
.pip-award-kicker {
  display: inline-flex;
  align-items: center;
  background: rgba(245,200,66,0.15);
  color: #f5c842;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  padding: 0.3rem 0.85rem;
  border-radius: 20px;
  border: 1px solid rgba(245,200,66,0.3);
  margin-bottom: 1.1rem;
}
.pip-award-title {
  font-size: clamp(1.75rem, 3.5vw, 2.6rem);
  font-weight: 800;
  line-height: 1.15;
  margin-bottom: 1rem;
  letter-spacing: -0.02em;
}
.pip-award-sub {
  font-size: 1rem;
  line-height: 1.65;
  opacity: 0.88;
  margin-bottom: 1.5rem;
  max-width: 480px;
}
.pip-award-bullets {
  list-style: none;
  padding: 0;
  margin: 0 0 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}
.pip-award-bullets li {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.92rem;
  opacity: 0.9;
}
.pip-award-bullets li i {
  color: #4ade80;
  font-size: 0.9rem;
  flex-shrink: 0;
}
.pip-award-btns {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  align-items: center;
}
.pip-award-btn {
  display: inline-flex;
  align-items: center;
  font-size: 0.875rem;
  font-weight: 700;
  padding: 0.65rem 1.35rem;
  border-radius: 7px;
  text-decoration: none;
  transition: all 0.18s;
  white-space: nowrap;
  letter-spacing: 0.01em;
}
.pip-award-btn--primary {
  background: #f5c842;
  color: #0c4e54;
}
.pip-award-btn--primary:hover {
  background: #ffd900;
  color: #072e32;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(245,200,66,0.4);
}
.pip-award-btn--outline {
  background: transparent;
  color: #fff;
  border: 1.5px solid rgba(255,255,255,0.4);
}
.pip-award-btn--outline:hover {
  background: rgba(255,255,255,0.1);
  border-color: rgba(255,255,255,0.7);
  color: #fff;
}
.pip-award-btn--ghost {
  background: transparent;
  color: rgba(255,255,255,0.65);
  font-size: 0.82rem;
  font-weight: 500;
  padding: 0.65rem 0.5rem;
}
.pip-award-btn--ghost:hover {
  color: #fff;
}

/* Card contagem */
.pip-award-card {
  flex: 0 0 300px;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 16px;
  padding: 2rem 1.75rem;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
  align-items: center;
  text-align: center;
}
.pip-award-card-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  opacity: 0.8;
  font-size: 0.82rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
}
.pip-award-card-icon {
  font-size: 2rem;
  color: #f5c842;
}
.pip-award-countdown {
  display: flex;
  align-items: center;
  gap: 0.3rem;
}
.pip-award-cd-unit {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 52px;
  background: rgba(0,0,0,0.25);
  border-radius: 8px;
  padding: 0.5rem 0.4rem 0.35rem;
}
.pip-award-cd-num {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.02em;
  color: #f5c842;
}
.pip-award-cd-lbl {
  font-size: 0.58rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  opacity: 0.6;
  margin-top: 3px;
}
.pip-award-cd-sep {
  font-size: 1.6rem;
  font-weight: 800;
  opacity: 0.4;
  align-self: flex-start;
  margin-top: 4px;
  line-height: 1.1;
}
.pip-award-card-divider {
  width: 100%;
  height: 1px;
  background: rgba(255,255,255,0.12);
}
.pip-award-card-note {
  font-size: 0.8rem;
  opacity: 0.7;
  line-height: 1.5;
  margin: 0;
}
.pip-award-card-cta {
  display: inline-flex;
  align-items: center;
  background: #f5c842;
  color: #0c4e54;
  font-size: 0.82rem;
  font-weight: 700;
  padding: 0.6rem 1.2rem;
  border-radius: 7px;
  text-decoration: none;
  transition: all 0.18s;
  width: 100%;
  justify-content: center;
}
.pip-award-card-cta:hover {
  background: #ffd900;
  color: #072e32;
  transform: translateY(-1px);
}

@media (max-width: 900px) {
  .pip-award-inner { gap: 2.5rem; }
  .pip-award-card  { flex: 1 1 100%; max-width: 420px; margin: 0 auto; }
}
@media (max-width: 600px) {
  .pip-award-section { padding: 3.5rem 1rem; }
  .pip-award-title   { font-size: 1.75rem; }
  .pip-award-btns    { flex-direction: column; align-items: stretch; }
  .pip-award-btn     { justify-content: center; }
  .pip-award-cd-num  { font-size: 1.6rem; }
  .pip-award-cd-unit { min-width: 42px; }
}
</style>

<script>
(function () {
  var deadline = new Date('2026-07-31T23:59:59-03:00').getTime();

  function pad(n) { return String(n).padStart(2, '0'); }

  function tickAward() {
    var diff = deadline - Date.now();
    if (diff <= 0) {
      ['days','hours','mins','secs'].forEach(function(id) {
        document.getElementById('pip-awd-' + id).textContent = '00';
      });
      return;
    }
    document.getElementById('pip-awd-days').textContent  = pad(Math.floor(diff / 86400000));
    document.getElementById('pip-awd-hours').textContent = pad(Math.floor((diff % 86400000) / 3600000));
    document.getElementById('pip-awd-mins').textContent  = pad(Math.floor((diff % 3600000)  / 60000));
    document.getElementById('pip-awd-secs').textContent  = pad(Math.floor((diff % 60000)    / 1000));
  }

  tickAward();
  setInterval(tickAward, 1000);
})();
</script>
<!-- ════ FIM: SEÇÃO DESTAQUE PREMIAÇÃO ════ -->


<!-- ════════════════════════════════════════════════
     POPUP — PIP Insights
     Aparece automaticamente ao entrar na home.
     Fecha ao clicar no X, no botão ou fora do modal.
     sessionStorage impede reexibição na mesma sessão.
════════════════════════════════════════════════ -->
<div id="pip-insights-popup" role="dialog" aria-modal="true" aria-labelledby="pip-insights-title" style="display:none;">
  <div class="pip-popup-backdrop" id="pip-popup-backdrop"></div>
  <div class="pip-popup-box">

    <button class="pip-popup-close" id="pip-popup-close" aria-label="Fechar popup">
      <i class="bi bi-x-lg"></i>
    </button>

    <a href="https://blog.impactospositivos.com/pip-insights-como-ser-referencia-para-as-pessoas-proximas/"
       target="_blank"
       rel="noopener noreferrer"
       class="pip-popup-link"
       aria-label="Leia o artigo: PIP Insights — Como ser referência para as pessoas próximas?">
      <img
        src="/assets/images/insights-09-jun.jfif"
        alt="PIP Insights — Como ser referência para as pessoas próximas?"
        class="pip-popup-img"
        width="600"
        height="400"
        loading="eager"
      >
    </a>

    <div class="pip-popup-footer">
      <p id="pip-insights-title" class="pip-popup-label">PIP Insights</p>
      <a href="https://blog.impactospositivos.com/pip-insights-como-ser-referencia-para-as-pessoas-proximas/"
         target="_blank"
         rel="noopener noreferrer"
         class="pip-popup-btn">
        Como ser referência para as pessoas próximas? <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

  </div>
</div>

<style>
/* ── Popup PIP Insights ── */
#pip-insights-popup {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex !important;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
#pip-insights-popup.pip-hidden { display: none !important; }

.pip-popup-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.60);
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(3px);
  cursor: pointer;
}

.pip-popup-box {
  position: relative;
  z-index: 1;
  background: #fff;
  border-radius: 12px;
  max-width: 520px;
  width: 100%;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.35);
  animation: pipFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes pipFadeIn {
  from { opacity: 0; transform: scale(0.92) translateY(16px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}

.pip-popup-close {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 2;
  background: rgba(0,0,0,0.55);
  color: #fff;
  border: none;
  border-radius: 50%;
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1rem;
  transition: background 0.18s;
}
.pip-popup-close:hover { background: rgba(0,0,0,0.80); }

.pip-popup-link { display: block; line-height: 0; }

.pip-popup-img {
  width: 100%;
  height: auto;
  display: block;
  object-fit: cover;
  transition: opacity 0.2s;
}
.pip-popup-link:hover .pip-popup-img { opacity: 0.92; }

.pip-popup-footer {
  padding: 1rem 1.25rem 1.25rem;
  background: #fff;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.pip-popup-label {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #01696f;
  margin: 0;
}

.pip-popup-btn {
  display: inline-flex;
  align-items: center;
  background: #01696f;
  color: #fff;
  font-size: 0.875rem;
  font-weight: 600;
  padding: 0.55rem 1.1rem;
  border-radius: 6px;
  text-decoration: none;
  align-self: flex-start;
  transition: background 0.18s;
}
.pip-popup-btn:hover { background: #0c4e54; color: #fff; }

@media (max-width: 480px) {
  .pip-popup-box { border-radius: 10px; }
  .pip-popup-footer { padding: 0.875rem 1rem 1rem; }
}

@media (prefers-reduced-motion: reduce) {
  .pip-popup-box { animation: none; }
}
</style>

<script>
(function () {
  var STORAGE_KEY = 'pip_insights_popup_seen';
  var popup       = document.getElementById('pip-insights-popup');
  var closeBtn    = document.getElementById('pip-popup-close');
  var backdrop    = document.getElementById('pip-popup-backdrop');

  function closePopup() {
    popup.classList.add('pip-hidden');
    try { sessionStorage.setItem(STORAGE_KEY, '1'); } catch(e) {}
  }

  // Exibe se ainda não foi fechado nesta sessão
  var seen = false;
  try { seen = !!sessionStorage.getItem(STORAGE_KEY); } catch(e) {}

  if (!seen) {
    popup.classList.remove('pip-hidden');
  }

  closeBtn.addEventListener('click', closePopup);
  backdrop.addEventListener('click', closePopup);

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePopup();
  });
})();
</script>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>
