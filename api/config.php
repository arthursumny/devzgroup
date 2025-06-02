<?php
// Arquivo de Configuração SMTP - config.php

// Defina as suas credenciais SMTP aqui
// IMPORTANTE: Adicione este arquivo ao seu .gitignore para não versionar suas senhas!

define('SMTP_HOST', 'smtpi.uni5.net');
define('SMTP_USERNAME', 'parceiro@devzgroup.com.br'); // Usuário SMTP para autenticação
define('SMTP_PASSWORD', 'Ti@d3vzgroup');          // Senha SMTP
define('SMTP_SECURE', PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS); // Ou PHPMailer::ENCRYPTION_SMTPS
define('SMTP_PORT', 587);                     // Porta SMTP (587 para STARTTLS, 465 para SMTPS)

// E-mail de remetente padrão para o sistema (noreply)
define('EMAIL_REMETENTE_SISTEMA', 'noreply@devzgroup.com.br');

// Destinatários padrão para notificações de acesso de parceiro
define('EMAIL_DESTINATARIO_ACESSO_PARCEIRO', 'rubia.martins@devzgroup.com.br');
define('EMAIL_COPIA_ACESSO_PARCEIRO_1', 'arthur.saggin@devzgroup.com.br');
define('EMAIL_COPIA_ACESSO_PARCEIRO_2', 'erodi@devzgroup.com.br');

?> 