<?php
/**
 * auth/register.php — Manage Users (Presentation Layer)
 * Uses User objects — data accessed via getter methods.
 */
require_once "../config/db.php";
require_once "../auth/session.php";
require_once "../classes/User.php";

if($_SERVER["REQUEST_METHOD"]==="POST" && isset($_POST["deactivate"])){
    requireOwner();
    $uid = (int)$_POST["uid"];
    if($uid && $uid !== $_SESSION["uid"]) User::deactivate($conn, $uid);
    flash("success","Account deactivated."); header("Location: register.php"); exit;
}

$t = "Manage Users"; $a = "users";
require_once "../includes/header.php";
include  "../includes/flash.php";

$q      = trim($_GET["q"]      ?? "");
$role   = trim($_GET["role"]   ?? "");
$status = trim($_GET["status"] ?? "");

// Returns array of User objects
$users = User::getAll($conn, $q ?: null, $role ?: null, $status ?: null);
$total = count($users);
?>
<div class="page-hdr">
  <h1>Manage Users <span style="font-size:14px;color:var(--t2);font-weight:400">(<?= $total ?>)</span></h1>
  <?php if(isOwner()): ?><a href="create.php" class="btn btn-primary">+ Create user</a><?php endif; ?>
</div>
<div class="card">
  <form method="GET" class="filter-bar">
    <input type="text" name="q" class="fc" placeholder="Search name or email..." value="<?= h($q) ?>" style="width:220px">
    <select name="role" class="fc">
      <option value="">All roles</option>
      <option value="store_owner" <?= $role==="store_owner"?"selected":"" ?>>Store Owner</option>
      <option value="employee"    <?= $role==="employee"?"selected":"" ?>>Employee</option>
    </select>
    <select name="status" class="fc">
      <option value="">All statuses</option>
      <option value="active"   <?= $status==="active"?"selected":"" ?>>Active</option>
      <option value="inactive" <?= $status==="inactive"?"selected":"" ?>>Inactive</option>
    </select>
    <button class="btn btn-outline btn-sm">Filter</button>
    <?php if($q||$role||$status): ?><a href="register.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    <span style="margin-left:auto;font-size:12px;color:var(--t2)"><?= $total ?> user<?= $total!==1?"s":"" ?></span>
  </form>
  <table class="tbl">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if(empty($users)): ?>
    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--t3)">No users found.</td></tr>
    <?php else: ?>
    <?php foreach($users as $user): // Each $user is a User object ?>
    <tr>
      <td class="fw"><?= h($user->getName()) ?></td>
      <td class="mono muted"><?= h($user->getEmail()) ?></td>
      <td><span class="badge <?= $user->getRoleBadge() ?>"><?= $user->getRoleLabel() ?></span></td>
      <td><span class="badge <?= $user->getStatusBadge() ?>"><?= $user->getStatusLabel() ?></span></td>
      <td class="muted" style="font-size:11px"><?= $user->getFormattedDate() ?></td>
      <td><div style="display:flex;gap:5px;align-items:center">
        <a href="view.php?id=<?= $user->getId() ?>" class="icon-btn" title="View">&#128065;</a>
        <?php if(isOwner()): ?>
        <a href="edit.php?id=<?= $user->getId() ?>" class="icon-btn" title="Edit">&#9998;</a>
        <?php if($user->isActive() && $user->getId() !== $_SESSION["uid"]): ?>
        <form method="POST" style="display:inline">
          <input type="hidden" name="uid" value="<?= $user->getId() ?>">
          <button name="deactivate" class="icon-btn del" title="Deactivate"
                  onclick="return confirm('Deactivate <?= h(addslashes($user->getName())) ?>?')">&#128683;</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
      </div></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>