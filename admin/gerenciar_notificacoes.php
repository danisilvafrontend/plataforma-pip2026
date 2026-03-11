<?php
/**
 * Sistema de Notificação e Rastreamento de Atualizações
 * - Envia notificação aos empreendedores importados
 * - Rastreia quando entram (primeiro acesso)
 * - Rastreia quando atualizam cadastro
 * - Gera relatórios de engajamento
 */

require_once 'config.php';

class GerenciadorNotificacoes {
    
    private $pdo;
    private $email_remetente = 'noreply@pip2026.com.br';
    private $nome_remetente = 'PIP 2026 - Plataforma de Impacto';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Envia notificação a um empreendedor importado
     * Pode ser chamado manualmente pelo admin
     */
    public function enviarNotificacao($usuario_id) {
        try {
            // Busca dados do usuário
            $usuario = $this->buscarUsuario($usuario_id);
            if (!$usuario) {
                throw new Exception("Usuário não encontrado");
            }
            
            // Busca se foi importado
            $importacao = $this->buscarImportacao($usuario_id);
            if (!$importacao) {
                throw new Exception("Usuário não é importado");
            }
            
            // Verifica se já foi notificado
            if ($importacao['email_notificacao_enviado']) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Notificação já foi enviada para este usuário'
                ];
            }
            
            // Gera link temporário (ou usa senha) para reset
            $token_reset = $this->gerarTokenReset($usuario_id);
            $link_acesso = "https://pip2026.dscriacaoweb.com.br/login.php?reset_token=" . $token_reset;
            
            // Prepara email
            $assunto = "Bem-vindo à Plataforma PIP 2026 - Complete seu Cadastro!";
            $corpo_html = $this->gerarEmailHTML($usuario, $link_acesso);
            
            // Envia email
            $enviado = $this->enviarEmail($usuario['email'], $assunto, $corpo_html);
            
