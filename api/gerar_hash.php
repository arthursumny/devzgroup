<?php
session_start();
ob_start(); // Inicia o buffer de saída para evitar problemas com headers

// Incluir arquivos do PHPMailer
require '../api/PHPMailer/Exception.php';
require '../api/PHPMailer/PHPMailer.php';
require '../api/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// --- Configura&ccedil;ões ---
define('ADMIN_USERNAME', 'devzgroup');
define('ADMIN_PASSWORD', 'Devz@2025'); // Senha para acessar esta p&aacute;gina

// Configura&ccedil;ões do Banco de Dados (ajuste conforme suas credenciais da KingHost)
define('DB_HOST', 'mysql64-farm2.uni5.net'); // Host do banco de dados da KingHost
define('DB_USER', 'devzgroup');              // Usu&aacute;rio do banco de dados
define('DB_PASS', 'D3vzgr0up');              // Senha do banco de dados
define('DB_NAME', 'devzgroup');              // Nome do banco de dados

$login_message = '';
$user_creation_message = '';

// --- Lógica de Login ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        if ($_POST['username'] === ADMIN_USERNAME && $_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: gerar_hash.php");
            exit;
        } else {
            $login_message = "<p style='color:red;'>Usu&aacute;rio ou senha inv&aacute;lidos.</p>";
        }
    }
}

// --- Lógica de Logout ---
if (isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    header("Location: gerar_hash.php");
    exit;
}

