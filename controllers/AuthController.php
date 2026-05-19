<?php

session_start();
require_once __DIR__ . "/../config/db.php";

// Determine the action: register or login
$action = $_GET["action"] ?? "register";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($action === "register") {

        // ===========================
        // REGISTER
        // ===========================

        // --- Step 2: Grab the data ---
        $name     = trim($_POST["name"]);
        $email    = trim($_POST["email"]);
        $phone    = trim($_POST["phone"]);
        $password = $_POST["password"];
        $role     = $_POST["role"] ?? "user"; 

        // --- Step 3: Validate ---
        $errors = [];

        // 1. Check if any field is empty
        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            $errors[] = "All fields are required.";
        } else {

            // 2. Check email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format.";
            }

            // 3. Check phone format (Moroccan: 10 digits, starts with 06 or 07)
            if (!preg_match("/^0[67]\d{8}$/", $phone)) {
                $errors[] = "Invalid phone number. Must be 10 digits starting with 06 or 07.";
            }

            // 4. Check password length (minimum 6 characters)
            if (strlen($password) < 6) {
                $errors[] = "Password must be at least 6 characters.";
            }
        }

        // Show errors if any
        if (!empty($errors)) {
            echo "<h2>Errors:</h2>";
            foreach ($errors as $error) {
                echo "<p style='color:red;'>❌ " . $error . "</p>";
            }
            echo "<br><a href='../views/auth/register.php'>← Go back</a>";
        } else {

            // --- Step 4: Hash the password ---
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // --- Step 5: Check if email or phone already exists ---
            $db   = new Database();
            $conn = $db->connect();

            // Check email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $emailResult = $stmt->get_result();
            $stmt->close();

            // Check phone
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $phoneResult = $stmt->get_result();
            $stmt->close();

            if ($emailResult->num_rows > 0 || $phoneResult->num_rows > 0) {
                echo "<h2>Error:</h2>";
                if ($emailResult->num_rows > 0) {
                    echo "<p style='color:red;'>❌ This email is already registered.</p>";
                }
                if ($phoneResult->num_rows > 0) {
                    echo "<p style='color:red;'>❌ This phone number is already registered.</p>";
                }
                echo "<br><a href='../views/auth/register.php'>← Go back</a>";
            } else {

                // --- Step 6: Insert user into database ---
                $stmt2 = $conn->prepare(
                    "INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt2->bind_param("sssss", $name, $email, $hashedPassword, $phone, $role);

                if ($stmt2->execute()) {
                    $newUserId = $conn->insert_id;

                    // --- NEW: If Merchant, create profile ---
                    if ($role === 'merchant') {
                        $stmt_m = $conn->prepare("INSERT INTO merchants (user_id, store_name, status) VALUES (?, ?, 'pending')");
                        $shopName = $name . "'s Store";
                        $stmt_m->bind_param("is", $newUserId, $shopName);
                        $stmt_m->execute();
                        $stmt_m->close();
                    }

                    // --- NEW: Generate & Send OTP ---
                    require_once __DIR__ . "/../includes/otp_helpers.php";
                    $otp = generateOTP();
                    
                    if (saveOTP($conn, $newUserId, $otp)) {
                        sendOTP($email, $otp);
                        
                        // Start a temporary session for the user so we know who is verifying
                        $_SESSION["temp_user_id"] = $newUserId;
                        $_SESSION["temp_user_email"] = $email;

                        // Trigger OTP pop-up on verify_otp.php screen
                        $_SESSION["show_otp_popup"] = [
                            'code' => $otp,
                            'type' => 'email'
                        ];

                        header("Location: ../views/auth/verify_otp.php");
                        exit;
                    } else {
                        echo "Account created, but could not send verification code.";
                    }
                } else {
                    echo "<p style='color:red;'>❌ Registration failed. Please try again.</p>";
                    echo "<br><a href='../views/auth/register.php'>← Go back</a>";
                }

                $stmt2->close();
            }

            $conn->close();
        }

    } elseif ($action === "login") {

        // ===========================
        // LOGIN
        // ===========================

        // --- Grab the data ---
        $email    = trim($_POST["email"]);
        $password = $_POST["password"];

        // --- Validate ---
        $errors = [];

        if (empty($email) || empty($password)) {
            $errors[] = "All fields are required.";
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        // Show errors if any
        if (!empty($errors)) {
            echo "<h2>Errors:</h2>";
            foreach ($errors as $error) {
                echo "<p style='color:red;'>❌ " . $error . "</p>";
            }
            echo "<br><a href='../views/auth/login.php'>← Go back</a>";
        } else {

            // --- Find user by email ---
            $db   = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("SELECT id, name, email, password, role, phone, is_verified FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // No user found with this email
                echo "<h2>Error:</h2>";
                echo "<p style='color:red;'>❌ " . userError("login_failed") . "</p>";
                echo "<br><a href='../views/auth/login.php'>← Go back</a>";
            } else {
                // User found → verify password
                $user = $result->fetch_assoc();

                if (password_verify($password, $user["password"])) {
                    // Password is correct → start session!

                    // Store user data in session
                    $_SESSION["user_id"]       = $user["id"];
                    $_SESSION["user_name"]     = $user["name"];
                    $_SESSION["user_email"]    = $user["email"];
                    $_SESSION["user_role"]     = $user["role"];
                    $_SESSION["user_phone"]    = $user["phone"];
                    $_SESSION["user_verified"] = $user["is_verified"];

                    // --- Step 7: Redirect based on role ---
                    if ($user["role"] === "merchant") {
                        // Fetch the merchant ID for this user
                        $m_stmt = $conn->prepare("SELECT id FROM merchants WHERE user_id = ?");
                        $m_stmt->bind_param("i", $user["id"]);
                        $m_stmt->execute();
                        $m_res = $m_stmt->get_result();
                        if ($m_row = $m_res->fetch_assoc()) {
                            $_SESSION["merchant_id"] = $m_row["id"];
                        }
                        $m_stmt->close();
                        
                        header("Location: ../views/merchant/dashboard.php");
                    } elseif ($user["role"] === "admin") {
                        header("Location: ../views/admin/dashboard.php");
                    } else {
                        header("Location: ../views/user/dashboard.php");
                    }
                    exit;
                } else {
                    // Wrong password
                    echo "<h2>Error:</h2>";
                    echo "<p style='color:red;'>❌ " . userError("login_failed") . "</p>";
                    echo "<br><a href='../views/auth/login.php'>← Go back</a>";
                }
            }

            $stmt->close();
            $conn->close();
        }
    }

} elseif ($action === "logout") {

    // ===========================
    // LOGOUT
    // ===========================
    session_destroy();
    header("Location: ../views/auth/login.php");
    exit;

} else {
    echo "No data submitted.";
}
