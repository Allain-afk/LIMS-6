<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'Ayhmklthm@gmail.com'; // Replace with your Gmail
        $this->mail->Password = 'ywgmwomqgkjsjwlq'; // Replace with app password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        
        // Sender info
        $this->mail->setFrom('Ayhmklthm@gmail.com', 'Book king');
    }
    
    public function sendOTP($recipientEmail, $otp) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($recipientEmail);
            
            // Email content
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Your OTP Code';
            $this->mail->Body = 'Your verification code is: <b>'.$otp.'</b>';
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log('Mailer Error: ' . $this->mail->ErrorInfo);
            return false;
        }
    }
}

// Example usage:
// $emailService = new EmailService();
// $emailService->sendOTP('recipient@example.com', '123456');
?>
