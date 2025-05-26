<?php
session_start();
ob_start();

// Configurações do Banco de Dados (as mesmas do seu login_handler.php e gerar_hash.php)
define('DB_HOST', 'mysql64-farm2.uni5.net');
define('DB_USER', 'devzgroup');
define('DB_PASS', 'D3vzgr0up');
define('DB_NAME', 'devzgroup');

header("Content-Type: application/json; charset=UTF-8");
// CORS Headers (ajuste para seu ambiente de produção)
header("Access-Control-Allow-Origin: https://devzgroup.com.br"); // Ou a origem do seu front-end
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit;
}

// Função para conexão com o banco
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Erro de conexão com o banco: " . $conn->connect_error]);
        exit;
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Verifica se o usuário (parceiro) está logado
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parceiro') {
    http_response_code(403); // Forbidden
    echo json_encode(["success" => false, "message" => "Acesso não autorizado."]);
    exit;
}
$parceiro_id_sessao = $_SESSION['user_id'];


$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'save_indicacao':
        // Validação básica dos campos (adicione mais conforme necessário)
        $parceiro_id_form = filter_input(INPUT_POST, 'parceiro_id', FILTER_VALIDATE_INT);
        $nome_indicado = trim(filter_input(INPUT_POST, 'nome_indicado', FILTER_SANITIZE_STRING));
        
        if ($parceiro_id_form !== $parceiro_id_sessao) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "ID do parceiro inválido."]);
            exit;
        }
        if (empty($nome_indicado)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Nome do indicado é obrigatório."]);
            exit;
        }

        $empresa_indicada = trim(filter_input(INPUT_POST, 'empresa_indicada', FILTER_SANITIZE_STRING));
        $telefone_indicado = trim(filter_input(INPUT_POST, 'telefone_indicado', FILTER_SANITIZE_STRING));
        $email_indicado = trim(filter_input(INPUT_POST, 'email_indicado', FILTER_VALIDATE_EMAIL) ? $_POST['email_indicado'] : '');
        $produto_interesse = trim(filter_input(INPUT_POST, 'produto_interesse', FILTER_SANITIZE_STRING));
        $detalhes_indicacao = trim(filter_input(INPUT_POST, 'detalhes_indicacao', FILTER_SANITIZE_STRING));
        // Status pode ser definido por padrão no DB ou vir do form se necessário

        $conn = getDbConnection();
        $stmt = $conn->prepare("INSERT INTO indicacoes (parceiro_id, nome_indicado, empresa_indicada, telefone_indicado, email_indicado, produto_interesse, detalhes_indicacao) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao preparar query: " . $conn->error]);
            exit;
        }
        $stmt->bind_param("issssss", $parceiro_id_sessao, $nome_indicado, $empresa_indicada, $telefone_indicado, $email_indicado, $produto_interesse, $detalhes_indicacao);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Indicação salva com sucesso!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao salvar indicação: " . $stmt->error]);
        }
        $stmt->close();
        $conn->close();
        break;

    case 'get_indicacoes':
        $parceiro_id_req = filter_input(INPUT_POST, 'parceiro_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'parceiro_id', FILTER_VALIDATE_INT);
        if ($parceiro_id_req !== $parceiro_id_sessao) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Não autorizado a ver estas indicações."]);
            exit;
        }

        $conn = getDbConnection();
        // Adicionei ORDER BY data_criacao DESC para mostrar as mais recentes primeiro
        $stmt = $conn->prepare("SELECT id, nome_indicado, empresa_indicada, telefone_indicado, email_indicado, produto_interesse, detalhes_indicacao, status_indicacao, DATE_FORMAT(data_criacao, '%Y-%m-%dT%H:%i:%sZ') as data_criacao FROM indicacoes WHERE parceiro_id = ? ORDER BY data_criacao DESC");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao preparar query: " . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $parceiro_id_sessao);
        $stmt->execute();
        $result = $stmt->get_result();
        $indicacoes = [];
        while ($row = $result->fetch_assoc()) {
            $indicacoes[] = $row;
        }
        echo json_encode($indicacoes); // Retorna array de indicações (pode ser vazio)
        $stmt->close();
        $conn->close();
        break;

    case 'delete_indicacao':
        $id_indicacao = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $parceiro_id_req = filter_input(INPUT_POST, 'parceiro_id', FILTER_VALIDATE_INT); // Para verificação extra

        if (empty($id_indicacao)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ID da indicação é obrigatório."]);
            exit;
        }
        if ($parceiro_id_req !== $parceiro_id_sessao) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Operação não autorizada."]);
            exit;
        }

        $conn = getDbConnection();
        // Importante: Deletar apenas se o parceiro_id corresponder ao da sessão para segurança
        $stmt = $conn->prepare("DELETE FROM indicacoes WHERE id = ? AND parceiro_id = ?");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao preparar query: " . $conn->error]);
            exit;
        }
        $stmt->bind_param("ii", $id_indicacao, $parceiro_id_sessao);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(["success" => true, "message" => "Indicação excluída com sucesso!"]);
            } else {
                // Não encontrou a indicação com esse ID para esse parceiro, ou já foi deletada
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Indicação não encontrada ou não pertence a você."]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao excluir indicação: " . $stmt->error]);
        }
        $stmt->close();
        $conn->close();
        break;

    default:
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Ação inválida."]);
        break;
}

ob_end_flush();
?>