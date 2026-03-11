<?php
// app/views/emails/new_user.txt.php
// Variáveis: $nome, $email, $tempPassword, $role, $appName, $loginUrl
?>
Olá <?= $nome ?>

Sua conta foi criada em <?= $appName ?>.

E-mail: <?= $email ?>
Senha temporária: <?= $tempPassword ?>
Role: <?= $role ?>

Acesse: <?= $loginUrl ?>

Por favor, troque sua senha no primeiro acesso.

Atenciosamente,
<?= $appName ?>