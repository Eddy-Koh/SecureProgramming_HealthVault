<?php
// crypto_vault.php - Patient Medical Records Symmetric Protection

require_once __DIR__ . '/vendor/autoload.php';
// createUnsafeImmutable() also populates getenv()/putenv(), which the code below relies on.
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

/* OLD VULNERABLE CODE (Flaw F & G: ECB mode + hardcoded key)
   $secret_key = "MedVaultKey123!";
   $encrypted = openssl_encrypt($medical_payload, 'aes-128-ecb', $secret_key);
   // ECB leaked ciphertext patterns; key was hardcoded directly in source.
*/

// FIX: encrypt with AES-256-GCM, random IV, key loaded from .env.
function encryptVaultPayload(string $plaintext, string $key): string
{
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

    // Pack IV + ciphertext + tag in fixed order, then base64 encode for transport.
    return base64_encode($iv . $ciphertext . $tag);
}

// FIX: decrypt with explicit tag verification instead of letting failure pass silently.
function decryptVaultPayload(string $encoded, string $key): string
{
    $raw = base64_decode($encoded);
    $iv         = substr($raw, 0, 12);
    $tag        = substr($raw, -16);
    $ciphertext = substr($raw, 12, -16);

    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

    // openssl_decrypt() returns false on tag mismatch; this turns that
    // silent failure into a controlled, isolated exception.
    if ($plaintext === false) {
        throw new RuntimeException('Decryption failed: authentication tag mismatch or tampered ciphertext.');
    }
    return $plaintext;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $medical_payload = $_POST['payload'] ?? '';
    $key = base64_decode(getenv('VAULT_ENCRYPTION_KEY')); // 32-byte AES-256 key

    $encoded = encryptVaultPayload($medical_payload, $key);
    echo json_encode(["status" => "vaulted", "data" => $encoded]);
}
?>