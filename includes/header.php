<?php
// $t = page title, $a = active nav key
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../auth/session.php';
requireLogin();
$_ac = alertCount($conn);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($t??'Dashboard') ?> — ElectroStock Solutions</title>
<link rel="stylesheet" href="<?= BASE ?>assets/style.css">
</head>
<body>
<?php include __DIR__.'/sidebar.php'; ?>
<header class="topbar">
  <span class="tb-title"><?= h($t??'Dashboard') ?></span>
  <form class="tb-search" action="<?= BASE ?>products/search.php" method="GET">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
    <input type="text" name="q" placeholder="Search Apple products…" value="<?= h($_GET['q']??'') ?>">
  </form>
  <a href="<?= BASE ?>monitoring/index.php" class="tb-bell" title="Alerts">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
    <?php if($_ac>0): ?><span class="tb-badge"><?= $_ac ?></span><?php endif; ?>
  </a>
</header>
<main class="main"><div class="content">
