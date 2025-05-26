<?php
session_start(); // Inicia ou resume a sessão

// Verifica se o usuário está logado E se é do tipo 'parceiro'
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'parceiro') {
    header('Location: partner-login.html'); 
    exit();
}

$nomeUsuario = htmlspecialchars($_SESSION['nome_completo'] ?? $_SESSION['username']);
$userId = $_SESSION['user_id']; // Guardar o ID do usuário para futuras queries
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Parceiro - Devzgroup</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard-style.css"> <!-- Novo CSS para dashboards -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <a href="index.html"><img src="img/logo_devz.png" alt="Devz Logo"></a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><span class="welcome-message">Olá, <?php echo $nomeUsuario; ?>!</span></li>
                    <li><a href="#" id="logoutButton" class="btn btn-logout">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container dashboard-main">
        <h1>Dashboard do Parceiro</h1>
        <p class="dashboard-subtitle">Acesse os recursos disponíveis para você.</p>

        <div class="dashboard-grid">
            <a href="sistemas-parceiro.php" class="dashboard-box">
                <i class="fas fa-cogs dashboard-box-icon"></i>
                <h3 class="dashboard-box-title">Acesso aos Sistemas</h3>
                <p class="dashboard-box-description">Links para as plataformas e ferramentas.</p>
            </a>

            <a href="ead-parceiro.php" class="dashboard-box">
                <i class="fas fa-graduation-cap dashboard-box-icon"></i>
                <h3 class="dashboard-box-title">EAD - Universidade Devz</h3>
                <p class="dashboard-box-description">Treinamentos e materiais de estudo.</p>
            </a>

            <a href="propostas-parceiro.php" class="dashboard-box">
                <i class="fas fa-file-signature dashboard-box-icon"></i>
                <h3 class="dashboard-box-title">Propostas</h3>
                <p class="dashboard-box-description">Crie e gerencie suas propostas comerciais.</p>
            </a>

            <a href="indicadores-parceiro.php" class="dashboard-box">
                <i class="fas fa-users dashboard-box-icon"></i>
                <h3 class="dashboard-box-title">Cadastro de Indicador</h3>
                <p class="dashboard-box-description">Gerencie seus indicadores e documentos.</p>
            </a>
        </div>
    </main>
    <footer class="main-footer">
        <div class="container">
            <p>Copyright © 2025 Devzgroup.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const logoutButton = document.getElementById('logoutButton');
        if(logoutButton) {
            logoutButton.addEventListener('click', async function(e) {
                e.preventDefault();
                try {
                    await fetch('api/logout.php', { 
                        method: 'POST',
                        credentials: 'include' 
                    });
                    window.location.href = 'partner-login.html';
                } catch (error) {
                    console.error('Erro ao fazer logout:', error);
                    alert('Erro ao tentar sair. Tente novamente.');
                    window.location.href = 'partner-login.html';
                }
            });
        }
    });
    </script>
</body>
</html>