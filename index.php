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
     HERO — Vídeo
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
════════════════════════════════════════════════ -->
<div class="pip-urgency-bar" role="banner" aria-label="Alerta de prazo — Premiação 2026">
  <div class="pip-urgency-inner">

    <div class="pip-urgency-left">
      <div class="pip-urgency-icon-wrap" aria-hidden="true">
        <i class="bi bi-trophy-fill"></i>
      </div>
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
        <span class="pip-countdown-sep" aria-hidden="true">:</span>
        <div class="pip-countdown-unit">
          <span class="pip-countdown-num" id="pip-cd-hours">--</span>
          <span class="pip-countdown-label">horas</span>
        </div>
        <span class="pip-countdown-sep" aria-hidden="true">:</span>
        <div class="pip-countdown-unit">
          <span class="pip-countdown-num" id="pip-cd-mins">--</span>
          <span class="pip-countdown-label">min</span>
        </div>
        <span class="pip-countdown-sep" aria-hidden="true">:</span>
        <div class="pip-countdown-unit">
          <span class="pip-countdown-num" id="pip-cd-secs">--</span>
          <span class="pip-countdown-label">seg</span>
        </div>
      </div>
    </div>

    <div class="pip-urgency-right">
      <a href="/premiacao.php" class="btn-premiacao-primary pip-urgency-btn-cta">
        Inscrever-me agora <i class="bi bi-arrow-right ms-1"></i>
      </a>
      <a href="/premiacao.php#como-funciona" class="btn-premiacao-outline pip-urgency-btn-cta">
        Como funciona
      </a>
    </div>

  </div>
</div>

