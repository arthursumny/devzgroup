<?php
session_start(); // Iniciar sessão para verificar se é o parceiro logado

$documento_uid_param = $_GET['uid'] ?? null;
$parceiro_pode_editar_restrito = false;
$dados_documento = null;
$nome_parceiro_logado = ''; // Nome do parceiro que está logado e visualizando/editando
$nome_parceiro_criador_doc = ''; // Nome do parceiro que originalmente gerou o link

if (empty($documento_uid_param)) {
    die("Erro: Identificador do documento não fornecido.");
}

// Conectar ao DB para buscar dados do documento e do parceiro criador
define('DB_HOST_FORM', 'mysql64-farm2.uni5.net');
define('DB_USER_FORM', 'devzgroup');
define('DB_PASS_FORM', 'D3vzgr0up');
define('DB_NAME_FORM', 'devzgroup');

$conn_form = new mysqli(DB_HOST_FORM, DB_USER_FORM, DB_PASS_FORM, DB_NAME_FORM);
if ($conn_form->connect_error) {
    die("Erro de conexão com o banco de dados: " . $conn_form->connect_error);
}
$conn_form->set_charset("utf8mb4");

// Buscar dados do documento e nome do parceiro criador
$stmt_doc = $conn_form->prepare("SELECT d.*, u.nome_completo as nome_parceiro_criador, u.username as username_parceiro_criador 
                                FROM documentos_indicacao d 
                                JOIN usuarios u ON d.parceiro_id = u.id 
                                WHERE d.documento_uid = ?");
if ($stmt_doc) {
    $stmt_doc->bind_param("s", $documento_uid_param);
    $stmt_doc->execute();
    $result_doc = $stmt_doc->get_result();
    $dados_documento = $result_doc->fetch_assoc();
    $stmt_doc->close();
}

if (!$dados_documento) {
    $conn_form->close();
    die("Erro: Documento não encontrado ou inválido.");
}

$nome_completo_db = $dados_documento['nome_parceiro_criador'] ?? null;
$username_db = $dados_documento['username_parceiro_criador'] ?? null;
$default_nome_pa = 'Parceiro Devzgroup';

if ($nome_completo_db !== null && trim($nome_completo_db) !== '') {
    // Use nome_completo if it's not null and not an empty/whitespace string
    $nome_parceiro_criador_doc = htmlspecialchars(trim($nome_completo_db));
} elseif ($username_db !== null && trim($username_db) !== '') {
    // Fallback to username if it's not null and not an empty/whitespace string
    $nome_parceiro_criador_doc = htmlspecialchars(trim($username_db));
} else {
    // Fallback to the default name if both nome_completo and username are null or empty/whitespace
    $nome_parceiro_criador_doc = htmlspecialchars($default_nome_pa);
}

// Verificar se o usuário logado é o parceiro dono deste documento
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && 
    $_SESSION['user_type'] === 'parceiro' && (int)$_SESSION['user_id'] === (int)$dados_documento['parceiro_id']) {
    $parceiro_pode_editar_restrito = true;
    $nome_parceiro_logado = htmlspecialchars($_SESSION['nome_completo'] ?? $_SESSION['username']);
}
$conn_form->close();

