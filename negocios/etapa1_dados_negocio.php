<?php

// /public_html/negocios/etapa1_dados_negocios.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../app/helpers/auth.php';



// Apenas empreendedores podem acessar
require_role(['empreendedor']);

$errors = $_SESSION['errors_etapa1'] ?? [];
unset($_SESSION['errors_etapa1']);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container mt-4">
  <h4>Etapa 1: Dados do Negócio</h4>

  <?php
    $etapaAtual = 1;
    include __DIR__ . '/../app/views/partials/progress.php';
    include __DIR__ . '/../app/views/partials/intro_text_dados_negocio.php';
  ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?>
        <?= htmlspecialchars($e) ?><br>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="/negocios/processar_etapa1.php">
    <input type="hidden" name="modo" value="cadastro">

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Nome Fantasia *</label>
      <input type="text" name="nome_fantasia" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Razão Social *</label>
      <input type="text" name="razao_social" class="form-control" required>
    </div>


    <!-- Categoria como radios com imagens e descrição -->
    <div class="mb-3">
        <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Categoria *</label>       

        <div id="categoriaRadios" class="d-flex m-2 align-items-stretch gap-2">
            <div class="col-md-3 mb-4">
                <label class="categoria-option card p-2 text-center" style="cursor:pointer;">
                    <input type="radio" name="categoria" value="Ideação" class="visually-hidden categoria-radio" required>
                    <img src="/../assets/images/icons/ideacao.png" alt="Ideação" class="d-block mx-auto mb-2" style="height:90px; width: 90px; object-fit:cover;">
                    <div class="fw-bold">Ideação</div>
                    <small class="text-muted d-block mb-2">Início da ideia</small>
                    <div class="categoria-desc text-start small text-muted">
                        Negócio em fase de concepção; validação de hipótese; equipe reduzida; foco em prototipagem e testes iniciais.
                    </div>
                </label>
            </div>
            <div class="col-md-3 mb-4">
                <label class="categoria-option card p-2 text-center" style="cursor:pointer;">
                    <input type="radio" name="categoria" value="Operação" class="visually-hidden categoria-radio">
                    <img src="/../assets/images/icons/operacao.png" alt="Operação" class="d-block mx-auto mb-2" style="height:90px; width:90px; object-fit:cover;">
                    <div class="fw-bold">Operação</div>
                    <small class="text-muted d-block mb-2">Negócio em funcionamento</small>
                    <div class="categoria-desc text-start small text-muted">
                        Empresa com produto/serviço no mercado; receitas regulares; processos operacionais estabelecidos; foco em eficiência.
                    </div>
                </label>
            </div>
            <div class="col-md-3 mb-4">
                <label class="categoria-option card p-2 text-center" style="cursor:pointer;">
                    <input type="radio" name="categoria" value="Tração/Escala" class="visually-hidden categoria-radio">
                    <img src="/../assets/images/icons/tracao.png" alt="Tração/Escala" class="d-block mx-auto mb-2" style="height:90px; width:90px; object-fit:cover;">
                    <div class="fw-bold">Tração/Escala</div>
                    <small class="text-muted d-block mb-2">Crescimento e escala</small>
                    <div class="categoria-desc text-start small text-muted">
                        Negócio com tração comprovada; crescimento acelerado; busca por expansão de mercado, parcerias e investimento para escalar.
                    </div>
                </label>
            </div>
            <div class="col-md-3 mb-4">
                <label class="categoria-option card p-2 text-center" style="cursor:pointer;">
                    <input type="radio" name="categoria" value="Dinamizador" class="visually-hidden categoria-radio">
                    <img src="/../assets/images/icons/dinamizadores.png" alt="Dinamizador" class="d-block mx-auto mb-2" style="height:90px; width:90px; object-fit:cover;">
                    <div class="fw-bold">Dinamizador</div>
                    <small class="text-muted d-block mb-2">Apoio e fomento</small>
                    <div class="categoria-desc text-start small text-muted">
                        Organizações que fomentam ecossistemas: aceleradoras, incubadoras, hubs e projetos de apoio a empreendedores.
                    </div>
                </label>
            </div>
        </div>

        <div class="form-text mt-2" id="categoriaHelp">Selecione a categoria que melhor descreve seu negócio. A descrição abaixo explica cada opção.</div>

        <!-- Área de descrição dinâmica (aparece/atualiza conforme seleção) -->
        <div id="categoriaDescricaoDinamica" class="mt-3 p-3 border rounded" style="background:#f8f9fa; display:none;">
            <div id="categoriaTitulo" class="fw-bold mb-1"></div>
            <div id="categoriaTexto" class="small text-muted"></div>
        </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-6" id="cnpjCpfWrapper">
        <label class="form-label" id="cnpjCpfLabel"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> CPF ou CNPJ *</label>
        <input type="text" name="cnpj_cpf" id="cnpj_cpf" class="form-control" required>
        <div class="invalid-feedback" id="cnpjCpfError"></div>
        <small id="cnpjCpfHelp" class="form-text text-muted"></small>
      </div>

      <div class="col-md-6">
          <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Formato Legal *</label>
          <select name="formato_legal" id="formato_legal" class="form-select" required>
              <option value="">Selecione...</option>
              <option value="Organização da Sociedade Civil">Organização da Sociedade Civil</option>
              <option value="MEI - Microempreendedor individual">MEI - Microempreendedor individual</option>
              <option value="Cooperativa">Cooperativa</option>
              <option value="Sociedade Limitada">Sociedade Limitada</option>
              <option value="Sociedade Anônima">Sociedade Anônima</option>
              <option value="Empresa Individual de Responsabilidade Limitada">Empresa Individual de Responsabilidade Limitada</option>
              <option value="Outros">Outros</option>
          </select>
        <div class="mb-3 d-none" id="formatoOutrosWrapper">
          <label class="form-label">Formato Outros</label>
          <input type="text" name="formato_outros" id="formato_outros" class="form-control">
        </div>
      </div>     

      <script>
        document.addEventListener("DOMContentLoaded", function() {
            const formatoSelect = document.getElementById("formato_legal");
            const formatoOutrosWrapper = document.getElementById("formatoOutrosWrapper");
            const formatoOutrosInput = document.getElementById("formato_outros");

            function toggleFormatoOutros() {
                if (formatoSelect.value === "Outros") {
                    formatoOutrosWrapper.classList.remove("d-none");
                    formatoOutrosInput.setAttribute("required", "required");
                } else {
                    formatoOutrosWrapper.classList.add("d-none");
                    formatoOutrosInput.removeAttribute("required");
                    formatoOutrosInput.value = ""; // Limpa o campo se mudar
                }
            }

            if (formatoSelect) {
                formatoSelect.addEventListener("change", toggleFormatoOutros);
                // Executa no carregamento para caso de edição já vir marcado
                toggleFormatoOutros();
            }
        });
      </script>
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
          <label for="email_comercial" class="form-label"><i class="bi bi-eye text-secondary me-1"></i> E-mail Institucional/Comercial</label>
          <input type="email" name="email_comercial" id="email_comercial" class="form-control" 
                value="<?= htmlspecialchars($negocio['email_comercial'] ?? '') ?>" 
                placeholder="contato@seunegocio.com.br" maxlength="100">
      </div>
      <div class="col-md-4 mb-3">
          <label for="telefone_comercial" class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Telefone/WhatsApp do Negócio</label>
          <input type="text" name="telefone_comercial" id="telefone_comercial" class="form-control" 
                value="<?= htmlspecialchars($negocio['telefone_comercial'] ?? '') ?>" 
                placeholder="(00) 00000-0000" maxlength="20">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Data de Fundação *</label>        
        <input type="date" name="data_fundacao" class="form-control" required>
      </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Setor de Atuação *</label>
        <select name="setor" id="setor" class="form-select" required>
            <option value="">Selecione...</option>

            <optgroup label="Setor Primário">
            <option value="Agricultura">Agricultura (soja, milho, café, cana-de-açúcar, hortaliças etc.)</option>
            <option value="Pecuária">Pecuária (gado de corte, leite, aves, suínos)</option>
            <option value="Pesca">Pesca (artesanal e industrial)</option>
            <option value="Silvicultura">Silvicultura (reflorestamento, produção de madeira e celulose)</option>
            <option value="Extração vegetal">Extração vegetal (castanha, borracha, óleos)</option>
            <option value="Mineração">Mineração (ferro, ouro, bauxita, nióbio, petróleo bruto)</option>
            <option value="Outro Primário">Outro</option>
            </optgroup>

            <optgroup label="Setor Secundário">
            <option value="Indústrias alimentícias e bebidas">Indústrias alimentícias e bebidas</option>
            <option value="Indústria têxtil e vestuário">Indústria têxtil e de vestuário</option>
            <option value="Indústria automobilística">Indústria automobilística</option>
            <option value="Indústria química e petroquímica">Indústria química e petroquímica</option>
            <option value="Indústria farmacêutica">Indústria farmacêutica</option>
            <option value="Indústria de papel e celulose">Indústria de papel e celulose</option>
            <option value="Indústria de cimento e construção civil">Indústria de cimento e construção civil</option>
            <option value="Siderurgia e metalurgia">Siderurgia e metalurgia</option>
            <option value="Indústria de eletroeletrônicos e tecnologia">Indústria de eletroeletrônicos e tecnologia</option>
            <option value="Energia">Geração e distribuição de energia</option>
            <option value="Outro Secundário">Outro</option>
            </optgroup>

            <optgroup label="Setor Terciário">
            <option value="Comércio">Comércio varejista e atacadista</option>
            <option value="Transporte e logística">Transporte e logística (rodoviário, ferroviário, aéreo, marítimo)</option>
            <option value="Serviços financeiros">Serviços financeiros (bancos, fintechs, cooperativas de crédito)</option>
            <option value="Educação">Educação (escolas, universidades, cursos técnicos)</option>
            <option value="Saúde">Saúde (hospitais, clínicas, laboratórios)</option>
            <option value="Turismo">Turismo, hotelaria e eventos</option>
            <option value="Tecnologia e serviços digitais">Tecnologia e serviços digitais (startups, plataformas, TI)</option>
            <option value="Serviços jurídicos e contábeis">Serviços jurídicos e contábeis</option>
            <option value="Comunicação e marketing">Comunicação e marketing</option>
            <option value="Administração pública e serviços sociais">Administração pública e serviços sociais</option>
            <option value="Serviços de limpeza, segurança e manutenção">Serviços de limpeza, segurança e manutenção</option>
            <option value="Entretenimento e cultura">Entretenimento e cultura (cinema, música, teatro)</option>
            <option value="Outro Terciário">Outro</option>
            </optgroup>
        </select>
    </div>

    <h5>Endereço</h5>

    <div class="row mb-3">        

        <div class="col-md-4">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i>CEP *</label>
            <input type="text" name="cep" id="cep" class="form-control" maxlength="9" required>
        </div>
        
        <div class="col-md-4">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Estado *</label>
            <input type="text" name="estado" id="estado" class="form-control" readonly required>
        </div>

        <div class="col-md-4">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Município *</label>            
            <input type="text" name="municipio" id="municipio" class="form-control" readonly required>
        </div>

    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i>Rua *</label>
            <input type="text" name="rua" id="rua" class="form-control" readonly required>
        </div>

        <div class="col-md-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i>Número *</label>
            <input type="text" name="numero" id="numero" class="form-control" required>
        </div>


        <div class="col-md-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i>Complemento</label>
            <input type="text" name="complemento" class="form-control">
        </div>
    </div>

    <h5><i class="bi bi-eye text-secondary me-1"></i> Presença Digital</h5>
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">
            <i class="bi bi-globe"></i> Site
            </label>
            <input type="url" name="site" id="site" class="form-control" placeholder="https://www.seusite.com">
        </div>

        <div class="col-md-4">
            <label class="form-label">
            <i class="bi bi-linkedin"></i> LinkedIn
            </label>
            <input type="url" name="linkedin" id="linkedin" class="form-control" placeholder="https://www.linkedin.com/in/seuperfil">
        </div>

        <div class="col-md-4">
            <label class="form-label">
            <i class="bi bi-instagram"></i> Instagram
            </label>
            <input type="url" name="instagram" id="instagram" class="form-control" placeholder="https://www.instagram.com/seuperfil">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">
            <i class="bi bi-facebook"></i> Facebook
            </label>
            <input type="url" name="facebook" id="facebook" class="form-control" placeholder="https://www.facebook.com/seupagina">
        </div>

        <div class="col-md-4">
            <label class="form-label">
            <i class="bi bi-tiktok"></i> TikTok
            </label>
            <input type="url" name="tiktok" id="tiktok" class="form-control" placeholder="https://www.tiktok.com/@seuperfil">
        </div>

        <div class="col-md-4">
            <label class="form-label">
            <i class="bi bi-youtube"></i> YouTube
            </label>
            <input type="url" name="youtube" id="youtube" class="form-control" placeholder="https://www.youtube.com/@seucanal">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <label class="form-label">
            <i class="bi bi-link-45deg"></i> Outros Links
            </label>
            <input type="url" name="outros_links" id="outros_links" class="form-control" placeholder="https://...">
        </div>
    </div>
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="/empreendedores/dashboard.php" class="btn btn-secondary me-md-2">Cancelar</a>
        <button type="submit" name="acao" value="salvar" class="btn btn-primary">Salvar e Avançar</button>
    </div>
    <!-- <button type="submit" class="btn btn-primary">Avançar para Etapa 2</button> -->
 </form>
