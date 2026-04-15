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
$sucesso = '';
$erro = '';

// 1. Garante que o parceiro já tem um registro na tabela de perfil
$stmt = $pdo->prepare("SELECT id FROM parceiros_perfil WHERE parceiro_id = ?");
$stmt->execute([$parceiro_id]);
if (!$stmt->fetch()) {
    $pdo->prepare("INSERT INTO parceiros_perfil (parceiro_id) VALUES (?)")->execute([$parceiro_id]);
}

// 2. Busca dados auxiliares principais
$stmtMain = $pdo->prepare("
    SELECT 
        p.nome_fantasia,
        pp.logo_url AS logo_perfil_url,
        c.logo_url AS logo_contrato_url
    FROM parceiros p
    LEFT JOIN parceiros_perfil pp ON p.id = pp.parceiro_id
    LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id
    WHERE p.id = ?
");
$stmtMain->execute([$parceiro_id]);
$dadosMain = $stmtMain->fetch(PDO::FETCH_ASSOC);

// 3. Processa o Formulário de Salvamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slogan = trim($_POST['slogan'] ?? '');
    $setor_atuacao = trim($_POST['setor_atuacao'] ?? '');
    $descricao_institucional = trim($_POST['descricao_institucional'] ?? '');
    $ano_fundacao = !empty($_POST['ano_fundacao']) ? (int)$_POST['ano_fundacao'] : null;
    $porte_empresa = trim($_POST['porte_empresa'] ?? '');
    $compromisso_impacto = trim($_POST['compromisso_impacto'] ?? '');

    $tags_str = trim($_POST['tags_especialidades'] ?? '');
    $tags_array = array_filter(array_map('trim', explode(',', $tags_str)));
    $tags_json = json_encode(array_values($tags_array), JSON_UNESCAPED_UNICODE);

    $email_publico = trim($_POST['email_publico'] ?? '');
    $whatsapp_publico = trim($_POST['whatsapp_publico'] ?? '');
    $linkedin_url = trim($_POST['linkedin_url'] ?? '');
    $instagram_url = trim($_POST['instagram_url'] ?? '');

    $perfil_publicado = isset($_POST['perfil_publicado']) ? 1 : 0;

    // Mantém arquivos atuais se não enviar novos
    $imagem_capa_url = $_POST['imagem_capa_atual'] ?? '';
    $logo_url = $_POST['logo_atual'] ?? '';

    $upload_dir = __DIR__ . '/../uploads/parceiros/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
    $max_capa = 10 * 1024 * 1024;
    $max_logo = 5 * 1024 * 1024;

    // Upload da capa
    if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['imagem_capa']['tmp_name'];
        $file_name = $_FILES['imagem_capa']['name'];
        $file_size = $_FILES['imagem_capa']['size'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_size > $max_capa) {
            $erro = "A imagem de capa deve ter no máximo 10MB.";
        } elseif (!in_array($ext, $allowed_exts, true)) {
            $erro = "Formato de imagem da capa inválido. Use JPG, PNG ou WEBP.";
        } else {
            $new_filename = 'capa_parceiro_' . $parceiro_id . '_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $destination)) {
                $imagem_capa_url = '/uploads/parceiros/' . $new_filename;
            } else {
                $erro = "Falha ao salvar a imagem de capa no servidor.";
            }
        }
    }

    // Upload do logo
    if (empty($erro) && isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['logo']['tmp_name'];
        $file_name = $_FILES['logo']['name'];
        $file_size = $_FILES['logo']['size'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_size > $max_logo) {
            $erro = "O logotipo deve ter no máximo 5MB.";
        } elseif (!in_array($ext, $allowed_exts, true)) {
            $erro = "Formato de logotipo inválido. Use JPG, PNG ou WEBP.";
        } else {
            $new_filename = 'logo_parceiro_' . $parceiro_id . '_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $destination)) {
                $logo_url = '/uploads/parceiros/' . $new_filename;
            } else {
                $erro = "Falha ao salvar o logotipo no servidor.";
            }
        }
    }

    if (empty($erro)) {
        try {
            $sql = "UPDATE parceiros_perfil SET 
                        logo_url = ?,
                        imagem_capa_url = ?, 
                        slogan = ?, 
                        setor_atuacao = ?, 
                        descricao_institucional = ?, 
                        ano_fundacao = ?, 
                        porte_empresa = ?, 
                        compromisso_impacto = ?, 
                        tags_especialidades = ?, 
                        email_publico = ?, 
                        whatsapp_publico = ?, 
                        linkedin_url = ?, 
                        instagram_url = ?, 
                        perfil_publicado = ?
                    WHERE parceiro_id = ?";

            $stmtUpdate = $pdo->prepare($sql);
            $stmtUpdate->execute([
                $logo_url,
                $imagem_capa_url,
                $slogan,
                $setor_atuacao,
                $descricao_institucional,
                $ano_fundacao,
                $porte_empresa,
                $compromisso_impacto,
                $tags_json,
                $email_publico,
                $whatsapp_publico,
                $linkedin_url,
                $instagram_url,
                $perfil_publicado,
                $parceiro_id
            ]);

            $sucesso = "Perfil atualizado com sucesso!";

        } catch (Exception $e) {
            $erro = "Erro ao salvar os dados: " . $e->getMessage();
        }
    }
}

