<?php
// /regulamento-do-premio.php
declare(strict_types=1);

$pageTitle = 'Regulamento do Prêmio Impactos Positivos 2026 | Impactos Positivos';

include __DIR__ . '/app/views/public/header_public.php';
?>

<!-- ═══════════════════════════════════════
     HERO — REGULAMENTO
════════════════════════════════════════ -->
<section class="reg-hero">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-8">
        <span class="reg-update-badge mb-3 d-inline-flex">
          <i class="bi bi-patch-check-fill me-1"></i>
          Última atualização: 31 de março de 2026
        </span>
        <h1 class="reg-hero-title mb-3">
          Regulamento<br>
          <span style="color:#CDDE00;">Prêmio Impactos Positivos 2026</span>
        </h1>
        <p class="reg-hero-sub mb-4">
          Leia com atenção todas as regras, critérios e disposições antes de realizar sua inscrição ou votação.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <a href="https://vitrine.impactospositivos.com" target="_blank" rel="noopener" class="btn-premiacao-primary">
            <i class="bi bi-pencil-square me-2"></i> Inscreva-se agora
          </a>
          <a href="#sec-participacao" class="btn-premiacao-outline">
            <i class="bi bi-file-text me-2"></i> Ver regulamento
          </a>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="d-flex flex-column gap-2">
          <div class="reg-hero-info-item">
            <i class="bi bi-calendar3" style="color:#CDDE00;"></i>
            Inscrições: <strong>11/05 a 24/07/2026</strong>
          </div>
          <div class="reg-hero-info-item">
            <i class="bi bi-trophy" style="color:#CDDE00;"></i>
            Premiação: <strong>24 de setembro de 2026</strong>
          </div>
          <div class="reg-hero-info-item">
            <i class="bi bi-grid-3x2-gap" style="color:#CDDE00;"></i>
            4 categorias disponíveis
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- /HERO -->


<!-- ═══════════════════════════════════════
     CONTEÚDO PRINCIPAL
