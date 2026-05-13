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
