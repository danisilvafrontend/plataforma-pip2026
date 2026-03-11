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
  <h2>Minha Conta</h2>
  <p><strong>Nome:</strong> <?= htmlspecialchars($user['nome']) ?> <?= htmlspecialchars($user['sobrenome']) ?></p>
  <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
  <p><strong>Celular:</strong> <?= htmlspecialchars($user['celular']) ?></p>
  <p><strong>Cidade/Estado:</strong> <?= htmlspecialchars($user['cidade']) ?> - <?= htmlspecialchars($user['estado']) ?></p>
  <p><strong>Data de Nascimento:</strong> <?= htmlspecialchars($user['data_nascimento']) ?></p>
  <p><strong>Profissão:</strong> <?= htmlspecialchars($user['profissao']) ?></p>
  <p><strong>Organização:</strong> <?= htmlspecialchars($user['organizacao']) ?></p>

  <a href="/sociedade_civil/editar-conta.php" class="btn btn-primary">Editar Conta</a>
</div>

<?php include __DIR__ . '/../app/views/public/footer_public.php'; ?>