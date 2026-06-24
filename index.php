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
        <span>Inscrições abertas — gratuito para negócios cadastrados. Garanta a sua vaga!</span>
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
        <i class="bi bi-trophy-fill me-1"></i> Inscrever-me agora
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
  margin-left:  calc(-50vw + 50%);
  margin-right: calc(-50vw + 50%);
  padding-left: calc(50vw - 50% + 2rem);
  padding-right: calc(50vw - 50% + 2rem);
  padding-top: 1.4rem;
  padding-bottom: 1.4rem;
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
  gap: 1.5rem;
  flex-wrap: wrap;
  position: relative;
  z-index: 1;
}
.pip-urgency-left {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1 1 240px;
}
.pip-urgency-icon-wrap {
  flex-shrink: 0;
  width: 52px;
  height: 52px;
  border-radius: 12px;
  background: rgba(205, 222, 0, 0.18);
  color: #CDDE00;
  font-size: 1.4rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(205,222,0,.25);
}
.pip-urgency-text {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.pip-urgency-text strong {
  font-size: 1.05rem;
  font-weight: 700;
  color: #fff;
  line-height: 1.2;
}
.pip-urgency-text span {
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.75);
  line-height: 1.4;
}
.pip-urgency-center { flex-shrink: 0; }
.pip-countdown {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(0, 0, 0, 0.28);
  border-radius: 12px;
  padding: 0.65rem 1.1rem;
  border: 1px solid rgba(205, 222, 0, 0.25);
}
.pip-countdown-unit {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 46px;
}
.pip-countdown-num {
  font-size: 1.85rem;
  font-weight: 800;
  line-height: 1;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.02em;
  color: #CDDE00;
}
.pip-countdown-label {
  font-size: 0.62rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: rgba(255, 255, 255, 0.6);
  margin-top: 3px;
}
.pip-countdown-sep {
  font-size: 1.6rem;
  font-weight: 800;
  color: rgba(205, 222, 0, 0.5);
  align-self: flex-start;
  margin-top: 2px;
  line-height: 1.1;
}
.pip-urgency-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-shrink: 0;
  flex-wrap: wrap;
}
.pip-urgency-btn-cta {
  font-size: 0.9rem !important;
  padding: 0.65rem 1.35rem !important;
  border-radius: 999px !important;
}
@media (max-width: 768px) {
  .pip-urgency-bar {
    padding-left: calc(50vw - 50% + 1.25rem);
    padding-right: calc(50vw - 50% + 1.25rem);
    padding-top: 1.1rem;
    padding-bottom: 1.1rem;
  }
  .pip-urgency-inner { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .pip-urgency-center { width: 100%; display: flex; justify-content: center; }
  .pip-urgency-right { width: 100%; justify-content: center; }
  .pip-countdown-num { font-size: 1.5rem; }
}
@media (max-width: 480px) {
  .pip-urgency-btn-cta { font-size: 0.82rem !important; padding: 0.55rem 1rem !important; }
  .pip-urgency-text strong { font-size: 0.95rem; }
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
    <h2 class="apoio-institucional__titulo">Apoio Institucional</h2>
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
     POPUP — Premiação 2026
     Layout: 2 colunas — imagem | resumo da premiação
════════════════════════════════════════════════ -->
<div id="pip-insights-popup" role="dialog" aria-modal="true" aria-labelledby="pip-popup-title" style="display:none;">
  <div class="pip-popup-backdrop" id="pip-popup-backdrop"></div>
  <div class="pip-popup-box">

    <button class="pip-popup-close" id="pip-popup-close" aria-label="Fechar popup">
      <i class="bi bi-x-lg"></i>
    </button>

    <!-- Coluna esquerda: imagem -->
    <div class="pip-popup-img-col">
      <img
        src="/assets/images/insights-09-jun.jfif"
        alt="Prêmio Impactos Positivos 2026"
        class="pip-popup-img" width="360" height="480" loading="eager">
    </div>

    <!-- Coluna direita: conteúdo da premiação -->
    <div class="pip-popup-content-col">

      <div class="pip-popup-kicker">
        <i class="bi bi-trophy-fill"></i> Premiação 2026
      </div>

      <h2 class="pip-popup-title" id="pip-popup-title">
        Seu negócio merece reconhecimento nacional.
      </h2>

      <p class="pip-popup-sub">
        O <strong>Prêmio Impactos Positivos 2026</strong> celebra quem gera impacto real — gratuito para negócios cadastrados.
      </p>

      <!-- Pilares -->
      <ul class="pip-popup-pilares">
        <li>
          <span class="pip-popup-pilar-icon"><i class="bi bi-award-fill"></i></span>
          <span>Reconhecimento e visibilidade nacional</span>
        </li>
        <li>
          <span class="pip-popup-pilar-icon"><i class="bi bi-people-fill"></i></span>
          <span>Avaliação por bancas especializadas</span>
        </li>
        <li>
          <span class="pip-popup-pilar-icon"><i class="bi bi-check-circle-fill"></i></span>
          <span>Inscrições encerram em <strong style="color:#CDDE00;">julho de 2026</strong></span>
        </li>
      </ul>

      <!-- Countdown -->
      <div class="pip-popup-cd" aria-live="polite" aria-label="Contador regressivo">
        <div class="pip-popup-cd-unit">
          <span class="pip-popup-cd-num" id="pip-pop-days">--</span>
          <span class="pip-popup-cd-lbl">dias</span>
        </div>
        <span class="pip-popup-cd-sep" aria-hidden="true">:</span>
        <div class="pip-popup-cd-unit">
          <span class="pip-popup-cd-num" id="pip-pop-hours">--</span>
          <span class="pip-popup-cd-lbl">horas</span>
        </div>
        <span class="pip-popup-cd-sep" aria-hidden="true">:</span>
        <div class="pip-popup-cd-unit">
          <span class="pip-popup-cd-num" id="pip-pop-mins">--</span>
          <span class="pip-popup-cd-lbl">min</span>
        </div>
        <span class="pip-popup-cd-sep" aria-hidden="true">:</span>
        <div class="pip-popup-cd-unit">
          <span class="pip-popup-cd-num" id="pip-pop-secs">--</span>
          <span class="pip-popup-cd-lbl">seg</span>
        </div>
      </div>

      <!-- CTAs -->
      <div class="pip-popup-btns">
        <a href="/premiacao.php" class="btn-premiacao-primary">
          <i class="bi bi-trophy me-1"></i> Quero me inscrever
        </a>
        <a href="/empreendedores/register.php" class="btn-premiacao-outline" style="font-size:.8rem; border-color:rgba(255,255,255,.3); color:rgba(255,255,255,.7);">
          Ainda não tenho conta <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>

    </div>
  </div>
</div>

<style>
/* ── Popup Premiação ── */
#pip-insights-popup {
  position: fixed; inset: 0; z-index: 9999;
  display: flex !important; align-items: center; justify-content: center; padding: 1rem;
}
#pip-insights-popup.pip-hidden { display: none !important; }

.pip-popup-backdrop {
  position: absolute; inset: 0;
  background: rgba(0,0,0,.65);
  backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
  cursor: pointer;
}

/* Box principal: 2 colunas */
.pip-popup-box {
  position: relative; z-index: 1;
  display: flex;
  max-width: 780px; width: 100%;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 24px 70px rgba(0,0,0,.45);
  animation: pipFadeIn 0.35s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes pipFadeIn {
  from { opacity:0; transform: scale(.93) translateY(18px); }
  to   { opacity:1; transform: scale(1) translateY(0); }
}

/* Botão fechar */
.pip-popup-close {
  position: absolute; top: 12px; right: 12px; z-index: 10;
  background: rgba(0,0,0,.5); color: #fff; border: none; border-radius: 50%;
  width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 1rem; transition: background .18s;
}
.pip-popup-close:hover { background: rgba(0,0,0,.8); }

/* Coluna imagem */
.pip-popup-img-col {
  flex: 0 0 42%;
  max-width: 42%;
  overflow: hidden;
}
.pip-popup-img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}

/* Coluna conteúdo */
.pip-popup-content-col {
  flex: 1;
  background: #1E3425;
  padding: 2rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  overflow-y: auto;
  position: relative;
}
.pip-popup-content-col::before {
  content: '';
  position: absolute; inset: 0;
  background: url('/assets/images/aneis.png') no-repeat center/cover;
  opacity: 0.05;
  pointer-events: none;
}

.pip-popup-kicker {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #CDDE00;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.pip-popup-title {
  font-size: 1.35rem;
  font-weight: 800;
  color: #fff;
  line-height: 1.25;
  margin: 0;
}

.pip-popup-sub {
  font-size: 0.82rem;
  color: rgba(255,255,255,.7);
  line-height: 1.55;
  margin: 0;
}

/* Pilares */
.pip-popup-pilares {
  list-style: none;
  padding: 0; margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}
.pip-popup-pilares li {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  font-size: 0.82rem;
  color: rgba(255,255,255,.85);
  line-height: 1.4;
}
.pip-popup-pilar-icon {
  flex-shrink: 0;
  width: 26px; height: 26px;
  border-radius: 7px;
  background: rgba(205,222,0,.15);
  color: #CDDE00;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.8rem;
  margin-top: 1px;
}

/* Countdown */
.pip-popup-cd {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  background: rgba(0,0,0,.3);
  border-radius: 10px;
  padding: 0.6rem 0.85rem;
  border: 1px solid rgba(205,222,0,.2);
  align-self: flex-start;
}
.pip-popup-cd-unit {
  display: flex; flex-direction: column; align-items: center;
  min-width: 40px;
}
.pip-popup-cd-num {
  font-size: 1.65rem;
  font-weight: 800;
  line-height: 1;
  font-variant-numeric: tabular-nums;
  letter-spacing: -.03em;
  color: #CDDE00;
}
.pip-popup-cd-lbl {
  font-size: 0.55rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: rgba(255,255,255,.5);
  margin-top: 2px;
}
.pip-popup-cd-sep {
  font-size: 1.4rem;
  font-weight: 800;
  color: rgba(205,222,0,.4);
  align-self: flex-start;
  margin-top: 2px;
  line-height: 1.1;
}

/* CTAs */
.pip-popup-btns {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  margin-top: auto;
}

/* Mobile: empilha as colunas */
@media (max-width: 600px) {
  .pip-popup-box { flex-direction: column; max-width: 420px; }
  .pip-popup-img-col { flex: 0 0 auto; max-width: 100%; max-height: 200px; }
  .pip-popup-content-col { padding: 1.5rem 1.25rem; }
  .pip-popup-title { font-size: 1.1rem; }
  .pip-popup-cd-num { font-size: 1.3rem; }
  .pip-popup-cd-unit { min-width: 34px; }
}
@media (prefers-reduced-motion: reduce) { .pip-popup-box { animation: none; } }
</style>

<script>
(function () {
  /* — Popup: exibe uma vez por sessão — */
  var STORAGE_KEY = 'pip_popup_premiacao_seen';
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

  /* — Countdown do popup — */
  var deadline = new Date('2026-07-31T23:59:59-03:00').getTime();
  function pad(n) { return String(n).padStart(2, '0'); }
  function tickPop() {
    var diff = deadline - Date.now();
    if (diff <= 0) {
      ['days','hours','mins','secs'].forEach(function(id){
        var el = document.getElementById('pip-pop-' + id);
        if (el) el.textContent = '00';
      });
      return;
    }
    var d = document.getElementById('pip-pop-days');
    var h = document.getElementById('pip-pop-hours');
    var m = document.getElementById('pip-pop-mins');
    var s = document.getElementById('pip-pop-secs');
    if(d) d.textContent = pad(Math.floor(diff / 86400000));
    if(h) h.textContent = pad(Math.floor((diff % 86400000) / 3600000));
    if(m) m.textContent = pad(Math.floor((diff % 3600000)  / 60000));
    if(s) s.textContent = pad(Math.floor((diff % 60000)    / 1000));
  }
  tickPop();
  setInterval(tickPop, 1000);
})();
</script>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>
