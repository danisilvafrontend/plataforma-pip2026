<?php
session_start();
include __DIR__ . '/app/views/public/header_public.php';
?>

<div class="container my-5 text-center">
  <h1 class="mb-4 text-success">Cadastro realizado com sucesso!</h1>
  <p class="lead">Obrigado por se cadastrar na Comunidade Civil. Seu acesso aos diretórios públicos já está disponível.</p>
  
  <div class="mt-4">
    <a href="/" class="btn btn-primary">Ir para a página inicial</a>
    <a href="/cadastro.php" class="btn btn-secondary">Cadastrar outro membro</a>
  </div>
</div>

<?php include __DIR__ . '/app/views/public/footer_public.php'; ?>