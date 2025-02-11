<?php
// /includes/footer.php

// Definir a URL base dinamicamente (similar ao header)
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    $base_url .= '/xzappro';
}
?>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="footer-brand">
                    <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/Logo-ZapLocal-fundo-escuro-1-1.png" alt="ZapLocal Logo" class="footer-logo">
                    <p class="mt-3">Automatize seus envios de WhatsApp de forma profissional e eficiente.</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                <h5>Links Rápidos</h5>
                <ul class="footer-links">
                    <li><a href="<?php echo $base_url; ?>/pages/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo $base_url; ?>/pages/enviar-mensagem.php">Enviar Mensagem</a></li>
                    <li><a href="<?php echo $base_url; ?>/pages/lista-leads.php">Listar Leads</a></li>
                    <li><a href="<?php echo $base_url; ?>/pages/dispositivos.php">Dispositivos</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                <h5>Suporte</h5>
                <ul class="footer-links">
                    <li><a href="#">Central de Ajuda</a></li>
                    <li><a href="#">Documentação</a></li>
                    <li><a href="#">Contato</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
            <div class="col-lg-4 col-md-4">
                <h5>Contato</h5>
                <ul class="footer-contact">
                    <li><i class="fas fa-envelope"></i> suporte@zaplocal.com.br</li>
                    <li><i class="fas fa-phone"></i> (11) 9999-9999</li>
                    <li><i class="fas fa-map-marker-alt"></i> São Paulo, SP - Brasil</li>
                </ul>
                <div class="social-links mt-3">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <hr class="footer-divider">
        <div class="footer-bottom">
            <p class="copyright">© <?php echo date('Y'); ?> ZapLocal. Todos os direitos reservados.</p>
            <div class="footer-bottom-links">
                <a href="#">Termos de Uso</a>
                <a href="#">Política de Privacidade</a>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts padrão -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    .footer {
        background-color: #fff;
        padding: 4rem 0 1rem;
        margin-top: 3rem;
        box-shadow: 0 -0.75rem 1.5rem rgba(0, 0, 0, 0.05);
    }

    .footer-logo {
        height: 40px;
    }

    .footer h5 {
        color: var(--text-color);
        font-weight: 600;
        margin-bottom: 1.5rem;
    }

    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 0.75rem;
    }

    .footer-links a {
        color: var(--text-color);
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .footer-links a:hover {
        color: var(--primary-color);
    }

    .footer-contact {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-contact li {
        margin-bottom: 0.75rem;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .footer-contact i {
        color: var(--primary-color);
        width: 20px;
    }

    .social-links {
        display: flex;
        gap: 1rem;
    }

    .social-link {
        color: var(--text-color);
        background-color: var(--background-color);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .social-link:hover {
        color: #fff;
        background-color: var(--primary-color);
    }

    .footer-divider {
        margin: 2rem 0;
        border-color: var(--border-color);
    }

    .footer-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .copyright {
        color: var(--text-color);
        margin: 0;
    }

    .footer-bottom-links {
        display: flex;
        gap: 1.5rem;
    }

    .footer-bottom-links a {
        color: var(--text-color);
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .footer-bottom-links a:hover {
        color: var(--primary-color);
    }

    @media (max-width: 991.98px) {
        .footer {
            padding: 3rem 0 1rem;
        }
        
        .footer-bottom {
            flex-direction: column;
            text-align: center;
        }
        
        .footer-bottom-links {
            justify-content: center;
        }
    }
</style>

</body>
</html>