════════════════════════════════════════ -->
<section class="py-5">
  <div class="container">
    <div class="row g-5">

      <!-- ── Índice lateral -->
      <aside class="col-lg-3 d-none d-lg-block">
        <nav class="reg-toc" aria-label="Índice do regulamento">
          <div class="reg-toc-title"><i class="bi bi-list-ul me-1"></i> Índice</div>
          <ul class="reg-toc-list">
            <li><a href="#sec-objetivo"><span class="toc-num">–</span> Objetivo &amp; Princípios</a></li>
            <li><a href="#sec-board"><span class="toc-num">–</span> Board 2026</a></li>
            <li><a href="#sec-participacao"><span class="toc-num">1</span> Participação</a></li>
            <li><a href="#sec-categorias"><span class="toc-num">2</span> Categorias</a></li>
            <li><a href="#sec-inscricao"><span class="toc-num">3</span> Inscrição</a></li>
            <li><a href="#sec-criterios"><span class="toc-num">4</span> Critérios de Avaliação</a></li>
            <li><a href="#sec-votacao"><span class="toc-num">5</span> Votação</a></li>
            <li><a href="#sec-juri"><span class="toc-num">6</span> Júri da Premiação</a></li>
            <li><a href="#sec-premiacao"><span class="toc-num">7</span> Premiação</a></li>
            <li><a href="#sec-entrega"><span class="toc-num">8</span> Entrega dos Prêmios</a></li>
            <li><a href="#sec-geral"><span class="toc-num">9</span> Disposições Gerais</a></li>
            <li><a href="#sec-dados"><span class="toc-num">10</span> Dados Pessoais</a></li>
            <li><a href="#sec-promotora"><span class="toc-num">–</span> Promotora</a></li>
          </ul>
          <div class="reg-toc-cta">
            <a href="https://vitrine.impactospositivos.com" target="_blank" rel="noopener" class="btn btn-success w-100" style="border-radius:999px;font-weight:700;">
              <i class="bi bi-pencil-square me-1"></i> Inscreva-se
            </a>
          </div>
        </nav>
      </aside>

      <!-- ── Conteúdo -->
      <div class="col-lg-9">

        <!-- Objetivo -->
        <section id="sec-objetivo" class="reg-section">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num" style="background:#CDDE00;color:#1E3425;">
              <i class="bi bi-star-fill" style="font-size:.85rem;"></i>
            </span>
            <h2 class="reg-section-title mb-0">Objetivo &amp; Princípios</h2>
          </div>
          <p>
            O <strong>Prêmio Impactos Positivos 2026</strong> tem como objetivo celebrar e mostrar para o mundo os incríveis negócios de impacto, ecossistemas de impacto, cidadãos de impacto, instituições de impacto e comunicadores de impacto que temos em nosso país, os quais deveriam receber mais destaque e reconhecimento que merecem.
          </p>
          <p>Estes são os nossos princípios:</p>
          <div class="row g-2 mb-3">
            <?php
            $principios = ['Educação','Transformação','Engajamento','Envolvimento','Colaboração','Sinergia'];
            foreach ($principios as $p): ?>
              <div class="col-6 col-sm-4">
                <div class="principio-chip text-center py-2 px-3 d-flex align-items-center justify-content-center gap-2">
                  <i class="bi bi-check-circle-fill" style="color:#97A327;font-size:.9rem;"></i>
                  <?= htmlspecialchars($p) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <p>
            Por isso, toda e qualquer iniciativa do Prêmio é em prol de um mundo onde negócios e pessoas inspiradoras possam ser cada vez mais enaltecidas e divulgadas.
          </p>
          <p>
            Assim, muito mais que uma vitrine de exibição, nossa premiação tem como objetivo trazer notoriedade aos negócios feitos por gente de verdade, independentemente de seu tamanho, acreditando em seu sonho de transformar a realidade em um ambiente melhor.
          </p>
        </section>

        <!-- Board -->
        <section id="sec-board" class="reg-section">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num" style="background:#95BCCC;color:#1E3425;">
              <i class="bi bi-people-fill" style="font-size:.85rem;"></i>
            </span>
            <h2 class="reg-section-title mb-0">Quem está trabalhando junto na edição 2026?</h2>
          </div>
          <p>
            A plataforma Impactos Positivos conta com um board de Conselheiros com muita experiência e conhecimento para a edição 2026 do Prêmio.
          </p>
          <div class="row g-3">
            <?php
            $board = [
              ['Alexandre Uehara',  'Board de Inovação e Tecnologia',    'A'],
              ['Gisele Abrahão',    'Board de Estratégia e Comunicação', 'G'],
              ['Eduardo Nunes',     'Board de Governança ESG',           'E'],
              ['Roberta Coutinho',  'Board de Finanças Sustentáveis',    'R'],
            ];
            foreach ($board as [$nome, $area, $ini]): ?>
              <div class="col-md-6">
                <div class="reg-board-card">
                  <div class="reg-board-avatar"><?= $ini ?></div>
                  <div>
                    <div class="reg-board-name"><?= htmlspecialchars($nome) ?></div>
                    <div class="reg-board-role"><?= htmlspecialchars($area) ?></div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

        <!-- 1. Participação -->
        <section id="sec-participacao" class="reg-section">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num">1</span>
            <h2 class="reg-section-title mb-0">Participação</h2>
          </div>
          <div class="reg-article">
            <span class="reg-article-num">1.1</span>
            Poderão participar deste prêmio pessoas jurídicas que cumprirem o disposto neste regulamento.
          </div>
          <div class="reg-article">
            <span class="reg-article-num">1.2</span>
            Estão impedidos de participar os funcionários da empresa promotora, de suas subsidiárias, de suas agências e fornecedores, empresas apoiadoras e patrocinadoras, bem como seus parentes de primeiro grau.
          </div>
          <div class="reg-article">
            <span class="reg-article-num">1.3</span>
            A participação de menores de idade é de exclusiva responsabilidade dos pais ou responsáveis legais.
          </div>
        </section>

        <!-- 2. Categorias -->
        <section id="sec-categorias" class="reg-section">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num">2</span>
            <h2 class="reg-section-title mb-0">Categorias</h2>
          </div>
          <div class="reg-article mb-3">
            <span class="reg-article-num">2.1</span>
            O prêmio é dividido em quatro (4) categorias:
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <div class="reg-categoria-card">
                <h5><i class="bi bi-lightbulb me-2" style="color:#CDDE00;"></i>Ideação</h5>
                <p>Dando os primeiros passos na formação da empresa, ainda sem formalização, mas com MVP sendo testado.</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="reg-categoria-card">
                <h5><i class="bi bi-gear me-2" style="color:#CDDE00;"></i>Operação</h5>
                <p>Empresa já formalizada, com faturamento e modelo de negócios validados.</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="reg-categoria-card">
                <h5><i class="bi bi-graph-up-arrow me-2" style="color:#CDDE00;"></i>Tração</h5>
                <p>Empresa já formalizada, atuando no mercado há mais de um ano, com ou sem entrada de investimento, e com escala de vendas em crescimento.</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="reg-categoria-card reg-categoria-card--destaque">
                <h5><i class="bi bi-diagram-3 me-2" style="color:#97A327;"></i>Dinamizador do Ecossistema</h5>
                <p>Organizações que fomentam o ecossistema de impacto: institutos, fundações, empresas, aceleradoras, incubadoras, academias, universidades, centros de pesquisa, laboratórios, consultorias, certificadoras, mentores, ONGs, movimentos sociais, entre outros.</p>
              </div>
            </div>
          </div>
          <div class="reg-alert-atencao d-flex gap-3 align-items-start">
            <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
            <div>
              <strong>Atenção!</strong> Esta edição do prêmio não permitirá a participação de projetos sob responsabilidade de órgãos do governo federal, em virtude da colaboração estabelecida entre o prêmio e outros órgãos federais.
            </div>
          </div>
        </section>

        <!-- 3. Inscrição -->
        <section id="sec-inscricao" class="reg-section">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num">3</span>
            <h2 class="reg-section-title mb-0">Inscrição</h2>
          </div>
          <div class="reg-article"><span class="reg-article-num">3.1</span> As inscrições podem ser realizadas em todo território nacional, por pessoas jurídicas, nos termos do item 1 deste regulamento.</div>
          <div class="reg-article">
            <span class="reg-article-num">3.2</span>
            Período de inscrições: <strong>11/05/2026 a 24/07/2026 às 23h59</strong>, por meio da plataforma
            <a href="https://vitrine.impactospositivos.com" target="_blank" rel="noopener" class="reg-ext-link">vitrine.impactospositivos.com</a>.
          </div>
          <div class="reg-article"><span class="reg-article-num">3.3</span> O Cadastro foi desenvolvido em parceria com o Sebrae Nacional, Enimpacto e CADImpacto — plataforma do Ministério do Desenvolvimento, Indústria, Comércio e Serviços (MDIC). Além dos dados da pessoa jurídica, serão solicitados dados pessoais do responsável: nome completo, CPF, data de nascimento, e-mail, telefone, gênero, formação acadêmica e endereço completo.</div>
          <div class="reg-article"><span class="reg-article-num">3.4</span> As informações pessoais fornecidas serão utilizadas para atender a sua solicitação e poderão ser compartilhadas com terceiros conforme a política do usuário.</div>
          <div class="reg-article"><span class="reg-article-num">3.5</span> Todos os inscritos são responsáveis pelas informações adicionadas em seus formulários e garantem que elas são verdadeiras e atualizadas.</div>
          <div class="reg-article"><span class="reg-article-num">3.6</span> Somente serão aceitas inscrições de empresas sem registros de impactos negativos ambientais, sociais e de governança.</div>
          <div class="reg-article">
            <span class="reg-article-num">3.7</span> Para confirmar a inscrição, serão realizadas pesquisas em sites públicos:
            <ul class="mt-2">
              <li><a href="https://www.tst.jus.br/certidao1" target="_blank" rel="noopener" class="reg-ext-link">tst.jus.br/certidao1</a> — certidão negativa de débitos trabalhistas.</li>
              <li><a href="https://servicos.ibama.gov.br/sicafiext/sistema.php" target="_blank" rel="noopener" class="reg-ext-link">servicos.ibama.gov.br</a> — certidão negativa de multas ambientais.</li>
            </ul>
            Essas verificações poderão ocorrer em todas as fases do prêmio.
          </div>
          <div class="reg-article"><span class="reg-article-num">3.8</span> Outras informações que atestem impactos negativos poderão ser pesquisadas em fontes seguras e públicas, incluindo decisões judiciais.</div>
          <div class="reg-article"><span class="reg-article-num">3.9</span> Será solicitado 1 (um) vídeo e 5 (cinco) fotos do negócio no momento do cadastro, que poderão ser utilizados para divulgação no site e redes sociais do prêmio.</div>
          <div class="reg-article"><span class="reg-article-num">3.10</span> Não serão válidas as participações recebidas após 24/07/2026. Inscrições incompletas, incorretas ou em desconformidade com este regulamento serão desclassificadas.</div>
          <div class="reg-article"><span class="reg-article-num">3.11</span> Os projetos inscritos devem estar diretamente relacionados às atividades comerciais registradas no CNPJ fornecido.</div>
          <div class="reg-article"><span class="reg-article-num">3.12</span> Serão desclassificadas as inscrições que apresentem qualquer prática de atos ilegais ou violação de legislação vigente.</div>
          <div class="reg-article"><span class="reg-article-num">3.13</span> Para participar deste prêmio, realizado nos termos da <strong>Lei nº 5.768/71</strong>, não é necessária a compra de qualquer bem, direito ou serviço.</div>
          <div class="reg-article"><span class="reg-article-num">3.14</span> A apuração do vencedor não está sujeita a qualquer tipo de sorteio ou operação assemelhada.</div>
        </section>

        <!-- 4. Critérios -->
        <section id="sec-criterios" class="reg-section">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num">4</span>
            <h2 class="reg-section-title mb-0">Critérios de Avaliação</h2>
          </div>
          <div class="reg-article mb-3">
            <span class="reg-article-num">4.1</span> Abaixo, seguem os critérios de avaliação para a premiação:
          </div>
          <div class="row g-3 mb-3">
            <?php
            $criterios = [
              ['bi-heart-pulse',    'Valores',           'A necessidade do negócio e o impacto que ele traz para a sociedade.'],
              ['bi-hand-thumbs-up', 'Facilidade',        'Facilidade de informações, de acesso e de engajamento.'],
              ['bi-speedometer2',   'Produtividade',     'Qualidade, sustentabilidade, economia de tempo e redução de custos.'],
              ['bi-tree',           'Impacto Ambiental', 'Benefícios ao meio ambiente, proteção e/ou recuperação da natureza.'],
              ['bi-people',         'Impacto Social',    'Como ajuda a resolver vulnerabilidade social, educação, saúde ou qualidade de vida.'],
              ['bi-coin',           'Impacto Econômico', 'Benefícios à comunidade, geração de empregos e rentabilidade adicional.'],
            ];
            foreach ($criterios as [$icon, $titulo, $desc]): ?>
              <div class="col-md-6">
                <div class="como-card h-100 text-start">
                  <i class="bi <?= $icon ?> como-icon"></i>
                  <h5><?= $titulo ?></h5>
                  <p><?= $desc ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="reg-article"><span class="reg-article-num">4.2</span> Os critérios acima poderão ser atualizados durante o período das inscrições.</div>
          <div class="reg-article">
            <span class="reg-article-num">4.3</span> <strong>Bancada Técnica de Avaliação</strong> — composta por profissionais qualificados nas áreas de impacto social, ambiental e inovação. Avaliará os projetos com base nas qualificações apresentadas e, junto com a votação aberta, selecionará os que avançarão nas fases. Os integrantes serão apresentados antes do período de votação.
          </div>
        </section>

        <!-- ═══════════════════════════════════════
             5. VOTAÇÃO
        ════════════════════════════════════════ -->
        <section id="sec-votacao" class="reg-section">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num">5</span>
            <h2 class="reg-section-title mb-0">Votação</h2>
          </div>
          <p>A classificação dos negócios inscritos será realizada por meio de <strong>3 fases classificatórias</strong>, sendo elas:</p>

          <!-- FASE 1 -->
          <div class="reg-fase-bloco mt-4">
            <div class="reg-fase-header">
              <span class="reg-fase-badge-lg">Fase 1 <span class="reg-fase-badge-sub">Classificatória 1</span></span>
              <span class="reg-fase-periodo-lg"><i class="bi bi-calendar3 me-1"></i> 30/07/2026 a 14/08/2026</span>
            </div>

            <div class="reg-fase-body">

              <h5 class="reg-fase-subtitulo"><i class="bi bi-diagram-2 me-2"></i>Processo</h5>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <div class="reg-pool-card reg-pool-card--popular">
                    <div class="reg-pool-icon"><i class="bi bi-people-fill"></i></div>
                    <div>
                      <strong>Voto Popular</strong>
                      <p>Aberto ao público cadastrado em <a href="https://www.impactospositivos.com" target="_blank" rel="noopener" class="reg-ext-link">impactospositivos.com</a>. Cada usuário vota 1 vez por negócio, podendo votar em múltiplos negócios.</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="reg-pool-card reg-pool-card--tecnico">
                    <div class="reg-pool-icon"><i class="bi bi-patch-check-fill"></i></div>
                    <div>
                      <strong>Avaliação Técnica</strong>
                      <p>A Bancada Técnica seleciona <strong>10 negócios por categoria</strong> com base nos critérios de avaliação.</p>
                    </div>
                  </div>
                </div>
              </div>

              <h5 class="reg-fase-subtitulo"><i class="bi bi-trophy me-2"></i>Classificação — TOP 20 por categoria</h5>
              <div class="reg-article mb-2">
                <span class="reg-article-num">→</span>
                10 mais votados pelo público <span class="reg-origem-badge reg-origem-badge--popular">origem: popular</span>
              </div>
              <div class="reg-article mb-2">
                <span class="reg-article-num">→</span>
                10 selecionados pela Bancada Técnica <span class="reg-origem-badge reg-origem-badge--tecnico">origem: técnica</span>
              </div>

              <h5 class="reg-fase-subtitulo mt-3"><i class="bi bi-intersect me-2"></i>Origens combinadas</h5>
              <div class="reg-article mb-2">
                Negócios selecionados nos <strong>dois pools</strong> (popular e técnico) recebem a origem <span class="reg-origem-badge reg-origem-badge--ambos">ambos</span> e têm <strong>prioridade máxima</strong> na classificação.
              </div>
              <div class="reg-article mb-2">
                Caso necessário, o complemento é preenchido pelos próximos negócios em ordem de votos técnicos.
              </div>

              <h5 class="reg-fase-subtitulo mt-3"><i class="bi bi-sort-down me-2"></i>Critérios de desempate (cascata)</h5>
              <div class="reg-desempate-lista">
                <div class="reg-desempate-item">
                  <span class="reg-desempate-num">1°</span>
                  <span><strong>Origem:</strong> <span class="reg-origem-badge reg-origem-badge--ambos">ambos</span> &gt; <span class="reg-origem-badge reg-origem-badge--tecnico">técnica</span> &gt; <span class="reg-origem-badge reg-origem-badge--popular">popular</span> &gt; complemento</span>
                </div>
                <div class="reg-desempate-item">
                  <span class="reg-desempate-num">2°</span>
                  <span>Votos técnicos (maior vence)</span>
                </div>
                <div class="reg-desempate-item">
                  <span class="reg-desempate-num">3°</span>
                  <span>Votos populares (maior vence)</span>
                </div>
                <div class="reg-desempate-item">
                  <span class="reg-desempate-num">4°</span>
                  <span>Score geral do negócio — tabela <code>scores_negocios</code> (maior vence)</span>
                </div>
                <div class="reg-desempate-item">
                  <span class="reg-desempate-num">5°</span>
                  <span>Data de inscrição (inscrição mais antiga vence)</span>
                </div>
              </div>

              <div class="reg-fase-resultado mt-3">
                <i class="bi bi-check2-circle me-2"></i>
                <strong>Resultado:</strong> <strong>80 negócios elegíveis</strong> (20 por categoria: Ideação, Tração, Operação, Dinamizador do Ecossistema) avançam para a Fase 2.
              </div>

            </div>
          </div>

          <!-- FASE 2 -->
          <div class="reg-fase-bloco mt-4">
            <div class="reg-fase-header">
              <span class="reg-fase-badge-lg">Fase 2 <span class="reg-fase-badge-sub">Classificatória 2</span></span>
              <span class="reg-fase-periodo-lg"><i class="bi bi-calendar3 me-1"></i> 24/08/2026 a 04/09/2026</span>
            </div>

            <div class="reg-fase-body">

              <h5 class="reg-fase-subtitulo"><i class="bi bi-diagram-2 me-2"></i>Processo</h5>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <div class="reg-pool-card reg-pool-card--popular">
                    <div class="reg-pool-icon"><i class="bi bi-people-fill"></i></div>
                    <div>
                      <strong>Voto Popular</strong>
                      <p>Votação aberta apenas nos <strong>TOP 20 da Fase 1</strong> por categoria.</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="reg-pool-card reg-pool-card--tecnico">
                    <div class="reg-pool-icon"><i class="bi bi-patch-check-fill"></i></div>
                    <div>
                      <strong>Avaliação Técnica</strong>
                      <p>A Bancada Técnica seleciona <strong>3 negócios por categoria</strong>.</p>
                    </div>
                  </div>
                </div>
              </div>

              <h5 class="reg-fase-subtitulo"><i class="bi bi-trophy me-2"></i>Classificação — TOP 6 por categoria</h5>
              <div class="reg-article mb-2">
                <span class="reg-article-num">→</span>
                3 mais votados pelo público <span class="reg-origem-badge reg-origem-badge--popular">origem: popular</span>
              </div>
              <div class="reg-article mb-2">
                <span class="reg-article-num">→</span>
                3 selecionados pela Bancada Técnica <span class="reg-origem-badge reg-origem-badge--tecnico">origem: técnica</span>
              </div>

              <div class="reg-article mt-2">
                Origens combinadas e critérios de desempate seguem os mesmos critérios estabelecidos na Fase 1.
              </div>

              <div class="reg-fase-resultado mt-3">
                <i class="bi bi-check2-circle me-2"></i>
                <strong>Resultado:</strong> <strong>24 negócios elegíveis</strong> (6 por categoria) avançam para a Fase 3.
              </div>

            </div>
          </div>

          <!-- FASE 3 -->
          <div class="reg-fase-bloco reg-fase-bloco--final mt-4">
            <div class="reg-fase-header">
              <span class="reg-fase-badge-lg reg-fase-badge-lg--final">Fase 3 <span class="reg-fase-badge-sub">Final</span></span>
              <span class="reg-fase-periodo-lg"><i class="bi bi-calendar3 me-1"></i> 07/09/2026 a 18/09/2026</span>
            </div>

            <div class="reg-fase-body">

              <h5 class="reg-fase-subtitulo"><i class="bi bi-diagram-2 me-2"></i>Processo</h5>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <div class="reg-pool-card reg-pool-card--popular">
                    <div class="reg-pool-icon"><i class="bi bi-people-fill"></i></div>
                    <div>
                      <strong>Voto Popular</strong>
                      <p>Aberto ao público nos <strong>TOP 6 finalistas</strong> por categoria.</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="reg-pool-card reg-pool-card--juri">
                    <div class="reg-pool-icon"><i class="bi bi-stars"></i></div>
                    <div>
                      <strong>Júri Final</strong>
                      <p>4 jurados votam em 1 negócio por categoria, adicionando <strong>+1 voto de peso</strong> cada.</p>
                    </div>
                  </div>
                </div>
              </div>

              <h5 class="reg-fase-subtitulo"><i class="bi bi-trophy-fill me-2"></i>Classificação final</h5>
              <div class="reg-article mb-2">
                1 vencedor por categoria — soma de <strong>votos populares + votos do júri</strong>.
              </div>
              <div class="reg-article mb-3">
                <i class="bi bi-broadcast me-1" style="color:#CDDE00;"></i>
                <strong>Evento híbrido:</strong> <strong>24/09/2026</strong> — divulgação dos ganhadores com transmissão ao vivo.
              </div>

              <h5 class="reg-fase-subtitulo"><i class="bi bi-sort-down me-2"></i>Critérios de desempate</h5>
              <div class="reg-desempate-lista">
                <div class="reg-desempate-item">
                  <span class="reg-desempate-num">1°</span>
                  <span>Total de votos (popular + júri)</span>
                </div>
                <div class="reg-desempate-item">
                  <span class="reg-desempate-num">2°</span>
                  <span>Votos do júri</span>
                </div>
                <div class="reg-desempate-item">
                  <span class="reg-desempate-num">3°</span>
                  <span>Votos populares</span>
                </div>
                <div class="reg-desempate-item">
                  <span class="reg-desempate-num">4°</span>
                  <span>Score geral — tabela <code>scores_negocios</code> (maior vence)</span>
                </div>
                <div class="reg-desempate-item">
                  <span class="reg-desempate-num">5°</span>
                  <span>Data de inscrição (inscrição mais antiga vence)</span>
                </div>
              </div>

            </div>
          </div>
          <!-- /FASES -->

          <!-- Lisura -->
          <h4 class="reg-subsection-title mt-5" id="sec-lisura">5.4 Da lisura das votações</h4>
          <div class="reg-article">Fica <strong>vedado o uso de meios automatizados</strong> (robôs ou similares) para inflar votos. Em caso de identificação, a organização comunicará o inscrito, retirará os votos irregulares e, em caso de reincidência, desclassificará o competidor.</div>
          <div class="reg-article">Não serão aceitos e-mails secundários criados artificialmente, temporários, falsos, não verificáveis ou que impeçam o contato direto com o votante.</div>
          <div class="reg-article">A organização poderá realizar auditorias e análises para garantir a integridade do processo.</div>

          <h4 class="reg-subsection-title">5.5 Substituição em caso de desclassificação</h4>
          <div class="reg-article">No caso de desclassificação durante a votação, o projeto subsequente com maior quantidade de votos será automaticamente classificado para assumir a posição, de forma imediata.</div>

        </section>
        <!-- /VOTAÇÃO -->

        <!-- 6. Júri -->
        <section id="sec-juri" class="reg-section">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num">6</span>
            <h2 class="reg-section-title mb-0">Júri da Premiação</h2>
          </div>
          <div class="reg-article"><span class="reg-article-num">6.1</span> O júri será composto por Membros do Board do Impactos Positivos e representantes de empresas de impacto do ecossistema brasileiro, anunciados pelos meios oficiais.</div>
          <div class="reg-article"><span class="reg-article-num">6.2</span> O time poderá ser alterado durante todo o período da premiação, com divulgação oficial até 18/09/2026.</div>
          <div class="reg-article"><span class="reg-article-num">6.3</span> Cada membro votará em 1 (um) negócio finalista por categoria. O negócio mais votado pelo público recebe 1 (um) ponto adicional. Total por categoria: até <strong>5 pontos</strong> (4 do Júri + 1 do público).</div>
          <div class="reg-article"><span class="reg-article-num">6.4</span> O negócio com maior pontuação combinada será declarado vencedor da respectiva categoria.</div>
        </section>

        <!-- 7. Premiação -->
        <section id="sec-premiacao" class="reg-section">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num">7</span>
            <h2 class="reg-section-title mb-0">Premiação</h2>
          </div>
          <div class="reg-article"><span class="reg-article-num">7.1</span> O maior prêmio será toda a exposição, engajamento e visibilidade gerada pelo Impactos Positivos 2026.</div>
          <div class="reg-article"><span class="reg-article-num">7.2</span> Apoiadores estarão comprometidos em usar suas plataformas e redes sociais para promover os projetos ganhadores de cada categoria.</div>
          <div class="reg-article">
            <span class="reg-article-num">7.3</span> Os vencedores de cada categoria receberão:
            <ul class="mt-2">
              <li>Troféu Prêmio Impactos Positivos 2026.</li>
              <li>As demais premiações serão divulgadas antes do início da primeira fase.</li>
            </ul>
          </div>
          <div class="reg-article"><span class="reg-article-num">7.4</span> Outras premiações poderão ser inseridas neste regulamento até a data final de inscrição.</div>
          <div class="reg-article"><span class="reg-article-num">7.5</span> Os vencedores terão seus nomes divulgados no site do Prêmio e nas redes sociais da Plataforma Impactos Positivos.</div>
          <div class="reg-article"><span class="reg-article-num">7.6</span> Os participantes autorizam a divulgação de seu nome, imagem e voz no site do prêmio em caso de serem eleitos entre os mais votados.</div>
          <div class="reg-article"><span class="reg-article-num">7.7</span> O prêmio só será atribuído após verificação do cumprimento de todas as regras. Em caso de desclassificação ou não aceitação, o participante seguinte assumirá a posição.</div>
          <div class="reg-article"><span class="reg-article-num">7.8</span> O prêmio não poderá em hipótese alguma ser convertido em dinheiro, tampouco trocado por qualquer outro prêmio, produto ou serviço.</div>
        </section>

        <!-- 8. Entrega -->
        <section id="sec-entrega" class="reg-section">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num">8</span>
            <h2 class="reg-section-title mb-0">Entrega dos Prêmios</h2>
          </div>
          <div class="reg-article"><span class="reg-article-num">8.1</span> Os vencedores receberão um voucher com os contatos das consultorias e assessorias apoiadoras para agendamento.</div>
          <div class="reg-article"><span class="reg-article-num">8.2</span> O prêmio será disponibilizado em até 20 (vinte) dias da data da divulgação, sem ônus, devendo o ganhador enviar a documentação necessária.</div>
          <div class="reg-article"><span class="reg-article-num">8.3</span> Todos os prêmios são pessoais e intransferíveis, de uso exclusivo do ganhador inscrito.</div>
          <div class="reg-article"><span class="reg-article-num">8.4</span> É responsabilidade do negócio ganhador o contato e agendamento dos benefícios junto às empresas apoiadoras.</div>
        </section>

        <!-- 9. Geral -->
        <section id="sec-geral" class="reg-section">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num">9</span>
            <h2 class="reg-section-title mb-0">Disposições Gerais</h2>
          </div>
          <div class="reg-article"><span class="reg-article-num">9.1</span> Para viabilizar o projeto contaremos com apoiadores voluntários, sem qualquer relação contratual com o Projeto Impactos Positivos.</div>
          <div class="reg-article"><span class="reg-article-num">9.2</span> É importante que você leia, entenda e esteja de acordo com este regulamento antes de realizar sua inscrição ou votação.</div>
          <div class="reg-article"><span class="reg-article-num">9.3</span> A equipe de tecnologia fornecerá suporte em caso de falhas. Instabilidades por alto volume de acessos não resultarão em prorrogação de prazos.</div>
          <div class="reg-article"><span class="reg-article-num">9.4</span> O participante concorda que a empresa promotora e seus representantes não serão responsáveis por danos oriundos da participação no concurso.</div>
          <div class="reg-article"><span class="reg-article-num">9.5</span> A empresa promotora reserva-se o direito de adiar, modificar ou alterar este concurso, com divulgação em <a href="https://impactospositivos.com" target="_blank" rel="noopener" class="reg-ext-link">impactospositivos.com</a>.</div>
          <div class="reg-article"><span class="reg-article-num">9.6</span> As decisões da empresa promotora são finais e irrecorríveis.</div>
          <div class="reg-article"><span class="reg-article-num">9.7</span> Será automaticamente excluído o participante que tentar fraudar ou burlar as regras estabelecidas.</div>
          <div class="reg-article"><span class="reg-article-num">9.8</span> Denúncias serão analisadas em até 10 dias, garantindo ampla defesa e contraditório ao denunciado.</div>
          <div class="reg-article"><span class="reg-article-num">9.9</span> Após o retorno do denunciado, a promotora analisará os documentos e emitirá sua decisão.</div>
          <div class="reg-article"><span class="reg-article-num">9.10</span> Todo o processo de denúncia será conduzido de forma confidencial.</div>
        </section>

        <!-- 10. LGPD -->
        <section id="sec-dados" class="reg-section">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num">10</span>
            <h2 class="reg-section-title mb-0">Coleta e Uso de Dados Pessoais (LGPD)</h2>
          </div>
          <div class="reg-article"><span class="reg-article-num">10.1</span> Estamos comprometidos com a <strong>Lei Geral de Proteção de Dados (LGPD)</strong>, demonstrando como os dados são tratados e para quais finalidades são coletados.</div>
          <div class="reg-article"><span class="reg-article-num">10.2</span> Para se inscrever será necessário informar dados da pessoa jurídica e pessoais do representante, conforme o item 3, tratados a partir de <a href="https://www.impactospositivos.com" target="_blank" rel="noopener" class="reg-ext-link">www.impactospositivos.com</a>.</div>
          <div class="reg-article"><span class="reg-article-num">10.3</span> Selecionaremos adequadamente as bases legais e atenderemos às solicitações de direitos previstos na LGPD.</div>
          <div class="reg-article">
            <span class="reg-article-num">10.4</span> Dados que poderão ser coletados:
            <ul class="mt-2">
              <li><strong>Cadastro do representante:</strong> nome completo, CPF, data de nascimento, e-mail, telefone, gênero, formação acadêmica, endereço completo.</li>
              <li><strong>Identificação e dispositivo:</strong> endereço IP, MAC, modelo, sistema operacional, navegador, velocidade de conexão e geolocalização.</li>
              <li><strong>Navegação:</strong> cookies, páginas visitadas, buscas realizadas, duração da visita e tipo de navegador.</li>
            </ul>
          </div>
          <div class="reg-article"><span class="reg-article-num">10.5</span> Os dados serão coletados para finalidades específicas, evitando coleta em excesso ou sem propósitos objetivos.</div>
          <div class="reg-article"><span class="reg-article-num">10.6</span> Coletamos dados para: inscrição no prêmio, atendimento, comunicados, cumprimento de determinações judiciais, prevenção a fraudes e registro legal de acesso.</div>
          <div class="reg-article"><span class="reg-article-num">10.7</span> Não nos responsabilizamos pela coleta de dados em sites de terceiros para os quais haja redirecionamento.</div>
          <div class="reg-article">
            <span class="reg-article-num">10.8</span> Categorias de destinatários com os quais podemos compartilhar os dados:
            <ul class="mt-2">
              <li>Empresas que fornecem e auxiliam na prestação dos nossos serviços.</li>
              <li>Terceiros com os quais devemos cumprir obrigações legais (órgãos governamentais, ANPD, poder judiciário).</li>
              <li>Parceiros, patrocinadores e apoiadores que auxiliam nos estudos sobre o ecossistema de impacto do Brasil.</li>
            </ul>
          </div>
          <div class="reg-article">
            <span class="reg-article-num">10.9</span> Para exercer seus direitos previstos na LGPD, envie um e-mail para <a href="mailto:contato@impactospositivos.com" class="reg-ext-link">contato@impactospositivos.com</a>.
          </div>
        </section>

        <!-- Promotora -->
        <section id="sec-promotora" class="reg-section" style="border-bottom:0;">
          <div class="d-flex align-items-start gap-3 mb-3">
            <span class="reg-section-num" style="background:#95BCCC;color:#1E3425;">
              <i class="bi bi-building" style="font-size:.85rem;"></i>
            </span>
            <h2 class="reg-section-title mb-0">É Promotora deste Concurso</h2>
          </div>
          <div class="reg-promotora-box">
            <div class="row g-3">
              <div class="col-md-6">
                <p class="mb-1"><strong>Razão Social:</strong> Global Vision Access Comunicação e Marketing Ltda.</p>
                <p class="mb-1"><strong>Nome Fantasia:</strong> GVA</p>
                <p class="mb-1"><strong>CNPJ:</strong> 08.817.535/0001-61</p>
                <p class="mb-1"><strong>Inscrição Estadual:</strong> Isenta</p>
                <p class="mb-0"><strong>Inscrição Municipal:</strong> 3.633.616-5</p>
              </div>
              <div class="col-md-6">
                <p class="mb-1"><strong>Endereço:</strong> Rua Apeninos, 429 cj. 1206</p>
                <p class="mb-1"><strong>Bairro:</strong> Aclimação — CEP: 01533-000</p>
                <p class="mb-1"><strong>Cidade / Estado:</strong> São Paulo / SP — Brasil</p>
                <p class="mb-0"><strong>Última atualização:</strong> 31 de março de 2026.</p>
              </div>
            </div>
          </div>
          <p class="mt-3" style="font-size:.82rem;color:#6c8070;">
            <sup>[1]</sup> Empresas que fomentam o ecossistema de impacto considerando a receita anual de acordo com a classificação do BNDES.
            Fonte: <a href="https://www.bndes.gov.br/wps/portal/site/home/financiamento/guia/porte-de-empresa" target="_blank" rel="noopener" class="reg-ext-link">bndes.gov.br/porte-de-empresa</a>.
          </p>
        </section>

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
      <!-- /col conteúdo -->
    </div>
    <!-- /row -->
  </div>
  <!-- /container -->
</section>
<!-- /CONTEÚDO -->


<script>
  /* Scroll-spy: destaca o item do índice conforme seção visível */
  (function () {
    const links = document.querySelectorAll('.reg-toc-list a');
    const targets = [];
    links.forEach(function (link) {
      const href = link.getAttribute('href');
      if (href && href.startsWith('#')) {
        const el = document.querySelector(href);
        if (el) targets.push({ link, el });
      }
    });
    function onScroll() {
      let current = null;
      targets.forEach(function ({ el }) {
        if (window.scrollY >= el.offsetTop - 120) current = el;
      });
      links.forEach(function (l) {
        l.classList.remove('reg-toc-link--active');
      });
      if (current) {
        const match = targets.find(function (t) { return t.el === current; });
        if (match) match.link.classList.add('reg-toc-link--active');
      }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
  })();
</script>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>
