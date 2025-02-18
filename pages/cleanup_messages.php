<?php
require_once 'includes/db.php';
require_once 'includes/assistant.php';

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

try {
    limparMensagensAntigas($pdo);
} catch (Exception $e) {
    error_log("Erro durante a limpeza automática: " . $e->getMessage());
}