<?php
// login_debug.php — Remova após usar
declare(strict_types=1);

// Mostra erros apenas temporariamente para debug
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// mostra o caminho do script e includes esperados
echo "<h3>Debug — info de paths</h3>";
echo "<p>__DIR__: " . htmlspecialchars(__DIR__) . "</p>";
echo "<p>Procura app em: " . realpath(__DIR__ . '/../app') . "</p>";
echo "<p>Procura db.php em: " . realpath(__DIR__ . '/../app/config/db.php') . "</p>";

try {
    $dbPath = __DIR__ . '/../app/config/db.php';
    if (!is_file($dbPath)) {
        throw new RuntimeException("db.php não encontrado em: $dbPath");
    }
    $cfg = require $dbPath;
    echo "<pre>db config: " . htmlspecialchars(var_export($cfg, true)) . "</pre>";
} catch (Throwable $e) {
    echo "<h4>Erro ao incluir db.php</h4>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

// Teste de conexão PDO
try {
    $config = require __DIR__ . '/../app/config/db.php';
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'], $config['port'] ?? 3306, $config['dbname'], $config['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    echo "<p>Conexão PDO OK. Banco: " . htmlspecialchars($pdo->query('select database()')->fetchColumn()) . "</p>";
} catch (Throwable $e) {
    echo "<h4>Erro de conexão PDO</h4><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}