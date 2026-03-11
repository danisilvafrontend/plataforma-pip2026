<?php
// app/views/emails/new_empreendedor.txt.php
// Variáveis: $nome, $email, $tempPassword, $role, $appName, $loginUrl
?>
Olá <?= $nome ?>!

Um administrador da <?= $appName ?> acaba de criar o seu perfil de Empreendedor na nossa plataforma.
A partir de agora, você já pode acessar o sistema para completar os seus dados e iniciar o cadastro do seu negócio de impacto.

--- SUAS CREDENCIAIS DE ACESSO ---
E-mail: <?= $email ?>
Senha temporária: <?= $tempPassword ?>

Para fazer o login, acesse o link:
<?= $loginUrl ?>


ATENÇÃO: Por questões de segurança, troque sua senha no primeiro acesso.

Atenciosamente,
Equipe <?= $appName ?>
