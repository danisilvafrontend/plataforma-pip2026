<?php
session_start();
$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Verifica login
if (!isset($_SESSION['parceiro_id'])) {
    header("Location: /login.php");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];
$mensagem = '';
$tipo_msg = '';

// PROCESSAMENTO DO FORMULÁRIO (Se for POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura as seleções
    $ods = $_POST['ods'] ?? [];
    $eixos = $_POST['eixos'] ?? [];
    $maturidade = $_POST['maturidade'] ?? [];
    $setores = $_POST['setores'] ?? [];
    $perfil = $_POST['perfil'] ?? [];
    $alcance = $_POST['alcance'] ?? '';

    // Codifica os arrays simples para JSON
    $eixos_json = json_encode($eixos, JSON_UNESCAPED_UNICODE);
    $maturidade_json = json_encode($maturidade, JSON_UNESCAPED_UNICODE);
    $setores_json = json_encode($setores, JSON_UNESCAPED_UNICODE);
    $perfil_json = json_encode($perfil, JSON_UNESCAPED_UNICODE);

    try {
        $pdo->beginTransaction();

        // 1. Processa a tabela auxiliar parceiro_interesses
        $sql_int = "INSERT INTO parceiro_interesses (parceiro_id, eixos_interesse, maturidade_negocios, setores_interesse, perfil_impacto, alcance_impacto) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    eixos_interesse = VALUES(eixos_interesse),
                    maturidade_negocios = VALUES(maturidade_negocios),
                    setores_interesse = VALUES(setores_interesse),
                    perfil_impacto = VALUES(perfil_impacto),
                    alcance_impacto = VALUES(alcance_impacto)";
                    
        $stmt = $pdo->prepare($sql_int);
        $stmt->execute([$parceiro_id, $eixos_json, $maturidade_json, $setores_json, $perfil_json, $alcance]);

        // 2. Processa as ODS (Remove as antigas e insere as novas)
        $pdo->prepare("DELETE FROM parceiro_ods WHERE parceiro_id = ?")->execute([$parceiro_id]);
        
        if (!empty($ods)) {
            $sql_ods = "INSERT INTO parceiro_ods (parceiro_id, ods_id) VALUES (?, ?)";
            $stmt_ods = $pdo->prepare($sql_ods);
            foreach ($ods as $ods_id) {
                $stmt_ods->execute([$parceiro_id, $ods_id]);
            }
        }

        $pdo->commit();
        $mensagem = "Preferências e interesses atualizados com sucesso! Seu radar de conexões foi ajustado.";
        $tipo_msg = "success";

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao atualizar interesses do Parceiro: " . $e->getMessage());
        $mensagem = "Erro ao salvar seus interesses. Tente novamente mais tarde.";
        $tipo_msg = "danger";
    }
}

// BUSCA OS DADOS ATUAIS PARA PREENCHER O FORMULÁRIO
$stmt_ods = $pdo->prepare("SELECT ods_id FROM parceiro_ods WHERE parceiro_id = ?");
$stmt_ods->execute([$parceiro_id]);
$ods_salvas = $stmt_ods->fetchAll(PDO::FETCH_COLUMN) ?: [];

