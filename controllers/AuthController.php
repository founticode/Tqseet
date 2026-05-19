<?php

session_start();
require_once __DIR__ . "/../config/db.php";

// Determine the action: register, login, logout, forgot_password, reset_password
$action = $_GET["action"] ?? "register";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($action === "register") {

        // ===========================
        // REGISTER
        // ===========================

        // --- Grab the data ---
        $name     = trim($_POST["name"]);
        $email    = trim($_POST["email"]);
        $phone    = trim($_POST["phone"]);
        $password = $_POST["password"];
        $role     = $_POST["role"] ?? "user"; 

        // --- Validate ---
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

        // Redirect with errors if any
        if (!empty($errors)) {
            $_SESSION['error'] = implode("<br>", $errors);
            header("Location: ../views/auth/register.php");
            exit;
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
                $errs = [];
                if ($emailResult->num_rows > 0) {
                    $errs[] = "This email is already registered.";
                }
                if ($phoneResult->num_rows > 0) {
                    $errs[] = "This phone number is already registered.";
                }
                $_SESSION['error'] = implode("<br>", $errs);
                $conn->close();
                header("Location: ../views/auth/register.php");
                exit;
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

                        $conn->close();
                        header("Location: ../views/auth/verify_otp.php");
                        exit;
                    } else {
                        $_SESSION['error'] = "Account created, but could not generate verification code.";
                        $conn->close();
                        header("Location: ../views/auth/register.php");
                        exit;
                    }
                } else {
                    $_SESSION['error'] = "Registration failed. Please try again.";
                    $stmt2->close();
                    $conn->close();
                    header("Location: ../views/auth/register.php");
                    exit;
                }
            }
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

        // Redirect with errors if any
        if (!empty($errors)) {
            $_SESSION['error'] = implode("<br>", $errors);
            header("Location: ../views/auth/login.php");
            exit;
        } else {

            // --- Find user by email ---
            $db   = new Database();
            $conn = $db->connect();

            $stmt = $conn->prepare("SELECT id, name, email, password, role, phone, is_verified FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $_SESSION['error'] = userError("login_failed");
                $stmt->close();
                $conn->close();
                header("Location: ../views/auth/login.php");
                exit;
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
                        
                        $conn->close();
                        header("Location: ../views/merchant/dashboard.php");
                    } elseif ($user["role"] === "admin") {
                        $conn->close();
                        header("Location: ../views/admin/dashboard.php");
                    } else {
                        $conn->close();
                        header("Location: ../views/user/dashboard.php");
                    }
                    exit;
                } else {
                    $_SESSION['error'] = userError("login_failed");
                    $stmt->close();
                    $conn->close();
                    header("Location: ../views/auth/login.php");
                    exit;
                }
            }
        }

    } elseif ($action === "forgot_password") {

        // ===========================
        // FORGOT PASSWORD REQUEST
        // ===========================

        $email = trim($_POST["email"]);

        if (empty($email)) {
            $_SESSION['error'] = "Email is required.";
            header("Location: ../views/auth/forgot_password.php");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
            header("Location: ../views/auth/forgot_password.php");
            exit;
        }

        $db   = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $_SESSION['error'] = "No account found with this email.";
            $stmt->close();
            $conn->close();
            header("Location: ../views/auth/forgot_password.php");
            exit;
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Generate OTP
        require_once __DIR__ . "/../includes/otp_helpers.php";
        $otp = generateOTP();

        if (saveOTP($conn, $user["id"], $otp)) {
            sendOTP($email, $otp);

            // Store temporary reset session variables
            $_SESSION["reset_user_id"] = $user["id"];
            $_SESSION["reset_user_email"] = $email;

            // Trigger OTP pop-up on reset screen
            $_SESSION["show_otp_popup"] = [
                'code' => $otp,
                'type' => 'reset'
            ];

            $_SESSION["success"] = "A 6-digit verification code has been sent to your email.";
            $conn->close();
            header("Location: ../views/auth/reset_password.php");
            exit;
        } else {
            $_SESSION['error'] = "Failed to generate verification code. Please try again.";
            $conn->close();
            header("Location: ../views/auth/forgot_password.php");
            exit;
        }

    } elseif ($action === "reset_password") {

        // ===========================
        // RESET PASSWORD SUBMISSION
        // ===========================

        if (!isset($_SESSION["reset_user_id"])) {
            $_SESSION['error'] = "Session expired. Please start again.";
            header("Location: ../views/auth/forgot_password.php");
            exit;
        }

        $resetUserId = $_SESSION["reset_user_id"];
        $code        = trim($_POST["code"]);
        $password    = $_POST["password"];
        $confirm     = $_POST["confirm_password"];

        if (empty($code) || empty($password) || empty($confirm)) {
            $_SESSION['error'] = "All fields are required.";
            header("Location: ../views/auth/reset_password.php");
            exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['error'] = "Password must be at least 6 characters.";
            header("Location: ../views/auth/reset_password.php");
            exit;
        }

        if ($password !== $confirm) {
            $_SESSION['error'] = "Passwords do not match.";
            header("Location: ../views/auth/reset_password.php");
            exit;
        }

        $db   = new Database();
        $conn = $db->connect();

        require_once __DIR__ . "/../includes/otp_helpers.php";

        if (verifyOTP($conn, $resetUserId, $code)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $resetUserId);

            if ($stmt->execute()) {
                unset($_SESSION["reset_user_id"]);
                unset($_SESSION["reset_user_email"]);

                $_SESSION["success"] = "Password reset successfully! Please login with your new password.";
                $stmt->close();
                $conn->close();
                header("Location: ../views/auth/login.php");
                exit;
            } else {
                $_SESSION['error'] = "Database error. Failed to update password.";
                $stmt->close();
                $conn->close();
                header("Location: ../views/auth/reset_password.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Invalid or expired verification code.";
            $conn->close();
            header("Location: ../views/auth/reset_password.php");
            exit;
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
