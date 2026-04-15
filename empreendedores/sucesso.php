<?php
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);

session_start();
// resto do código...

// Verifica se existe sessão de empreendedor
if (empty($_SESSION['empreendedor_id'])) {
    http_response_code(403);
    die("Acesso negado. Somente empreendedores podem acessar esta página.");
}

// Recupera dados da sessão
$nome       = $_SESSION['empreendedor_nome'] ?? '';
$email      = $_GET['email'] ?? ($_SESSION['empreendedor_email'] ?? '');
$ehFundador = $_SESSION['eh_fundador'] ?? 'Não';

// Inclui o header específico de empreendedor
include __DIR__ . '/../app/views/empreendedor/header.php';
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
          <h2 class="h5 mb-0">Cadastro concluído</h2>
        </div>
        <div class="card-body">
          <?php if ($ehFundador === 'Sim'): ?>
            <p class="lead">🎉 Parabéns <?= htmlspecialchars($nome) ?>!</p>
            <p>Seu cadastro como <strong>fundador</strong> foi concluído com sucesso.</p>
            <p>Agora você poderá iniciar o cadastro do seu negócio e, se desejar, adicionar cofundadores.</p>
          <?php else: ?>
            <p class="lead">🎉 Parabéns <?= htmlspecialchars($nome) ?>!</p>
            <p>Seu cadastro foi concluído com sucesso.</p>
            <p>Na próxima etapa, você poderá cadastrar o negócio e informar quem são os fundadores.</p>
          <?php endif; ?>

          <hr>
          <p>Um e-mail de confirmação foi enviado para <strong><?= htmlspecialchars($email) ?></strong>.</p>
          <a href="/negocios/etapa1_dados_negocio.php" class="btn btn-primary">Cadastrar meu negócio</a>
        </div>

      </div>
    </div>
  </div>
</div>

<?php
// Inclui o footer específico de empreendedor
include __DIR__ . '/../app/views/empreendedor/footer.php';
?>