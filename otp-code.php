<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="96x96" href="./assets/images/logo-dark.png">
    <title>Forgot Password</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    
    <!-- CSS -->
    <link rel="stylesheet" href="./assets/login.css">
</head>
<body>
    <div class="login-container">
        <img src="./assets/images/logoall-grey.png" alt="Logo">
        <header>Code Verification</header>
        <p>We've sent a password reset otp to your email [emailAddress]</p><br>
        <form action="" method="POST">
            <div class="input-field">
                <input type="email" id="email" name="email" required>
                <label for="email">Enter OTP code</label>
            </div>
            <button type="submit" class="submit-button">Submit</button>
        </form>
        
    </div>
</body>
</html>
