<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; color: #333; background-color: #f9f9f9; }
    .container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
    h2 { color: #2c3e50; }
    p { line-height: 1.6; }
    .btn {
      display: inline-block;
      padding: 12px 24px;
      background: #28a745;
      color: #fff;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
    }
    .footer { font-size: 12px; color: #777; margin-top: 20px; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Bem-vindo(a), <?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($sobrenome, ENT_QUOTES, 'UTF-8') ?>!</h2>

    <p>É uma alegria ter você conosco na <strong>Plataforma Impactos Positivos</strong>.</p>

    <p>Seu cadastro foi realizado com sucesso utilizando o e-mail: 
      <strong><?= htmlspecialchars($email) ?></strong>.
    </p>

    <p>
      Ao cadastrar seu negócio, você dá o primeiro passo para ampliar sua visibilidade e 
      participar de iniciativas que reconhecem e aceleram empreendedores comprometidos com 
      <strong>impacto social, ambiental e de governança (ESG)</strong>.
    </p>

    <p>
      Nossa plataforma conecta você a oportunidades únicas:
      <ul>
        <li><strong>Premiação</strong> que valoriza negócios de impacto;</li>
        <li><strong>Projetos de aceleração</strong> para impulsionar seu crescimento;</li>
        <li><strong>Rede de empreendedores</strong> que compartilham experiências e aprendizados.</li>
      </ul>
    </p>

    <p>
      Estamos aqui para apoiar sua jornada e dar visibilidade ao seu trabalho. 
      Clique no botão abaixo para acessar sua conta:
    </p>

    <p style="text-align: center;">
      <a href="<?= htmlspecialchars($base_url) ?>/empreendedores/dashboard.php" class="btn">
        Acessar minha conta
      </a>
    </p>

    <p>
      Se você não estiver logado, será redirecionado para a página de login. 
      Caso já esteja logado, irá direto para o painel.
    </p>

    <div class="footer">
      <p>Este é um e-mail automático, por favor não responda.</p>
      <p>&copy; <?= date('Y') ?> Plataforma Impactos Positivos</p>
    </div>
  </div>
</body>
</html>
