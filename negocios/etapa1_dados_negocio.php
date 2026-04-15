<?php
// /public_html/negocios/etapa1_dados_negocios.php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../app/helpers/auth.php';

require_role(['empreendedor']);

$errors = $_SESSION['errors_etapa1'] ?? [];
unset($_SESSION['errors_etapa1']);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>


<div class="container mt-4 mb-5" style="max-width: 860px;">

  <?php
    $etapaAtual = 1;
    include __DIR__ . '/../app/views/partials/progress.php';
    include __DIR__ . '/../app/views/partials/intro_text_dados_negocio.php';
  ?>

  <?php if (!empty($errors)): ?>
    <div class="alert d-flex align-items-start gap-2 mb-4"
         style="background:#fde8ea;border:1px solid #f5c2c7;color:#842029;border-radius:10px;">
      <i class="bi bi-exclamation-circle-fill mt-1"></i>
      <div>
        <?php foreach ($errors as $e): ?>
          <?= htmlspecialchars($e) ?><br>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" action="/negocios/processar_etapa1.php">
    <input type="hidden" name="modo" value="cadastro">

    <!-- ── Identificação ───────────────────────────────── -->
    <div class="form-section">
      <div class="form-section-title"><i class="bi bi-card-text"></i> Identificação</div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">
            <i class="bi bi-eye-fill lbl-pub me-1"></i> Nome Fantasia *
          </label>
          <input type="text" name="nome_fantasia" class="form-control"
                 placeholder="Como seu negócio é conhecido" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">
            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Razão Social *
          </label>
          <input type="text" name="razao_social" class="form-control"
                 placeholder="Razão social conforme CNPJ" required>
        </div>
      </div>
    </div>

    <!-- ── Categoria ───────────────────────────────────── -->
    <div class="form-section">
      <div class="form-section-title"><i class="bi bi-grid-1x2"></i> Categoria *</div>

      <div class="row g-3" id="categoriaRadios">
        <?php
        $categorias = [
          ['value' => 'Ideação',       'img' => 'ideacao.png',      'sub' => 'Início da ideia',         'desc' => 'Negócio em fase de concepção; validação de hipótese; equipe reduzida; foco em prototipagem e testes iniciais.'],
          ['value' => 'Operação',      'img' => 'operacao.png',     'sub' => 'Negócio em funcionamento', 'desc' => 'Empresa com produto/serviço no mercado; receitas regulares; processos operacionais estabelecidos; foco em eficiência.'],
          ['value' => 'Tração/Escala', 'img' => 'tracao.png',       'sub' => 'Crescimento e escala',     'desc' => 'Negócio com tração comprovada; crescimento acelerado; busca por expansão de mercado, parcerias e investimento para escalar.'],
          ['value' => 'Dinamizador',   'img' => 'dinamizadores.png','sub' => 'Apoio e fomento',          'desc' => 'Organizações que fomentam ecossistemas: aceleradoras, incubadoras, hubs e projetos de apoio a empreendedores.'],
        ];
        foreach ($categorias as $cat): ?>
        <div class="col-6 col-md-3">
          <label class="categoria-option">
            <input type="radio" name="categoria" value="<?= $cat['value'] ?>"
                   class="visually-hidden categoria-radio" required>
            <img src="/../assets/images/icons/<?= $cat['img'] ?>" alt="<?= $cat['value'] ?>">
            <div class="cat-name"><?= $cat['value'] ?></div>
            <div class="cat-sub"><?= $cat['sub'] ?></div>
            <div class="categoria-desc"><?= $cat['desc'] ?></div>
          </label>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="cat-desc-box" id="categoriaDescricaoDinamica">
        <div class="cat-desc-title" id="categoriaTitulo"></div>
        <div class="cat-desc-text"  id="categoriaTexto"></div>
      </div>

      <div class="form-text mt-2">Selecione a categoria que melhor descreve o estágio do seu negócio.</div>
    </div>

    <!-- ── Dados Jurídicos ─────────────────────────────── -->
    <div class="form-section">
      <div class="form-section-title"><i class="bi bi-file-earmark-text"></i> Dados Jurídicos</div>

      <div class="row g-3">
        <div class="col-md-6" id="cnpjCpfWrapper">
          <label class="form-label" id="cnpjCpfLabel">
            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> CPF ou CNPJ *
          </label>
          <input type="text" name="cnpj_cpf" id="cnpj_cpf" class="form-control" required>
          <div class="invalid-feedback" id="cnpjCpfError"></div>
          <div class="form-text" id="cnpjCpfHelp"></div>
        </div>

        <div class="col-md-6">
          <label class="form-label">
            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Formato Legal *
          </label>
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
          <div class="mt-2 d-none" id="formatoOutrosWrapper">
            <input type="text" name="formato_outros" id="formato_outros"
                   class="form-control" placeholder="Descreva o formato jurídico">
          </div>
        </div>
      </div>
    </div>

    <!-- ── Contato e Fundação ──────────────────────────── -->
    <div class="form-section">
      <div class="form-section-title"><i class="bi bi-telephone"></i> Contato e Fundação</div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">
            <i class="bi bi-eye-fill lbl-pub me-1"></i> E-mail Institucional/Comercial
          </label>
          <input type="email" name="email_comercial" id="email_comercial" class="form-control"
                 value="<?= htmlspecialchars($negocio['email_comercial'] ?? '') ?>"
                 placeholder="contato@seunegocio.com.br" maxlength="100">
        </div>
        <div class="col-md-4">
          <label class="form-label">
            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Telefone/WhatsApp do Negócio
          </label>
          <input type="text" name="telefone_comercial" id="telefone_comercial" class="form-control"
                 value="<?= htmlspecialchars($negocio['telefone_comercial'] ?? '') ?>"
                 placeholder="(00) 00000-0000" maxlength="20">
        </div>
        <div class="col-md-4">
          <label class="form-label">
            <i class="bi bi-eye-fill lbl-pub me-1"></i> Data de Fundação *
          </label>
          <input type="date" name="data_fundacao" class="form-control" required>
        </div>
      </div>

      <div class="row g-3 mt-0">
        <div class="col-12">
          <label class="form-label">
            <i class="bi bi-eye-fill lbl-pub me-1"></i> Setor de Atuação *
          </label>
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
              <option value="Transporte e logística">Transporte e logística</option>
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
      </div>
    </div>

    <!-- ── Endereço ─────────────────────────────────────── -->
    <div class="form-section">
      <div class="form-section-title"><i class="bi bi-geo-alt"></i> Endereço</div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">
            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> CEP *
          </label>
          <input type="text" name="cep" id="cep" class="form-control" maxlength="9"
                 placeholder="00000-000" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">
            <i class="bi bi-eye-fill lbl-pub me-1"></i> Estado *
          </label>
          <input type="text" name="estado" id="estado" class="form-control" readonly required>
        </div>
        <div class="col-md-4">
          <label class="form-label">
            <i class="bi bi-eye-fill lbl-pub me-1"></i> Município *
          </label>
          <input type="text" name="municipio" id="municipio" class="form-control" readonly required>
        </div>
        <div class="col-md-6">
          <label class="form-label">
            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Rua *
          </label>
          <input type="text" name="rua" id="rua" class="form-control" readonly required>
        </div>
        <div class="col-md-3">
          <label class="form-label">
            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Número *
          </label>
          <input type="text" name="numero" id="numero" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">
            <i class="bi bi-eye-slash-fill lbl-priv me-1"></i> Complemento
          </label>
          <input type="text" name="complemento" class="form-control" placeholder="Apto, sala...">
        </div>
      </div>
    </div>

    <!-- ── Presença Digital ─────────────────────────────── -->
    <div class="form-section">
      <div class="form-section-title">
        <i class="bi bi-globe"></i> Presença Digital
        <span style="font-size:.7rem;color:#9aab9d;font-weight:500;text-transform:none;letter-spacing:0;">
          <i class="bi bi-eye-fill lbl-pub me-1"></i> Todos os campos são públicos
        </span>
      </div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label"><i class="bi bi-globe me-1"></i> Site</label>
          <input type="url" name="site" class="form-control" placeholder="https://www.seusite.com">
        </div>
        <div class="col-md-4">
          <label class="form-label"><i class="bi bi-linkedin me-1"></i> LinkedIn</label>
          <input type="url" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/...">
        </div>
        <div class="col-md-4">
          <label class="form-label"><i class="bi bi-instagram me-1"></i> Instagram</label>
          <input type="url" name="instagram" class="form-control" placeholder="https://instagram.com/...">
        </div>
        <div class="col-md-4">
          <label class="form-label"><i class="bi bi-facebook me-1"></i> Facebook</label>
          <input type="url" name="facebook" class="form-control" placeholder="https://facebook.com/...">
        </div>
        <div class="col-md-4">
          <label class="form-label"><i class="bi bi-tiktok me-1"></i> TikTok</label>
          <input type="url" name="tiktok" class="form-control" placeholder="https://tiktok.com/@...">
        </div>
        <div class="col-md-4">
          <label class="form-label"><i class="bi bi-youtube me-1"></i> YouTube</label>
          <input type="url" name="youtube" class="form-control" placeholder="https://youtube.com/@...">
        </div>
        <div class="col-12">
          <label class="form-label"><i class="bi bi-link-45deg me-1"></i> Outros Links</label>
          <input type="url" name="outros_links" class="form-control" placeholder="https://...">
        </div>
      </div>
    </div>

    <!-- ── Marketplace ──────────────────────────────────── -->
    <div class="marketplace-card">
      <h5><i class="bi bi-shop me-2" style="color:#1E3425;"></i>Marketplace Impactos Positivos</h5>
      <p>Estamos desenvolvendo um marketplace exclusivo para conectar negócios de impacto com compradores e parceiros do ecossistema. Você tem interesse em comercializar seus produtos/serviços nele no futuro?</p>
      <div class="d-flex gap-4">
        <div class="form-check">
          <input class="form-check-input" type="radio" name="interesse_marketplace"
                 id="mkp_sim" value="Sim" required
                 <?= (isset($negocio['interesse_marketplace']) && $negocio['interesse_marketplace'] === 'Sim') ? 'checked' : '' ?>>
          <label class="form-check-label fw-semibold" for="mkp_sim" style="cursor:pointer;">
            Sim, tenho interesse
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="interesse_marketplace"
                 id="mkp_nao" value="Não" required
                 <?= (isset($negocio['interesse_marketplace']) && $negocio['interesse_marketplace'] === 'Não') ? 'checked' : '' ?>>
          <label class="form-check-label fw-semibold" for="mkp_nao" style="cursor:pointer;">
            Não, no momento não
          </label>
        </div>
      </div>
    </div>

    <!-- ── Botões ────────────────────────────────────────── -->
    <div class="d-flex justify-content-end gap-2 mb-5">
      <a href="/empreendedores/dashboard.php" class="btn-cancelar">Cancelar</a>
      <button type="submit" name="acao" value="salvar" class="btn-avancar">
        Salvar e Avançar <i class="bi bi-arrow-right ms-1"></i>
      </button>
    </div>

  </form>
