<?php
// app/views/emails/new_empreendedor.php
// Variáveis esperadas: $nome, $email, $tempPassword, $role, $appName, $loginUrl

// Para empreendedores, o login é o padrão (/login.php)
$loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
          . ($_SERVER['HTTP_HOST'] ?? 'seusite')
          . '/login.php';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Bem-vindo(a) à <?= htmlspecialchars($appName, ENT_QUOTES) ?></title>
  <style>
    body { font-family: Arial, sans-serif; background:#f6f9fc; margin:0; padding:0; color:#333; }
    .container { max-width:600px; margin:40px auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
    h2 { color:#2c3e50; }
    table { width:100%; margin:20px 0; border-collapse:collapse; }
    table td { padding:10px; border:1px solid #eee; background:#fafafa; }
    .button { display:inline-block; padding:12px 24px; margin-top:20px; background:#007bff; color:#fff !important; text-decoration:none; border-radius:5px; font-weight:bold; }
    .footer { margin-top:30px; font-size:12px; color:#888; text-align:center; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Olá <?= htmlspecialchars($nome, ENT_QUOTES) ?>,</h2>
    <p>Um administrador da <strong><?= htmlspecialchars($appName, ENT_QUOTES) ?></strong> acaba de criar o seu perfil de <strong>Empreendedor</strong> na nossa plataforma.</p>
    
    <p>A partir de agora, você já pode acessar o sistema para completar os seus dados e iniciar o cadastro do seu negócio de impacto.</p>

    <table>
      <tr><td><strong>E-mail de acesso</strong></td><td><?= htmlspecialchars($email, ENT_QUOTES) ?></td></tr>
      <tr><td><strong>Senha temporária</strong></td><td><?= htmlspecialchars($tempPassword, ENT_QUOTES) ?></td></tr>
    </table>

    <p style="text-align:center;">
      <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES) ?>" class="button">Acessar minha conta</a>
    </p>

    <p><strong>Atenção:</strong> Por questões de segurança, recomendamos que você troque sua senha no seu primeiro acesso.</p>

    <p>Atenciosamente,<br>Equipe <?= htmlspecialchars($appName, ENT_QUOTES) ?></p>

    <div class="footer">
      &copy; <?= date('Y') ?> <?= htmlspecialchars($appName, ENT_QUOTES) ?>. Todos os direitos reservados.
    </div>
  </div>
</body>
</html>
