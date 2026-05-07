<?php
require_once '../config/db.php';
require_once '../auth/session.php';
if (loggedIn()) { header('Location: ' . BASE . 'dashboard/index.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!$email || !$pass) {
        $err = 'Please enter your email and password.';
    } else {
        $st = mysqli_prepare($conn, "SELECT * FROM users WHERE email=? AND is_active=1 LIMIT 1");
        mysqli_stmt_bind_param($st, 's', $email);
        mysqli_stmt_execute($st);
        $u = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        if (!$u || !password_verify($pass, $u['password_hash'])) {
            $err = 'Incorrect email or password.';
        } else {
            $_SESSION['uid']  = $u['id'];
            $_SESSION['name'] = $u['full_name'];
            $_SESSION['role'] = $u['role'];
            header('Location: ' . BASE . 'dashboard/index.php'); exit;
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — ElectroStock Solutions</title>
<link rel="stylesheet" href="<?= BASE ?>assets/style.css">
</head>
<body class="auth-body">
<div class="auth-center">
  <div class="auth-card">
    <div class="auth-logo">ESS</div>
    <h1 class="auth-h">Sign in</h1>
    <p class="auth-sub">ElectroStock Solutions &middot; Apple Inventory</p>
    <?php if ($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
    <?php if ($f=getFlash('error')): ?><div class="alert alert-danger"><?= h($f) ?></div><?php endif; ?>
    <form method="POST">
      <div class="fg"><label>Email address</label>
        <input type="email" name="email" class="fc" value="<?= h($_POST['email']??'') ?>" placeholder="owner@electrostock.com" required autofocus></div>
      <div class="fg"><label>Password</label>
        <input type="password" name="password" class="fc" placeholder="••••••••" required></div>
      <button type="submit" class="btn btn-primary w100 mt8">Sign in &rarr;</button>
    </form>
    <p class="auth-hint">Default: owner@electrostock.com &nbsp;/&nbsp; Admin@123</p>
  </div>
</div>
</body></html>