</div>

<!-- ── Scripts (lógica original preservada integralmente) ── -->
<script>
  // Seleção visual das opções e atualização da área de descrição
  document.querySelectorAll('.categoria-option').forEach(function(label) {
    label.addEventListener('click', function(e) {
      const radio = this.querySelector('.categoria-radio');
      if (radio) radio.checked = true;

      document.querySelectorAll('.categoria-option').forEach(function(l){ l.classList.remove('selected'); });
      this.classList.add('selected');

      const titulo = this.querySelector('.cat-name').textContent.trim();
      const texto  = this.querySelector('.categoria-desc').textContent.trim();
      const descricaoBox = document.getElementById('categoriaDescricaoDinamica');
      document.getElementById('categoriaTitulo').textContent = titulo;
      document.getElementById('categoriaTexto').textContent  = texto;
      descricaoBox.style.display = 'block';

      radio.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });
</script>

<script>
  // Formato legal: mostrar campo "Outros"
  document.addEventListener("DOMContentLoaded", function() {
    const formatoSelect = document.getElementById("formato_legal");
    const formatoOutrosWrapper = document.getElementById("formatoOutrosWrapper");
    const formatoOutrosInput   = document.getElementById("formato_outros");

    function toggleFormatoOutros() {
      if (formatoSelect.value === "Outros") {
        formatoOutrosWrapper.classList.remove("d-none");
        formatoOutrosInput.setAttribute("required", "required");
      } else {
        formatoOutrosWrapper.classList.add("d-none");
        formatoOutrosInput.removeAttribute("required");
        formatoOutrosInput.value = "";
      }
    }
    if (formatoSelect) {
      formatoSelect.addEventListener("change", toggleFormatoOutros);
      toggleFormatoOutros();
    }
  });
</script>

<script>
  // CPF / CNPJ — lógica original preservada integralmente
  (function() {
    const radios       = document.querySelectorAll('input[name="categoria"]');
    const labelCnpjCpf = document.getElementById('cnpjCpfLabel');
    const help         = document.getElementById('cnpjCpfHelp');
    const cnpjCpfInput = document.getElementById('cnpj_cpf');
    const cnpjCpfError = document.getElementById('cnpjCpfError');

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

    function isValidCPF(cpf) {
      cpf = onlyDigits(cpf);
      if (cpf.length !== 11) return false;
      if (/^(\d)\1{10}$/.test(cpf)) return false;
      let sum = 0;
      for (let i = 0; i < 9; i++) sum += parseInt(cpf.charAt(i)) * (10 - i);
      let r = sum % 11, d1 = (r < 2) ? 0 : 11 - r;
      if (d1 !== parseInt(cpf.charAt(9))) return false;
      sum = 0;
      for (let i = 0; i < 10; i++) sum += parseInt(cpf.charAt(i)) * (11 - i);
      r = sum % 11;
      return ((r < 2) ? 0 : 11 - r) === parseInt(cpf.charAt(10));
    }

    function isValidCNPJ(cnpj) {
      cnpj = onlyDigits(cnpj);
      if (cnpj.length !== 14) return false;
      if (/^(\d)\1{13}$/.test(cnpj)) return false;
      const p1 = [5,4,3,2,9,8,7,6,5,4,3,2];
      let s = 0;
      for (let i = 0; i < 12; i++) s += parseInt(cnpj[i]) * p1[i];
      let r = s % 11, d1 = r < 2 ? 0 : 11 - r;
      if (d1 !== parseInt(cnpj[12])) return false;
      const p2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
      s = 0;
      for (let i = 0; i < 13; i++) s += parseInt(cnpj[i]) * p2[i];
      r = s % 11;
      return ((r < 2) ? 0 : 11 - r) === parseInt(cnpj[13]);
    }

    function formatCPF(d) {
      d = d.slice(0,11);
      return d.replace(/^(\d{3})(\d)/,'$1.$2').replace(/^(\d{3})\.(\d{3})(\d)/,'$1.$2.$3').replace(/\.(\d{3})(\d)/,'.$1-$2').slice(0,14);
    }
    function formatCNPJ(d) {
      d = d.slice(0,14);
      return d.replace(/^(\d{2})(\d)/,'$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/,'$1.$2.$3').replace(/\.(\d{3})(\d)/,'.$1/$2').replace(/(\d{4})(\d)/,'$1-$2').slice(0,18);
    }
    function applyMaskAuto(v) {
      const d = onlyDigits(v);
      return d.length <= 11 ? formatCPF(d) : formatCNPJ(d);
    }
    function getSelectedCategory() {
      const radio = document.querySelector('input[name="categoria"]:checked');
      return radio ? radio.value : '';
    }
    function validateCnpjCpfForCategory(category) {
      if (!cnpjCpfInput) return true;
      clearInvalid();
      const digits = onlyDigits(cnpjCpfInput.value.trim());
      if (category === 'Ideação') {
        if (digits.length === 11) { if (!isValidCPF(digits)) { setInvalid('CPF inválido.'); return false; } }
        else if (digits.length === 14) { if (!isValidCNPJ(digits)) { setInvalid('CNPJ inválido.'); return false; } }
        else { setInvalid('Informe CPF (11 dígitos) ou CNPJ (14 dígitos).'); return false; }
      } else {
        if (digits.length !== 14) { setInvalid('Informe um CNPJ válido com 14 dígitos.'); return false; }
        if (!isValidCNPJ(digits)) { setInvalid('CNPJ inválido.'); return false; }
      }
      return true;
    }
    function onCategoryChange() {
      const cat = getSelectedCategory();
      if (cat === 'Ideação') {
        if (labelCnpjCpf) labelCnpjCpf.innerHTML = '<i class="bi bi-eye-slash-fill lbl-priv me-1"></i> CPF ou CNPJ *';
        if (help) help.textContent = "Na categoria Ideação, você pode informar CPF (11 dígitos) ou CNPJ (14 dígitos).";
        if (cnpjCpfInput) { cnpjCpfInput.removeAttribute('maxlength'); cnpjCpfInput.setAttribute('placeholder','CPF ou CNPJ'); cnpjCpfInput.value = applyMaskAuto(cnpjCpfInput.value); }
      } else if (cat) {
        if (labelCnpjCpf) labelCnpjCpf.innerHTML = '<i class="bi bi-eye-slash-fill lbl-priv me-1"></i> CNPJ *';
        if (help) help.textContent = "Para esta categoria, informe apenas um CNPJ válido (14 dígitos).";
        if (cnpjCpfInput) { cnpjCpfInput.setAttribute('maxlength','18'); cnpjCpfInput.setAttribute('placeholder','00.000.000/0000-00'); cnpjCpfInput.value = formatCNPJ(onlyDigits(cnpjCpfInput.value)); }
      } else {
        if (labelCnpjCpf) labelCnpjCpf.innerHTML = '<i class="bi bi-eye-slash-fill lbl-priv me-1"></i> CPF ou CNPJ *';
        if (help) help.textContent = "";
        if (cnpjCpfInput) { cnpjCpfInput.removeAttribute('maxlength'); cnpjCpfInput.setAttribute('placeholder',''); cnpjCpfInput.value = applyMaskAuto(cnpjCpfInput.value); }
      }
      validateCnpjCpfForCategory(cat);
    }

    if (cnpjCpfInput) {
      cnpjCpfInput.addEventListener('input', function() {
        const cat = getSelectedCategory();
        cnpjCpfInput.value = (cat && cat !== 'Ideação') ? formatCNPJ(onlyDigits(cnpjCpfInput.value)) : applyMaskAuto(cnpjCpfInput.value);
        clearInvalid();
      });
      cnpjCpfInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const cat   = getSelectedCategory();
        cnpjCpfInput.value = (cat && cat !== 'Ideação') ? formatCNPJ(onlyDigits(paste)) : applyMaskAuto(paste);
        cnpjCpfInput.dispatchEvent(new Event('input', { bubbles: true }));
      });
      cnpjCpfInput.addEventListener('blur',  function() { validateCnpjCpfForCategory(getSelectedCategory()); });
      cnpjCpfInput.addEventListener('focus', clearInvalid);
    }

    radios.forEach(function(r) { r.addEventListener('change', onCategoryChange); });

    const form = cnpjCpfInput ? cnpjCpfInput.closest('form') : null;
    if (form) {
      form.addEventListener('submit', function(ev) {
        const cat = getSelectedCategory();
        if (!validateCnpjCpfForCategory(cat)) {
          ev.preventDefault();
          if (cnpjCpfInput) cnpjCpfInput.focus();
        } else {
          if (cnpjCpfInput) cnpjCpfInput.value = onlyDigits(cnpjCpfInput.value);
        }
      });
    }

    (function applyInitialSelection() {
      const checked = document.querySelector('input[name="categoria"]:checked');
      if (checked) { checked.closest('.categoria-option')?.classList.add('selected'); }
      onCategoryChange();
    })();
  })();
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>