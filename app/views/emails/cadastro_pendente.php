<?php
// app/views/emails/cadastro_pendente.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Finalize sua inscrição</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f7f7f7;
      margin: 0;
      padding: 0;
    }
    .email-container {
      max-width: 600px;
      margin: 20px auto;
      background: #ffffff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .header {
      background-color: #0066cc;
      color: #ffffff;
      text-align: center;
      padding: 20px;
    }
    .header h1 {
      margin: 0;
      font-size: 22px;
    }
    .content {
      padding: 20px;
      color: #333333;
      line-height: 1.6;
    }
    .cta {
      text-align: center;
      margin: 30px 0;
    }
    .cta a {
      background-color: #28a745;
      color: #ffffff;
      text-decoration: none;
      padding: 12px 24px;
      border-radius: 4px;
      font-weight: bold;
      display: inline-block;
    }
    .footer {
      background-color: #f0f0f0;
      text-align: center;
      padding: 15px;
      font-size: 12px;
      color: #666666;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="header">
      <h1>Impactos Positivos</h1>
    </div>
    <div class="content">
      <p>Olá <?= htmlspecialchars($nome) ?>,</p>

      <p>Seu negócio <strong><?= htmlspecialchars($nome_fantasia) ?></strong> ainda não concluiu o cadastro na plataforma <strong>Impactos Positivos</strong>.</p>

      <p>Atualmente, ele está parado na etapa <strong><?= htmlspecialchars($etapa_atual) ?></strong>.</p>

      <p>Para garantir sua participação na premiação, finalize sua inscrição clicando no botão abaixo:</p>

      <div class="cta">
        <a href="https://pip2026.dscriacaoweb.com.br/negocios/<?= $etapa_atual ?>.php?id=<?= urlencode($nome_fantasia) ?>">
          Finalizar Cadastro
        </a>
      </div>

      <p>Estamos ansiosos para ver seu negócio brilhar!</p>
    </div>
    <div class="footer">
      © <?= date('Y') ?> Impactos Positivos — Todos os direitos reservados
    </div>
  </div>
</body>
</html>