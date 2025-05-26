<?php
session_start(); // Inicia ou resume a sessão

// Verifica se o usuário está logado E se é do tipo 'indicador'
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'indicador') {
    // Se não estiver logado ou não for do tipo correto, redireciona para a página de login
    header('Location: partner-login.html'); 
    exit();
}

$nomeUsuario = htmlspecialchars($_SESSION['nome_completo'] ?? $_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Indicador - Devzgroup</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Adicione aqui quaisquer outros CSS específicos do dashboard -->
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <a href="index.html"><img src="img/logo_devz.png" alt="Devz Logo"></a>
            </div>
            <nav class="main-nav">
                 <ul>
                    <li><span>Olá, <?php echo $nomeUsuario; ?>!</span></li>
                    <li><a href="#" id="logoutButton">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container" style="padding-top: 20px; padding-bottom: 20px;">
        <h1>Bem-vindo ao Dashboard do Indicador!</h1>
        <p>Conteúdo específico para indicadores aqui.</p>
        <!-- Aqui você adicionará os links úteis e outras funcionalidades do indicador -->
    </main>
     <footer class="main-footer" style="margin-top: auto;">
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