<?php
session_start();
include '../includes/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403); // Forbidden
    echo "Acesso negado.";
    exit;
}

// Garante que a execução não exceda um tempo limite razoável
set_time_limit(300); // 5 minutos

// Função para enviar a mensagem (adaptada do código anterior)
function send_message($token, $numero, $mensagem, $arquivo_path = '') {
    $url = 'https://api2.publicidadeja.com.br/api/messages/send';
    $sucesso = true;
    $erro = '';

    // 1. Se tiver arquivo, envia primeiro
    if (!empty($arquivo_path) && file_exists($arquivo_path)) {
        $arquivo_nome = preg_replace('/[^a-zA-Z0-9\.]/', '', basename($arquivo_path));
        $cfile = new CURLFile($arquivo_path, mime_content_type($arquivo_path), $arquivo_nome);

        $post_data_media = [
            'number' => $numero,
            'medias' => $cfile
        ];

        $headers_media = [
            'Authorization: Bearer ' . $token,
            'Content-Type: multipart/form-data'
        ];

        // Envia mídia
        $ch_media = curl_init();
        curl_setopt($ch_media, CURLOPT_URL, $url);
        curl_setopt($ch_media, CURLOPT_POST, true);
        curl_setopt($ch_media, CURLOPT_HTTPHEADER, $headers_media);
        curl_setopt($ch_media, CURLOPT_POSTFIELDS, $post_data_media);
        curl_setopt($ch_media, CURLOPT_RETURNTRANSFER, true);

        $response_media = curl_exec($ch_media);
        $http_code_media = curl_getinfo($ch_media, CURLINFO_HTTP_CODE);
        curl_close($ch_media);

        // Verifica se o envio da mídia foi bem sucedido
        if ($http_code_media != 200) {
            return ['sucesso' => false, 'erro' => 'Erro ao enviar mídia'];
        }

        // Aguarda 2 segundos após enviar a mídia
        sleep(2);
    }

    // 2. Envia o texto logo em seguida
    if (!empty($mensagem)) {
        $headers_text = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];

        $post_data_text = json_encode([
            'number' => $numero,
            'message' => $mensagem
        ]);

        $ch_text = curl_init();
        curl_setopt($ch_text, CURLOPT_URL, $url);
        curl_setopt($ch_text, CURLOPT_POST, true);
        curl_setopt($ch_text, CURLOPT_HTTPHEADER, $headers_text);
        curl_setopt($ch_text, CURLOPT_POSTFIELDS, $post_data_text);
        curl_setopt($ch_text, CURLOPT_RETURNTRANSFER, true);

        $response_text = curl_exec($ch_text);
        $http_code_text = curl_getinfo($ch_text, CURLINFO_HTTP_CODE);
        curl_close($ch_text);

        if ($http_code_text != 200) {
            return ['sucesso' => false, 'erro' => 'Erro ao enviar mensagem'];
        }
    }

    return ['sucesso' => true, 'erro' => ''];
}

// Obtém os dados do POST
$mensagem = $_POST['mensagem'];
$arquivo_path = $_FILES['arquivo']['tmp_name'] ? $_FILES['arquivo']['tmp_name'] : '';

// Consulta para obter os leads do usuário
$stmt = $pdo->prepare("SELECT id, nome, numero FROM leads_enviados WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inicializa as variáveis de controle
$total_enviados = 0;
$erros_envio = [];
$token = $usuario['token_dispositivo'];

// Loop pelos leads e envia as mensagens
foreach ($leads as $lead) {
    $numero = $lead['numero'];
    $nome = $lead['nome'];
    $mensagem_personalizada = str_replace('{nome}', $nome, $mensagem);

    // Envio da mensagem
    $resultado = send_message($token, $numero, $mensagem_personalizada, $arquivo_path);

    if ($resultado['success']) {
        $total_enviados++;
    } else {
        $erros_envio[] = "Erro ao enviar mensagem para " . htmlspecialchars($numero) . ": " . htmlspecialchars($resultado['error']);
    }

    // Espaço de tempo aleatório entre 5 e 15 segundos
    sleep(rand(5, 15));
}

// Atualiza as variáveis de sessão
$_SESSION['envio_em_andamento'] = false;
$_SESSION['total_enviados'] = $total_enviados;
$_SESSION['erros_envio'] = $erros_envio;

// Envia uma resposta para o AJAX
echo "Envio concluído. Total de mensagens enviadas: " . htmlspecialchars($total_enviados);
?>