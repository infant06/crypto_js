<?php
define('SECURE_ACCESS', true);
require_once 'includes/encryption.php';

// CORS settings for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

class SecureAPI
{
    private $encryption;
    private $dataFile;

    public function __construct()
    {
        $this->encryption = new EncryptionHandler();
        $this->dataFile = 'data/secure_data.json';

        // Create data directory if it doesn't exist
        if (!file_exists('data')) {
            mkdir('data', 0755, true);
        }
    }

    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                return $this->sendResponse(false, 'Invalid JSON data');
            }

            $action = $input['action'] ?? '';

            switch ($action) {
                case 'getKey':
                    return $this->generateClientKey();
                case 'submit':
                    return $this->handleSubmission($input);
                case 'getData':
                    return $this->getData($input);
                default:
                    return $this->sendResponse(false, 'Invalid action');
            }
        }

        return $this->sendResponse(false, 'Method not allowed');
    }

    private function generateClientKey()
    {
        $clientKey = $this->encryption->generateClientKey();
        $sessionKey = $this->encryption->hashKey($clientKey);

        return $this->sendResponse(true, 'Keys generated', [
            'clientKey' => $clientKey,
            'sessionKey' => $sessionKey
        ]);
    }

    private function handleSubmission($input)
    {
        try {
            if (!isset($input['encrypted_data']) || !isset($input['client_key'])) {
                return $this->sendResponse(false, 'Missing required data');
            }

            // Recreate session key
            $sessionKey = $this->encryption->hashKey($input['client_key']);

            // Decrypt the data
            $decryptedData = $this->decryptClientData($input['encrypted_data'], $sessionKey);

            if (!$decryptedData) {
                return $this->sendResponse(false, 'Decryption failed');
            }

            // Validate and sanitize data
            $validatedData = $this->validateData($decryptedData);

            if (!$validatedData) {
                return $this->sendResponse(false, 'Data validation failed');
            }

            // Store the data securely
            $dataId = $this->storeData($validatedData);

            return $this->sendResponse(true, 'Data stored successfully', [
                'data_id' => $dataId
            ]);

        } catch (Exception $e) {
            error_log('API Error: ' . $e->getMessage());
            return $this->sendResponse(false, 'Internal server error');
        }
    }
    private function decryptClientData($encryptedData, $sessionKey)
    {
        try {
            // Decode base64
            $combined = base64_decode($encryptedData);

            if (!$combined) {
                error_log('Failed to decode base64 data');
                return false;
            }

            // Extract IV (first 16 bytes) and ciphertext
            $iv = substr($combined, 0, 16);
            $ciphertext = substr($combined, 16);

            // Convert hex session key to binary for OpenSSL
            $binaryKey = hex2bin($sessionKey);

            if (!$binaryKey) {
                error_log('Failed to convert session key from hex to binary');
                return false;
            }

            // Decrypt using OpenSSL
            $decrypted = openssl_decrypt(
                $ciphertext,
                'AES-256-CBC',
                $binaryKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                error_log('OpenSSL decryption failed: ' . openssl_error_string());
                return false;
            }

            $jsonDecoded = json_decode($decrypted, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('JSON decode failed: ' . json_last_error_msg());
                return false;
            }

            return $jsonDecoded;

        } catch (Exception $e) {
            error_log('Decryption error: ' . $e->getMessage());
            return false;
        }
    }

    private function validateData($data)
    {
        $required = ['name', 'email', 'phone', 'message'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                return false;
            }
        }

        // Sanitize data
        $sanitized = [
            'name' => htmlspecialchars(trim($data['name'])),
            'email' => filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL),
            'phone' => htmlspecialchars(trim($data['phone'])),
            'message' => htmlspecialchars(trim($data['message'])),
            'sensitive_data' => isset($data['sensitive_data']) ? htmlspecialchars(trim($data['sensitive_data'])) : '',
            'timestamp' => $data['timestamp'] ?? time(),
            'submitted_at' => date('Y-m-d H:i:s')
        ];

        // Validate email
        if (!filter_var($sanitized['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return $sanitized;
    }

    private function storeData($data)
    {
        // Generate unique ID
        $dataId = uniqid('data_', true);

        // Encrypt data before storage
        $encryptedForStorage = $this->encryption->encrypt($data);

        // Load existing data
        $allData = [];
        if (file_exists($this->dataFile)) {
            $existing = file_get_contents($this->dataFile);
            $allData = json_decode($existing, true) ?: [];
        }

        // Add new data
        $allData[$dataId] = [
            'encrypted_data' => $encryptedForStorage,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Save to file
        file_put_contents($this->dataFile, json_encode($allData, JSON_PRETTY_PRINT));

        return $dataId;
    }

    private function getData($input)
    {
        if (!file_exists($this->dataFile)) {
            return $this->sendResponse(true, 'No data found', []);
        }

        $allData = json_decode(file_get_contents($this->dataFile), true);
        $decryptedData = [];

        foreach ($allData as $id => $item) {
            $decrypted = $this->encryption->decrypt($item['encrypted_data']);
            if ($decrypted) {
                $decryptedData[] = [
                    'id' => $id,
                    'data' => $decrypted,
                    'created_at' => $item['created_at']
                ];
            }
        }

        return $this->sendResponse(true, 'Data retrieved', $decryptedData);
    }

    private function sendResponse($success, $message, $data = null)
    {
        $response = [
            'success' => $success,
            'message' => $message
        ];

        if ($data !== null) {
            if (is_array($data) && isset($data[0])) {
                $response['data'] = $data;
            } else {
                $response = array_merge($response, $data);
            }
        }

        echo json_encode($response);
        exit;
    }
}

// Initialize and handle the request
$api = new SecureAPI();
$api->handleRequest();
?>