<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in.
 * If not → redirect to login page.
 */
function requireLogin() {
    if (!isset($_SESSION["user_id"])) {
        header("Location: /views/auth/login.php");
        exit;
    }
}

/**
 * Check if user has the required role.
 * If not → show access denied.
 * 
 * @param string|array $role - one role ("admin") or multiple (["merchant", "admin"])
 */
function requireRole($role) {
    requireLogin(); // First make sure they're logged in

    // Convert string to array so we can always use in_array()
    if (is_string($role)) {
        $role = [$role];
    }

    if (!in_array($_SESSION["user_role"], $role)) {
        http_response_code(403);
        echo "<h2>403 - Access Denied</h2>";
        echo "<p>You do not have permission to view this page.</p>";
        echo "<a href='/views/auth/login.php'>← Go to Login</a>";
        exit;
    }
}

/**
 * Check if user is logged in (returns true/false).
 * Does NOT redirect — just checks.
 */
function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

/**
 * Get current logged-in user's data from session.
 * Returns an array with user info, or null if not logged in.
 * 
 * @return array|null
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        "id"    => $_SESSION["user_id"],
        "name"  => $_SESSION["user_name"],
        "email" => $_SESSION["user_email"],
        "role"  => $_SESSION["user_role"],
        "phone" => $_SESSION["user_phone"] ?? "Not set",
        "is_verified" => $_SESSION["user_verified"] ?? 0,
    ];
}

/**
 * Redirect logged-in users away from guest-only pages (like login/register).
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        $role = $_SESSION["user_role"] ?? "user";
        header("Location: /views/{$role}/dashboard.php");
        exit;
    }
}
