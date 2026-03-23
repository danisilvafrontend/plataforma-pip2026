<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id = (int)($_POST['negocio_id'] ?? 0);
$eixo_principal_id = (int)($_POST['eixo_principal'] ?? 0);
$subareas = $_POST['subareas'] ?? [];

$errors = [];

if ($negocio_id === 0) {
    $errors[] = "Negócio inválido.";
}
if ($eixo_principal_id === 0) {
    $errors[] = "Selecione um eixo temático.";
}
if (empty($subareas)) {
    $errors[] = "Selecione pelo menos uma subárea.";
}

if (!empty($errors)) {
    $_SESSION['errors_etapa3'] = $errors;
    header("Location: /negocios/etapa3_eixo_tematico.php?id=" . $negocio_id);
    exit;
}

// Atualiza eixo principal e updated_at (removido o etapa_atual = 4)
$stmt = $pdo->prepare("UPDATE negocios SET eixo_principal_id = ?, updated_at = NOW() WHERE id = ? AND empreendedor_id = ?");
$stmt->execute([$eixo_principal_id, $negocio_id, $_SESSION['user_id']]);


// Remove subáreas antigas
$stmt = $pdo->prepare("DELETE FROM negocio_subareas WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);

// Insere novas subáreas (IDs vindos direto do formulário)
$stmt = $pdo->prepare("INSERT INTO negocio_subareas (negocio_id, subarea_id) VALUES (?, ?)");
foreach ($subareas as $subarea_id) {
    $stmt->execute([$negocio_id, (int)$subarea_id]);
}


// --------- Redirecionamento Inteligente ---------
$modo = $_POST['modo'] ?? 'cadastro';

// PRIMEIRO: Busca como o negócio está AGORA no banco
$stmtProgresso = $pdo->prepare("SELECT etapa_atual, inscricao_completa FROM negocios WHERE id = ?");
$stmtProgresso->execute([$negocio_id]);
$progresso = $stmtProgresso->fetch(PDO::FETCH_ASSOC);

if ($modo === 'cadastro') {
    // Modo Cadastro: Atualiza a etapa (neste caso da 2 para a 3) SOMENTE SE ainda não passou por ela
    $etapaAtualNoBanco = (int)($progresso['etapa_atual'] ?? 1);
    
    if ($etapaAtualNoBanco < 4) {
        $stmtUpdate = $pdo->prepare("
            UPDATE negocios 
            SET etapa_atual = 4, updated_at = NOW() 
            WHERE id = ? AND empreendedor_id = ?
        ");
        $stmtUpdate->execute([$negocio_id, $_SESSION['user_id']]);
    }

    // Avança para a PRÓXIMA etapa
    header("Location: /negocios/etapa4_ods.php?id=" . $negocio_id);
    exit;
    
} else {
    // Modo Edição: Para onde enviamos o usuário agora?
    
    if (!empty($progresso['inscricao_completa'])) {
        // Já completou tudo = volta para confirmação
        header("Location: /negocios/confirmacao.php?id=" . $negocio_id);
        exit;
    } else {
        // Ainda em andamento = volta para a etapa onde parou
        $rotas_etapas = [
            1 => '/negocios/etapa1_dados_negocio.php',
            2 => '/negocios/etapa2_fundadores.php',
            3 => '/negocios/etapa3_eixo_tematico.php',
            4 => '/negocios/etapa4_ods.php',    
            5 => '/negocios/etapa5_apresentacao.php',
            6 => '/negocios/etapa6_financeiro.php',
            7 => '/negocios/etapa7_impacto.php',
            8 => '/negocios/etapa8_visao.php',
            9 => '/negocios/etapa9_documentacao.php',
            10 => '/negocios/confirmacao.php'
        ];

        $etapaParada = (int)($progresso['etapa_atual'] ?? 1);
        
        if (isset($rotas_etapas[$etapaParada])) {
            header("Location: " . $rotas_etapas[$etapaParada] . "?id=" . $negocio_id);
        } else {
            header("Location: /empreendedores/meus-negocios.php");
        }
        exit;
    }
}