// 4. Busca os dados atuais para preencher o formulário
$stmt = $pdo->prepare("SELECT * FROM parceiros_perfil WHERE parceiro_id = ?");
$stmt->execute([$parceiro_id]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

// Converte JSON de tags para string
$tags_atuais = '';
if (!empty($perfil['tags_especialidades'])) {
    $decodificado = json_decode($perfil['tags_especialidades'], true);
    if (is_array($decodificado)) {
        $tags_atuais = implode(', ', $decodificado);
    }
}

// Recarrega dados principais depois do salvamento
$stmtMain = $pdo->prepare("
    SELECT 
        p.nome_fantasia,
        pp.logo_url AS logo_perfil_url,
        c.logo_url AS logo_contrato_url
    FROM parceiros p
    LEFT JOIN parceiros_perfil pp ON p.id = pp.parceiro_id
    LEFT JOIN parceiro_contrato c ON p.id = c.parceiro_id
    WHERE p.id = ?
");
$stmtMain->execute([$parceiro_id]);
$dadosMain = $stmtMain->fetch(PDO::FETCH_ASSOC);

$logo_exibicao = $dadosMain['logo_perfil_url'] ?: ($dadosMain['logo_contrato_url'] ?? '');

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar do Perfil -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-4 sticky-top" style="top: 20px;">
                <?php if (!empty($logo_exibicao)): ?>
                    <img src="<?= htmlspecialchars($logo_exibicao) ?>" alt="Logo" class="rounded-circle img-thumbnail mb-3 mx-auto" style="width: 120px; height: 120px; object-fit: cover;">
                <?php else: ?>
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 120px; height: 120px;">
                        <i class="bi bi-building fs-1 text-white"></i>
                    </div>
                <?php endif; ?>

                <h5 class="fw-bold mb-1"><?= htmlspecialchars($dadosMain['nome_fantasia'] ?? 'Parceiro') ?></h5>
                <p class="small text-muted mb-3">Parceiro Oficial</p>

                <div class="d-grid gap-2">
                    <?php if (!empty($perfil['perfil_publicado'])): ?>
                        <a href="/perfil_parceiro.php?id=<?= $parceiro_id ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye me-1"></i> Ver Perfil Público
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnVerPerfilPublico">
                            <i class="bi bi-eye-slash me-1"></i> Ver Perfil Público
                        </button>
                    <?php endif; ?>
                    <a href="dashboard.php" class="btn btn-light btn-sm text-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Voltar ao Painel
                    </a>
                </div>
            </div>
        </div>

        <!-- Formulário de Edição -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Meu Perfil Público</h2>
                    <p class="text-muted mb-0">Preencha as informações que serão exibidas na vitrine da plataforma para atrair conexões e negócios.</p>
                </div>
            </div>

            <div id="alertPerfilNaoPublicado" class="alert alert-warning alert-dismissible fade d-none" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Seu perfil público ainda não está visível na vitrine. Ative a opção <strong>Visibilidade do Perfil</strong> para liberar a visualização pública.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($sucesso) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Visibilidade -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 <?= ($perfil['perfil_publicado'] ?? 0) ? 'border-success' : 'border-secondary' ?>">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="fw-bold mb-1">Visibilidade do Perfil</h5>
                            <p class="small text-muted mb-0">Ative esta opção apenas quando seu perfil estiver completo e pronto para ser visto.</p>
                        </div>
                        <div class="form-check form-switch fs-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="perfil_publicado" name="perfil_publicado" value="1" <?= ($perfil['perfil_publicado'] ?? 0) ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>

                <!-- Identidade -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-person-badge text-primary me-2"></i> Identidade e Setor</h5>
                    </div>
                    <div class="card-body p-4">

                        <!-- Upload de Logo -->
                        <div class="mb-4 bg-light p-3 rounded border">
                            <label class="form-label fw-bold text-dark mb-2">
                                <i class="bi bi-badge-ad me-1"></i> Logotipo Público do Perfil
                            </label>

                            <?php
                            $logo_atual_form = $perfil['logo_url'] ?? ($dadosMain['logo_contrato_url'] ?? '');
                            ?>

                            <?php if (!empty($logo_atual_form)): ?>
                                <div class="mb-3 d-flex align-items-center gap-3">
                                    <img src="<?= htmlspecialchars($logo_atual_form) ?>" alt="Logo atual" class="img-thumbnail rounded-circle" style="width: 90px; height: 90px; object-fit: cover;">
                                    <div>
                                        <small class="text-muted d-block">Logo atual exibido no perfil público.</small>
                                        <small class="text-muted">Envie um novo arquivo para substituir.</small>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <input type="file" name="logo" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp">
                            <input type="hidden" name="logo_atual" value="<?= htmlspecialchars($logo_atual_form ?? '') ?>">

                            <div class="form-text mt-2">
                                <i class="bi bi-info-circle"></i> Formatos aceitos: JPG, PNG ou WEBP (Max: 5MB). Preferência por imagem quadrada.
                            </div>
                        </div>

                        <!-- Upload de Capa -->
                        <div class="mb-4 bg-light p-3 rounded border">
                            <label class="form-label fw-bold text-dark mb-2">
                                <i class="bi bi-image me-1"></i> Imagem de Capa do Perfil
                            </label>

                            <?php if (!empty($perfil['imagem_capa_url'])): ?>
                                <div class="mb-3">
                                    <div class="w-100 rounded" style="height: 120px; background-image: url('<?= htmlspecialchars($perfil['imagem_capa_url']) ?>'); background-size: cover; background-position: center;"></div>
                                    <small class="text-muted d-block mt-1">Capa atual. Envie um novo arquivo para substituir.</small>
                                </div>
                            <?php endif; ?>

                            <input type="file" name="imagem_capa" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp">
                            <input type="hidden" name="imagem_capa_atual" value="<?= htmlspecialchars($perfil['imagem_capa_url'] ?? '') ?>">

                            <div class="form-text mt-2">
                                <i class="bi bi-info-circle"></i> Formatos aceitos: JPG, PNG ou WEBP (Max: 10MB). Recomendamos proporção horizontal, como 1920x400.
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold text-muted small">Slogan / Frase de Impacto</label>
                                <input type="text" name="slogan" class="form-control" placeholder="Ex: Inovação para um futuro sustentável" value="<?= htmlspecialchars($perfil['slogan'] ?? '') ?>" maxlength="100">
                                <div class="form-text">Uma frase curta que resume o propósito da sua empresa (máx 100 caracteres).</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted small">Ano de Fundação</label>
                                <input type="number" name="ano_fundacao" class="form-control" placeholder="Ex: 2015" value="<?= htmlspecialchars($perfil['ano_fundacao'] ?? '') ?>" min="1900" max="<?= date('Y') ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted small">Porte da Empresa</label>
                                <select name="porte_empresa" class="form-select">
                                    <option value="">Selecione...</option>
                                    <option value="1-10" <?= ($perfil['porte_empresa'] ?? '') === '1-10' ? 'selected' : '' ?>>1 a 10 colaboradores</option>
                                    <option value="11-50" <?= ($perfil['porte_empresa'] ?? '') === '11-50' ? 'selected' : '' ?>>11 a 50 colaboradores</option>
                                    <option value="51-200" <?= ($perfil['porte_empresa'] ?? '') === '51-200' ? 'selected' : '' ?>>51 a 200 colaboradores</option>
                                    <option value="200+" <?= ($perfil['porte_empresa'] ?? '') === '200+' ? 'selected' : '' ?>>Mais de 200 colaboradores</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted small">Setor de Atuação</label>
                                <input type="text" name="setor_atuacao" class="form-control" placeholder="Ex: Tecnologia, Consultoria, Varejo" value="<?= htmlspecialchars($perfil['setor_atuacao'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sobre -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-file-text text-primary me-2"></i> Sobre a Organização</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-muted small">Descrição Institucional</label>
                            <textarea name="descricao_institucional" class="form-control" rows="5" placeholder="Conte um pouco da história da empresa, o que fazem, seus valores e diferenciais..."><?= htmlspecialchars($perfil['descricao_institucional'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-muted small">Nosso Compromisso com Impacto</label>
                            <textarea name="compromisso_impacto" class="form-control" rows="3" placeholder="Quais ações sociais ou ambientais sua empresa realiza internamente ou externamente?"><?= htmlspecialchars($perfil['compromisso_impacto'] ?? '') ?></textarea>
                            <div class="form-text">Isso é muito valorizado pelos empreendedores da plataforma.</div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold text-muted small">Especialidades / Soluções Oferecidas</label>
                            <input type="text" name="tags_especialidades" class="form-control" placeholder="Ex: Software B2B, Logística Reversa, Mentorias, Assessoria Jurídica" value="<?= htmlspecialchars($tags_atuais) ?>">
                            <div class="form-text">Separe as especialidades por vírgula.</div>
                        </div>
                    </div>
                </div>

                <!-- Contatos -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-link-45deg text-primary me-2"></i> Contato Público e Redes</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info border-0 small bg-info-subtle mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i> Os dados abaixo ficarão visíveis para o público. Use contatos comerciais que possam receber leads.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">E-mail Comercial Público</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email_publico" class="form-control" placeholder="contato@suaempresa.com.br" value="<?= htmlspecialchars($perfil['email_publico'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">WhatsApp Comercial</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-whatsapp text-success"></i></span>
                                    <input type="text" name="whatsapp_publico" class="form-control wpp_mask" placeholder="(00) 00000-0000" value="<?= htmlspecialchars($perfil['whatsapp_publico'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Página do LinkedIn</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-linkedin text-primary"></i></span>
                                    <input type="url" name="linkedin_url" class="form-control" placeholder="https://linkedin.com/company/suaempresa" value="<?= htmlspecialchars($perfil['linkedin_url'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Perfil do Instagram</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-instagram text-danger"></i></span>
                                    <input type="url" name="instagram_url" class="form-control" placeholder="https://instagram.com/suaempresa" value="<?= htmlspecialchars($perfil['instagram_url'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botão -->
                <div class="d-flex justify-content-end mb-5">
                    <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold shadow-sm">
                        <i class="bi bi-floppy me-2"></i> Salvar Perfil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    if (typeof jQuery !== 'undefined') {
        $('.wpp_mask').mask('(00) 00000-0000');
    }
</script>

<script>
    if (typeof jQuery !== 'undefined') {
        $('.wpp_mask').mask('(00) 00000-0000');
    }

    const btnVerPerfilPublico = document.getElementById('btnVerPerfilPublico');
    const alertPerfilNaoPublicado = document.getElementById('alertPerfilNaoPublicado');

    if (btnVerPerfilPublico && alertPerfilNaoPublicado) {
        btnVerPerfilPublico.addEventListener('click', function () {
            alertPerfilNaoPublicado.classList.remove('d-none');
            alertPerfilNaoPublicado.classList.add('show');

            window.scrollTo({
                top: alertPerfilNaoPublicado.offsetTop - 120,
                behavior: 'smooth'
            });
        });
    }
</script>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>