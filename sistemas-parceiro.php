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
    <title>Acesso aos Sistemas - Devzgroup</title>
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
    <h1>Acesso aos Sistemas</h1>
    <p class="dashboard-subtitle">Selecione o sistema que deseja acessar.</p>
    <div class="dashboard-grid">
        <!-- Links Atualizados -->
        <a href="https://www.devz.app.br/sistema/" target="_blank" class="dashboard-box">
            <i class="fas fa-desktop dashboard-box-icon"></i>
            <h3 class="dashboard-box-title">Devz Web</h3>
            <p class="dashboard-box-description">Acesso à plataforma geral de gestão.</p>
        </a>
        <a href="https://food.devz.app.br/sistema/" target="_blank" class="dashboard-box">
            <i class="fas fa-utensils dashboard-box-icon"></i>
            <h3 class="dashboard-box-title">Devz Food</h3>
            <p class="dashboard-box-description">Sistema para gestão de restaurantes e delivery.</p>
        </a>
        <a href="https://agro.devz.app.br/sistema/" target="_blank" class="dashboard-box">
            <i class="fas fa-tractor dashboard-box-icon"></i>
            <h3 class="dashboard-box-title">Devz Agro</h3>
            <p class="dashboard-box-description">Plataforma voltada para o agronegócio.</p>
        </a>
        <a href="https://pet.devz.app.br/sistema/" target="_blank" class="dashboard-box">
            <i class="fas fa-paw dashboard-box-icon"></i>
            <h3 class="dashboard-box-title">Devz Pet</h3>
            <p class="dashboard-box-description">Sistema para petshops e clínicas veterinárias.</p>
        </a>
        <a href="https://shop.devz.app.br/login" target="_blank" class="dashboard-box">
            <i class="fas fa-store dashboard-box-icon"></i>
            <h3 class="dashboard-box-title">Devz Shop</h3>
            <p class="dashboard-box-description">Plataforma de e-commerce e lojas virtuais.</p>
        </a>
        <a href="https://clinica.devz.app.br/acesso" target="_blank" class="dashboard-box">
            <i class="fas fa-clinic-medical dashboard-box-icon"></i>
            <h3 class="dashboard-box-title">Devz Clínica</h3>
            <p class="dashboard-box-description">Sistema para gestão de clínicas e consultórios.</p>
        </a>
        <!-- Adicione mais sistemas conforme necessário -->
    </div>
    </main>
    <footer class="main-footer"><div class="container"><p>Copyright © 2025 Devzgroup.</p></div></footer>
    <script> /* Script de Logout (igual ao do dashboard principal) */
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