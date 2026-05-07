<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function loggedIn()  { return !empty($_SESSION['uid']); }
function isOwner()   { return loggedIn() && $_SESSION['role'] === 'store_owner'; }

function requireLogin() {
    if (!loggedIn()) { header('Location: ' . BASE . 'auth/login.php'); exit; }
}
function requireOwner() {
    requireLogin();
    if (!isOwner()) { flash('error','Access denied — Store Owner only.'); header('Location: ' . BASE . 'dashboard/index.php'); exit; }
}
function flash($type, $msg) { $_SESSION['flash'][$type] = $msg; }
function getFlash($type) {
    $m = $_SESSION['flash'][$type] ?? '';
    unset($_SESSION['flash'][$type]);
    return $m;
}
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function initials() {
    $parts = array_filter(explode(' ', $_SESSION['name'] ?? ''));
    return strtoupper(implode('', array_map(fn($p) => $p[0], array_slice($parts, 0, 2))));
}
function alertCount($conn) {
    $r = mysqli_query($conn,"SELECT COUNT(*) c FROM monitoring WHERE alert_status='active'");
    return (int)(mysqli_fetch_assoc($r)['c'] ?? 0);
}