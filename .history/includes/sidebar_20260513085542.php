<?php // sidebar.php — $a = active key, $conn available ?>
<aside class="sidebar">
  <div class="sb-top">
    <div class="sb-brand">
      <div class="sb-logo">ESS</div>
      <div><div class="sb-name">ElectroStock</div><div class="sb-sub">Apple Inventory</div></div>
    </div>
    <div class="role-chip <?= isOwner()?'rc-owner':'rc-emp' ?>">
      <span class="rdot"></span><?= isOwner()?'Store Owner':'Employee' ?>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nl">Overview</div>
    <a class="ni <?= ($a??'')==='dash'?'active':'' ?>" href="<?= BASE ?>dashboard/index.php">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <div class="nl">Inventory</div>
    <a class="ni <?= ($a??'')==='products'?'active':'' ?>" href="<?= BASE ?>products/index.php">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
      Products
    </a>
    <a class="ni <?= ($a??'')==='stock'?'active':'' ?>" href="<?= BASE ?>stock/index.php">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12l5-5 4 4 5-5 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Stock
    </a>
    <a class="ni <?= ($a??'')==='monitoring'?'active':'' ?>" href="<?= BASE ?>monitoring/index.php">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
      Alerts
      <?php $ac=alertCount($conn); if($ac>0): ?><span class="nb"><?= $ac ?></span><?php endif; ?>
    </a>
    <a class="ni <?= ($a??'')==='orders'?'active':'' ?>" href="<?= BASE ?>orders/index.php">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Orders
    </a>
    <?php if(isOwner()): ?>
    <div class="nl">Admin</div>
    <a class="ni <?= ($a??'')==='users'?'active':'' ?>" href="<?= BASE ?>auth/register.php">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      Users <span class="nav-lock">Owner</span>
    </a>
    <?php endif; ?>
  </nav>
  <div class="sb-foot">
    <div class="user-row">
      <div class="uav"><?= h(initials()) ?></div>
      <div style="flex:1;min-width:0">
        <div class="uname"><?= h($_SESSION['name']??'') ?></div>
        <div class="urole"><?= h($_SESSION['role']??'') ?></div>
      </div>
      <a href="<?= BASE ?>auth/logout.php" class="logout-btn" title="Logout">&#8619;</a>
    </div>
  </div>
</aside>