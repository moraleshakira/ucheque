<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'ucheque');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["emailAddress"];
    $verification_code = $_POST["verification_code"];
    $new_password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Verify code
    $sql = "SELECT * FROM employee WHERE emailAddress=? AND code=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $email, $verification_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update password
        $sql = "UPDATE employee SET password=?, code=NULL WHERE emailAddress=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $new_password, $email);
        $stmt->execute();
        echo 'Your password has been reset successfully.';
    } else {
        echo 'Invalid verification code.';
    }
} else {
    // Display the reset form if the code is valid
    if (isset($_GET["emailAddress"]) && isset($_GET["code"])) {
        $email = $_GET["emailAddress"];
        $code = $_GET["code"];
        $sql = "SELECT * FROM employee WHERE emailAddress=? AND code=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo '<form action="reset_password.php" method="POST">
                    <input type="hidden" name="email" value="' . $email . '">
                    <div class="input-field">
                        <input type="text" id="verification_code" name="verification_code" required>
                        <label for="verification_code">Verification Code</label>
                    </div>
                    <div class="input-field">
                        <input type="password" id="password" name="password" required>
                        <label for="password">New Password</label>
                    </div>
                    <button type="submit" class="submit-button">Reset Password</button>
                  </form>';
        } else {
            echo 'Invalid or expired verification code.';
        }
    }
}
?>
