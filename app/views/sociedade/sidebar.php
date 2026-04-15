<?php
$nomeCompletoSidebar = $nomeCompletoSidebar ?? 'Usuário';
$emailSidebar = $emailSidebar ?? '';
$iniciaisSidebar = $iniciaisSidebar ?? 'SC';
$tipoContaSidebar = $tipoContaSidebar ?? 'Sociedade Civil';
$menuAtivoSidebar = $menuAtivoSidebar ?? 'meus-dados';
?>

<aside class="conta-sidebar-card">
    <div class="conta-sidebar-top">
        <div class="conta-avatar"><?= htmlspecialchars($iniciaisSidebar) ?></div>
        <h2 class="conta-nome"><?= htmlspecialchars($nomeCompletoSidebar) ?></h2>
        <p class="conta-email"><?= htmlspecialchars($emailSidebar) ?></p>

        <span class="conta-badge">
            <i class="bi bi-people-fill"></i>
            <?= htmlspecialchars($tipoContaSidebar) ?>
        </span>
    </div>

    <div class="list-group list-group-flush conta-menu">
        <a href="minha_conta.php"
           class="list-group-item list-group-item-action <?= $menuAtivoSidebar === 'meus-dados' ? 'active' : '' ?>">
            <i class="bi bi-person-vcard me-2"></i>Meus Dados
        </a>
        <a href="editar_interesse.php"
           class="list-group-item list-group-item-action <?= $menuAtivoSidebar === 'meus-interesses' ? 'active' : '' ?>">
            <i class="bi bi-sliders me-2"></i></i>Editar Interesses e Perfil
        </a>

        <a href="#"
           class="list-group-item list-group-item-action <?= $menuAtivoSidebar === 'meus-votos' ? 'active' : '' ?>">
            <i class="bi bi-star me-2"></i>Meus Votos
            <small>Em breve</small>
        </a>

        <a href="#"
           class="list-group-item list-group-item-action <?= $menuAtivoSidebar === 'favoritos' ? 'active' : '' ?>">
            <i class="bi bi-heart me-2"></i>Negócios Favoritos
            <small>Em breve</small>
        </a>

        <a href="logout.php" class="list-group-item list-group-item-action conta-sair">
            <i class="bi bi-box-arrow-right me-2"></i>Sair
        </a>
    </div>
</aside>