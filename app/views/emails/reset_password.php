<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Redefinição de senha</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f6f9fc; margin:0; padding:0; }
    .container { max-width:600px; margin:40px auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
    .header { text-align:center; color:#2c3e50; }
    .button { display:inline-block; padding:12px 24px; margin-top:20px; background:#007bff; color:#fff !important; text-decoration:none; border-radius:5px; font-weight:bold; }
    .footer { margin-top:30px; font-size:12px; color:#888; text-align:center; }
  </style>
</head>
<body>
  <div class="container">
    <h2 class="header">Redefinição de senha</h2>
    <p>Olá <strong><?= htmlspecialchars($nome) ?></strong>,</p>

    <p>Recebemos uma solicitação para redefinir sua senha na <strong>Plataforma Impactos Positivos</strong>.</p>

    <p>Para criar uma nova senha, clique no botão abaixo:</p>

    <p style="text-align:center;">
      <a href="<?= htmlspecialchars($resetUrl) ?>" class="button">Redefinir minha senha</a>
    </p>

    <p>Este link é válido por <strong><?= $expiresHours ?></strong> hora<?= $expiresHours > 1 ? 's' : '' ?>. Após esse período, será necessário solicitar uma nova redefinição.</p>

    <p>Se você não solicitou essa alteração, pode ignorar este e-mail com segurança.</p>

    <div class="footer">
      &copy; <?= date('Y') ?> Plataforma Impactos Positivos. Todos os direitos reservados.
    </div>
  </div>
</body>
</html>