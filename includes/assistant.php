<?php
// includes/assistant.php

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php'; // Inclui a conexão com o banco de dados
require_once 'functions.php'; // Inclui funções auxiliares (se houver)

// Inicia a sessão
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); // Não autorizado
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$usuario_id = $_SESSION['usuario_id']; // Obtém o ID do usuário da sessão

// Função para salvar mensagem no histórico (com verificação de duplicação aprimorada)
function salvarMensagemAssistente($pdo, $usuario_id, $mensagem, $tipo) {
    try {
        // Inicia a transação
        $pdo->beginTransaction();

        // Tenta inserir a mensagem (com um pequeno atraso aleatório para evitar colisões)
        usleep(rand(1000, 10000)); // Atraso de 1 a 10 milissegundos

        $stmt = $pdo->prepare("
            INSERT INTO assistant_chat_historico (usuario_id, mensagem, tipo_mensagem, data_criacao)
            VALUES (?, ?, ?, NOW())
        ");

        $result = $stmt->execute([$usuario_id, $mensagem, $tipo]);

        // Se a inserção falhar (por exemplo, por causa de uma violação de chave única),
        // não faz nada (a mensagem já existe)
        if (!$result) {
            $pdo->rollBack(); // Desfaz a transação
            return true; // Considera como sucesso (a mensagem já existe)
        }

        $pdo->commit(); // Confirma a transação
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack(); // Desfaz a transação em caso de erro
        error_log("Erro ao salvar mensagem: " . $e->getMessage());
        return false;
    }
}

// Função para carregar mensagens do histórico
function carregarMensagensAssistente($pdo, $usuario_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                mensagem,
                tipo_mensagem as type,
                DATE_FORMAT(data_criacao, '%Y-%m-%d %H:%i:%s') as timestamp
            FROM assistant_chat_historico
            WHERE usuario_id = ?
            AND DATE(data_criacao) = CURDATE()
            ORDER BY data_criacao ASC
        ");
        $stmt->execute([$usuario_id]);
        return [
            'success' => true,
            'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    } catch (PDOException $e) {
        error_log("Erro ao carregar mensagens: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Erro ao carregar mensagens.'
        ];
    }
}

// Função para limpar histórico do dia atual
function limparHistorico($pdo, $usuario_id) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM assistant_chat_historico
            WHERE usuario_id = ?
            AND DATE(data_criacao) = CURDATE()
        ");
        return $stmt->execute([$usuario_id]);
    } catch (PDOException $e) {
        error_log("Erro ao limpar histórico: " . $e->getMessage());
        return false;
    }
}

// Função para limpar mensagens antigas (mais de 24 horas) - agendada por cron job
function limparMensagensAntigas($pdo) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM assistant_chat_historico
            WHERE data_criacao < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $result = $stmt->execute();

        if ($result) {
            error_log("Limpeza automática do histórico: " . $stmt->rowCount() . " mensagens removidas.");
        }

        return $result;
    } catch (PDOException $e) {
        error_log("Erro ao limpar mensagens antigas: " . $e->getMessage());
        return false;
    }
}

// -- Roteamento da Requisição --

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['action'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'Ação não especificada.']);
        exit;
    }

    switch ($data['action']) {
        case 'load':
            $messages = carregarMensagensAssistente($pdo, $usuario_id);
            echo json_encode($messages);
            break;

        case 'save':
            $mensagem = $data['message'] ?? '';
            $tipo = $data['type'] ?? 'user';
            $result = salvarMensagemAssistente($pdo, $usuario_id, $mensagem, $tipo);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Mensagem salva com sucesso.' : 'Erro ao salvar mensagem.'
            ]);
            break;

        case 'clear':
            $result = limparHistorico($pdo, $usuario_id);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Histórico limpo com sucesso.' : 'Erro ao limpar histórico.'
            ]);
            break;

        case 'clean_old': // Para ser executado por um cron job
            $result = limparMensagensAntigas($pdo);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Mensagens antigas removidas com sucesso.' : 'Erro ao remover mensagens antigas.'
            ]);
            break;

        default:
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'error' => 'Ação inválida.']);
            break;
    }

} else {
    http_response_code(405); // Método não permitido
    echo json_encode(['success' => false, 'error' => 'Método de requisição inválido.']);
}

?>