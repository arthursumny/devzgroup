<?php
session_start();
ob_start();

define('DB_HOST', 'mysql64-farm2.uni5.net');
define('DB_USER', 'devzgroup');
define('DB_PASS', 'D3vzgr0up');
define('DB_NAME', 'devzgroup');

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

// Verificar autenticação do parceiro
$parceiro_id_sessao = null;
$is_parceiro_logado = false;

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'parceiro') {
    $parceiro_id_sessao = (int)$_SESSION['user_id'];
    $is_parceiro_logado = true;
}

if (!$is_parceiro_logado) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Acesso não autorizado."]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_propostas':
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT id, numero_proposta, nome_cliente, status_proposta, data_criacao, 
                               data_validade, valor_total FROM propostas 
                               WHERE parceiro_id = ? ORDER BY data_criacao DESC");
        
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao preparar consulta: " . $conn->error]);
            exit;
        }

        $stmt->bind_param("i", $parceiro_id_sessao);
        
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao executar consulta: " . $stmt->error]);
            exit;
        }

        $result = $stmt->get_result();
        $propostas = [];
        
        while ($row = $result->fetch_assoc()) {
            $propostas[] = $row;
        }

        echo json_encode(["success" => true, "data" => $propostas]);
        
        $stmt->close();
        $conn->close();
        break;

    case 'get_proposta_details':
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ID da proposta inválido"]);
            exit;
        }

        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM propostas WHERE id = ? AND parceiro_id = ?");
        
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao preparar consulta: " . $conn->error]);
            exit;
        }

        $stmt->bind_param("ii", $id, $parceiro_id_sessao);
        
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao executar consulta: " . $stmt->error]);
            exit;
        }

        $result = $stmt->get_result();
        $proposta = $result->fetch_assoc();

        if (!$proposta) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Proposta não encontrada"]);
            exit;
        }

        echo json_encode(["success" => true, "data" => $proposta]);
        
        $stmt->close();
        $conn->close();
        break;

    case 'save_proposta':
        $conn = getDbConnection();
        
        // Recebe e valida os dados da proposta
        $dados = [
            'nome_cliente' => trim(filter_input(INPUT_POST, 'nome_cliente', FILTER_SANITIZE_STRING)),
            'razao_social' => trim(filter_input(INPUT_POST, 'razao_social', FILTER_SANITIZE_STRING)),
            'cnpj' => trim(filter_input(INPUT_POST, 'cnpj', FILTER_SANITIZE_STRING)),
            'endereco' => trim(filter_input(INPUT_POST, 'endereco', FILTER_SANITIZE_STRING)),
            'cidade' => trim(filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING)),
            'estado' => trim(filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING)),
            'cep' => trim(filter_input(INPUT_POST, 'cep', FILTER_SANITIZE_STRING)),
            'email' => trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL)),
            'telefone' => trim(filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING)),
            'contato' => trim(filter_input(INPUT_POST, 'contato', FILTER_SANITIZE_STRING)),
            'data_validade' => trim(filter_input(INPUT_POST, 'data_validade', FILTER_SANITIZE_STRING)),
            'valor_total' => filter_input(INPUT_POST, 'valor_total', FILTER_VALIDATE_FLOAT),
            'condicoes_pagamento' => trim(filter_input(INPUT_POST, 'condicoes_pagamento', FILTER_SANITIZE_STRING)),
            'prazo_entrega' => trim(filter_input(INPUT_POST, 'prazo_entrega', FILTER_SANITIZE_STRING)),
            'observacoes' => trim(filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_STRING)),
            'itens_proposta' => $_POST['itens_proposta'] ?? '[]' // JSON string dos itens
        ];

        // Validação básica
        if (empty($dados['nome_cliente'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Nome do cliente é obrigatório"]);
            exit;
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT); // Para edição

        if ($id) {
            // Atualizar proposta existente
            $stmt = $conn->prepare("UPDATE propostas SET 
                nome_cliente = ?, razao_social = ?, cnpj = ?, endereco = ?, 
                cidade = ?, estado = ?, cep = ?, email = ?, telefone = ?, 
                contato = ?, data_validade = ?, valor_total = ?, 
                condicoes_pagamento = ?, prazo_entrega = ?, observacoes = ?, 
                itens_proposta = ? WHERE id = ? AND parceiro_id = ?");

            if (!$stmt) {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Erro ao preparar atualização: " . $conn->error]);
                exit;
            }

            $stmt->bind_param("sssssssssssdssssii", 
                $dados['nome_cliente'], $dados['razao_social'], $dados['cnpj'], 
                $dados['endereco'], $dados['cidade'], $dados['estado'], 
                $dados['cep'], $dados['email'], $dados['telefone'], 
                $dados['contato'], $dados['data_validade'], $dados['valor_total'], 
                $dados['condicoes_pagamento'], $dados['prazo_entrega'], 
                $dados['observacoes'], $dados['itens_proposta'], $id, $parceiro_id_sessao);

        } else {
            // Inserir nova proposta
            $numero_proposta = date('Ymd') . '-' . substr(uniqid(), -5);
            
            $stmt = $conn->prepare("INSERT INTO propostas 
                (parceiro_id, numero_proposta, nome_cliente, razao_social, cnpj, 
                endereco, cidade, estado, cep, email, telefone, contato, 
                data_validade, valor_total, condicoes_pagamento, prazo_entrega, 
                observacoes, itens_proposta) VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt) {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Erro ao preparar inserção: " . $conn->error]);
                exit;
            }

            $stmt->bind_param("issssssssssssdssss", 
                $parceiro_id_sessao, $numero_proposta, 
                $dados['nome_cliente'], $dados['razao_social'], $dados['cnpj'], 
                $dados['endereco'], $dados['cidade'], $dados['estado'], 
                $dados['cep'], $dados['email'], $dados['telefone'], 
                $dados['contato'], $dados['data_validade'], $dados['valor_total'], 
                $dados['condicoes_pagamento'], $dados['prazo_entrega'], 
                $dados['observacoes'], $dados['itens_proposta']);
        }

        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao salvar proposta: " . $stmt->error]);
            exit;
        }

        echo json_encode([
            "success" => true, 
            "message" => "Proposta " . ($id ? "atualizada" : "criada") . " com sucesso!",
            "id" => $id ?? $conn->insert_id
        ]);

        $stmt->close();
        $conn->close();
        break;

    case 'delete_proposta':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ID da proposta inválido"]);
            exit;
        }

        $conn = getDbConnection();
        $stmt = $conn->prepare("DELETE FROM propostas WHERE id = ? AND parceiro_id = ?");
        
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao preparar exclusão: " . $conn->error]);
            exit;
        }

        $stmt->bind_param("ii", $id, $parceiro_id_sessao);
        
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao excluir proposta: " . $stmt->error]);
            exit;
        }

        if ($stmt->affected_rows === 0) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Proposta não encontrada ou você não tem permissão para excluí-la"]);
            exit;
        }

        echo json_encode(["success" => true, "message" => "Proposta excluída com sucesso"]);
        
        $stmt->close();
        $conn->close();
        break;

    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Ação inválida"]);
}

ob_end_flush();
?>
