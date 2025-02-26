<?php
// /pages/admin/menu.php
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    $base_url .= '/xzappro';
}
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="col-md-3 col-lg-2 px-0">
    <nav class="sidebar bg-dark">
        <div class="sidebar-header">
            <img src="<?php echo $base_url; ?>/assets/images/logo.png" alt="Zaponto" class="img-fluid p-3">
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'usuarios.php' ? 'active' : ''; ?>" 
                   href="usuarios.php">
                    <i class="fas fa-users"></i> Usuários
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'planos.php' ? 'active' : ''; ?>" 
                   href="planos.php">
                    <i class="fas fa-box"></i> Planos
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'relatorios.php' ? 'active' : ''; ?>" 
                   href="relatorios.php">
                    <i class="fas fa-chart-bar"></i> Relatórios
                </a>
            </li>

            <li class="nav-item">
    <a class="nav-link" href="notificacoes.php">
        <i class="fas fa-bell"></i>
        <span>Notificações</span>
    </a>
</li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'configuracoes.php' ? 'active' : ''; ?>" 
                   href="configuracoes.php">
                    <i class="fas fa-cog"></i> Configurações
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </li>
        </ul>
    </nav>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    width: 250px;
    padding: 0;
    z-index: 100;
    overflow-y: auto;
    transition: all 0.3s;
}

.sidebar .nav-link {
    color: rgba(255, 255, 255, 0.8);
    padding: 15px 20px;
    transition: all 0.3s;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.1);
}

.sidebar .nav-link i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

@media (max-width: 768px) {
    .sidebar {
        margin-left: -250px;
    }
    .sidebar.active {
        margin-left: 0;
    }
    .main-content.active {
        margin-left: 250px;
    }
}
</style>