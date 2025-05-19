<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $token = bin2hex(random_bytes(16));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        $user_id = $user['id'];

        // Remove old tokens
        $conn->query("DELETE FROM password_resets WHERE user_id = $user_id");

        // Insert new reset token
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $token, $expires);
        $stmt->execute();

        // Prepare and send the email
        $mail = new PHPMailer(true);
        try {
            // SMTP server configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'markdelapaz150@gmail.com'; // Your Gmail address
            $mail->Password = 'mkqd ntxw nijb ehmk';       // Gmail app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Sender and recipient
            $mail->setFrom('markdelapaz150@gmail.com', 'Contact Manager');
            $mail->addAddress($email);

            // Email content
            $reset_link = "http://localhost/contact_manager/edit_password.php?token=$token";

            $mail->isHTML(true);
            $mail->Subject = '🔐 Password Reset Request';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                    <div style='max-width: 600px; margin: auto; background: white; border-radius: 10px; padding: 30px;'>
                        <h2 style='color: #333;'>Password Reset Request</h2>
                        <p>Hello,</p>
                        <p>We received a request to reset your password. Click the button below to create a new password:</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='$reset_link' 
                               style='padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>
                                Reset Password
                            </a>
                        </div>
                        <p>If you didn't request this, you can safely ignore this email.</p>
                        <p style='margin-top: 40px; font-size: 12px; color: #999;'>This link will expire in 1 hour for your security.</p>
                    </div>
                </div>
            ";

            $mail->AltBody = "You requested a password reset. Use the link below to reset your password:\n\n$reset_link\n\nIf you didn't request this, ignore this email.";

            $mail->send();
            echo "<p style='color: green;'>Password reset link has been sent to your email.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Email could not be sent. Mailer Error: {$mail->ErrorInfo}</p>";
        }

    } else {
        echo "<p style='color: red;'>No user found with that email.</p>";
    }
}
?>

<h2>Forgot Password</h2>
<form method="POST">
    Enter your email: <input type="email" name="email" required><br><br>
    <button type="submit">Request Reset</button>
</form>
