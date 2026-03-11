<?php
// /public_html/empreendedores/meus-negocios.php
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

// Busca todos os negócios do empreendedor logado
$stmt = $pdo->prepare("SELECT id, nome_fantasia, categoria, etapa_atual, inscricao_completa, publicado_vitrine, status_operacional 
                       FROM negocios WHERE empreendedor_id = ? ORDER BY id DESC");


$stmt->execute([$_SESSION['user_id']]);
$negocios = $stmt->fetchAll();

include __DIR__ . '/../app/views/empreendedor/header.php';

if (isset($_GET['ok']) && $_GET['ok'] === 'publicado'): ?>
    <div class="alert alert-success mt-3">
      Negócio publicado com sucesso na vitrine!
    </div>
<?php elseif (isset($_GET['ok']) && $_GET['ok'] === 'removido'): ?>
    <div class="alert alert-info mt-3">
      Negócio ocultado da vitrine pública.
    </div>
<?php endif; ?>


<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success">
    <?= htmlspecialchars($_SESSION['success_message']) ?>
  </div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['errors_message'])): ?>
  <div class="alert alert-danger">
    <?= htmlspecialchars($_SESSION['errors_message']) ?>
  </div>
  <?php unset($_SESSION['errors_message']); ?>
<?php endif; ?>

<div class="container-fluid">
  <div class="row g-3">
    <nav class="col-md-2 col-lg-2 d-md-block bg-light sidebar">
      <?php include __DIR__ . '/../app/views/empreendedor/menu_lateral.php'; ?>
    </nav>
    <main class="col-md-10 col-lg-10 ms-sm-auto px-md-6">
      <div class="row align-items-center mb-4">
        <div class="col-md-8">
          <h1 class="mb-0">Meus Negócios</h1>
          <p class="text-muted">Acompanhe o andamento da inscrição dos seus negócios e continue o cadastro de onde parou.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
          <a href="/../negocios/etapa1_dados_negocio.php" class="btn btn-success">
            + Cadastrar novo negócio
          </a>
        </div>
      </div>

      <?php if (empty($negocios)): ?>
        <div class="alert alert-info">Você ainda não cadastrou nenhum negócio.</div>
        <a href="/../negocios/etapa1_dados_negocio.php" class="btn btn-primary">Cadastrar novo negócio</a>
      <?php else: ?>
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Nome Fantasia</th>
              <th>Categoria</th>
              <th>Etapa Atual</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php 
                $etapas = [
                  1 => 'Dados do Negócio',
                  2 => 'Fundadores',
                  3 => 'Eixo Temático',
                  4 => 'Conexão com os ODS',
                  5 => 'Apresentação do Negócio',
                  6 => 'Dados Financeiro e Modelo de Receita',
                  7 => 'Avaliação de Impacto',
                  8 => 'Visão de Futuro',
                  9 => 'Revisão e Confirmação'
                ];

                $arquivosEtapas = [
                  1 => 'etapa1_dados_negocio.php',
                  2 => 'etapa2_fundadores.php',
                  3 => 'etapa3_eixo_tematico.php',
                  4 => 'etapa4_ods.php',
                  5 => 'etapa5_apresentacao.php',
                  6 => 'etapa6_financeiro.php',
                  7 => 'etapa7_impacto.php',
                  8 => 'etapa8_visao.php',
                  9 => 'confirmacao.php'
                ];
              ?>
            <?php foreach ($negocios as $n): ?>
              <tr>
                <td><?= htmlspecialchars($n['nome_fantasia']) ?></td>
                <td><?= htmlspecialchars($n['categoria']) ?></td>
                <td>
                  <!-- CORREÇÃO: Exibe texto personalizado se completo -->
                    <?php 
                    if ($n['inscricao_completa']) {
                        echo 'Todas as etapas concluídas';
                    } else {
                        echo $etapas[$n['etapa_atual']] ?? 'Não iniciado';
                    }
                    ?>
                </td>
                <td>
                    <?php if (($n['status_operacional'] ?? '') === 'encerrado'): ?>
                        <span class="badge bg-danger">Encerrado</span>
                    <?php elseif ($n['inscricao_completa']): ?>
                        <span class="badge bg-success">Completo</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Em andamento</span>
                    <?php endif; ?>
                </td>
                <td>
                  <?php if ($n['inscricao_completa']): ?>
                      <!-- Visualizar/Editar (quando já está concluído) -->
                      <a href="/negocios/confirmacao.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-info mb-1">Visualizar/Editar</a>
                      
                      <!-- Lógica de Ocultar/Publicar Vitrine -->
                      <?php if (($n['publicado_vitrine'] ?? 0) == 1): ?>
                          <a href="/negocio.php?id=<?= $n['id'] ?>" target="_blank" class="btn btn-sm btn-success ms-1 mb-1">Ver na Vitrine</a>
                          <!-- Botão de Remover -->
                          <form action="/negocios/publicar.php" method="post" class="d-inline-block ms-1 mb-1" onsubmit="return confirm('Tem certeza que deseja ocultar seu negócio da vitrine pública? Seus dados não serão perdidos.');">
                              <input type="hidden" name="negocio_id" value="<?= $n['id'] ?>">
                              <input type="hidden" name="acao" value="remover">
                              <!-- Botão que abre o modal de Remover -->
                              <button type="button" class="btn btn-sm btn-outline-danger ms-1 mb-1" title="Ocultar da Vitrine" onclick="abrirModalOcultar(<?= $n['id'] ?>)">
                                  <i class="bi bi-eye-slash"></i> Ocultar</button>

                          </form>
                      <?php else: ?>
                          <!-- Botão de Publicar -->
                          <form action="/negocios/publicar.php" method="post" class="d-inline-block ms-1 mb-1">
                              <input type="hidden" name="negocio_id" value="<?= $n['id'] ?>">
                              <input type="hidden" name="acao" value="publicar">
                              <button type="submit" class="btn btn-sm btn-success" title="Publicar na Vitrine">
                                  <i class="bi bi-globe"></i> Publicar na Vitrine
                              </button>
                          </form>
                      <?php endif; ?>

                  <?php elseif ($n['etapa_atual'] == 9): ?>
                      <!-- Revisão e Confirmação -->
                      <a href="/negocios/confirmacao.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-warning">
                          Revisão e Confirmação
                      </a>
                  <?php else: ?>
                      <!-- Botão Continuar -->
                      <a href="/negocios/<?= $arquivosEtapas[$n['etapa_atual']] ?>?id=<?= $n['id'] ?>"  
                        class="btn btn-sm btn-primary">
                        Continuar na Etapa <?= $etapas[$n['etapa_atual']] ?>
                      </a>

                      <!-- Dropdown para editar etapas anteriores (MANTIDO!) -->
                      <div class="dropdown d-inline">
                          <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                              Editar etapas anteriores
                          </button>
                          <ul class="dropdown-menu">
                              <?php for ($num = 1; $num <= $n['etapa_atual']; $num++): ?>
                                  <li>
                                      <a class="dropdown-item" href="/negocios/editar_etapa<?= $num ?>.php?id=<?= $n['id'] ?>">
                                          <?= $num ?> - <?= $etapas[$num] ?> (Editar)
                                      </a>
                                  </li>
                              <?php endfor; ?>
                          </ul>
                      </div>
                  <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </main>                           
  </div>
