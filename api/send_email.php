<?php
// enviar_email.php
ob_start(); // Inicia o buffer de saída

// Incluir arquivo de configuração SMTP
require_once __DIR__ . '/config.php';

// Incluir arquivos do PHPMailer
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Headers para resposta JSON e CORS
// ATENÇÃO: Para desenvolvimento local, você pode precisar mudar a linha abaixo para:
// header("Access-Control-Allow-Origin: *"); 
// ou para a origem do seu servidor de desenvolvimento local (ex: http://localhost:xxxx)
// Em produção, use o domínio específico onde partner-login.html está hospedado.
header("Access-Control-Allow-Origin: https://devzgroup.com.br"); // Ajustado para o domínio raiz, ou seja mais específico se necessário.
// Se partner-login.html estiver em https://devzgroup.com.br/partner-login.html, então https://devzgroup.com.br é suficiente.
// Se estiver em https://devzgroup.com.br/teste/partner-login.html, use: header("Access-Control-Allow-Origin: https://devzgroup.com.br");
// ou mais especificamente: header("Access-Control-Allow-Origin: https://devzgroup.com.br/teste"); (sem a página no final)
// Para o exemplo anterior: header("Access-Control-Allow-Origin: https://devzgroup.com.br/teste/partner-login.html"); é muito específico e pode não funcionar se o JS está em outro caminho.
// Recomenda-se usar o domínio ou subdiretório.
// Se o seu partner-login.html está em c:\Users\arthur.saggin\Desktop\devzgroup\partner-login.html
// e você o acessa via file://, AJAX requests para http(s):// não funcionarão devido à política de mesma origem.
// Você precisará servir seus arquivos HTML através de um servidor web local (XAMPP, WAMP, MAMP, Python http.server, etc.)
// e então o Access-Control-Allow-Origin deve ser o endereço desse servidor local (e.g., http://localhost)

header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit;
}

// Definir Content-Type para todas as respostas JSON
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonPayload = file_get_contents('php://input');
    $data = json_decode($jsonPayload, true);

    if ($data === null) {
        ob_clean();
        http_response_code(400);
        echo json_encode(["message" => "Erro: Dados inválidos ou não recebidos."]);
        exit;
    }

    // Identificar o tipo de solicitação (opcional, mas bom para futuras expansões)
    $requestType = $data['requestType'] ?? 'unknown';

    if ($requestType === 'partnerAccess') {
        // Extrair dados para solicitação de acesso de parceiro
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        $city = trim($data['city'] ?? '');

        if (empty($name) || empty($phone) || empty($email) || empty($city) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["message" => "Erro: Todos os campos (Nome, Telefone, Email, Cidade) devem ser preenchidos corretamente."]);
            exit;
        }

        // Configurações de Email para Solicitação de Acesso Parceiro
        $emailDestinatario = 'rubia.martins@devzgroup.com.br';
        $emailCopia = [
            'arthur.saggin@devzgroup.com.br',
            'erodi@devzgroup.com.br'
        ];
        $emailRemetente = 'noreply@devzgroup.com.br'; // O email que aparecerá no campo "De:"
        $nomeRemetente = 'Sistema Devzgroup - Solicitação de Acesso';
        $assunto = 'Nova Solicitação de Acesso Parceiro - ' . htmlspecialchars($name);
        $corpoEmail = "<p>Uma nova solicitação de acesso para parceiro foi feita através do site:</p>" .
                      "<ul>" .
                      "<li><strong>Nome Completo:</strong> " . htmlspecialchars($name) . "</li>" .
                      "<li><strong>Telefone/Celular:</strong> " . htmlspecialchars($phone) . "</li>" .
                      "<li><strong>Email do Solicitante:</strong> " . htmlspecialchars($email) . "</li>" .
                      "<li><strong>Cidade:</strong> " . htmlspecialchars($city) . "</li>" .
                      "</ul>" .
                      "<hr><p><em>Este é um e-mail automático enviado pelo sistema do site devzgroup.com.br.</em></p>";

    } else {
        // Aqui você poderia adicionar lógica para outros tipos de requestType, como o de voucher CAASC
        // Por enquanto, se não for 'partnerAccess', consideramos um erro ou tipo não suportado.
        ob_clean();
        http_response_code(400);
        echo json_encode(["message" => "Erro: Tipo de solicitação desconhecido."]);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // Configurações do Servidor SMTP (mantidas do seu script original)
        $mail->isSMTP();
        $mail->Host       = 'smtpi.uni5.net';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME; // Usuário SMTP para autenticação
        $mail->Password   = SMTP_PASSWORD;          // Senha SMTP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Debugging (opcional, pode comentar/remover em produção se não precisar de logs detalhados)
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        // $mail->Debugoutput = function($str, $level) { error_log("debug level $level; message: $str"); };

        // Destinatários e Conteúdo
        $mail->setFrom($emailRemetente, $nomeRemetente); // Define o remetente (FROM)
        $mail->addAddress($emailDestinatario);           // Adiciona o destinatário principal (TO)
        
        foreach ($emailCopia as $cc) {
            if (!empty($cc) && filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                $mail->addCC($cc); // Adiciona destinatários em cópia (CC)
            }
        }
        
        $mail->addReplyTo($email, $name); // Email do usuário que preencheu o formulário como "Responder Para"

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $assunto;
        $mail->Body    = $corpoEmail;
        $mail->AltBody = strip_tags($corpoEmail);

        $mail->send();

        ob_clean();
        http_response_code(200);
        echo json_encode(["message" => "Solicitação enviada com sucesso! Em breve entraremos em contato."]);
        exit;

    } catch (Exception $e) {
        ob_clean();
        http_response_code(500);
        // Log do erro detalhado no servidor para análise
        error_log("PHPMailer Erro ao enviar e-mail ({$requestType}): {$mail->ErrorInfo}. Dados: " . json_encode($data));
        // Mensagem genérica para o usuário
        echo json_encode(["message" => "Erro ao enviar a solicitação. Por favor, tente novamente mais tarde."]);
        // echo json_encode(["message" => "Erro ao enviar a solicitação. Detalhe: {$mail->ErrorInfo}"]); // Para debug no cliente, não recomendado em produção
        exit;
    }

} else {
    ob_clean();
    http_response_code(405); // Method Not Allowed
    echo json_encode(["message" => "Método não permitido."]);
    exit;
}
// ob_end_flush(); // Geralmente não é necessário com exit()
?>