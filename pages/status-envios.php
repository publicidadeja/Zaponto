<?php
session_start();
include '../includes/db.php';

// Buscar filas do usuário
$stmt = $pdo->prepare("SELECT * FROM mensagens_fila 
    WHERE usuario_id = ? 
    ORDER BY created_at DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="status-container">
    <h2>Status dos Envios em Massa</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Status</th>
                <th>Data Início</th>
                <th>Última Atualização</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filas as $fila): ?>
                <tr>
                    <td><?= $fila['id'] ?></td>
                    <td><?= $fila['status'] ?></td>
                    <td><?= $fila['created_at'] ?></td>
                    <td><?= $fila['updated_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>