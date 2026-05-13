<?php
/**
 * auth/register.php — Manage Users (Presentation Layer)
 * Lists all users with search and filter.
 * Create User button goes to create.php (separate page).
 */

require_once "../config/db.php";
require_once "../auth/session.php";

// Handle deactivate before HTML output
if($_SERVER["REQUEST_METHOD"]==="POST" && isset($_POST["deactivate"])){
    requireOwner();
    $uid = (int)$_POST["uid"];
    if($uid && $uid !== $_SESSION["uid"])
        mysqli_query($conn,"UPDATE users SET is_active=0 WHERE id=$uid");
    flash("success","Account deactivated.");
    header("Location: register.php"); exit;
}

$t = "Manage Users"; $a = "users";
require_once "../includes/header.php";
include  "../includes/flash.php";

$q      = trim($_GET["q"]      ?? "");
$role   = trim($_GET["role"]   ?? "");
$status = trim($_GET["status"] ?? "");

$where = ["1=1"]; $params = []; $types = "";
if($q){
    $where[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%"; $types .= "ss";
}
if($role)               { $where[] = "role=?";       $params[] = $role; $types .= "s"; }
if($status === "active")   $where[] = "is_active=1";
if($status === "inactive") $where[] = "is_active=0";

$sql = "SELECT * FROM users WHERE ".implode(" AND ",$where)." ORDER BY created_at DESC";
if($params){
    $st = mysqli_prepare($conn,$sql);
    mysqli_stmt_bind_param($st,$types,...$params);
    mysqli_stmt_execute($st);
    $users = mysqli_stmt_get_result($st);
} else {
    $users = mysqli_query($conn,$sql);
}
$total = mysqli_num_rows($users);
?>

<div class="page-hdr">
  <h1>Manage Users <span style="font-size:14px;color:var(--t2);font-weight:400">(<?= $total ?>)</span></h1>
  <?php if(isOwner()): ?>
  <a href="create.php" class="btn btn-primary">+ Create user</a>
  <?php endif; ?>
</div>

<div class="card">
  <!-- Search and filter bar -->
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
    <?php if($q||$role||$status): ?>
    <a href="register.php" class="btn btn-outline btn-sm">Clear</a>
    <?php endif; ?>
    <span style="margin-left:auto;font-size:12px;color:var(--t2)"><?= $total ?> user<?= $total!==1?"s":"" ?></span>
  </form>

  <table class="tbl">
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php $e=true; while($u=mysqli_fetch_assoc($users)): $e=false; ?>
    <tr>
      <td class="fw"><?= h($u["full_name"]) ?></td>
      <td class="mono muted"><?= h($u["email"]) ?></td>
      <td><span class="badge <?= $u["role"]==="store_owner"?"b-blue":"b-gray" ?>"><?= $u["role"]==="store_owner"?"Owner":"Employee" ?></span></td>
      <td><span class="badge <?= $u["is_active"]?"b-green":"b-red" ?>"><?= $u["is_active"]?"Active":"Inactive" ?></span></td>
      <td class="muted" style="font-size:11px"><?= date("d M Y",strtotime($u["created_at"])) ?></td>
      <td>
        <div style="display:flex;gap:5px;align-items:center">
          <a href="view.php?id=<?= $u["id"] ?>" class="icon-btn" title="View">&#128065;</a>
          <?php if(isOwner()): ?>
          <a href="edit.php?id=<?= $u["id"] ?>" class="icon-btn" title="Edit">&#9998;</a>
          <?php if($u["is_active"] && $u["id"] != $_SESSION["uid"]): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="uid" value="<?= $u["id"] ?>">
            <button name="deactivate" class="icon-btn del" title="Deactivate"
                    onclick="return confirm('Deactivate <?= h(addslashes($u["full_name"])) ?>?')">&#128683;</button>
          </form>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endwhile; if($e): ?>
    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--t3)">No users found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