            if ($enviado) {
                // Registra que notificação foi enviada
                $sql = "UPDATE usuarios_importacao 
                        SET email_notificacao_enviado = 1, 
                            data_email_notificacao = NOW() 
                        WHERE usuario_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$usuario_id]);
                
                // Log de notificação
                $this->logAcao('notificacao_enviada', $usuario_id, null, 
                    "Notificação enviada para: {$usuario['email']}", 'sucesso');
                
                return [
                    'sucesso' => true,
                    'mensagem' => 'Notificação enviada com sucesso',
                    'email' => $usuario['email']
                ];
            } else {
                throw new Exception("Falha ao enviar email");
            }
            
        } catch (Exception $e) {
            $this->logAcao('notificacao_enviada', $usuario_id ?? null, null, 
                "Erro ao enviar notificação: {$e->getMessage()}", 'erro');
            
            return [
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Envia notificação em LOTE para todos os empreendedores não notificados
     */
    public function enviarNotificacaoEmLote() {
        try {
            // Busca usuários que ainda não foram notificados
            $sql = "SELECT usuario_id FROM usuarios_importacao 
                    WHERE email_notificacao_enviado = 0 
                    ORDER BY data_importacao ASC
                    LIMIT 100"; // Processa 100 por vez
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $enviados = 0;
            $erros = 0;
            
            foreach ($usuarios as $user) {
                $resultado = $this->enviarNotificacao($user['usuario_id']);
                if ($resultado['sucesso']) {
                    $enviados++;
                } else {
                    $erros++;
                }
            }
            
            return [
                'sucesso' => true,
                'total_processados' => count($usuarios),
                'enviados' => $enviados,
                'erros' => $erros
            ];
            
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Registra o primeiro acesso de um usuário importado
     * Chamado quando o usuário faz login
     */
    public function registrarPrimeiroAcesso($usuario_id) {
        try {
            $sql = "UPDATE usuarios_importacao 
                    SET status_atualizacao = 'acessou', 
                        data_primeiro_acesso = NOW() 
                    WHERE usuario_id = ? AND status_atualizacao = 'nao_acessou'";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$usuario_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logAcao('cadastro_atualizado', $usuario_id, null, 
                    "Primeiro acesso registrado", 'sucesso');
            }
            
        } catch (Exception $e) {
            error_log("Erro ao registrar primeiro acesso: {$e->getMessage()}");
        }
    }
    
    /**
     * Registra quando um usuário atualiza seu cadastro
     * Chamado ao final de cada processarEtapa
     */
    public function registrarAtualizacaoCadastro($usuario_id, $etapa) {
        try {
            $sql = "UPDATE usuarios_importacao 
                    SET status_atualizacao = 'atualizou_cadastro', 
                        data_ultima_atualizacao = NOW(),
                        observacoes = CONCAT(IFNULL(observacoes, ''), 
                        '\nEtapa ', ?, ' atualizada em ', NOW()) 
                    WHERE usuario_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$etapa, $usuario_id]);
            
            $this->logAcao('cadastro_atualizado', $usuario_id, null, 
                "Etapa $etapa atualizada", 'sucesso');
            
        } catch (Exception $e) {
            error_log("Erro ao registrar atualização: {$e->getMessage()}");
        }
    }
    
    /**
     * Gera relatório de engajamento dos empreendedores importados
     */
    public function gerarRelatorioEngajamento() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status_atualizacao = 'nao_acessou' THEN 1 ELSE 0 END) as nao_acessaram,
                        SUM(CASE WHEN status_atualizacao = 'acessou' THEN 1 ELSE 0 END) as acessaram,
                        SUM(CASE WHEN status_atualizacao = 'atualizou_cadastro' THEN 1 ELSE 0 END) as atualizaram,
                        SUM(CASE WHEN email_notificacao_enviado = 1 THEN 1 ELSE 0 END) as notificados
                    FROM usuarios_importacao";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $dados = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcula porcentagens
            $total = $dados['total'] ?? 0;
            
            return [
                'total' => $total,
                'nao_acessaram' => $dados['nao_acessaram'] ?? 0,
                'acessaram' => $dados['acessaram'] ?? 0,
                'atualizaram' => $dados['atualizaram'] ?? 0,
                'notificados' => $dados['notificados'] ?? 0,
                'percentuais' => [
                    'nao_acessaram' => $total > 0 ? round(($dados['nao_acessaram'] / $total) * 100, 2) : 0,
                    'acessaram' => $total > 0 ? round(($dados['acessaram'] / $total) * 100, 2) : 0,
                    'atualizaram' => $total > 0 ? round(($dados['atualizaram'] / $total) * 100, 2) : 0
                ]
            ];
            
        } catch (Exception $e) {
            return ['erro' => $e->getMessage()];
        }
    }
    
    /**
     * Lista usuários por status
     */
    public function listarPorStatus($status = 'nao_acessou', $limite = 50) {
        try {
            $sql = "SELECT u.id, u.nome, u.email, ui.status_atualizacao, 
                           ui.data_importacao, ui.data_primeiro_acesso, 
                           ui.data_ultima_atualizacao, ui.email_notificacao_enviado
                    FROM usuarios_importacao ui
                    JOIN usuarios u ON ui.usuario_id = u.id
                    WHERE ui.status_atualizacao = ?
                    ORDER BY ui.data_importacao ASC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$status, $limite]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    // ===== MÉTODOS PRIVADOS =====
    
    private function buscarUsuario($usuario_id) {
        $sql = "SELECT id, nome, email FROM usuarios WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function buscarImportacao($usuario_id) {
        $sql = "SELECT * FROM usuarios_importacao WHERE usuario_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function gerarTokenReset($usuario_id) {
        $token = bin2hex(random_bytes(32));
        $hash_token = hash('sha256', $token);
        $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $sql = "UPDATE usuarios SET reset_token = ?, reset_token_expira = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hash_token, $expira, $usuario_id]);
        
        return $token;
    }
    
    private function gerarEmailHTML($usuario, $link_acesso) {
        $nome = $usuario['nome'] ?? 'Empreendedor';
        
        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; }
                .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { background: white; padding: 20px; margin-top: 20px; }
                .cta { background: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; 
                       border-radius: 4px; display: inline-block; margin-top: 20px; }
                .footer { margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Bem-vindo à Plataforma PIP 2026!</h1>
                </div>
                
                <div class='content'>
                    <p>Olá <strong>$nome</strong>,</p>
                    
                    <p>Você foi incluído na <strong>Plataforma de Impacto Positivo 2026</strong> com sucesso!</p>
                    
                    <p>Agora você pode:</p>
                    <ul>
                        <li>Atualizar seus dados de empreendedor</li>
                        <li>Registrar seus negócios de impacto</li>
                        <li>Conectar com outros empreendedores</li>
                        <li>Acessar recursos e ferramentas</li>
                    </ul>
                    
                    <p><strong>Clique no botão abaixo para acessar sua conta:</strong></p>
                    
                    <a href='$link_acesso' class='cta'>Acessar Minha Conta</a>
                    
                    <p style='margin-top: 20px; font-size: 12px;'>
                        Link expira em 24 horas. Se tiver dúvidas, entre em contato com nosso suporte.
                    </p>
                </div>
                
                <div class='footer'>
                    <p>PIP 2026 - Plataforma de Impacto Positivo</p>
                    <p>© 2026 - Todos os direitos reservados</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function enviarEmail($para, $assunto, $corpo_html) {
        require_once 'PHPMailer/PHPMailer.php';
        require_once 'PHPMailer/SMTP.php';
        require_once 'PHPMailer/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.seuservidor.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'seu_email@seuservidor.com';
            $mail->Password = 'sua_senha';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            $mail->setFrom($this->email_remetente, $this->nome_remetente);
            $mail->addAddress($para);
            $mail->Subject = $assunto;
            $mail->isHTML(true);
            $mail->Body = $corpo_html;
            
            return $mail->send();
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    private function logAcao($tipo_acao, $usuario_id, $negocio_id, $descricao, $resultado) {
        try {
            $sql = "INSERT INTO importacao_log 
                    (tipo_acao, usuario_id, negocio_id, descricao, resultado, data_acao) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tipo_acao, $usuario_id, $negocio_id, $descricao, $resultado]);
            
        } catch (Exception $e) {
            error_log("Erro ao registrar log: {$e->getMessage()}");
        }
    }
}

// ===== EXEMPLOS DE USO =====
//
// 1. Enviar notificação para um usuário específico:
//    $gerenciador = new GerenciadorNotificacoes($pdo);
//    $resultado = $gerenciador->enviarNotificacao($usuario_id);
//
// 2. Enviar notificações em lote:
//    $resultado = $gerenciador->enviarNotificacaoEmLote();
//
// 3. Registrar primeiro acesso (em login.php):
//    if ($login_bem_sucedido) {
//        $gerenciador->registrarPrimeiroAcesso($_SESSION['usuario_id']);
//    }
//
// 4. Registrar atualização de cadastro (em processar_etapa1.php):
//    $gerenciador->registrarAtualizacaoCadastro($_SESSION['usuario_id'], 1);
//
// 5. Gerar relatório:
//    $relatorio = $gerenciador->gerarRelatorioEngajamento();
//
?>