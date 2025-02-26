<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/stripe-config.php';

// Verificar se usuário está logado
redirecionarSeNaoLogado();

// Buscar dados da assinatura atual
$stmt = $pdo->prepare("
    SELECT a.*, p.nome as plano_nome, p.preco as plano_valor,
           COALESCE(a.proximo_pagamento, a.data_inicio) as proximo_pagamento
    FROM assinaturas a
    JOIN planos p ON a.plano_id = p.id
    WHERE a.usuario_id = ? AND a.status = 'ativo'
    ORDER BY a.data_inicio DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['usuario_id']]);
$assinatura = $stmt->fetch();

// Buscar histórico completo de pagamentos
$stmt = $pdo->prepare("
    SELECT p.*, a.status as status_pagamento 
    FROM pagamentos p
    LEFT JOIN assinaturas a ON p.assinatura_id = a.id
    WHERE p.usuario_id = ?
    ORDER BY p.data_pagamento DESC
");
$stmt->execute([$_SESSION['usuario_id']]);
$pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Gerenciar Assinatura";
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4>Gerenciar Assinatura</h4>
                </div>
                <div class="card-body">
                    <?php if ($assinatura): ?>
                        <div class="subscription-details">
                            <h5>Plano Atual: <?php echo htmlspecialchars($assinatura['plano_nome']); ?></h5>
                            <p>Valor: R$ <?php echo number_format($assinatura['plano_valor'], 2, ',', '.'); ?>/mês</p>
                            <p>Status: <span class="badge badge-success">Ativo</span></p>
                            <p>Próximo Pagamento: 
    <?php 
    if (isset($assinatura['proximo_pagamento']) && $assinatura['proximo_pagamento']) {
        echo date('d/m/Y', strtotime($assinatura['proximo_pagamento']));
    } else {
        echo "Não definido";
    }
    ?>
</p>
                            
                            <div class="mt-4">
                                <a href="planos.php" class="btn btn-primary">Mudar de Plano</a>
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#cancelarModal">
                                    Cancelar Assinatura
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h5>Histórico de Pagamentos</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                            <th>Fatura</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pagamentos as $pagamento): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($pagamento['data_pagamento'])); ?></td>
                                                <td>R$ <?php echo number_format($pagamento['valor'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <?php if ($pagamento['status_pagamento'] === 'ativo'): ?>
                                                        <span class="badge badge-success">Pago</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Cancelado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($pagamento['fatura_url']): ?>
                                                        <a href="<?php echo htmlspecialchars($pagamento['fatura_url']); ?>" target="_blank">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center">
                            <p>Você não possui uma assinatura ativa.</p>
                            <a href="planos.php" class="btn btn-primary">Escolher um Plano</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cancelamento -->
<div class="modal fade" id="cancelarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Cancelamento</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja cancelar sua assinatura?</p>
                <p>Você perderá acesso aos recursos premium ao final do período atual.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Não</button>
                <form action="cancelado.php" method="POST">
                    <input type="hidden" name="subscription_id" value="<?php echo $assinatura['stripe_subscription_id']; ?>">
                    <button type="submit" name="cancelar_assinatura" class="btn btn-danger">Sim, Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>