<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$config = require __DIR__ . '/app/config/db.php';
// 2. Cria a conexão PDO manualmente
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

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h2 class="h5 mb-0">Cadastro da Sociedade Civil</h2>
        </div>
        <div class="card-body">
        
          <?php if (isset($_SESSION['cadastro_errors']) && is_array($_SESSION['cadastro_errors'])): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($_SESSION['cadastro_errors'] as $erro): ?>
                  <li><?= htmlspecialchars($erro) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <?php unset($_SESSION['cadastro_errors']); ?>
          <?php endif; ?>

          <form action="/auth/processar_cadastro_sociedade.php" method="post" id="formCadastroComunidade" novalidate>

            <p class="mb-3">Seus dados são protegidos e usados apenas para garantir a integridade do voto, evitar fraudes e melhorar sua experiência na plataforma.</p>
            <div class="step active" id="step1">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Nome</label>
                  <input type="text" name="nome" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Sobrenome</label>
                  <input type="text" name="sobrenome" class="form-control" required>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control" required>
                  <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" name="email_autorizacao" value="1">
                    <label class="form-check-label">Aceito receber notificações por e-mail</label>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Celular/WhatsApp</label>
                  <input type="text" name="celular" class="form-control" required>
                  <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" name="celular_autorizacao" value="1">
                    <label class="form-check-label">Aceito receber notificações por WhatsApp</label>
                  </div>
                </div>
              </div>    
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">CPF</label>
                  <input type="text" name="cpf" id="cpfInput" class="form-control" placeholder="000.000.000-00" maxlength="14" required>
                  <div class="invalid-feedback" id="cpfFeedback">CPF inválido.</div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Data de Nascimento</label>
                  <input type="date" name="data_nascimento" class="form-control">
                </div>
              </div>

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label">CEP</label>
                  <input type="text" id="cep" class="form-control" maxlength="9" required>
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label">Cidade</label>
                  <input type="text" id="municipio" name="cidade" class="form-control" readonly required>
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label">Estado</label>
                  <input type="text" id="estado" name="estado" class="form-control" readonly required>
                </div>
              </div>


              <div class="mb-3">
                <label class="form-label">Senha</label>
                <input type="password" name="senha" class="form-control" minlength="8" required>
                <div class="form-text">Mínimo de 8 caracteres.</div>
              </div>

              <div class="mb-3">
                <label class="form-label">Confirme a Senha</label>
                <input type="password" name="senha_confirmacao" class="form-control" required>
              </div>

              <h3>Seu Perfil</h3>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Profissão / área de atuação</label>
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

                <div class="col-md-6 mb-3">
                  <label class="form-label">Organização onde trabalha (opcional)</label>
                  <input type="text" name="organizacao" class="form-control">
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Você se identifica como (até 3 escolhas)</label>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="identificacoes[]" value="Sociedade civil"> Sociedade civil / cidadão(ã)</div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="identificacoes[]" value="Profissional"> Profissional (CLT, autônomo etc.)</div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="identificacoes[]" value="Estudante"> Estudante</div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="identificacoes[]" value="Voluntário"> Voluntário(a)</div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="identificacoes[]" value="Empreendedor"> Empreendedor(a)</div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="identificacoes[]" value="Investidor"> Investidor(a)</div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="identificacoes[]" value="Outro"> Outro</div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">O que te trouxe até aqui hoje?</label>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="motivacoes[]" value="Votar"> Quero votar no prêmio</div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="motivacoes[]" value="Conhecer"> Quero conhecer negócios de impacto</div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="motivacoes[]" value="Engajar"> Quero me engajar e participar</div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="motivacoes[]" value="Voluntariado"> Quero apoiar com voluntariado</div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="motivacoes[]" value="Investir"> Quero investir/doar</div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="motivacoes[]" value="Outro"> Outro</div>
                </div>
              </div>
                

              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="button" class="btn btn-primary next">Próximo</button>
              </div>

            </div>
            
            
            <div class="step active" id="step2">
              <h3>Temas de interesse</h3>

              <div class="mb-3">
                <label class="form-label">Quais temas mais despertam seu interesse?</label>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Meio Ambiente e Clima">
                      <label class="form-check-label"><i class="bi bi-tree"></i> Meio Ambiente e Clima</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Água e Oceanos">
                      <label class="form-check-label"><i class="bi bi-droplet"></i> Água e Oceanos</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Biodiversidade e Florestas">
                      <label class="form-check-label"><i class="bi bi-flower1"></i> Biodiversidade e Florestas</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Economia Circular">
                      <label class="form-check-label"><i class="bi bi-recycle"></i> Economia Circular</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Energia Limpa">
                      <label class="form-check-label"><i class="bi bi-lightning-charge"></i> Energia Limpa</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Segurança Alimentar">
                      <label class="form-check-label"><i class="bi bi-basket"></i> Segurança Alimentar</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Saúde e Bem-Estar">
                      <label class="form-check-label"><i class="bi bi-heart-pulse"></i> Saúde e Bem-Estar</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Educação">
                      <label class="form-check-label"><i class="bi bi-book"></i> Educação</label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Igualdade de Gênero">
                      <label class="form-check-label"><i class="bi bi-gender-ambiguous"></i> Igualdade de Gênero</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Equidade Racial">
                      <label class="form-check-label"><i class="bi bi-people"></i> Equidade Racial</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Trabalho e Renda">
                      <label class="form-check-label"><i class="bi bi-briefcase"></i> Trabalho e Renda</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Cidades Sustentáveis">
                      <label class="form-check-label"><i class="bi bi-buildings"></i> Cidades Sustentáveis</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Inovação e Tecnologia">
                      <label class="form-check-label"><i class="bi bi-cpu"></i> Inovação e Tecnologia</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Inclusão Social">
                      <label class="form-check-label"><i class="bi bi-people-fill"></i> Inclusão Social</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Governança e Transparência">
                      <label class="form-check-label"><i class="bi bi-shield-check"></i> Governança e Transparência</label>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="interesses[]" value="Parcerias e Investimento Social">
                      <label class="form-check-label"><i class="bi bi-person-hearts"></i> Parcerias e Investimento Social</label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <p>Quais ODS você mais se identifica ou gostaria de acompanhar?</p>
                <div class="row">
                  <div class="col-md-6">
                    <?php
                    $stmt = $pdo->query("SELECT * FROM ods ORDER BY n_ods ASC");
                    $ods = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $half = ceil(count($ods) / 2);

                    for ($i = 0; $i < $half; $i++): ?>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ods[]" value="<?= $ods[$i]['n_ods'] ?>">
                        <label class="form-check-label">
                          <img src="<?= htmlspecialchars($ods[$i]['icone_url']) ?>" alt="ODS <?= $ods[$i]['n_ods'] ?>" style="width:24px;height:24px;">
                          <?= htmlspecialchars($ods[$i]['nome']) ?>
                        </label>
                      </div>
                    <?php endfor; ?>
                  </div>

                  <div class="col-md-6">
                    <?php for ($i = $half; $i < count($ods); $i++): ?>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ods[]" value="<?= $ods[$i]['n_ods'] ?>">
                        <label class="form-check-label">
                          <img src="<?= htmlspecialchars($ods[$i]['icone_url']) ?>" alt="ODS <?= $ods[$i]['n_ods'] ?>" style="width:24px;height:24px;">
                          <?= htmlspecialchars($ods[$i]['nome']) ?>
                        </label>
                      </div>
                    <?php endfor; ?>
                  </div>
                </div>
              </div>

              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="button" class="btn btn-secondary prev">Voltar</button>
                <button type="button" class="btn btn-primary next">Próximo</button>
              </div>
            </div>

            
            <div class="step active" id="step3">

              <h3>Mapeamento de interesses e perfil de impacto</h3>

              <div class="mb-3">
                <label class="form-label">Você prefere acompanhar negócios em qual estágio de maturidade?</label>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="maturidade[]" value="Ideação">
                      <label class="form-check-label"><i class="bi bi-lightbulb"></i> Ideação (começando agora)</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="maturidade[]" value="Validação">
                      <label class="form-check-label"><i class="bi bi-rocket"></i> Validação (modelo sendo testado)</label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="maturidade[]" value="Crescimento">
                      <label class="form-check-label"><i class="bi bi-graph-up"></i> Crescimento (já operando e expandindo)</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="maturidade[]" value="Escala">
                      <label class="form-check-label"><i class="bi bi-globe"></i> Escala (impacto consolidado e ampliando alcance)</label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Há algum setor específico que você gostaria de acompanhar?</label>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Tecnologia"><label class="form-check-label"><i class="bi bi-cpu"></i> Tecnologia</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Agronegócio sustentável"><label class="form-check-label"><i class="bi bi-tree"></i> Agronegócio sustentável</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Saúde"><label class="form-check-label"><i class="bi bi-heart-pulse"></i> Saúde</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Educação"><label class="form-check-label"><i class="bi bi-book"></i> Educação</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Finanças de impacto"><label class="form-check-label"><i class="bi bi-cash-stack"></i> Finanças de impacto</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Energia"><label class="form-check-label"><i class="bi bi-lightning-charge"></i> Energia</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Moda sustentável"><label class="form-check-label"><i class="bi bi-bag"></i> Moda sustentável</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Alimentação"><label class="form-check-label"><i class="bi bi-basket"></i> Alimentação</label></div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Construção civil"><label class="form-check-label"><i class="bi bi-building"></i> Construção civil</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Cultura"><label class="form-check-label"><i class="bi bi-music-note"></i> Cultura</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="ESG corporativo"><label class="form-check-label"><i class="bi bi-bar-chart"></i> ESG corporativo</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Startups"><label class="form-check-label"><i class="bi bi-lightbulb"></i> Startups</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Negócios sociais"><label class="form-check-label"><i class="bi bi-people"></i> Negócios sociais</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="Cooperativas"><label class="form-check-label"><i class="bi bi-diagram-3"></i> Cooperativas</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="setores[]" value="ONGs"><label class="form-check-label"><i class="bi bi-hand-thumbs-up"></i> ONGs</label></div>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Que tipo de impacto você quer ver mais?</label>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Social"><label class="form-check-label"><i class="bi bi-people"></i> Social</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Ambiental"><label class="form-check-label"><i class="bi bi-tree"></i> Ambiental</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Social + Ambiental"><label class="form-check-label"><i class="bi bi-globe"></i> Social + Ambiental</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Inovação tecnológica"><label class="form-check-label"><i class="bi bi-cpu"></i> Inovação tecnológica</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Base comunitária"><label class="form-check-label"><i class="bi bi-house"></i> Base comunitária</label></div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Liderado por mulheres"><label class="form-check-label"><i class="bi bi-gender-female"></i> Liderado por mulheres</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Liderado por jovens"><label class="form-check-label"><i class="bi bi-person"></i> Liderado por jovens</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Impacto regional / local"><label class="form-check-label"><i class="bi bi-geo-alt"></i> Impacto regional / local</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="perfil_impacto[]" value="Impacto global"><label class="form-check-label"><i class="bi bi-globe2"></i> Impacto global</label></div>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Você prefere causas locais, nacionais ou globais?</label>
                <div class="form-check"><input class="form-check-input" type="radio" name="alcance" value="Local"><label class="form-check-label">Local</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="alcance" value="Nacional"><label class="form-check-label">Nacional</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="alcance" value="Global"><label class="form-check-label">Global</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="alcance" value="Todos"><label class="form-check-label">Todos</label></div>

              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="button" class="btn btn-secondary prev">Voltar</button>                
                <button type="submit" class="btn btn-success" id="btnSubmit">Finalizar Cadastro</button>
              </div>
            </div>
          </form>
          
          <div class="text-center mt-3">
             <a href="/login.php" class="text-decoration-none">Já tem conta? Faça login</a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- SCRIPT DE VALIDAÇÃO E MÁSCARA -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const cpfInput = document.getElementById('cpfInput');
  const feedback = document.getElementById('cpfFeedback');
  const form = document.getElementById('formCadastroComunidade');
  const btnSubmit = document.getElementById('btnSubmit');

  // Máscara de CPF (000.000.000-00)
  cpfInput.addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
    if (v.length > 11) v = v.substring(0, 11); // Limita a 11 dígitos

    // Aplica a formatação
    if (v.length > 9) {
      v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    } else if (v.length > 6) {
      v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    } else if (v.length > 3) {
      v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    }
    
    e.target.value = v;
    validarInputCPF(); // Valida a cada dígito inserido
  });

  // Função de Validação de CPF (Algoritmo Oficial)
  function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]+/g, '');
    if (cpf == '') return false;
    // Elimina CPFs invalidos conhecidos
    if (cpf.length != 11 || 
        cpf == "00000000000" || 
        cpf == "11111111111" || 
        cpf == "22222222222" || 
        cpf == "33333333333" || 
        cpf == "44444444444" || 
        cpf == "55555555555" || 
        cpf == "66666666666" || 
        cpf == "77777777777" || 
        cpf == "88888888888" || 
        cpf == "99999999999")
            return false;
    
    // Valida 1o digito
    let add = 0;
    for (let i = 0; i < 9; i++) add += parseInt(cpf.charAt(i)) * (10 - i);
    let rev = 11 - (add % 11);
    if (rev == 10 || rev == 11) rev = 0;
    if (rev != parseInt(cpf.charAt(9))) return false;
    
    // Valida 2o digito
    add = 0;
    for (let i = 0; i < 10; i++) add += parseInt(cpf.charAt(i)) * (11 - i);
    rev = 11 - (add % 11);
    if (rev == 10 || rev == 11) rev = 0;
    if (rev != parseInt(cpf.charAt(10))) return false;
    
    return true;
  }

  function validarInputCPF() {
    const valor = cpfInput.value;
    const limpo = valor.replace(/\D/g, '');

    // Se estiver vazio, reseta estados (ou marca inválido se o campo for required e tiver sido tocado)
    if (limpo.length === 0) {
      cpfInput.classList.remove('is-valid', 'is-invalid');
      cpfInput.setCustomValidity('');
      return false;
    }

    // Só valida se tiver 11 dígitos completos
    if (limpo.length === 11) {
      if (validarCPF(limpo)) {
        // Válido
        cpfInput.classList.remove('is-invalid');
        cpfInput.classList.add('is-valid');
        cpfInput.setCustomValidity('');
        return true;
      } else {
        // Inválido
        cpfInput.classList.remove('is-valid');
        cpfInput.classList.add('is-invalid');
        feedback.textContent = 'CPF inválido.';
        cpfInput.setCustomValidity('CPF inválido');
        return false;
      }
    } else {
      // Incompleto
      cpfInput.classList.remove('is-valid');
      // Opcional: não mostrar erro enquanto digita, só no blur
      cpfInput.setCustomValidity('Incompleto');
      return false;
    }
  }

  // Validação ao sair do campo (Blur)
  cpfInput.addEventListener('blur', function() {
    const limpo = this.value.replace(/\D/g, '');
    if (limpo.length > 0 && limpo.length < 11) {
       this.classList.add('is-invalid');
       feedback.textContent = 'CPF incompleto.';
    }
  });

  // Impede o envio se estiver inválido
  form.addEventListener('submit', function(event) {
    if (!form.checkValidity() || !validarInputCPF()) {
      event.preventDefault();
      event.stopPropagation();
      // Força mostrar o erro se o usuário tentar enviar com CPF errado
      if (!validarInputCPF()) {
         cpfInput.classList.add('is-invalid');
         feedback.textContent = 'Por favor, corrija o CPF antes de enviar.';
      }
    }
    form.classList.add('was-validated');
  });
});

document.addEventListener('DOMContentLoaded', function() {
  const steps = document.querySelectorAll('.step');
  let currentStep = 0;

  function showStep(index) {
    steps.forEach((step, i) => {
      step.style.display = i === index ? 'block' : 'none';
    });
  }

  document.querySelectorAll('.next').forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStep < steps.length - 1) {
        currentStep++;
        showStep(currentStep);
      }
    });
  });

  document.querySelectorAll('.prev').forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStep > 0) {
        currentStep--;
        showStep(currentStep);
      }
    });
  });

  showStep(currentStep);
});
</script>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>