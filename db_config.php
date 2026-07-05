<?php
// db_config.php - Database Connection Bootstrap
// Used by: search.php

// FIX: credentials loaded from .env instead of being hardcoded, and the
// connection now uses a scoped, least-privilege application account
// (medvault_app_user) instead of the original high-privilege root account.
require_once __DIR__ . '/vendor/autoload.php';
// createUnsafeImmutable() also populates getenv()/putenv(), which the code below relies on.
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    getenv('DB_HOST'),
    getenv('DB_NAME')
);

$pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);
?>
