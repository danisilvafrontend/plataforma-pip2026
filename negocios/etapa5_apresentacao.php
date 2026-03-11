<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
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

// Aceita ID via GET (de meus-negocios) OU sessão
$negocio_id = (int)($_GET['id'] ?? $_SESSION['negocio_id'] ?? 0);
if ($negocio_id === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}

// Define na sessão para usar no formulário
$_SESSION['negocio_id'] = $negocio_id;

// Busca dados do negócio e empreendedor
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

// Busca fundadores já cadastrados (CORRIGIDO: usa $negocio_id)
$stmt = $pdo->prepare("SELECT * FROM negocio_fundadores WHERE negocio_id = ? ORDER BY tipo, id");
$stmt->execute([$negocio_id]);  // ✅ $negocio_id
$fundadoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/empreendedor/header.php'; ?>

<div class="container my-5">
    <h1 class="mb-4">Etapa 5 - Apresentação do Negócio</h1>

    <?php
        $etapaAtual = 5;
        include __DIR__ . '/../app/views/partials/progress.php';
        include __DIR__ . '/../app/views/partials/intro_text_apresentacao_negocios.php';
    ?>

    <?php if (!empty($_SESSION['errors_etapa5'])): ?>
        <div class="alert alert-danger">
            <h5>Erros encontrados:</h5>
            <ul class="mb-0">
                <?php foreach ($_SESSION['errors_etapa5'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['errors_etapa5']); ?>
    <?php endif; ?>



    <form action="/negocios/processar_etapa5.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="negocio_id" value="<?= $negocio_id ?>">
    <input type="hidden" name="modo" value="cadastro">

        
        <h3 class="mt-4">Identidade e Propósito do Negócio</h3>
        <!-- 1 -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Logotipo do negócio</label>
            <input type="file" name="logo_negocio" class="form-control" 
                accept="image/png,image/jpeg,image/jpg,image/webp" required>
            <small class="text-muted">
                Envie o logotipo oficial da sua empresa/negócio.<br>
                ⚠️ Máx. 50MB.<br>
                Recomendação: imagem quadrada (ex.: 500x500px) em formato PNG, JPG, JPEG ou WebP.
            </small>
        </div>
        <!-- 2 -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Descreva seu negócio de impacto em uma frase (até 120 caracteres)</label>
            <input type="text" name="frase_negocio" class="form-control" maxlength="120" required>
            <small class="text-muted">Exemplo: Plataforma que conecta pessoas, negócios e instituições...</small>
        </div>

        <!-- 3 -->
        <!-- Problema -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Qual problema você resolve? (até 200 caracteres)</label>
            <textarea name="problema_resolvido" class="form-control" maxlength="200" rows="3" required><?= htmlspecialchars($apresentacao['problema_resolvido'] ?? '') ?></textarea>
            <small class="text-muted">Descreva a dor ou desafio do seu público-alvo.</small>
        </div>

        <!-- Solução -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Qual solução você oferece? (até 200 caracteres)</label>
            <textarea name="solucao_oferecida" class="form-control" maxlength="200" rows="3" required><?= htmlspecialchars($apresentacao['solucao_oferecida'] ?? '') ?></textarea>
            <small class="text-muted">Descreva como seu produto/serviço resolve o problema acima.</small>
        </div>


        <h3 class="mt-4">Apresente seu Negócio em Vídeo e Imagens</h3>

        <!-- 4 -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Compartilhe um vídeo de até 3 minutos com o pitch do seu negócio</label>
            <input type="url" name="video_pitch_url" class="form-control" 
                placeholder="Cole aqui a URL do YouTube" 
                pattern="^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[A-Za-z0-9_-]{11}$" 
                required>
            <small class="text-muted">
                Exemplo válido: https://www.youtube.com/watch?v=XXXXXXXXXXX<br>
                Esse video será sua apresentação na vitrine de negócios e para a premiação
            </small>
        </div>

        <!-- 5 -->
       <div class="row mb-3">
            <!-- Coluna PDF -->
            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Adicione uma apresentação institucional (PDF)</label>
                <input type="file" name="apresentacao_pdf" class="form-control" accept=".pdf">
                <small class="text-muted">
                    Upload de material explicativo sobre sua solução, trajetória e impacto.<br>
                    ⚠️ Máx. 5MB.
                </small>
            </div>
            <!-- 6 -->
            <!-- Coluna Vídeo YouTube -->
            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Compartilhe um vídeo institucional (YouTube)</label>
                <input type="url" name="apresentacao_video_url" class="form-control"
                    placeholder="Cole aqui a URL do YouTube"
                    pattern="^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[A-Za-z0-9_-]{11}$">
                <small class="text-muted">Somente vídeos do YouTube são aceitos. Exemplo: https://www.youtube.com/watch?v=XXXXXXXXXXX</small>
            </div>
        </div>

        <!-- 7 -->
        <h3 class="mt-4"><i class="bi bi-eye text-secondary me-1"></i> Galeria de imagens do seu negócio</h3>
        <div class="mb-3">
            <label class="form-label">Envie até 10 fotos (máx. 50MB cada)</label>
            <input type="file" name="galeria_imagens[]" class="form-control" accept="image/*" multiple required>
            <small class="text-muted">
                Mostre sua equipe, beneficiários, clientes, produto, operação ou local de atuação.<br>
                ⚠️ Recomendação: envie apenas imagens essenciais. Muitas fotos podem deixar a página muito carregada e a plataforma não irá aceitar.
            </small>
        </div>


        <h3 class="mt-4">Inovação e Modelo de Atuação</h3>
        <div class="mb-3">
        <label class="form-label">
            <i class="bi bi-eye text-secondary me-1"></i>
            Seu negócio incorpora inovação? Marque onde houver inovação real.
        </label>

            <div class="row row-cols-1 row-cols-md-2 g-3">
                <!-- 1. Inovação Tecnológica -->
                <div class="col">
                    <div class="border rounded p-2 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Inovação Tecnológica</strong>
                            <div class="small text-muted">
                            IA, Big Data, IoT, blockchain, plataformas digitais, tecnologias verdes.
                            </div>
                        </div>
                        <div class="ms-2">
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_tecnologica" value="sim">
                            <label class="form-check-label small">Sim</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_tecnologica" value="nao" checked>
                            <label class="form-check-label small">Não</label>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Inovação de Produto -->
                <div class="col">
                    <div class="border rounded p-2 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Inovação de Produto</strong>
                            <div class="small text-muted">
                            Novo produto sustentável, materiais ecológicos, soluções regenerativas ou de saúde/educação inovadoras.
                            </div>
                        </div>
                        <div class="ms-2">
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_produto" value="sim">
                            <label class="form-check-label small">Sim</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_produto" value="nao" checked>
                            <label class="form-check-label small">Não</label>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Inovação de Serviço -->
                <div class="col">
                    <div class="border rounded p-2 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Inovação de Serviço</strong>
                            <div class="small text-muted">
                            Telemedicina para populações remotas, Educação online inclusiva, Plataformas de acesso a crédito, Serviços financeiros acessíveis.
                            </div>
                        </div>
                        <div class="ms-2">
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_servico" value="sim">
                            <label class="form-check-label small">Sim</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_servico" value="nao" checked>
                            <label class="form-check-label small">Não</label>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Inovação em Modelo de Negócio -->
                <div class="col">
                    <div class="border rounded p-2 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Inovação em Modelo de Negócio</strong>
                            <div class="small text-muted">
                            Marketplace de impacto, Economia compartilhada, Assinaturas acessíveis, Pay-per-use, Finanças inclusivas, Modelos B2G (empresa para governo).
                            </div>
                        </div>
                        <div class="ms-2">
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_modelo" value="sim">
                            <label class="form-check-label small">Sim</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_modelo" value="nao" checked>
                            <label class="form-check-label small">Não</label>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
                
                <!-- 5. Inovação Social -->
                <div class="col">
                    <div class="border rounded p-2 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Inovação Social</strong>
                            <div class="small text-muted">
                            Inclusão produtiva, Empoderamento de comunidades, Modelos de geração de renda, Educação transformadora, Democracia e participação cidadã.
                            </div>
                        </div>
                        <div class="ms-2">
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_social" value="sim">
                            <label class="form-check-label small">Sim</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_social" value="nao" checked>
                            <label class="form-check-label small">Não</label>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
                <!-- 6. Inovação Ambiental -->
                <div class="col">
                    <div class="border rounded p-2 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Inovação Ambiental</strong>
                            <div class="small text-muted">
                            Economia circular, Redução de emissões, Agricultura regenerativa, Energia renovável, Gestão de resíduos, Conservação de biodiversidade.
                            </div>
                        </div>
                        <div class="ms-2">
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_ambiental" value="sim">
                            <label class="form-check-label small">Sim</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_ambiental" value="nao" checked>
                            <label class="form-check-label small">Não</label>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- 7. Inovação na Cadeia de Valor -->
                <div class="col">
                    <div class="border rounded p-2 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Inovação na Cadeia de Valor</strong>
                            <div class="small text-muted">
                            Cadeias produtivas inclusivas, Comércio justo, Logística sustentável, Produção local descentralizada, Cadeias transparentes.
                            </div>
                        </div>
                        <div class="ms-2">
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_cadeia_valor" value="sim">
                            <label class="form-check-label small">Sim</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_cadeia_valor" value="nao" checked>
                            <label class="form-check-label small">Não</label>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- 8. Inovação em Governança -->
                <div class="col">
                    <div class="border rounded p-2 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Inovação em Governança</strong>
                            <div class="small text-muted">
                            Governança participativa, Cooperativismo moderno, Empresas de propriedade compartilhada, Modelos de gestão horizontal.
                            </div>
                        </div>
                        <div class="ms-2">
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_governanca" value="sim">
                            <label class="form-check-label small">Sim</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_governanca" value="nao" checked>
                            <label class="form-check-label small">Não</label>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- 9. Inovação em Impacto -->
                <div class="col">
                    <div class="border rounded p-2 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Inovação em Impacto</strong>
                            <div class="small text-muted">
                            Novas métricas de impacto, Modelos de impacto escalável, Tecnologia para monitoramento socioambiental, Impacto em cadeia ou sistêmico.
                            </div>
                        </div>
                        <div class="ms-2">
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_impacto" value="sim">
                            <label class="form-check-label small">Sim</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_impacto" value="nao" checked>
                            <label class="form-check-label small">Não</label>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- 10. Inovação em Financiamento -->
                <div class="col">
                    <div class="border rounded p-2 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Inovação em Financiamento</strong>
                            <div class="small text-muted">
                            Blended finance, Crowdfunding de impacto, Finanças regenerativas, Fundos comunitários, Impact investing.
                            </div>
                        </div>
                        <div class="ms-2">
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_financiamento" value="sim">
                            <label class="form-check-label small">Sim</label>
                            </div>
                            <div class="form-check form-check-inline">
                            <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_financiamento" value="nao" checked>
                            <label class="form-check-label small">Não</label>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-3 mt-3" id="bloco-descricao-inovacao" style="display:none;">
        <label class="form-label">
            <i class="bi bi-eye text-secondary me-1"></i>
            Descreva brevemente as principais inovações do seu negócio (máx. 300 caracteres)
        </label>
        <textarea name="descricaoinovacao" id="descricaoinovacao" class="form-control" rows="3" maxlength="300"></textarea>
        <small class="text-muted">
            Foque no que é realmente novo: tecnologia, forma de operar, modelo de negócio, impacto ou financiamento.
        </small>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
        const radios = document.querySelectorAll('.inovacao-tipo');
        const blocoDescricao = document.getElementById('bloco-descricao-inovacao');

        function atualizarDescricao() {
            let temAlgumSim = false;
            radios.forEach(r => {
            if (r.checked && r.value === 'sim') {
                temAlgumSim = true;
            }
            });
            if (temAlgumSim) {
            blocoDescricao.style.display = 'block';
            blocoDescricao.querySelector('textarea').setAttribute('required', 'required');
            } else {
            blocoDescricao.style.display = 'none';
            const txt = blocoDescricao.querySelector('textarea');
            txt.removeAttribute('required');
            txt.value = '';
            }
        }

        radios.forEach(r => r.addEventListener('change', atualizarDescricao));
        atualizarDescricao(); // estado inicial (útil na edição)
        });
        </script>



        <div class="row mb-4">
            <!-- Item 9 -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Tipo de solução oferecida</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_solucao" value="Produto" required>
                            <label class="form-check-label">Produto</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_solucao" value="Serviço">
                            <label class="form-check-label">Serviço</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_solucao" value="Produto e Serviço">
                            <label class="form-check-label">Produto e Serviço</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item 10 -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Modelo de negócio</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="B2B" required>
                            <label class="form-check-label">B2B – Empresa para Empresa</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="B2C">
                            <label class="form-check-label">B2C – Empresa para Consumidor</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="C2C">
                            <label class="form-check-label">C2C – Consumidor para Consumidor</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="B2B2C">
                            <label class="form-check-label">B2B2C – Empresa para Empresa para Consumidor</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="B2G">
                            <label class="form-check-label">B2G – Empresa para Governo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="B2N">
                            <label class="form-check-label">B2N – Empresa para Ongs, Fundações, Associações</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item 11 -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Número de colaboradores</h5>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="colaboradores" value="Até 5" required>
                            <label class="form-check-label">Até 5</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="colaboradores" value="6–20">
                            <label class="form-check-label">6–20</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="colaboradores" value="21–50">
                            <label class="form-check-label">21–50</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="colaboradores" value="51 ou mais">
                            <label class="form-check-label">51 ou mais</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mt-4">Histórico e Desafios do Negócio</h3>

        <!-- 12 -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Seu negócio já teve apoio de uma aceleradora ou programa de fomento?</label><br>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="apoio" value="nao" required>
                <label class="form-check-label">Não</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="apoio" value="sim">
                <label class="form-check-label">Sim. Quais programas ou instituições apoiaram seu negócio? Até 120 caracteres</label>
            </div>
            <input type="text" name="programas" class="form-control mt-2"  maxlength="120">
            <small class="text-muted">
                Exemplos: aceleradoras, incubadoras, editais públicos, programas de impacto, universidades, ONGs, Sebrae, fundos etc. 
            </small>
        </div>

        <div class="mb-4 mt-5">
            <label class="form-label">
                <i class="bi bi-eye-slash text-danger-emphasis me-1"></i>
                Quais são hoje os principais desafios para o desenvolvimento do seu negócio?
            </label>
            <p class="text-muted">
                <strong>Passo 1:</strong> Selecione na lista abaixo os desafios que limitam o crescimento do seu negócio.<br>
                <strong>Passo 2:</strong> Logo abaixo, classifique os itens selecionados.
            </p>

            <?php
            // Nova estrutura agrupada
            $gruposDesafios = [
                "A. Finanças e Capital" => [
                    "Acessar investimento ou capital" => ["bi-cash-stack", "desafio_acessar_capital"],
                    "Capital de giro / fluxo de caixa insuficiente" => ["bi-bank", "desafio_fluxo_caixa"],
                    "Estruturar modelo financeiro sustentável" => ["bi-graph-up-arrow", "desafio_melhorar_gestao"],
                    "Dificuldade de acessar crédito ou financiamento" => ["bi-credit-card", "desafio_falta_entendimento_bancos"] 
                ],
                "B. Mercado e Vendas" => [
                    "Baixa demanda ou volume de vendas" => ["bi-cart-x", "desafio_baixa_demanda_vendas"],
                    "Dificuldade de acessar novos mercados" => ["bi-shop", "desafio_acesso_mercado_distribuicao"],
                    "Marketing e reconhecimento de marca" => ["bi-megaphone", "desafio_marketing_posicionamento"],
                    "Dificuldade em comunicar o valor do impacto" => ["bi-chat-left-dots", "desafio_falta_entendimento_publico"]
                ],
                "C. Gestão e Estratégia" => [
                    "Falta de conselho/mentoria estratégica" => ["bi-lightbulb", "desafio_falta_conselho_mentoria"],
                    "Acesso a mentoria especializada" => ["bi-journal-check", "desafio_acesso_mentoria_especializada"],
                    "Falta de tempo ou sobrecarga da liderança" => ["bi-hourglass-split", "desafio_baixa_capacidade_entrega"]
                ],
                "D. Equipe e Talentos" => [
                    "Estruturar ou expandir a equipe" => ["bi-people", "desafio_estruturar_equipe"],
                    "Escassez de profissionais técnicos" => ["bi-person-workspace", "desafio_escassez_tecnico"]
                ],
                "E. Operação e Escala" => [
                    "Logística cara ou ineficiente" => ["bi-box-seam", "desafio_logistica_cara_ineficiente"],
                    "Infraestrutura limitada ou cara" => ["bi-hammer", "desafio_infraestrutura_limitada_cara"],
                    "Internacionalização" => ["bi-globe", "desafio_internacionalizacao"]
                ],
                "F. Conexões e Ecossistema" => [
                    "Desenvolver parcerias e networking" => ["bi-diagram-3", "desafio_parcerias_networking"],
                    "Relacionamento com governo" => ["bi-building", "desafio_relacionamento_governo"]
                ],
                "G. Ambiente e Contexto Econômico" => [
                    "Carga tributária e burocracia" => ["bi-file-earmark-text", "desafio_carga_tributaria_burocracia"],
                    "Regulação desfavorável" => ["bi-shield-exclamation", "desafio_regulacao_desfavoravel"],
                    "Instabilidade econômica" => ["bi-graph-down", "desafio_instabilidade_economica"]
                ]
            ];
            ?>

            <!-- PASSO 1: SELEÇÃO -->
            <div class="row g-3">
                <?php foreach ($gruposDesafios as $grupo => $itens): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0 bg-light">
                            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                                <h6 class="mb-0 text-primary"><?= $grupo ?></h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($itens as $label => [$icon, $name]): ?>
                                    <?php 
                                        // Se for edição, verifica se no BD o valor salvo é > 0
                                        $valorAtual = (int)($apresentacao[$name] ?? 0);
                                        $isChecked = $valorAtual > 0 ? 'checked' : ''; 
                                    ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input check-desafio" type="checkbox" 
                                            id="chk_<?= $name ?>" 
                                            data-name="<?= $name ?>" 
                                            data-label="<?= htmlspecialchars($label) ?>" 
                                            data-icon="<?= $icon ?>"
                                            <?= $isChecked ?>>
                                        <label class="form-check-label small" for="chk_<?= $name ?>">
                                            <i class="bi <?= $icon ?> text-secondary me-1"></i> <?= $label ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- PASSO 2: CLASSIFICAÇÃO (Gerado via JS) -->
            <div class="mt-5 p-4 border rounded bg-white shadow-sm" id="bloco-classificacao" style="display: none;">
                <h5 class="text-dark"><i class="bi bi-list-ol me-2"></i> Passo 2: Classifique os selecionados</h5>
                <p class="small text-muted mb-4">Dê uma nota para cada desafio escolhido. (5 = Maior desafio / 1 = Menor desafio).</p>
                
                <div id="lista-ranking" class="row">
                    <!-- Inputs e selects serão injetados aqui -->
                </div>

                <!-- Hidden inputs originais (garante que desmarcados enviem 0 pro banco) -->
                <div id="hidden-inputs-desafios">
                    <?php foreach ($gruposDesafios as $grupo => $itens): ?>
                        <?php foreach ($itens as $label => [$icon, $name]): ?>
                            <input type="hidden" name="<?= $name ?>" id="input_real_<?= $name ?>" value="<?= (int)($apresentacao[$name] ?? 0) ?>">
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>



        <h3 class="mt-4">Reconhecimentos e Visibilidade</h3>
        <!-- 14 -->
        <h4 class="mt-4">Informações adicionais que mostram a relevância do seu negócio </h4>
        <small class="text-muted">Compartilhe prêmios, matérias jornalísticas, artigos, eventos, parcerias institucionais ou outros destaques que ajudam a evidenciar sua credibilidade e impacto.</small>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Texto adicional</label>
                <textarea name="info_adicionais" class="form-control" rows="5" maxlength="3000"
                        placeholder="Descreva informações adicionais relevantes..."></textarea>
                <small class="text-muted">Máx. 3000 caracteres.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Links externos</label>
                <div id="links-container">
                    <input type="url" name="info_adicionais_link[]" class="form-control mb-2"
                        placeholder="Cole aqui um link (YouTube, matéria, PDF hospedado)">
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addLinkField()">+ Adicionar outro link</button>
                <small class="text-muted">
                    Você pode adicionar vários links externos (vídeos, matérias, PDFs hospedados).<br>
                    ⚠️ Somente links, não é permitido upload de arquivos grandes.
                </small>
            </div>

            <script>
            function addLinkField() {
                const container = document.getElementById('links-container');
                const input = document.createElement('input');
                input.type = 'url';
                input.name = 'info_adicionais_link[]';
                input.className = 'form-control mb-2';
                input.placeholder = 'Outro link opcional';
                container.appendChild(input);
            }
            </script>
        </div>
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="/negocios/editar_etapa4.php?id=<?= $negocio_id ?>" class="btn btn-secondary me-md-2">← Voltar</a>
            <button type="submit" class="btn btn-primary">Salvar e avançar</button>
        </div>
    </form>
