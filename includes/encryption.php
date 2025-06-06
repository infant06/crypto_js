<?php
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}
require_once 'config.php';

class EncryptionHandler
{
    private $key;
    private $iv;

    public function __construct()
    {
        $this->key = ENCRYPTION_KEY;
        $this->iv = ENCRYPTION_IV;
    }
    public function encrypt($data)
    {
        // Generate a random IV for each encryption
        $iv = random_bytes(16);

        // Decode the base64 key to get raw bytes
        $key = base64_decode($this->key);

        $encrypted = openssl_encrypt(
            json_encode($data),
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        // Combine IV and encrypted data, then base64 encode
        return base64_encode($iv . $encrypted);
    }
    public function decrypt($encryptedData)
    {
        $combined = base64_decode($encryptedData);

        if (!$combined || strlen($combined) < 16) {
            return false;
        }

        // Extract IV (first 16 bytes) and encrypted data
        $iv = substr($combined, 0, 16);
        $encrypted = substr($combined, 16);

        // Decode the base64 key to get raw bytes
        $key = base64_decode($this->key);

        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            return false;
        }

        return json_decode($decrypted, true);
    }

    public function generateClientKey()
    {
        // Generate a temporary key for client-side encryption
        // This will be used with the server key for double encryption
        return base64_encode(random_bytes(32));
    }

    public function hashKey($clientKey)
    {
        // Combine client key with server key for additional security
        return hash('sha256', $clientKey . $this->key);
    }
}
?>