<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$empreendedorId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /empreendedores/meus-negocios.php');
    exit;
}

$negocioId = (int)($_POST['negocio_id'] ?? 0);
$acao = $_POST['acao'] ?? 'publicar'; 
$motivo = $_POST['motivo'] ?? 'oculto'; // Pega o motivo do modal (oculto ou encerrado)

if ($negocioId <= 0) {
    header('Location: /empreendedores/meus-negocios.php');
    exit;
}

$config = require __DIR__ . '/../app/config/db.php';

try {
  $pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (PDOException $e) {
  die('Erro ao conectar ao banco.');
}

$colDono = 'empreendedor_id';

$stmt = $pdo->prepare("SELECT id, publicado_vitrine FROM negocios WHERE id = ? AND {$colDono} = ? LIMIT 1");
$stmt->execute([$negocioId, $empreendedorId]);
$negocio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$negocio) {
  die('Negócio não encontrado ou sem permissão.');
}

if ($acao === 'remover') {
    // Se escolheu "Esse negócio não existe mais", o status vira encerrado. Senão, continua ativo.
    $statusOperacional = ($motivo === 'encerrado') ? 'encerrado' : 'ativo';
    
    $stmt = $pdo->prepare("UPDATE negocios SET publicado_vitrine = 0, status_operacional = ? WHERE id = ? AND {$colDono} = ?");
    $stmt->execute([$statusOperacional, $negocioId, $empreendedorId]);
    header('Location: /empreendedores/meus-negocios.php?ok=removido');
} else {
    // Se publicar, garantimos que ele volte a ficar 'ativo' caso estivesse encerrado
    $stmt = $pdo->prepare("
      UPDATE negocios
      SET publicado_vitrine = 1,
          status_operacional = 'ativo',
          publicado_em = COALESCE(publicado_em, NOW()),
          etapa_atual = 'publicado',
          inscricao_completa = 1
      WHERE id = ? AND {$colDono} = ?
    ");
    $stmt->execute([$negocioId, $empreendedorId]);
    header('Location: /empreendedores/meus-negocios.php?ok=publicado');
}
exit;
