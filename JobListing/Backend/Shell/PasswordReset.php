<?php
class PasswordReset {
    private $db;
    private $conn;
    
    public function __construct() {
        require_once __DIR__ . '/../Core/Config/DataManagement/DB_Operations.php';
        $this->db = new SQL_Operations();
        $this->conn = $this->db->getConnection();
    }
    
    private function sendEmail($to, $subject, $message) {
        try {
            $email = '23-32966@g.batstate-u.edu.ph'; 
            $app_password = 'zzus gtya bhdj unhi';
            
            $smtp_server = 'smtp.gmail.com';
            $smtp_port = 587;
            
            $socket_context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            $socket = stream_socket_client(
                "tcp://{$smtp_server}:{$smtp_port}",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $socket_context
            );
            
            if (!$socket) {
                throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
            }
            
            stream_set_timeout($socket, 30);
            
            $this->getResponse($socket);
            
            $this->sendCommand($socket, "EHLO " . $_SERVER['SERVER_NAME']);
            $this->sendCommand($socket, "STARTTLS");
            
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            $this->sendCommand($socket, "EHLO " . $_SERVER['SERVER_NAME']);
            
            $this->sendCommand($socket, "AUTH LOGIN");
            $this->sendCommand($socket, base64_encode($email));
            $this->sendCommand($socket, base64_encode($app_password));
            
            $this->sendCommand($socket, "MAIL FROM:<{$email}>");
            $this->sendCommand($socket, "RCPT TO:<{$to}>");
            $this->sendCommand($socket, "DATA");
            
            $headers = [
                "MIME-Version: 1.0",
                "Content-type: text/html; charset=UTF-8",
                "From: BatStateU Job Portal <{$email}>",
                "Reply-To: {$email}",
                "X-Mailer: PHP/" . phpversion(),
                "Subject: {$subject}"
            ];
            
            $email_content = implode("\r\n", $headers) . "\r\n\r\n" . $message . "\r\n.";
            $this->sendCommand($socket, $email_content);
            
            $this->sendCommand($socket, "QUIT");
            fclose($socket);
            
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendCommand($socket, $command) {
        fwrite($socket, $command . "\r\n");
        return $this->getResponse($socket);
    }
    
    private function getResponse($socket) {
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        return $response;
    }
    
    public function initiateReset($email) {
        try {
            // Check if email exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return [
                    'success' => false,
                    'message' => 'Email not found in our records'
                ];
            }
            
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $user_id = $result->fetch_assoc()['id'];
            
            // Store reset token
            $stmt = $this->conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $token, $expires);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $resetLink = "http://{$_SERVER['HTTP_HOST']}/ojtportal-austria/JobListing/Frontend/reset-password.html?token=" . $token;
                $logoUrl = "http://{$_SERVER['HTTP_HOST']}/ojtportal-austria/JobListing/Assets/Images/BatStateU-NEU-Logo.png";
                $to = $email;
                $subject = "Password Reset Request - BatStateU Job Portal";
                $message = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset='UTF-8'>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <title>Reset Your Password</title>
                        <style>
                            @media only screen and (max-width: 620px) {
                                .container { width: 100% !important; }
                                .content { padding: 15px !important; }
                                .button { width: 80% !important; }
                            }

                            * {
                                margin: 0;
                                padding: 0;
                                box-sizing: border-box;
                            }

                            body { 
                                font-family: Arial, sans-serif;
                                line-height: 1.6;
                                color: #495057;
                                background-color: #f8f9fa;
                            }

                            .container {
                                max-width: 600px;
                                margin: 0 auto;
                                padding: 20px;
                            }

                            .email-body {
                                background-color: #ffffff;
                                border-radius: 10px;
                                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                                overflow: hidden;
                            }

                            .header {
                                text-align: center;
                                padding: 30px 20px;
                                background: linear-gradient(135deg, #C8102E 0%, #a00d24 100%);
                            }

                            .logo {
                                width: 120px;
                                height: auto;
                                margin-bottom: 15px;
                            }

                            .school-info {
                                color: #ffffff;
                                margin-bottom: 10px;
                            }

                            .school-name {
                                font-size: 24px;
                                font-weight: bold;
                                margin-bottom: 5px;
                            }

                            .campus-name {
                                font-size: 16px;
                            }

                            .content {
                                padding: 40px 30px;
                            }

                            h1 {
                                color: #C8102E;
                                font-size: 28px;
                                font-weight: bold;
                                margin-bottom: 25px;
                                text-align: center;
                            }

                            p {
                                margin-bottom: 20px;
                                color: #495057;
                                font-size: 16px;
                            }

                            .button-container {
                                text-align: center;
                                margin: 30px 0;
                            }

                            .button {
                                display: inline-block;
                                padding: 15px 35px;
                                background-color: #C8102E;
                                color: #ffffff;
                                text-decoration: none;
                                border-radius: 5px;
                                font-weight: 500;
                                font-size: 16px;
                                transition: background-color 0.3s;
                            }

                            .button:hover {
                                background-color: #a00d24;
                            }

                            .note {
                                background-color: #fff3cd;
                                border: 1px solid #ffeeba;
                                color: #856404;
                                padding: 15px;
                                border-radius: 5px;
                                margin: 20px 0;
                                font-size: 14px;
                            }

                            .footer {
                                text-align: center;
                                padding-top: 25px;
                                margin-top: 25px;
                                border-top: 1px solid #dee2e6;
                                color: #6c757d;
                                font-size: 14px;
                            }

                            .divider {
                                margin: 30px 0;
                                border: none;
                                border-top: 1px solid #dee2e6;
                            }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='email-body'>
                                <div class='header'>
                                    <img src='{$logoUrl}' alt='BatStateU Logo' class='logo'>
                                    <div class='school-info'>
                                        <div class='school-name'>Batangas State University</div>
                                        <div class='campus-name'>The NEU - Lipa Campus</div>
                                    </div>
                                </div>
                                <div class='content'>
                                    <h1>Password Reset Request</h1>
                                    <p>Hello,</p>
                                    <p>We received a request to reset your password for your BatStateU Job Portal account. To set a new password, please click the button below:</p>
                                    
                                    <div class='button-container'>
                                        <a href='{$resetLink}' class='button'>Reset Password</a>
                                    </div>

                                    <div class='note'>
                                        <strong>Important:</strong> This reset link will expire in 1 hour for security purposes.
                                    </div>

                                    <hr class='divider'>

                                    <p style='font-size: 14px; color: #6c757d;'>If you didn't request this password reset, you can safely ignore this email. Your account security is important to us.</p>

                                    <div class='footer'>
                                        <p>Best regards,<br>BatStateU Job Portal Team</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>
                ";

                $emailSent = $this->sendEmail($to, $subject, $message);
                error_log("Email sending attempt to {$to}: " . ($emailSent ? "Success" : "Failed"));
                
                if ($emailSent) {
                    return [
                        'success' => true,
                        'message' => 'Password reset instructions have been sent to your email'
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'Failed to send reset email. Please try again later.'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to initiate password reset'
            ];
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during password reset'
            ];
        }
    }
    
