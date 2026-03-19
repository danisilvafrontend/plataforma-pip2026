<?php
// /public_html/admin/negocios.php
session_start();
require_once __DIR__ . '/../app/helpers/auth.php';

// só permite admin, superadmin ou juri
require_admin_login();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config/db.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // --- FILTROS ---
    $where = [];
    $params = [];

    // Filtro por Nome Fantasia
    $filtro_nome = $_GET['nome'] ?? '';
    if (!empty($filtro_nome)) {
        $where[] = "n.nome_fantasia LIKE ?";
        $params[] = "%" . $filtro_nome . "%";
    }
    // Filtro por Categoria
    $filtro_categoria = $_GET['categoria'] ?? '';
    if (!empty($filtro_categoria)) {
        $where[] = "n.categoria = ?";
        $params[] = $filtro_categoria;
    }

        // Filtro por Status
    $filtro_status = $_GET['status'] ?? '';
    if ($filtro_status === 'encerrado') {
        $where[] = "n.status_operacional = 'encerrado'";
    } elseif ($filtro_status === 'concluido') {
        $where[] = "n.inscricao_completa = 1 AND (n.status_operacional != 'encerrado' OR n.status_operacional IS NULL)";
    } elseif ($filtro_status === 'andamento') {
        $where[] = "(n.inscricao_completa IS NULL OR n.inscricao_completa = 0) AND (n.status_operacional != 'encerrado' OR n.status_operacional IS NULL)";
    } elseif ($filtro_status === 'analise') {
        $where[] = "n.status_vitrine = 'em_analise'";
    } elseif ($filtro_status === 'aprovado') {
        $where[] = "n.status_vitrine = 'aprovado'";
    }


    // Monta a Query
      // Monta a Query
$sql = "SELECT n.id, n.nome_fantasia, n.categoria, n.etapa_atual, n.inscricao_completa, 
        n.status_operacional, n.status_vitrine, n.publicado_vitrine, 
        CONCAT(TRIM(e.nome), ' ', TRIM(e.sobrenome)) AS empreendedor, 
        s.score_impacto, s.score_investimento, s.score_escala, s.score_geral 
        FROM negocios n 
        JOIN empreendedores e ON n.empreendedor_id = e.id 
        LEFT JOIN scores_negocios s ON n.id = s.negocio_id ";


        if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    // SISTEMA DE ORDENAÇÃO DINÂMICA
    $coluna_ordem = $_GET['ordem'] ?? 'created_at'; // Coluna padrão
    $direcao_ordem = $_GET['dir'] ?? 'DESC';        // Direção padrão

    // Lista de colunas permitidas (Segurança contra SQL Injection)
    $colunas_permitidas = [
        'created_at' => 'n.created_at',
        'escala' => 's.score_escala',
        'investimento' => 's.score_investimento',
        'impacto' => 's.score_impacto',
        'geral' => 's.score_geral'
    ];
    $direcoes_permitidas = ['ASC', 'DESC'];

    // Validar se o que veio pela URL é permitido
    $campo_sql = $colunas_permitidas[$coluna_ordem] ?? 'n.created_at';
    $dir_sql = in_array(strtoupper($direcao_ordem), $direcoes_permitidas) ? strtoupper($direcao_ordem) : 'DESC';

    // Aplica a ordem final
    $sql .= " ORDER BY {$campo_sql} {$dir_sql}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $negocios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // FUNÇÃO PARA CRIAR LINKS DE ORDENAÇÃO SEM PERDER OS FILTROS
    function linkOrdenacao($coluna) {
        $get = $_GET; // Copia os parâmetros atuais da URL (filtros)
        $dir_atual = $get['dir'] ?? 'DESC';
        $col_atual = $get['ordem'] ?? 'created_at';

        // Se clicou na mesma coluna, inverte a ordem. Se não, começa DESC
        $get['dir'] = ($col_atual === $coluna && $dir_atual === 'DESC') ? 'ASC' : 'DESC';
        $get['ordem'] = $coluna;

        return '?' . http_build_query($get);
    }

    // Função para mostrar setinha (Cima/Baixo) na tabela se a coluna estiver ativa
    function iconeOrdenacao($coluna) {
        $dir_atual = $_GET['dir'] ?? 'DESC';
        $col_atual = $_GET['ordem'] ?? 'created_at';
        if ($col_atual === $coluna) {
            return $dir_atual === 'ASC' ? '<i class="bi bi-caret-up-fill ms-1"></i>' : '<i class="bi bi-caret-down-fill ms-1"></i>';
        }
        return ''; // Se não estiver ordenado por essa coluna, não mostra seta
    }


    // Lista de Categorias para o Select (Pode vir do banco ou ser fixa)
    // Se quiser pegar do banco: SELECT DISTINCT categoria FROM negocios
    $stmtCat = $pdo->query("SELECT DISTINCT categoria FROM negocios WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");
    $categorias_disponiveis = $stmtCat->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}

