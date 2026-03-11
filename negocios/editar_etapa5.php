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

$negocio_id = (int)($_GET['id'] ?? 0);
if ($negocio_id === 0) {
    header("Location: /empreendedores/meus-negocios.php");
    exit;
}

// Busca dados da apresentação
$stmt = $pdo->prepare("SELECT * FROM negocio_apresentacao WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);
$apresentacao = $stmt->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-4">Editar Etapa 5 - Apresentação do Negócio</h1>
        <a href="/empreendedores/meus-negocios.php" class="btn btn-secondary">← Voltar aos negócios</a>
    </div>
    
    <?php
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
        <input type="hidden" name="modo" value="editar">

        <h3 class="mt-4">Identidade e Propósito do Negócio</h3>
        <!-- Logo -->
       <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Logotipo atual</label><br>
            <?php if (!empty($apresentacao['logo_negocio'])): ?>
                <img src="<?= htmlspecialchars($apresentacao['logo_negocio']) ?>" 
                    alt="Logo" style="max-height:120px;">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remover_logo" value="1" id="removerLogo">
                    <label class="form-check-label" for="removerLogo">Remover logotipo atual</label>
                </div>
            <?php else: ?>
                <span class="text-muted">Nenhum logotipo enviado</span>
            <?php endif; ?>

            <input type="file" id="logo_negocio_edit" name="logo_negocio" 
                class="form-control mt-2" 
                accept="image/png,image/jpeg,image/jpg,image/webp">

            <small class="text-muted">
                ⚠️ Máx. 50MB.<br>
                Formatos aceitos: PNG, JPG, JPEG ou WebP.
            </small>
        </div>


        <!-- Frase - Problema/Solução -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Frase do negócio</label>
            <textarea name="frase_negocio" class="form-control"><?= htmlspecialchars($apresentacao['frase_negocio'] ?? '') ?></textarea>
        </div>

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
        <!-- Vídeo Pitch -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Vídeo Pitch (YouTube)</label>
            <input type="url" name="video_pitch_url" class="form-control"
                   value="<?= htmlspecialchars($apresentacao['video_pitch_url'] ?? '') ?>">
        </div>

        <!-- PDF -->

       <div class="row mb-3">
            <!-- Coluna PDF -->
            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Apresentação Institucional PDF</label><br>
                <?php if (!empty($apresentacao['apresentacao_pdf'])): ?>
                    <a href="<?= htmlspecialchars($apresentacao['apresentacao_pdf']) ?>" target="_blank">Ver PDF atual</a>
                <?php else: ?>
                    <span class="text-muted">Nenhum PDF enviado</span>
                <?php endif; ?>
                <input type="file" name="apresentacao_pdf" class="form-control mt-2" accept=".pdf">
            </div>
            <!-- 6 -->
            <!-- Coluna Vídeo YouTube -->
            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Vídeo Institucional (YouTube)</label>
                <input type="url" name="apresentacao_video_url" class="form-control"
                   value="<?= htmlspecialchars($apresentacao['apresentacao_video_url'] ?? '') ?>">
                   <small class="text-muted">Somente vídeos do YouTube são aceitos. Exemplo: https://www.youtube.com/watch?v=XXXXXXXXXXX</small>
            </div>
        </div>

        <!-- Galeria -->
         
        <h3 class="mt-4">Galeria de imagens do seu negócio</h3>
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye text-secondary me-1"></i> Galeria de imagens</label><br>
            <div class="row">
                <?php
                $galeria = json_decode($apresentacao['galeria_imagens'] ?? '[]', true);
                if (!empty($galeria)):
                    foreach ($galeria as $index => $img): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <img src="<?= htmlspecialchars($img) ?>" alt="Imagem" style="max-height:120px; margin-bottom:10px;" class="img-fluid">
                                    <div class="form-check text-start">
                                        <input class="form-check-input" type="checkbox" name="remover_imagem[]" value="<?= $index ?>" id="remover<?= $index ?>">
                                        <label class="form-check-label" for="remover<?= $index ?>">Remover</label>
                                    </div>
                                    <input type="file" name="substituir_imagem[<?= $index ?>]" class="form-control mt-2" accept="image/*">
                                    <small class="text-muted">Substituir esta imagem</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <span class="text-muted">Nenhuma imagem enviada</span>
                <?php endif; ?>
            </div>

            <div class="mt-3">
                <label class="form-label">Adicionar novas imagens (máx. 10 no total)</label>
                <input type="file" name="galeria_imagens[]" class="form-control" accept="image/*" multiple>
                <small class="text-muted">Você pode adicionar novas imagens, mas o total não pode ultrapassar 10 por galeria.</small>
            </div>
        </div>

        <h3 class="mt-4">Inovação e Modelo de Atuação</h3>

        <div class="mb-3 mt-3">
        <label class="form-label fw-bold">
            <i class="bi bi-eye text-secondary me-1"></i>
            Seu negócio incorpora inovação? Marque onde houver inovação real.
        </label>

        <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
            <!-- 1. Inovação Tecnológica -->
            <div class="col">
            <div class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>1. Inovação Tecnológica</strong>
                    <div class="small text-muted lh-sm mt-1">Uso de IA, Big Data, IoT, blockchain, plataformas digitais, biotecnologia ou tecnologias verdes.</div>
                </div>
                <div class="ms-3 flex-shrink-0">
                    <div class="form-check form-check-inline me-1">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_tecnologica" value="sim" <?= !empty($apresentacao['inovacao_tecnologica']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Sim</label>
                    </div>
                    <div class="form-check form-check-inline me-0">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_tecnologica" value="nao" <?= empty($apresentacao['inovacao_tecnologica']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Não</label>
                    </div>
                </div>
                </div>
            </div>
            </div>

            <!-- 2. Inovação de Produto -->
            <div class="col">
            <div class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>2. Inovação de Produto</strong>
                    <div class="small text-muted lh-sm mt-1">Novo produto sustentável, materiais ecológicos, soluções regenerativas ou tecnologias de saúde/educação.</div>
                </div>
                <div class="ms-3 flex-shrink-0">
                    <div class="form-check form-check-inline me-1">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_produto" value="sim" <?= !empty($apresentacao['inovacao_produto']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Sim</label>
                    </div>
                    <div class="form-check form-check-inline me-0">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_produto" value="nao" <?= empty($apresentacao['inovacao_produto']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Não</label>
                    </div>
                </div>
                </div>
            </div>
            </div>

            <!-- 3. Inovação de Serviço -->
            <div class="col">
            <div class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>3. Inovação de Serviço</strong>
                    <div class="small text-muted lh-sm mt-1">Telemedicina, educação online inclusiva, plataformas de acesso a crédito ou serviços financeiros acessíveis.</div>
                </div>
                <div class="ms-3 flex-shrink-0">
                    <div class="form-check form-check-inline me-1">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_servico" value="sim" <?= !empty($apresentacao['inovacao_servico']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Sim</label>
                    </div>
                    <div class="form-check form-check-inline me-0">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_servico" value="nao" <?= empty($apresentacao['inovacao_servico']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Não</label>
                    </div>
                </div>
                </div>
            </div>
            </div>

            <!-- 4. Inovação em Modelo de Negócio -->
            <div class="col">
            <div class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>4. Modelo de Negócio</strong>
                    <div class="small text-muted lh-sm mt-1">Marketplace de impacto, economia compartilhada, assinaturas, pay-per-use, B2G.</div>
                </div>
                <div class="ms-3 flex-shrink-0">
                    <div class="form-check form-check-inline me-1">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_modelo" value="sim" <?= !empty($apresentacao['inovacao_modelo']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Sim</label>
                    </div>
                    <div class="form-check form-check-inline me-0">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_modelo" value="nao" <?= empty($apresentacao['inovacao_modelo']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Não</label>
                    </div>
                </div>
                </div>
            </div>
            </div>

            <!-- 5. Inovação Social -->
            <div class="col">
            <div class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>5. Inovação Social</strong>
                    <div class="small text-muted lh-sm mt-1">Inclusão produtiva, empoderamento, geração de renda, educação transformadora, participação cidadã.</div>
                </div>
                <div class="ms-3 flex-shrink-0">
                    <div class="form-check form-check-inline me-1">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_social" value="sim" <?= !empty($apresentacao['inovacao_social']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Sim</label>
                    </div>
                    <div class="form-check form-check-inline me-0">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_social" value="nao" <?= empty($apresentacao['inovacao_social']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Não</label>
                    </div>
                </div>
                </div>
            </div>
            </div>

            <!-- 6. Inovação Ambiental -->
            <div class="col">
            <div class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>6. Inovação Ambiental</strong>
                    <div class="small text-muted lh-sm mt-1">Economia circular, redução de emissões, agricultura regenerativa, energia renovável, gestão de resíduos.</div>
                </div>
                <div class="ms-3 flex-shrink-0">
                    <div class="form-check form-check-inline me-1">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_ambiental" value="sim" <?= !empty($apresentacao['inovacao_ambiental']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Sim</label>
                    </div>
                    <div class="form-check form-check-inline me-0">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_ambiental" value="nao" <?= empty($apresentacao['inovacao_ambiental']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Não</label>
                    </div>
                </div>
                </div>
            </div>
            </div>

            <!-- 7. Cadeia de Valor -->
            <div class="col">
            <div class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>7. Cadeia de Valor</strong>
                    <div class="small text-muted lh-sm mt-1">Cadeias produtivas inclusivas, comércio justo, logística sustentável, produção descentralizada.</div>
                </div>
                <div class="ms-3 flex-shrink-0">
                    <div class="form-check form-check-inline me-1">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_cadeia_valor" value="sim" <?= !empty($apresentacao['inovacao_cadeia_valor']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Sim</label>
                    </div>
                    <div class="form-check form-check-inline me-0">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_cadeia_valor" value="nao" <?= empty($apresentacao['inovacao_cadeia_valor']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Não</label>
                    </div>
                </div>
                </div>
            </div>
            </div>

            <!-- 8. Governança -->
            <div class="col">
            <div class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>8. Governança</strong>
                    <div class="small text-muted lh-sm mt-1">Governança participativa, cooperativismo, propriedade compartilhada, gestão horizontal.</div>
                </div>
                <div class="ms-3 flex-shrink-0">
                    <div class="form-check form-check-inline me-1">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_governanca" value="sim" <?= !empty($apresentacao['inovacao_governanca']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Sim</label>
                    </div>
                    <div class="form-check form-check-inline me-0">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_governanca" value="nao" <?= empty($apresentacao['inovacao_governanca']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Não</label>
                    </div>
                </div>
                </div>
            </div>
            </div>

            <!-- 9. Impacto -->
            <div class="col">
            <div class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>9. Inovação em Impacto</strong>
                    <div class="small text-muted lh-sm mt-1">Novas métricas de impacto, modelos escaláveis, tecnologia para monitoramento socioambiental.</div>
                </div>
                <div class="ms-3 flex-shrink-0">
                    <div class="form-check form-check-inline me-1">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_impacto" value="sim" <?= !empty($apresentacao['inovacao_impacto']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Sim</label>
                    </div>
                    <div class="form-check form-check-inline me-0">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_impacto" value="nao" <?= empty($apresentacao['inovacao_impacto']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Não</label>
                    </div>
                </div>
                </div>
            </div>
            </div>

            <!-- 10. Financiamento -->
            <div class="col">
            <div class="border rounded p-2 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>10. Financiamento</strong>
                    <div class="small text-muted lh-sm mt-1">Blended finance, crowdfunding, finanças regenerativas, fundos comunitários, impact investing.</div>
                </div>
                <div class="ms-3 flex-shrink-0">
                    <div class="form-check form-check-inline me-1">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_financiamento" value="sim" <?= !empty($apresentacao['inovacao_financiamento']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Sim</label>
                    </div>
                    <div class="form-check form-check-inline me-0">
                    <input class="form-check-input inovacao-tipo" type="radio" name="inovacao_financiamento" value="nao" <?= empty($apresentacao['inovacao_financiamento']) ? 'checked' : '' ?>>
                    <label class="form-check-label small">Não</label>
                    </div>
                </div>
                </div>
            </div>
            </div>
        </div>
        </div>

        <!-- Text Area Condicional (só aparece se alguma inovação for 'sim') -->
        <div id="bloco-descricao-inovacao" class="mb-4 p-3 border rounded bg-light" style="display: none;">
        <label class="form-label fw-bold">
            <i class="bi bi-eye text-secondary me-1"></i>
            Descreva brevemente as inovações marcadas acima
        </label>
        <textarea name="descricao_inovacao" id="descricao_inovacao" class="form-control" rows="3" maxlength="300" placeholder="Detalhe como a inovação é aplicada no seu negócio..."><?= htmlspecialchars($apresentacao['descricao_inovacao'] ?? '') ?></textarea>
        <small class="text-muted">Foque no que é realmente novo ou estrutural no seu negócio (máx. 300 caracteres).</small>
        </div>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const radiosInovacao = document.querySelectorAll('.inovacao-tipo');
            const blocoDescricao = document.getElementById('bloco-descricao-inovacao');
            const campoDescricao = document.getElementById('descricao_inovacao');

            function checarInovacoes() {
                let temSim = false;
                radiosInovacao.forEach(r => {
                    if (r.checked && r.value === 'sim') {
                        temSim = true;
                    }
                });

                if (temSim) {
                    blocoDescricao.style.display = 'block';
                    campoDescricao.setAttribute('required', 'required');
                } else {
                    blocoDescricao.style.display = 'none';
                    campoDescricao.removeAttribute('required');
                    campoDescricao.value = ''; // Limpa o campo se tudo for 'não'
                }
            }

            radiosInovacao.forEach(r => {
                r.addEventListener('change', checarInovacoes);
            });

            // Executa no carregamento da página para mostrar o campo se na edição já tiver 'Sim'
            checarInovacoes();
        });
        </script>



        <div class="row mb-4">
            <!-- Item 9 -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Tipo de solução oferecida</h5>
                        <?php $tipo = $apresentacao['tipo_solucao'] ?? ''; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_solucao" value="Produto"
                                <?= ($tipo === 'Produto') ? 'checked' : '' ?> required>
                            <label class="form-check-label">Produto</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_solucao" value="Serviço"
                                <?= ($tipo === 'Serviço') ? 'checked' : '' ?>>
                            <label class="form-check-label">Serviço</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_solucao" value="Produto e Serviço"
                                <?= ($tipo === 'Produto e Serviço') ? 'checked' : '' ?>>
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
                        <?php $modelo = $apresentacao['modelo_negocio'] ?? ''; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="B2B"
                                <?= ($modelo === 'B2B') ? 'checked' : '' ?> required>
                            <label class="form-check-label">B2B – Empresa para Empresa</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="B2C"
                                <?= ($modelo === 'B2C') ? 'checked' : '' ?>>
                            <label class="form-check-label">B2C – Empresa para Consumidor</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="C2C"
                                <?= ($modelo === 'C2C') ? 'checked' : '' ?>>
                            <label class="form-check-label">C2C – Consumidor para Consumidor</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="B2B2C"
                                <?= ($modelo === 'B2B2C') ? 'checked' : '' ?>>
                            <label class="form-check-label">B2B2C – Empresa para Empresa para Consumidor</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="B2G"
                                <?= ($modelo === 'B2G') ? 'checked' : '' ?>>
                            <label class="form-check-label">B2G – Empresa para Governo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modelo_negocio" value="B2N"                            
                                <?= ($modelo === 'B2N') ? 'checked' : '' ?>>
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
                        <?php $colab = $apresentacao['colaboradores'] ?? ''; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="colaboradores" value="Até 5"
                                <?= ($colab === 'Até 5') ? 'checked' : '' ?> required>
                            <label class="form-check-label">Até 5</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="colaboradores" value="6–20"
                                <?= ($colab === '6–20') ? 'checked' : '' ?>>
                            <label class="form-check-label">6–20</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="colaboradores" value="21–50"
                                <?= ($colab === '21–50') ? 'checked' : '' ?>>
                            <label class="form-check-label">21–50</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="colaboradores" value="51 ou mais"
                                <?= ($colab === '51 ou mais') ? 'checked' : '' ?>>
                            <label class="form-check-label">51 ou mais</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

         <h3 class="mt-4">Histórico e Desafios do Negócio</h3>
        <!-- Item 12 -->
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Seu negócio já teve apoio de uma aceleradora ou programa de fomento?</label><br>
            <?php $apoio = $apresentacao['apoio'] ?? ''; ?>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="apoio" value="nao"
                    <?= ($apoio === 'nao') ? 'checked' : '' ?> required>
                <label class="form-check-label">Não</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="apoio" value="sim"
                    <?= ($apoio === 'sim') ? 'checked' : '' ?>>
                <label class="form-check-label">Sim. Quais?</label>
            </div>
            <input type="text" name="programas" class="form-control mt-2" maxlength="120"
                value="<?= htmlspecialchars($apresentacao['programas'] ?? '') ?>">
            <small class="text-muted">Até 120 caracteres</small>
        </div>

       <!-- Desafios -->
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
            // Nova estrutura agrupada de desafios
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
                                <h6 class="mb-0 text-primary fw-bold"><?= $grupo ?></h6>
                            </div>
                            <div class="card-body pt-2">
                                <?php foreach ($itens as $label => [$icon, $name]): ?>
                                    <?php 
                                        // Se for edição, verifica se no BD o valor salvo é maior que 0
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
                <h5 class="text-dark"><i class="bi bi-list-ol text-primary me-2"></i> Passo 2: Classifique os selecionados</h5>
                <p class="small text-muted mb-4">Dê uma nota para cada desafio escolhido. (<strong>5 = Maior desafio</strong> / <strong>1 = Menor desafio</strong>).</p>
                
                <div id="lista-ranking" class="row">
                    <!-- Inputs e selects serão injetados aqui dinamicamente -->
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

        <h4 class="mt-4">Informações adicionais que mostram a relevância do seu negócio </h4>
        <small class="text-muted">Compartilhe prêmios, matérias jornalísticas, artigos, eventos, parcerias institucionais ou outros destaques que ajudam a evidenciar sua credibilidade e impacto.</small>
        <!-- Informações adicionais -->
         
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Texto adicional</label>
                <textarea name="info_adicionais" class="form-control" rows="5" maxlength="3000"><?= htmlspecialchars($apresentacao['info_adicionais'] ?? '') ?></textarea>
                <small class="text-muted">Máx. 3000 caracteres.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-eye-slash text-danger-emphasis me-1"></i> Links externos</label>
                <?php
                $links = json_decode($apresentacao['info_adicionais_links'] ?? '[]', true);
                if (!empty($links)):
                    foreach ($links as $link): ?>
                        <input type="url" name="info_adicionais_link[]" class="form-control mb-2"
                            value="<?= htmlspecialchars($link) ?>">
                    <?php endforeach;
                else: ?>
                    <input type="url" name="info_adicionais_link[]" class="form-control mb-2"
                        placeholder="Cole aqui um link (YouTube, matéria, PDF hospedado)">
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addLinkField()">+ Adicionar outro link</button>
            </div>
        </div>  

        <script>
            function addLinkField() {
                const container = document.querySelector('[name="info_adicionais_link[]"]').parentNode;
                const input = document.createElement('input');
                input.type = 'url';
                input.name = 'info_adicionais_link[]';
                input.className = 'form-control mb-2';
                input.placeholder = 'Outro link opcional';
                container.appendChild(input);
            }
        </script>

        
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="/negocios/editar_etapa4.php?id=<?= $negocio_id ?>" class="btn btn-secondary me-md-2">← Voltar</a>
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('logo_negocio_edit').addEventListener('change', function() {
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
            } else {
                // Zera o input escondido se não estiver marcado
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
            card.className = "col-12 col-md-6 mb-3"; // Em duas colunas para não ficar gigante
            card.innerHTML = `
                <div class="d-flex align-items-center justify-content-between p-3 border rounded bg-light h-100">
                    <div class="d-flex align-items-center pe-3">
                        <i class="bi ${item.icon} fs-4 text-primary me-3"></i>
                        <span class="fw-medium small lh-sm">${item.label}</span>
                    </div>
                    <div class="ms-auto flex-shrink-0" style="width: 130px;">
                        <select class="form-select form-select-sm select-nota border-primary shadow-sm" data-target="${item.name}" required>
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

    // Roda ao iniciar (Para preencher a edição corretamente carregando os dados do banco)
    atualizarRanking();
});
</script>


<?php include __DIR__ . '/../app/views/empreendedor/footer.php'; ?>