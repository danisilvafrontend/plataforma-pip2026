<?php if (!empty($negociosDestaque)): ?>
<section class="py-5">
    <div class="container">
        <!-- Grid de negócios -->
        <div class="row">
            <?php foreach ($negociosDestaque as $n): ?>
                <div class="col-md-4 mb-4"> <!-- Coluna limpa, sem overflow -->
                    
                    <!-- O CARD: Aqui entram as classes position-relative e overflow-hidden -->
                    <div class="card d-flex justify-content-between h-100 p-3 shadow-sm position-relative overflow-hidden">
                        
                        <!-- A Faixa Diagonal (Ajustada para textos longos) -->
                        <div class="position-absolute" style="top: -5px; left: -5px; width: 130px; height: 130px; z-index: 10;">
                            <div class="text-bg-primary bg-opacity-50 text-center shadow-sm text-uppercase d-flex align-items-center justify-content-center" 
                                style="position: absolute; top: 30px; left: -40px; width: 180px; height: 30px; transform: rotate(-45deg); font-size: 0.6rem; font-weight: bold; line-height: 1;">
                                <span class="d-inline-block px-2" style="max-width: 100%; white-space: normal;">
                                    <?= htmlspecialchars($n['categoria'] ?? '') ?>
                                </span>
                            </div>
                        </div>

                        <div class="row d-flex align-items-center">
                            <div class="col-md-4 mb-2 text-center">
                                <!-- A Imagem do Logotipo (removi o overflow daqui também) -->
                                <?php if (!empty($n['logo_negocio'])): ?>
                                    <img src="<?= htmlspecialchars($n['logo_negocio']) ?>" 
                                        class="card-logo mt-4 mb-3 position-relative" 
                                        style="z-index: 1;"
                                        alt="Logo do negócio">
                                <?php endif; ?>
                            </div>

                            <div class="col-md-8 mb-2 text-center">
                                <h5 class="card-title text-center"><?= htmlspecialchars($n['nome_fantasia']) ?></h5>                        
                                <span class="small-muted"><?= htmlspecialchars($n['municipio']) ?>/<?= htmlspecialchars($n['estado']) ?></span> 
                                <div class="d-flex justify-content-center">                  
                                    <span class="badge m-1 text-wrap text-bg-primary text-center"> <?= htmlspecialchars($n['eixo_tematico_nome'] ?? '') ?> </span>  
                                </div> 
                            </div>
                        </div>

                        <div class="row d-flex align-items-center">
                            <div class="col-md-3 mb-2">
                                <div class="d-flex justify-content-center">
                                    <?php if (!empty($n['icone_url'])): ?>
                                        <img src="<?= htmlspecialchars($n['icone_url']) ?>" alt="ODS" style="max-height:50px;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-9 mb-2">
                                <blockquote class="apresentacao-quote fst-italic text-primary border-start border-4 ps-3">
                                    <i class="bi bi-quote"></i> <?= htmlspecialchars($n['frase_negocio']) ?>
                                </blockquote>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <div class="d-grid">
                                    <a href="/negocio.php?id=<?= $n['id'] ?>" class="btn btn-outline-primary mt-2">Ver negócio</a>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="d-grid">
                                    <a href="/negocio.php?id=<?= $n['id'] ?>" class="btn btn-outline-secondary mt-2">Apoiar</a>
                                </div>
                            </div>
                        </div>
                    </div> <!-- Fim do Card -->

                </div> <!-- Fim da Coluna -->
            <?php endforeach; ?>
        </div>

    </div>
</section>
<?php endif; ?>