// --- QUERY DE TOTAIS GERAIS (Para o Card) ---
// Conta total, concluídos, andamento e encerrados
$sqlTotais = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status_operacional = 'encerrado' THEN 1 ELSE 0 END) as encerrados,
                SUM(CASE WHEN inscricao_completa = 1 AND (status_operacional != 'encerrado' OR status_operacional IS NULL) THEN 1 ELSE 0 END) as concluidos,
                SUM(CASE WHEN (inscricao_completa = 0 OR inscricao_completa IS NULL) AND (status_operacional != 'encerrado' OR status_operacional IS NULL) THEN 1 ELSE 0 END) as andamento
                FROM negocios";
$stmtTotais = $pdo->query($sqlTotais);
$totais = $stmtTotais->fetch(PDO::FETCH_ASSOC);

// Garante que não retorne NULL se tabela vazia
$totalGeral = $totais['total'] ?? 0;
$totalEncerrados = $totais['encerrados'] ?? 0;
$totalConcluidos = $totais['concluidos'] ?? 0;
$totalAndamento = $totais['andamento'] ?? 0;


// Etapas legíveis
$etapas = [
    'dados_gerais' => 'Dados Gerais',
    'contatos'     => 'Contatos',
    'endereco'     => 'Endereço',
    'midias'       => 'Mídias',
    'pitch'        => 'Pitch',
    'impacto'      => 'Impacto',
    'demografia'   => 'Demografia',
    'finalizado'   => 'Finalizado'
];

include __DIR__ . '/../app/views/admin/header.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Negócios Cadastrados</h1>
        <div>
            <a href="/admin/recalcular_scores.php" class="btn btn-success btn-sm me-2">
                <i class="bi bi-arrow-repeat"></i> Recalcular Scores
            </a>
            <!-- Botão Novo -->
            <a href="/admin/enviar_email_negocios_pendentes.php" class="btn btn-warning btn-sm me-2">
                <i class="bi bi-envelope-exclamation"></i> Notificar Pendentes
            </a>
            
            <a href="/admin/dashboard.php" class="btn btn-secondary btn-sm">Voltar</a>
        </div>
    </div>


    <p class="mb-4">Acompanhe o andamento de todos os negócios inscritos na plataforma.</p>

    <div class="row mb-4">
    <!-- Coluna do Card: Ocupa 5/12 em telas médias/grandes -->
    <div class="col-12 col-md-5"> 
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total de Negócios
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $totalGeral ?>
                        </div>
                        
                        <div class="mt-2 small d-flex gap-3 flex-wrap">
                            <span class="text-success">
                                <i class="bi bi-check-circle-fill"></i> <?= $totalConcluidos ?> Concluídos
                            </span>
                            <span class="text-warning text-dark">
                                <i class="bi bi-hourglass-split"></i> <?= $totalAndamento ?> Em andamento
                            </span>
                            <span class="text-danger">
                                <i class="bi bi-x-circle-fill"></i> <?= $totalEncerrados ?> Encerrados
                            </span>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <i class="bi bi-briefcase fa-2x text-gray-300" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Coluna do Botão: Ocupa 7/12 (5+7 = 12). d-flex e justify-content-end empurram o botão pra direita -->
    <div class="col-12 col-md-7 d-flex align-items-center justify-content-md-end justify-content-center mt-3 mt-md-0">
        <a href="relatorios_negocios.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-bar-chart-fill text-white-50"></i> Ver Relatórios Gráficos Detalhados
        </a>
    </div>
