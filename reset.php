<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="96x96" href="./assets/images/logo-dark.png">
    <title>Reset Password</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    
    <!-- CSS -->
    <link rel="stylesheet" href="./assets/login.css">

</head>
<body>
    <div class="login-container">
        <img src="./assets/images/logoall-grey.png" alt="Logo">
        <header>Reset Password</header>
        <form action="./controller/reset_password.php" method="POST">
            <!-- <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>"> -->
            <div class="input-field">
                <input type="password" id="password" name="password" required>
                <label for="password">Create new Password</label>
            </div>
            <div class="input-field">
                <input type="password" id="password" name="password" required>
                <label for="password">Confirm new Password</label>
            </div>
            <div class="checkbox-field" style="text-align: left; font-size:12px; margin-left: 10px;">
                <input type="checkbox" id="show-password">
                <label for="show-password">Show Password</label>
            </div><br>
            <button type="submit" class="submit-button">Reset Password</button>
        </form>
       
    </div>
</body>

<script>
    const showPassword = document.getElementById('show-password');
    const passwordField = document.getElementById('password');

    showPassword.addEventListener('change', function() {
        if (showPassword.checked) {
            passwordField.type = 'text';
        } else {
            passwordField.type = 'password';
        }
    });
</script>
</html>
