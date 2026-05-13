<?php
/**
 * config/db.php
 * Database configuration and connection file.
 * Change DB_USER, DB_PASS and BASE to match your server.
 */

// Database credentials — update these for your environment
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Your MySQL username
define('DB_PASS', '');          // Your MySQL password
define('DB_NAME', 'inventory_db');

// Base URL — must match your folder name in htdocs
// e.g. if folder is 'ElectroStock-Solutions' use '/ElectroStock-Solutions/'
define('BASE', '/ESS/');

// Establish the database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Show a clear error if connection fails
if (!$conn) {
    die('<div style="font-family:sans-serif;padding:30px;background:#1a1a2e;color:#ff6b6b;text-align:center">
        <h2>&#9888; Database connection failed</h2>
        <p>' . mysqli_connect_error() . '</p>
        <p style="color:#888">Edit config/db.php with your MySQL credentials.</p>
    </div>');
}

// Set character encoding to support all Unicode characters
mysqli_set_charset($conn, 'utf8mb4');