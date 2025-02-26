<?php
require_once 'logger.php';

// Inicializa o logger
Logger::init();

// Teste o log (opcional, para verificar se está funcionando)
try {
    Logger::log("Teste de visualização de logs", "INFO");
} catch (Exception $e) {
    die("Erro ao registrar log: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logs do Sistema</title>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            max-height: 800px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .error-log { color: #dc3545; }
        .success-log { color: #198754; }
        .info-log { color: #0dcaf0; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Logs do Sistema</h2>
        
        <div class="mb-3">
            <button class="btn btn-danger btn-sm" onclick="clearLogs()">Limpar Logs</button>
            <button class="btn btn-primary btn-sm" onclick="refreshLogs()">Atualizar</button>
            <span id="status" class="ms-2"></span>
        </div>

        <pre id="logContent"><?php 
            $logs = Logger::getLogContent();
            echo htmlspecialchars($logs); 
        ?></pre>
    </div>

    <script>
        function clearLogs() {
            if (confirm('Tem certeza que deseja limpar os logs?')) {
                fetch('clear_logs.php')
                    .then(response => response.text())
                    .then(result => {
                        document.getElementById('status').textContent = result;
                        refreshLogs();
                    })
                    .catch(error => {
                        document.getElementById('status').textContent = 'Erro ao limpar logs: ' + error;
                    });
            }
        }

        function refreshLogs() {
            fetch('get_logs.php')
                .then(response => response.text())
                .then(logs => {
                    document.getElementById('logContent').innerHTML = logs;
                    document.getElementById('status').textContent = 'Logs atualizados';
                })
                .catch(error => {
                    document.getElementById('status').textContent = 'Erro ao atualizar logs: ' + error;
                });
        }

        // Atualiza os logs a cada 30 segundos
        setInterval(refreshLogs, 30000);
    </script>
</body>
</html>