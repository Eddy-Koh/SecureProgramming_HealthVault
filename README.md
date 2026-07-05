# SECR4483 Secure Programming – Alternative Assessment

**Student:** Eddy Koh Wei Hen
**Matric No.:** A22EC0154
**Course:** SECR4483/SCSR4483 Secure Programming
**Lecturer:** Dr. Mohd Kufaisal Bin Mohd Sidik

## Overview

This repository contains the forensic security audit and remediation of three vulnerable PHP components from the MediChain E-MedicVault system: `search.php`, `auth.php`, and `crypto_vault.php`. Each file was identified with critical vulnerabilities, refactored with secure coding practices, and verified with an automated PHPUnit test suite.

## Vulnerabilities Identified & Fixed

| File | Vulnerability | Fix Applied |
|---|---|---|
| `search.php` | SQL Injection via raw string concatenation | Migrated to PDO prepared statements with bound parameters |
| `search.php` | Reflected XSS (unescaped output) | Applied `htmlspecialchars()` with `ENT_QUOTES` on all user-facing output |
| `auth.php` | Bound Constraint Failure (byte vs character mismatch) | Replaced `strlen()` with `mb_strlen()` for UTF-8-aware length checks |
| `auth.php` | Obsolete Cryptographic Primitive (MD5, unsalted) | Migrated to `PASSWORD_ARGON2ID` with `password_verify()` |
| `crypto_vault.php` | ECB Mode Pattern Leakage | Migrated to AES-256-GCM (authenticated encryption) |
| `crypto_vault.php` | Hardcoded Cryptographic Key | Moved secrets to `.env`, excluded via `.gitignore` |

## Project Structure

    ├── search.php              # Patient record search (refactored)
    ├── auth.php                # Staff key authentication (refactored)
    ├── crypto_vault.php        # Patient record encryption (refactored)
    ├── CryptoVaultTest.php     # PHPUnit test suite
    ├── schema.sql              # Database schema and seed data
    ├── .env.example            # Environment variable template
    ├── .gitignore              # Excludes .env, vendor/, and test cache
    ├── .db_config.php          # Database Config
    └── README.md

## Setup

1. Clone this repository and install dependencies:

       composer install

2. Copy `.env.example` to `.env` and fill in real values:

       cp .env.example .env

3. Import `schema.sql` into your local MySQL/MariaDB instance.
4. Configure `db_config.php` with your database credentials.

## Testing Runbook

### Terminal A — start the app server (leave this running)

    cd "D:\Programming\Secure Programming Project\SP_AA"
    php -S localhost:8000

### Terminal B — run each test

**1. PHPUnit suite (crypto_vault.php)**

    php vendor/bin/phpunit --testdox CryptoVaultTest.php

Expect: `3 / 3 (100%)`, `OK (3 tests, 4 assertions)`.

**2. auth.php — correct vs wrong credential**

    curl.exe -X POST http://localhost:8000/auth.php -d "auth_key=testkey123"
    curl.exe -X POST http://localhost:8000/auth.php -d "auth_key=wrongkey"

Expect: `Access Granted.` then `Access Denied.`

**3. auth.php — multi-byte bound-check proof**

    $payload = "auth_key=" + ("😀" * 90)   # 360 bytes, 90 characters
    curl.exe -X POST http://localhost:8000/auth.php --data-urlencode $payload

Expect: `Access Denied.` (not the old "Bound overflow detected") — 90 characters is under the 256-character limit, even though it's 360 bytes, proving `mb_strlen` measures characters correctly.

**4. search.php — normal search**

    curl.exe "http://localhost:8000/search.php?keyword=John"

Expect: patient record HTML for "John Doe".

**5. search.php — SQL injection attempt**

    curl.exe "http://localhost:8000/search.php?keyword=%25%27%20UNION%20SELECT%20username%2Cauth_key_hash%2Crole%20FROM%20staff_credentials%20--%20"

Expect: `No records found for: ...` — the UNION payload is treated as a literal search string, not SQL.

**6. search.php — reflected XSS attempt**

    curl.exe "http://localhost:8000/search.php?keyword=<script>alert(1)</script>"

Expect: response contains `&lt;script&gt;alert(1)&lt;/script&gt;` (escaped), not a raw `<script>` tag.

**7. crypto_vault.php — encrypt a payload**

    curl.exe -X POST http://localhost:8000/crypto_vault.php --data-urlencode "payload=DIAGNOSIS: Stage-2 Carcinoma."

Expect: `{"status":"vaulted","data":"<base64>"}` — run it twice and note the base64 output differs each time (random IV), unlike the old ECB version which would've been identical.

When done, stop the server in Terminal A with `Ctrl+C`.
