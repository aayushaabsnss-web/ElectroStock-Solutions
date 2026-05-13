<?php
require_once 'config/db.php';
$hash = password_hash('Admin@123', PASSWORD_DEFAULT);
mysqli_query($conn, "UPDATE users SET password_hash='$hash' WHERE email='owner@electrostock.com'");
mysqli_query($conn, "UPDATE users SET password_hash='$hash' WHERE email='staff@electrostock.com'");
echo "Done — you can log in now. Delete this file.";
?>