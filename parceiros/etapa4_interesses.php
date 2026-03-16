<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

if (!isset($_SESSION['parceiro_id'])) {
    header("Location: login.php?msg=login_necessario");
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];

// 1. Busca as ODS selecionadas
$stmt_ods = $pdo->prepare("SELECT ods_id FROM parceiro_ods WHERE parceiro_id = ?");
$stmt_ods->execute([$parceiro_id]);
$ods_salvas = $stmt_ods->fetchAll(PDO::FETCH_COLUMN) ?: [];

// 2. Busca todas as ODS do banco para renderizar na tela
$todas_ods = $pdo->query("SELECT * FROM ods ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. Busca os outros interesses na tabela parceiro_interesses
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
$orcamento_anual = $interesses['orcamento_anual'] ?? '';
$tipo_relacionamento = $interesses['tipo_relacionamento'] ?? '';
$horizonte_engajamento = $interesses['horizonte_engajamento'] ?? '';

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="mb-4">
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span class="fw-bold text-primary">Etapa 4: Perfil de Impacto</span>
                    <span>4 de 6</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 66%;" aria-valuenow="66" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold text-dark mb-1">Mapeamento de Interesses</h3>
                    <p class="text-muted mb-4">Essas respostas nos ajudarão a conectar sua organização com os negócios de impacto e oportunidades mais adequadas para o seu perfil.</p>

                    <form method="POST" action="processar_etapa4.php">

                        <!-- BLOCO 1: ODS -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-2">ODS de Interesse</h5>
                        <p class="small text-muted mb-3">Quais Objetivos de Desenvolvimento Sustentável (ODS) você mais se identifica ou gostaria de acompanhar?</p>
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
                                            ODS <?= $ods['n_ods'] ?> - <?= htmlspecialchars($ods['nome']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- BLOCO NOVO A: CAPACIDADE FINANCEIRA -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">1. Qual o orçamento anual estimado para impacto na plataforma?</h5>
                        <p class="small text-muted mb-3">Isso permite um matchmaking mais assertivo com negócios que buscam investimentos compatíveis.</p>
                        <div class="row mb-4">
                            <?php 
                            $opcoes_orcamento = [
                                'Ate 100k' => 'Até R$ 100 mil',
                                '100k a 500k' => 'R$ 100 mil – R$ 500 mil',
                                '500k a 2M' => 'R$ 500 mil – R$ 2 milhões',
                                'Acima de 2M' => 'Acima de R$ 2 milhões',
                                'Sem aporte' => 'Não envolve aporte financeiro'
                            ];
                            foreach ($opcoes_orcamento as $val => $label): 
                            ?>
                                <div class="col-md-4 col-6 mb-2">
                                    <div class="form-check custom-radio-card border rounded p-2 text-center">
                                        <input class="form-check-input d-none" type="radio" name="orcamento_anual" value="<?= $val ?>" id="orc_<?= md5($val) ?>" <?= ($orcamento_anual === $val) ? 'checked' : '' ?> required>
                                        <label class="form-check-label w-100 fw-medium m-0 py-1" style="cursor:pointer; font-size: 0.9rem;" for="orc_<?= md5($val) ?>">
                                            <?= $label ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- BLOCO NOVO B: TIPO DE RELACIONAMENTO -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">2. Qual tipo de relacionamento você prefere com os negócios?</h5>
                        <p class="small text-muted mb-3">Define o modelo de conexão desejado pela sua instituição.</p>
                        <div class="row mb-4">
                            <?php 
                            $opcoes_relacionamento = [
                                'Varios menores' => 'Apoiar vários negócios menores',
                                'Poucos estrategicos' => 'Apoiar poucos negócios estratégicos',
                                'Programa estruturado' => 'Construir programa estruturado (ex: Impact Chain)',
                                'Investimento direto' => 'Investimento direto com participação societária',
                                'Explorando' => 'Ainda explorando possibilidades'
                            ];
                            foreach ($opcoes_relacionamento as $val => $label): 
                            ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check custom-radio-card border rounded p-2 px-3 text-start">
                                        <input class="form-check-input d-none" type="radio" name="tipo_relacionamento" value="<?= $val ?>" id="rel_<?= md5($val) ?>" <?= ($tipo_relacionamento === $val) ? 'checked' : '' ?> required>
                                        <label class="form-check-label w-100 fw-medium m-0 py-1" style="cursor:pointer;" for="rel_<?= md5($val) ?>">
                                            <?= $label ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- BLOCO NOVO C: HORIZONTE DE ENGAJAMENTO -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">3. Qual o horizonte de engajamento desejado?</h5>
                        <p class="small text-muted mb-3">Evita conectar demandas de curto prazo com quem busca apoio estrutural longo.</p>
                        <div class="row mb-4">
                            <?php 
                            $opcoes_horizonte = [
                                'Pontual' => 'Pontual (até 6 meses)',
                                'Medio' => 'Médio prazo (1 ano)',
                                'Longo' => 'Longo prazo (2+ anos)',
                                'Projeto' => 'Projeto específico'
                            ];
                            foreach ($opcoes_horizonte as $val => $label): 
                            ?>
                                <div class="col-md-3 col-6 mb-2">
                                    <div class="form-check custom-radio-card border rounded p-2 text-center">
                                        <input class="form-check-input d-none" type="radio" name="horizonte_engajamento" value="<?= $val ?>" id="hor_<?= md5($val) ?>" <?= ($horizonte_engajamento === $val) ? 'checked' : '' ?> required>
                                        <label class="form-check-label w-100 fw-medium m-0 py-1" style="cursor:pointer; font-size: 0.9rem;" for="hor_<?= md5($val) ?>">
                                            <?= $label ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- BLOCO 2: EIXOS TEMÁTICOS -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">Eixos Temáticos Adicionais</h5>
                        <p class="small text-muted mb-3">Quais temas mais despertam seu interesse, além das ODS?</p>
                        <div class="row mb-4">
                            <?php 
                            $eixos = [
                                'Meio Ambiente e Clima', 'Água e Oceanos', 'Biodiversidade e Florestas',
                                'Economia Circular', 'Energia Limpa', 'Segurança Alimentar', 'Saúde e Bem-Estar',
                                'Educação', 'Igualdade de Gênero', 'Equidade Racial', 'Trabalho e Renda',
                                'Cidades Sustentáveis', 'Inovação e Tecnologia', 'Inclusão Social',
                                'Governança e Transparência', 'Parcerias e Investimento Social'
                            ];
                            foreach ($eixos as $eixo): 
                                $checked = in_array($eixo, $eixos_salvos) ? 'checked' : '';
                            ?>
                                <div class="col-md-4 col-sm-6 mb-2">
                                    <div class="form-check custom-checkbox-card border rounded p-2 px-3">
                                        <input class="form-check-input mt-1" type="checkbox" name="eixos[]" value="<?= htmlspecialchars($eixo) ?>" id="eixo_<?= md5($eixo) ?>" <?= $checked ?>>
                                        <label class="form-check-label w-100 ms-1" style="cursor:pointer; font-size: 0.9rem;" for="eixo_<?= md5($eixo) ?>">
                                            <?= $eixo ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- BLOCO 3: MATURIDADE -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">Maturidade dos Negócios</h5>
                        <p class="small text-muted mb-3">Você prefere acompanhar negócios em qual estágio?</p>
                        <div class="row mb-4">
                            <?php 
                            $maturidades = [
                                'Ideação' => 'Ideação (começando agora)',
                                'Operação' => 'Operação (modelo sendo testado)',
                                'Tração/Escala' => 'Tração/Escala (já operando e expandindo)',
                                'Dinamizador' => 'Dinamizador (impacto consolidado e ampliando alcance)'
                            ];
                            foreach ($maturidades as $val => $label): 
                                $checked = in_array($val, $maturidade_salva) ? 'checked' : '';
                            ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check custom-checkbox-card border rounded p-2 px-3">
                                        <input class="form-check-input mt-1" type="checkbox" name="maturidade[]" value="<?= htmlspecialchars($val) ?>" id="mat_<?= $val ?>" <?= $checked ?>>
                                        <label class="form-check-label w-100 ms-1" style="cursor:pointer;" for="mat_<?= $val ?>">
                                            <?= $label ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- BLOCO 4 E 5: SETORES E PERFIL -->
                        <div class="row pt-3">
                            <div class="col-md-6 mb-4">
                                <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Setores / Indústrias</h5>
                                <?php 
                                $setores = ['Tecnologia', 'Agronegócio sustentável', 'Saúde', 'Educação', 'Finanças de impacto', 'Energia', 'Moda sustentável', 'Alimentação', 'Construção civil', 'Cultura', 'ESG corporativo', 'Startups', 'Negócios sociais', 'Cooperativas', 'ONGs'];
                                foreach ($setores as $setor): 
                                    $checked = in_array($setor, $setores_salvos) ? 'checked' : '';
                                ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="setores[]" value="<?= htmlspecialchars($setor) ?>" id="set_<?= md5($setor) ?>" <?= $checked ?>>
                                        <label class="form-check-label text-muted" for="set_<?= md5($setor) ?>">
                                            <?= $setor ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="col-md-6 mb-4">
                                <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Perfil de Impacto Desejado</h5>
                                <?php 
                                $perfis = ['Social', 'Ambiental', 'Social / Ambiental', 'Inovação tecnológica', 'Base comunitária', 'Liderado por mulheres', 'Liderado por jovens', 'Impacto regional/local', 'Impacto global'];
                                foreach ($perfis as $perfil): 
                                    $checked = in_array($perfil, $perfil_salvo) ? 'checked' : '';
                                ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="perfil[]" value="<?= htmlspecialchars($perfil) ?>" id="perf_<?= md5($perfil) ?>" <?= $checked ?>>
                                        <label class="form-check-label text-muted" for="perf_<?= md5($perfil) ?>">
                                            <?= $perfil ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- BLOCO 6: ALCANCE -->
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary pt-3">Alcance do Impacto</h5>
                        <p class="small text-muted mb-3">Você prefere apoiar causas locais, nacionais ou globais?</p>
                        <div class="row mb-4">
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

                        <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                            <a href="etapa3_combinado.php" class="btn btn-outline-secondary btn-lg fw-bold"><i class="bi bi-arrow-left me-2"></i> Voltar</a>
                            <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold">Salvar e Avançar <i class="bi bi-arrow-right ms-2"></i></button>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* O mesmo CSS da etapa anterior para manter o padrão visual de cards clicáveis */
.custom-radio-card { transition: all 0.2s; background: #fff; }
.custom-radio-card input:checked + label { color: #0d6efd; font-weight: bold !important; }
.custom-radio-card:has(input:checked) { border-color: #0d6efd !important; background-color: #e9ecef; }

.custom-checkbox-card { transition: all 0.2s; }
.custom-checkbox-card:hover { border-color: #0d6efd !important; background-color: #f8f9fa; }
.custom-checkbox-card input:checked + label { color: #0d6efd; }
.custom-checkbox-card:has(input:checked) { border-color: #0d6efd !important; background-color: #e9ecef !important; }
</style>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>
