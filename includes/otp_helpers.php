<?php

/**
 * Generate a random 6-digit numeric OTP.
 * Example: 482910
 */
function generateOTP() {
    // random_int is cryptographically secure and perfect for OTPs
    try {
        return (string)random_int(100000, 999999);
    } catch (Exception $e) {
        // Fallback for very old PHP versions, though random_int is standard now
        return (string)mt_rand(100000, 999999);
    }
}

/**
 * Save a generated OTP to the database with a 10-minute expiration.
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - ID of the user receiving the OTP
 * @param string $code - The 6-digit code
 * @return bool - Success or failure
 */
function saveOTP($conn, $userId, $code) {
    // Set expiration to 10 minutes from now
    $expiresAt = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // Prepare the SQL (Matching your schema: table 'otp_codes', column 'code')
    $stmt = $conn->prepare("INSERT INTO otp_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $code, $expiresAt);

    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Simulate sending an OTP (Logs it to a file for testing).
 * 
 * @param string $email - User's email
 * @param string $code - The 6-digit code
 * @return bool - Always returns true for simulation
 */
function sendOTP($email, $code) {
    $message = "[" . date("Y-m-d H:i:s") . "] Sending OTP $code to $email" . PHP_EOL;
    
    // Save to a local log file so you can "read" the code without a real email server
    file_put_contents(__DIR__ . "/../otp_sent.log", $message, FILE_APPEND);
    
    return true; 
}

/**
 * Verify if the provided OTP is correct and not expired.
 * 
 * @param mysqli $conn - Database connection
 * @param int $userId - ID of the user
 * @param string $inputCode - The code entered by the user
 * @return bool - True if valid, False otherwise
 */
function verifyOTP($conn, $userId, $inputCode) {
    $now = date("Y-m-d H:i:s");
    // 1. Fetch the latest OTP for this user that hasn't expired
    $stmt = $conn->prepare("SELECT id FROM otp_codes 
                            WHERE user_id = ? 
                            AND code = ? 
                            AND expires_at > ? 
                            ORDER BY id DESC LIMIT 1");
    
    $stmt->bind_param("iss", $userId, $inputCode, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 2. Success! Delete the codes for this user so they can't be used again
        $stmt_delete = $conn->prepare("DELETE FROM otp_codes WHERE user_id = ?");
        $stmt_delete->bind_param("i", $userId);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        $stmt->close();
        return true;
    }

    $stmt->close();
    return false; // Code wrong or expired
}
