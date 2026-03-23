<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Aceita ID via GET OU sessão
$negocio_id = (int)($_GET['id'] ?? $_SESSION['negocio_id'] ?? 0);
if ($negocio_id === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}

$_SESSION['negocio_id'] = $negocio_id;

// Busca negócio
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


// Busca eixo principal já salvo
$eixoSelecionado = (int)$negocio['eixo_principal_id'];

// Busca subáreas já salvas
$stmt = $pdo->prepare("SELECT subarea_id FROM negocio_subareas WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$subareasSelecionadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Busca eixos temáticos
$stmt = $pdo->query("SELECT id, nome, descricao, icone_url FROM eixos_tematicos ORDER BY id");
$eixos = $stmt->fetchAll(PDO::FETCH_ASSOC);


include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
      <!-- Título à esquerda -->
      <h1 class="mb-4">Editar Etapa 3 - Eixo Temático</h1>
      
      <!-- Botões à direita -->
      <div class="d-flex gap-2">
          <a href="/negocios/confirmacao.php?id=<?= htmlspecialchars($_GET['id'] ?? 0) ?>" class="btn btn-warning">
              <i class="bi bi-card-checklist me-1"></i> Voltar para revisão
          </a>
          <a href="/empreendedores/meus-negocios.php" class="btn btn-secondary">
              <i class="bi bi-arrow-left me-1"></i> Voltar aos negócios
          </a>
      </div>
    </div>
    <?php
    include __DIR__ . '/../app/views/partials/intro_text_eixo_tematico.php';
    ?>

     <?php if (isset($_SESSION['errors_etapa3'])): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($_SESSION['errors_etapa3'] as $erro): ?>
                            <li><?= htmlspecialchars($erro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errors_etapa3']); ?>
            <?php endif; ?>


    <div class="row justify-content-center">

            <form action="/negocios/processar_etapa3.php" method="post">
                <input type="hidden" name="negocio_id" value="<?= htmlspecialchars($negocio_id) ?>">
                <input type="hidden" name="modo" value="editar">

                <!-- Seleção do eixo principal -->
                <div class="mb-4">
                    <label class="form-label"><strong><i class="bi bi-eye text-secondary me-1"></i> Quais são os principais eixos de impacto que seu negócio aborda?</strong></label>
                    <div id="eixosRadios" class="row">
                        <?php foreach ($eixos as $eixo): ?>
                        <div class="col-md-4 mb-4">
                            <label class="eixo-option card p-3 text-center h-100 <?= $eixoSelecionado === (int)$eixo['id'] ? 'selected' : '' ?>" style="cursor:pointer;">
                            <input type="radio" name="eixo_principal" value="<?= $eixo['id'] ?>"
                                    class="visually-hidden eixo-radio"
                                    <?= $eixoSelecionado === (int)$eixo['id'] ? 'checked' : '' ?> required>
                            <img src="<?= htmlspecialchars($eixo['icone_url']) ?>"
                                alt="<?= htmlspecialchars($eixo['nome']) ?>"
                                class="d-block mx-auto mb-2"
                                style="height:90px; width:90px; object-fit:cover;">
                            <div class="fw-bold"><?= htmlspecialchars($eixo['nome']) ?></div>
                            <small class="text-muted d-block mb-2">Clique para selecionar</small>
                            <div class="eixo-desc text-start small text-muted">
                                <?= htmlspecialchars($eixo['descricao']) ?>
                            </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Área dinâmica -->
                    <div id="eixoDescricaoDinamica" class="mt-3 p-3 border rounded" style="background:#f8f9fa; display:none;">
                        <div id="eixoTitulo" class="fw-bold mb-1"></div>
                        <div id="eixoTexto" class="small text-muted"></div>
                    </div>
                </div>


                <!-- Subáreas (renderizadas dinamicamente via JS) -->
                <div id="subareas-container" class="mb-4">
                    <!-- Checkboxes serão carregados via JS -->
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="/negocios/editar_etapa2.php?id=<?= $negocio_id ?>" class="btn btn-secondary me-md-2">← Voltar</a>
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    const subareasContainer = document.getElementById('subareas-container');


    // Subáreas completas (usar os textos originais que você já listou)
    const subareas = {
       
        1: [
            {id: 1, nome: "Soluções e tecnologias para democracia e participação cidadã"},
            {id: 2, nome: "Soluções e tecnologias para administração pública, gestão de governo e transparência"},
            {id: 3, nome: "Soluções e tecnologias para inclusão social, igualdade, resolução de conflitos e coesão social"},
            {id: 4, nome: "Soluções e tecnologias para diversidade, gênero, comunidade LGBTQIA+ e minorias étnicas"},
            {id: 5, nome: "Soluções e tecnologias para comunidades tradicionais (indígenas, quilombolas, ribeirinhos, pescadores artesanais, extrativistas e outras)"},
            {id: 6, nome: "Soluções e tecnologias para comércio justo e economia solidária"},
            {id: 7, nome: "Soluções e tecnologias para Direitos Humanos, direitos e deveres do cidadão"},
            {id: 8, nome: "Soluções e tecnologias para direitos trabalhistas"},
            {id: 9, nome: "Soluções e tecnologias para garantir o acesso aos bens comuns (terra, ar, água, florestas etc.) "},
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
            {id: 42, nome: "Tratamento de efluentes e saneamento básico, construção ou gestão de infraestruturas para abastecimento de água, drenagem urbana, coleta e tratamento de efluentes líquidos"},
            {id: 43, nome: "Tratamento de resíduos sólidos urbanos e reciclagem"},
            {id: 44, nome: "Fornecimento de Energia sustentável"},
            {id: 45, nome: "Planejamento e gestão de assentamentos humanos"},
            {id: 46, nome: "Sistemas de transporte públicos seguros, acessíveis e sustentáveis"},
            {id: 47, nome: "Segurança rodoviária "},
            {id: 48, nome: "Prevenção de catástrofes naturais e desastres, gerenciamento holístico do risco de desastres"},
            {id: 49, nome: "Defesa civil e socorro às vítimas de catástrofes naturais e desastres"},
            {id: 50, nome: "Qualidade do ar e redução da poluição em cidades"},
            {id: 51, nome: "Agricultura urbana, Hortas urbanas"},
            {id: 52, nome: "Infraestruturas para pessoas com deficiências"},
            {id: 53, nome: "Relações econômicas, sociais e ambientais positivas entre áreas urbanas, periurbanas e rurais, reforçando o planejamento nacional e regional de desenvolvimento"},
            {id: 54, nome: "Redução do impacto ambiental negativo das cidades prevendo inclusão, a eficiência dos recursos, mitigação e adaptação às mudanças climáticas,"},
            {id: 55, nome: "Logística e mobilidade, movimentação de cargas e passageiros, com diversos e modais de transportes (ex.: ferroviário, aquaviário, aeroviário e rodoviário)"},
            {id: 56, nome: "Tecnologia da Informação e Inteligência Artificial para área de cidades, mobilidade e infraestrutura urbana,"},
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
            {id: 30, nome: "Saúde ambiental (Redução de químicos para o ar, água e solo, para minimizar seus impactos negativos sobre a saúde humana e o meio ambiente)"},
            {id: 31, nome: "Saúde sexual e reprodutiva, incluindo o planejamento familiar, informação e educação"},
            {id: 32, nome: "Prevenção e tratamento de substâncias entorpecentes e uso nocivo do álcool e tabaco"},
            {id: 33, nome: "Tecnologia da Informação e Inteligência Artificial para área de saúde"},
            {id: 34, nome: "Outro"}
        ],
        5: [
            {id: 75, nome: "Serviços financeiros e tecnologias visando a redução de custo e escala em acesso à crédito"},
            {id: 76, nome: "Serviços financeiros e tecnologias visando a redução de custo e escala em transações financeiras"},
            {id: 77, nome: "Serviços financeiros e tecnologias visando a redução de custo e escala em educação financeira"},
            {id: 78, nome: "Serviços financeiros e tecnologias visando a redução de custo e escala em gestão pública"},
            {id: 79, nome: "Serviços financeiros e tecnologias visando a inclusão financeira/bancarização"},
            {id: 80, nome: "Novas tecnologias apropriadas e serviços financeiros, incluindo microfinanças"},
            {id: 81, nome: "Sistemas de transparência financeira e eliminação da corrupção"},
            {id: 82, nome: "Serviços para ampliação dos recursos financeiros para a conservação e o uso sustentável da biodiversidade e dos ecossistemas"},
            {id: 83, nome: "Tecnologia da Informação e Inteligência Artificial para a área financeira"},
            {id: 84, nome: "Outro"}
        ],


        6: [
            {id: 85, nome: "Agropecuária, sistemas sustentáveis de produção de alimentos, fornecimento de insumos e comercialização agrícola"},
            {id: 86, nome: "Água e saneamento, construção e gestão de infraestruturas para o abastecimento de água"},
            {id: 87, nome: "Florestas e uso do solo, produção de produtos madeireiros e não madeireiros (ex.: fibras, alimentos, extratos etc.), bem como atividades de reflorestamento e manutenção de floresta nativa para fim de conservação."},
            {id: 88, nome: "Gestão de Resíduos, empresas que realizam o tratamento de resíduos sólidos, e empresas que fazem a gestão, coleta, separação, reaproveitamento e reciclagem destes."},
            {id: 89, nome: "Mitigação da mudança no clima"},
            {id: 90, nome: "Adaptação à mudança no clima"},
            {id: 91, nome: "Preservação da fauna e da flora"},
            {id: 92, nome: "Prevenção e combate aos maus tratos a animais"},
            {id: 93, nome: "Diversidade genética de Sementes, plantas cultivadas, animais de criação"},
            {id: 94, nome: "Acesso à energia"},
            {id: 95, nome: "Conservação de oceanos, zonas costeiras e marinhas, prevenção e redução da poluição marinha"},
            {id: 96, nome: "Minimização e enfrentamento dos impactos da acidificação dos oceanos"},
            {id: 97, nome: "Diminuição da sobrepesca e práticas de pesca destrutivas, Restauração das populações de peixes e da vida aquática"},
            {id: 98, nome: "Acesso dos pescadores artesanais de pequena escala aos recursos marinhos e mercados"},
            {id: 99, nome: "Proteção e restauração de ecossistemas relacionados com a água, incluindo montanhas, florestas, zonas úmidas, rios, aquíferos e lagos"},
            {id: 100, nome: "Manejo ambientalmente saudável dos produtos químicos e todos os resíduos, ao longo de todo o ciclo de vida destes"},
            {id: 101, nome: "Proteção, recuperação e promoção do uso sustentável de ecossistemas terrestres e florestas"},
            {id: 102, nome: "Combate à desertificação, degradação da terra, perda de biodiversidade. Restauração de terra e solo degradados"},
            {id: 103, nome: "Combate ao desmatamento, restauração de florestas degradadas e aumento do florestamento e o reflorestamento"},
            {id: 104, nome: "Conservação dos ecossistemas de montanha, incluindo a sua biodiversidade"},
            {id: 105, nome: "Redução da degradação de habitats naturais e perda da biodiversidade"},
            {id: 106, nome: "Prevenção da extinção de espécies ameaçadas"},
            {id: 107, nome: "Repartição justa e equitativa dos benefícios derivados da utilização dos recursos genéticos e acesso adequado aos recursos genéticos"},
            {id: 108, nome: "Combate à caça ilegal e ao tráfico de espécies da flora e fauna protegidas"},
            {id: 109, nome: "Redução do impacto de espécies exóticas invasoras em ecossistemas terrestres e aquáticos"},
            {id: 110, nome: "Tecnologias e processos industriais limpos"},
            {id: 111, nome: "Indústria Sustentável - Energia e biocombustíveis, empresas geradoras, transmissoras e distribuidoras de energia elétrica, produtores de biocombustíveis (etanol e biodiesel) energias renováveis. Acesso a pesquisa e tecnologias de energia limpa, incluindo energias renováveis, eficiência energética, Tecnologias de combustíveis fósseis avançadas e mais limpas"},
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
            {id: 122, nome: "Tecnologia da Informação, monitoramento geológico, e Inteligência Artificial aplicada à Biodiversidade, Bioeconomia, Tecnologias Verdes e Indústria Sustentável"},
            {id: 123, nome: "OUTRO (Especifique)"}
        ]
    };

    // Subáreas já selecionadas vindas do PHP
    const subareasSelecionadas = <?= json_encode(array_map('strval', $subareasSelecionadas)) ?>;
document.querySelectorAll('.eixo-option').forEach(function(label) {
        label.addEventListener('click', function() {
            const radio = this.querySelector('.eixo-radio');
            if (radio) {
                radio.checked = true;

                // destaque visual
                document.querySelectorAll('.eixo-option').forEach(function(l){ l.classList.remove('selected'); });
                this.classList.add('selected');

                // atualiza área dinâmica
                const titulo = this.querySelector('.fw-bold').textContent.trim();
                const texto = this.querySelector('.eixo-desc').textContent.trim();
                document.getElementById('eixoTitulo').textContent = titulo;
                document.getElementById('eixoTexto').textContent = texto;
                document.getElementById('eixoDescricaoDinamica').style.display = 'block';

                // popula subáreas
                const eixoId = radio.value;
                subareasContainer.innerHTML = "";
                if (subareas[eixoId]) {
                    subareas[eixoId].forEach(sa => {
                        const checked = subareasSelecionadas.includes(String(sa.id)) ? "checked" : "";
                        const div = document.createElement('div');
                        div.classList.add('form-check');
                        div.style.marginBottom = "10px";
                        div.innerHTML = `
                            <input class="form-check-input" type="checkbox" name="subareas[]" value="${sa.id}" ${checked}>
                            <label class="form-check-label">${sa.nome}</label>
                        `;
                        subareasContainer.appendChild(div);
                    });
                }
            }
        });
    });

    // render inicial (se já havia eixo selecionado)
    const checkedRadio = document.querySelector('input[name="eixo_principal"]:checked');
    if (checkedRadio) {
        checkedRadio.closest('.eixo-option').click();
    }
});
</script>


<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>