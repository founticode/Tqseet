<?php

function userError($type) {

    $errors = [
        "db_error" => "System error. Please try again later.",
        "login_failed" => "Email or password is incorrect",
        "not_verified" => "Please verify your account first",
        "unauthorized" => "Access denied"
    ];

    return $errors[$type] ?? "Unknown error";
}