$tabela_valores = !empty($dados_documento['tabela_valores_json']) ? json_decode($dados_documento['tabela_valores_json'], true) : [];
if (empty($tabela_valores)) {
    // Valores padrão da tabela (com ponto decimal para processamento interno)
    $tabela_valores_padrao = [
        ["produto" => "Kit PF A3 1 ano + Smart card", "custo_jed" => "97.00", "venda_cliente_final" => "198.00", "visivel" => false],
        ["produto" => "Kit PF A3 1 anos + Smart card + Leitora", "custo_jed" => "202.00", "venda_cliente_final" => "295.00", "visivel" => false],
        ["produto" => "PFA3 - SYN 12 Meses (nuvem)", "custo_jed" => "76.00", "venda_cliente_final" => "159.00", "visivel" => false],
        ["produto" => "Kit PJ A3 1 ano + Smart card", "custo_jed" => "102.00", "venda_cliente_final" => "225.00", "visivel" => false],
        ["produto" => "Kit PJ A3 1 anos + Smart card + Leitora", "custo_jed" => "207.00", "venda_cliente_final" => "325.00", "visivel" => false],
        ["produto" => "Kit PJ A3 1 anos + Token", "custo_jed" => "142.00", "venda_cliente_final" => "265.00", "visivel" => false],
        ["produto" => "Kit PJ A3 2 anos + Smart card", "custo_jed" => "122.00", "venda_cliente_final" => "245.00", "visivel" => false],
        ["produto" => "Kit PJ A3 2 anos + Smart card + Leitora", "custo_jed" => "227.00", "venda_cliente_final" => "345.00", "visivel" => false],
        ["produto" => "Kit PJ A3 2 anos + Token", "custo_jed" => "162.00", "venda_cliente_final" => "295.00", "visivel" => false],
        ["produto" => "Kit PJ A3 3 anos + Smart card", "custo_jed" => "132.00", "venda_cliente_final" => "276.00", "visivel" => false],
        ["produto" => "Kit PJ A3 3 anos + Smart card + Leitora", "custo_jed" => "237.00", "venda_cliente_final" => "365.00", "visivel" => false],
        ["produto" => "Kit PJ A3 3 anos + Token", "custo_jed" => "172.00", "venda_cliente_final" => "315.00", "visivel" => false],
        ["produto" => "PF A1 3 meses", "custo_jed" => "35.00", "venda_cliente_final" => "90.00", "visivel" => false],
        ["produto" => "PF A1 1 ano", "custo_jed" => "62.00", "venda_cliente_final" => "138.00", "visivel" => false],
        ["produto" => "PF A3 1 ano", "custo_jed" => "72.00", "venda_cliente_final" => "149.00", "visivel" => false],
        ["produto" => "PF A3 2 anos", "custo_jed" => "82.00", "venda_cliente_final" => "169.00", "visivel" => false],
        ["produto" => "PF A3 3 anos", "custo_jed" => "92.00", "venda_cliente_final" => "189.00", "visivel" => false],
        ["produto" => "PJ A1 3 meses", "custo_jed" => "40.00", "venda_cliente_final" => "110.00", "visivel" => false],
        ["produto" => "PJ A1 1 ano", "custo_jed" => "67.00", "venda_cliente_final" => "198.00", "visivel" => false],
        ["produto" => "PJ A3 1 ano", "custo_jed" => "77.00", "venda_cliente_final" => "205.00", "visivel" => false],
        ["produto" => "PJ A3 2 ano", "custo_jed" => "87.00", "venda_cliente_final" => "215.00", "visivel" => false],
        ["produto" => "PJ A3 3 anos", "custo_jed" => "97.00", "venda_cliente_final" => "225.00", "visivel" => false],
        ["produto" => "Smart Card", "custo_jed" => "35.00", "venda_cliente_final" => "65.00", "visivel" => false],
        ["produto" => "Token", "custo_jed" => "75.00", "venda_cliente_final" => "110.00", "visivel" => false],
        ["produto" => "Leitora Smart Card", "custo_jed" => "105.00", "venda_cliente_final" => "145.00", "visivel" => false],
    ];
    $tabela_valores = $tabela_valores_padrao;
}

$readonly_parceiro_fields = $parceiro_pode_editar_restrito ? '' : 'readonly';
$disabled_parceiro_fields = $parceiro_pode_editar_restrito ? '' : 'disabled';

$readonly_geral = ''; 
$disabled_geral = '';

if ($dados_documento['status_documento'] === 'Finalizado pelo Parceiro' || $dados_documento['status_documento'] === 'Assinado') {
    $readonly_parceiro_fields = 'readonly';
    $disabled_parceiro_fields = 'disabled';
    $readonly_geral = 'readonly';
    $disabled_geral = 'disabled';
} elseif ($dados_documento['status_documento'] === 'Finalizado pelo Cliente' && !$parceiro_pode_editar_restrito) {
    // Se finalizado pelo cliente E NÃO é o parceiro editando, campos gerais ficam readonly
    $readonly_geral = 'readonly';
    $disabled_geral = 'disabled';
}

