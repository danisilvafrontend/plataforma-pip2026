<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Volte a se engajar</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f7f7f7; margin:0; padding:0; }
    .container { max-width:600px; margin:20px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
    .header { background:#ffc107; color:#000; text-align:center; padding:20px; }
    .content { padding:20px; color:#333; line-height:1.6; }
    .cta { text-align:center; margin:30px 0; }
    .cta a { background:#28a745; color:#fff; padding:12px 24px; border-radius:4px; text-decoration:none; font-weight:bold; }
    .footer { background:#f0f0f0; text-align:center; padding:15px; font-size:12px; color:#666; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header"><h1>Impactos Positivos</h1></div>
    <div class="content">
      <p>Olá <?= htmlspecialchars($nome) ?>,</p>
      <p>Já faz <strong><?= $dias ?></strong> dias desde seu último login. Queremos muito ver você novamente!</p>
      <p>Confira as novidades e benefícios exclusivos que preparamos para reativar sua participação.</p>
      <div class="cta"><a href="https://pip2026.dscriacaoweb.com.br/">Descubra as novidades</a></div>
    </div>
    <div class="footer">© <?= date('Y') ?> Impactos Positivos</div>
  </div>
</body>
</html>