<?php
session_start();

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Verifica o login do parceiro
if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// Busca todos os dados da Tabela principal de Parceiros e do Contrato
$stmt = $pdo->prepare("
    SELECT p.*, 
           c.tipos_parceria, c.natureza_parceria, c.escopo_atuacao, c.escopo_outro, 
           c.nivel_engajamento, c.oferece_premiacao, c.premio_descricao,
           c.deseja_publicar, c.rede_impacto 
    FROM parceiros p
    LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id
    WHERE p.id = ?
");
$stmt->execute([$parceiro_id]);
$parceiro = $stmt->fetch(PDO::FETCH_ASSOC);

// Decodifica os JSONs
$tipos = !empty($parceiro['tipos_parceria']) ? json_decode($parceiro['tipos_parceria'], true) : [];
$naturezas = !empty($parceiro['natureza_parceria']) ? json_decode($parceiro['natureza_parceria'], true) : [];
$escopo = !empty($parceiro['escopo_atuacao']) ? json_decode($parceiro['escopo_atuacao'], true) : [];
$publicacoes = !empty($parceiro['deseja_publicar']) ? json_decode($parceiro['deseja_publicar'], true) : [];
$rede_impacto = $parceiro['rede_impacto'] ?? '';

if (!is_array($tipos)) $tipos = [];
if (!is_array($naturezas)) $naturezas = [];
if (!is_array($escopo)) $escopo = [];
if (!is_array($publicacoes)) $publicacoes = [];

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container py-5 parceiro-review-shell">
    <div class="parceiro-review-hero mb-4 mb-lg-5">
        <div class="parceiro-review-hero-card">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <span class="parceiro-step-kicker">Revisão final</span>
                    <h1 class="parceiro-step-title mb-1">Confira suas informações antes da Carta-Acordo</h1>
                    <p class="parceiro-step-subtitle mb-0">
                        Revise os dados preenchidos, ajuste o que for necessário e siga para a geração da minuta da parceria.
                    </p>
                </div>

                <div class="parceiro-review-badge">
                    <i class="bi bi-shield-check"></i>
                    Cadastro pronto para validação
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <aside class="parceiro-review-aside">
                <div class="parceiro-step-aside-card">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-list-check"></i>
                        Antes de continuar
                    </div>
                    <ul class="parceiro-step-aside-list">
                        <li>Revise os dados institucionais e os termos principais da parceria com atenção.</li>
                        <li>Esta etapa está focada na geração da Carta-Acordo com base nas informações essenciais já preenchidas.</li>
                        <li>A etapa 4 poderá ser complementada ou ajustada posteriormente, sem impedir a continuidade deste fluxo.</li>
                    </ul>
                </div>

                <div class="parceiro-step-aside-card parceiro-step-aside-highlight">
                    <div class="parceiro-step-aside-title">
                        <i class="bi bi-file-earmark-text-fill"></i>
                        Próxima tela
                    </div>
                    <p class="mb-0">
                        Após esta revisão, a plataforma irá montar a minuta da Carta-Acordo com base nos dados informados ao longo do cadastro.
                    </p>
                </div>
            </aside>
        </div>

        <div class="col-lg-8">
            <div class="parceiro-review-stack">

                <section class="parceiro-review-card">
                    <div class="parceiro-review-card-header">
                        <div>
                            <span class="parceiro-review-step">Etapa 1</span>
                            <h2 class="parceiro-review-card-title">Dados da Instituição</h2>
                        </div>
                        <a href="etapa1_dados.php?from=confirmacao" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                    </div>

                    <div class="parceiro-review-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="parceiro-review-item">
                                    <span class="parceiro-review-label">Nome Fantasia</span>
                                    <strong class="parceiro-review-value"><?= htmlspecialchars($parceiro['nome_fantasia']) ?></strong>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="parceiro-review-item">
                                    <span class="parceiro-review-label">Razão Social</span>
                                    <strong class="parceiro-review-value"><?= htmlspecialchars($parceiro['razao_social']) ?></strong>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="parceiro-review-item">
                                    <span class="parceiro-review-label">CNPJ</span>
                                    <strong class="parceiro-review-value"><?= htmlspecialchars($parceiro['cnpj']) ?></strong>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="parceiro-review-item">
                                    <span class="parceiro-review-label">Localização</span>
                                    <strong class="parceiro-review-value"><?= htmlspecialchars($parceiro['cidade'] ?? '') ?> - <?= htmlspecialchars($parceiro['estado'] ?? '') ?></strong>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="parceiro-review-item">
                                    <span class="parceiro-review-label">Representante Legal</span>
                                    <strong class="parceiro-review-value">
                                        <?= htmlspecialchars($parceiro['rep_nome']) ?> (<?= htmlspecialchars($parceiro['rep_cargo']) ?>)
                                    </strong>
                                    <span class="parceiro-review-note">CPF: <?= htmlspecialchars($parceiro['rep_cpf'] ?? 'Não informado') ?></span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="parceiro-review-item">
                                    <span class="parceiro-review-label">Contatos do Representante</span>
                                    <strong class="parceiro-review-value"><?= htmlspecialchars($parceiro['rep_email']) ?></strong>
                                    <span class="parceiro-review-note"><?= htmlspecialchars($parceiro['rep_telefone'] ?? '') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="parceiro-review-card">
                    <div class="parceiro-review-card-header">
                        <div>
                            <span class="parceiro-review-step">Etapa 2</span>
                            <h2 class="parceiro-review-card-title">Tipo de Parceria</h2>
                        </div>
                        <a href="etapa2_tipo.php?from=confirmacao" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                    </div>

                    <div class="parceiro-review-card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <span class="parceiro-review-label mb-2 d-block">Papéis selecionados</span>
                                <?php if (!empty($tipos)): ?>
                                    <div class="parceiro-review-pill-list">
                                        <?php foreach ($tipos as $t): ?>
                                            <span class="parceiro-review-pill"><?= htmlspecialchars($t) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="parceiro-review-empty">Nenhum tipo selecionado.</span>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <span class="parceiro-review-label mb-2 d-block">Natureza dos recursos</span>
                                <?php if (!empty($naturezas)): ?>
                                    <div class="parceiro-review-pill-list">
                                        <?php foreach ($naturezas as $n): ?>
                                            <span class="parceiro-review-pill"><?= htmlspecialchars($n) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="parceiro-review-empty">Nenhuma natureza selecionada.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="parceiro-review-card">
                    <div class="parceiro-review-card-header">
                        <div>
                            <span class="parceiro-review-step">Etapa 3</span>
                            <h2 class="parceiro-review-card-title">O Nosso Acordo</h2>
                        </div>
                        <a href="etapa3_combinado.php?from=confirmacao" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                    </div>

                    <div class="parceiro-review-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="parceiro-review-item">
                                    <span class="parceiro-review-label">Nível de Engajamento</span>
                                    <strong class="parceiro-review-value"><?= htmlspecialchars(ucfirst($parceiro['nivel_engajamento'] ?? 'Não informado')) ?></strong>
                                </div>
                            </div>

                            <div class="col-12">
                                <span class="parceiro-review-label mb-2 d-block">Escopo de Atuação</span>
                                <?php if (!empty($escopo)): ?>
                                    <div class="parceiro-review-pill-list">
                                        <?php foreach ($escopo as $e): ?>
                                            <span class="parceiro-review-pill"><?= htmlspecialchars($e) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="parceiro-review-empty">Nenhum escopo selecionado.</span>
                                <?php endif; ?>

                                <?php if (!empty($parceiro['escopo_outro'])): ?>
                                    <div class="parceiro-review-note-box mt-3">
                                        <strong>Outro:</strong> <?= htmlspecialchars($parceiro['escopo_outro']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($parceiro['oferece_premiacao'])): ?>
                                <div class="col-12">
                                    <div class="parceiro-review-highlight-success">
                                        <span class="parceiro-review-label text-success mb-1 d-block">
                                            <i class="bi bi-gift me-1"></i>Premiação oferecida
                                        </span>
                                        <div class="parceiro-review-highlight-text">
                                            <?= htmlspecialchars($parceiro['premio_descricao']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="parceiro-review-card">
                    <div class="parceiro-review-card-header">
                        <div>
                            <span class="parceiro-review-step">Etapa 5</span>
                            <h2 class="parceiro-review-card-title">Uso da Plataforma</h2>
                        </div>
                        <a href="etapa5_plataforma.php?from=confirmacao" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                    </div>

                    <div class="parceiro-review-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <span class="parceiro-review-label mb-2 d-block">Deseja Publicar / Promover</span>
                                <?php if (!empty($publicacoes)): ?>
                                    <div class="parceiro-review-pill-list">
                                        <?php foreach ($publicacoes as $pub): ?>
                                            <span class="parceiro-review-pill"><?= htmlspecialchars($pub) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="parceiro-review-empty">Nada selecionado.</span>
                                <?php endif; ?>
                            </div>

                            <div class="col-12">
                                <div class="parceiro-review-item">
                                    <span class="parceiro-review-label">Participação na Rede de Impacto</span>
                                    <strong class="parceiro-review-value">
                                        <?php
                                            if ($rede_impacto === 'sim') echo 'Sim, quero participar';
                                            elseif ($rede_impacto === 'nao') echo 'Não';
                                            elseif ($rede_impacto === 'avaliar_depois') echo 'Avaliar depois';
                                            else echo 'Não informado';
                                        ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="parceiro-review-card parceiro-review-declaration">
                    <div class="parceiro-review-card-header parceiro-review-card-header-warn">
                        <div>
                            <span class="parceiro-review-step">Confirmação</span>
                            <h2 class="parceiro-review-card-title">Geração da Carta-Acordo</h2>
                        </div>
                    </div>

                    <div class="parceiro-review-card-body">
                        <p class="parceiro-review-text mb-4">
                            Ao avançar, o sistema irá gerar o documento oficial da Carta-Acordo de Parceria com base nas informações revisadas nesta tela. Você ainda poderá ler o documento completo e verificar as cláusulas antes da assinatura digital.
                        </p>

                        <div class="parceiro-step-toggle-box parceiro-review-confirm-box">
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" id="checkRevisao" required>
                                <label class="form-check-label fw-semibold" for="checkRevisao">
                                    Declaro que as informações acima foram revisadas, são verdadeiras e desejo gerar a minuta da Carta-Acordo.
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="parceiro-review-actions">
                    <a href="etapa6_juridico.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Voltar e editar
                    </a>

                    <a href="assinar_acordo.php" class="btn-reg-submit parceiro-review-btn-disabled" id="btnAvancar" aria-disabled="true">
                        <i class="bi bi-file-earmark-text me-2"></i>Gerar e ler Carta-Acordo
                        <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkRevisao = document.getElementById('checkRevisao');
    const btnAvancar = document.getElementById('btnAvancar');

    if (!checkRevisao || !btnAvancar) return;

    checkRevisao.addEventListener('change', function () {
        if (this.checked) {
            btnAvancar.classList.remove('parceiro-review-btn-disabled');
            btnAvancar.setAttribute('aria-disabled', 'false');
            btnAvancar.style.pointerEvents = 'auto';
        } else {
            btnAvancar.classList.add('parceiro-review-btn-disabled');
            btnAvancar.setAttribute('aria-disabled', 'true');
            btnAvancar.style.pointerEvents = 'none';
        }
    });
});
</script>


<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
