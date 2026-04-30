<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Register</title>
</head>
<body>

    <h1>Create Account</h1>

    <form action="../../controllers/AuthController.php?action=register" method="POST">

        <label for="name">Full Name:</label><br>
        <input type="text" id="name" name="name" placeholder="Enter your name"><br><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" placeholder="Enter your email"><br><br>

        <label for="phone">Phone:</label><br>
        <input type="text" id="phone" name="phone" placeholder="Enter your phone"><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" placeholder="Enter your password"><br><br>

        <label for="confirm_password">Confirm Password:</label><br>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password"><br><br>

        <button type="submit">Register</button>

    </form>

    <br>
    <p>Already have an account? <a href="login.php">Login here</a></p>

</body>
</html>
