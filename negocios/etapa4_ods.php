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
if (!$negocio) die("Negócio não encontrado ou você não tem permissão. ID: " . $negocio_id);

$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ods_descricao = [
    1  => ["Erradicação da Pobreza",                    ["Acesso a crédito","Geração de renda","Redução da pobreza","Acesso a serviços essenciais"]],
    2  => ["Fome Zero e Agricultura Sustentável",        ["Segurança alimentar","Agricultura sustentável e regenerativa","Inovação agro e foodtech","Nutrição adequada"]],
    3  => ["Saúde e Bem-Estar",                         ["Saúde mental e bem-estar","Envelhecimento e cuidados contínuos","Prevenção e resposta a doenças","Acesso a serviços de saúde"]],
    4  => ["Educação de Qualidade",                      ["Acesso à educação básica","Educação técnica e requalificação","Edtech e ensino híbrido","Qualidade educacional"]],
    5  => ["Igualdade de Gênero",                       ["Equidade salarial e carreira","Liderança e empreendedorismo feminino","Combate à violência de gênero","Inclusão de mulheres em STEM"]],
    6  => ["Água Potável e Saneamento",                  ["Saneamento básico","Eficiência no uso da água","Segurança hídrica em áreas críticas","Tratamento e reuso"]],
    7  => ["Energia Limpa e Acessível",                  ["Transição energética","Acesso à energia limpa","Eficiência energética","Segurança energética"]],
    8  => ["Trabalho Decente e Crescimento Econômico",   ["Trabalho e salário digno","Direitos trabalhistas","Redução da informalidade","Empregos verdes"]],
    9  => ["Indústria, Inovação e Infraestrutura",      ["Indústria sustentável","Infraestrutura resiliente","Inovação tecnológica e IA","Cadeias produtivas estratégicas"]],
    10 => ["Redução das Desigualdades",                  ["Inclusão econômica","Redução de desigualdades regionais","Inclusão social","Integração de MPEs"]],
    11 => ["Cidades e Comunidades Sustentáveis",        ["Moradia acessível","Infraestrutura urbana","Mobilidade sustentável","Planejamento urbano"]],
    12 => ["Consumo e Produção Responsáveis",            ["Economia circular","Gestão de resíduos","Cadeias responsáveis","Logística reversa e rastreabilidade"]],
    13 => ["Ação Climática",                             ["Redução de emissões","Planos de transição","Justiça e resiliência climática","Finanças climáticas"]],
    14 => ["Vida na Água",                               ["Biodiversidade marinha","Pesca sustentável","Redução da poluição","Economia azul"]],
    15 => ["Vida Terrestre",                             ["Restauração ambiental","Combate ao desmatamento","Uso sustentável do solo","Soluções baseadas na natureza"]],
    16 => ["Paz, Justiça e Instituições Eficazes",     ["Direitos humanos","Combate à corrupção","Governança e transparência","Proteção de comunidades"]],
    17 => ["Parcerias para os Objetivos",                ["Finanças sustentáveis","Parcerias público-privadas","Transferência de tecnologia","Prestação de contas"]],
];

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container my-5 emp-inner">

    <?php
        $etapaAtual = 4;
        include __DIR__ . '/../app/views/partials/progress.php';
        include __DIR__ . '/../app/views/partials/intro_text_ods.php';
    ?>

    <?php if (isset($_SESSION['errors_etapa4'])): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-circle-fill mt-1"></i>
            <ul class="mb-0 ps-2">
                <?php foreach ($_SESSION['errors_etapa4'] as $erro): ?>
                    <li><?= htmlspecialchars($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors_etapa4']); ?>
    <?php endif; ?>

    <form action="/negocios/processar_etapa4.php" method="post">
        <input type="hidden" name="negocio_id" value="<?= htmlspecialchars((string)$negocio_id) ?>">
        <input type="hidden" name="modo" value="cadastro">

        <!-- ── ODS Prioritária ────────────────────────────── -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-bullseye"></i> ODS Prioritária *
            </div>
            <p style="font-size:.88rem;color:#4a5e4f;margin-bottom:1.25rem;">
                <i class="bi bi-eye-fill lbl-pub me-1"></i>
                Qual é a ODS prioritária do seu negócio? Selecione apenas uma. Dado público — será exibido na vitrine.
            </p>

            <div class="row g-3">
                <?php for ($i = 1; $i <= 17; $i++):
                    if (!isset($ods_descricao[$i])) continue;
                    [$titulo, $itens] = $ods_descricao[$i];
                ?>
                <div class="col-12 col-md-6">
                    <label class="ods-card">
                        <input type="radio" name="ods_prioritaria" value="<?= $i ?>"
                               class="visually-hidden ods-radio" required>
                        <div class="ods-card-inner">
                            <div class="ods-img-wrap">
                                <img src="/assets/images/img-ods/<?= str_pad((string)$i, 2, '0', STR_PAD_LEFT) ?>.png"
                                     alt="ODS <?= $i ?>">
                            </div>
                            <div class="ods-info">
                                <div class="ods-titulo"><?= htmlspecialchars($titulo) ?></div>
                                <ul class="ods-itens">
                                    <?php foreach ($itens as $item): ?>
                                        <li><?= htmlspecialchars($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </label>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- ── ODS Relacionadas ───────────────────────────── -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="bi bi-grid-3x3-gap"></i> ODS Relacionadas
                <span style="font-size:.7rem;color:#9aab9d;font-weight:500;text-transform:none;letter-spacing:0;">opcional</span>
            </div>
            <p style="font-size:.85rem;color:#6c8070;margin-bottom:1rem;">
                Selecione outras ODS que também se relacionam com o seu negócio.
            </p>

            <div class="row g-2">
                <?php for ($i = 1; $i <= 17; $i++): ?>
                <div class="col-4 col-md-2 text-center">
                    <label class="ods-check-label">
                        <input type="checkbox" name="ods_relacionadas[]"
                               value="<?= $i ?>" class="visually-hidden ods-check">
                        <img src="/assets/images/img-ods/<?= str_pad((string)$i, 2, '0', STR_PAD_LEFT) ?>.png"
                             alt="ODS <?= $i ?>">
                        <div class="ods-num">ODS <?= $i ?></div>
                    </label>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- ── Botões ─────────────────────────────────────── -->
        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="/negocios/editar_etapa3.php?id=<?= $negocio_id ?>" class="btn-emp-outline">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
            <button type="submit" class="btn-emp-primary">
                Salvar e Avançar <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // ODS prioritária — seleção visual
    document.querySelectorAll(".ods-radio").forEach(function (radio) {
        radio.addEventListener("change", function () {
            document.querySelectorAll(".ods-card").forEach(function (c) { c.classList.remove("selected"); });
            this.closest(".ods-card").classList.add("selected");
        });
    });
    // ODS relacionadas — toggle visual
    document.querySelectorAll(".ods-check").forEach(function (cb) {
        cb.addEventListener("change", function () {
            this.closest(".ods-check-label").classList.toggle("selected", this.checked);
        });
    });
});
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>