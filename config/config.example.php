<?php
/**
 * SIAMS - Student Industrial Attachment Management System
 * Application settings. Copy this file to config.php and fill in the database password.
 * Change these values when moving to a live server.
 */

// Database connection (local XAMPP). The account is created by database/setup.sql.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'siams_db');
define('DB_USER', 'siams_app');
define('DB_PASS', 'change-me');

// General settings
define('APP_NAME', 'SIAMS');
define('APP_FULL_NAME', 'Student Industrial Attachment Management System');
define('INSTITUTION', 'Kabarak University – Nakuru Town Campus');
define('BASE_URL', '/siams');

// Security settings
define('MAX_LOGIN_ATTEMPTS', 5);        // failed attempts allowed ...
define('LOCKOUT_MINUTES', 15);          // ... within this many minutes
define('SESSION_TIMEOUT_MINUTES', 15);  // log out after this much inactivity

date_default_timezone_set('Africa/Nairobi');