<style>
/* ── Faixa de Urgência — full-bleed + brand colors ── */
.pip-urgency-bar {
  /* full-bleed: mesmo padrão de .hero-home e .como-funciona-home */
  margin-left:  calc(-50vw + 50%);
  margin-right: calc(-50vw + 50%);
  padding-left: calc(50vw - 50% + 1.5rem);
  padding-right: calc(50vw - 50% + 1.5rem);
  padding-top: 0.85rem;
  padding-bottom: 0.85rem;

  background: linear-gradient(90deg, #1E3425 0%, #2d5038 60%, #1E3425 100%);
  color: #fff;
  position: relative;
  overflow: hidden;
  border-bottom: 3px solid #CDDE00;
}
.pip-urgency-bar::before {
  content: '';
  position: absolute;
  inset: 0;
  background: url('/assets/images/aneis.png') no-repeat center/cover;
  opacity: 0.05;
  mix-blend-mode: luminosity;
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
  gap: 0.85rem;
  flex: 1 1 220px;
}
.pip-urgency-icon-wrap {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(205, 222, 0, 0.18);
  color: #CDDE00;
  font-size: 1.15rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.pip-urgency-text {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}
.pip-urgency-text strong {
  font-size: 0.95rem;
  font-weight: 700;
  color: #fff;
  line-height: 1.2;
}
.pip-urgency-text span {
  font-size: 0.78rem;
  color: rgba(255, 255, 255, 0.75);
  line-height: 1.3;
}
.pip-urgency-center { flex-shrink: 0; }
.pip-countdown {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  background: rgba(0, 0, 0, 0.25);
  border-radius: 10px;
  padding: 0.45rem 0.9rem;
  border: 1px solid rgba(205, 222, 0, 0.2);
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
  color: #CDDE00;
}
.pip-countdown-label {
  font-size: 0.58rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: rgba(255, 255, 255, 0.6);
  margin-top: 2px;
}
.pip-countdown-sep {
  font-size: 1.3rem;
  font-weight: 800;
  color: rgba(205, 222, 0, 0.5);
  align-self: flex-start;
  margin-top: 2px;
  line-height: 1.1;
}
.pip-urgency-right {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-shrink: 0;
  flex-wrap: wrap;
}
.pip-urgency-btn-cta {
  font-size: 0.82rem !important;
  padding: 0.5rem 1.15rem !important;
  border-radius: 999px !important;
}
@media (max-width: 768px) {
  .pip-urgency-bar {
    padding-left: calc(50vw - 50% + 1rem);
    padding-right: calc(50vw - 50% + 1rem);
  }
  .pip-urgency-inner { flex-direction: column; align-items: flex-start; gap: 0.85rem; }
  .pip-urgency-center { width: 100%; display: flex; justify-content: center; }
  .pip-urgency-right { width: 100%; justify-content: center; }
  .pip-countdown-num { font-size: 1.3rem; }
}
@media (max-width: 480px) {
  .pip-urgency-btn-cta { font-size: 0.78rem !important; padding: 0.45rem 0.9rem !important; }
}
</style>

<script>
(function () {
  var deadline = new Date('2026-07-31T23:59:59-03:00').getTime();
  function pad(n) { return String(n).padStart(2, '0'); }
  function tick() {
    var diff = deadline - Date.now();
    if (diff <= 0) {
      ['days','hours','mins','secs'].forEach(function(id){
        document.getElementById('pip-cd-' + id).textContent = '00';
      });
      return;
    }
    document.getElementById('pip-cd-days').textContent  = pad(Math.floor(diff / 86400000));
    document.getElementById('pip-cd-hours').textContent = pad(Math.floor((diff % 86400000) / 3600000));
    document.getElementById('pip-cd-mins').textContent  = pad(Math.floor((diff % 3600000)  / 60000));
    document.getElementById('pip-cd-secs').textContent  = pad(Math.floor((diff % 60000)    / 1000));
  }
  tick();
  setInterval(tick, 1000);
})();
</script>

<!-- ═══════════════════════════════════════════════
     SEÇÃO — Apoio Institucional
════════════════════════════════════════════════ -->
<section class="apoio-institucional">
  <div class="apoio-institucional__inner">

    <h2 class="apoio-institucional__titulo">
      Apoio Institucional
    </h2>

    <ul class="apoio-institucional__grid">
      <li class="apoio-institucional__item">
        <img src="/assets/images/apoio/mdic.png" alt="Ministério do Desenvolvimento, Indústria, Comércio e Serviços" class="apoio-institucional__logo" loading="lazy" width="200" height="100">
      </li>
      <li class="apoio-institucional__item">
        <img src="/assets/images/apoio/enimpacto.webp" alt="ENIMPACTO" class="apoio-institucional__logo" loading="lazy" width="200" height="100">
      </li>
      <li class="apoio-institucional__item">
        <img src="/assets/images/apoio/cadimpacto.png" alt="CADIMPACTO" class="apoio-institucional__logo" loading="lazy" width="200" height="100">
      </li>
      <li class="apoio-institucional__item">
        <img src="/assets/images/apoio/yunus.webp" alt="Yunus Negócios Sociais Brasil" class="apoio-institucional__logo" loading="lazy" width="200" height="100">
      </li>
      <li class="apoio-institucional__item">
        <img src="/assets/images/apoio/capitalismo-consciente.webp" alt="Capitalismo Consciente Brasil" class="apoio-institucional__logo" loading="lazy" width="200" height="100">
      </li>
      <li class="apoio-institucional__item">
        <img src="/assets/images/apoio/alianca.webp" alt="Aliança pelos Investimentos e Negócios de Impacto" class="apoio-institucional__logo" loading="lazy" width="200" height="100">
      </li>
    </ul>

  </div>
</section>


<!-- ════ BLOCO: VITRINE + PREMIAÇÃO ════ -->
<section class="vitrine-home py-5">
  <div class="container">

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
      <?php include __DIR__ . '/app/views/public/grid_vitrine.php'; ?>
    <?php endif; ?>

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
      <div class="parceiros-header-top">
        <div>
          <span class="section-kicker section-kicker--accent">Rede de parceiros</span>
          <h2 class="section-title mt-2 mb-0">Organizações que fortalecem o ecossistema</h2>
        </div>
        <a href="/parceiros.php" class="btn-vitrine-ver-todos flex-shrink-0">
          Ver perfis dos parceiros <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
      <p class="section-sub mt-2 mb-0">
        Empresas, institutos e organizações que acreditam no poder das iniciativas de impacto positivo e caminham junto com a plataforma para ampliar conexões, visibilidade e transformação real nos territórios.
      </p>
    </div>

    <?php include __DIR__ . '/app/views/public/grid_parceiros.php'; ?>

  </div>
</section>
<?php endif; ?>
<!-- ════ FIM: PARCEIROS ════ -->


<!-- ═══════════════════════════════════════════════
     SEÇÃO DESTAQUE — Prêmio Impactos Positivos 2026
════════════════════════════════════════════════ -->
<section class="premiacao-home premiacao-home--fullbleed py-5" aria-label="Prêmio Impactos Positivos 2026">
  <div class="container">
    <div class="premiacao-inner">

      <div class="premiacao-grafismo" aria-hidden="true"></div>

      <div class="row align-items-center g-5 position-relative">

        <!-- Coluna de texto -->
        <div class="col-lg-7">
          <span class="section-kicker section-kicker--claro mb-3">
            <i class="bi bi-trophy-fill me-1"></i> Premiação 2026
          </span>

          <h2 class="premiacao-title mt-3">
            Seu negócio merece<br>reconhecimento nacional.
          </h2>

          <p class="premiacao-sub mt-3">
            O <strong>Prêmio Impactos Positivos 2026</strong> celebra iniciativas que provam que é possível gerar impacto real — social, ambiental e econômico — transformando territórios e vidas em todo o Brasil.
          </p>

          <div class="premiacao-pilares mt-4">
            <div class="premiacao-pilar">
              <div class="premiacao-pilar-icon"><i class="bi bi-award-fill"></i></div>
              <div>
                <strong>Reconhecimento e visibilidade nacional</strong>
                <span>Seu negócio apresentado para o ecossistema de impacto do Brasil.</span>
              </div>
            </div>
            <div class="premiacao-pilar">
              <div class="premiacao-pilar-icon"><i class="bi bi-people-fill"></i></div>
              <div>
                <strong>Avaliação por bancas especializadas</strong>
                <span>Critérios claros de impacto, inovação e sustentabilidade.</span>
              </div>
            </div>
            <div class="premiacao-pilar">
              <div class="premiacao-pilar-icon"><i class="bi bi-check-circle-fill"></i></div>
              <div>
                <strong>Gratuito para negócios cadastrados</strong>
                <span>Inscrições encerram em <strong style="color:#CDDE00;">julho de 2026</strong>.</span>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-3 mt-4">
            <a href="/premiacao.php" class="btn-premiacao-primary">
              <i class="bi bi-trophy me-1"></i> Quero me inscrever
            </a>
            <a href="/premiacao.php#criterios" class="btn-premiacao-outline">
              Ver critérios de avaliação
            </a>
            <a href="/empreendedores/register.php" class="btn-premiacao-outline" style="border-color:rgba(255,255,255,.25); color:rgba(255,255,255,.65); font-size:.85rem;">
              Ainda não tenho conta <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>
        </div>

        <!-- Coluna card countdown -->
        <div class="col-lg-5">
          <div class="pip-cd-card">
            <div class="pip-cd-card-top">
              <i class="bi bi-hourglass-split"></i>
              <span>Inscrições encerram em</span>
            </div>

            <div class="pip-cd-countdown" aria-live="polite">
              <div class="pip-cd-unit">
                <span class="pip-cd-num" id="pip-awd-days">--</span>
                <span class="pip-cd-lbl">dias</span>
              </div>
              <span class="pip-cd-sep" aria-hidden="true">:</span>
              <div class="pip-cd-unit">
                <span class="pip-cd-num" id="pip-awd-hours">--</span>
                <span class="pip-cd-lbl">horas</span>
              </div>
              <span class="pip-cd-sep" aria-hidden="true">:</span>
              <div class="pip-cd-unit">
                <span class="pip-cd-num" id="pip-awd-mins">--</span>
                <span class="pip-cd-lbl">min</span>
              </div>
              <span class="pip-cd-sep" aria-hidden="true">:</span>
              <div class="pip-cd-unit">
                <span class="pip-cd-num" id="pip-awd-secs">--</span>
                <span class="pip-cd-lbl">seg</span>
              </div>
            </div>

            <hr class="pip-cd-divider">

            <p class="pip-cd-note">
              Negócios já cadastrados na plataforma têm acesso direto à inscrição.
            </p>
            <a href="/premiacao.php" class="btn-premiacao-primary w-100 justify-content-center">
              Acessar página da premiação <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<style>
/* ── Seção premiação — full-bleed ── */
.premiacao-home--fullbleed {
  /* full-bleed: mesmo padrão de .hero-home e .como-funciona-home */
  margin-left:  calc(-50vw + 50%);
  margin-right: calc(-50vw + 50%);
  padding-left: calc(50vw - 50%);
  padding-right: calc(50vw - 50%);
}

/* ── Card countdown ── */
.pip-cd-card {
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(205,222,0,.25);
  border-radius: 20px;
  padding: 2rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
  align-items: center;
  text-align: center;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}
.pip-cd-card-top {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  color: rgba(255,255,255,.7);
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
}
.pip-cd-card-top i {
  font-size: 2rem;
  color: #CDDE00;
}
.pip-cd-countdown {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.pip-cd-unit {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 56px;
  background: rgba(0,0,0,.28);
  border-radius: 10px;
  padding: 0.55rem 0.4rem 0.4rem;
  border: 1px solid rgba(205,222,0,.15);
}
.pip-cd-num {
  font-size: 2.2rem;
  font-weight: 800;
  line-height: 1;
  font-variant-numeric: tabular-nums;
  letter-spacing: -.03em;
  color: #CDDE00;
}
.pip-cd-lbl {
  font-size: 0.58rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: rgba(255,255,255,.55);
  margin-top: 3px;
}
.pip-cd-sep {
  font-size: 1.8rem;
  font-weight: 800;
  color: rgba(205,222,0,.4);
  align-self: flex-start;
  margin-top: 4px;
  line-height: 1.1;
}
.pip-cd-divider {
  width: 100%;
  border-color: rgba(255,255,255,.12);
  margin: 0;
}
.pip-cd-note {
  font-size: 0.82rem;
  color: rgba(255,255,255,.6);
  line-height: 1.5;
  margin: 0;
}
@media (max-width: 767.98px) {
  .pip-cd-card { padding: 1.5rem 1.25rem; }
  .pip-cd-num  { font-size: 1.75rem; }
  .pip-cd-unit { min-width: 46px; }
}
</style>
<!-- ════ FIM: SEÇÃO DESTAQUE PREMIAÇÃO ════ -->

<script>
(function () {
  var deadline = new Date('2026-07-31T23:59:59-03:00').getTime();
  function pad(n) { return String(n).padStart(2, '0'); }
  function tickAward() {
    var diff = deadline - Date.now();
    if (diff <= 0) {
      ['days','hours','mins','secs'].forEach(function(id){
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


<!-- ═══════════════════════════════════════════════
     POPUP — PIP Insights
════════════════════════════════════════════════ -->
<div id="pip-insights-popup" role="dialog" aria-modal="true" aria-labelledby="pip-insights-title" style="display:none;">
  <div class="pip-popup-backdrop" id="pip-popup-backdrop"></div>
  <div class="pip-popup-box">

    <button class="pip-popup-close" id="pip-popup-close" aria-label="Fechar popup">
      <i class="bi bi-x-lg"></i>
    </button>

    <a href="https://blog.impactospositivos.com/pip-insights-como-ser-referencia-para-as-pessoas-proximas/"
       target="_blank" rel="noopener noreferrer"
       class="pip-popup-link"
       aria-label="Leia o artigo: PIP Insights — Como ser referência para as pessoas próximas?">
      <img
        src="/assets/images/insights-09-jun.jfif"
        alt="PIP Insights — Como ser referência para as pessoas próximas?"
        class="pip-popup-img" width="600" height="400" loading="eager">
    </a>

    <div class="pip-popup-footer">
      <p id="pip-insights-title" class="pip-popup-label">PIP Insights</p>
      <a href="https://blog.impactospositivos.com/pip-insights-como-ser-referencia-para-as-pessoas-proximas/"
         target="_blank" rel="noopener noreferrer" class="pip-popup-btn">
        Como ser referência para as pessoas próximas? <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

  </div>
</div>

<style>
#pip-insights-popup {
  position: fixed; inset: 0; z-index: 9999;
  display: flex !important; align-items: center; justify-content: center; padding: 1rem;
}
#pip-insights-popup.pip-hidden { display: none !important; }
.pip-popup-backdrop {
  position: absolute; inset: 0;
  background: rgba(0,0,0,.60);
  backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px);
  cursor: pointer;
}
.pip-popup-box {
  position: relative; z-index: 1;
  background: #fff; border-radius: 12px;
  max-width: 520px; width: 100%; overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,.35);
  animation: pipFadeIn 0.35s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes pipFadeIn {
  from { opacity:0; transform: scale(.92) translateY(16px); }
  to   { opacity:1; transform: scale(1) translateY(0); }
}
.pip-popup-close {
  position:absolute; top:10px; right:10px; z-index:2;
  background:rgba(0,0,0,.55); color:#fff; border:none; border-radius:50%;
  width:34px; height:34px; display:flex; align-items:center; justify-content:center;
  cursor:pointer; font-size:1rem; transition:background .18s;
}
.pip-popup-close:hover { background:rgba(0,0,0,.80); }
.pip-popup-link { display:block; line-height:0; }
.pip-popup-img { width:100%; height:auto; display:block; object-fit:cover; transition:opacity .2s; }
.pip-popup-link:hover .pip-popup-img { opacity:.92; }
.pip-popup-footer {
  padding:1rem 1.25rem 1.25rem; background:#fff;
  display:flex; flex-direction:column; gap:.5rem;
}
.pip-popup-label {
  font-size:.75rem; font-weight:700; letter-spacing:.08em;
  text-transform:uppercase; color:#1E3425; margin:0;
}
.pip-popup-btn {
  display:inline-flex; align-items:center;
  background:#1E3425; color:#CDDE00 !important;
  font-size:.875rem; font-weight:600; padding:.55rem 1.1rem;
  border-radius:6px; text-decoration:none;
  align-self:flex-start; transition:background .18s;
}
.pip-popup-btn:hover { background:#162a1c; color:#CDDE00 !important; }
@media (max-width:480px) {
  .pip-popup-box { border-radius:10px; }
  .pip-popup-footer { padding:.875rem 1rem 1rem; }
}
@media (prefers-reduced-motion:reduce) { .pip-popup-box { animation:none; } }
</style>

<script>
(function () {
  var STORAGE_KEY = 'pip_insights_popup_seen';
  var popup    = document.getElementById('pip-insights-popup');
  var closeBtn = document.getElementById('pip-popup-close');
  var backdrop = document.getElementById('pip-popup-backdrop');
  function closePopup() {
    popup.classList.add('pip-hidden');
    try { sessionStorage.setItem(STORAGE_KEY, '1'); } catch(e) {}
  }
  var seen = false;
  try { seen = !!sessionStorage.getItem(STORAGE_KEY); } catch(e) {}
  if (!seen) { popup.classList.remove('pip-hidden'); }
  closeBtn.addEventListener('click', closePopup);
  backdrop.addEventListener('click', closePopup);
  document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closePopup(); });
})();
</script>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>
