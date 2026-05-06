<?php
// premiacao.php — Página pública da Premiação IP 2026
require_once __DIR__ . '/app/config/database.php';

$pageTitle = 'Premiação Impactos Positivos 2026';

// Busca a premiação ativa/mais recente
try {
    $stPremiacao = $pdo->query("
        SELECT id, nome, ano, status
        FROM premiacoes
        WHERE status = 'ativa'
        ORDER BY ano DESC, id DESC
        LIMIT 1
    ");
    $premiacaoAtiva = $stPremiacao->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    $premiacaoAtiva = [];
}

// ── Classificados por fase/categoria (apenas fases apuradas ou encerradas) ────
$classificadosPorFase = [];
if (!empty($premiacaoAtiva['id'])) {
    try {
        $premId = (int)$premiacaoAtiva['id'];

        // Fases com classificados gravados
        $stFases = $pdo->prepare("
            SELECT pf.id, pf.nome, pf.tipo_fase, pf.ordem_exibicao, pf.status
            FROM premiacao_fases pf
            WHERE pf.premiacao_id = ?
              AND pf.status IN ('apurada', 'encerrada')
              AND EXISTS (
                  SELECT 1 FROM premiacao_classificados pc WHERE pc.fase_id = pf.id
              )
            ORDER BY pf.ordem_exibicao ASC
        ");
        $stFases->execute([$premId]);
        $fases = $stFases->fetchAll(PDO::FETCH_ASSOC);

        foreach ($fases as $fase) {
            $faseId = (int)$fase['id'];

            // Subquery garante uma única inscrição por negócio (a de maior id),
            // evitando duplicatas quando o mesmo negócio tem mais de uma inscrição
            // na premiação. Filtra também pelos status classificados.
            $stCl = $pdo->prepare("
                SELECT
                    pc.posicao,
                    pc.origem,
                    cat.nome       AS categoria_nome,
                    cat.ordem      AS categoria_ordem,
                    n.nome_fantasia,
                    n.municipio,
                    n.estado,
                    e.nome         AS empreendedor_nome,
                    na.logo_negocio
                FROM premiacao_classificados pc
                INNER JOIN premiacao_categorias  cat ON cat.id = pc.categoria_id
                INNER JOIN negocios              n   ON n.id   = pc.negocio_id
                INNER JOIN (
                    SELECT negocio_id, empreendedor_id
                    FROM premiacao_inscricoes
                    WHERE premiacao_id = ?
                      AND status IN (
                          'classificada_fase_1','classificada_fase_2',
                          'classificada_fase_3','classificada_fase_4',
                          'classificada_fase_5','finalista','vencedora'
                      )
                    GROUP BY negocio_id, empreendedor_id
                ) pi ON pi.negocio_id = pc.negocio_id
                INNER JOIN empreendedores        e   ON e.id   = pi.empreendedor_id
                LEFT  JOIN negocio_apresentacao  na  ON na.negocio_id = n.id
                WHERE pc.fase_id = ?
                ORDER BY cat.ordem ASC, pc.posicao ASC
            ");
            $stCl->execute([$premId, $faseId]);
            $rows = $stCl->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) continue;

            // Agrupa por categoria
            $porCat = [];
            foreach ($rows as $r) {
                $cn = $r['categoria_nome'];
                if (!isset($porCat[$cn])) {
                    $porCat[$cn] = ['ordem' => $r['categoria_ordem'], 'itens' => []];
                }
                $porCat[$cn]['itens'][] = $r;
            }

            $classificadosPorFase[] = [
                'fase'   => $fase,
                'porCat' => $porCat,
            ];
        }
    } catch (Exception $e) {
        $classificadosPorFase = [];
    }
}

require_once __DIR__ . '/app/views/public/header_public.php';

function h(?string $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
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
          <?php if (!empty($classificadosPorFase)): ?>
          <a href="#classificados" class="btn-premiacao-outline">
            <i class="bi bi-award-fill me-2"></i> Ver classificados
          </a>
          <?php else: ?>
          <a href="#como-funciona" class="btn-premiacao-outline">
            <i class="bi bi-info-circle me-2"></i> Como funciona
          </a>
          <?php endif; ?>
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

<?php if (!empty($classificadosPorFase)): ?>
<!-- ══════════════════════════════════════════
     CLASSIFICADOS — DINÂMICO
══════════════════════════════════════════ -->
<section id="classificados" class="py-5" style="background:#f8f9f3;">
  <div class="container">

    <div class="text-center mb-5">
      <span class="section-kicker section-kicker--accent mb-3">
        <i class="bi bi-award-fill me-1"></i> Classificados
      </span>
      <h2 class="section-title mt-2">Negócios classificados</h2>
      <p class="section-sub mx-auto" style="max-width:520px;">
        Conheça os negócios que avançaram nas fases da Premiação Impactos Positivos <?= (int)($premiacaoAtiva['ano'] ?? 2026) ?>.
      </p>
    </div>

    <?php foreach ($classificadosPorFase as $bloco): ?>
      <?php
        $fase      = $bloco['fase'];
        $labelTipo = match($fase['tipo_fase'] ?? '') {
            'classificatoria' => 'Classificatória',
            'final'           => 'Fase Final',
            default           => ucfirst((string)($fase['tipo_fase'] ?? '')),
        };
      ?>

      <!-- Título da fase -->
      <div class="d-flex align-items-center gap-3 mb-4 mt-2">
        <div style="flex:1; height:2px; background:linear-gradient(90deg,#CDDE00,transparent);"></div>
        <h3 class="mb-0 fw-bold" style="font-size:1.15rem; color:#1E3425; white-space:nowrap;">
          <i class="bi bi-layers-fill me-2" style="color:#CDDE00;"></i>
          <?= h($fase['nome']) ?>
          <span class="badge ms-2" style="background:#1E3425; color:#CDDE00; font-size:.7rem; font-weight:600; vertical-align:middle;"><?= h($labelTipo) ?></span>
        </h3>
        <div style="flex:1; height:2px; background:linear-gradient(90deg,transparent,#CDDE00);"></div>
      </div>

      <?php foreach ($bloco['porCat'] as $catNome => $catDados): ?>
        <div class="mb-5">

          <!-- Cabeçalho da categoria -->
          <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-bookmark-fill" style="color:#CDDE00; font-size:1rem;"></i>
            <h4 class="mb-0 fw-semibold" style="font-size:1rem; color:#1E3425;"><?= h($catNome) ?></h4>
            <span class="badge bg-secondary" style="font-size:.7rem;"><?= count($catDados['itens']) ?> classificado<?= count($catDados['itens']) !== 1 ? 's' : '' ?></span>
          </div>

          <!-- Grid de cards -->
          <div class="row g-3">
            <?php foreach ($catDados['itens'] as $item): ?>
              <?php
                $pos   = (int)$item['posicao'];
                $medal = match($pos) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => $pos . 'º' };
                $isTop = $pos <= 3;
                $bordaColor = match($pos) {
                    1 => '#CDDE00',
                    2 => '#B0BEC5',
                    3 => '#CD7F32',
                    default => '#dee2e6',
                };
                $origemLabel = match($item['origem'] ?? '') {
                    'popular'     => ['bg' => '#fff3cd', 'color' => '#856404', 'text' => 'Popular'],
                    'tecnica'     => ['bg' => '#cfe2ff', 'color' => '#084298', 'text' => 'Técnica'],
                    'ambos'       => ['bg' => '#d1e7dd', 'color' => '#0a3622', 'text' => 'Popular + Técnica'],
                    'juri'        => ['bg' => '#e2d9f3', 'color' => '#3d0f6e', 'text' => 'Júri'],
                    'complemento' => ['bg' => '#f8f9fa', 'color' => '#6c757d', 'text' => 'Complemento'],
                    default       => ['bg' => '#f8f9fa', 'color' => '#6c757d', 'text' => h($item['origem'] ?? '')],
                };
              ?>
              <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm"
                     style="border-top: 3px solid <?= $bordaColor ?> !important; border-radius:12px; overflow:hidden;">

                  <!-- Logo / Avatar -->
                  <div style="height:80px; background:#f0f2ec; display:flex; align-items:center; justify-content:center; position:relative;">
                    <?php if (!empty($item['logo_negocio'])): ?>
                      <img src="/uploads/<?= h($item['logo_negocio']) ?>"
                           alt="Logo <?= h($item['nome_fantasia']) ?>"
                           style="max-height:60px; max-width:90%; object-fit:contain;">
                    <?php else: ?>
                      <div style="width:50px; height:50px; border-radius:50%; background:#1E3425; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-building" style="color:#CDDE00; font-size:1.3rem;"></i>
                      </div>
                    <?php endif; ?>
                    <!-- Posição badge -->
                    <span style="position:absolute; top:8px; left:10px; font-size:1.3rem; line-height:1;"><?= $medal ?></span>
                  </div>

                  <div class="card-body p-3">
                    <p class="fw-bold mb-1" style="font-size:.93rem; color:#1E3425; line-height:1.25;">
                      <?= h($item['nome_fantasia']) ?>
                    </p>
                    <p class="mb-2" style="font-size:.78rem; color:#6c757d; line-height:1.3;">
                      <i class="bi bi-person-fill me-1"></i><?= h($item['empreendedor_nome']) ?>
                      <?php if (!empty($item['municipio'])): ?>
                        <br><i class="bi bi-geo-alt-fill me-1"></i><?= h($item['municipio']) ?>/<?= h($item['estado']) ?>
                      <?php endif; ?>
                    </p>
                    <span class="badge" style="background:<?= $origemLabel['bg'] ?>; color:<?= $origemLabel['color'] ?>; font-size:.7rem; font-weight:600;">
                      <?= $origemLabel['text'] ?>
                    </span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

        </div>
      <?php endforeach; ?>

    <?php endforeach; ?>

  </div>
</section>
<?php endif; ?>

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
