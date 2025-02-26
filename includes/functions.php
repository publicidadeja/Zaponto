<?php
// Função para validar número de telefone (DDI + DDD + número)
function validarNumeroTelefone($numero) {
    // Remove todos os caracteres não numéricos
    $numero = preg_replace('/[^0-9]/', '', $numero);
    
    // Verifica se o número tem 10 ou 11 dígitos (sem o código do país)
    $tamanho = strlen($numero);
    return ($tamanho == 10 || $tamanho == 11);
}

// Função para formatar data no padrão brasileiro (dd/mm/yyyy hh:mm:ss)
function formatarData($data) {
    return date('d/m/Y H:i:s', strtotime($data));
}

function verificarNumeroExistente($pdo, $numero, $usuario_id) {
    // Ensure consistent number format
    $numero = preg_replace('/[^0-9]/', '', $numero);
    
    // Add Brazilian country code if not present
    if (strlen($numero) == 10 || strlen($numero) == 11) {
        $numero = '55' . $numero;
    }
    
    $stmt = $pdo->prepare("SELECT nome FROM leads_enviados WHERE numero = ? AND usuario_id = ? LIMIT 1");
    $stmt->execute([$numero, $usuario_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function verificarPeriodoTeste($pdo, $usuario_id) {
    // Primeiro verifica se existe uma assinatura ativa não-trial
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM assinaturas 
        WHERE usuario_id = ? 
        AND status = 'ativo' 
        AND is_trial = 0 
        AND (data_fim IS NULL OR data_fim > NOW())
    ");
    $stmt->execute([$usuario_id]);
    $tem_plano_ativo = $stmt->fetchColumn() > 0;

    // Se já tem um plano ativo não-trial, retorna false
    if ($tem_plano_ativo) {
        return false;
    }

    // Caso contrário, verifica o período de teste
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            DATEDIFF(a.data_fim, NOW()) as dias_restantes
        FROM assinaturas a
        WHERE a.usuario_id = ? 
        AND a.is_trial = 1 
        AND a.status = 'ativo'
        AND a.data_fim > NOW()
        LIMIT 1
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function verificarAssinaturaAtiva($pdo, $usuario_id) {
    // Primeiro tenta buscar uma assinatura não-trial ativa
    $stmt = $pdo->prepare("
        SELECT * FROM assinaturas 
        WHERE usuario_id = ? 
        AND status = 'ativo' 
        AND is_trial = 0
        AND (data_fim IS NULL OR data_fim > NOW())
        LIMIT 1
    ");
    $stmt->execute([$usuario_id]);
    $assinatura = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Se não encontrou assinatura não-trial, busca assinatura trial
    if (!$assinatura) {
        $stmt = $pdo->prepare("
            SELECT * FROM assinaturas 
            WHERE usuario_id = ? 
            AND status = 'ativo' 
            AND is_trial = 1
            AND data_fim > NOW()
            LIMIT 1
        ");
        $stmt->execute([$usuario_id]);
        $assinatura = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return $assinatura;
}

function verificarLimitesUsuario($pdo, $usuario_id) {
    $stmt = $pdo->prepare("
        SELECT a.*, p.* 
        FROM assinaturas a
        JOIN planos p ON a.plano_id = p.id
        WHERE a.usuario_id = ? 
        AND a.status = 'ativo'
        ORDER BY a.data_inicio DESC 
        LIMIT 1
    ");
    $stmt->execute([$usuario_id]);
    $assinatura = $stmt->fetch();
    
    if (!$assinatura) {
        return false;
    }
    
    return [
        'limite_leads' => $assinatura['limite_leads'],
        'limite_mensagens' => $assinatura['limite_mensagens'],
        'tem_ia' => (bool)$assinatura['tem_ia']
    ];
}

function verificarAcessoPermitido($pdo, $usuario_id) {
    $teste = verificarPeriodoTeste($pdo, $usuario_id);
    $assinatura = verificarAssinaturaAtiva($pdo, $usuario_id);
    
    if (!$teste && !$assinatura) {
        header('Location: planos.php');
        exit;
    }
    
    return true;
}

function formatarNumeroWhatsApp($numero) {
    // Remove todos os caracteres não numéricos
    $numero = preg_replace('/[^0-9]/', '', $numero);
    
    // Adiciona código do país se não existir
    if (strlen($numero) == 10 || strlen($numero) == 11) {
        $numero = '55' . $numero;
    }
    
    return $numero;
}

function isHorarioComercial() {
    $hora_atual = (int)date('H');
    $dia_semana = date('N'); // 1 (Segunda) até 7 (Domingo)
    
    // Horário comercial: 8h às 18h, Segunda a Sexta
    return ($hora_atual >= 8 && $hora_atual < 18) && ($dia_semana >= 1 && $dia_semana <= 5);
}


// /includes/functions.php
function buscarNotificacoesComFiltros($pdo, $filtros = []) {
    $where = [];
    $params = [];
    
    if (!empty($filtros['tipo'])) {
        $where[] = "tipo = ?";
        $params[] = $filtros['tipo'];
    }
    
    if (!empty($filtros['data_inicio'])) {
        $where[] = "data_criacao >= ?";
        $params[] = $filtros['data_inicio'];
    }
    
    if (!empty($filtros['data_fim'])) {
        $where[] = "data_criacao <= ?";
        $params[] = $filtros['data_fim'];
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $sql = "SELECT 
        n.*,
        COUNT(DISTINCT usuario_id) as total_usuarios,
        ROUND(AVG(CASE WHEN lida = 1 THEN 1 ELSE 0 END) * 100, 2) as taxa_leitura
        FROM notificacoes n
        $whereClause
        GROUP BY n.id
        ORDER BY n.data_criacao DESC";
        
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function registrarExportacao($pdo, $admin_id, $tipo, $formato, $filtros = null) {
    $stmt = $pdo->prepare("
        INSERT INTO exportacoes_log 
        (admin_id, tipo, formato, filtros) 
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$admin_id, $tipo, $formato, json_encode($filtros)]);
}

function verificarAcessoIA($pdo, $usuario_id) {
    // Check active subscription
    $stmt = $pdo->prepare("
        SELECT a.*, p.tem_ia 
        FROM assinaturas a
        JOIN planos p ON a.plano_id = p.id
        WHERE a.usuario_id = ? 
        AND a.status = 'ativo'
        ORDER BY a.data_inicio DESC 
        LIMIT 1
    ");
    $stmt->execute([$usuario_id]);
    $assinatura = $stmt->fetch();
    
    if (!$assinatura) {
        return false;
    }
    
    return (bool)$assinatura['tem_ia'];
}

function verificarLimitesEnvio($pdo, $usuario_id) {
    // Buscar limites do plano atual
    $stmt = $pdo->prepare("
        SELECT a.*, p.* 
        FROM assinaturas a
        JOIN planos p ON a.plano_id = p.id
        WHERE a.usuario_id = ? 
        AND a.status = 'ativo'
        ORDER BY a.data_inicio DESC 
        LIMIT 1
    ");
    $stmt->execute([$usuario_id]);
    $plano = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Contar envios do mês atual
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM leads_enviados 
        WHERE usuario_id = ? 
        AND MONTH(data_envio) = MONTH(CURRENT_DATE())
        AND YEAR(data_envio) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$usuario_id]);
    $envios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Verifica se o plano é ilimitado
    $is_ilimitado = ($plano['limite_mensagens'] == -1);
    
    return [
        'pode_enviar' => $is_ilimitado ? true : ($envios < $plano['limite_mensagens']),
        'limite_total' => $is_ilimitado ? 'Ilimitado' : (int)$plano['limite_mensagens'],
        'envios_realizados' => (int)$envios,
        'restantes' => $is_ilimitado ? 'Ilimitado' : (int)($plano['limite_mensagens'] - $envios),
        'is_ilimitado' => $is_ilimitado
    ];
}

function renovarLimitesUsuario($pdo, $usuario_id) {
    try {
        $pdo->beginTransaction();
        
        // Buscar informações do plano atual
        $stmt = $pdo->prepare("
            SELECT a.*, p.limite_leads, p.limite_mensagens 
            FROM assinaturas a
            JOIN planos p ON a.plano_id = p.id
            WHERE a.usuario_id = ? AND a.status = 'ativo'
            ORDER BY a.data_inicio DESC LIMIT 1
        ");
        $stmt->execute([$usuario_id]);
        $plano = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plano) {
            throw new Exception('Nenhum plano ativo encontrado');
        }

        // Atualizar limites do usuário
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET leads_disponiveis = ?,
                mensagens_disponiveis = ?,
                ultima_renovacao = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $plano['limite_leads'],
            $plano['limite_mensagens'],
            $usuario_id
        ]);

        // Registrar log de renovação
        $stmt = $pdo->prepare("
            INSERT INTO renovacoes_log 
            (usuario_id, data_renovacao, status, detalhes)
            VALUES (?, NOW(), 'sucesso', ?)
        ");
        
        $stmt->execute([
            $usuario_id,
            json_encode([
                'plano_id' => $plano['plano_id'],
                'limite_leads' => $plano['limite_leads'],
                'limite_mensagens' => $plano['limite_mensagens']
            ])
        ]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Erro ao renovar limites: ' . $e->getMessage());
        return false;
    }
}
?>