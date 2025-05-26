<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'parceiro') {
    header('Location: partner-login.html'); 
    exit();
}
$nomeUsuario = htmlspecialchars($_SESSION['nome_completo'] ?? $_SESSION['username']);
$userId = $_SESSION['user_id']; // ID do parceiro logado
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciador de Propostas - Devzgroup</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard-style.css">
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
        <h1>Gerenciador de Propostas</h1>
        <p class="dashboard-subtitle">Crie novas propostas e visualize as existentes.</p>
        
        <!-- Conteúdo da página de propostas virá aqui -->
        <!-- Exemplo: Formulário para nova proposta e lista de propostas existentes -->
        <div style="border: 1px dashed #ccc; padding: 20px; text-align: center; margin-top: 30px;">
            <p><strong>Funcionalidade em Desenvolvimento</strong></p>
            <p>Aqui você poderá criar e visualizar suas propostas.</p>
            <p>Isso exigirá integração com banco de dados para salvar e listar propostas associadas ao seu usuário (ID: <?php echo $userId; ?>).</p>
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