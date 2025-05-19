<?php
require_once __DIR__ . '/includes/db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Validate token
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $reset = $res->fetch_assoc();

        // Check if token expired
        if (strtotime($reset['expires_at']) < time()) {
            echo "<p style='color: red;'>Reset link has expired.</p>";
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

            // Update user password
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_pass, $reset['user_id']);
            $stmt->execute();

            // Remove used token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            echo "<p style='color: green;'>Your password has been updated. <a href='login.php'>Login now</a>.</p>";
            exit;
        }
    } else {
        echo "<p style='color: red;'>Invalid or used token.</p>";
        exit;
    }
} else {
    echo "<p style='color: red;'>No token provided.</p>";
    exit;
}
?>

<h2>Reset Your Password</h2>
<form method="POST">
    New Password: <input type="password" name="new_password" required><br><br>
    <button type="submit">Update Password</button>
</form>
