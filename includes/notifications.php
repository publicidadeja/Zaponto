<?php


function criarNotificacao($pdo, $usuario_id, $tipo, $titulo, $mensagem) {
    $stmt = $pdo->prepare("INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$usuario_id, $tipo, $titulo, $mensagem]);
}

function buscarNotificacoes($pdo, $usuario_id, $apenas_nao_lidas = true) {
    $query = "SELECT * FROM notificacoes WHERE usuario_id = ?";
    if ($apenas_nao_lidas) {
        $query .= " AND lida = FALSE";
    }
    $query .= " ORDER BY data_criacao DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function marcarNotificacaoComoLida($pdo, $notificacao_id, $usuario_id) {
    $stmt = $pdo->prepare("UPDATE notificacoes SET lida = TRUE, data_leitura = NOW() WHERE id = ? AND usuario_id = ?");
    return $stmt->execute([$notificacao_id, $usuario_id]);
}

function agendarNotificacao($pdo, $titulo, $mensagem, $tipo, $data_agendamento, $segmentacao) {
    $stmt = $pdo->prepare("
        INSERT INTO notificacoes_agendadas 
        (titulo, mensagem, tipo, data_agendamento, segmentacao, status) 
        VALUES (?, ?, ?, ?, ?, 'pendente')
    ");
    return $stmt->execute([$titulo, $mensagem, $tipo, $data_agendamento, $segmentacao]);
}

function getCachedNotifications($pdo, $usuario_id, $tempo_cache = 300) {
    $cache_key = "notifications_" . $usuario_id;
    
    if (isset($_SESSION[$cache_key]) && 
        (time() - $_SESSION[$cache_key]['time']) < $tempo_cache) {
        return $_SESSION[$cache_key]['data'];
    }
    
    $notifications = buscarNotificacoes($pdo, $usuario_id);
    $_SESSION[$cache_key] = [
        'time' => time(),
        'data' => $notifications
    ];
    
    return $notifications;
}

function logNotificationAction($pdo, $acao, $dados) {
    $stmt = $pdo->prepare("
        INSERT INTO logs_notificacoes 
        (acao, dados, data_registro) 
        VALUES (?, ?, NOW())
    ");
    return $stmt->execute([$acao, json_encode($dados)]);
}


function verificarNotificacoes($pdo, $usuario_id) {
    // Verificar plano
    $stmt = $pdo->prepare("SELECT * FROM assinaturas WHERE usuario_id = ? AND status = 'ativo' ORDER BY data_fim DESC LIMIT 1");
    $stmt->execute([$usuario_id]);
    $assinatura = $stmt->fetch();
    
    if ($assinatura && $assinatura['data_fim']) {
        $dias_restantes = floor((strtotime($assinatura['data_fim']) - time()) / (60 * 60 * 24));
        if ($dias_restantes <= 5) {
            criarNotificacao(
                $pdo, 
                $usuario_id, 
                'plano',
                'Seu plano está próximo do vencimento',
                "Seu plano vence em {$dias_restantes} dias. Renove agora para não perder o acesso."
            );
        }
    }
    
    // Verificar limites de envios e leads
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads_enviados WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $total_leads = $stmt->fetch()['total'];
    
    if ($assinatura && $assinatura['limite_leads'] > 0) {
        $percentual_usado = ($total_leads / $assinatura['limite_leads']) * 100;
        if ($percentual_usado >= 80) {
            criarNotificacao(
                $pdo,
                $usuario_id,
                'leads',
                'Limite de leads próximo do fim',
                "Você já utilizou {$percentual_usado}% do seu limite de leads."
            );
        }
    }
}