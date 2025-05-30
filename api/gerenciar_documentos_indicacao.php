<?php
session_start();
ob_start();

define('DB_HOST', 'mysql64-farm2.uni5.net');
define('DB_USER', 'devzgroup');
define('DB_PASS', 'D3vzgr0up');
define('DB_NAME', 'devzgroup');

// Define the upload directory relative to this API script's location
// Assumes 'api' is a subdirectory, and 'uploads' is a sibling to 'api' (i.e., in the web root)
define('UPLOAD_DIR', __DIR__ . '/../uploads/'); 
// Ensure UPLOAD_DIR has a trailing slash if not already included by __DIR__ logic.
// For robustness: define('UPLOAD_DIR', rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);


// ... (headers e getDbConnection como antes) ...
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: https://devzgroup.com.br");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit;
}

function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("DB Connection Error: " . $conn->connect_error);
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Erro de conexão com o banco."]);
        exit;
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

function gen_uuid_v4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$parceiro_id_sessao = null;
$is_parceiro_logado_para_api = false;

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'parceiro') {
    $parceiro_id_sessao = (int)$_SESSION['user_id'];
    $is_parceiro_logado_para_api = true;
}


switch ($action) {

    case 'generate_new_document_link':
    if (!$is_parceiro_logado_para_api) {
    http_response_code(403); echo json_encode(["success" => false, "message" => "Acesso não autorizado para gerar link."]); exit;
    }
    $nome_documento_req = trim(filter_input(INPUT_POST, 'nome_documento', FILTER_SANITIZE_STRING));
    if (empty($nome_documento_req)) {
    $nome_documento_req = 'Documento de Indicação (sem nome)';
    }
    $conn = getDbConnection();
    $documento_uid = gen_uuid_v4();
    $status_inicial = 'Pendente de Preenchimento';
    // Valores padrão para campos restritos ao parceiro
    $pagamento_tipo_default = 'Split'; 
    $obs_pa_indicacoes_default = '';
    $banco_comprovante_path_default = null; // Initialize new field 
    // Tabela de valores padrão (produtos fixos, valores vazios ou padrão)
    // Adicionado "visivel" => true para cada item
    $tabela_valores_default = json_encode([
    ["produto" => "Kit PF A3 1 ano + Smart card", "custo_jed" => "97.00", "venda_cliente_final" => "198.00", "visivel" => false],
    ["produto" => "Kit PF A3 1 anos + Smart card + Leitora", "custo_jed" => "202.00", "venda_cliente_final" => "295.00", "visivel" => false],
    ["produto" => "Kit PF A3 1 ano + Token", "custo_jed" => "137.00", "venda_cliente_final" => "245.00", "visivel" => false],
    ["produto" => "Kit PF A3 2 anos + Smart card", "custo_jed" => "117.00", "venda_cliente_final" => "239.00", "visivel" => false],
    ["produto" => "Kit PF A3 2 anos + Smart card + Leitora", "custo_jed" => "222.00", "venda_cliente_final" => "324.00", "visivel" => false],
    ["produto" => "Kit PF A3 2 ano + Token", "custo_jed" => "157.00", "venda_cliente_final" => "275.00", "visivel" => false],
    ["produto" => "Kit PF A3 3 anos + Smart card", "custo_jed" => "127.00", "venda_cliente_final" => "265.00", "visivel" => false],
    ["produto" => "Kit PF A3 3 anos + Smart card + Leitora", "custo_jed" => "232.00", "venda_cliente_final" => "355.00", "visivel" => false],
    ["produto" => "Kit PF A3 3 anos + Token", "custo_jed" => "167.00", "venda_cliente_final" => "295.00", "visivel" => false],
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
    ]);

        $stmt = $conn->prepare("INSERT INTO documentos_indicacao (parceiro_id, documento_uid, nome_documento, status_documento, pagamento_tipo, obs_pa_indicacoes, tabela_valores_json, banco_comprovante_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) { http_response_code(500); echo json_encode(["success" => false, "message" => "Erro INSERT prepare: " . $conn->error]); exit; }
        // Corrected bind_param types from "issss" to "isssssss" (1 i, 7 s for 8 params)
        $stmt->bind_param("isssssss", $parceiro_id_sessao, $documento_uid, $nome_documento_req, $status_inicial, $pagamento_tipo_default, $obs_pa_indicacoes_default, $tabela_valores_default, $banco_comprovante_path_default);
    
        if ($stmt->execute()) {
        $link = "https://devzgroup.com.br/teste/formulario-indicacao.php?uid=" . $documento_uid;
        echo json_encode(["success" => true, "message" => "Link gerado com sucesso!", "documento_uid" => $documento_uid, "link_compartilhavel" => $link, "nome_documento" => $nome_documento_req]);
        } else {
        http_response_code(500); echo json_encode(["success" => false, "message" => "Erro ao gerar link: " . $stmt->error]);
        }
        $stmt->close(); $conn->close();
        break;

    case 'save_documento_public':
        $documento_uid = trim(filter_input(INPUT_POST, 'documento_uid', FILTER_SANITIZE_STRING));
        if (empty($documento_uid)) {
            http_response_code(400); echo json_encode(["success" => false, "message" => "UID do documento é obrigatório."]); exit;
        }

        $conn = getDbConnection();

        $stmt_get_current = $conn->prepare("SELECT parceiro_id, pagamento_tipo, obs_pa_indicacoes, tabela_valores_json, status_documento, banco_comprovante_path FROM documentos_indicacao WHERE documento_uid = ?");
        if (!$stmt_get_current) { http_response_code(500); echo json_encode(["success" => false, "message" => "Erro ao buscar doc atual (prepare): " . $conn->error]); $conn->close(); exit;}
        $stmt_get_current->bind_param("s", $documento_uid);
        if(!$stmt_get_current->execute()){ http_response_code(500); echo json_encode(["success" => false, "message" => "Erro ao buscar doc atual (execute): " . $stmt_get_current->error]); $conn->close(); exit;}
        $result_current = $stmt_get_current->get_result();
        $current_doc_data = $result_current->fetch_assoc();
        $stmt_get_current->close();

        if (!$current_doc_data) {
        http_response_code(404); echo json_encode(["success" => false, "message" => "Documento não encontrado."]); $conn->close(); exit;
        }

        if ($current_doc_data['status_documento'] === 'Finalizado pelo Parceiro' || $current_doc_data['status_documento'] === 'Assinado') {
        http_response_code(403); echo json_encode(["success" => false, "message" => "Este documento já foi finalizado pelo parceiro e não pode mais ser alterado."]); $conn->close(); exit;
        }

        $is_parceiro_editando_este_doc = $is_parceiro_logado_para_api && ($parceiro_id_sessao === (int)$current_doc_data['parceiro_id']);
        $data = []; // Initialize array for data to be updated ONCE at the beginning

        // Handle file upload/removal for banco_comprovante
        $upload_error_message = '';
        if (isset($_POST['remover_banco_comprovante']) && $_POST['remover_banco_comprovante'] == '1') {
            if (!empty($current_doc_data['banco_comprovante_path'])) {
                $filePathToDelete = UPLOAD_DIR . $current_doc_data['banco_comprovante_path'];
                if (file_exists($filePathToDelete)) {
                    unlink($filePathToDelete);
                }
            }
            $data['banco_comprovante_path'] = null;
        } elseif (isset($_FILES['banco_comprovante']) && $_FILES['banco_comprovante']['error'] == UPLOAD_ERR_OK) {
            if (!is_dir(UPLOAD_DIR) || !is_writable(UPLOAD_DIR)) {
                $upload_error_message = "Erro no servidor: Diretório de upload não configurado ou sem permissão.";
                error_log("Upload directory issue: " . UPLOAD_DIR . " is not a dir or not writable.");
            } else {
                $fileTmpPath = $_FILES['banco_comprovante']['tmp_name'];
                $fileName = $_FILES['banco_comprovante']['name'];
                $fileSize = $_FILES['banco_comprovante']['size'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                $safeFileNameBase = preg_replace('/[^A-Za-z0-9\-_]/', '', pathinfo($fileName, PATHINFO_FILENAME));
                $newFileName = $documento_uid . '_' . time() . '_' . $safeFileNameBase . '.' . $fileExtension;
                $dest_path = UPLOAD_DIR . $newFileName;
                $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
                $maxFileSize = 5 * 1024 * 1024; // 5MB

                if (!in_array($fileExtension, $allowedfileExtensions)) {
                    $upload_error_message = "Tipo de arquivo inválido. Permitidos: JPG, PNG, PDF.";
                } elseif ($fileSize > $maxFileSize) {
                    $upload_error_message = "Arquivo muito grande. Tamanho máximo: 5MB.";
                } elseif (move_uploaded_file($fileTmpPath, $dest_path)) {
                    if (!empty($current_doc_data['banco_comprovante_path']) && $current_doc_data['banco_comprovante_path'] !== $newFileName) {
                        $oldFilePath = UPLOAD_DIR . $current_doc_data['banco_comprovante_path'];
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                    $data['banco_comprovante_path'] = $newFileName;
                } else {
                    $upload_error_message = "Erro ao salvar o arquivo comprovante.";
                }
            }
        } elseif (isset($_FILES['banco_comprovante']) && $_FILES['banco_comprovante']['error'] != UPLOAD_ERR_NO_FILE) {
            $upload_error_message = "Erro no upload do arquivo: código " . $_FILES['banco_comprovante']['error'];
        }

        if (!empty($upload_error_message)) {
            http_response_code(400); 
            echo json_encode(["success" => false, "message" => $upload_error_message]); 
            $conn->close(); 
            exit;
        }

        $fields_publicos = [ 
            'ag_nome_razao_social', 'ag_nome_fantasia', 'ag_endereco', 'ag_complemento', 'ag_bairro', 
            'ag_cidade', 'ag_cep', 'ag_uf', 'ag_cpf_cnpj', 'ag_representante_legal', 'ag_cargo', 
            'ag_cpf_representante', 'ag_rg_representante', 'ag_email', 'ag_telefone',
            'banco_nome_razao_social', 'banco_cpf_cnpj', 'banco_nome', 'banco_agencia', 'banco_conta', 
            'banco_tipo_conta', 'banco_chave_pix',
            'obs_anotacoes',
            'decl_local', 'decl_data', 'decl_resp_parceiro', 
        ];
        // IMPORTANT FIX: Removed the second "$data = [];" line that was here in Search Result 2.
        // Now, $data will retain banco_comprovante_path if it was set by the file upload logic.
        foreach ($fields_publicos as $field) {
            if (isset($_POST[$field])) { 
                $data[$field] = trim(filter_input(INPUT_POST, $field, FILTER_SANITIZE_STRING));
            }
        }
        if (isset($_POST['ag_email'])) $data['ag_email'] = filter_input(INPUT_POST, 'ag_email', FILTER_VALIDATE_EMAIL) ? $_POST['ag_email'] : ($current_doc_data['ag_email'] ?? ''); 
        if (isset($_POST['decl_data'])) $data['decl_data'] = !empty($_POST['decl_data']) ? date('Y-m-d', strtotime($_POST['decl_data'])) : ($current_doc_data['decl_data'] ?? null);

        if ($is_parceiro_editando_este_doc) {
            if (isset($_POST['pagamento_tipo'])) $data['pagamento_tipo'] = in_array($_POST['pagamento_tipo'], ['Split', 'Mensal']) ? $_POST['pagamento_tipo'] : $current_doc_data['pagamento_tipo'];
            if (isset($_POST['obs_pa_indicacoes'])) $data['obs_pa_indicacoes'] = trim(filter_input(INPUT_POST, 'obs_pa_indicacoes', FILTER_SANITIZE_STRING));
        } else {
            // If not partner, ensure these fields are not taken from POST if they were somehow submitted
            // and instead retain their current DB values if they are not meant to be updated by non-partners.
            // However, if they are not in $fields_publicos, they won't be added to $data from POST anyway.
            // For safety, if these fields are part of $data due to other logic, ensure they are set from $current_doc_data.
            if (array_key_exists('pagamento_tipo', $data) && !$is_parceiro_editando_este_doc) {
                 $data['pagamento_tipo'] = $current_doc_data['pagamento_tipo'];
            }
             if (array_key_exists('obs_pa_indicacoes', $data) && !$is_parceiro_editando_este_doc) {
                 $data['obs_pa_indicacoes'] = $current_doc_data['obs_pa_indicacoes'];
            }
        }

            // Lidar com a tabela_valores_json
            $tabela_valores_input_do_form = $_POST['tabela_valores'] ?? [];
        $tabela_valores_do_bd = json_decode($current_doc_data['tabela_valores_json'], true) ?: [];
        $nova_tabela_valores_para_salvar = [];

        foreach ($tabela_valores_do_bd as $index_bd => $item_bd) {
            $novo_item_para_salvar = $item_bd; 
            if (isset($tabela_valores_input_do_form[$index_bd])) {
                $item_do_formulario = $tabela_valores_input_do_form[$index_bd];
                if (isset($item_do_formulario['produto']) && $item_do_formulario['produto'] === $item_bd['produto']) {
                    if (isset($item_do_formulario['venda_cliente_final'])) {
                        $novo_item_para_salvar['venda_cliente_final'] = filter_var($item_do_formulario['venda_cliente_final'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    }
                    if ($is_parceiro_editando_este_doc) {
                        if (isset($item_do_formulario['custo_jed'])) {
                            $novo_item_para_salvar['custo_jed'] = filter_var($item_do_formulario['custo_jed'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                        }
                        if (isset($item_do_formulario['visivel'])) {
                            $novo_item_para_salvar['visivel'] = ($item_do_formulario['visivel'] === 'true');
                        } else {
                            $novo_item_para_salvar['visivel'] = $item_bd['visivel'] ?? true;
                        }
                    } else {
                        $novo_item_para_salvar['custo_jed'] = $item_bd['custo_jed'] ?? '';
                        $novo_item_para_salvar['visivel'] = $item_bd['visivel'] ?? true; 
                    }
                } else {
                    $novo_item_para_salvar['visivel'] = $item_bd['visivel'] ?? true;
                }
            } else {
                $novo_item_para_salvar['visivel'] = $item_bd['visivel'] ?? true;
            }
            $nova_tabela_valores_para_salvar[] = $novo_item_para_salvar;
        }
        // Only add to $data if it has changed or is always needed
        $new_tabela_json = json_encode($nova_tabela_valores_para_salvar);
        if ($new_tabela_json !== $current_doc_data['tabela_valores_json']) {
            $data['tabela_valores_json'] = $new_tabela_json;
        }

        $isBankStatementPresent = false;
        if (isset($data['banco_comprovante_path'])) { // New upload or explicit removal to null
            if ($data['banco_comprovante_path'] !== null) {
                $isBankStatementPresent = true;
            }
        } elseif (!empty($current_doc_data['banco_comprovante_path'])) { // Existed before and not touched by current POST
            $isBankStatementPresent = true;
        }
        if ($current_doc_data['status_documento'] !== 'Finalizado pelo Cliente' && $current_doc_data['status_documento'] !== 'Finalizado pelo Parceiro' && $current_doc_data['status_documento'] !== 'Assinado') {
            // Use values from $data if available, otherwise from $current_doc_data
            $check_ag_nome = $data['ag_nome_razao_social'] ?? $current_doc_data['ag_nome_razao_social'];
            $check_ag_cpf_cnpj = $data['ag_cpf_cnpj'] ?? $current_doc_data['ag_cpf_cnpj'];

            $todosCamposObrigatoriosPreenchidos = !empty($check_ag_nome) && !empty($check_ag_cpf_cnpj) && $isBankStatementPresent;

            if ($todosCamposObrigatoriosPreenchidos) {
                $data['status_documento'] = 'Preenchido';
            } else {
                $data['status_documento'] = 'Preenchimento em Andamento';
            }
        } else {
            // If status is already finalized, don't change it unless specific logic allows
            // For now, we ensure it's not accidentally changed if it's already some form of "Finalizado" or "Assinado"
            if (isset($data['status_documento']) && $data['status_documento'] !== $current_doc_data['status_documento']) {
                // If $data['status_documento'] was set by the logic above, respect it
                // otherwise, ensure it's not changed from a finalized state
            } else {
                 $data['status_documento'] = $current_doc_data['status_documento'];
            }
        }
        // Ensure status_documento is part of $data if it changed or was calculated
        if ($data['status_documento'] === $current_doc_data['status_documento'] && !array_key_exists('status_documento', $data)){
            // If status didn't change and wasn't explicitly added to $data by the logic above,
            // remove it from $data to avoid unnecessary update if it's the only field.
            // However, it's safer to always include it if it's calculated.
            // The check `if (empty($data))` below handles no actual changes.
        }


        if (empty($data)) { 
            echo json_encode([
                "success" => true, 
                "message" => "Nenhuma alteração detectada.", 
                "documento_uid" => $documento_uid, 
                "status_documento" => $current_doc_data['status_documento'], // return current status
                "banco_comprovante_path" => $current_doc_data['banco_comprovante_path'] // return current path
            ]);
            $conn->close(); exit;
        }

        $set_clauses = [];
        $values_to_bind = []; // Values for the SET part
        $types = '';          // Types for the SET part

        foreach ($data as $key => $value) {
            $set_clauses[] = "$key = ?";
            $values_to_bind[] = $value;
            $types .= 's'; // Assuming all string types for simplicity; adjust if specific types are needed (e.g., integer for ID)
        }

        $sql_set = implode(', ', $set_clauses);

        $stmt = $conn->prepare("UPDATE documentos_indicacao SET $sql_set WHERE documento_uid = ?");
        // Add check for prepare failure
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao preparar atualização do documento: " . $conn->error]);
            $conn->close();
            exit;
        }

        // Add the UID to the values and its type
        $values_to_bind[] = $documento_uid;
        $types .= 's';

        $stmt->bind_param($types, ...$values_to_bind); // Spread operator for values

        if ($stmt->execute()) {
            $response_data = [
                "success" => true, 
                "message" => "Documento salvo com sucesso!", 
                "documento_uid" => $documento_uid, 
                "status_documento" => $data['status_documento'] ?? $current_doc_data['status_documento']
            ];

            // Determine the correct banco_comprovante_path for the response
            if (isset($data['banco_comprovante_path'])) { // Path was changed (uploaded or removed)
                $response_data['banco_comprovante_path'] = $data['banco_comprovante_path'];
            } else { // Path was not part of the $data array (i.e., not changed in this request)
                $response_data['banco_comprovante_path'] = $current_doc_data['banco_comprovante_path'];
            }

            echo json_encode($response_data);
        } else {
            http_response_code(500); echo json_encode(["success" => false, "message" => "Erro ao salvar documento: " . $stmt->error]);
        }
        $stmt->close(); $conn->close();
        break;

    // ... (update_documento_nome, get_documentos, get_documento_details_public, delete_documento, finalize_documento_parceiro como antes) ...
    // A ação 'finalize_documento_public' foi removida. O usuário do link público não "finaliza" mais explicitamente,
    // o status muda para 'Preenchido' quando os campos obrigatórios são preenchidos.
    // O parceiro então usa 'finalize_documento_parceiro'.

    case 'update_documento_nome':
        if (!$is_parceiro_logado_para_api) {
            http_response_code(403); echo json_encode(["success" => false, "message" => "Acesso não autorizado."]); exit;
            }
            $documento_uid = trim(filter_input(INPUT_POST, 'documento_uid', FILTER_SANITIZE_STRING));
            $novo_nome_documento = trim(filter_input(INPUT_POST, 'nome_documento', FILTER_SANITIZE_STRING));
            if (empty($documento_uid) || empty($novo_nome_documento)) {
            http_response_code(400); echo json_encode(["success" => false, "message" => "UID e novo nome do documento são obrigatórios."]); exit;
            }
            $conn = getDbConnection();
            $stmt = $conn->prepare("UPDATE documentos_indicacao SET nome_documento = ? WHERE documento_uid = ? AND parceiro_id = ?");
            if (!$stmt) { http_response_code(500); echo json_encode(["success" => false, "message" => "Erro UPDATE NOME prepare: " . $conn->error]); exit; }
            $stmt->bind_param("ssi", $novo_nome_documento, $documento_uid, $parceiro_id_sessao);
            if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Nome do documento atualizado com sucesso!"]);
            } else {
            echo json_encode(["success" => false, "message" => "Nenhuma alteração no nome ou documento não encontrado/não pertence a você."]);
            }
            } else {
            http_response_code(500); echo json_encode(["success" => false, "message" => "Erro ao atualizar nome do documento: " . $stmt->error]);
            }
            $stmt->close(); $conn->close();
            break;

    case 'get_documentos':
        if (!$is_parceiro_logado_para_api) {
            http_response_code(403); echo json_encode(["success" => false, "message" => "Acesso não autorizado."]); exit;
            }
            $conn = getDbConnection();
            $stmt = $conn->prepare("SELECT id, documento_uid, nome_documento, ag_nome_razao_social, status_documento, DATE_FORMAT(data_criacao, '%Y-%m-%dT%H:%i:%sZ') as data_criacao, banco_comprovante_path FROM documentos_indicacao WHERE parceiro_id = ? ORDER BY data_criacao DESC");
            if (!$stmt) { http_response_code(500); echo json_encode(["success" => false, "message" => "Erro prepare: " . $conn->error]); exit; }
            $stmt->bind_param("i", $parceiro_id_sessao);
            $stmt->execute();
            $result = $stmt->get_result();
            $documentos = [];
            while ($row = $result->fetch_assoc()) { $documentos[] = $row; }
            echo json_encode(["success" => true, "data" => $documentos]);
            $stmt->close(); $conn->close();
            break;
    
            case 'get_documento_details_public': // Usado por formulario-indicacao.php
                $documento_uid = trim(filter_input(INPUT_GET, 'uid', FILTER_SANITIZE_STRING));
                if (empty($documento_uid)) {
                    http_response_code(400); echo json_encode(["success" => false, "message" => "UID do documento é obrigatório."]); exit;
                }
                $conn = getDbConnection();
                // Modificado para buscar nome do parceiro criador
                $stmt = $conn->prepare(
                    "SELECT d.*, u.nome_completo as nome_parceiro_criador, u.username as username_parceiro_criador " .
                    "FROM documentos_indicacao d " .
                    "LEFT JOIN usuarios u ON d.parceiro_id = u.id " .
                    "WHERE d.documento_uid = ?"
                );
                if (!$stmt) { http_response_code(500); echo json_encode(["success" => false, "message" => "Erro prepare: " . $conn->error]); $conn->close(); exit; }
                $stmt->bind_param("s", $documento_uid);
                $stmt->execute();
                $result = $stmt->get_result();
                $documento_data = $result->fetch_assoc();
            
                if ($documento_data) {
                    // Determinar o nome do parceiro criador para o template Word
                    $nome_final_parceiro_criador = 'Parceiro Devzgroup'; // Valor padrão
                    if (!empty(trim($documento_data['nome_parceiro_criador']))) {
                        $nome_final_parceiro_criador = trim($documento_data['nome_parceiro_criador']);
                    } elseif (!empty(trim($documento_data['username_parceiro_criador']))) {
                        $nome_final_parceiro_criador = trim($documento_data['username_parceiro_criador']);
                    }
                    // Adicionar o nome determinado ao array de dados para ser usado no JS
                    $documento_data['fetched_decl_resp_pa'] = $nome_final_parceiro_criador;
            
                    echo json_encode(["success" => true, "data" => $documento_data]);
                } else {
                    http_response_code(404); echo json_encode(["success" => false, "message" => "Documento não encontrado."]);
                }
                $stmt->close(); $conn->close();
                break;

    case 'delete_documento':
        if (!$is_parceiro_logado_para_api) {
            http_response_code(403); echo json_encode(["success" => false, "message" => "Acesso não autorizado."]); exit;
            }
            $documento_uid = trim(filter_input(INPUT_POST, 'documento_uid', FILTER_SANITIZE_STRING));
            if (empty($documento_uid)) {
            http_response_code(400); echo json_encode(["success" => false, "message" => "UID do documento é obrigatório."]); exit;
            }
            $conn = getDbConnection();
            $stmt_get_path = $conn->prepare("SELECT banco_comprovante_path FROM documentos_indicacao WHERE documento_uid = ? AND parceiro_id = ?");
            if (!$stmt_get_path) { http_response_code(500); echo json_encode(["success" => false, "message" => "Erro prepare (get path): " . $conn->error]); $conn->close(); exit; }
            $stmt_get_path->bind_param("si", $documento_uid, $parceiro_id_sessao);
            $stmt_get_path->execute();
            $result_path = $stmt_get_path->get_result();
            $doc_file_data = $result_path->fetch_assoc();
            $stmt_get_path->close();
        
            $stmt = $conn->prepare("DELETE FROM documentos_indicacao WHERE documento_uid = ? AND parceiro_id = ?");
            if (!$stmt) { http_response_code(500); echo json_encode(["success" => false, "message" => "Erro prepare (delete): " . $conn->error]); $conn->close(); exit; }
            $stmt->bind_param("si", $documento_uid, $parceiro_id_sessao);
            
            if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
            if ($doc_file_data && !empty($doc_file_data['banco_comprovante_path'])) {
            $filePathToDelete = UPLOAD_DIR . $doc_file_data['banco_comprovante_path'];
            if (file_exists($filePathToDelete)) {
            unlink($filePathToDelete);
            }
            }
            echo json_encode(["success" => true, "message" => "Documento excluído com sucesso!"]);
            } else {
            http_response_code(404); echo json_encode(["success" => false, "message" => "Documento não encontrado ou não pertence a você."]);
            }
            } else {
            http_response_code(500); echo json_encode(["success" => false, "message" => "Erro ao excluir documento: " . $stmt->error]);
            }
            $stmt->close(); $conn->close();
            break;

    case 'finalize_documento_parceiro':
        // ... (code from Search Result 2, ensure UPLOAD_DIR is correctly used if any file interaction happens here)
        // The version from my previous response had a more detailed check for banco_comprovante_path here.
        if (!$is_parceiro_logado_para_api) {
            http_response_code(403); echo json_encode(["success" => false, "message" => "Acesso não autorizado."]); exit;
        }
        $documento_uid = trim(filter_input(INPUT_POST, 'documento_uid', FILTER_SANITIZE_STRING));
        if (empty($documento_uid)) {
            http_response_code(400); echo json_encode(["success" => false, "message" => "UID do documento é obrigatório."]); exit;
        }
        $conn = getDbConnection();
    
        // Check if bank statement is uploaded before finalizing
        $checkStmt = $conn->prepare("SELECT status_documento, banco_comprovante_path FROM documentos_indicacao WHERE documento_uid = ? AND parceiro_id = ?");
        if (!$checkStmt) { http_response_code(500); echo json_encode(["success" => false, "message" => "Erro ao verificar documento (prepare): " . $conn->error]); $conn->close(); exit; }
        $checkStmt->bind_param("si", $documento_uid, $parceiro_id_sessao); // Corrected to "si"
        if(!$checkStmt->execute()){ http_response_code(500); echo json_encode(["success" => false, "message" => "Erro ao verificar documento (execute): " . $checkStmt->error]); $conn->close(); exit; }
        $docResult = $checkStmt->get_result();
        $docDataForFinalize = $docResult->fetch_assoc();
        $checkStmt->close();
    
        if (!$docDataForFinalize) {
            http_response_code(404); echo json_encode(["success" => false, "message" => "Documento não encontrado ou não pertence a você."]); $conn->close(); exit;
        }
        if (empty($docDataForFinalize['banco_comprovante_path'])) {
            echo json_encode(["success" => false, "message" => "Não foi possível finalizar. O comprovante bancário é obrigatório."]); $conn->close(); exit;
        }
        if (!in_array($docDataForFinalize['status_documento'], ['Preenchido', 'Finalizado pelo Cliente', 'Preenchimento em Andamento'])) {
             echo json_encode(["success" => false, "message" => "Documento não pode ser finalizado neste status ('{$docDataForFinalize['status_documento']}'). Verifique se já foi finalizado ou se está pendente."]); $conn->close(); exit;
        }
    
    
        $stmt = $conn->prepare("UPDATE documentos_indicacao SET status_documento = 'Finalizado pelo Parceiro' 
            WHERE documento_uid = ? AND parceiro_id = ? 
            AND (status_documento = 'Preenchido' OR status_documento = 'Finalizado pelo Cliente' OR status_documento = 'Preenchimento em Andamento')");
        if (!$stmt) { http_response_code(500); echo json_encode(["success" => false, "message" => "Erro prepare (finalize): " . $conn->error]); $conn->close(); exit; }
        $stmt->bind_param("si", $documento_uid, $parceiro_id_sessao);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(["success" => true, "message" => "Documento finalizado pelo parceiro!"]);
            } else {
                // Re-fetch to give a more accurate message if already finalized or other condition
                $conn_check_again = getDbConnection(); // New connection for safety or use existing
                $check_again_stmt = $conn_check_again->prepare("SELECT status_documento FROM documentos_indicacao WHERE documento_uid = ? AND parceiro_id = ?");
                $check_again_stmt->bind_param("si", $documento_uid, $parceiro_id_sessao);
                $check_again_stmt->execute();
                $status_res = $check_again_stmt->get_result()->fetch_assoc();
                $check_again_stmt->close();
                $conn_check_again->close();
    
                if ($status_res && ($status_res['status_documento'] === 'Finalizado pelo Parceiro' || $status_res['status_documento'] === 'Assinado')) {
                     echo json_encode(["success" => false, "message" => "Documento já se encontra finalizado."]);
                } else {
                     echo json_encode(["success" => false, "message" => "Não foi possível finalizar o documento. Verifique o status ou se todos os campos obrigatórios (incluindo comprovante bancário) foram preenchidos."]);
                }
            }
        } else {
            http_response_code(500); echo json_encode(["success" => false, "message" => "Erro ao atualizar status para finalizado: " . $stmt->error]);
        }
        $stmt->close(); $conn->close();
        break;
    
    case 'save_word_document':
        if (!$is_parceiro_logado_para_api) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Acesso não autorizado para salvar documento Word."]);
            exit;
        }

        $documento_uid_req = trim(filter_input(INPUT_POST, 'documento_uid', FILTER_SANITIZE_STRING));
        $parceiro_id_req = filter_input(INPUT_POST, 'parceiro_id', FILTER_VALIDATE_INT);
        $client_fileName = trim(filter_input(INPUT_POST, 'fileName', FILTER_SANITIZE_STRING));

        if (empty($documento_uid_req) || empty($client_fileName)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "UID do documento e nome do arquivo são obrigatórios."]);
            exit;
        }
        // Verifica se o parceiro_id enviado no formulário corresponde ao da sessão
        if ($parceiro_id_req !== $parceiro_id_sessao) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "ID do parceiro inválido para salvar o documento."]);
            exit;
        }
        
        // Validação adicional: Verificar se o documento_uid pertence ao parceiro logado
        $conn_check = getDbConnection();
        $stmt_check_owner = $conn_check->prepare("SELECT id FROM documentos_indicacao WHERE documento_uid = ? AND parceiro_id = ?");
        if (!$stmt_check_owner) {
            error_log("Erro ao preparar statement de verificação de proprietário: " . $conn_check->error);
            http_response_code(500); echo json_encode(["success" => false, "message" => "Erro ao verificar proprietário do documento (prepare)."]); $conn_check->close(); exit;
        }
        $stmt_check_owner->bind_param("si", $documento_uid_req, $parceiro_id_sessao);
        if (!$stmt_check_owner->execute()) {
            error_log("Erro ao executar statement de verificação de proprietário: " . $stmt_check_owner->error);
            http_response_code(500); echo json_encode(["success" => false, "message" => "Erro ao verificar proprietário do documento (execute)."]); $stmt_check_owner->close(); $conn_check->close(); exit;
        }
        $result_check_owner = $stmt_check_owner->get_result();
        if ($result_check_owner->num_rows === 0) {
            http_response_code(403); echo json_encode(["success" => false, "message" => "Documento não pertence ao parceiro logado ou não encontrado."]); $stmt_check_owner->close(); $conn_check->close(); exit;
        }
        $stmt_check_owner->close();
        $conn_check->close();


        if (isset($_FILES['documento_word']) && $_FILES['documento_word']['error'] == UPLOAD_ERR_OK) {
            $word_upload_dir_relative = '../documentos/'; // Um nível acima de 'api', depois para 'documentos'
            // Adicionado realpath para resolver caminhos relativos de forma mais robusta e DIRECTORY_SEPARATOR para compatibilidade
            $word_upload_dir_absolute = realpath(__DIR__ . DIRECTORY_SEPARATOR . $word_upload_dir_relative);

            // Verifica se realpath retornou um caminho válido e adiciona a barra no final se necessário
            if (!$word_upload_dir_absolute) {
                 error_log("Falha ao resolver o caminho absoluto para o diretório de upload de Word: " . __DIR__ . DIRECTORY_SEPARATOR . $word_upload_dir_relative);
                 http_response_code(500);
                 echo json_encode(["success" => false, "message" => "Erro no servidor: Não foi possível determinar o diretório de destino para o documento Word."]);
                 exit;
            }
            $word_upload_dir_absolute .= DIRECTORY_SEPARATOR;


            if (!is_dir($word_upload_dir_absolute)) {
                if (!mkdir($word_upload_dir_absolute, 0775, true)) { // Cria recursivamente com permissões
                    error_log("Falha ao criar diretório de upload de Word: " . $word_upload_dir_absolute);
                    http_response_code(500);
                    echo json_encode(["success" => false, "message" => "Erro no servidor: Não foi possível criar o diretório de destino para o documento Word."]);
                    exit;
                }
            }

            if (!is_writable($word_upload_dir_absolute)) {
                error_log("Diretório de upload de Word não tem permissão de escrita: " . $word_upload_dir_absolute);
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Erro no servidor: Diretório de destino para o documento Word sem permissão de escrita."]);
                exit;
            }

            $baseName = pathinfo($client_fileName, PATHINFO_FILENAME);
            $extension = pathinfo($client_fileName, PATHINFO_EXTENSION);
            if (strtolower($extension) !== 'docx') {
                 http_response_code(400);
                 echo json_encode(["success" => false, "message" => "Tipo de arquivo inválido. Somente .docx é permitido."]);
                 exit;
            }
            $sanitizedBaseName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $baseName);
            $server_fileName = $sanitizedBaseName . '.' . $extension; // O JS já cria um nome único
            $target_file_path = $word_upload_dir_absolute . $server_fileName;

            if (move_uploaded_file($_FILES['documento_word']['tmp_name'], $target_file_path)) {
                // Opcional: Salvar o caminho do documento Word no banco de dados
                // $conn_update = getDbConnection();
                // $stmt_update_path = $conn_update->prepare("UPDATE documentos_indicacao SET path_documento_word = ? WHERE documento_uid = ? AND parceiro_id = ?");
                // if ($stmt_update_path) {
                //     $relative_path_for_db = 'documentos/' . $server_fileName; // Salva caminho relativo à raiz da web
                //     $stmt_update_path->bind_param("ssi", $relative_path_for_db, $documento_uid_req, $parceiro_id_sessao);
                //     $stmt_update_path->execute();
                //     $stmt_update_path->close();
                // }
                // $conn_update->close();

                echo json_encode(["success" => true, "message" => "Documento Word '" . htmlspecialchars($server_fileName) . "' salvo no servidor com sucesso.", "path" => 'documentos/' . $server_fileName]);
            } else {
                error_log("Falha ao mover arquivo Word para: " . $target_file_path . ". Erro PHP: " . $_FILES['documento_word']['error']);
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Erro ao salvar o documento Word no servidor."]);
            }
        } else {
            http_response_code(400);
            $upload_error = isset($_FILES['documento_word']['error']) ? $_FILES['documento_word']['error'] : 'Nenhum arquivo recebido';
            error_log("Nenhum arquivo 'documento_word' recebido ou erro no upload. Código de erro: " . $upload_error);
            echo json_encode(["success" => false, "message" => "Nenhum arquivo 'documento_word' recebido ou erro no upload. Código: " . $upload_error]);
        }
        break;

        default:
        http_response_code(400);
        // Log da ação recebida para depuração no servidor
        error_log("API gerenciar_documentos_indicacao.php: Ação inválida ou não especificada. Ação recebida: '" . $action . "'. Dados POST: " . print_r($_POST, true));
        
        // Envia uma mensagem mais informativa de volta para o cliente
        echo json_encode([
            "success" => false,
            "message" => "Ação inválida ou não especificada no servidor. Ação que o servidor recebeu: '" . htmlspecialchars($action) . "'. Verifique se a ação 'save_word_document' está sendo enviada corretamente.",
            "received_action_on_server" => $action // Inclui a ação recebida no JSON
        ]);
        break;
    }
ob_end_flush();
?>