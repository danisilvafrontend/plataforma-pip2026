<?php
// intro_text_dados_negocio.php
// Uso: inclua este arquivo no início da etapa para mostrar o texto padrão de orientação
?>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <h3 class="fw-bold">
      <i class="bi bi-building me-2"></i> Dados básicos do negócio
    </h3>
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
      <i class="bi bi-info-circle me-2"></i> Em caso de dúvida, consulte o material de apoio: 
      <button type="button" class="btn btn-link p-0 fw-bold" data-bs-toggle="modal" data-bs-target="#modalAnexo1">
        Formato Legal
      </button> e 
      <button type="button" class="btn btn-link p-0 fw-bold" data-bs-toggle="modal" data-bs-target="#modalAnexo2">
        Setor de Atuação
      </button>.
    </p>
    <div class="mt-3">
      <small class="d-block text-muted">
        <i class="bi bi-eye text-secondary me-1"></i> Público — será exibido na vitrine
      </small>
      <small class="d-block text-muted">
        <i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Privado — visível apenas para análise interna
      </small>
    </div>
  </div>
</div>

<!-- Modal Anexo 1 -->
<div class="modal fade" id="modalAnexo1" tabindex="-1" aria-labelledby="modalAnexo1Label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalAnexo1Label">Anexo 1 – Formato Legal do Negócio</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p>
          <strong>Selecione o enquadramento jurídico atual do seu empreendimento.</strong><br>
          Essas informações nos ajudam a compreender a estrutura legal e o estágio de formalização do seu negócio.
        </p>
        <p>Veja abaixo as definições de cada formato:</p>

        <div class="list-group">
          <div class="list-group-item">
            <h6 class="mb-1">Organização da Sociedade Civil (OSC)</h6>
            <p class="mb-0 text-muted">Entidade privada sem fins lucrativos com atuação voltada ao interesse público (ex: associações, institutos, fundações, ONGs).</p>
          </div>

          <div class="list-group-item">
            <h6 class="mb-1">MEI – Microempreendedor Individual</h6>
            <p class="mb-0 text-muted">Pessoa que trabalha por conta própria, formalizada com CNPJ e receita bruta anual limitada (até R$ 81 mil/ano), com regime simplificado de tributação.</p>
          </div>

          <div class="list-group-item">
            <h6 class="mb-1">Cooperativa</h6>
            <p class="mb-0 text-muted">Sociedade formada por pessoas com interesses comuns, que se organizam de forma democrática para produzir ou consumir bens e serviços coletivamente.</p>
          </div>

          <div class="list-group-item">
            <h6 class="mb-1">Sociedade Limitada (LTDA)</h6>
            <p class="mb-0 text-muted">Empresa com dois ou mais sócios, com responsabilidade limitada ao valor das cotas. É o formato mais comum entre pequenos e médios negócios.</p>
          </div>

          <div class="list-group-item">
            <h6 class="mb-1">Sociedade Anônima (S.A.)</h6>
            <p class="mb-0 text-muted">Empresa de capital dividido em ações, podendo ser de capital aberto ou fechado. Indicada para negócios que visam captar investimento em larga escala.</p>
          </div>

          <div class="list-group-item">
            <h6 class="mb-1">Empresa Individual de Responsabilidade Limitada (EIRELI)</h6>
            <p class="mb-0 text-muted">Empresa com apenas um titular, que separa o patrimônio pessoal do empresarial. Requer capital social mínimo de 100 salários mínimos (nota: esse formato foi absorvido pelo SLU, mas ainda pode constar em registros antigos).</p>
          </div>

          <div class="list-group-item">
            <h6 class="mb-1">Outros</h6>
            <p class="mb-0 text-muted">Use esta opção se o seu modelo não se encaixa nas categorias anteriores. Ex: negócio informal em processo de formalização, consórcios, ou formatos híbridos.</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Anexo 2 -->
<div class="modal fade" id="modalAnexo2" tabindex="-1" aria-labelledby="modalAnexo2Label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalAnexo2Label">Anexo 2 – Setores de Atuação</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">

        <!-- Setor Primário -->
        <h5 class="mt-3">Setor Primário</h5>
        <p class="text-muted">Atividades relacionadas à extração e produção de recursos naturais:</p>
        <ul>
          <li>Agricultura (soja, milho, café, cana-de-açúcar, hortaliças etc.)</li>
          <li>Pecuária (gado de corte, leite, aves, suínos)</li>
          <li>Pesca (artesanal e industrial)</li>
          <li>Silvicultura (reflorestamento, produção de madeira e celulose)</li>
          <li>Extração vegetal (castanha, borracha, óleos)</li>
          <li>Mineração (ferro, ouro, bauxita, nióbio, petróleo bruto)</li>
          <li>Outro</li>
        </ul>

        <!-- Setor Secundário -->
        <h5 class="mt-4">Setor Secundário</h5>
        <p class="text-muted">Atividades ligadas à transformação industrial e construção civil:</p>
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
          <li>Geração e distribuição de energia (também pode ser considerada terciária em alguns contextos)</li>
          <li>Outro</li>
        </ul>

        <!-- Setor Terciário -->
        <h5 class="mt-4">Setor Terciário</h5>
        <p class="text-muted">Atividades de comércio, serviços e distribuição:</p>
        <ul>
          <li>Comércio varejista e atacadista</li>
          <li>Transporte e logística (rodoviário, ferroviário, aéreo, marítimo)</li>
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
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>