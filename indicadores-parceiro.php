<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'parceiro') {
    header('Location: partner-login.html'); 
    exit();
}
$nomeUsuario = htmlspecialchars($_SESSION['nome_completo'] ?? $_SESSION['username']);
$parceiroId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Documentos de Indicação - Devzgroup</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="css/indicacoes-style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Include PizZip -->
    <script src="https://cdn.jsdelivr.net/npm/pizzip@3.1.0/dist/pizzip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pizzip-utils/0.0.5/pizzip-utils.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/docxtemplater/3.38.0/docxtemplater.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo"><a href="index.html"><img src="img/logo_devz.png" alt="Devz Logo"></a></div>
            <nav class="main-nav">
                <ul>
                    <li><span class="welcome-message">Olá, <?php echo $nomeUsuario; ?>!</span></li>
                    <li><a href="parceiro-dashboard.php" class="btn btn-dashboard-header">Voltar ao Dashboard</a></li>
                    <li><a href="#" id="logoutButton" class="btn btn-logout">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container dashboard-main">
        <h1>Meus Documentos de Indicação</h1>
        <p class="dashboard-subtitle">Gerencie seus documentos de indicação de negócios.</p>

        <div class="actions-bar">
            <a href="formulario-indicacao.php" class="btn btn-primary btn-gerar-novo"><i class="fas fa-plus-circle"></i> Gerar Novo Documento</a>
        </div>

        <section class="lista-documentos-section">
            <h2>Documentos Salvos</h2>
            <table id="listaDocumentosTable" class="table-documentos">
                <thead>
                    <tr>
                        <th>Nome do Documento</th>
                        <th>Agente Indicador</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="listaDocumentosTableBody">
                    <tr>
                        <td colspan="5" style="text-align:center;">Carregando documentos...</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>
    <footer class="main-footer"><div class="container"><p>Copyright © 2025 Devzgroup.</p></div></footer>
    
    <script>
        const ID_PARCEIRO_LOGADO = <?php echo json_encode($parceiroId); ?>;
    </script>
    <script src="js/documentos-indicacao-script.js"></script>
    <script> /* Script de Logout */
    document.addEventListener('DOMContentLoaded', function() {
        const logoutButton = document.getElementById('logoutButton');
        if(logoutButton) {
            logoutButton.addEventListener('click', async function(e) {
                e.preventDefault();
                try { await fetch('api/logout.php', { method: 'POST', credentials: 'include' }); window.location.href = 'partner-login.html'; }
                catch (error) { console.error('Erro ao fazer logout:', error); window.location.href = 'partner-login.html';}
            });
        }
    });
    </script>
</body>
</html>