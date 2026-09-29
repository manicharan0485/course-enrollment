<?php
/**
 * Database Connection Handler
 * Delegates to config/config.php, which builds and returns the PDO instance
 */
require_once __DIR__ . '/../config/config.php';

return $pdo;