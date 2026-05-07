<?php
/**
 * monitoring/search.php — Search and Filter Alerts (Presentation Layer)
 * Search alerts by product name. Filter by alert status.
 * Access: All authenticated users.
 */
$t = "Search Alerts"; $a = "monitoring";
require_once "../includes/header.php";

$q      = trim($_GET["q"]      ?? "");
$status = trim($_GET["status"] ?? "");

// Build query with optional filters
$where = ["1=1"]; $params = []; $types = "";
if ($q) {
    $where[] = "p.name LIKE ?";
    $params[] = "%$q%"; $types .= "s";
}
if ($status) {
    $where[] = "m.alert_status=?";
    $params[] = $status; $types .= "s";
}
$sql = "SELECT m.*,p.name pname,p.quantity,p.sku,
               u.full_name rby
        FROM monitoring m
        JOIN products p ON p.id=m.product_id
        LEFT JOIN users u ON u.id=m.resolved_by
        WHERE ".implode(" AND ",$where)."
        ORDER BY m.alerted_at DESC";
if ($params) {
    $stmt = mysqli_prepare($conn,$sql);
    mysqli_stmt_bind_param($stmt,$types,...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $res = mysqli_query($conn,$sql);
}
$n = mysqli_num_rows($res);
?>
<div class="page-hdr"><h1>Search Alerts</h1></div>
<div class="card" style="margin-bottom:16px">
  <div class="card-body">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
    <input type="text" name="q" class="fc" placeholder="Search by product name…" value="<?= h($q) ?>" style="flex:1;min-width:180px">
    <select name="status" class="fc" style="width:160px">
      <option value="">All statuses</option>
      <option value="active"   <?= $status==="active"?"selected":"" ?>>Active</option>
      <option value="resolved" <?= $status==="resolved"?"selected":"" ?>>Resolved</option>
    </select>
    <button class="btn btn-primary">Search</button>
    <?php if($q||$status): ?><a href="search.php" class="btn btn-outline">Clear</a><?php endif; ?>
  </form>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title"><?= $n ?> alert<?= $n!==1?"s":"" ?> found</span></div>
  <table class="tbl">
    <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Threshold</th><th>Status</th><th>Triggered</th><th>Resolved by</th><th>Actions</th></tr></thead>
    <tbody>
    <?php $e=true; while($al=mysqli_fetch_assoc($res)): $e=false; ?>
    <tr>
      <td class="fw"><?= h($al["pname"]) ?></td>
      <td class="mono muted"><?= h($al["sku"]) ?></td>
      <td class="mono <?= $al["quantity"]==0?"c-red":"c-amber" ?>"><?= $al["quantity"] ?></td>
      <td class="mono"><?= $al["threshold"] ?></td>
      <td><span class="badge <?= $al["alert_status"]==="active"?"b-red":"b-green" ?>"><?= ucfirst($al["alert_status"]) ?></span></td>
      <td class="muted" style="font-size:11px"><?= date("d M Y",strtotime($al["alerted_at"])) ?></td>
      <td><?= h($al["rby"] ?? "—") ?></td>
      <td><div style="display:flex;gap:5px">
        <a href="view.php?id=<?= $al["id"] ?>" class="icon-btn" title="View">&#128065;</a>
        <?php if(isOwner() && $al["alert_status"]==="resolved"): ?>
        <a href="delete.php?id=<?= $al["id"] ?>" class="icon-btn del"
           onclick="return confirm('Delete this alert record?')" title="Delete">&#128465;</a>
        <?php endif; ?>
      </div></td>
    </tr>
    <?php endwhile; if($e): ?>
    <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--t3)">No alerts found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
