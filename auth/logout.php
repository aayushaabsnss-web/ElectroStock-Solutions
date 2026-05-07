<?php
require_once '../config/db.php';
require_once '../auth/session.php';
session_destroy();
header('Location: ' . BASE . 'auth/login.php'); exit;