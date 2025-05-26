<?php
session_start();
$_SESSION = array(); // Limpa todas as variáveis de sessão

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Opcional: responder com JSON para o fetch, ou apenas redirecionar se chamado diretamente
// header("Content-Type: application/json");
// echo json_encode(["message" => "Logout bem-sucedido"]);

// Se o JavaScript for redirecionar, não precisa de redirecionamento aqui.
// Se esta página for acessada diretamente, pode redirecionar:
// header("Location: ../partner-login.html"); // Ajuste o caminho se necessário
exit;
?>