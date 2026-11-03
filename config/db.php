<?php
/**
 * Creates one shared PDO connection to the MySQL/MariaDB database.
 * Every query in the system uses prepared statements through this connection.
 */
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Do not show database details to the user.
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('The system cannot connect to the database. Please make sure MySQL is running in XAMPP.');
        }
    }

    return $pdo;
}
