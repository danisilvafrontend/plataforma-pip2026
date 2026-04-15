<?php
// /app/views/partials/intro_text_dados_negocio.php
?>

<!-- ── Card introdutório ─────────────────────────────────── -->
<div class="intro-card">
  <div class="intro-card-header">
    <div class="header-icon"><i class="bi bi-building"></i></div>
    <h3>Dados básicos do negócio</h3>
  </div>
  <div class="intro-card-body">
    <p>
      Nesta etapa reunimos os <strong>dados cadastrais</strong> e <strong>estruturais</strong> do seu
      <strong>negócio de impacto</strong> — porte, setor, formato jurídico, tempo de atuação e presença digital.
    </p>
    <p>
      Essas informações são essenciais para <strong>classificar corretamente</strong> o empreendimento,
      identificar <strong>oportunidades de apoio</strong> e garantir alinhamento às
      <strong>categorias do Prêmio</strong>.
    </p>
    <p>
      <i class="bi bi-info-circle me-1" style="color:#95BCCC;"></i>
      Em caso de dúvida, consulte o material de apoio:
      <button type="button" class="intro-modal-link" data-bs-toggle="modal" data-bs-target="#modalAnexo1">
        Formato Legal
      </button>
      e
      <button type="button" class="intro-modal-link" data-bs-toggle="modal" data-bs-target="#modalAnexo2">
        Setor de Atuação
      </button>.
    </p>

    <div class="intro-legend">
      <div class="intro-legend-item">
        <span class="legend-dot pub"><i class="bi bi-eye-fill"></i></span>
        Público — será exibido na vitrine
      </div>
      <div class="intro-legend-item">
        <span class="legend-dot priv"><i class="bi bi-eye-slash-fill"></i></span>
        Privado — visível apenas para análise interna
      </div>
    </div>
  </div>
</div>

<!-- ── Modal Anexo 1 — Formato Legal ────────────────────── -->
<div class="modal fade ip-modal" id="modalAnexo1" tabindex="-1" aria-labelledby="modalAnexo1Label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border:none;border-radius:14px;overflow:hidden;">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAnexo1Label">
          <i class="bi bi-file-earmark-text me-2" style="color:#CDDE00;"></i>Anexo 1 – Formato Legal do Negócio
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p>
          <strong>Selecione o enquadramento jurídico atual do seu empreendimento.</strong><br>
          Essas informações nos ajudam a compreender a estrutura legal e o estágio de formalização do seu negócio.
        </p>

        <div class="list-group list-group-flush">
          <div class="list-group-item">
            <h6>Organização da Sociedade Civil (OSC)</h6>
            <p>Entidade privada sem fins lucrativos com atuação voltada ao interesse público (ex: associações, institutos, fundações, ONGs).</p>
          </div>
          <div class="list-group-item">
            <h6>MEI – Microempreendedor Individual</h6>
            <p>Pessoa que trabalha por conta própria, formalizada com CNPJ e receita bruta anual limitada (até R$ 81 mil/ano), com regime simplificado de tributação.</p>
          </div>
          <div class="list-group-item">
            <h6>Cooperativa</h6>
            <p>Sociedade formada por pessoas com interesses comuns, que se organizam de forma democrática para produzir ou consumir bens e serviços coletivamente.</p>
          </div>
          <div class="list-group-item">
            <h6>Sociedade Limitada (LTDA)</h6>
            <p>Empresa com dois ou mais sócios, com responsabilidade limitada ao valor das cotas. É o formato mais comum entre pequenos e médios negócios.</p>
          </div>
          <div class="list-group-item">
            <h6>Sociedade Anônima (S.A.)</h6>
            <p>Empresa de capital dividido em ações, podendo ser de capital aberto ou fechado. Indicada para negócios que visam captar investimento em larga escala.</p>
          </div>
          <div class="list-group-item">
            <h6>Empresa Individual de Responsabilidade Limitada (EIRELI)</h6>
            <p>Empresa com apenas um titular, que separa o patrimônio pessoal do empresarial. Requer capital social mínimo de 100 salários mínimos (nota: esse formato foi absorvido pelo SLU, mas ainda pode constar em registros antigos).</p>
          </div>
          <div class="list-group-item">
            <h6>Outros</h6>
            <p>Use esta opção se o seu modelo não se encaixa nas categorias anteriores. Ex: negócio informal em processo de formalização, consórcios, ou formatos híbridos.</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-modal-fechar" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal Anexo 2 — Setores de Atuação ───────────────── -->
<div class="modal fade ip-modal" id="modalAnexo2" tabindex="-1" aria-labelledby="modalAnexo2Label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border:none;border-radius:14px;overflow:hidden;">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAnexo2Label">
          <i class="bi bi-diagram-3 me-2" style="color:#CDDE00;"></i>Anexo 2 – Setores de Atuação
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">

        <h5 class="setor-titulo">Setor Primário</h5>
        <p style="color:#6c8070;font-size:.85rem;">Atividades relacionadas à extração e produção de recursos naturais:</p>
        <ul>
          <li>Agricultura (soja, milho, café, cana-de-açúcar, hortaliças etc.)</li>
          <li>Pecuária (gado de corte, leite, aves, suínos)</li>
          <li>Pesca (artesanal e industrial)</li>
          <li>Silvicultura (reflorestamento, produção de madeira e celulose)</li>
          <li>Extração vegetal (castanha, borracha, óleos)</li>
          <li>Mineração (ferro, ouro, bauxita, nióbio, petróleo bruto)</li>
          <li>Outro</li>
        </ul>

        <h5 class="setor-titulo">Setor Secundário</h5>
        <p style="color:#6c8070;font-size:.85rem;">Atividades ligadas à transformação industrial e construção civil:</p>
        <ul>
          <li>Indústrias alimentícias e bebidas</li>
          <li>Indústria têxtil e de vestuário</li>
          <li>Indústria automobilística</li>
          <li>Indústria química e petroquímica</li>
          <li>Indústria farmacêutica</li>
          <li>Indústria de papel e celulose</li>
          <li>Indústria de cimento e construção civil</li>
          <li>Siderurgia e metalurgia</li>
          <li>Indústria de eletroeletrônicos e tecnologia</li>
          <li>Geração e distribuição de energia</li>
          <li>Outro</li>
        </ul>

        <h5 class="setor-titulo">Setor Terciário</h5>
        <p style="color:#6c8070;font-size:.85rem;">Atividades de comércio, serviços e distribuição:</p>
        <ul>
          <li>Comércio varejista e atacadista</li>
          <li>Transporte e logística</li>
          <li>Serviços financeiros (bancos, fintechs, cooperativas de crédito)</li>
          <li>Educação (escolas, universidades, cursos técnicos)</li>
          <li>Saúde (hospitais, clínicas, laboratórios)</li>
          <li>Turismo, hotelaria e eventos</li>
          <li>Tecnologia e serviços digitais (startups, plataformas, TI)</li>
          <li>Serviços jurídicos e contábeis</li>
          <li>Comunicação e marketing</li>
          <li>Administração pública e serviços sociais</li>
          <li>Serviços de limpeza, segurança e manutenção</li>
          <li>Entretenimento e cultura (cinema, música, teatro)</li>
          <li>Outro</li>
        </ul>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn-modal-fechar" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>