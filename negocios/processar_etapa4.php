<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$config = require __DIR__ . '/../app/config/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$negocio_id = (int)($_POST['negocio_id'] ?? 0);
$ods_prioritaria = (int)($_POST['ods_prioritaria'] ?? 0);
$ods_relacionadas = $_POST['ods_relacionadas'] ?? [];

$errors = [];

if ($negocio_id === 0) {
    $errors[] = "Negócio inválido.";
}
if ($ods_prioritaria === 0) {
    $errors[] = "Selecione a ODS prioritária.";
}

if (!empty($errors)) {
    $_SESSION['errors_etapa4'] = $errors;
    header("Location: /negocios/etapa4_ods.php?id=" . $negocio_id);
    exit;
}

// Atualiza ODS prioritária na tabela negocios
$stmt = $pdo->prepare("UPDATE negocios SET ods_prioritaria_id = ?, updated_at = NOW() WHERE id = ? AND empreendedor_id = ?");
$stmt->execute([$ods_prioritaria, $negocio_id, $_SESSION['user_id']]);


// Remove ODS relacionadas antigas
$stmt = $pdo->prepare("DELETE FROM negocio_ods WHERE negocio_id = ?");
$stmt->execute([$negocio_id]);

// Insere novas ODS relacionadas
$stmt = $pdo->prepare("INSERT INTO negocio_ods (negocio_id, ods_id) VALUES (?, ?)");
foreach ($ods_relacionadas as $ods_id) {
    $stmt->execute([$negocio_id, (int)$ods_id]);
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
    
    if ($etapaAtualNoBanco < 5) {
        $stmtUpdate = $pdo->prepare("
            UPDATE negocios 
            SET etapa_atual = 5, updated_at = NOW() 
            WHERE id = ? AND empreendedor_id = ?
        ");
        $stmtUpdate->execute([$negocio_id, $_SESSION['user_id']]);
    }

    // Avança para a PRÓXIMA etapa
    header("Location: /negocios/etapa5_financeiro.php?id=" . $negocio_id);
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
            5 => '/negocios/etapa5_financeiro.php',
            6 => '/negocios/etapa6_impacto.php',
            7 => '/negocios/etapa7_visao.php',
            8 => '/negocios/etapa8_apresentacao.php',
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
