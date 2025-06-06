<?php
// Security configuration
define('ENCRYPTION_KEY', 'E0nbuum/JSZ7zsM5ZveAEyqAD9wA0w0E6fCTWXKiOFo=');
define('ENCRYPTION_IV', 'S3k5fkAm3YDYsJOvbz5owQ==');

// Database configuration (if needed)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cryptojs_db');

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    die('Direct access not allowed');
}
?>