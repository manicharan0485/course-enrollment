<?php
/**
 * Environment Variable Loader
 * Load .env file and set environment variables
 */

$envFile = __DIR__ . '/../.env';

if (!file_exists($envFile)) {
    die("Error: .env file not found at {$envFile}. Copy .env.example to .env and configure it.");
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    // Skip comments
    if (strpos(trim($line), '#') === 0) {
        continue;
    }
    
    // Parse KEY=VALUE
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes if present
        if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
            (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
            $value = substr($value, 1, -1);
        }
        
        // Set as environment variable
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}
?>
