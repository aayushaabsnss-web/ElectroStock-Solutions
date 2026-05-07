<?php
/**
 * auth/search.php — Search and Filter Users (Presentation Layer)
 * Search users by name or email. Filter by role or status.
 * Access: Store Owner only.
 */
$t = "Search Users"; $a = "users";
require_once "../includes/header.php";
requireOwner();

$q      = trim($_GET["q"]      ?? "");
$role   = trim($_GET["role"]   ?? "");
$status = trim($_GET["status"] ?? "");

// Build query with optional filters
$where = ["1=1"]; $params = []; $types = "";
if ($q) {
    $where[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%"; $types .= "ss";
}
if ($role)   { $where[] = "role=?";      $params[] = $role;   $types .= "s"; }
if ($status === "active")   $where[] = "is_active=1";
if ($status === "inactive") $where[] = "is_active=0";

$sql = "SELECT * FROM users WHERE ".implode(" AND ",$where)." ORDER BY created_at DESC";
if ($params) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $res = mysqli_query($conn, $sql);
}
$n = mysqli_num_rows($res);
?>
<div class="page-hdr"><h1>Search Users</h1></div>
<div class="card" style="margin-bottom:16px">
  <div class="card-body">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
    <input type="text" name="q" class="fc" placeholder="Search name or email…" value="<?= h($q) ?>" style="flex:1;min-width:180px">
    <select name="role" class="fc" style="width:160px">
      <option value="">All roles</option>
      <option value="store_owner" <?= $role==="store_owner"?"selected":"" ?>>Store Owner</option>
      <option value="employee"    <?= $role==="employee"?"selected":"" ?>>Employee</option>
    </select>
    <select name="status" class="fc" style="width:150px">
      <option value="">All statuses</option>
      <option value="active"   <?= $status==="active"?"selected":"" ?>>Active</option>
      <option value="inactive" <?= $status==="inactive"?"selected":"" ?>>Inactive</option>
    </select>
    <button class="btn btn-primary">Search</button>
    <?php if($q||$role||$status): ?><a href="search.php" class="btn btn-outline">Clear</a><?php endif; ?>
  </form>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title"><?= $n ?> user<?= $n!==1?"s":"" ?> found</span></div>
  <table class="tbl">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php $e=true; while($u=mysqli_fetch_assoc($res)): $e=false; ?>
    <tr>
      <td class="fw"><?= h($u["full_name"]) ?></td>
      <td class="mono muted"><?= h($u["email"]) ?></td>
      <td><span class="badge <?= $u["role"]==="store_owner"?"b-blue":"b-gray" ?>"><?= $u["role"]==="store_owner"?"Owner":"Employee" ?></span></td>
      <td><span class="badge <?= $u["is_active"]?"b-green":"b-red" ?>"><?= $u["is_active"]?"Active":"Inactive" ?></span></td>
      <td class="muted" style="font-size:11px"><?= date("d M Y",strtotime($u["created_at"])) ?></td>
      <td><div style="display:flex;gap:5px">
        <a href="view.php?id=<?= $u["id"] ?>" class="icon-btn" title="View">&#128065;</a>
        <a href="edit.php?id=<?= $u["id"] ?>" class="icon-btn" title="Edit">&#9998;</a>
      </div></td>
    </tr>
    <?php endwhile; if($e): ?>
    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--t3)">No users found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>