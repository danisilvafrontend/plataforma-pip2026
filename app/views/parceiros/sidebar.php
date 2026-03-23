<?php
// app/views/parceiros/sidebar.php

// Pega o nome do arquivo atual para destacar o menu ativo
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <h6 class="text-muted text-uppercase mb-3 fw-bold" style="font-size: 0.8rem;">Área do Parceiro</h6>
        <hr class="mt-0 mb-3 text-muted">
        
        <ul class="nav flex-column gap-2">
            <li class="nav-item">
                <a class="nav-link rounded-3 px-3 py-2 <?= $current_page == 'dashboard.php' ? 'active bg-primary text-white' : 'text-dark' ?>" 
                   href="/parceiros/dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-item mt-2">
                <a class="nav-link rounded-3 px-3 py-2 <?= $current_page == 'editar_dados.php' ? 'active bg-primary text-white' : 'text-dark' ?>" 
                   href="/parceiros/editar_dados.php">
                    <i class="bi bi-person-badge me-2"></i> Editar Dados
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link rounded-3 px-3 py-2 <?= $current_page == 'editar_interesses.php' ? 'active bg-primary text-white' : 'text-dark' ?>" 
                   href="/parceiros/editar_interesses.php">
                    <i class="bi bi-geo-alt me-2"></i> Radar de Interesses
                </a>
            </li>

            <?php 
            // Verifica se o parceiro está com status aprovado/ativo para liberar o perfil
            $stmt_status = $pdo->prepare("SELECT status FROM parceiros WHERE id = ?");
            $stmt_status->execute([$_SESSION['parceiro_id']]);
            $status_parceria = $stmt_status->fetchColumn();

            if ($status_parceria === 'ativo' || $status_parceria === 'aprovado'): 
            ?>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'editar_perfil.php') ? 'active' : '' ?>" href="editar_perfil.php">
                        <i class="bi bi-person-lines-fill me-2"></i> Meu Perfil Público
                    </a>
                </li>
            <?php endif; ?>


            <!-- Os itens abaixo você pode criar no futuro -->
            <li class="nav-item mt-2">
                <a class="nav-link rounded-3 px-3 py-2 text-dark disabled" aria-disabled="true" href="#" >
                    <i class="bi bi-megaphone me-2"></i> Oportunidades
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link rounded-3 px-3 py-2 text-dark disabled" aria-disabled="true" href="#">
                    <i class="bi bi-people me-2"></i> Rede de Impacto
                </a>
            </li>

            <!-- Seção: Documentação -->
            <li class="nav-item mt-3">
                <small class="text-muted text-uppercase fw-bold ms-3" style="font-size: 0.75rem;">Documentação</small>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'visualizar_minha_carta.php') !== false) ? 'active fw-bold text-primary' : 'text-dark' ?>" 
                href="visualizar_minha_carta.php">
                    <i class="bi bi-file-earmark-text me-2 <?= (strpos($_SERVER['REQUEST_URI'], 'visualizar_minha_carta.php') !== false) ? 'text-primary' : 'text-muted' ?>"></i>
                    Minha Carta-Acordo
                </a>
            </li>

            <li class="nav-item mt-4">
                <a class="nav-link rounded-3 px-3 py-2 text-danger fw-semibold" href="/logout.php">
                    <i class="bi bi-box-arrow-left me-2"></i> Sair da Conta
                </a>
            </li>

        </ul>
    </div>
</div>

<style>
/* Estilo sutil pro hover do menu */
.nav-item .nav-link:not(.active):hover {
    background-color: #f8f9fa;
    color: #0d6efd !important;
}
</style>
