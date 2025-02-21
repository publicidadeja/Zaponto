<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

// Log function
function writeLog($message) {
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] $message\n";
    file_put_contents(__DIR__ . '/logs/renewals.log', $logMessage, FILE_APPEND);
}

try {
    // Conectar ao banco de dados
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar usuários com assinaturas ativas que precisam de renovação
    $stmt = $pdo->query("
        SELECT u.id 
        FROM usuarios u
        JOIN assinaturas a ON u.id = a.usuario_id
        WHERE a.status = 'ativo'
        AND (u.ultima_renovacao IS NULL 
             OR u.ultima_renovacao < DATE_SUB(NOW(), INTERVAL 1 MONTH))
    ");
    
    $usuarios = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($usuarios as $usuario_id) {
        try {
            if (renovarLimitesUsuario($pdo, $usuario_id)) {
                writeLog("Limites renovados com sucesso para usuário ID: $usuario_id");
            } else {
                writeLog("Falha ao renovar limites para usuário ID: $usuario_id");
            }
        } catch (Exception $e) {
            writeLog("Erro ao processar renovação para usuário ID: $usuario_id - " . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    writeLog("Erro geral no processamento: " . $e->getMessage());
}