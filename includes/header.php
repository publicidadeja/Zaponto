<?php
// /includes/header.php

// Definir a URL base dinamicamente
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    $base_url .= '/xzappro';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ZapLocal' : 'ZapLocal'; ?></title>
    
    <!-- CSS Padrão -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Personalizado -->
    <style>
        :root {
            --primary-color: #0098fc;
            --primary-hover: #283593;
            --success-color: #2CC149;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --background-color: #f7f9fc;
            --text-color: #364a63;
            --border-color: #e2e8f0;
            --card-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.05);
            --border-radius: 10px;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: #fff;
            box-shadow: var(--card-shadow);
            padding: 1rem 1.5rem;
        }

        .navbar-brand img {
            height: 40px;
        }

        .navbar-nav .nav-link {
            color: var(--text-color);
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            transition: color 0.2s ease, background-color 0.2s ease;
            border-radius: var(--border-radius);
        }

        .navbar-nav .nav-link i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(53, 71, 219, 0.1);
        }

        .navbar-icons {
            display: flex;
            align-items: center;
        }

        .navbar-icons a {
            color: var(--text-color);
            margin-left: 1rem;
            font-size: 1.2rem;
            transition: color 0.2s ease;
            padding: 0.5rem;
            border-radius: 50%;
        }

        .navbar-icons a:hover {
            color: var(--primary-color);
            background-color: rgba(53, 71, 219, 0.1);
        }

        @media (max-width: 991.98px) {
            .navbar-nav {
                padding: 1rem 0;
            }

            .navbar-nav .nav-link {
                padding: 0.75rem 1rem;
            }

            .navbar-icons {
                margin-top: 1rem;
                justify-content: center;
            }
        }
    </style>

    <!-- CSS Isolado do Assistente IA -->
    <?php if (isset($_SESSION['usuario_id'])): ?>
    <style>
        #zaplocal-ia-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 99999;
            display: none;
            font-family: 'Nunito', sans-serif;
        }

        #zaplocal-ia-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #0098fc;
            color: #ffffff;
            border: none;
            cursor: pointer;
            z-index: 99998;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #zaplocal-ia-header {
            padding: 15px;
            background: #0098fc;
            color: #ffffff;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #zaplocal-ia-messages {
            height: 300px;
            overflow-y: auto;
            padding: 15px;
            background: #ffffff;
        }

        #zaplocal-ia-input-area {
            padding: 15px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
        }

        .zaplocal-ia-message {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            max-width: 85%;
            font-size: 14px;
        }

        .zaplocal-ia-message.user {
            background-color: #0098fc;
            color: #ffffff;
            margin-left: auto;
        }

        .zaplocal-ia-message.assistant {
            background-color: #f0f0f0;
            color: #364a63;
            margin-right: auto;
        }

        #zaplocal-ia-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            margin-bottom: 8px;
            font-family: inherit;
        }

        #zaplocal-ia-send {
            width: 100%;
            padding: 8px;
            background: #0098fc;
            color: #ffffff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
    <?php endif; ?>
    
    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>
    <?php if (isset($_SESSION['usuario_id'])): ?>
    <!-- Assistente IA com IDs únicos -->
    <button id="zaplocal-ia-toggle">
        <i class="fas fa-robot fa-lg"></i>
    </button>
    
    <div id="zaplocal-ia-widget">
        <div id="zaplocal-ia-header">
            <h5 style="margin:0">Assistente de Marketing</h5>
            <button onclick="document.getElementById('zaplocal-ia-widget').style.display='none';document.getElementById('zaplocal-ia-toggle').style.display='flex';" 
                    style="background:none;border:none;color:white;cursor:pointer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="zaplocal-ia-messages"></div>
        <div id="zaplocal-ia-input-area">
            <textarea id="zaplocal-ia-input" rows="3" placeholder="Digite sua pergunta..."></textarea>
            <button id="zaplocal-ia-send">Enviar</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header Original -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $base_url; ?>/pages/dashboard.php">
                <img src="https://publicidadeja.com.br/wp-content/uploads/2025/02/Logo-ZapLocal-fundo-escuro-1-1.png" alt="ZapLocal Logo">
            </a>
            <!-- [Resto do código da navbar permanece idêntico] -->
        </div>
    </nav>

    <?php if (isset($_SESSION['usuario_id'])): ?>
    <script>
        document.getElementById('zaplocal-ia-toggle').onclick = function() {
            document.getElementById('zaplocal-ia-widget').style.display = 'block';
            this.style.display = 'none';
        };
    </script>
    <?php endif; ?>