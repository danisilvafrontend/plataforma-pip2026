<?php
// localize e require o Database e UserModel conforme sua estrutura
require_once __DIR__ . '/../app/services/Database.php';
require_once __DIR__ . '/../app/models/UserModel.php';
try {
  $um = new UserModel();
  echo "UserModel criado\n";
  echo "count() = " . htmlspecialchars((string)$um->count('')) . "\n";
  $rows = $um->getAll(1,10,'');
  echo "<pre>" . htmlspecialchars(var_export($rows, true)) . "</pre>";
} catch (Throwable $e) {
  echo "Erro model: " . htmlspecialchars($e->getMessage());
}