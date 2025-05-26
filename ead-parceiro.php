<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'parceiro') {
    header('Location: partner-login.html'); 
    exit();
}
$nomeUsuario = htmlspecialchars($_SESSION['nome_completo'] ?? $_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>EAD Universidade Devz - Devzgroup</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo"><a href="index.html"><img src="img/logo_devz.png" alt="Devz Logo"></a></div>
            <nav class="main-nav">
                <ul>
                    <li><span class="welcome-message">Olá, <?php echo $nomeUsuario; ?>!</span></li>
                    <li><a href="parceiro-dashboard.php" class="btn">Voltar ao Dashboard</a></li>
                    <li><a href="#" id="logoutButton" class="btn btn-logout">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container dashboard-main">
        <h1>EAD - Universidade Devz</h1>
        <p class="dashboard-subtitle">Nossos treinamentos disponíveis para você.</p>
        <div class="dashboard-grid">
            <a href="LINK_YOUTUBE_COMERCIAL" target="_blank" class="dashboard-box">
                <i class="fab fa-youtube dashboard-box-icon" style="color: #FF0000;"></i>
                <h3 class="dashboard-box-title">Treinamento Comercial</h3>
                <p class="dashboard-box-description">Aprenda as melhores práticas de vendas.</p>
            </a>
            <a href="LINK_YOUTUBE_DEVZ_WEB" target="_blank" class="dashboard-box">
                <i class="fab fa-youtube dashboard-box-icon" style="color: #FF0000;"></i>
                <h3 class="dashboard-box-title">Treinamento Devz Web</h3>
                <p class="dashboard-box-description">Domine nossa plataforma web.</p>
            </a>
            <a href="LINK_YOUTUBE_DEVZ_AGRO" target="_blank" class="dashboard-box">
                <i class="fab fa-youtube dashboard-box-icon" style="color: #FF0000;"></i>
                <h3 class="dashboard-box-title">Treinamento Devz Agro</h3>
                <p class="dashboard-box-description">Conheça as soluções para o agronegócio.</p>
            </a>
        </div>
    </main>
    <footer class="main-footer"><div class="container"><p>Copyright © 2025 Devzgroup.</p></div></footer>
    <script> /* Script de Logout */
    document.addEventListener('DOMContentLoaded', function() {
        const logoutButton = document.getElementById('logoutButton');
        if(logoutButton) {
            logoutButton.addEventListener('click', async function(e) {
                e.preventDefault();
                try { await fetch('api/logout.php', { method: 'POST', credentials: 'include' }); window.location.href = 'partner-login.html'; }
                catch (error) { console.error('Erro:', error); window.location.href = 'partner-login.html';}
            });
        }
    });
    </script>
</body>
</html>