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
    $stmt = $pdo->prepare("
        SELECT * FROM assinaturas 
        WHERE usuario_id = ? 
        AND status = 'ativo' 
        AND (is_trial = 1 OR plano_id > 0)
        AND (data_fim IS NULL OR data_fim > NOW())
    ");
    $stmt->execute([$usuario_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function verificarLimitesUsuario($pdo, $usuario_id) {
    $assinatura = verificarAssinaturaAtiva($pdo, $usuario_id);
    if (!$assinatura) {
        return false;
    }
    return [
        'limite_leads' => $assinatura['limite_leads'],
        'limite_mensagens' => $assinatura['limite_mensagens'],
        'tem_ia' => $assinatura['tem_ia']
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
?>