$upload_display_path = 'uploads/'; 

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Formulário de Indicação - Devzgroup</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="css/indicacoes-style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo"><a href="index.html"><img src="img/logo_devz.png" alt="Devz Logo"></a></div>
            <?php if ($parceiro_pode_editar_restrito): ?>
            <nav class="main-nav">
                <ul>
                    <li><span class="welcome-message">Editando como: <?php echo $nome_parceiro_logado; ?></span></li>
                    <li><a href="indicadores-parceiro.php" class="btn">Voltar para Documentos</a></li>
                    <li><a href="#" id="logoutButtonForm" class="btn btn-logout">Sair</a></li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </header>
    <main class="container dashboard-main">
        <h1>Formulário de Indicação de Negócios</h1>
        <?php if (!$parceiro_pode_editar_restrito): ?>
        <p>Preencha os dados abaixo. Você pode salvar o progresso e continuar depois.</p>
        <?php else: ?>
        <p>Você está editando este documento como parceiro. Campos restritos estão habilitados.</p>
        <?php endif; ?>
        
        <form id="formDocumentoIndicacao">
            <input type="hidden" name="documento_uid" value="<?php echo htmlspecialchars($documento_uid_param); ?>">

            <h2>1. IDENTIFICAÇÃO DAS PARTES</h2>
            <p><strong>DEVZ SOLUÇÕES LTDA</strong>, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº 35.115.124/0001-05, com sede à Rua Antonio Nunes Varela, nº 688, Vila Pedrini, Joaçaba-SC, CEP 89600-000, neste ato representada na forma de seu contrato social, doravante denominada simplesmente por DEVZGROUP. Fone: (49) 3523-1020</p>
            
            <h3>1.2. AGENTE INDICADOR DO NEGÓCIO</h3>
            <div class="form-grid">
                <div class="form-group"><label>Nome/Razão social: <input type="text" name="ag_nome_razao_social" value="<?php echo htmlspecialchars($dados_documento['ag_nome_razao_social'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?> required></label></div>
                <div class="form-group"><label>Nome fantasia: <input type="text" name="ag_nome_fantasia" value="<?php echo htmlspecialchars($dados_documento['ag_nome_fantasia'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group full-width"><label>Endereço: <input type="text" name="ag_endereco" value="<?php echo htmlspecialchars($dados_documento['ag_endereco'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>Complemento: <input type="text" name="ag_complemento" value="<?php echo htmlspecialchars($dados_documento['ag_complemento'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>Bairro: <input type="text" name="ag_bairro" value="<?php echo htmlspecialchars($dados_documento['ag_bairro'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>Cidade: <input type="text" name="ag_cidade" value="<?php echo htmlspecialchars($dados_documento['ag_cidade'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>CEP: <input type="text" name="ag_cep" value="<?php echo htmlspecialchars($dados_documento['ag_cep'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>UF: <input type="text" name="ag_uf" value="<?php echo htmlspecialchars($dados_documento['ag_uf'] ?? 'SC'); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>CPF/CNPJ: <input type="text" name="ag_cpf_cnpj" value="<?php echo htmlspecialchars($dados_documento['ag_cpf_cnpj'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>Nome do representante legal: <input type="text" id="ag_representante_legal_input" name="ag_representante_legal" value="<?php echo htmlspecialchars($dados_documento['ag_representante_legal'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>Cargo/Função: <input type="text" name="ag_cargo" value="<?php echo htmlspecialchars($dados_documento['ag_cargo'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>CPF: <input type="text" id="ag_cpf_representante_input" name="ag_cpf_representante" value="<?php echo htmlspecialchars($dados_documento['ag_cpf_representante'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>RG: <input type="text" name="ag_rg_representante" value="<?php echo htmlspecialchars($dados_documento['ag_rg_representante'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>E-mail: <input type="email" name="ag_email" value="<?php echo htmlspecialchars($dados_documento['ag_email'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>Telefone: <input type="tel" name="ag_telefone" value="<?php echo htmlspecialchars($dados_documento['ag_telefone'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
            </div>
            
            <h3>1.3. INFORMAÇÕES BANCÁRIAS PARA PAGAMENTO</h3>
            <div class="form-grid">
                <div class="form-group"><label>Nome/Razão social: <input type="text" name="banco_nome_razao_social" value="<?php echo htmlspecialchars($dados_documento['banco_nome_razao_social'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>CPF/CNPJ: <input type="text" name="banco_cpf_cnpj" value="<?php echo htmlspecialchars($dados_documento['banco_cpf_cnpj'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>Banco: <input type="text" name="banco_nome" value="<?php echo htmlspecialchars($dados_documento['banco_nome'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>Agência: <input type="text" name="banco_agencia" value="<?php echo htmlspecialchars($dados_documento['banco_agencia'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>Conta: <input type="text" name="banco_conta" value="<?php echo htmlspecialchars($dados_documento['banco_conta'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>Tipo (Corrente/Poupança): <input type="text" name="banco_tipo_conta" value="<?php echo htmlspecialchars($dados_documento['banco_tipo_conta'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>Chave Pix: <input type="text" name="banco_chave_pix" value="<?php echo htmlspecialchars($dados_documento['banco_chave_pix'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <!-- New File Upload Field -->
                <div class="form-group full-width">
                    <label for="banco_comprovante">Comprovante dos Dados Bancários (Foto do cartão ou Print da conta): <span style="color:red;">*</span></label>
                    <input type="file" id="banco_comprovante" name="banco_comprovante" accept="image/jpeg,image/png,application/pdf" <?php echo $disabled_geral; ?>>
                    <?php if (!empty($dados_documento['banco_comprovante_path'])): ?>
                        <p class="comprovante-atual-info">Comprovante atual: 
                            <a href="<?php echo $upload_display_path . htmlspecialchars($dados_documento['banco_comprovante_path']); ?>" target="_blank">
                                <?php echo htmlspecialchars($dados_documento['banco_comprovante_path']); ?>
                            </a>
                        </p>
                        <?php if (empty($disabled_geral)): // Only show remove option if form is editable ?>
                        <label class="remover-comprovante-label">
                            <input type="checkbox" name="remover_banco_comprovante" value="1" <?php echo $disabled_geral; ?>> Remover comprovante atual ao salvar
                        </label>
                        <?php endif; ?>
                    <?php endif; ?>
                    <small>Tipos permitidos: JPG, PNG, PDF. Tamanho máximo: 5MB.</small>
                </div>
                <!-- End New File Upload Field -->
                <div class="form-group">
                    <label>Pagamento: 
                        <select name="pagamento_tipo" <?php echo $disabled_parceiro; ?> <?php echo $disabled_publico_apos_finalizacao_parceiro; ?>>
                            <option value="Split" <?php echo (($dados_documento['pagamento_tipo'] ?? '') === 'Split') ? 'selected' : ''; ?>>Split</option>
                            <option value="Mensal" <?php echo (($dados_documento['pagamento_tipo'] ?? '') === 'Mensal') ? 'selected' : ''; ?>>Mensal</option>
                        </select>
                    </label>
                    <?php if (!$parceiro_pode_editar_restrito && !empty($dados_documento['pagamento_tipo'])): ?>
                        <input type="hidden" name="pagamento_tipo" value="<?php echo htmlspecialchars($dados_documento['pagamento_tipo']); ?>">
                        <p class="readonly-info">Pagamento: <?php echo htmlspecialchars($dados_documento['pagamento_tipo']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <h3>2. OBSERVAÇÕES</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>PA para qual faz as indicações: 
                        <input type="text" name="obs_pa_indicacoes" value="<?php echo htmlspecialchars($dados_documento['obs_pa_indicacoes'] ?? ''); ?>" <?php echo $readonly_parceiro; ?> <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>>
                    </label>
                     <?php if (!$parceiro_pode_editar_restrito && !empty($dados_documento['obs_pa_indicacoes'])): ?>
                        <input type="hidden" name="obs_pa_indicacoes" value="<?php echo htmlspecialchars($dados_documento['obs_pa_indicacoes']); ?>">
                        <p class="readonly-info">PA Indicações: <?php echo htmlspecialchars($dados_documento['obs_pa_indicacoes']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="form-group full-width"><label>Anotações/observações: <textarea name="obs_anotacoes" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>><?php echo htmlspecialchars($dados_documento['obs_anotacoes'] ?? ''); ?></textarea></label></div>
            </div>

            <h4>Tabela de Valores</h4>
    <div class="tabela-valores-container">
    <table>
    <thead>
    <tr>
    <th>Produto</th>
    <th>Custo JED (R$) <?php if ($parceiro_pode_editar_restrito) echo '<i class="fas fa-edit edit-icon-parceiro" title="Editável pelo Parceiro"></i>'; ?></th>
    <th>Venda Cliente Final (R$) <i class="fas fa-edit edit-icon-publico" title="Editável por você"></i></th>
    <?php if ($parceiro_pode_editar_restrito): ?>
        <th>Ações <?php echo '<i class="fas fa-edit edit-icon-parceiro" title="Editável pelo Parceiro"></i>';?></th>
    <?php endif; ?>
    </tr>
    </thead>
    <tbody id="tabelaValoresBody">
    <?php foreach ($tabela_valores as $index => $item):
        // Garante que 'visivel' exista, default para true se não (para dados antigos)
        $visivel = isset($item['visivel']) ? (bool)$item['visivel'] : true; 
    
        // Se NÃO for o parceiro editando E o item NÃO estiver visível,
        // não renderizar esta linha para o usuário convidado.
        if (!$parceiro_pode_editar_restrito && !$visivel) {
            continue; // Pula para o próximo item do loop, não renderizando a linha atual.
        }
    ?>
    <tr class="<?php 
        // Aplicar a classe 'item-oculto' apenas se for o parceiro editando e o item estiver oculto.
        // Para o convidado, se o item estiver oculto, ele não será renderizado (devido ao 'continue' acima).
        // Se o item for visível para o convidado, nenhuma classe especial é necessária aqui.
        echo ($parceiro_pode_editar_restrito && !$visivel) ? 'item-oculto' : ''; 
    ?>">
    <td>
    <input type="hidden" name="tabela_valores[<?php echo $index; ?>][produto]" value="<?php echo htmlspecialchars($item['produto']); ?>">
    <?php echo htmlspecialchars($item['produto']); ?>
    <?php 
    // O input hidden para 'visivel' só é necessário se o parceiro pode editar.
    // Se não for o parceiro, a API manterá o valor do BD.
    if ($parceiro_pode_editar_restrito): ?>
        <input type="hidden" name="tabela_valores[<?php echo $index; ?>][visivel]" value="<?php echo $visivel ? 'true' : 'false'; ?>" class="input-visibilidade">
    <?php endif; ?>
    </td>
    <td><input type="number" step="0.01" name="tabela_valores[<?php echo $index; ?>][custo_jed]" value="<?php echo htmlspecialchars($item['custo_jed'] ?? ''); ?>" <?php echo $readonly_parceiro_fields; ?> <?php echo $readonly_geral; ?>></td>
    <td><input type="number" step="0.01" name="tabela_valores[<?php echo $index; ?>][venda_cliente_final]" value="<?php echo htmlspecialchars($item['venda_cliente_final'] ?? ''); ?>" <?php echo $readonly_geral; ?>></td>
    <?php if ($parceiro_pode_editar_restrito): ?>
        <td>
            <button type="button" class="btn btn-sm btn-toggle-visibility" <?php echo $disabled_geral; // Desabilita se o form estiver bloqueado ?> >
                <?php echo $visivel ? '<i class="fas fa-eye-slash"></i> Ocultar' : '<i class="fas fa-eye"></i> Mostrar'; ?>
            </button>
        </td>
    <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
            
            <h3>3. POLÍTICAS DE RELACIONAMENTO</h3>
            <p>São considerados Contabilidades ou Contadores Parceiros pessoas físicas ou jurídicas. O seu relacionamento consiste no fornecimento de informações sobre possíveis compradores das soluções oferecidas pela Atena Tecnologia. O relacionamento com esses Parceiros (as) <strong>não caracteriza vínculo empregatício ou de representação, estando habilitado apenas ao fornecimento de “indicação de negócios”.</strong></p>
            
            <h3>4. DECLARAÇÃO</h3>
            <p>Eu (nós), com poderes para assinar pela empresa acima qualificada, certifico(amos) que as informações por mim apresentadas são reais.</p>
            <div class="form-grid">
                <div class="form-group"><label>Local: <input type="text" name="decl_local" value="<?php echo htmlspecialchars($dados_documento['decl_local'] ?? ''); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
                <div class="form-group"><label>Data: <input type="date" name="decl_data" value="<?php echo htmlspecialchars($dados_documento['decl_data'] ?? date('Y-m-d')); ?>" <?php echo $readonly_publico_apos_finalizacao_parceiro; ?>></label></div>
            </div>
            <br>
            <div class="form-grid">
                <div class="form-group">
                    <label>Responsável legal parceiro indicador:</label>
                    <input type="text" name="decl_resp_parceiro" id="decl_resp_parceiro_display" value="<?php echo htmlspecialchars($dados_documento['decl_resp_parceiro'] ?? ($dados_documento['ag_representante_legal'] ?? '')); ?>" readonly placeholder="Preenchido automaticamente pelo sistema">
                </div>
                <div class="form-group">
                    <label>Responsável legal do PA:</label>
                    <input type="text" name="decl_resp_pa" value="<?php echo $nome_parceiro_criador_doc; ?>" readonly placeholder="<?php echo $nome_parceiro_criador_doc; ?>">
                </div>
            </div>
            
            <div id="formDocMessage" class="form-message"></div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" <?php echo $disabled_publico_apos_finalizacao_parceiro; ?>><i class="fas fa-save"></i> Salvar Progresso</button>

            </div>
        </form>
    </main>
    <footer class="main-footer"><div class="container"><p>Copyright © 2025 Devzgroup.</p></div></footer>
    <script>
        const DOCUMENTO_UID_ATUAL = <?php echo json_encode($documento_uid_param); ?>;
        const IS_PARCEIRO_DONO = <?php echo json_encode($parceiro_pode_editar_restrito); ?>;
        const STATUS_DOCUMENTO_ATUAL = <?php echo json_encode($dados_documento['status_documento'] ?? 'Rascunho'); ?>;
    // Pass the current bank statement path to JS if needed for dynamic updates, though not strictly necessary for this request
    // const BANCO_COMPROVANTE_ATUAL = <?php echo json_encode($dados_documento['banco_comprovante_path'] ?? null); ?>;
    </script>
    <script src="js/documentos-indicacao-script.js"></script> 
    <?php if ($parceiro_pode_editar_restrito): ?>
    <script> /* Script de Logout para o parceiro nesta página */
    document.addEventListener('DOMContentLoaded', function() {
        const logoutButton = document.getElementById('logoutButtonForm');
        if(logoutButton) {
            logoutButton.addEventListener('click', async function(e) {
                e.preventDefault();
                try { await fetch('api/logout.php', { method: 'POST', credentials: 'include' }); window.location.href = 'partner-login.html'; }
                catch (error) { console.error('Erro ao fazer logout:', error); window.location.href = 'partner-login.html';}
            });
        }
    });
    </script>
    <?php endif; ?>
</body>
</html>