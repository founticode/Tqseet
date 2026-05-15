<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TQSEET - Register</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0;">

    <div style="background: white; padding: 40px; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); width: 100%; max-width: 400px; border: 1px solid #eee;">
        
        <h1 style="margin: 0 0 10px 0; font-size: 2rem; font-weight: 900; text-align: center; letter-spacing: -1px;">Join TQSEET</h1>
        <p style="text-align: center; color: #666; margin-bottom: 30px; font-weight: 500;">Start your journey with us today.</p>

        <form action="../../controllers/AuthController.php?action=register" method="POST">
            
            <!-- Account Type Selector -->
            <div style="margin-bottom: 25px; background: #f1f3f5; padding: 5px; border-radius: 12px; display: flex; gap: 5px;">
                <label style="flex: 1; text-align: center; cursor: pointer; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 0.9rem; transition: 0.3s;">
                    <input type="radio" name="role" value="user" checked style="display: none;">
                    I want to Shop
                </label>
                <label style="flex: 1; text-align: center; cursor: pointer; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 0.9rem; transition: 0.3s; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <input type="radio" name="role" value="merchant" style="display: none;">
                    I want to Sell
                </label>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; font-size: 0.85rem; color: #b2bec3; margin-bottom: 8px; text-transform: uppercase;">Full Name</label>
                <input type="text" name="name" placeholder="John Doe" required style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 12px; box-sizing: border-box; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; font-size: 0.85rem; color: #b2bec3; margin-bottom: 8px; text-transform: uppercase;">Email Address</label>
                <input type="email" name="email" placeholder="john@example.com" required style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 12px; box-sizing: border-box; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; font-size: 0.85rem; color: #b2bec3; margin-bottom: 8px; text-transform: uppercase;">Phone Number</label>
                <input type="text" name="phone" placeholder="+212 6..." required style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 12px; box-sizing: border-box; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; font-size: 0.85rem; color: #b2bec3; margin-bottom: 8px; text-transform: uppercase;">Create Password</label>
                <input type="password" name="password" placeholder="••••••••" required style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 12px; box-sizing: border-box; font-size: 1rem;">
            </div>

            <button type="submit" style="width: 100%; padding: 15px; background: #222; color: white; border: none; border-radius: 12px; font-weight: bold; font-size: 1rem; cursor: pointer; margin-top: 10px; transition: 0.3s;">
                Register Now
            </button>
        </form>

        <p style="text-align: center; margin-top: 25px; color: #666; font-size: 0.9rem;">
            Already have an account? <a href="login.php" style="color: #222; font-weight: bold; text-decoration: none;">Login</a>
        </p>

    </div>

    <!-- JS for toggle effect -->
    <script>
        document.querySelectorAll('input[name="role"]').forEach(input => {
            input.addEventListener('change', function() {
                document.querySelectorAll('input[name="role"]').forEach(i => {
                    i.parentElement.style.background = 'transparent';
                    i.parentElement.style.boxShadow = 'none';
                });
                this.parentElement.style.background = 'white';
                this.parentElement.style.boxShadow = '0 2px 5px rgba(0,0,0,0.05)';
            });
        });
    </script>

</body>
</html>
