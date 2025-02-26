<?php
require_once 'config/database.php';
require_once 'includes/notifications.php';

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

// Log function
function writeLog($message) {
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] $message\n";
    file_put_contents(__DIR__ . '/logs/notifications.log', $logMessage, FILE_APPEND);
}

try {
    // Conectar ao banco de dados
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar notificações agendadas pendentes que já devem ser enviadas
    $stmt = $pdo->prepare("
        SELECT * FROM notificacoes_agendadas 
        WHERE status = 'pendente' 
        AND data_agendamento <= NOW()
    ");
    $stmt->execute();
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($notificacoes as $notificacao) {
        try {
            $pdo->beginTransaction();
            
            // Buscar usuários baseado na segmentação
            $query = "SELECT id FROM usuarios WHERE status = 'ativo'";
            
            if ($notificacao['segmentacao'] === 'plano_ativo') {
                $query .= " AND EXISTS (
                    SELECT 1 FROM assinaturas 
                    WHERE usuario_id = usuarios.id 
                    AND status = 'ativo'
                )";
            } elseif ($notificacao['segmentacao'] === 'plano_vencendo') {
                $query .= " AND EXISTS (
                    SELECT 1 FROM assinaturas 
                    WHERE usuario_id = usuarios.id 
                    AND status = 'ativo' 
                    AND data_fim <= DATE_ADD(NOW(), INTERVAL 5 DAY)
                )";
            }
            
            $stmtUsers = $pdo->query($query);
            $usuarios = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($usuarios)) {
                foreach ($usuarios as $usuario_id) {
                    criarNotificacao(
                        $pdo,
                        $usuario_id,
                        $notificacao['tipo'],
                        $notificacao['titulo'],
                        $notificacao['mensagem']
                    );
                }
                
                // Atualizar status da notificação agendada
                $stmtUpdate = $pdo->prepare("
                    UPDATE notificacoes_agendadas 
                    SET status = 'enviada',
                        data_processamento = NOW() 
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$notificacao['id']]);
                
                writeLog("Notificação ID {$notificacao['id']} enviada com sucesso para " . count($usuarios) . " usuários.");
            } else {
                writeLog("Notificação ID {$notificacao['id']} não teve usuários para envio.");
                
                // Marcar como processada mesmo sem usuários
                $stmtUpdate = $pdo->prepare("
                    UPDATE notificacoes_agendadas 
                    SET status = 'sem_destinatarios',
                        data_processamento = NOW() 
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$notificacao['id']]);
            }
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            writeLog("Erro ao processar notificação ID {$notificacao['id']}: " . $e->getMessage());
            
            // Marcar como falha
            $stmtUpdate = $pdo->prepare("
                UPDATE notificacoes_agendadas 
                SET status = 'falha',
                    data_processamento = NOW() 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$notificacao['id']]);
        }
    }
    
} catch (Exception $e) {
    writeLog("Erro geral no processamento: " . $e->getMessage());
}