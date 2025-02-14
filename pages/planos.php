<?php
$page_title = "Planos e Preços";
require_once '../includes/header.php';
require_once '../includes/stripe-config.php';

// Buscar planos do banco de dados
$stmt = $pdo->query("SELECT * FROM planos WHERE ativo = 1 AND id != 4 ORDER BY preco ASC");
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
        transform: rotate(45deg);
        background: var(--primary-color);
        color: white;
        padding: 8px 40px;
        font-size: 14px;
        font-weight: bold;
    }

    .price-value {
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--primary-color);
        margin: 20px 0;
    }

    .price-period {
        font-size: 1rem;
        color: #6c757d;
    }

    .feature-list {
        list-style: none;
        padding: 0;
        margin: 20px 0;
        flex-grow: 1;
    }

    .feature-list li {
        padding: 10px 0;
        color: #666;
        display: flex;
        align-items: center;
    }

    .feature-list i {
        color: var(--primary-color);
        margin-right: 10px;
    }

    .cta-button {
        width: 100%;
        padding: 15px;
        font-weight: 600;
        margin-top: auto;
    }

    .guarantee-section {
        margin-top: 80px;
        padding: 40px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    .testimonials {
        margin-top: 80px;
        padding: 40px 0;
    }

    .testimonial-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin: 20px 0;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    .testimonial-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin-bottom: 20px;
    }

    .testimonial-text {
        font-style: italic;
        color: #666;
        margin: 20px 0;
    }

    .testimonial-author {
        font-weight: bold;
        color: var(--primary-color);
    }

    .testimonial-position {
        font-size: 0.9rem;
        color: #6c757d;
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
                $isPopular = $index === 1;
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

                    <button 
                        class="btn <?php echo $isPopular ? 'btn-primary' : 'btn-outline-primary'; ?> cta-button subscribe-button"
                        data-plan-id="<?php echo $plano['id']; ?>"
                        data-stripe-price-id="<?php echo htmlspecialchars($plano['stripe_price_id']); ?>">
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
                    <div class="testimonial-card text-center">
                        <img src="<?php echo $baseUrl; ?>/assets/images/testimonial-1.jpg" alt="Cliente 1" class="testimonial-avatar">
                        <p class="testimonial-text">"Aumentei minhas vendas em 300% usando o ZapLocal. A ferramenta é simplesmente incrível!"</p>
                        <div class="testimonial-author">João Silva</div>
                        <div class="testimonial-position">Dono da Padaria Sabor & Cia</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card text-center">
                        <img src="<?php echo $baseUrl; ?>/assets/images/testimonial-2.jpg" alt="Cliente 2" class="testimonial-avatar">
                        <p class="testimonial-text">"O melhor investimento que fiz para meu negócio. Atendimento excepcional!"</p>
                        <div class="testimonial-author">Maria Santos</div>
                        <div class="testimonial-position">Proprietária do Salão Beauty</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card text-center">
                        <img src="<?php echo $baseUrl; ?>/assets/images/testimonial-3.jpg" alt="Cliente 3" class="testimonial-avatar">
                        <p class="testimonial-text">"Facilidade de uso e resultados impressionantes. Recomendo a todos!"</p>
                        <div class="testimonial-author">Pedro Oliveira</div>
                        <div class="testimonial-position">Gerente da Oficina AutoTech</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe('<?php echo STRIPE_PUBLIC_KEY; ?>');

document.querySelectorAll('.subscribe-button').forEach(button => {
    button.addEventListener('click', async (e) => {
        const button = e.currentTarget;
        button.disabled = true;
        
        try {
            const response = await fetch('../ajax/create-checkout-session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    planId: button.dataset.planId,
                    stripePriceId: button.dataset.stripePriceId
                })
            });

            const session = await response.json();
            
            if (session.error) {
                alert(session.error);
                button.disabled = false;
                return;
            }

            const result = await stripe.redirectToCheckout({
                sessionId: session.id
            });

            if (result.error) {
                alert(result.error.message);
                button.disabled = false;
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao processar sua solicitação.');
            button.disabled = false;
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>