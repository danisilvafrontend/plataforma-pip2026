<?php
declare(strict_types=1);
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

// CORREÇÃO: Verifica ambas as possibilidades de nome da coluna (prioritaria/prioritario) e trata NULL
$odsPrioritaria = 0;
if (isset($negocio['ods_prioritaria_id'])) {
    $odsPrioritaria = (int)$negocio['ods_prioritaria_id'];
} elseif (isset($negocio['ods_prioritario_id'])) {
    $odsPrioritaria = (int)$negocio['ods_prioritario_id'];
}

// ODS relacionadas já salvas
$stmt = $pdo->prepare("SELECT ods_id FROM negocio_ods WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$odsRelacionadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <!-- Título à esquerda -->
            <h1 class="mb-4">Editar Etapa 4: Conexão com os ODS</h1>
            
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
            include __DIR__ . '/../app/views/partials/intro_text_ods.php';
        ?>

        <div class="col-md-10">
            <?php if (isset($_SESSION['errors_etapa4'])): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($_SESSION['errors_etapa4'] as $erro): ?>
                            <li><?= htmlspecialchars($erro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errors_etapa4']); ?>
            <?php endif; ?>

            <form action="/negocios/processar_etapa4.php" method="post">
                <!-- CORREÇÃO: Cast para string no htmlspecialchars -->
                <input type="hidden" name="negocio_id" value="<?= htmlspecialchars((string)$negocio_id) ?>">
                <input type="hidden" name="modo" value="editar">

                <!-- ODS prioritária -->
                <div class="mb-4">
                    <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> <strong>Qual é a ODS prioritária do seu negócio? (Selecione apenas uma)</strong></label>
                    <div class="row">
                        <?php
                        $ods_descricao = [
                            1 => ["Erradicação da Pobreza 💰", ["Acesso a crédito","Geração de renda","Redução da pobreza","Acesso a serviços essenciais"]],
                            2 => ["Fome Zero e Agricultura Sustentável 🌾", ["Segurança alimentar","Agricultura sustentável e regenerativa","Inovação agro e foodtech","Nutrição adequada"]],
                            3 => ["Saúde e Bem-Estar ❤️", ["Saúde mental e bem-estar","Envelhecimento e cuidados contínuos","Prevenção e resposta a doenças","Acesso a serviços de saúde"]],
                            4 => ["Educação de Qualidade 📚", ["Acesso à educação básica","Educação técnica e requalificação","Edtech e ensino híbrido","Qualidade educacional"]],
                            5 => ["Igualdade de Gênero ⚖️", ["Equidade salarial e carreira","Liderança e empreendedorismo feminino","Combate à violência de gênero","Inclusão de mulheres em STEM"]],
                            6 => ["Água Potável e Saneamento 💧", ["Saneamento básico","Eficiência no uso da água","Segurança hídrica","Tratamento e reuso"]],
                            7 => ["Energia Limpa e Acessível ⚡", ["Transição energética","Acesso à energia limpa","Eficiência energética","Segurança energética"]],
                            8 => ["Trabalho Decente e Crescimento Econômico 💼", ["Trabalho e salário digno","Direitos trabalhistas","Redução da informalidade","Empregos verdes"]],
                            9 => ["Indústria, Inovação e Infraestrutura 🏗️", ["Indústria sustentável","Infraestrutura resiliente","Inovação tecnológica","Cadeias produtivas"]],
                            10 => ["Redução das Desigualdades 📉", ["Inclusão econômica","Redução de desigualdades regionais","Inclusão social","Integração de MPEs"]],
                            11 => ["Cidades e Comunidades Sustentáveis 🏙️", ["Moradia acessível","Infraestrutura urbana","Mobilidade sustentável","Planejamento urbano"]],
                            12 => ["Consumo e Produção Responsáveis ♻️", ["Economia circular","Gestão de resíduos","Cadeias responsáveis","Logística reversa"]],
                            13 => ["Ação Climática 🌍", ["Redução de emissões","Planos de transição","Justiça climática","Finanças climáticas"]],
                            14 => ["Vida na Água 🌊", ["Biodiversidade marinha","Pesca sustentável","Redução da poluição","Economia azul"]],
                            15 => ["Vida Terrestre 🌱", ["Restauração ambiental","Combate ao desmatamento","Uso sustentável do solo","Soluções baseadas na natureza"]],
                            16 => ["Paz, Justiça e Instituições Eficazes 🕊️", ["Direitos humanos","Combate à corrupção","Governança","Proteção de comunidades"]],
                            17 => ["Parcerias para os Objetivos 🤝", ["Finanças sustentáveis","Parcerias público-privadas","Transferência de tecnologia","Prestação de contas"]],
                        ];

                        for ($i=1; $i<=17; $i++):
                            if (!isset($ods_descricao[$i])) continue;
                            $descricao = $ods_descricao[$i][0] ?? '';
                            $itens = $ods_descricao[$i][1] ?? [];
                        ?>
                            <div class="col-md-6 mb-4">
                                <label class="d-block border rounded p-3" style="cursor:pointer;">
                                    <div class="row align-items-center">
                                        <div class="col-4 text-center">
                                            <input type="radio" name="ods_prioritaria" value="<?= $i ?>" <?= $odsPrioritaria === $i ? 'checked' : '' ?> required>
                                            <!-- CORREÇÃO: Cast para string no str_pad -->
                                            <img src="/assets/images/img-ods/<?= str_pad((string)$i,2,'0',STR_PAD_LEFT) ?>.png" alt="ODS <?= $i ?>" style="width:80px;" class="mt-2">
                                        </div>
                                        <div class="col-8">
                                            <strong><?= $descricao ?></strong>
                                            <ul class="mb-0 ps-3">
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

                <!-- ODS relacionadas -->
                <div class="mb-4">
                    <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> <strong>Selecione outras ODSs relacionadas (opcional)</strong></label>
                    <div class="row">
                        <?php for ($i=1; $i<=17; $i++): ?>
                        <div class="col-md-3 text-center mb-3">
                            <label>
                                <input type="checkbox" name="ods_relacionadas[]" value="<?= $i ?>" <?= in_array($i, $odsRelacionadas) ? 'checked' : '' ?>>
                                <!-- CORREÇÃO: Cast para string no str_pad -->
                                <img src="/assets/images/img-ods/<?= str_pad((string)$i,2,'0',STR_PAD_LEFT) ?>.png" alt="ODS <?= $i ?>" style="width:80px; display:block; margin:auto;">
                                <div>ODS <?= $i ?></div>
                            </label>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="/negocios/editar_etapa3.php?id=<?= $negocio_id ?>" class="btn btn-secondary me-md-2">← Voltar</a>
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>