$todas_ods = $pdo->query("SELECT * FROM ods ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmt_int = $pdo->prepare("SELECT * FROM parceiro_interesses WHERE parceiro_id = ?");
$stmt_int->execute([$parceiro_id]);
$interesses = $stmt_int->fetch(PDO::FETCH_ASSOC) ?: [];

// Decodifica JSONs
$eixos_salvos = !empty($interesses['eixos_interesse']) ? json_decode($interesses['eixos_interesse'], true) : [];
$maturidade_salva = !empty($interesses['maturidade_negocios']) ? json_decode($interesses['maturidade_negocios'], true) : [];
$setores_salvos = !empty($interesses['setores_interesse']) ? json_decode($interesses['setores_interesse'], true) : [];
$perfil_salvo = !empty($interesses['perfil_impacto']) ? json_decode($interesses['perfil_impacto'], true) : [];

if (!is_array($eixos_salvos)) $eixos_salvos = [];
if (!is_array($maturidade_salva)) $maturidade_salva = [];
if (!is_array($setores_salvos)) $setores_salvos = [];
if (!is_array($perfil_salvo)) $perfil_salvo = [];
$alcance = $interesses['alcance_impacto'] ?? '';

include __DIR__ . '/../app/views/public/header_public.php'; 
?>


<div class="container py-5">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-lg-3 col-md-4 mb-4 mb-md-0">
            <?php include __DIR__ . '/../app/views/parceiros/sidebar.php'; ?>
        </div>

        <!-- CONTEÚDO PRINCIPAL -->
        <div class="col-lg-9 col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">Radar de Interesses</h2>
                    <p class="text-muted mb-0">Ajuste o foco do seu matchmaking na Rede de Impacto.</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i> Voltar ao Painel</a>
            </div>

            <?php if ($mensagem): ?>
                <div class="alert alert-<?= $tipo_msg ?> alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="bi <?= $tipo_msg == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2 fs-5"></i>
                    <div><?= htmlspecialchars($mensagem) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4 p-md-5">

                    <form method="POST" action="">
                        
                        <!-- BLOCO 1: ODS -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-globe me-2"></i>ODS de Interesse</h5>
                        <p class="small text-muted mb-3">Quais Objetivos de Desenvolvimento Sustentável (ODS) sua organização deseja priorizar neste momento?</p>
                        
                        <div class="row g-2 mb-4">
                            <?php foreach ($todas_ods as $ods): 
                                $checked = in_array($ods['id'], $ods_salvas) ? 'checked' : '';
                            ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-check custom-checkbox-card border rounded p-2 d-flex align-items-center bg-light">
                                        <input class="form-check-input ms-1 me-2" type="checkbox" name="ods[]" value="<?= $ods['id'] ?>" id="ods_<?= $ods['id'] ?>" <?= $checked ?>>
                                        <label class="form-check-label w-100 fw-medium m-0 d-flex align-items-center" style="cursor:pointer; font-size: 0.85rem;" for="ods_<?= $ods['id'] ?>">
                                            <?php if (!empty($ods['icone_url'])): ?>
                                                <img src="<?= htmlspecialchars($ods['icone_url']) ?>" alt="ODS <?= $ods['n_ods'] ?>" class="me-2 rounded-2" style="width: 28px; height: 28px; object-fit: cover;">
                                            <?php endif; ?>
                                            ODS <?= $ods['n_ods'] ?>: <?= htmlspecialchars($ods['nome']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- BLOCO 2: EIXOS TEMÁTICOS -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary mt-5"><i class="bi bi-diagram-3 me-2"></i>Eixos Temáticos</h5>
                        
                        <div class="row mb-4 mt-3">
                            <?php 
                            $eixos = [
                                "Meio Ambiente e Clima", "Água e Oceanos", "Biodiversidade e Florestas", 
                                "Economia Circular", "Energia Limpa", "Segurança Alimentar", 
                                "Saúde e Bem-Estar", "Educação", "Igualdade de Gênero", 
                                "Equidade Racial", "Trabalho e Renda", "Cidades Sustentáveis", 
                                "Inovação e Tecnologia", "Inclusão Social", "Governança e Transparência", 
                                "Parcerias e Investimento Social"
                            ];
                            foreach ($eixos as $eixo): 
                                $checked = in_array($eixo, $eixos_salvos) ? 'checked' : '';
                            ?>
                                <div class="col-md-4 col-sm-6 mb-2">
                                    <div class="form-check custom-checkbox-card border rounded p-2 px-3">
                                        <input class="form-check-input mt-1" type="checkbox" name="eixos[]" value="<?= htmlspecialchars($eixo) ?>" id="eixo_<?= md5($eixo) ?>" <?= $checked ?>>
                                        <label class="form-check-label w-100 ms-1" style="cursor:pointer; font-size: 0.9rem;" for="eixo_<?= md5($eixo) ?>"><?= $eixo ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- BLOCO 3: MATURIDADE -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary mt-5"><i class="bi bi-graph-up-arrow me-2"></i>Maturidade dos Negócios</h5>
                        
                        <div class="row mb-4 mt-3">
                            <?php 
                            $maturidades = [
                                "Ideação (começando agora)" => "Ideação",
                                "Validação (modelo sendo testado)" => "Validação",
                                "Crescimento (já operando e expandindo)" => "Crescimento",
                                "Escala (impacto consolidado e ampliando alcance)" => "Escala"
                            ];
                            foreach ($maturidades as $label => $val): 
                                $checked = in_array($val, $maturidade_salva) ? 'checked' : '';
                            ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check custom-checkbox-card border rounded p-2 px-3">
                                        <input class="form-check-input mt-1" type="checkbox" name="maturidade[]" value="<?= htmlspecialchars($val) ?>" id="mat_<?= $val ?>" <?= $checked ?>>
                                        <label class="form-check-label w-100 ms-1" style="cursor:pointer;" for="mat_<?= $val ?>"><?= $label ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- BLOCO 4 E 5: SETORES E PERFIL -->
                        <div class="row mt-5">
                            <div class="col-md-6 mb-4">
                                <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-buildings me-2"></i>Setores / Indústrias</h5>
                                <div class="p-3 border rounded bg-light">
                                    <?php 
                                    $setores = ["Tecnologia", "Agronegócio sustentável", "Saúde", "Educação", "Finanças de impacto", "Energia", "Moda sustentável", "Alimentação", "Construção civil", "Cultura", "ESG corporativo", "Startups", "Negócios sociais", "Cooperativas", "ONGs"];
                                    foreach ($setores as $setor): 
                                        $checked = in_array($setor, $setores_salvos) ? 'checked' : '';
                                    ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="setores[]" value="<?= htmlspecialchars($setor) ?>" id="set_<?= md5($setor) ?>" <?= $checked ?>>
                                            <label class="form-check-label text-dark" for="set_<?= md5($setor) ?>"><?= $setor ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-star me-2"></i>Perfil de Impacto</h5>
                                <div class="p-3 border rounded bg-light">
                                    <?php 
                                    $perfis = ["Social", "Ambiental", "Social + Ambiental", "Inovação tecnológica", "Base comunitária", "Liderado por mulheres", "Liderado por jovens", "Impacto regional / local", "Impacto global"];
                                    foreach ($perfis as $perfil): 
                                        $checked = in_array($perfil, $perfil_salvo) ? 'checked' : '';
                                    ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="perfil[]" value="<?= htmlspecialchars($perfil) ?>" id="perf_<?= md5($perfil) ?>" <?= $checked ?>>
                                            <label class="form-check-label text-dark" for="perf_<?= md5($perfil) ?>"><?= $perfil ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- BLOCO 6: ALCANCE -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary mt-4"><i class="bi bi-geo-alt me-2"></i>Alcance do Impacto</h5>
                        <div class="row mb-4 mt-3">
                            <?php 
                            $alcance_opcoes = ['local' => 'Local', 'nacional' => 'Nacional', 'global' => 'Global', 'todos' => 'Todos os níveis'];
                            foreach ($alcance_opcoes as $val => $label): 
                            ?>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="form-check custom-radio-card border rounded p-2 text-center">
                                        <input class="form-check-input d-none" type="radio" name="alcance" value="<?= $val ?>" id="alc_<?= $val ?>" <?= ($alcance === $val) ? 'checked' : '' ?> required>
                                        <label class="form-check-label w-100 fw-medium m-0 py-1" style="cursor:pointer;" for="alc_<?= $val ?>">
                                            <?= $label ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-5 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold"><i class="bi bi-floppy me-2"></i> Atualizar Radar</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>


<style>
/* Estilos visuais para botões interativos */
.custom-radio-card { transition: all 0.2s; background: #fff; }
.custom-radio-card input:checked + label { color: #0d6efd; font-weight: bold !important; }
.custom-radio-card:has(input:checked) { border-color: #0d6efd !important; background-color: #e9ecef; }
.custom-checkbox-card { transition: all 0.2s; }
.custom-checkbox-card:hover { border-color: #0d6efd !important; background-color: #f8f9fa; }
.custom-checkbox-card input:checked + label { color: #0d6efd; }
.custom-checkbox-card:has(input:checked) { border-color: #0d6efd !important; background-color: #e9ecef !important; }
</style>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
