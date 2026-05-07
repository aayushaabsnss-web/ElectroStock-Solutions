<?php
/**
 * dashboard/index.php — Main Dashboard (Presentation Layer)
 * Displays live inventory stats, alerts, recent products and transactions.
 * Uses the Report and Alert classes from the middle layer.
 */
$t = "Dashboard"; $a = "dash";
require_once "../includes/header.php";
require_once "../classes/Report.php";
require_once "../classes/Alert.php";

$reportObj = new Report($conn);  // Middle layer: Report class
$alertObj  = new Alert($conn);   // Middle layer: Alert class

// Fetch summary stats via sp_getDashboardStats stored procedure
$stats   = $reportObj->getDashboardStats();

// Fetch recent products for overview table
$recent  = mysqli_query($conn, "SELECT * FROM products WHERE is_active=1 ORDER BY created_at DESC LIMIT 6");

// Fetch recent stock transactions for activity feed
$txns    = mysqli_query($conn, "SELECT sm.*,p.name pname,u.full_name dby FROM stock_movements sm JOIN products p ON p.id=sm.product_id JOIN users u ON u.id=sm.moved_by ORDER BY sm.created_at DESC LIMIT 6");

// Fetch active alerts using Alert class
$alerts  = $alertObj->getActive();
?>
<?php include "../includes/flash.php"; ?>
<?php if(($stats["low_stock"]+$stats["out_of_stock"])>0): ?>
<div class="alert alert-warning">&#9888; <strong><?= $stats["low_stock"]+$stats["out_of_stock"] ?> products</strong> need attention &mdash; <a href="<?= BASE ?>monitoring/index.php" style="font-weight:600;color:inherit">View alerts &rarr;</a></div>
<?php endif; ?>
<div class="stats-grid">
  <div class="sc"><div class="sl">Total Products</div><div class="sv c-blue"><?= $stats["total_products"] ?></div><div class="sn">Active in catalogue</div></div>
  <div class="sc"><div class="sl">In Stock</div><div class="sv c-green"><?= $stats["in_stock"] ?></div><div class="sn">Healthy levels</div></div>
  <div class="sc"><div class="sl">Low / Out of Stock</div><div class="sv c-amber"><?= $stats["low_stock"]+$stats["out_of_stock"] ?></div><div class="sn"><?= $stats["low_stock"] ?> low &middot; <?= $stats["out_of_stock"] ?> out</div></div>
  <div class="sc"><div class="sl">Inventory Value</div><div class="sv c-blue">$<?= number_format($stats["total_inventory_value"],0) ?></div><div class="sn">Total stock value</div></div>
</div>
<div class="g2" style="margin-bottom:16px">
  <div class="card">
    <div class="card-hdr"><span class="card-title">Recent products</span><a href="<?= BASE ?>products/index.php" class="btn btn-outline btn-sm">View all &rarr;</a></div>
    <table class="tbl"><thead><tr><th>Product</th><th>Cat</th><th>Stock</th><th>Status</th><th>Price</th></tr></thead><tbody>
    <?php foreach($recent as $p):
      $st=$p["quantity"]==0?"out":($p["quantity"]<=$p["min_qty"]?"low":"in");
      $bl=["in"=>"b-green","low"=>"b-amber","out"=>"b-red"];
      $ll=["in"=>"In Stock","low"=>"Low Stock","out"=>"Out of Stock"];
      $fc=["in"=>"var(--green)","low"=>"var(--amber)","out"=>"var(--red)"];
      $pct=min(100,$p["min_qty"]>0?round($p["quantity"]/($p["min_qty"]*2)*100):100);
    ?>
    <tr><td class="fw"><?= h($p["name"]) ?></td><td><span class="badge b-blue"><?= h($p["category"]) ?></span></td>
    <td><div class="sbar"><div class="sbg"><div class="sbf" style="width:<?= $pct ?>%;background:<?= $fc[$st] ?>"></div></div><span class="mono"><?= $p["quantity"] ?></span></div></td>
    <td><span class="badge <?= $bl[$st] ?>"><?= $ll[$st] ?></span></td><td class="mono">$<?= number_format($p["price"],2) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="card">
    <div class="card-hdr"><span class="card-title">Active alerts</span><a href="<?= BASE ?>monitoring/index.php" class="btn btn-outline btn-sm">View all &rarr;</a></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
    <?php $any=false; foreach($alerts as $al):$any=true; ?>
    <div style="display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--bg3);border-radius:8px;border-left:3px solid var(--amber)">
      <div style="flex:1"><div style="font-size:12px;font-weight:500"><?= h($al["product_name"]) ?></div>
      <div style="font-size:10px;color:var(--t2)"><?= $al["current_qty"] ?> left &middot; min <?= $al["threshold"] ?></div></div>
      <?php if(isOwner()): ?><form method="POST" action="<?= BASE ?>monitoring/index.php"><input type="hidden" name="resolve_id" value="<?= $al["id"] ?>"><button class="btn btn-outline btn-sm">Resolve</button></form><?php endif; ?>
    </div>
    <?php endforeach; if(!$any): ?><div style="text-align:center;padding:24px;color:var(--t3);font-size:12px">&#10003; No active alerts</div><?php endif; ?>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title">Recent stock transactions</span><a href="<?= BASE ?>stock/search.php" class="btn btn-outline btn-sm">Full history &rarr;</a></div>
  <table class="tbl"><thead><tr><th>Product</th><th>Type</th><th>Qty</th><th>Date</th><th>By</th><th>Notes</th></tr></thead><tbody>
  <?php while($tx=mysqli_fetch_assoc($txns)):
    $tc=["IN"=>"b-green","OUT"=>"b-red","ADJUSTMENT"=>"b-amber"]; $sign=$tx["quantity"]>0?"+":"";
  ?>
  <tr><td class="fw"><?= h($tx["pname"]) ?></td><td><span class="badge <?= $tc[$tx["type"]] ?>"><?= $tx["type"] ?></span></td>
  <td class="mono"><?= $sign.$tx["quantity"] ?></td><td class="muted" style="font-size:11px"><?= date("d M Y H:i",strtotime($tx["created_at"])) ?></td>
  <td><?= h($tx["dby"]) ?></td><td class="muted"><?= h($tx["notes"]??"—") ?></td></tr>
  <?php endwhile; ?>
  </tbody></table>
</div>
<?php require_once "../includes/footer.php"; ?>