</div>

<!-- Modal Ocultar Negócio -->
<div class="modal fade" id="modalOcultar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/negocios/publicar.php" method="post">
          <div class="modal-header">
            <h5 class="modal-title text-danger"><i class="bi bi-eye-slash"></i> Ocultar Negócio</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <p>Você está prestes a remover este negócio da vitrine pública. Por favor, escolha a opção que melhor descreve o motivo:</p>
            
            <input type="hidden" name="negocio_id" id="modal_ocultar_negocio_id" value="">
            <input type="hidden" name="acao" value="remover">

            <div class="form-check mb-3 mt-4">
              <input class="form-check-input" type="radio" name="motivo" id="motivoOcultar" value="oculto" checked>
              <label class="form-check-label" for="motivoOcultar">
                <strong>Somente Ocultar da Vitrine</strong><br>
                <small class="text-muted">O negócio continua em operação, mas eu quero tirá-lo do ar temporariamente.</small>
              </label>
            </div>
            
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="motivo" id="motivoEncerrado" value="encerrado">
              <label class="form-check-label text-danger" for="motivoEncerrado">
                <strong>Esse negócio não existe mais</strong><br>
                <small class="text-muted">As atividades foram encerradas. Os dados serão mantidos apenas no meu histórico e para a plataforma.</small>
              </label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger">Confirmar Ação</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function abrirModalOcultar(id) {
    // Coloca o ID do negócio clicado no input hidden do modal
    document.getElementById('modal_ocultar_negocio_id').value = id;
    // Abre o modal do Bootstrap
    var myModal = new bootstrap.Modal(document.getElementById('modalOcultar'));
    myModal.show();
}
</script>

<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>