<!-- Modal Preview Vitrine -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Pré-visualização da Vitrine de Negócios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="container">
                    
                    <!-- Cabeçalho do Negócio -->
                    <div class="row">
                        <div class="col-md-4">
                            <!-- Logotipo -->
                            <?php if (!empty($apresentacao['logo_negocio'])): ?>
                                <div class="text-center mb-3">
                                    <img src="<?= htmlspecialchars($apresentacao['logo_negocio']) ?>" alt="Logotipo" class="img-fluid" style="max-height: 120px;">
                                </div>
                            <?php endif; ?>
                            
                            <p class="text-center">
                                <span class="badge bg-secondary fs-6"><?= htmlspecialchars($negocio['categoria'] ?? '-') ?></span>
                            </p>
                            <p class="text-center">
                                <strong><?= htmlspecialchars($negocio['municipio'] ?? '-') . ', ' . htmlspecialchars($negocio['estado'] ?? '-') ?></strong>
                            </p>
                        </div>
                        <div class="col-md-8">
                            <!-- Nome fantasia e frase -->
                            <h2 class="text-center"><?= htmlspecialchars($negocio['nome_fantasia'] ?? '-') ?></h2>
                            
                            <!-- Eixo principal -->
                            <?php if ($eixo_principal): ?>
                                <p class="text-center"><span class="badge bg-primary text-wrap fs-6"><?= htmlspecialchars($eixo_principal['eixo_nome'] ?? 'Não informado') ?></span></p>
                            <?php endif; ?>
                            
                            <p class="lead text-center"><em><?= htmlspecialchars($apresentacao['frase_negocio'] ?? '-') ?></em></p>
                            
                            <!-- Sites e redes -->
                            <?php 
                            $icones = [
                                'site' => 'bi-globe', 'linkedin' => 'bi-linkedin', 
                                'instagram' => 'bi-instagram', 'facebook' => 'bi-facebook', 
                                'youtube' => 'bi-youtube', 'tiktok' => 'bi-tiktok', 
                                'outroslinks' => 'bi-link-45deg'
                            ];
                            $temRedes = false;
                            foreach ($icones as $rede => $icone) {
                                if (!empty($negocio[$rede])) { $temRedes = true; break; }
                            }
                            ?>
                            
                            <?php if ($temRedes): ?>
                                <h5 class="mt-3">Sites e Redes</h5>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($icones as $rede => $icone): ?>
                                        <?php if (!empty($negocio[$rede])): ?>
                                            <a href="<?= htmlspecialchars($negocio[$rede]) ?>" target="_blank" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                                                <i class="<?= $icone ?>"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>

                    <!-- Conteúdo Principal -->
                    <div class="row">
                        <div class="col-12">
                            <!-- Problema x Solução -->
                            <?php if (!empty($apresentacao['problema_solucao'])): ?>
                                <h5 class="mt-4">Problema x Solução</h5>
                                <span class="badge bg-light text-bg-light text-wrap p-3 d-block text-start text-dark border">
                                    <?= nl2br(htmlspecialchars($apresentacao['problema_solucao'])) ?>
                                </span>
                            <?php endif; ?>

                            <!-- Inovação -->
                            <?php if (!empty($apresentacao['descricao_inovacao'])): ?>
                                <h5 class="mt-3">Inovação</h5>
                                <span class="badge bg-light text-bg-light text-wrap p-3 d-block text-start text-dark border">
                                    <?= nl2br(htmlspecialchars($apresentacao['descricao_inovacao'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ODS -->
                    <div class="row p-3 mt-3 bg-light rounded">
                        <div class="col-12 col-md-6 text-center">
                            <!-- ODS Prioritária -->
                            <?php if (!empty($ods_prioritaria['icone_url'])): ?>
                                <h5>ODS Prioritária</h5>
                                <img src="<?= htmlspecialchars($ods_prioritaria['icone_url']) ?>" alt="ODS" style="max-height: 100px;">
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-md-6 text-center">
                            <!-- ODS Relacionadas -->
                            <?php if (!empty($ods_relacionadas)): ?>
                                <h5>ODS Relacionadas</h5>
                                <div class="d-flex flex-wrap gap-2 justify-content-center">
                                    <?php foreach ($ods_relacionadas as $ods): ?>
                                        <?php if (!empty($ods['icone_url'])): ?>
                                            <img src="<?= htmlspecialchars($ods['icone_url']) ?>" alt="ODS" style="max-height: 50px;">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Mídia (Vídeo e Galeria) -->
                    <div class="row mt-4">
                        <!-- Vídeo Pitch -->
                        <?php if (!empty($apresentacao['video_pitch_url'])): ?>
                            <div class="col-12 mb-4">
                                <h5 class="mb-2"><i class="bi bi-play-circle me-1"></i> Pitch (visualização)</h5>
                                <div class="ratio ratio-16x9 shadow-sm">
                                    <iframe src="<?= str_replace("watch?v=", "embed/", htmlspecialchars($apresentacao['video_pitch_url'])) ?>" title="Pitch Video" allowfullscreen class="rounded" loading="lazy"></iframe>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- GALERIA DE FOTOS (CORRIGIDA) -->
                        <?php if (!empty($galeria) && is_array($galeria)): ?>
                        <?php
                            $uid = preg_replace('/[^a-zA-Z0-9]/', '', (string)($negocioid ?? $negocio_id ?? '0'));
                            if ($uid === '') $uid = 'x' . mt_rand(1000, 9999);
                        ?>

                        <div class="col-12">
                            <h5 class="mb-2"><i class="bi bi-images me-1"></i> Galeria de Fotos</h5>

                            <div class="jg"
                                data-jg-uid="<?= $uid ?>"
                                data-jg-target-h="150"
                                data-jg-max-h="210">
                            <?php foreach ($galeria as $i => $img): if (empty($img)) continue; ?>
                                <a href="<?= htmlspecialchars($img) ?>">
                                <img src="<?= htmlspecialchars($img) ?>" alt="Galeria <?= ($i+1) ?>" loading="lazy">
                                </a>
                            <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="jg-lightbox" id="jgLightbox-<?= $uid ?>" aria-hidden="true">
                            <button type="button" class="jg-close" aria-label="Fechar">×</button>
                            <button type="button" class="jg-btn jg-prev" aria-label="Anterior">‹</button>
                            <img class="jg-full" alt="">
                            <button type="button" class="jg-btn jg-next" aria-label="Próximo">›</button>
                            <div class="jg-caption"></div>
                        </div>
                        <?php endif; ?>

                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
