<?php
class GeminiChat {
    private $apiKey;
    private $pdo;
    private $userContext;
    private $usuario_id;

    public function __construct($pdo, $apiKey, $usuario_id) {
        $this->pdo = $pdo;
        $this->apiKey = $apiKey;
        $this->usuario_id = $usuario_id;
        $this->loadContext();
    }

    private function loadContext() {
        $stmt = $this->pdo->prepare("
            SELECT dados 
            FROM chat_contextos 
            WHERE usuario_id = ? 
            ORDER BY versao DESC 
            LIMIT 1
        ");
        $stmt->execute([$this->usuario_id]);
        $this->userContext = $stmt->fetch(PDO::FETCH_ASSOC)['dados'] ?? '{}';
    }

    public function sendMessage($message) {
        try {
            // Registra mensagem do usuário
            $this->saveMessage($message, 'usuario');

            // Chama a API do Gemini
            $response = $this->callGeminiAPI($message);

            // Registra resposta do assistente
            $this->saveMessage($response, 'assistente');

            return $response;
        } catch (Exception $e) {
            error_log("Erro no chat: " . $e->getMessage());
            throw $e;
        }
    }

    private function saveMessage($message, $tipo) {
        $stmt = $this->pdo->prepare("
            INSERT INTO chat_conversations 
            (usuario_id, mensagem, tipo, contexto) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->usuario_id,
            $message,
            $tipo,
            $this->userContext
        ]);
    }

    private function callGeminiAPI($message) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $this->apiKey;
        
        $data = [
            "contents" => [
                [
                    "parts"=> [
                        ["text" => $message]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Desculpe, não consegui processar sua mensagem.';
    }
}
?>