<?php
require_once __DIR__ . '/../app/services/Database.php';
try {
  $pdo = Database::getInstance();
  echo "Conexão OK<br>";
  $c = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  echo "COUNT(users) = " . htmlspecialchars((string)$c) . "<br>";
  $rows = $pdo->query("SELECT id, nome, email FROM users ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
  echo "<pre>" . htmlspecialchars(var_export($rows, true)) . "</pre>";
} catch (Throwable $e) {
  echo "Erro DB: " . htmlspecialchars($e->getMessage());
}