</div>
<!-- Script para comportamento, descrição dinâmica e integração com CPF/CNPJ -->
<script>
  // Seleção visual das opções e atualização da área de descrição
  document.querySelectorAll('.categoria-option').forEach(function(label) {
    label.addEventListener('click', function(e) {
      const radio = this.querySelector('.categoria-radio');
      if (radio) radio.checked = true;

      document.querySelectorAll('.categoria-option').forEach(function(l){ l.classList.remove('selected'); });
      this.classList.add('selected');

      // Atualiza área dinâmica
      const titulo = this.querySelector('.fw-bold').textContent.trim();
      const texto = this.querySelector('.categoria-desc').textContent.trim();
      const descricaoBox = document.getElementById('categoriaDescricaoDinamica');
      document.getElementById('categoriaTitulo').textContent = titulo;
      document.getElementById('categoriaTexto').textContent = texto;
      descricaoBox.style.display = 'block';

      // Dispara evento change no input radio para compatibilidade
      const changeEvent = new Event('change', { bubbles: true });
      radio.dispatchEvent(changeEvent);
    });
  });
</script>
<script>
  // ---------- Integração com a lógica de CPF/CNPJ (CORRIGIDO) ----------
  (function() {
    const radios = document.querySelectorAll('input[name="categoria"]');
    const labelCnpjCpf = document.getElementById('cnpjCpfLabel');
    const help = document.getElementById('cnpjCpfHelp');
    const cnpjCpfInput = document.getElementById('cnpj_cpf');
    const cnpjCpfError = document.getElementById('cnpjCpfError');

    // Helpers
    function onlyDigits(str) { return (str || '').replace(/\D/g, ''); }
    
    function setInvalid(msg) {
      if (!cnpjCpfInput) return;
      cnpjCpfInput.classList.add('is-invalid');
      if (cnpjCpfError) cnpjCpfError.textContent = msg || 'Valor inválido.';
      cnpjCpfInput.setCustomValidity(msg || 'Inválido');
    }
    
    function clearInvalid() {
      if (!cnpjCpfInput) return;
      cnpjCpfInput.classList.remove('is-invalid');
      if (cnpjCpfError) cnpjCpfError.textContent = '';
      cnpjCpfInput.setCustomValidity('');
    }

    // Validações (algoritmo oficial)
    function isValidCPF(cpf) {
      cpf = onlyDigits(cpf);
      if (cpf.length !== 11) return false;
      if (/^(\d)\1{10}$/.test(cpf)) return false;
      
      let sum = 0;
      for (let i = 0; i < 9; i++) sum += parseInt(cpf.charAt(i)) * (10 - i);
      let r = sum % 11;
      let d1 = (r < 2) ? 0 : 11 - r;
      if (d1 !== parseInt(cpf.charAt(9))) return false;
      
      sum = 0;
      for (let i = 0; i < 10; i++) sum += parseInt(cpf.charAt(i)) * (11 - i);
      r = sum % 11;
      let d2 = (r < 2) ? 0 : 11 - r;
      return d2 === parseInt(cpf.charAt(10));
    }

    function isValidCNPJ(cnpj) {
      cnpj = onlyDigits(cnpj);
      if (cnpj.length !== 14) return false;
      if (/^(\d)\1{13}$/.test(cnpj)) return false;
      
      // Primeiro dígito verificador (peso começa em 5)
      const pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
      let soma = 0;
      for (let i = 0; i < 12; i++) {
        soma += parseInt(cnpj.charAt(i)) * pesos1[i];
      }
      let resto = soma % 11;
      let d1 = (resto < 2) ? 0 : 11 - resto;
      if (d1 !== parseInt(cnpj.charAt(12))) return false;
      
      // Segundo dígito verificador (peso começa em 6)
      const pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
      soma = 0;
      for (let i = 0; i < 13; i++) {
        soma += parseInt(cnpj.charAt(i)) * pesos2[i];
      }
      resto = soma % 11;
      let d2 = (resto < 2) ? 0 : 11 - resto;
      return d2 === parseInt(cnpj.charAt(13));
    }

    // Máscaras
    function formatCPF(digits) {
      digits = digits.slice(0, 11);
      return digits
        .replace(/^(\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1-$2')
        .slice(0, 14);
    }
    
    function formatCNPJ(digits) {
      digits = digits.slice(0, 14);
      return digits
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2')
        .slice(0, 18);
    }
    
    function applyMaskAuto(value) {
      const digits = onlyDigits(value);
      return digits.length <= 11 ? formatCPF(digits) : formatCNPJ(digits);
    }

    // Categoria selecionada
    function getSelectedCategory() {
      const sel = document.getElementById('categoria');
      if (sel) return sel.value;
      const radio = document.querySelector('input[name="categoria"]:checked');
      return radio ? radio.value : '';
    }

    // Validação conforme categoria
    function validateCnpjCpfForCategory(category) {
      if (!cnpjCpfInput) return true;
      clearInvalid();
      const raw = cnpjCpfInput.value.trim();
      const digits = onlyDigits(raw);

      if (category === 'Ideação') {
        if (digits.length === 11) {
          if (!isValidCPF(digits)) { setInvalid('CPF inválido. Verifique os dígitos.'); return false; }
        } else if (digits.length === 14) {
          if (!isValidCNPJ(digits)) { setInvalid('CNPJ inválido. Verifique os dígitos.'); return false; }
        } else {
          setInvalid('Informe CPF (11 dígitos) ou CNPJ (14 dígitos).'); return false;
        }
      } else {
        if (digits.length !== 14) { setInvalid('Informe um CNPJ válido com 14 dígitos.'); return false; }
        if (!isValidCNPJ(digits)) { setInvalid('CNPJ inválido. Verifique os dígitos.'); return false; }
      }
      clearInvalid();
      return true;
    }

    // Atualiza label/help e força máscara CNPJ quando necessário
    function onCategoryChange() {
      const categoria = getSelectedCategory();
      if (categoria === 'Ideação') {
        if (labelCnpjCpf) labelCnpjCpf.innerHTML = '<i class="bi bi-eye-slash text-danger-emphasis me-1"></i> CPF ou CNPJ *';
        if (help) help.textContent = "Na categoria Ideação, você pode informar CPF (11 dígitos) ou CNPJ (14 dígitos).";
        if (cnpjCpfInput) {
          cnpjCpfInput.removeAttribute('maxlength');
          cnpjCpfInput.setAttribute('placeholder', 'CPF (000.000.000-00) ou CNPJ (00.000.000/0000-00)');
          cnpjCpfInput.value = applyMaskAuto(cnpjCpfInput.value);
        }
      } else if (categoria) {
        if (labelCnpjCpf) labelCnpjCpf.innerHTML = '<i class="bi bi-eye-slash text-danger-emphasis me-1"></i> CNPJ *';
        if (help) help.textContent = "Para esta categoria, informe apenas um CNPJ válido (14 dígitos).";
        if (cnpjCpfInput) {
          cnpjCpfInput.setAttribute('maxlength', '18');
          cnpjCpfInput.setAttribute('placeholder', '00.000.000/0000-00');
          cnpjCpfInput.value = formatCNPJ(onlyDigits(cnpjCpfInput.value));
        }
      } else {
        if (labelCnpjCpf) labelCnpjCpf.innerHTML = '<i class="bi bi-eye-slash text-danger-emphasis me-1"></i> CPF ou CNPJ *';
        if (help) help.textContent = "";
        if (cnpjCpfInput) {
          cnpjCpfInput.removeAttribute('maxlength');
          cnpjCpfInput.setAttribute('placeholder', '');
          cnpjCpfInput.value = applyMaskAuto(cnpjCpfInput.value);
        }
      }

      // revalida campo
      validateCnpjCpfForCategory(categoria);
    }

    // Eventos do campo cnpj_cpf
    if (cnpjCpfInput) {
      // input: aplica máscara dinâmica
      cnpjCpfInput.addEventListener('input', function(e) {
        const categoria = getSelectedCategory();
        const oldPos = cnpjCpfInput.selectionStart || cnpjCpfInput.value.length;
        const oldValue = cnpjCpfInput.value;
        let newMasked;
        if (categoria && categoria !== 'Ideação') {
          newMasked = formatCNPJ(onlyDigits(oldValue));
        } else {
          newMasked = applyMaskAuto(oldValue);
        }
        cnpjCpfInput.value = newMasked;

        // mantém cursor na posição
        try {
          const left = oldValue.slice(0, oldPos);
          const leftDigits = left.replace(/\D/g, '').length;
          let pos = 0, digitsSeen = 0;
          while (pos < newMasked.length && digitsSeen < leftDigits) {
            if (/\d/.test(newMasked[pos])) digitsSeen++;
            pos++;
          }
          cnpjCpfInput.setSelectionRange(pos, pos);
        } catch (err) { /* ignore */ }

        clearInvalid();
      });

      // paste
      cnpjCpfInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const digits = onlyDigits(paste);
        const categoria = getSelectedCategory();
        cnpjCpfInput.value = (categoria && categoria !== 'Ideação') ? formatCNPJ(digits) : applyMaskAuto(digits);
        cnpjCpfInput.dispatchEvent(new Event('input', { bubbles: true }));
        cnpjCpfInput.dispatchEvent(new Event('blur', { bubbles: true }));
      });

      // blur: valida
      cnpjCpfInput.addEventListener('blur', function() {
        const categoria = getSelectedCategory();
        validateCnpjCpfForCategory(categoria);
      });

      // focus: limpa mensagens
      cnpjCpfInput.addEventListener('focus', function() {
        clearInvalid();
      });
    }

    // Observadores de categoria
    const categoriaSelect = document.getElementById('categoria');
    if (categoriaSelect) categoriaSelect.addEventListener('change', onCategoryChange);
    radios.forEach(function(r) { r.addEventListener('change', onCategoryChange); });

    // Validação no submit
    const form = cnpjCpfInput ? cnpjCpfInput.closest('form') : null;
    if (form) {
      form.addEventListener('submit', function(ev) {
        const categoria = getSelectedCategory();
        const ok = validateCnpjCpfForCategory(categoria);
        if (!ok) {
          ev.preventDefault();
          if (cnpjCpfInput) cnpjCpfInput.focus();
        } else {
          if (cnpjCpfInput) {
            cnpjCpfInput.value = onlyDigits(cnpjCpfInput.value);
          }
        }
      });
    }

    // Aplica estado inicial
    (function applyInitialSelection() {
      const checked = document.querySelector('input[name="categoria"]:checked');
      if (checked) {
        const label = checked.closest('.categoria-option');
        if (label) label.classList.add('selected');
      }
      onCategoryChange();
    })();

  })();
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>