</div>


    <!-- FILTROS -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body py-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="nome" class="form-label fw-bold small">Nome do Negócio</label>
                    <input type="text" name="nome" id="nome" 
                        value="<?= htmlspecialchars($filtro_nome) ?>" 
                        class="form-control form-control-sm" 
                        placeholder="Digite o nome">
                </div>
                <div class="col-md-3">
                    <label for="categoria" class="form-label fw-bold small">Categoria</label>
                    <select name="categoria" id="categoria" class="form-select form-select-sm">
                        <option value="">Todas as categorias</option>
                        <?php foreach ($categorias_disponiveis as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $filtro_categoria === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label fw-bold small">Status</label>
                                        <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">Todos os status</option>
                        <option value="concluido" <?= $filtro_status === 'concluido' ? 'selected' : '' ?>>Concluído</option>
                        <option value="andamento" <?= $filtro_status === 'andamento' ? 'selected' : '' ?>>Em andamento</option>
                        <option value="encerrado" <?= $filtro_status === 'encerrado' ? 'selected' : '' ?>>Encerrados</option>
                    </select>

                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filtrar</button>
                </div>
                
                <?php if(!empty($filtro_categoria) || !empty($filtro_status) || !empty($filtro_nome)): ?>
                    <div class="col-md-2">
                        <a href="negocios.php" class="btn btn-outline-secondary btn-sm w-100">Limpar</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nome Fantasia</th>
                            <th>Categoria</th>
                            <th>Empreendedor</th>
                            <th>Etapa Atual</th>
                            <th>
                                <a href="<?= linkOrdenacao('escala') ?>" class="text-dark text-decoration-none" title="Ordenar por Potencial de Escala">
                                    <i class="bi bi-graph-up-arrow text-primary me-1"></i><?= iconeOrdenacao('escala') ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= linkOrdenacao('investimento') ?>" class="text-dark text-decoration-none" title="Ordenar por Investimento">
                                    <i class="bi bi-currency-dollar text-success me-1"></i><?= iconeOrdenacao('investimento') ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= linkOrdenacao('impacto') ?>" class="text-dark text-decoration-none" title="Ordenar por Impacto">
                                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i><?= iconeOrdenacao('impacto') ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= linkOrdenacao('geral') ?>" class="text-dark text-decoration-none" title="Ordenar por Score Geral">
                                    <i class="bi bi-star-fill text-dark me-1"></i><?= iconeOrdenacao('geral') ?>
                                </a>
                            </th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($negocios)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Nenhum negócio encontrado com os filtros selecionados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($negocios as $n): ?>
                                <tr>
                                    <td><?= htmlspecialchars($n['id']) ?></td>
                                    <td><?= htmlspecialchars($n['nome_fantasia']) ?></td>
                                    <td><?= htmlspecialchars($n['categoria']) ?></td>
                                    <td><?= htmlspecialchars($n['empreendedor']) ?></td>
                                    <td>
                                        <?php if (!empty($n['inscricao_completa'])): ?>
                                            <span><i class="bi bi-check-all"></i> Todas as etapas concluídas</span>
                                        <?php else: ?>
                                            <span class="text-primary">
                                                Etapa: <strong><?= isset($etapas[$n['etapa_atual']]) ? $etapas[$n['etapa_atual']] : ($n['etapa_atual'] ?: 'Início') ?></strong>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $n['score_escala'] ?? '-' ?></td>
                                    <td><?= $n['score_investimento'] ?? '-' ?></td>
                                    <td><?= $n['score_impacto'] ?? '-' ?></td>
                                    <td><strong><?= $n['score_geral'] ?? '-' ?></strong></td>
                                    <td class="text-center">
                                        <?php if ($n['status_operacional'] === 'encerrado'): ?>
                                            <span class="badge bg-danger">Encerrado</span>
                                        <?php elseif ($n['status_vitrine'] === 'aprovado' || $n['publicado_vitrine'] == 1): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Aprovado e Publicado</span>
                                        <?php elseif ($n['status_vitrine'] === 'em_analise'): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Aguardando Aprovação</span>
                                        <?php elseif ($n['status_vitrine'] === 'rejeitado'): ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> Rejeitado</span>
                                        <?php elseif ($n['inscricao_completa'] == 1): ?>
                                            <span class="badge bg-info text-dark">Preenchimento Concluído</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Em andamento</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <!-- Visualizar: todos -->
                                            <a href="/admin/visualizar_negocio.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-info text-white" title="Visualizar">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <!-- Desclassificar -->
                                            <?php if (is_admin() || is_superadmin()): ?>
                                                <a href="/admin/desclassificar_negocio.php?id=<?= $n['id'] ?>" 
                                                   class="btn btn-sm btn-warning"
                                                   title="Desclassificar"
                                                   onclick="return confirm('Tem certeza que deseja desclassificar este negócio da premiação?');">
                                                    <i class="bi bi-slash-circle"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Remover -->
                                            <?php if (is_superadmin()): ?>
                                                <a href="/admin/remover_negocio.php?id=<?= $n['id'] ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   title="Remover"
                                                   onclick="return confirm('ATENÇÃO: Esta ação irá remover TODOS os dados do negócio. Deseja continuar?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/admin/footer.php'; ?>
