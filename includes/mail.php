<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

function enviarEmail($para, $assunto, $corpo, $nome_destinatario = '') {
    $mail = new PHPMailer(true);

    try {
        // Configurações do servidor
        $mail->isSMTP();
        $mail->Host = 'zaponto.com.br'; // Altere para seu servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'suporte@zaponto.com.br'; // Seu email
        $mail->Password = '@Speaker120123'; // Sua senha
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';

        // Remetente
        $mail->setFrom('suporte@zaponto.com.br', 'Zaponto');
        
        // Destinatário
        $mail->addAddress($para, $nome_destinatario);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $corpo;
        $mail->AltBody = strip_tags($corpo);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar email: {$mail->ErrorInfo}");
        return false;
    }
}

function gerarTokenRecuperacao($email, $pdo) {
    try {
        // Gerar token único
        $token = bin2hex(random_bytes(32));
        
        // Definir expiração (24 horas)
        $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Atualizar no banco de dados
        $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expira = ? WHERE email = ?");
        $stmt->execute([$token, $expira, $email]);
        
        return $token;
    } catch (Exception $e) {
        error_log("Erro ao gerar token: " . $e->getMessage());
        return false;
    }
}

function enviarEmailRecuperacao($email, $token) {
    $link = "https://seusite.com/pages/redefinir-senha.php?token=" . $token;
    
    $assunto = "Recuperação de Senha - Zaponto";
    
    $corpo = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .btn { background-color: #009aff; color: white; padding: 10px 20px; 
                   text-decoration: none; border-radius: 5px; display: inline-block; }
            .footer { margin-top: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Recuperação de Senha</h2>
            <p>Você solicitou a recuperação de senha da sua conta no ZapLocal.</p>
            <p>Clique no botão abaixo para criar uma nova senha:</p>
            <p><a href='$link' class='btn'>Redefinir Senha</a></p>
            <p>Se você não solicitou esta recuperação, ignore este email.</p>
            <p>Este link expira em 24 horas.</p>
            <div class='footer'>
                <p>Este é um email automático, por favor não responda.</p>
                <p>Zaponto - Sistema de Automação de WhatsApp</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return enviarEmail($email, $assunto, $corpo);
}