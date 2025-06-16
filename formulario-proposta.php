<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'parceiro') {
    header('Location: partner-login.html');
    exit();
}

$proposta_id = $_GET['id'] ?? null;
$dados_proposta = null;
$nomeUsuario = htmlspecialchars($_SESSION['nome_completo'] ?? $_SESSION['username']);

// Conectar ao banco de dados
define('DB_HOST_FORM', 'mysql64-farm2.uni5.net');
define('DB_USER_FORM', 'devzgroup');
define('DB_PASS_FORM', 'D3vzgr0up');
define('DB_NAME_FORM', 'devzgroup');

$conn_form = new mysqli(DB_HOST_FORM, DB_USER_FORM, DB_PASS_FORM, DB_NAME_FORM);
if ($conn_form->connect_error) {
    die("Erro de conexão com o banco de dados: " . $conn_form->connect_error);
}
$conn_form->set_charset("utf8mb4");

// Se estiver editando uma proposta existente
if ($proposta_id) {
    $stmt = $conn_form->prepare("SELECT * FROM propostas WHERE id = ? AND parceiro_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $proposta_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $dados_proposta = $result->fetch_assoc();
        $stmt->close();
    }

    if (!$dados_proposta) {
        $conn_form->close();
        die("Erro: Proposta não encontrada ou sem permissão para editar.");
    }
}

$conn_form->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo $proposta_id ? 'Editar' : 'Nova'; ?> Proposta - Devzgroup</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="css/indicacoes-style.css">
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
                    <li><a href="propostas-parceiro.php" class="btn">Voltar para Propostas</a></li>
                    <li><a href="#" id="logoutButton" class="btn btn-logout">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container dashboard-main">
        <h1><?php echo $proposta_id ? 'Editar' : 'Nova'; ?> Proposta</h1>
        <p class="dashboard-subtitle">Preencha os dados da proposta comercial abaixo.</p>

        <form id="formProposta">
            <?php if ($proposta_id): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($proposta_id); ?>">
            <?php endif; ?>

            <h2>1. DADOS DO CLIENTE</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nome do Cliente: <span class="required">*</span>
                        <input type="text" name="nome_cliente" value="<?php echo htmlspecialchars($dados_proposta['nome_cliente'] ?? ''); ?>" required>
                    </label>
                </div>
                <div class="form-group">
                    <label>Razão Social:
                        <input type="text" name="razao_social" value="<?php echo htmlspecialchars($dados_proposta['razao_social'] ?? ''); ?>">
                    </label>
                </div>
                <div class="form-group">
                    <label>CNPJ:
                        <input type="text" name="cnpj" value="<?php echo htmlspecialchars($dados_proposta['cnpj'] ?? ''); ?>">
                    </label>
                </div>
                <div class="form-group full-width">
                    <label>Endereço:
                        <input type="text" name="endereco" value="<?php echo htmlspecialchars($dados_proposta['endereco'] ?? ''); ?>">
                    </label>
                </div>
                <div class="form-group">
                    <label>Cidade:
                        <input type="text" name="cidade" value="<?php echo htmlspecialchars($dados_proposta['cidade'] ?? ''); ?>">
                    </label>
                </div>
                <div class="form-group">
                    <label>Estado:
                        <input type="text" name="estado" value="<?php echo htmlspecialchars($dados_proposta['estado'] ?? ''); ?>">
                    </label>
                </div>
                <div class="form-group">
                    <label>CEP:
                        <input type="text" name="cep" value="<?php echo htmlspecialchars($dados_proposta['cep'] ?? ''); ?>">
                    </label>
                </div>
                <div class="form-group">
                    <label>E-mail:
                        <input type="email" name="email" value="<?php echo htmlspecialchars($dados_proposta['email'] ?? ''); ?>">
                    </label>
                </div>
                <div class="form-group">
                    <label>Telefone:
                        <input type="tel" name="telefone" value="<?php echo htmlspecialchars($dados_proposta['telefone'] ?? ''); ?>">
                    </label>
                </div>
                <div class="form-group">
                    <label>Contato:
                        <input type="text" name="contato" value="<?php echo htmlspecialchars($dados_proposta['contato'] ?? ''); ?>">
                    </label>
                </div>
            </div>

            <h2>2. PRODUTOS/SERVIÇOS</h2>
            <div class="tabela-produtos">
                <table>
                    <thead>
                        <tr>
                            <th>Produto/Serviço</th>
                            <th>Quantidade</th>
                            <th>Valor Unitário</th>
                            <th>Valor Total</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tabelaProdutosBody">
                        <!-- Será preenchido via JavaScript -->
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm" id="btnAdicionarItem">
                    <i class="fas fa-plus"></i> Adicionar Item
                </button>
            </div>

            <h2>3. CONDIÇÕES COMERCIAIS</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Valor Total da Proposta: <span class="required">*</span>
                        <input type="number" name="valor_total" step="0.01" min="0" value="<?php echo htmlspecialchars($dados_proposta['valor_total'] ?? '0.00'); ?>" required readonly>
                    </label>
                </div>
                <div class="form-group">
                    <label>Data de Validade: <span class="required">*</span>
                        <input type="date" name="data_validade" value="<?php echo htmlspecialchars($dados_proposta['data_validade'] ?? ''); ?>" required>
                    </label>
                </div>
                <div class="form-group full-width">
                    <label>Condições de Pagamento:
                        <textarea name="condicoes_pagamento"><?php echo htmlspecialchars($dados_proposta['condicoes_pagamento'] ?? ''); ?></textarea>
                    </label>
                </div>
                <div class="form-group">
                    <label>Prazo de Entrega:
                        <input type="text" name="prazo_entrega" value="<?php echo htmlspecialchars($dados_proposta['prazo_entrega'] ?? ''); ?>">
                    </label>
                </div>
                <div class="form-group full-width">
                    <label>Observações:
                        <textarea name="observacoes"><?php echo htmlspecialchars($dados_proposta['observacoes'] ?? ''); ?></textarea>
                    </label>
                </div>
            </div>

            <div id="formMessage" class="form-message"></div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Proposta
                </button>
                <button type="button" class="btn btn-secondary" id="btnPrevisualizar">
                    <i class="fas fa-eye"></i> Previsualizar
                </button>
                <?php if ($proposta_id): ?>
                    <button type="button" class="btn" id="btnGerarDoc">
                        <i class="fas fa-file-word"></i> Gerar Documento
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>Copyright © 2025 Devzgroup.</p>
        </div>
    </footer>

    <script>
        const PROPOSTA_ID = <?php echo $proposta_id ? json_encode($proposta_id) : 'null'; ?>;
        const ITENS_PROPOSTA = <?php echo !empty($dados_proposta['itens_proposta']) ? $dados_proposta['itens_proposta'] : '[]'; ?>;
    </script>
    <script src="js/propostas-form-script.js"></script>
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
                        window.location.href = 'partner-login.html';
                    }
                });
            }
        });
    </script>
</body>
</html>
