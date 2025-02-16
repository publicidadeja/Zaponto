<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $response = ['success' => false];

    if (!isset($_SESSION['usuario_id'])) {
        $response['error'] = 'Usuário não autenticado';
        echo json_encode($response);
        exit;
    }

    switch ($data['action']) {
        case 'save':
            if (isset($data['message'])) {
                $success = salvarMensagemAssistente($pdo, $_SESSION['usuario_id'], $data['message']);
                $response['success'] = $success;
            }
            break;

            case 'load':
                $mensagens = carregarMensagensAssistente($pdo, $_SESSION['usuario_id']);
                $response = [
                    'success' => true,
                    'messages' => array_map(function($msg) {
                        return [
                            'type' => 'assistant',
                            'mensagem' => $msg['mensagem'],
                            'timestamp' => $msg['data_criacao']
                        ];
                    }, $mensagens)
                ];
                break;
            

        case 'clear':
            $stmt = $pdo->prepare("DELETE FROM assistant_chat_historico WHERE usuario_id = ?");
            $response['success'] = $stmt->execute([$_SESSION['usuario_id']]);
            break;
    }

    echo json_encode($response);
    exit;
}