// --- Lógica de Cria&ccedil;&atilde;o de Usu&aacute;rio (se logado) ---
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user_submit'])) {
        $new_username = trim($_POST['new_username'] ?? '');
        $new_password = $_POST['new_password'] ?? ''; // Senha em texto plano, ser&aacute; usada no e-mail
        $new_user_type = $_POST['new_user_type'] ?? '';
        $new_email = trim($_POST['new_email'] ?? '');
        $new_nome_completo = trim($_POST['new_nome_completo'] ?? '');

        if (empty($new_username) || empty($new_password) || empty($new_user_type)) {
            $user_creation_message = "<p style='color:red;'>Nome de usu&aacute;rio, senha e tipo de usu&aacute;rio s&atilde;o obrigatórios.</p>";
        } elseif (!in_array($new_user_type, ['parceiro', 'indicador'])) {
            $user_creation_message = "<p style='color:red;'>Tipo de usu&aacute;rio inv&aacute;lido. Use 'parceiro' ou 'indicador'.</p>";
        } elseif (!empty($new_email) && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $user_creation_message = "<p style='color:red;'>Formato de e-mail inv&aacute;lido.</p>";
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                $user_creation_message = "<p style='color:red;'>Erro de conex&atilde;o com o banco: " . htmlspecialchars($conn->connect_error) . "</p>";
            } else {
                $conn->set_charset("utf8mb4");
                
                $stmt = $conn->prepare("INSERT INTO usuarios (username, password_hash, user_type, email, nome_completo) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt) {
                    // CORRIGIDO: bind_param para "sssss"
                    $stmt->bind_param("sssss", $new_username, $password_hash, $new_user_type, $new_email, $new_nome_completo);
                    if ($stmt->execute()) {
                        $user_creation_message = "<p style='color:green;'>Usu&aacute;rio '<strong>" . htmlspecialchars($new_username) . "</strong>' criado com sucesso!</p>";

                        if (!empty($new_email)) {
                            $mail = new PHPMailer(true);
                            try {
                                $mail->isSMTP();
                                $mail->Host       = 'smtpi.uni5.net';
                                $mail->SMTPAuth   = true;
                                $mail->Username   = 'parceiro@devzgroup.com.br';
                                $mail->Password   = 'Ti@d3vzgroup';
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                $mail->Port       = 587;
                                $mail->CharSet    = 'latin'; // Charset do e-mail mantido como UTF-8 para melhor compatibilidade

                                $mail->setFrom('noreply@devzgroup.com.br', 'Sistema Devzgroup');
                                $mail->addAddress($new_email, $new_nome_completo ?: $new_username);
                                $mail->addCC('rubia.martins@devzgroup.com.br');
                                $mail->addCC('erodi@devzgroup.com.br');
                                $mail->addCC('arthur.saggin@devzgroup.com.br');

                                $mail->isHTML(true);
                                $mail->Subject = 'Seu acesso à plataforma Devzgroup foi criado!';
                                // MODIFICADO: Incluindo a senha no corpo do e-mail
                                $emailBody = "<h1>Bem-vindo(a) à Plataforma Devzgroup!</h1>" .
                                             "<p>Ol&aacute; " . htmlspecialchars($new_nome_completo ?: $new_username) . ",</p>" .
                                             "<p>Seu acesso à plataforma Devzgroup foi criado com sucesso.</p>" .
                                             "<p><strong>Seu nome de usu&aacute;rio é:</strong> " . htmlspecialchars($new_username) . "</p>" .
                                             "<p><strong>Sua senha é:</strong> " . htmlspecialchars($new_password) . "</p>" . // SENHA ADICIONADA AQUI
                                             "<p>Você pode acessar a &aacute;rea de parceiros/indicadores através do nosso site.</p>" .
                                             "<p><strong>Aten&ccedil;&atilde;o:</strong> Por motivos de seguran&ccedil;a, recomendamos que você altere esta senha após o primeiro login, se a plataforma oferecer essa funcionalidade.</p>" .
                                             "<p>Lembre-se de manter sua senha segura. Caso precise de ajuda, entre em contato conosco.</p>" .
                                             "<br><p>Atenciosamente,<br>Equipe Devzgroup</p>";
                                $mail->Body    = $emailBody;
                                $mail->AltBody = strip_tags($emailBody); // O strip_tags remover&aacute; o HTML para a vers&atilde;o em texto plano

                                $mail->send();
                                $user_creation_message .= "<p style='color:green;'>E-mail de notifica&ccedil;&atilde;o enviado para " . htmlspecialchars($new_email) . " e para a equipe.</p>";
                            } catch (Exception $e) {
                                $user_creation_message .= "<p style='color:red;'>Usu&aacute;rio criado, mas falha ao enviar e-mail de notifica&ccedil;&atilde;o: {$mail->ErrorInfo}</p>";
                                error_log("PHPMailer Erro (gerar_hash.php): {$mail->ErrorInfo}");
                            }
                        } else {
                            $user_creation_message .= "<p style='color:orange;'>Usu&aacute;rio criado, mas nenhum e-mail fornecido para notifica&ccedil;&atilde;o do usu&aacute;rio.</p>";
                        }

                    } else {
                        if ($conn->errno == 1062) {
                             $user_creation_message = "<p style='color:red;'>Erro ao criar usu&aacute;rio: O nome de usu&aacute;rio '<strong>" . htmlspecialchars($new_username) . "</strong>' j&aacute; existe.</p>";
                        } else {
                             $user_creation_message = "<p style='color:red;'>Erro ao criar usu&aacute;rio: " . htmlspecialchars($stmt->error) . "</p>";
                        }
                    }
                    $stmt->close();
                } else {
                    $user_creation_message = "<p style='color:red;'>Erro ao preparar a query: " . htmlspecialchars($conn->error) . "</p>";
                }
                $conn->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- MODIFICADO: Charset alterado para ISO-8859-1 (latin1) -->
    <meta http-equiv="Content-Type" content="text/html" charset="latin">
    <title>Admin - Gerenciador de Usu&aacute;rios</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 600px; margin: auto; }
        h1, h2 { color: #333; text-align: center; }
        label { display: block; margin-top: 10px; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"], input[type="email"], select {
            width: calc(100% - 22px); padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        button {
            background-color: #5cb85c; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;
        }
        button:hover { background-color: #4cae4c; }
        .logout-link { display: block; text-align: right; margin-bottom: 20px; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
        .message.warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba;}
        code { background-color: #eee; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ferramenta Administrativa</h1>

        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
            <a href="?logout=true" class="logout-link">Sair (Logout)</a>
            <h2>Criar Novo Usu&aacute;rio</h2>
            
            <?php if (!empty($user_creation_message)): ?>
                <div class="message <?php 
                    if (strpos($user_creation_message, 'sucesso') !== false && strpos($user_creation_message, 'falha ao enviar e-mail') === false) echo 'success';
                    elseif (strpos($user_creation_message, 'nenhum e-mail fornecido') !== false) echo 'warning';
                    else echo 'error'; 
                ?>">
                    <?php echo $user_creation_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="gerar_hash.php">
                <label for="new_username">Nome de Usu&aacute;rio (para login):</label>
                <input type="text" id="new_username" name="new_username" required>

                <label for="new_password">Senha:</label>
                <input type="password" id="new_password" name="new_password" required>

                <label for="new_user_type">Tipo de Usu&aacute;rio:</label>
                <select id="new_user_type" name="new_user_type" required>
                    <option value="">Selecione...</option>
                    <option value="parceiro">Parceiro</option>
                    <option value="indicador">Indicador</option>
                </select>

                <label for="new_email">Email (para notifica&ccedil;&atilde;o e login, se aplic&aacute;vel):</label>
                <input type="email" id="new_email" name="new_email">

                <label for="new_nome_completo">Nome Completo (opcional):</label>
                <input type="text" id="new_nome_completo" name="new_nome_completo">

                <button type="submit" name="create_user_submit">Criar Usu&aacute;rio</button>
            </form>
            
        <?php else: ?>
            <h2>Login Administrativo</h2>
            <?php if (!empty($login_message)) echo "<div class='message error'>{$login_message}</div>"; ?>
            <form method="POST" action="gerar_hash.php">
                <label for="username">Usu&aacute;rio:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit" name="login_submit">Entrar</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
ob_end_flush(); // Envia o buffer de saída
?>