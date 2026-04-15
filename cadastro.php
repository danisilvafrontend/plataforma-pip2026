<?php
session_start();

$config = require __DIR__ . '/app/config/db.php';
// 2. Cria a conexção PDO manualmente
try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro ao conectar no banco de dados: " . $e->getMessage());
}
include __DIR__ . '/app/views/public/header_public.php';

?>

<div class="cadastro-civil-page py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-9 col-lg-10">
        <div class="cadastro-card card border-0 shadow-sm overflow-hidden">

          <div class="cadastro-card__top">
            <div class="cadastro-card__badge">Cadastro</div>
            <h1 class="cadastro-card__title">Cadastro da Sociedade Civil</h1>
            <p class="cadastro-card__text mb-0">
              Preencha seus dados em etapas rápidas. Suas informações são usadas apenas para garantir a integridade do voto,
              evitar fraudes e melhorar sua experiência na plataforma.
            </p>
          </div>

          <div class="cadastro-card__progress">
            <div class="cadastro-progress-head">
              <span class="cadastro-progress-label">Etapa <strong id="stepAtualLabel">1</strong> de 3</span>
              <span class="cadastro-progress-label">Dados pessoais</span>
            </div>

            <div class="progress cadastro-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="33">
              <div class="progress-bar" id="cadastroProgressBar" style="width: 33%;"></div>
            </div>

            <div class="cadastro-steps-indicator" aria-label="Progresso do cadastro">
              <div class="cadastro-step-dot is-active" data-step-indicator="0" aria-current="step">
                <span>1</span>
                <small>Dados</small>
              </div>
              <div class="cadastro-step-dot" data-step-indicator="1">
                <span>2</span>
                <small>Interesses</small>
              </div>
              <div class="cadastro-step-dot" data-step-indicator="2">
                <span>3</span>
                <small>Perfil</small>
              </div>
            </div>
          </div>

          <div class="card-body cadastro-card__body">

            <?php if (isset($_SESSION['cadastro_errors']) && is_array($_SESSION['cadastro_errors'])): ?>
              <div class="alert alert-danger cadastro-alert">
                <div class="fw-semibold mb-2">Encontramos alguns pontos para corrigir:</div>
                <ul class="mb-0">
                  <?php foreach ($_SESSION['cadastro_errors'] as $erro): ?>
                    <li><?= htmlspecialchars($erro) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <?php unset($_SESSION['cadastro_errors']); ?>
            <?php endif; ?>

            <form action="/auth/processar_cadastro_sociedade.php" method="post" id="formCadastroComunidade" novalidate>

              <!-- STEP 1 -->
              <section class="step active" id="step1" data-step="0">
                <div class="cadastro-step-header">
                  <span class="cadastro-step-kicker">Primeira etapa</span>
                  <h2 class="cadastro-step-title">Seus dados de acesso</h2>
                  <p class="cadastro-step-desc mb-0">
                    Vamos começar com sua identificação, contato e criação da senha.
                  </p>
                </div>

                <div class="cadastro-block">
                  <div class="cadastro-block__header">
                    <h3 class="cadastro-block__title">Identificação</h3>
                    <p class="cadastro-block__desc mb-0">Esses dados ajudam a validar seu cadastro com segurança.</p>
                  </div>

                  <div class="row g-2">
                    <div class="col-md-6">
                      <label class="form-label cadastro-label" for="cpfInput">
                        CPF <span class="text-danger">*</span>
                      </label>
                      <input
                        type="text"
                        name="cpf"
                        id="cpfInput"
                        class="form-control form-control-lg"
                        placeholder="000.000.000-00"
                        maxlength="14"
                        required
                      >
                      <div class="invalid-feedback" id="cpfFeedback">CPF inválido.</div>
                      <div class="form-text">Digite seu CPF para iniciarmos.</div>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label cadastro-label" for="dataNascimento">
                        Data de nascimento <span class="text-danger">*</span>
                      </label>
                      <input
                        type="date"
                        name="data_nascimento"
                        id="dataNascimento"
                        class="form-control form-control-lg"
                        required
                      >
                    </div>
                  </div>
                </div>

                <div class="cadastro-block">
                  <div class="cadastro-block__header">
                    <h3 class="cadastro-block__title">Informações pessoais</h3>
                    <p class="cadastro-block__desc mb-0">Preencha seus dados básicos de contato.</p>
                  </div>

                  <div class="row g-2">
                    <div class="col-md-6">
                      <label class="form-label cadastro-label">Nome <span class="text-danger">*</span></label>
                      <input type="text" name="nome" class="form-control" placeholder="Seu primeiro nome" required>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label cadastro-label">Sobrenome <span class="text-danger">*</span></label>
                      <input type="text" name="sobrenome" class="form-control" placeholder="Restante do seu nome" required>
                    </div>
                  </div>

                  <div class="row g-2 mt-1">
                    <div class="col-md-6">
                      <label class="form-label cadastro-label">E-mail <span class="text-danger">*</span></label>
                      <input type="email" name="email" class="form-control" placeholder="voce@email.com" required>

                      <div class="form-check form-check-soft mt-2">
                        <input class="form-check-input" type="checkbox" name="email_autorizacao" value="1" id="checkEmail">
                        <label class="form-check-label" for="checkEmail">
                          Aceito receber notificações por e-mail
                        </label>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label cadastro-label">Celular / WhatsApp <span class="text-danger">*</span></label>
                      <input type="text" name="celular" class="form-control celular_mask" placeholder="(00) 00000-0000" required>

                      <div class="form-check form-check-soft mt-2">
                        <input class="form-check-input" type="checkbox" name="celular_autorizacao" value="1" id="checkWhats">
                        <label class="form-check-label" for="checkWhats">
                          Aceito receber notificações por WhatsApp
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="row g-2 mt-1">
                    <div class="col-md-4">
                      <label class="form-label cadastro-label">CEP <span class="text-danger">*</span></label>
                      <input type="text" id="cep" class="form-control cep_mask" placeholder="00000-000" maxlength="9" required>
                    </div>

                    <div class="col-md-4">
                      <label class="form-label cadastro-label">Cidade <span class="text-danger">*</span></label>
                      <input type="text" id="municipio" name="cidade" class="form-control bg-light" readonly required>
                    </div>

                    <div class="col-md-4">
                      <label class="form-label cadastro-label">Estado <span class="text-danger">*</span></label>
                      <input type="text" id="estado" name="estado" class="form-control bg-light" readonly required>
                    </div>
                  </div>
                </div>

                <div class="cadastro-block">
                  <div class="cadastro-block__header">
                    <h3 class="cadastro-block__title">Segurança da conta</h3>
                    <p class="cadastro-block__desc mb-0">Crie uma senha segura para acessar a plataforma.</p>
                  </div>

                  <div class="row g-2">
                    <div class="col-md-6">
                      <label class="form-label cadastro-label">Crie uma senha <span class="text-danger">*</span></label>
                      <input type="password" name="senha" class="form-control" minlength="8" required>
                      <div class="form-text">Mí­nimo de 8 caracteres.</div>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label cadastro-label">Confirme a senha <span class="text-danger">*</span></label>
                      <input type="password" name="senha_confirmacao" class="form-control" required>
                    </div>
                  </div>
                </div>

                <div class="cadastro-block">
                  <div class="cadastro-block__header">
                    <h3 class="cadastro-block__title">Seu perfil profissional</h3>
                    <p class="cadastro-block__desc mb-0">Conte um pouco sobre sua atuação e motivação.</p>
                  </div>

                  <div class="row g-2">
                    <div class="col-md-6">
                      <label class="form-label cadastro-label">Profissão / Área de atuação</label>
                      <select name="profissao" class="form-select" required>
                        <option value="">Selecione...</option>
                        <option value="Saúde">Saúde</option>
                        <option value="Educação">Educação</option>
                        <option value="Tecnologia">Tecnologia</option>
                        <option value="Agronegócio">Agronegócio</option>
                        <option value="Serviços">Serviços</option>
                        <option value="Outro">Outro</option>
                      </select>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label cadastro-label">Organização onde trabalha <span class="text-muted">(opcional)</span></label>
                      <input type="text" name="organizacao" class="form-control">
                    </div>
                  </div>

                  <div class="row g-4 mt-1">
                    <div class="col-md-6">
                      <label class="form-label cadastro-label">Você se identifica como <span class="text-muted">(até 3 escolhas)</span></label>

                      <div class="cadastro-check-grid">
                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="identificacoes[]" value="Sociedade civil">
                          <span>Sociedade civil / cidadão(ã)</span>
                        </label>

                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="identificacoes[]" value="Profissional">
                          <span>Profissional (CLT, autónomo etc.)</span>
                        </label>

                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="identificacoes[]" value="Estudante">
                          <span>Estudante</span>
                        </label>

                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="identificacoes[]" value="Voluntário">
                          <span>Voluntário(a)</span>
                        </label>

                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="identificacoes[]" value="Empreendedor">
                          <span>Empreendedor(a)</span>
                        </label>

                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="identificacoes[]" value="Investidor">
                          <span>Investidor(a)</span>
                        </label>

                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="identificacoes[]" value="Outro">
                          <span>Outro</span>
                        </label>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label cadastro-label">O que te trouxe até aqui hoje?</label>

                      <div class="cadastro-check-grid">
                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="motivacoes[]" value="Votar">
                          <span>Quero votar no prêmio</span>
                        </label>

                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="motivacoes[]" value="Conhecer">
                          <span>Quero conhecer negócios de impacto</span>
                        </label>

                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="motivacoes[]" value="Engajar">
                          <span>Quero me engajar e participar</span>
                        </label>

                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="motivacoes[]" value="Voluntariado">
                          <span>Quero apoiar com voluntariado</span>
                        </label>

                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="motivacoes[]" value="Investir">
                          <span>Quero investir / doar</span>
                        </label>

                        <label class="cadastro-check-card">
                          <input class="form-check-input" type="checkbox" name="motivacoes[]" value="Outro">
                          <span>Outro</span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="cadastro-actions">
                  <div class="cadastro-actions__info">Você está quase terminando a primeira etapa.</div>
                  <button type="button" class="btn btn-primary btn-lg next">Continuar</button>
                </div>
              </section>
            
            
              <!-- STEP 2 -->
              <section class="step" id="step2" data-step="1">
                <div class="cadastro-step-header">
                  <span class="cadastro-step-kicker">Segunda etapa</span>
                  <h2 class="cadastro-step-title">Temas que despertam seu interesse</h2>
                  <p class="cadastro-step-desc mb-0">
                    Selecione os assuntos e Objetivos de Desenvolvimento Sustentável que você mais gostaria de acompanhar.
                  </p>
                </div>

                <div class="cadastro-block">
                  <div class="cadastro-block__header">
                    <h3 class="cadastro-block__title">Temas de interesse</h3>
                    <p class="cadastro-block__desc mb-0">
                      Escolha os temas que mais combinam com suas causas, curiosidades e preferências.
                    </p>
                  </div>

                  <fieldset class="cadastro-fieldset">
                    <legend class="cadastro-legend">
                      Quais temas mais despertam seu interesse?
                    </legend>

                    <div class="row g-2">
                      <div class="col-md-6">
                        <div class="cadastro-check-grid">
                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Meio Ambiente e Clima">
                            <span><i class="bi bi-tree me-2"></i>Meio Ambiente e Clima</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Água e Oceanos">
                            <span><i class="bi bi-droplet me-2"></i>Água e Oceanos</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Biodiversidade e Florestas">
                            <span><i class="bi bi-flower1 me-2"></i>Biodiversidade e Florestas</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Economia Circular">
                            <span><i class="bi bi-recycle me-2"></i>Economia Circular</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Energia Limpa">
                            <span><i class="bi bi-lightning-charge me-2"></i>Energia Limpa</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Segurança Alimentar">
                            <span><i class="bi bi-basket me-2"></i>Segurança Alimentar</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Saúde e Bem-Estar">
                            <span><i class="bi bi-heart-pulse me-2"></i>Saúde e Bem-Estar</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Educação">
                            <span><i class="bi bi-book me-2"></i>Educação</span>
                          </label>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="cadastro-check-grid">
                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Igualdade de Gênero">
                            <span><i class="bi bi-gender-ambiguous me-2"></i>Igualdade de Gênero</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Equidade Racial">
                            <span><i class="bi bi-people me-2"></i>Equidade Racial</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Trabalho e Renda">
                            <span><i class="bi bi-briefcase me-2"></i>Trabalho e Renda</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Cidades Sustentáveis">
                            <span><i class="bi bi-buildings me-2"></i>Cidades Sustentáveis</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Inovação e Tecnologia">
                            <span><i class="bi bi-cpu me-2"></i>Inovação e Tecnologia</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Inclusão Social">
                            <span><i class="bi bi-people-fill me-2"></i>Inclusão Social</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Governança e Transparência">
                            <span><i class="bi bi-shield-check me-2"></i>Governança e Transparência</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="interesses[]" value="Parcerias e Investimento Social">
                            <span><i class="bi bi-person-hearts me-2"></i>Parcerias e Investimento Social</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </fieldset>
                </div>

                <div class="cadastro-block">
                  <div class="cadastro-block__header">
                      <h3 class="cadastro-block__title">ODS de interesse</h3>
                      <p class="cadastro-block__desc mb-0">
                          Quais Objetivos de Desenvolvimento Sustentável (ODS) você mais se identifica ou gostaria de acompanhar?
                      </p>
                  </div>

                  <?php
                  $stmt = $pdo->query("
                      SELECT id, n_ods, nome, icone_url,
                            CAST(REPLACE(n_ods, 'ODS ', '') AS UNSIGNED) AS numero_ods
                      FROM ods
                      ORDER BY CAST(REPLACE(n_ods, 'ODS ', '') AS UNSIGNED) ASC
                  ");
                  $todas_ods = $stmt->fetchAll(PDO::FETCH_ASSOC);
                  ?>

                  <div class="row g-2">
                      <?php foreach ($todas_ods as $ods): ?>
                          <div class="col-12 col-md-6">
                              <label class="cadastro-ods-card">
                                  <input
                                      class="form-check-input"
                                      type="checkbox"
                                      name="ods[]"
                                      value="<?= (int)$ods['id'] ?>"
                                  >

                                  <span class="cadastro-ods-card__content">
                                      <?php if (!empty($ods['icone_url'])): ?>
                                          <img
                                              src="<?= htmlspecialchars($ods['icone_url']) ?>"
                                              alt="ODS <?= (int)$ods['n_ods'] ?>"
                                              class="cadastro-ods-card__icon"
                                          >
                                      <?php endif; ?>

                                      <span class="cadastro-ods-card__text">
                                          <strong>ODS <?= (int)$ods['numero_ods'] ?> - <?= htmlspecialchars($ods['nome']) ?></strong>
                                      </span>
                                  </span>
                              </label>
                          </div>
                      <?php endforeach; ?>
                  </div>
                </div>

                <div class="cadastro-actions">
                  <button type="button" class="btn btn-outline-secondary btn-lg prev">Voltar</button>
                  <button type="button" class="btn btn-primary btn-lg next">Continuar</button>
                </div>
              </section>

            
              <!-- STEP 3 -->
              <section class="step" id="step3" data-step="2">
                <div class="cadastro-step-header">
                  <span class="cadastro-step-kicker">Terceira etapa</span>
                  <h2 class="cadastro-step-title">Mapeamento de interesses e perfil de impacto</h2>
                  <p class="cadastro-step-desc mb-0">
                    Agora falta pouco. Conte quais tipos de negócios, setores e causas você prefere acompanhar.
                  </p>
                </div>

                <div class="cadastro-block">
                  <div class="cadastro-block__header">
                    <h3 class="cadastro-block__title">Estágio de maturidade</h3>
                    <p class="cadastro-block__desc mb-0">
                      Selecione os estágios dos negócios que mais fazem sentido para você acompanhar.
                    </p>
                  </div>

                  <fieldset class="cadastro-fieldset">
                    <legend class="cadastro-legend">
                      Você prefere acompanhar negócios em qual estágio de maturidade?
                    </legend>

                    <div class="row g-2">
                      <div class="col-md-6">
                        <div class="cadastro-check-grid">
                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="maturidade[]" value="Ideação">
                            <span><i class="bi bi-lightbulb me-2"></i>Ideação (começando agora)</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="maturidade[]" value="Operação">
                            <span><i class="bi bi-rocket me-2"></i>Operação (modelo sendo testado)</span>
                          </label>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="cadastro-check-grid">
                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="maturidade[]" value="Tração / Escala">
                            <span><i class="bi bi-graph-up me-2"></i>Tração / Escala (já operando e expandindo)</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="maturidade[]" value="Dinamizador">
                            <span><i class="bi bi-globe me-2"></i>Dinamizador (impacto consolidado e ampliando alcance)</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </fieldset>
                </div>

                <div class="cadastro-block">
                  <div class="cadastro-block__header">
                    <h3 class="cadastro-block__title">Setores de interesse</h3>
                    <p class="cadastro-block__desc mb-0">
                      Escolha os setores que você gostaria de acompanhar mais de perto.
                    </p>
                  </div>

                  <fieldset class="cadastro-fieldset">
                    <legend class="cadastro-legend">
                      Há algum setor especí­fico que você gostaria de acompanhar?
                    </legend>

                    <div class="row g-2">
                      <div class="col-md-6">
                        <div class="cadastro-check-grid">
                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Tecnologia">
                            <span><i class="bi bi-cpu me-2"></i>Tecnologia</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Agronegócio sustentável">
                            <span><i class="bi bi-tree me-2"></i>Agronegócio sustentável</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Saúde">
                            <span><i class="bi bi-heart-pulse me-2"></i>Saúde</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Educação">
                            <span><i class="bi bi-book me-2"></i>Educação</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Finanças de impacto">
                            <span><i class="bi bi-cash-stack me-2"></i>Finanças de impacto</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Energia">
                            <span><i class="bi bi-lightning-charge me-2"></i>Energia</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Moda sustentável">
                            <span><i class="bi bi-bag me-2"></i>Moda sustentável</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Alimentação">
                            <span><i class="bi bi-basket me-2"></i>Alimentação</span>
                          </label>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="cadastro-check-grid">
                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Construção civil">
                            <span><i class="bi bi-building me-2"></i>Construção civil</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Cultura">
                            <span><i class="bi bi-music-note me-2"></i>Cultura</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="ESG corporativo">
                            <span><i class="bi bi-bar-chart me-2"></i>ESG corporativo</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Startups">
                            <span><i class="bi bi-lightbulb me-2"></i>Startups</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Negócios sociais">
                            <span><i class="bi bi-people me-2"></i>Negócios sociais</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="Cooperativas">
                            <span><i class="bi bi-diagram-3 me-2"></i>Cooperativas</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="setores[]" value="ONGs">
                            <span><i class="bi bi-hand-thumbs-up me-2"></i>ONGs</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </fieldset>
                </div>

                <div class="cadastro-block">
                  <div class="cadastro-block__header">
                    <h3 class="cadastro-block__title">Perfil de impacto</h3>
                    <p class="cadastro-block__desc mb-0">
                      Indique o tipo de impacto e de causa que você gostaria de ver mais na plataforma.
                    </p>
                  </div>

                  <fieldset class="cadastro-fieldset">
                    <legend class="cadastro-legend">
                      Que tipo de impacto você quer ver mais?
                    </legend>

                    <div class="row g-2">
                      <div class="col-md-6">
                        <div class="cadastro-check-grid">
                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Social">
                            <span><i class="bi bi-people me-2"></i>Social</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Ambiental">
                            <span><i class="bi bi-tree me-2"></i>Ambiental</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Social + Ambiental">
                            <span><i class="bi bi-globe me-2"></i>Social + Ambiental</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Inovação tecnológica">
                            <span><i class="bi bi-cpu me-2"></i>Inovação tecnológica</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Base comunitária">
                            <span><i class="bi bi-house me-2"></i>Base comunitária</span>
                          </label>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="cadastro-check-grid">
                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Liderado por mulheres">
                            <span><i class="bi bi-gender-female me-2"></i>Liderado por mulheres</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Liderado por jovens">
                            <span><i class="bi bi-person me-2"></i>Liderado por jovens</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Impacto regional / local">
                            <span><i class="bi bi-geo-alt me-2"></i>Impacto regional / local</span>
                          </label>

                          <label class="cadastro-check-card">
                            <input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Impacto global">
                            <span><i class="bi bi-globe2 me-2"></i>Impacto global</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </fieldset>
                </div>

                <div class="cadastro-block">
                  <div class="cadastro-block__header">
                    <h3 class="cadastro-block__title">Alcance preferido</h3>
                    <p class="cadastro-block__desc mb-0">
                      Escolha o tipo de alcance das causas que você prefere acompanhar.
                    </p>
                  </div>

                  <fieldset class="cadastro-fieldset">
                    <legend class="cadastro-legend">
                      Você prefere causas locais, nacionais ou globais?
                    </legend>

                    <div class="cadastro-radio-grid">
                      <label class="cadastro-radio-card">
                        <input class="form-check-input" type="radio" name="alcance" value="Local">
                        <span>Local</span>
                      </label>

                      <label class="cadastro-radio-card">
                        <input class="form-check-input" type="radio" name="alcance" value="Nacional">
                        <span>Nacional</span>
                      </label>

                      <label class="cadastro-radio-card">
                        <input class="form-check-input" type="radio" name="alcance" value="Global">
                        <span>Global</span>
                      </label>

                      <label class="cadastro-radio-card">
                        <input class="form-check-input" type="radio" name="alcance" value="Todos">
                        <span>Todos</span>
                      </label>
                    </div>
                  </fieldset>
                </div>

                <div class="cadastro-submit-box">
                  <div>
                    <h3 class="cadastro-submit-box__title">Tudo pronto para finalizar</h3>
                    <p class="cadastro-submit-box__text mb-0">
                      Revise suas escolhas, volte se precisar ajustar alguma informação e finalize seu cadastro.
                    </p>
                  </div>

                  <div class="cadastro-actions cadastro-actions--final">
                    <button type="button" class="btn btn-outline-secondary btn-lg prev">Voltar</button>
                    <button type="submit" class="btn btn-success btn-lg" id="btnSubmit">Finalizar cadastro</button>
                  </div>
                </div>
              </section>

            </form>

            <div class="text-center mt-4">
              <a href="/login.php" class="cadastro-login-link text-decoration-none">
                Já tem conta? Faça login
              </a>
            </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('formCadastroComunidade');
  if (!form) return;

  const steps = Array.from(document.querySelectorAll('.step'));
  const nextButtons = document.querySelectorAll('.next');
  const prevButtons = document.querySelectorAll('.prev');
  const progressBar = document.getElementById('cadastroProgressBar');
  const stepAtualLabel = document.getElementById('stepAtualLabel');
  const stepIndicators = document.querySelectorAll('[data-step-indicator]');
  const progressContainer = document.querySelector('.cadastro-progress-bar');

  const cpfInput = document.getElementById('cpfInput');
  const cpfFeedback = document.getElementById('cpfFeedback');

  const stepNames = [
    'Dados pessoais',
    'Interesses',
    'Perfil'
  ];

  let currentStep = 0;

  function getStepPercent(index) {
    return ((index + 1) / steps.length) * 100;
  }

  function updateProgress(index) {
    const percent = getStepPercent(index);

    if (progressBar) {
      progressBar.style.width = percent + '%';
    }

    if (progressContainer) {
      progressContainer.setAttribute('aria-valuenow', String(Math.round(percent)));
    }

    if (stepAtualLabel) {
      stepAtualLabel.textContent = String(index + 1);
    }

    const progressLabels = document.querySelectorAll('.cadastro-progress-label');
    if (progressLabels.length > 1) {
      progressLabels[1].textContent = stepNames[index] || `Etapa ${index + 1}`;
    }

    stepIndicators.forEach((indicator, i) => {
      indicator.classList.remove('is-active', 'is-complete');
      indicator.removeAttribute('aria-current');

      if (i < index) {
        indicator.classList.add('is-complete');
      } else if (i === index) {
        indicator.classList.add('is-active');
        indicator.setAttribute('aria-current', 'step');
      }
    });
  }

  function showStep(index) {
    steps.forEach((step, i) => {
      step.classList.toggle('active', i === index);
    });

    currentStep = index;
    updateProgress(index);

    const firstField = steps[index].querySelector('input, select, textarea');
    if (firstField) {
      setTimeout(() => firstField.focus(), 120);
    }

    window.scrollTo({
      top: form.offsetTop - 40,
      behavior: 'smooth'
    });
  }

  function aplicarMascaraCPF(valor) {
    let v = valor.replace(/\D/g, '');
    if (v.length > 11) v = v.substring(0, 11);

    if (v.length > 9) {
      v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    } else if (v.length > 6) {
      v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    } else if (v.length > 3) {
      v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    }

    return v;
  }

  function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]+/g, '');
    if (cpf === '') return false;

    if (
      cpf.length !== 11 ||
      cpf === '00000000000' ||
      cpf === '11111111111' ||
      cpf === '22222222222' ||
      cpf === '33333333333' ||
      cpf === '44444444444' ||
      cpf === '55555555555' ||
      cpf === '66666666666' ||
      cpf === '77777777777' ||
      cpf === '88888888888' ||
      cpf === '99999999999'
    ) {
      return false;
    }

    let add = 0;
    for (let i = 0; i < 9; i++) {
      add += parseInt(cpf.charAt(i), 10) * (10 - i);
    }

    let rev = 11 - (add % 11);
    if (rev === 10 || rev === 11) rev = 0;
    if (rev !== parseInt(cpf.charAt(9), 10)) return false;

    add = 0;
    for (let i = 0; i < 10; i++) {
      add += parseInt(cpf.charAt(i), 10) * (11 - i);
    }

    rev = 11 - (add % 11);
    if (rev === 10 || rev === 11) rev = 0;
    if (rev !== parseInt(cpf.charAt(10), 10)) return false;

    return true;
  }

  function validarInputCPF() {
    if (!cpfInput) return true;

    const valor = cpfInput.value;
    const limpo = valor.replace(/\D/g, '');

    if (limpo.length === 0) {
      cpfInput.classList.remove('is-valid', 'is-invalid');
      cpfInput.setCustomValidity('CPF obrigatÃ³rio');
      if (cpfFeedback) cpfFeedback.textContent = 'Informe seu CPF.';
      return false;
    }

    if (limpo.length < 11) {
      cpfInput.classList.remove('is-valid');
      cpfInput.classList.add('is-invalid');
      cpfInput.setCustomValidity('CPF incompleto');
      if (cpfFeedback) cpfFeedback.textContent = 'CPF incompleto.';
      return false;
    }

    if (!validarCPF(limpo)) {
      cpfInput.classList.remove('is-valid');
      cpfInput.classList.add('is-invalid');
      cpfInput.setCustomValidity('CPF invÃ¡lido');
      if (cpfFeedback) cpfFeedback.textContent = 'CPF invÃ¡lido.';
      return false;
    }

    cpfInput.classList.remove('is-invalid');
    cpfInput.classList.add('is-valid');
    cpfInput.setCustomValidity('');
    return true;
  }

  function validarGrupoLimite(name, maximo) {
    const itens = form.querySelectorAll(`input[name="${name}"]`);
    if (!itens.length) return true;

    const marcados = Array.from(itens).filter(item => item.checked);
    const valido = marcados.length <= maximo;

    itens.forEach(item => {
      item.setCustomValidity(valido ? '' : `Selecione no mÃ¡ximo ${maximo} opÃ§Ãµes.`);
    });

    return valido;
  }

  function validarStep(stepIndex) {
    const step = steps[stepIndex];
    if (!step) return true;

    const fields = step.querySelectorAll('input, select, textarea');
    let isStepValid = true;

    fields.forEach(field => {
      if (field.disabled || field.type === 'hidden') return;

      if (!field.checkValidity()) {
        isStepValid = false;
      }
    });

    if (step.contains(cpfInput)) {
      if (!validarInputCPF()) {
        isStepValid = false;
      }
    }

    if (stepIndex === 0) {
      if (!validarGrupoLimite('identificacoes[]', 3)) {
        isStepValid = false;
      }
    }

    step.classList.add('was-validated');

    const firstInvalid = step.querySelector(':invalid');
    if (firstInvalid) {
      firstInvalid.focus();
    }

    return isStepValid;
  }

  if (cpfInput) {
    cpfInput.addEventListener('input', function (e) {
      e.target.value = aplicarMascaraCPF(e.target.value);
      validarInputCPF();
    });

    cpfInput.addEventListener('blur', function () {
      validarInputCPF();
    });
  }

  const identificacoes = form.querySelectorAll('input[name="identificacoes[]"]');
  identificacoes.forEach(item => {
    item.addEventListener('change', function () {
      validarGrupoLimite('identificacoes[]', 3);
    });
  });

  nextButtons.forEach(button => {
    button.addEventListener('click', function () {
      if (!validarStep(currentStep)) return;

      if (currentStep < steps.length - 1) {
        showStep(currentStep + 1);
      }
    });
  });

  prevButtons.forEach(button => {
    button.addEventListener('click', function () {
      if (currentStep > 0) {
        showStep(currentStep - 1);
      }
    });
  });

  form.addEventListener('submit', function (event) {
    const cpfOk = validarInputCPF();
    const limiteOk = validarGrupoLimite('identificacoes[]', 3);
    const currentStepOk = validarStep(currentStep);

    if (!form.checkValidity() || !cpfOk || !limiteOk || !currentStepOk) {
      event.preventDefault();
      event.stopPropagation();

      const firstInvalidGlobal = form.querySelector(':invalid');
      if (firstInvalidGlobal) {
        const invalidStep = firstInvalidGlobal.closest('.step');
        if (invalidStep) {
          const invalidStepIndex = steps.indexOf(invalidStep);
          if (invalidStepIndex >= 0) {
            showStep(invalidStepIndex);
          }
        }

        setTimeout(() => firstInvalidGlobal.focus(), 120);
      }
    }

    form.classList.add('was-validated');
  });

  showStep(0);
});
</script>
<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>