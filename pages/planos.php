<?php
$page_title = "Planos e Preços";
require_once '../includes/header.php';

// Buscar planos do banco de dados
$stmt = $pdo->query("SELECT * FROM planos WHERE ativo = 1 ORDER BY preco ASC");
$planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .pricing-section {
        padding: 80px 0;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .pricing-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .pricing-card {
        background: #fff;
        border-radius: 20px;
        padding: 40px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .pricing-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    .pricing-card.popular {
        border: 2px solid var(--primary-color);
    }

    .popular-badge {
        position: absolute;
        top: 20px;
        right: -35px;
        background: var(--primary-color);
        color: white;
        padding: 8px 40px;
        transform: rotate(45deg);
        font-size: 14px;
        font-weight: bold;
    }

    .price-value {
        font-size: 48px;
        font-weight: bold;
        color: var(--primary-color);
        margin: 20px 0;
    }

    .price-period {
        font-size: 16px;
        color: #6c757d;
    }

    .feature-list {
        list-style: none;
        padding: 0;
        margin: 30px 0;
        flex-grow: 1;
    }

    .feature-list li {
        padding: 10px 0;
        display: flex;
        align-items: center;
        color: #495057;
    }

    .feature-list i {
        margin-right: 10px;
        color: var(--success-color);
    }

    .cta-button {
        padding: 15px 30px;
        border-radius: 50px;
        font-weight: bold;
        transition: all 0.3s ease;
        width: 100%;
    }

    .testimonials {
        background: #fff;
        padding: 60px 60px;
        margin-top: 60px;
    }

    .testimonial-card {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 15px;
        margin: 40px 0;
    }

    .testimonial-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin-right: 15px;
    }

    .guarantee-section {
        text-align: center;
        margin: 40px 0;
        padding: 20px;
        background: rgba(53, 71, 219, 0.1);
        border-radius: 15px;
    }
</style>

<div class="pricing-section">
    <div class="container">
        <div class="pricing-header">
            <h1 class="display-4 mb-3">Escolha o Plano Ideal para Seu Negócio</h1>
            <p class="lead text-muted">Comece gratuitamente e evolua conforme seu crescimento</p>
        </div>

        <div class="row">
            <?php 
            foreach ($planos as $index => $plano):
                $isPopular = $index === 1; // Plano do meio será o popular
            ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="pricing-card <?php echo $isPopular ? 'popular' : ''; ?>">
                    <?php if ($isPopular): ?>
                        <div class="popular-badge">MAIS POPULAR</div>
                    <?php endif; ?>

                    <h3 class="text-center mb-4"><?php echo htmlspecialchars($plano['nome']); ?></h3>
                    
                    <div class="text-center">
                        <div class="price-value">
                            R$ <?php echo number_format($plano['preco'], 2, ',', '.'); ?>
                            <span class="price-period">/mês</span>
                        </div>
                    </div>

                    <ul class="feature-list">
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <?php echo $plano['limite_leads'] == -1 ? 'Leads ilimitados' : number_format($plano['limite_leads']) . ' leads'; ?>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <?php echo $plano['limite_mensagens'] == -1 ? 'Mensagens ilimitadas' : number_format($plano['limite_mensagens']) . ' mensagens/mês'; ?>
                        </li>
                        <?php if ($plano['tem_ia']): ?>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            Assistente de IA Incluído
                        </li>
                        <?php endif; ?>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            Suporte prioritário
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            Relatórios avançados
                        </li>
                    </ul>

                    <button class="btn <?php echo $isPopular ? 'btn-primary' : 'btn-outline-primary'; ?> cta-button">
                        <?php echo $isPopular ? 'Começar Agora' : 'Selecionar Plano'; ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="guarantee-section">
            <div class="row align-items-center">
                <div class="col-md-6 text-center">
                    <i class="fas fa-shield-alt fa-4x text-primary mb-3"></i>
                    <h3>Garantia de 7 Dias</h3>
                    <p>Teste nossa plataforma sem compromisso. Se não gostar, devolvemos seu dinheiro.</p>
                </div>
                <div class="col-md-6 text-center">
                    <i class="fas fa-headset fa-4x text-primary mb-3"></i>
                    <h3>Suporte 24/7</h3>
                    <p>Nossa equipe está sempre disponível para ajudar você.</p>
                </div>
            </div>
        </div>

        <div class="testimonials">
            <h2 class="text-center mb-5">O que nossos clientes dizem</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="Avatar" class="testimonial-avatar">
                            <div>
                                <h5 class="mb-0">João Silva</h5>
                                <small class="text-muted">Empresário</small>
                            </div>
                        </div>
                        <p>"Aumentei minhas vendas em 300% usando o Zaponto. A melhor ferramenta que já usei!"</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/women/1.jpg" alt="Avatar" class="testimonial-avatar">
                            <div>
                                <h5 class="mb-0">Maria Santos</h5>
                                <small class="text-muted">Lojista</small>
                            </div>
                        </div>
                        <p>"O suporte é incrível e a plataforma é super fácil de usar. Recomendo!"</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/men/2.jpg" alt="Avatar" class="testimonial-avatar">
                            <div>
                                <h5 class="mb-0">Pedro Oliveira</h5>
                                <small class="text-muted">Empreendedor</small>
                            </div>
                        </div>
                        <p>"Melhor investimento que fiz para meu negócio. Resultados impressionantes!"</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>