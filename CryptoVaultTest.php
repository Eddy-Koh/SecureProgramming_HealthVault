<?php
// =====================================================================
// CryptoVaultTest.php - PHPUnit test suite for crypto_vault.php
// Run with: ./vendor/bin/phpunit CryptoVaultTest.php
// =====================================================================

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/crypto_vault.php';

final class CryptoVaultTest extends TestCase
{
    private string $key;

    protected function setUp(): void
    {
        // Key sourced from .env (loaded when crypto_vault.php was required above).
        // if VAULT_ENCRYPTION_KEY failed to load, every test below would fail.
        $this->key = base64_decode(getenv('VAULT_ENCRYPTION_KEY'));
    }

    // State 1: untampered cryptographic lifecycle
    public function testEncryptDecryptRoundTripSucceeds(): void
    {
        $plaintext = 'DIAGNOSIS: Stage-2 Carcinoma.';
        $encoded = encryptVaultPayload($plaintext, $this->key);
        $decrypted = decryptVaultPayload($encoded, $this->key);

        $this->assertEquals($plaintext, $decrypted);
    }

    // State 2: tampered ciphertext throws AEAD exception
    public function testTamperedCiphertextThrowsException(): void
    {
        $plaintext = 'DIAGNOSIS: Stage-2 Carcinoma.';
        $encoded = encryptVaultPayload($plaintext, $this->key);

        $raw = base64_decode($encoded);
        $raw[15] = $raw[15] === "\x00" ? "\x01" : "\x00"; // flip one ciphertext byte
        $tamperedEncoded = base64_encode($raw);

        $this->expectException(RuntimeException::class);
        decryptVaultPayload($tamperedEncoded, $this->key);
    }

    // State 3: credential hash integrity match
    public function testPasswordHashVerificationMatches(): void
    {
        $rawKey = 'testkey123';
        $hash = password_hash($rawKey, PASSWORD_ARGON2ID);

        $this->assertTrue(password_verify($rawKey, $hash));
        $this->assertFalse(password_verify('wrongkey', $hash));
    }
}
?>