    public function resetPassword($token, $newPassword) {
        try {
            error_log("Attempting password reset with token: " . substr($token, 0, 10) . "...");
            
            // Verify token and check expiration
            $stmt = $this->conn->prepare("
                SELECT pr.user_id, pr.expires_at, pr.used 
                FROM password_resets pr 
                WHERE pr.token = ?
            ");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                error_log("Token not found in database");
                return [
                    'success' => false,
                    'message' => 'Invalid reset token'
                ];
            }

            $resetData = $result->fetch_assoc();
            
            // Check if token is already used
            if ($resetData['used'] == 1) {
                error_log("Token already used");
                return [
                    'success' => false,
                    'message' => 'This reset link has already been used. Please request a new one.'
                ];
            }

            // Check if token is expired
            if (strtotime($resetData['expires_at']) < time()) {
                error_log("Token expired at: " . $resetData['expires_at']);
                return [
                    'success' => false,
                    'message' => 'This reset link has expired. Please request a new one.'
                ];
            }

            $user_id = $resetData['user_id'];
            
            // Start transaction
            $this->conn->begin_transaction();
            
            try {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashedPassword, $user_id);
                $stmt->execute();
                
                if ($stmt->affected_rows <= 0) {
                    throw new Exception("Failed to update password");
                }
                
                // Mark token as used
                $stmt = $this->conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                if ($stmt->affected_rows <= 0) {
                    throw new Exception("Failed to mark token as used");
                }
                
                // Commit transaction
                $this->conn->commit();
                
                error_log("Password reset successful for user ID: " . $user_id);
                return [
                    'success' => true,
                    'message' => 'Password has been successfully reset'
                ];
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $this->conn->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while resetting your password. Please try again.'
            ];
        }
    }
}