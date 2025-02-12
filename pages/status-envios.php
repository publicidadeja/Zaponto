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

<?php if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Progresso do Envio</h5>
        <div class="progress mb-3">
            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" style="width: 0%"></div>
        </div>
        <div id="statusInfo" class="text-center">
            Carregando status...
        </div>
    </div>
</div>

<script>
function atualizarProgresso() {
    fetch('/queue-progress/<?php echo $_SESSION['usuario_id']; ?>')
        .then(response => response.json())
        .then(data => {
            // Atualizar interface com o progresso
            const progresso = (data.enviados / data.total) * 100;
            $('#progressBar').css('width', progresso + '%');
            $('#statusInfo').text(`Enviados: ${data.enviados} | Erros: ${data.erros} | Pendentes: ${data.pendentes}`);
            
            // Continuar atualizando se ainda houver mensagens pendentes
            if (data.pendentes > 0) {
                setTimeout(atualizarProgresso, 5000);
            }
        });
}

// Iniciar monitoramento quando a página carregar
$(document).ready(function() {
    atualizarProgresso();
});
</script>git 