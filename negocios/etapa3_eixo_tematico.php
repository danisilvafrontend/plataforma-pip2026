<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id = (int)($_GET['id'] ?? $_SESSION['negocio_id'] ?? 0);
if ($negocio_id === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}
$_SESSION['negocio_id'] = $negocio_id;

$stmt = $pdo->prepare("
    SELECT n.*, e.eh_fundador 
    FROM negocios n 
    JOIN empreendedores e ON n.empreendedor_id = e.id 
    WHERE n.id = ? AND n.empreendedor_id = ?
");
$stmt->execute([$negocio_id, $_SESSION['user_id']]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$negocio) {
    die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);
}

$stmt  = $pdo->query("SELECT id, nome, descricao, icone_url FROM eixos_tematicos ORDER BY id");
$eixos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<style>

</style>

<div class="container my-5" style="max-width: 900px;">

  <?php
    $etapaAtual = 3;
    include __DIR__ . '/../app/views/partials/progress.php';
    include __DIR__ . '/../app/views/partials/intro_text_eixo_tematico.php';
  ?>

  <?php if (isset($_SESSION['errors_etapa3'])): ?>
    <div class="alert d-flex align-items-start gap-2 mb-4"
         style="background:#fde8ea;border:1px solid #f5c2c7;color:#842029;border-radius:10px;">
      <i class="bi bi-exclamation-circle-fill mt-1"></i>
      <ul class="mb-0 ps-2">
        <?php foreach ($_SESSION['errors_etapa3'] as $erro): ?>
          <li><?= htmlspecialchars($erro) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php unset($_SESSION['errors_etapa3']); ?>
  <?php endif; ?>

  <form action="/negocios/processar_etapa3.php" method="post">
    <input type="hidden" name="negocio_id" value="<?= htmlspecialchars($negocio_id) ?>">
    <input type="hidden" name="modo" value="cadastro">

    <!-- ── Seleção do Eixo Principal ──────────────────────── -->
    <div class="form-section">
      <div class="form-section-title">
        <i class="bi bi-bullseye"></i> Eixo Principal de Impacto *
      </div>

      <p style="font-size:.88rem;color:#4a5e4f;margin-bottom:1rem;">
        <i class="bi bi-eye-fill me-1" style="color:#3a6f82;"></i>
        Quais são os principais eixos de impacto que seu negócio aborda?
      </p>

      <div class="row g-3" id="eixosRadios">
        <?php foreach ($eixos as $eixo): ?>
        <div class="col-6 col-md-4">
          <label class="eixo-option">
            <input type="radio" name="eixo_principal" value="<?= $eixo['id'] ?>"
                   class="visually-hidden eixo-radio" required>
            <img src="<?= htmlspecialchars($eixo['icone_url']) ?>"
                 alt="<?= htmlspecialchars($eixo['nome']) ?>" width="100">
            <div class="eixo-nome"><?= htmlspecialchars($eixo['nome']) ?></div>
            <small>Clique para selecionar</small>
            <div class="eixo-desc"><?= htmlspecialchars($eixo['descricao']) ?></div>
          </label>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Área de descrição dinâmica -->
      <div class="eixo-desc-box" id="eixoDescricaoDinamica">
        <div class="desc-title" id="eixoTitulo"></div>
        <div class="desc-text"  id="eixoTexto"></div>
      </div>
    </div>

    <!-- ── Subáreas ────────────────────────────────────────── -->
    <div class="form-section" id="subareas-section" style="display:none;">
      <div class="form-section-title">
        <i class="bi bi-list-check"></i> Subáreas de Atuação
      </div>
      <p style="font-size:.85rem;color:#6c8070;margin-bottom:1rem;">
        Selecione todas as subáreas que se aplicam ao seu negócio dentro do eixo escolhido.
      </p>
      <div id="subareas-container"></div>
    </div>

    <!-- ── Botões ──────────────────────────────────────────── -->
    <div class="d-flex justify-content-end gap-2 mb-5">
      <a href="/negocios/editar_etapa2.php?id=<?= $negocio_id ?>" class="btn-voltar">
        <i class="bi bi-arrow-left me-1"></i> Voltar
      </a>
      <button type="submit" class="btn-avancar">
        Salvar e Avançar <i class="bi bi-arrow-right ms-1"></i>
      </button>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const subareasContainer = document.getElementById('subareas-container');
    const subareasSection   = document.getElementById('subareas-section');

    const subareas = {
        1: [
            {id: 1,  nome: "Soluções e tecnologias para democracia e participação cidadã"},
            {id: 2,  nome: "Soluções e tecnologias para administração pública, gestão de governo e transparência"},
            {id: 3,  nome: "Soluções e tecnologias para inclusão social, igualdade, resolução de conflitos e coesão social"},
            {id: 4,  nome: "Soluções e tecnologias para diversidade, gênero, comunidade LGBTQIA+ e minorias étnicas"},
            {id: 5,  nome: "Soluções e tecnologias para comunidades tradicionais (indígenas, quilombolas, ribeirinhos, pescadores artesanais, extrativistas e outras)"},
            {id: 6,  nome: "Soluções e tecnologias para comércio justo e economia solidária"},
            {id: 7,  nome: "Soluções e tecnologias para Direitos Humanos, direitos e deveres do cidadão"},
            {id: 8,  nome: "Soluções e tecnologias para direitos trabalhistas"},
            {id: 9,  nome: "Soluções e tecnologias para garantir o acesso aos bens comuns (terra, ar, água, florestas etc.)"},
            {id: 10, nome: "Soluções e tecnologias para apoio à Agricultura Familiar e/ou Pequenos Produtores Rurais"},
            {id: 11, nome: "Soluções e tecnologias para segurança alimentar e gestão de alimentos"},
            {id: 12, nome: "Soluções e tecnologias para cultura de paz, não violência, discriminação e racismo"},
            {id: 13, nome: "Soluções e tecnologias para ampliação do empreendedorismo e inovação"},
            {id: 14, nome: "Soluções e tecnologias para geração de emprego e qualificação profissional"},
            {id: 15, nome: "Soluções e tecnologias para promoção do Consumo sustentável"},
            {id: 16, nome: "Soluções e tecnologias para apoio a processos de migração e combate ao tráfico de pessoas e de drogas"},
            {id: 17, nome: "Soluções e tecnologias para promoção do Acesso público à informação"},
            {id: 18, nome: "Soluções e tecnologias para proteção e salvaguarda do patrimônio cultural e natural"},
            {id: 19, nome: "Outro"}
        ],
        2: [
            {id: 35, nome: "Transporte, Logística, Mobilidade"},
            {id: 36, nome: "Soluções e tecnologias para a Habitação, infraestrutura e construção, urbanização de favelas, moradia digna, acesso à habitação adequada e a preço acessível"},
            {id: 37, nome: "Assistência técnica e financeira, para construções sustentáveis e resilientes, utilizando materiais locais"},
            {id: 38, nome: "Serviços relacionados à participação cidadã"},
            {id: 39, nome: "Monitoramento e inteligência de dados em cidades"},
            {id: 40, nome: "Segurança pública"},
            {id: 41, nome: "Acesso universal a espaços públicos seguros, inclusivos e verdes"},
            {id: 42, nome: "Tratamento de efluentes e saneamento básico"},
            {id: 43, nome: "Tratamento de resíduos sólidos urbanos e reciclagem"},
            {id: 44, nome: "Fornecimento de Energia sustentável"},
            {id: 45, nome: "Planejamento e gestão de assentamentos humanos"},
            {id: 46, nome: "Sistemas de transporte públicos seguros, acessíveis e sustentáveis"},
            {id: 47, nome: "Segurança rodoviária"},
            {id: 48, nome: "Prevenção de catástrofes naturais e desastres"},
            {id: 49, nome: "Defesa civil e socorro às vítimas de catástrofes naturais e desastres"},
            {id: 50, nome: "Qualidade do ar e redução da poluição em cidades"},
            {id: 51, nome: "Agricultura urbana, Hortas urbanas"},
            {id: 52, nome: "Infraestruturas para pessoas com deficiências"},
            {id: 53, nome: "Relações econômicas, sociais e ambientais positivas entre áreas urbanas, periurbanas e rurais"},
            {id: 54, nome: "Redução do impacto ambiental negativo das cidades"},
            {id: 55, nome: "Logística e mobilidade, movimentação de cargas e passageiros"},
            {id: 56, nome: "Tecnologia da Informação e Inteligência Artificial para cidades, mobilidade e infraestrutura urbana"},
            {id: 57, nome: "Outro"}
        ],
        3: [
            {id: 58, nome: "Soluções e tecnologias de educação para a primeira infância"},
            {id: 59, nome: "Soluções e tecnologias de educação para o ensino fundamental"},
            {id: 60, nome: "Soluções e tecnologias de educação para o ensino fundamental II"},
            {id: 61, nome: "Soluções e tecnologias de educação para o ensino médio"},
            {id: 62, nome: "Soluções e tecnologias de educação para o ensino superior"},
            {id: 63, nome: "Soluções relacionadas à educação técnica e profissional"},
            {id: 64, nome: "Educação em sustentabilidade, ciência e cidadania"},
            {id: 65, nome: "Ensino de artes"},
            {id: 66, nome: "Formação de professores"},
            {id: 67, nome: "Soluções e tecnologia de educação relacionados à alfabetização"},
            {id: 68, nome: "Educação de pessoas maiores"},
            {id: 69, nome: "Educação para pessoas com deficiência"},
            {id: 70, nome: "Cooperação científica e difusão de ciências"},
            {id: 71, nome: "Marketing, mídias e jornalismo"},
            {id: 72, nome: "Acesso à informação, tecnologia da informação e telecomunicações"},
            {id: 73, nome: "Acesso à cultura"},
            {id: 74, nome: "Outro"}
        ],
        4: [
            {id: 20, nome: "Soluções para problemas de gestão de saúde: atendimento, governança, análise de dados, redução de custos"},
            {id: 21, nome: "Soluções para melhoria da qualidade de vida de pacientes: diagnósticos, tratamentos, prevenção, suporte, cura"},
            {id: 22, nome: "Vacinas"},
            {id: 23, nome: "Genética"},
            {id: 24, nome: "Doação de sangue"},
            {id: 25, nome: "Soluções para resistência microbiana"},
            {id: 26, nome: "Nutrição e Alimentação Saudável"},
            {id: 27, nome: "Controle de epidemias e doenças transmissíveis"},
            {id: 28, nome: "Saúde mental"},
            {id: 29, nome: "Saúde animal"},
            {id: 30, nome: "Saúde ambiental"},
            {id: 31, nome: "Saúde sexual e reprodutiva, incluindo o planejamento familiar"},
            {id: 32, nome: "Prevenção e tratamento de substâncias entorpecentes e uso nocivo do álcool e tabaco"},
            {id: 33, nome: "Tecnologia da Informação e Inteligência Artificial para área de saúde"},
            {id: 34, nome: "Outro"}
        ],
        5: [
            {id: 75, nome: "Serviços financeiros e tecnologias visando redução de custo e escala em acesso à crédito"},
            {id: 76, nome: "Serviços financeiros e tecnologias visando redução de custo e escala em transações financeiras"},
            {id: 77, nome: "Serviços financeiros e tecnologias visando redução de custo e escala em educação financeira"},
            {id: 78, nome: "Serviços financeiros e tecnologias visando redução de custo e escala em gestão pública"},
            {id: 79, nome: "Serviços financeiros e tecnologias visando a inclusão financeira/bancarização"},
            {id: 80, nome: "Novas tecnologias apropriadas e serviços financeiros, incluindo microfinanças"},
            {id: 81, nome: "Sistemas de transparência financeira e eliminação da corrupção"},
            {id: 82, nome: "Serviços para ampliação dos recursos financeiros para a conservação e o uso sustentável da biodiversidade"},
            {id: 83, nome: "Tecnologia da Informação e Inteligência Artificial para a área financeira"},
            {id: 84, nome: "Outro"}
        ],
        6: [
            {id: 85,  nome: "Agropecuária, sistemas sustentáveis de produção de alimentos"},
            {id: 86,  nome: "Água e saneamento"},
            {id: 87,  nome: "Florestas e uso do solo"},
            {id: 88,  nome: "Gestão de Resíduos"},
            {id: 89,  nome: "Mitigação da mudança no clima"},
            {id: 90,  nome: "Adaptação à mudança no clima"},
            {id: 91,  nome: "Preservação da fauna e da flora"},
            {id: 92,  nome: "Prevenção e combate aos maus tratos a animais"},
            {id: 93,  nome: "Diversidade genética de Sementes, plantas cultivadas, animais de criação"},
            {id: 94,  nome: "Acesso à energia"},
            {id: 95,  nome: "Conservação de oceanos, zonas costeiras e marinhas"},
            {id: 96,  nome: "Minimização e enfrentamento dos impactos da acidificação dos oceanos"},
            {id: 97,  nome: "Diminuição da sobrepesca e práticas de pesca destrutivas"},
            {id: 98,  nome: "Acesso dos pescadores artesanais de pequena escala aos recursos marinhos e mercados"},
            {id: 99,  nome: "Proteção e restauração de ecossistemas relacionados com a água"},
            {id: 100, nome: "Manejo ambientalmente saudável dos produtos químicos e resíduos"},
            {id: 101, nome: "Proteção, recuperação e promoção do uso sustentável de ecossistemas terrestres e florestas"},
            {id: 102, nome: "Combate à desertificação, degradação da terra, perda de biodiversidade"},
            {id: 103, nome: "Combate ao desmatamento e reflorestamento"},
            {id: 104, nome: "Conservação dos ecossistemas de montanha"},
            {id: 105, nome: "Redução da degradação de habitats naturais e perda da biodiversidade"},
            {id: 106, nome: "Prevenção da extinção de espécies ameaçadas"},
            {id: 107, nome: "Repartição justa dos benefícios derivados da utilização dos recursos genéticos"},
            {id: 108, nome: "Combate à caça ilegal e ao tráfico de espécies da flora e fauna protegidas"},
            {id: 109, nome: "Redução do impacto de espécies exóticas invasoras"},
            {id: 110, nome: "Tecnologias e processos industriais limpos"},
            {id: 111, nome: "Indústria Sustentável - Energia e biocombustíveis"},
            {id: 112, nome: "Indústria Sustentável Fabricação de Alimentos e Bebidas"},
            {id: 113, nome: "Indústria Sustentável Farmoquímico e Farmacêutico"},
            {id: 114, nome: "Indústria Sustentável Madeira e Móveis"},
            {id: 115, nome: "Indústria Sustentável Metal-Mecânico e Metalurgia"},
            {id: 116, nome: "Indústria Sustentável Papel e Celulose"},
            {id: 117, nome: "Indústria Sustentável Químico"},
            {id: 118, nome: "Indústria Sustentável Têxtil, Confecção e Calçados"},
            {id: 119, nome: "Indústria Sustentável Petróleo e Gás"},
            {id: 120, nome: "Mineração"},
            {id: 121, nome: "Pesca e Aquicultura"},
            {id: 122, nome: "Tecnologia da Informação e Inteligência Artificial aplicada à Biodiversidade e Bioeconomia"},
            {id: 123, nome: "OUTRO (Especifique)"}
        ]
    };

    document.querySelectorAll('.eixo-option').forEach(function(label) {
        label.addEventListener('click', function() {
            const radio = this.querySelector('.eixo-radio');
            if (!radio) return;
            radio.checked = true;

            // Destaque visual
            document.querySelectorAll('.eixo-option').forEach(function(l) { l.classList.remove('selected'); });
            this.classList.add('selected');

            // Área de descrição dinâmica
            const titulo = this.querySelector('.eixo-nome').textContent.trim();
            const texto  = this.querySelector('.eixo-desc').textContent.trim();
            document.getElementById('eixoTitulo').textContent = titulo;
            document.getElementById('eixoTexto').textContent  = texto;
            document.getElementById('eixoDescricaoDinamica').style.display = 'block';

            // Popula subáreas
            const eixoId = radio.value;
            subareasContainer.innerHTML = '';

            if (subareas[eixoId] && subareas[eixoId].length > 0) {
                suareasSection.style.display = 'block';
                subareas[eixoId].forEach(function(sa) {
                    const div = document.createElement('div');
                    div.classList.add('form-check');
                    div.innerHTML =
                        '<input class="form-check-input" type="checkbox" name="subareas[]" value="' + sa.id + '" id="sa_' + sa.id + '">' +
                        '<label class="form-check-label" for="sa_' + sa.id + '">' + sa.nome + '</label>';
                    subareasContainer.appendChild(div);
                });
            } else {
                subareasSection.style.display = 'none';
            }
        });
    });

    // Alias para corrigir typo no código acima
    var suareasSection = subareasSection;
});
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>