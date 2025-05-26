<?php
session_start(); // Inicia ou resume uma sessão
ob_start(); // Inicia o buffer de saída

define('DB_HOST', 'mysql64-farm2.uni5.net'); // Host do banco de dados da KingHost
define('DB_USER', 'devzgroup');              // Usu&aacute;rio do banco de dados
define('DB_PASS', 'D3vzgr0up');              // Senha do banco de dados
define('DB_NAME', 'devzgroup');              // Nome do banco de dados

// Headers para resposta JSON e CORS
header("Access-Control-Allow-Origin: https://devzgroup.com.br"); // Ou a origem do seu front-end
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true"); // Importante para sessões com fetch

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit;
}

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonPayload = file_get_contents('php://input');
    $data = json_decode($jsonPayload, true);

    if ($data === null || !isset($data['username']) || !isset($data['password'])) {
        ob_clean();
        http_response_code(400);
        echo json_encode(["message" => "Erro: Usuário e senha são obrigatórios."]);
        exit;
    }

    $username = trim($data['username']);
    $password = $data['password'];

    // Conexão com o banco de dados
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        ob_clean();
        http_response_code(500);
        error_log("Erro de conexão com DB: " . $conn->connect_error);
        echo json_encode(["message" => "Erro interno do servidor (DB Connect)."]);
        exit;
    }
    $conn->set_charset("utf8mb4");

    // Preparar a consulta para evitar SQL Injection
    $stmt = $conn->prepare("SELECT id, username, password_hash, user_type, nome_completo FROM usuarios WHERE username = ?");
    if (!$stmt) {
        ob_clean();
        http_response_code(500);
        error_log("Erro ao preparar statement: " . $conn->error);
        echo json_encode(["message" => "Erro interno do servidor (DB Prepare)."]);
        $conn->close();
        exit;
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            // Senha correta
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['nome_completo'] = $user['nome_completo'];

            ob_clean();
            http_response_code(200);
            echo json_encode([
                "message" => "Login bem-sucedido!",
                "user_type" => $user['user_type'],
                "nome_completo" => $user['nome_completo']
            ]);
        } else {
            // Senha incorreta
            ob_clean();
            http_response_code(401); // Unauthorized
            echo json_encode(["message" => "Usuário ou senha inválidos."]);
        }
    } else {
        // Usuário não encontrado
        ob_clean();
        http_response_code(401); // Unauthorized
        echo json_encode(["message" => "Usuário ou senha inválidos."]);
    }

    $stmt->close();
    $conn->close();
    exit;

} else {
    ob_clean();
    http_response_code(405); // Method Not Allowed
    echo json_encode(["message" => "Método não permitido."]);
    exit;
}
?>