</div>
<script>
document.getElementById('logo_negocio').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const validTypes = ['image/png','image/jpeg','image/jpg','image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Arquivo inválido! Apenas PNG, JPG, JPEG ou WebP são aceitos.');
            this.value = ''; // limpa o campo
        }
    }
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const camposTexto = document.querySelectorAll("input[type='text'], textarea");

    function validarTexto(campo) {
        const regex = /[a-zA-ZÀ-ÿ]/g;
        const letras = (campo.value.match(regex) || []).length;

        // Se o campo é obrigatório OU se foi preenchido
        if (campo.hasAttribute("required") || campo.value.trim() !== "") {
            if (letras < 5) {
                campo.setCustomValidity("Digite um texto válido (mínimo 5 letras reais).");
            } else {
                campo.setCustomValidity("");
            }
        } else {
            // Se não é obrigatório e está vazio, não gera erro
            campo.setCustomValidity("");
        }
    }

    // Valida em tempo real e ao sair do campo
    camposTexto.forEach(campo => {
        campo.addEventListener("input", function() {
            validarTexto(campo);
        });
        campo.addEventListener("blur", function() {
            validarTexto(campo);
            campo.reportValidity();
        });
    });

    // Valida todos os campos antes de enviar o formulário
    const form = document.querySelector("form");
    form.addEventListener("submit", function(e) {
        let valido = true;
        camposTexto.forEach(campo => {
            validarTexto(campo);
            if (!campo.checkValidity()) {
                campo.reportValidity();
                valido = false;
            }
        });
        if (!valido) e.preventDefault();
    });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const checkboxes = document.querySelectorAll('.check-desafio');
    const blocoClassificacao = document.getElementById('bloco-classificacao');
    const listaRanking = document.getElementById('lista-ranking');

    function atualizarRanking() {
        let selecionados = [];
        
        // Coleta quem está marcado
        checkboxes.forEach(chk => {
            if (chk.checked) {
                selecionados.push({
                    name: chk.getAttribute('data-name'),
                    label: chk.getAttribute('data-label'),
                    icon: chk.getAttribute('data-icon')
                });
            }
            // Zera o input escondido se não estiver marcado
            if (!chk.checked) {
                document.getElementById('input_real_' + chk.getAttribute('data-name')).value = "0";
            }
        });

        // Mostra ou esconde o bloco do passo 2
        if (selecionados.length > 0) {
            blocoClassificacao.style.display = 'block';
        } else {
            blocoClassificacao.style.display = 'none';
        }

        // Renderiza a lista para o usuário classificar
        listaRanking.innerHTML = '';
        
        selecionados.forEach((item) => {
            let hiddenInput = document.getElementById('input_real_' + item.name);
            let valorSalvo = hiddenInput.value > 0 ? hiddenInput.value : ''; // Pega nota anterior se existir

            let card = document.createElement('div');
            card.className = "col-12 col-md-6 mb-3"; // Coloquei em duas colunas pra ficar mais leve na tela
            card.innerHTML = `
                <div class="d-flex align-items-center justify-content-between p-3 border rounded bg-light h-100">
                    <div class="d-flex align-items-center pe-2">
                        <i class="bi ${item.icon} fs-4 text-primary me-3"></i>
                        <span class="fw-medium small">${item.label}</span>
                    </div>
                    <div class="ms-auto flex-shrink-0" style="width: 140px;">
                        <select class="form-select form-select-sm select-nota" data-target="${item.name}" required>
                            <option value="">Selecione...</option>
                            <option value="5" ${valorSalvo == '5' ? 'selected' : ''}>5 (Maior)</option>
                            <option value="4" ${valorSalvo == '4' ? 'selected' : ''}>4</option>
                            <option value="3" ${valorSalvo == '3' ? 'selected' : ''}>3</option>
                            <option value="2" ${valorSalvo == '2' ? 'selected' : ''}>2</option>
                            <option value="1" ${valorSalvo == '1' ? 'selected' : ''}>1 (Menor)</option>
                        </select>
                    </div>
                </div>
            `;
            listaRanking.appendChild(card);
        });

        // Adiciona evento nos selects recém-criados para atualizar o input hidden real que vai pro PHP
        document.querySelectorAll('.select-nota').forEach(select => {
            select.addEventListener('change', function() {
                let targetName = this.getAttribute('data-target');
                document.getElementById('input_real_' + targetName).value = this.value;
            });
            // Força o envio do valor se ele já for selecionado (na edição)
            if(select.value !== "") {
                 document.getElementById('input_real_' + select.getAttribute('data-target')).value = select.value;
            }
        });
    }

    checkboxes.forEach(chk => {
        chk.addEventListener('change', atualizarRanking);
    });

    // Roda ao iniciar (Para preencher a edição corretamente)
    atualizarRanking();
});
</script>



<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>