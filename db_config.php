<?php
/*
 * Database Configuration
 * For improved security, store credentials in environment variables
 * instead of hardcoding them in the file.
 */
define('DB_SERVER', getenv('DB_SERVER') ?: 'localhost');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: ''); // IMPORTANT: Set a password!
define('DB_NAME', getenv('DB_NAME') ?: 'requisition_db');

// Attempt to connect to MySQL database
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($mysqli->connect_error){
    // In a production environment, you should log this error and show a generic message.
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}

// Set character set to utf8mb4 for full Unicode support
$mysqli->set_charset("utf8mb4");
