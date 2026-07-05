<?php
// auth.php - Staff Key Authentication System

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputKey = $_POST['auth_key'] ?? '';

    /* OLD VULNERABLE CODE (Flaw D: Bound Constraint Failure)
       if (strlen($inputKey) > 256) {
           die("Fatal Error: Bound overflow detected.");
       }
       // strlen() counts bytes, not characters, so multi-byte input bypassed the limit.
    */

    // FIX: mb_strlen() counts real UTF-8 characters instead of raw bytes.
    if (mb_strlen($inputKey, 'UTF-8') > 256) {
        die("Fatal Error: Bound overflow detected.");
    }

    /* OLD VULNERABLE CODE (Flaw E: Weak Hashing)
       $stored_hash = "098f6bcd4621d373cade4e832627b4f6"; // MD5 of 'test'
       if (md5($inputKey) === $stored_hash) {
           echo "Access Granted.";
       }
       // MD5 is fast, unsalted, and easily brute-forced/cracked.
    */

    // FIX: Argon2id hash (generated at registration, loaded from .env, never hardcoded).
    $stored_hash = getenv('AUTH_KEY_HASH');

    // password_verify() re-applies the same cost settings and does a safe comparison.
    if (password_verify($inputKey, $stored_hash)) {
        echo "Access Granted.";
    } else {
        echo "Access Denied.";
    }
}
?>
