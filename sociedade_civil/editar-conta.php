<?php
session_start();
require_once __DIR__ . '/../app/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare("SELECT * FROM sociedade_civil WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "Usuário não encontrado.";
        exit;
    }
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}

include __DIR__ . '/../app/views/public/header_public.php';
?>

<div class="container my-5">
  <h2>Editar Conta</h2>
  <form action="/sociedade_civil/processar_edicao.php" method="post">
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($user['nome']) ?>" required>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Sobrenome</label>
        <input type="text" name="sobrenome" class="form-control" value="<?= htmlspecialchars($user['sobrenome']) ?>" required>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Celular</label>
      <input type="text" name="celular" class="form-control" value="<?= htmlspecialchars($user['celular']) ?>">
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Cidade</label>
        <input type="text" name="cidade" class="form-control" value="<?= htmlspecialchars($user['cidade']) ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Estado</label>
        <input type="text" name="estado" class="form-control" value="<?= htmlspecialchars($user['estado']) ?>">
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Data de Nascimento</label>
      <input type="date" name="data_nascimento" class="form-control" value="<?= htmlspecialchars($user['data_nascimento']) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Profissão</label>
      <input type="text" name="profissao" class="form-control" value="<?= htmlspecialchars($user['profissao']) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Organização</label>
      <input type="text" name="organizacao" class="form-control" value="<?= htmlspecialchars($user['organizacao']) ?>">
    </div>

    <button type="submit" class="btn btn-success">Salvar Alterações</button>
  </form>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>