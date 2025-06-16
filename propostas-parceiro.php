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
    <title>Gerenciador de Propostas - Devzgroup</title>    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="css/indicacoes-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Document generation libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pizzip/3.1.0/pizzip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pizzip-utils/0.0.5/pizzip-utils.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/docxtemplater/3.38.0/docxtemplater.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
    </header>    <main class="container dashboard-main">
        <h1>Gerenciador de Propostas</h1>
        <p class="dashboard-subtitle">Crie e gerencie suas propostas comerciais.</p>

        <div class="actions-bar">
            <a href="formulario-proposta.php" class="btn btn-primary btn-gerar-novo"><i class="fas fa-plus-circle"></i> Nova Proposta</a>
        </div>

        <section class="filtros-documentos-wrapper">
            <h2>Filtrar Propostas</h2>
            <div class="filtros-grid">
                <div class="filtro-item">
                    <label for="filtroNome">Nome do Cliente</label>
                    <input type="text" id="filtroNome" name="filtroNome" placeholder="Digite o nome...">
                </div>
                <div class="filtro-item">
                    <label for="filtroData">Data de Criação</label>
                    <input type="date" id="filtroData" name="filtroData">
                </div>
                <div class="filtro-item">
                    <label for="filtroStatus">Status</label>
                    <select id="filtroStatus" name="filtroStatus">
                        <option value="">Todos</option>
                        <option value="Em Elaboração">Em Elaboração</option>
                        <option value="Enviada">Enviada</option>
                        <option value="Aprovada">Aprovada</option>
                        <option value="Rejeitada">Rejeitada</option>
                        <option value="Expirada">Expirada</option>
                    </select>
                </div>
            </div>
            <div class="filtro-actions">
                <button id="btnAplicarFiltros" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar Filtros</button>
                <button id="btnLimparFiltros" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar Filtros</button>
            </div>
        </section>

        <section class="lista-documentos-section">
            <h2>Propostas Salvas</h2>
            <table id="listaPropostasTable" class="table-documentos">
                <thead>
                    <tr>
                        <th>Nome do Cliente</th>
                        <th>Valor Total</th>
                        <th>Status</th>
                        <th>Data de Criação</th>
                        <th>Validade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="listaPropostasTableBody">
                    <tr>
                        <td colspan="6" style="text-align:center;">Carregando propostas...</td>
                    </tr>
                </tbody>
            </table>
        </section>

    </main>
    <footer class="main-footer"><div class="container"><p>Copyright © 2025 Devzgroup.</p></div></footer>    <script>
        const ID_PARCEIRO_LOGADO = <?php echo json_encode($userId); ?>;
    </script>
    <script src="js/propostas-script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Logout handler
        const logoutButton = document.getElementById('logoutButton');
        if(logoutButton) {
            logoutButton.addEventListener('click', async function(e) {
                e.preventDefault();
                try { 
                    await fetch('api/logout.php', { method: 'POST', credentials: 'include' }); 
                    window.location.href = 'partner-login.html'; 
                }
                catch (error) { 
                    console.error('Erro ao fazer logout:', error); 
                    window.location.href = 'partner-login.html';
                }
            });
        }
    });
    </script>
</body>
</html>