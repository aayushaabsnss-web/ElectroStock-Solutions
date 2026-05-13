<?php
/**
 * monitoring/index.php — Inventory Alerts (Presentation Layer)
 * Uses Alert objects — data accessed via getter methods.
 */
require_once "../config/db.php";
require_once "../auth/session.php";
require_once "../classes/Alert.php";

// POST handling before HTML output
if($_SERVER["REQUEST_METHOD"]==="POST" && isset($_POST["resolve_id"])){
    requireOwner();
    Alert::resolve($conn, (int)$_POST["resolve_id"], $_SESSION["uid"]);
    flash("success","Alert resolved."); header("Location: index.php"); exit;
}
if($_SERVER["REQUEST_METHOD"]==="POST" && isset($_POST["add_threshold"])){
    requireOwner();
    $pid=(int)$_POST["product_id"]; $thr=(int)$_POST["threshold"];
    if($pid && $thr>0){ Alert::setThreshold($conn,$pid,$thr); flash("success","Threshold set."); header("Location: index.php"); exit; }
}

$t = "Inventory Alerts"; $a = "monitoring";
require_once "../includes/header.php";
include  "../includes/flash.php";

// Fetch Alert objects
$active   = Alert::getActive($conn);
$resolved = Alert::getResolved($conn, 10);
$an       = count($active);
$prods    = mysqli_query($conn,"SELECT id,name FROM products WHERE is_active=1 ORDER BY name");
?>
<div class="page-hdr">
  <h1>Inventory Alerts <span style="font-size:14px;color:var(--t2);font-weight:400">(<?= $an ?> active)</span></h1>
</div>
<?php if(isOwner()): ?>
<div class="card" style="margin-bottom:16px">
  <div class="card-hdr"><span class="card-title">Set alert threshold</span></div>
  <div class="card-body">
  <form method="POST" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
    <div class="fg" style="margin:0;flex:1;min-width:200px"><label>Product</label>
      <select name="product_id" class="fc" required><option value="">Select product...</option>
        <?php while($p=mysqli_fetch_assoc($prods)): ?><option value="<?= $p["id"] ?>"><?= h($p["name"]) ?></option><?php endwhile; ?>
      </select></div>
    <div class="fg" style="margin:0;width:180px"><label>Min stock level</label>
      <input type="number" name="threshold" class="fc" min="1" placeholder="5" required></div>
    <button name="add_threshold" class="btn btn-primary">Set threshold</button>
  </form>
  </div>
</div>
<?php endif; ?>
<div class="card" style="margin-bottom:16px">
  <div class="card-hdr"><span class="card-title">Active alerts</span></div>
  <table class="tbl">
    <thead><tr><th>Product</th><th>SKU</th><th>Current qty</th><th>Threshold</th><th>Shortfall</th><th>Triggered</th><?php if(isOwner()): ?><th>Action</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if(empty($active)): ?>
    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--t3)">&#10003; No active alerts.</td></tr>
    <?php else: ?>
    <?php foreach($active as $alert): // Each $alert is an Alert object ?>
    <tr>
      <td class="fw"><?= h($alert->getProductName()) ?></td>
      <td class="mono muted"><?= h($alert->getSku()) ?></td>
      <td class="mono <?= $alert->getQtyColor() ?>"><?= $alert->getCurrentQty() ?></td>
      <td class="mono"><?= $alert->getThreshold() ?></td>
      <td><span class="badge b-red">-<?= $alert->getShortfall() ?></span></td>
      <td class="muted" style="font-size:11px"><?= $alert->getFormattedAlertedAt() ?></td>
      <?php if(isOwner()): ?>
      <td><form method="POST"><input type="hidden" name="resolve_id" value="<?= $alert->getId() ?>">
        <button class="btn btn-success btn-sm">&#10003; Resolve</button></form></td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title">Recently resolved</span></div>
  <table class="tbl">
    <thead><tr><th>Product</th><th>Resolved</th><th>Resolved by</th></tr></thead>
    <tbody>
    <?php if(empty($resolved)): ?>
    <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--t3)">No resolved alerts yet.</td></tr>
    <?php else: ?>
    <?php foreach($resolved as $alert): // Each $alert is an Alert object ?>
    <tr>
      <td class="fw"><?= h($alert->getProductName()) ?></td>
      <td class="muted"><?= $alert->getFormattedResolvedAt() ?></td>
      <td><?= h($alert->getResolvedBy() ?: "—") ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
