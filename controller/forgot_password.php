<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'path/to/PHPMailer/src/Exception.php';
require 'path/to/PHPMailer/src/PHPMailer.php';
require 'path/to/PHPMailer/src/SMTP.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'ucheque');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["emailAddress"];

    $verification_code = rand(100000, 999999);

    // Update verification code in the database
    $sql = "UPDATE employee SET code=? WHERE emailAddress=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $verification_code, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $mail = new PHPMailer(true);
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'morales.shakiraa@gmail.com';
            $mail->Password = 'Shak2025..';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Email settings
            $mail->setFrom('morales.shakiraa@gmail.com', 'Ucheque');
            $mail->addAddress($email);
            $mail->Subject = 'Password Reset Verification Code';
            $mail->Body = 'Your verification code is: ' . $verification_code;

            $mail->send();
            echo 'A verification code has been sent to your email.';
        } catch (Exception $e) {
            echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
        }
    } else {
        echo 'No user found with that email address.';
    }
}
?>
