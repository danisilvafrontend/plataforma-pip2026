<?php
// /public_html/empreendedores/meus-negocios.php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$pageTitle = 'Meus Negócios — Impactos Positivos';

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function labelStatusPremiacao(?string $status): string
{
    return match ($status) {
        'rascunho' => 'Rascunho',
        'elegivel' => 'Elegível',
        'inelegivel' => 'Inelegível',
        'classificada_fase_1' => 'Classificada Fase 1',
        'classificada_fase_2' => 'Classificada Fase 2',
        'finalista' => 'Finalista',
        'vencedora' => 'Vencedora',
        'eliminada' => 'Eliminada',
        default => 'Não inscrito',
    };
}

function badgePremiacao(?string $status): array
{
    return match ($status) {
        'enviada' => ['bg' => '#e3f2fd', 'color' => '#1565c0'],
        'em_triagem' => ['bg' => '#fff8e1', 'color' => '#f57f17'],
        'elegivel' => ['bg' => '#e8f5e9', 'color' => '#2e7d32'],
        'inelegivel' => ['bg' => '#fdecea', 'color' => '#c62828'],
        'classificada_fase_1' => ['bg' => '#e0f7fa', 'color' => '#006064'],
        'classificada_fase_2' => ['bg' => '#e0f2f1', 'color' => '#00695c'],
        'finalista' => ['bg' => '#ede7f6', 'color' => '#5e35b1'],
        'vencedora' => ['bg' => '#fff3cd', 'color' => '#856404'],
        'eliminada' => ['bg' => '#fdecea', 'color' => '#c62828'],
        'rascunho' => ['bg' => '#f5f5f5', 'color' => '#757575'],
        default => ['bg' => '#f5f5f5', 'color' => '#757575'],
    };
}

