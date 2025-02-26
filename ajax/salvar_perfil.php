<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET 
            nome_negocio = ?,
            segmento = ?,
            publico_alvo = ?,
            objetivo_principal = ?,
            perfil_completo = 1
            WHERE id = ?");
            
        $stmt->execute([
            $_POST['nome_negocio'],
            $_POST['segmento'],
            $_POST['publico_alvo'],
            $_POST['objetivo_principal'],
            $_SESSION['usuario_id']
        ]);

        // Atualizar a sessão
        $_SESSION['perfil_completo'] = 1;

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>