<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Login</title>
</head>
<body>

    <h1>Login</h1>

    <form action="../../controllers/AuthController.php?action=login" method="POST">

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" placeholder="Enter your email"><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" placeholder="Enter your password"><br><br>

        <button type="submit">Login</button>

    </form>

    <br>
    <p>Don't have an account? <a href="register.php">Register here</a></p>

</body>
</html>
