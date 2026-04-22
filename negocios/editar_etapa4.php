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

/* Compatibilidade com ambos os nomes de coluna */
$odsPrioritaria = 0;
if (isset($negocio['ods_prioritaria_id'])) {
    $odsPrioritaria = (int)$negocio['ods_prioritaria_id'];
} elseif (isset($negocio['ods_prioritario_id'])) {
    $odsPrioritaria = (int)$negocio['ods_prioritario_id'];
}

$stmt = $pdo->prepare("SELECT ods_id FROM negocio_ods WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$odsRelacionadas = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

$ods_descricao = [
    1  => ["Erradicação da Pobreza", ["Acesso a crédito","Geração de renda","Redução da pobreza","Acesso a serviços essenciais"]],
    2  => ["Fome Zero e Agricultura Sustentável", ["Segurança alimentar","Agricultura sustentável e regenerativa","Inovação agro e foodtech","Nutrição adequada"]],
    3  => ["Saúde e Bem-Estar", ["Saúde mental e bem-estar","Envelhecimento e cuidados contínuos","Prevenção e resposta a doenças","Acesso a serviços de saúde"]],
    4  => ["Educação de Qualidade", ["Acesso à educação básica","Educação técnica e requalificação","Edtech e ensino híbrido","Qualidade educacional"]],
    5  => ["Igualdade de Gênero", ["Equidade salarial e carreira","Liderança e empreendedorismo feminino","Combate à violência de gênero","Inclusão de mulheres em STEM"]],
    6  => ["Água Potável e Saneamento", ["Saneamento básico","Eficiência no uso da água","Segurança hídrica em áreas críticas","Tratamento e reuso"]],
    7  => ["Energia Limpa e Acessível", ["Transição energética","Acesso à energia limpa","Eficiência energética","Segurança energética"]],
    8  => ["Trabalho Decente e Crescimento Econômico", ["Trabalho e salário digno","Direitos trabalhistas","Redução da informalidade","Empregos verdes"]],
    9  => ["Indústria, Inovação e Infraestrutura", ["Indústria sustentável","Infraestrutura resiliente","Inovação tecnológica e IA","Cadeias produtivas estratégicas"]],
    10 => ["Redução das Desigualdades", ["Inclusão econômica","Redução de desigualdades regionais","Inclusão social","Integração de MPEs"]],
    11 => ["Cidades e Comunidades Sustentáveis", ["Moradia acessível","Infraestrutura urbana","Mobilidade sustentável","Planejamento urbano"]],
    12 => ["Consumo e Produção Responsáveis", ["Economia circular","Gestão de resíduos","Cadeias responsáveis","Logística reversa e rastreabilidade"]],
    13 => ["Ação Climática", ["Redução de emissões","Planos de transição","Justiça e resiliência climática","Finanças climáticas"]],
    14 => ["Vida na Água", ["Biodiversidade marinha","Pesca sustentável","Redução da poluição","Economia azul"]],
    15 => ["Vida Terrestre", ["Restauração ambiental","Combate ao desmatamento","Uso sustentável do solo","Soluções baseadas na natureza"]],
    16 => ["Paz, Justiça e Instituições Eficazes", ["Direitos humanos","Combate à corrupção","Governança e transparência","Proteção de comunidades"]],
    17 => ["Parcerias para os Objetivos", ["Finanças sustentáveis","Parcerias público-privadas","Transferência de tecnologia","Prestação de contas"]],
];

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container py-4" style="max-width: 1100px;">

    <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="emp-page-title mb-1">Editar: <?= htmlspecialchars($negocio['nome_fantasia']) ?></h1>
            <p class="emp-page-subtitle mb-0">Etapa 4 — ODS e Alinhamento</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <?php if (!empty($negocio['inscricao_completa'])): ?>
                <a href="/negocios/confirmacao.php?id=<?= (int)$negocio_id ?>" class="btn-emp-outline">
                    <i class="bi bi-card-checklist me-1"></i> Voltar à Revisão
                </a>
            <?php endif; ?>

            <a href="/empreendedores/meus-negocios.php" class="btn-emp-outline">
                <i class="bi bi-arrow-left me-1"></i> Meus Negócios
            </a>
        </div>
    </div>

    <?php if (!empty($_SESSION['errors_etapa4'])): ?>
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 ps-3">
                <?php foreach ($_SESSION['errors_etapa4'] as $erro): ?>
                    <li><?= htmlspecialchars($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors_etapa4']); ?>
    <?php endif; ?>

    <form action="/negocios/processar_etapa4.php" method="post">
        <input type="hidden" name="negocio_id" value="<?= (int)$negocio_id ?>">
        <input type="hidden" name="modo" value="editar">

        <div class="row g-4">
            <div class="col-12 col-lg-8">

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-stars"></i> ODS prioritária
                    </div>

                    <p class="text-muted small mb-3">
                        Escolha a ODS principal que melhor representa o impacto central do seu negócio.
                    </p>

                    <div class="row g-3">
                        <?php foreach ($ods_descricao as $id => [$titulo, $itens]): ?>
                            <div class="col-12 col-md-6">
                                <label class="ods-card <?= $odsPrioritaria === $id ? 'selected' : '' ?>">
                                    <input
                                        type="radio"
                                        name="ods_prioritaria"
                                        value="<?= $id ?>"
                                        class="d-none ods-radio"
                                        <?= $odsPrioritaria === $id ? 'checked' : '' ?>
                                        required
                                    >

                                    <div class="ods-card-inner">
                                        <div class="ods-img-wrap">
                                            <img
                                                src="/assets/images/img-ods/<?= str_pad((string)$id, 2, '0', STR_PAD_LEFT) ?>.png"
                                                alt="ODS <?= $id ?>"
                                            >
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
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-diagram-3"></i> ODS relacionadas
                    </div>

                    <p class="text-muted small mb-3">
                        Marque outras ODS conectadas à atuação do negócio.
                    </p>

                    <div class="row g-3">
                        <?php foreach ($ods_descricao as $id => [$titulo, $itens]): ?>
                            <div class="col-4 col-sm-3 col-lg-3 text-center">
                                <label class="ods-check-label <?= in_array($id, $odsRelacionadas, true) ? 'selected' : '' ?>">
                                    <input
                                        type="checkbox"
                                        name="ods_relacionadas[]"
                                        value="<?= $id ?>"
                                        class="d-none ods-check-input"
                                        <?= in_array($id, $odsRelacionadas, true) ? 'checked' : '' ?>
                                    >

                                    <img
                                        src="/assets/images/img-ods/<?= str_pad((string)$id, 2, '0', STR_PAD_LEFT) ?>.png"
                                        alt="ODS <?= $id ?>"
                                    >

                                    <span class="ods-num">ODS <?= $id ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <div class="col-12 col-lg-4">

                <div class="emp-card mb-4">
                    <div class="emp-card-header">
                        <i class="bi bi-info-circle"></i> Orientações
                    </div>

                    <p class="small text-muted mb-2">
                        A ODS prioritária representa o foco principal de impacto do negócio.
                    </p>
                    <p class="small text-muted mb-0">
                        As ODS relacionadas complementam a atuação e ampliam a leitura do impacto gerado.
                    </p>
                </div>

                <div class="emp-card">
                    <div class="emp-card-header">
                        <i class="bi bi-floppy"></i> Salvar
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn-emp-primary w-100 justify-content-center">
                            <i class="bi bi-floppy me-1"></i> Salvar alterações
                        </button>

                        <?php if (!empty($negocio['inscricao_completa'])): ?>
                            <a href="/negocios/confirmacao.php?id=<?= (int)$negocio_id ?>"
                               class="btn-emp-outline w-100 justify-content-center">
                                <i class="bi bi-card-checklist me-1"></i> Voltar à revisão
                            </a>
                        <?php endif; ?>

                        <a href="/negocios/editar_etapa3.php?id=<?= (int)$negocio_id ?>"
                           class="btn-emp-outline w-100 justify-content-center">
                            <i class="bi bi-arrow-left me-1"></i> Etapa Anterior
                        </a>

                        <a href="/empreendedores/meus-negocios.php"
                           class="btn-emp-outline w-100 justify-content-center">
                            <i class="bi bi-grid me-1"></i> Meus negócios
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ods-card').forEach(function(card) {
        card.addEventListener('click', function() {
            document.querySelectorAll('.ods-card').forEach(function(c) {
                c.classList.remove('selected');
            });
            this.classList.add('selected');

            const radio = this.querySelector('.ods-radio');
            if (radio) radio.checked = true;
        });
    });

    document.querySelectorAll('.ods-check-label').forEach(function(label) {
        label.addEventListener('click', function() {
            const input = this.querySelector('.ods-check-input');

            setTimeout(() => {
                if (input && input.checked) {
                    this.classList.add('selected');
                } else {
                    this.classList.remove('selected');
                }
            }, 10);
        });
    });
});
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>