$stmtPremiacaoAtiva = $pdo->query("
    SELECT id, nome, ano, status
    FROM premiacoes
    WHERE status IN ('ativa', 'planejada')
    ORDER BY 
        CASE WHEN status = 'ativa' THEN 0 ELSE 1 END,
        ano DESC,
        id DESC
    LIMIT 1
");
$premiacaoAtiva = $stmtPremiacaoAtiva->fetch(PDO::FETCH_ASSOC);
$premiacaoIdAtiva = (int)($premiacaoAtiva['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_inscricao_premiacao') {
    try {
        if ($premiacaoIdAtiva <= 0) {
            throw new Exception('Nenhuma edição da premiação disponível no momento.');
        }

        $negocioId = (int)($_POST['negocio_id'] ?? 0);
        $desejaParticipar = isset($_POST['deseja_participar']) ? 1 : 0;
        $aceiteRegulamento = isset($_POST['aceite_regulamento']) ? 1 : 0;
        $aceiteVeracidade = isset($_POST['aceite_veracidade']) ? 1 : 0;

        $stmtNegocio = $pdo->prepare("
            SELECT id, empreendedor_id, categoria, inscricao_completa, publicado_vitrine
            FROM negocios
            WHERE id = ? AND empreendedor_id = ?
            LIMIT 1
        ");
        $stmtNegocio->execute([$negocioId, $_SESSION['user_id']]);
        $negocioPremiacao = $stmtNegocio->fetch(PDO::FETCH_ASSOC);

        if (!$negocioPremiacao) {
            throw new Exception('Negócio não encontrado.');
        }

        if ((int)$negocioPremiacao['inscricao_completa'] !== 1 || (int)$negocioPremiacao['publicado_vitrine'] !== 1) {
            throw new Exception('Este negócio ainda não está apto para participar da premiação.');
        }

        if ($desejaParticipar === 1 && ($aceiteRegulamento !== 1 || $aceiteVeracidade !== 1)) {
            throw new Exception('Para participar da premiação, aceite o regulamento e a veracidade das informações.');
        }

        $stmtExiste = $pdo->prepare("
            SELECT id, status
            FROM premiacao_inscricoes
            WHERE premiacao_id = ? AND negocio_id = ?
            LIMIT 1
        ");
        $stmtExiste->execute([$premiacaoIdAtiva, $negocioId]);
        $inscricaoExistente = $stmtExiste->fetch(PDO::FETCH_ASSOC);

        $statusSalvar = $desejaParticipar ? 'elegivel' : 'rascunho';
        $enviadoEm = $desejaParticipar ? date('Y-m-d H:i:s') : null;

        if ($inscricaoExistente) {
            if (!in_array($inscricaoExistente['status'], ['rascunho', 'enviada'], true)) {
                throw new Exception('Esta inscrição já está em andamento e não pode ser alterada nesta tela.');
            }

            $stmtUpdate = $pdo->prepare("
                UPDATE premiacao_inscricoes
                SET
                    categoria = ?,
                    deseja_participar = ?,
                    aceite_regulamento = ?,
                    aceite_veracidade = ?,
                    status = ?,
                    enviado_em = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmtUpdate->execute([
                $negocioPremiacao['categoria'],
                $desejaParticipar,
                $aceiteRegulamento,
                $aceiteVeracidade,
                $statusSalvar,
                $enviadoEm,
                $inscricaoExistente['id']
            ]);
        } else {
            $stmtInsert = $pdo->prepare("
                INSERT INTO premiacao_inscricoes (
                    premiacao_id,
                    negocio_id,
                    empreendedor_id,
                    categoria,
                    aceite_regulamento,
                    aceite_veracidade,
                    deseja_participar,
                    status,
                    enviado_em,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmtInsert->execute([
                $premiacaoIdAtiva,
                $negocioId,
                $_SESSION['user_id'],
                $negocioPremiacao['categoria'],
                $aceiteRegulamento,
                $aceiteVeracidade,
                $desejaParticipar,
                $statusSalvar,
                $enviadoEm
            ]);
        }

        $_SESSION['success_message'] = 'Participação na premiação salva com sucesso.';
        header('Location: /empreendedores/meus-negocios.php');
        exit;
    } catch (Throwable $e) {
        $_SESSION['errors_message'] = $e->getMessage();
        header('Location: /empreendedores/meus-negocios.php');
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT 
        n.id,
        n.nome_fantasia,
        n.categoria,
        n.etapa_atual,
        n.inscricao_completa,
        n.status_operacional,
        n.status_vitrine,
        n.publicado_vitrine,
        a.logo_negocio,
        a.imagem_destaque,
        pi.id AS premiacao_inscricao_id,
        pi.status AS premiacao_status,
        pi.aceite_regulamento,
        pi.aceite_veracidade,
        pi.deseja_participar,
        pi.enviado_em
    FROM negocios n
    LEFT JOIN negocio_apresentacao a ON a.negocio_id = n.id
    LEFT JOIN premiacao_inscricoes pi
        ON pi.negocio_id = n.id
       AND pi.premiacao_id = ?
    WHERE n.empreendedor_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$premiacaoIdAtiva, $_SESSION['user_id']]);
$negocios = $stmt->fetchAll();

$etapas = [
    1 => 'Dados do Negócio',       2 => 'Fundadores',
    3 => 'Eixo Temático',          4 => 'Conexão com os ODS',
    5 => 'Apresentação',           6 => 'Dados Financeiros',
    7 => 'Avaliação de Impacto',   8 => 'Visão de Futuro',
    9 => 'Documentação',           10 => 'Revisão e Confirmação'
];
$arquivosEtapas = [
    1 => 'etapa1_dados_negocio.php', 2 => 'etapa2_fundadores.php',
    3 => 'etapa3_eixo_tematico.php', 4 => 'etapa4_ods.php',
    5 => 'etapa5_apresentacao.php',  6 => 'etapa6_financeiro.php',
    7 => 'etapa7_impacto.php',       8 => 'etapa8_visao.php',
    9 => 'etapa9_documentacao.php',  10 => 'confirmacao.php'
];

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<?php if (!empty($_SESSION['success_message'])): ?>
  <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    <?= htmlspecialchars($_SESSION['success_message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['errors_message'])): ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?= htmlspecialchars($_SESSION['errors_message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['errors_message']); ?>
<?php endif; ?>

<?php if (isset($_GET['ok'])): ?>
  <div class="alert alert-<?= $_GET['ok'] === 'publicado' ? 'success' : 'info' ?> alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-<?= $_GET['ok'] === 'publicado' ? 'check-circle' : 'eye-slash' ?> me-2"></i>
    <?= $_GET['ok'] === 'publicado' ? 'Negócio publicado com sucesso na vitrine!' : 'Negócio ocultado da vitrine pública.' ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Título -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h1 class="emp-page-title mb-1"><i class="bi bi-briefcase me-2"></i>Meus Negócios</h1>
    <p class="emp-page-subtitle mb-0">Acompanhe e gerencie todos os seus negócios cadastrados</p>
  </div>
  <a href="/negocios/etapa1_dados_negocio.php" class="btn-emp-primary">
    <i class="bi bi-plus-lg"></i> Cadastrar Novo Negócio
  </a>
</div>

<?php if (empty($negocios)): ?>

  <!-- Estado vazio -->
  <div class="emp-card text-center py-5">
    <i class="bi bi-briefcase" style="font-size:3rem; color:#c8d4c0;"></i>
    <h5 class="mt-3 mb-1" style="color:#1E3425;">Nenhum negócio cadastrado ainda</h5>
    <p class="text-muted small mb-4">Comece agora e apresente seu negócio de impacto para o mundo.</p>
    <a href="/negocios/etapa1_dados_negocio.php" class="btn-emp-primary">
      <i class="bi bi-plus-lg me-1"></i> Cadastrar meu primeiro negócio
    </a>
  </div>

<?php else: ?>

  <div class="row g-4">
    <?php foreach ($negocios as $n): ?>

      <?php
        $etapaAtual     = (int)$n['etapa_atual'];
        $completo       = (bool)$n['inscricao_completa'];
        $encerrado      = ($n['status_operacional'] ?? '') === 'encerrado';
        $publicado      = (int)($n['publicado_vitrine'] ?? 0) === 1;
        $statusVitrine  = $n['status_vitrine'] ?? 'pendente';
        $statusPremiacao = $n['premiacao_status'] ?? null;
        $podeParticipar = $completo && $publicado && !$encerrado;

        // DEPOIS
        $vitrineBadge = match($statusVitrine) {
            'aprovado'    => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'text' => 'Aprovado',    'icon' => 'bi-check-circle-fill'],
            'em_analise'  => ['bg' => '#fff8e1', 'color' => '#f57f17', 'text' => 'Em Análise',  'icon' => 'bi-hourglass-split'],
            'indeferido'  => ['bg' => '#fdecea', 'color' => '#c62828', 'text' => 'Indeferido',  'icon' => 'bi-x-circle-fill'],
            default       => ['bg' => '#f5f5f5', 'color' => '#757575', 'text' => 'Pendente',    'icon' => 'bi-clock'],
        };

        $premiacaoBadge = badgePremiacao($statusPremiacao);
        $progresso = $completo ? 100 : min(round(($etapaAtual / 10) * 100), 95);
      ?>

      <div class="col-12 col-md-6 col-xl-4">
        <div class="emp-negocio-card">

          <!-- Capa / Imagem de destaque -->
          <div class="emp-negocio-capa">
            <?php if (!empty($n['imagem_destaque'])): ?>
              <img src="<?= htmlspecialchars($n['imagem_destaque']) ?>" alt="Capa">
            <?php elseif (!empty($n['logo_negocio'])): ?>
              <img src="<?= htmlspecialchars($n['logo_negocio']) ?>"
                   alt="Logo" style="object-fit:contain; padding:1rem; background:#f0f4ed;">
            <?php else: ?>
              <div class="emp-negocio-capa-placeholder">
                <i class="bi bi-building"></i>
              </div>
            <?php endif; ?>

            <!-- Badge vitrine sobreposta -->
            <span class="emp-negocio-vitrine-badge"
                  style="background:<?= $vitrineBadge['bg'] ?>; color:<?= $vitrineBadge['color'] ?>;">
              <i class="bi <?= $vitrineBadge['icon'] ?> me-1"></i><?= $vitrineBadge['text'] ?>
            </span>

            <?php if ($encerrado): ?>
              <span class="emp-negocio-encerrado-badge">
                <i class="bi bi-slash-circle me-1"></i> Encerrado
              </span>
            <?php endif; ?>
          </div>

          <!-- Corpo do card -->
          <div class="emp-negocio-body">

            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
              <h5 class="emp-negocio-nome"><?= htmlspecialchars($n['nome_fantasia']) ?></h5>
              <?php if ($completo && !$encerrado): ?>
                <span class="emp-badge-ativo flex-shrink-0">Completo</span>
              <?php elseif ($encerrado): ?>
                <span class="emp-badge-rascunho flex-shrink-0">Encerrado</span>
              <?php else: ?>
                <span class="emp-badge-pendente flex-shrink-0">Em andamento</span>
              <?php endif; ?>
            </div>

            <p class="emp-negocio-categoria mb-2">
              <i class="bi bi-tag me-1"></i><?= htmlspecialchars($n['categoria'] ?? '—') ?>
            </p>

            <!-- Barra de progresso -->
            <?php if (!$completo): ?>
              <div class="emp-progress-wrap mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="small" style="color:#6c8070; font-size:.75rem;">
                    <?= $etapas[$etapaAtual] ?? "Etapa $etapaAtual" ?>
                  </span>
                  <span class="small fw-bold" style="color:#1E3425; font-size:.75rem;">
                    <?= $etapaAtual ?>/10
                  </span>
                </div>
                <div class="emp-progress-bar-wrap">
                  <div class="emp-progress-bar-fill" style="width:<?= $progresso ?>%"></div>
                </div>
              </div>
            <?php else: ?>
              <div class="d-flex align-items-center gap-1 mb-3 small" style="color:#2e7d32;">
                <i class="bi bi-check-circle-fill"></i> Todas as etapas concluídas
              </div>
            <?php endif; ?>

            <!-- Bloco premiação -->
            <?php if ($premiacaoAtiva): ?>
              <div class="mb-3 p-3 rounded" style="background:#f7f9f5; border:1px solid #e6ece1;">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2 flex-wrap">
                  <div class="small fw-semibold" style="color:#1E3425;">
                    <i class="bi bi-trophy me-1"></i> Premiação
                  </div>
                  <span class="small" style="color:#6c8070;">
                    <?= htmlspecialchars($premiacaoAtiva['nome']) ?>
                  </span>
                </div>

                <?php if (!$podeParticipar): ?>
                  <div class="small text-muted">
                    Disponível após cadastro completo e publicação na vitrine.
                  </div>

                <?php elseif (!empty($statusPremiacao) && $statusPremiacao !== 'rascunho'): ?>
                  <span class="emp-negocio-vitrine-badge"
                        style="position:static; display:inline-flex; background:<?= $premiacaoBadge['bg'] ?>; color:<?= $premiacaoBadge['color'] ?>;">
                    <i class="bi bi-award me-1"></i><?= htmlspecialchars(labelStatusPremiacao($statusPremiacao)) ?>
                  </span>

                  <?php if (!empty($n['enviado_em'])): ?>
                    <div class="small text-muted mt-2">
                      Enviado em <?= date('d/m/Y H:i', strtotime($n['enviado_em'])) ?>
                    </div>
                  <?php endif; ?>

                <?php else: ?>
                  <button
                    type="button"
                    class="btn-emp-primary w-100 mt-2"
                    onclick="abrirModalPremiacao(<?= (int)$n['id'] ?>, <?= (int)($n['deseja_participar'] ?? 0) ?>, <?= (int)($n['aceite_regulamento'] ?? 0) ?>, <?= (int)($n['aceite_veracidade'] ?? 0) ?>)">
                    <i class="bi bi-trophy me-1"></i> Quero participar da Premiação
                  </button>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <!-- Ações -->
            <div class="emp-negocio-acoes">

              <?php if ($completo): ?>
                <a href="/negocios/confirmacao.php?id=<?= $n['id'] ?>" class="btn-emp-outline flex-1">
                  <i class="bi bi-card-checklist me-1"></i> Ver Revisão
                </a>

                <?php if ($publicado && !$encerrado): ?>
                  <a href="/negocio.php?id=<?= $n['id'] ?>" target="_blank" class="btn-emp-primary flex-1">
                    <i class="bi bi-eye me-1"></i> Ver na Vitrine
                  </a>
                  <button class="btn-emp-icon text-danger" title="Ocultar da Vitrine"
                          onclick="abrirModalOcultar(<?= $n['id'] ?>)">
                    <i class="bi bi-eye-slash"></i>
                  </button>
                <?php elseif ($encerrado && $statusVitrine === 'aprovado'): ?>
                  <form action="/negocios/publicar.php" method="post" class="flex-1">
                    <input type="hidden" name="negocio_id" value="<?= $n['id'] ?>">
                    <input type="hidden" name="acao" value="republicar">
                    <button type="submit" class="btn-emp-primary w-100">
                      <i class="bi bi-arrow-repeat me-1"></i> Republicar
                    </button>
                  </form>
                <?php endif; ?>

              <?php elseif ($etapaAtual >= 10): ?>
                <a href="/negocios/confirmacao.php?id=<?= $n['id'] ?>" class="btn-emp-primary flex-1">
                  <i class="bi bi-send me-1"></i> Revisão Final
                </a>

              <?php else: ?>
                <a href="/negocios/<?= $arquivosEtapas[$etapaAtual] ?? 'etapa1_dados_negocio.php' ?>?id=<?= $n['id'] ?>"
                   class="btn-emp-primary flex-1">
                  <i class="bi bi-arrow-right me-1"></i> Continuar
                </a>

                <!-- Dropdown editar etapas anteriores -->
                <div class="dropdown">
                  <button class="btn-emp-icon" type="button" data-bs-toggle="dropdown"
                          title="Editar etapa anterior">
                    <i class="bi bi-pencil-square"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end emp-dropdown">
                    <li class="px-3 py-1 emp-dropdown-role">Editar Etapa</li>
                    <?php for ($num = 1; $num <= $etapaAtual; $num++): ?>
                      <li>
                        <a class="dropdown-item emp-dropdown-item"
                           href="/negocios/editar_etapa<?= $num ?>.php?id=<?= $n['id'] ?>">
                          <i class="bi bi-pencil me-2"></i>
                          <?= $num ?>. <?= $etapas[$num] ?? "Etapa $num" ?>
                        </a>
                      </li>
                    <?php endfor; ?>
                  </ul>
                </div>
              <?php endif; ?>

            </div>
          </div>
        </div>
      </div>

    <?php endforeach; ?>
  </div>

<?php endif; ?>

<!-- Modal Premiação -->
<div class="modal fade" id="modalPremiacao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px; border:none;">
      <form method="post" id="formModalPremiacao">
        <input type="hidden" name="acao" value="salvar_inscricao_premiacao">
        <input type="hidden" name="negocio_id" id="modal_premiacao_negocio_id" value="">

        <div class="modal-header" style="border-bottom:1px solid #f0f4ed;">
          <h5 class="modal-title" style="color:#1E3425;">
            <i class="bi bi-trophy me-2" style="color:#CDDE00;"></i>Participar da Premiação
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <!-- Texto explicativo -->
          <div class="p-3 rounded mb-4" style="background:#f7f9f5; border:1px solid #e6ece1;">
            <p class="small mb-2" style="color:#1E3425; font-weight:600;">
              <i class="bi bi-info-circle me-1"></i> Sobre a Premiação Impactos Positivos
            </p>
            <p class="small text-muted mb-2">
              A Premiação Impactos Positivos reconhece negócios de impacto social e ambiental
              que estão transformando realidades. Ao se inscrever, seu negócio concorre ao
              reconhecimento público, visibilidade na vitrine nacional e ao voto popular da nossa comunidade.
            </p>
            <p class="small text-muted mb-0">
              Negócios aprovados e publicados na vitrine já estão aptos a participar. Sua inscrição será registrada imediatamente.
            </p>
          </div>

          <!-- Checkboxes de aceite -->
          <div class="form-check p-3 mb-2 rounded" style="background:#f5f7f2; border:1px solid #e8ede5;">
            <input class="form-check-input" type="checkbox"
                   name="deseja_participar"
                   id="modal_deseja_participar" value="1">
            <label class="form-check-label small fw-semibold" for="modal_deseja_participar"
                   style="color:#1E3425;">
              Desejo participar da Premiação Impactos Positivos
            </label>
          </div>

          <div class="form-check p-3 mb-2 rounded" style="background:#f5f7f2; border:1px solid #e8ede5;">
            <input class="form-check-input" type="checkbox"
                   name="aceite_regulamento"
                   id="modal_aceite_regulamento" value="1">
            <label class="form-check-label small" for="modal_aceite_regulamento">
              Li e aceito o
              <a href="https://impactospositivos.com/regulamento-do-premio/"
                 target="_blank" rel="noopener noreferrer" style="color:#1E3425; font-weight:600;">
                regulamento da Premiação
              </a>
            </label>
          </div>

          <div class="form-check p-3 rounded" style="background:#f5f7f2; border:1px solid #e8ede5;">
            <input class="form-check-input" type="checkbox"
                   name="aceite_veracidade"
                   id="modal_aceite_veracidade" value="1">
            <label class="form-check-label small" for="modal_aceite_veracidade">
              Declaro que todas as informações publicadas sobre este negócio são verdadeiras
              e de minha responsabilidade
            </label>
          </div>

        </div>

        <div class="modal-footer" style="border-top:1px solid #f0f4ed;">
          <button type="button" class="btn-emp-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn-emp-primary">
            <i class="bi bi-send me-1"></i> Enviar inscrição
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<!-- Modal Ocultar -->
<div class="modal fade" id="modalOcultar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px; border:none;">
      <form action="/negocios/publicar.php" method="post">
        <div class="modal-header" style="border-bottom:1px solid #f0f4ed;">
          <h5 class="modal-title text-danger">
            <i class="bi bi-eye-slash me-2"></i>Ocultar Negócio
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small mb-4">Escolha o motivo para remover este negócio da vitrine pública:</p>
          <input type="hidden" name="negocio_id" id="modal_ocultar_negocio_id" value="">
          <input type="hidden" name="acao" value="remover">

          <div class="form-check p-3 mb-2 rounded" style="background:#f5f7f2; border:1px solid #e8ede5;">
            <input class="form-check-input" type="radio" name="motivo" id="motivoOcultar" value="oculto" checked>
            <label class="form-check-label" for="motivoOcultar">
              <strong class="d-block">Ocultar temporariamente</strong>
              <small class="text-muted">O negócio continua em operação, mas ficará fora da vitrine por ora.</small>
            </label>
          </div>
          <div class="form-check p-3 rounded" style="background:#fff5f5; border:1px solid #ffd7d7;">
            <input class="form-check-input" type="radio" name="motivo" id="motivoEncerrado" value="encerrado">
            <label class="form-check-label text-danger" for="motivoEncerrado">
              <strong class="d-block">Este negócio foi encerrado</strong>
              <small style="color:#e57373;">As atividades foram encerradas. Os dados são mantidos no seu histórico.</small>
            </label>
          </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid #f0f4ed;">
          <button type="button" class="btn-emp-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger rounded-pill px-4">Confirmar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function abrirModalPremiacao(negocioId, desejaParticipar, aceiteRegulamento, aceiteVeracidade) {
  document.getElementById('modal_premiacao_negocio_id').value = negocioId;
  document.getElementById('modal_deseja_participar').checked   = desejaParticipar === 1;
  document.getElementById('modal_aceite_regulamento').checked  = aceiteRegulamento === 1;
  document.getElementById('modal_aceite_veracidade').checked   = aceiteVeracidade === 1;
  new bootstrap.Modal(document.getElementById('modalPremiacao')).show();
}
function abrirModalOcultar(id) {
  document.getElementById('modal_ocultar_negocio_id').value = id;
  new bootstrap.Modal(document.getElementById('modalOcultar')).show();
}

</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>