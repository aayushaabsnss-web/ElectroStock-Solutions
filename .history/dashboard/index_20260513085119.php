<?php
/**
 * dashboard/index.php — Main Dashboard
 * Shows stats, active orders, alerts, products and stock summary.
 */
require_once "../config/db.php";
require_once "../auth/session.php";
require_once "../classes/Alert.php";
requireLogin();

$t = "Dashboard"; $a = "dash";
require_once "../includes/header.php";
include  "../includes/flash.php";

// Stat cards
$total = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE is_active=1"))['c'] ?? 0);
$in    = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE is_active=1 AND quantity>min_qty"))['c'] ?? 0);
$low   = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE is_active=1 AND quantity<=min_qty"))['c'] ?? 0);
$pend  = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders WHERE status='pending'"))['c'] ?? 0);

// Active orders
$orders = mysqli_query($conn,
    "SELECT o.*, u.full_name cby,
     (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) items
     FROM orders o JOIN users u ON u.id=o.created_by
     WHERE o.status IN ('pending','processing')
     ORDER BY o.created_at DESC LIMIT 5");

// Alerts
$alertObj = new Alert($conn);
$alerts   = $alertObj->getActive();

// Recent products
$products = mysqli_query($conn,
    "SELECT id,name,sku,category,price FROM products
     WHERE is_active=1 ORDER BY created_at DESC LIMIT 5");

// Low stock items
$stock = mysqli_query($conn,
    "SELECT id,name,sku,quantity,min_qty FROM products
     WHERE is_active=1 ORDER BY quantity ASC LIMIT 5");

$sc = ["pending"=>"b-amber","processing"=>"b-blue","completed"=>"b-green","cancelled"=>"b-gray"];
?>

<?php if($low > 0): ?>
<div class="alert alert-warning">&#9888; <strong><?= $low ?> product<?= $low>1?"s":"" ?></strong> at or below minimum stock &mdash; <a href="<?= BASE ?>monitoring/index.php" style="font-weight:600;color:inherit">View alerts &rarr;</a></div>
<?php endif; ?>

<!-- Stat cards -->
<div class="stats-grid">
  <div class="sc"><div class="sl">Total Products</div><div class="sv c-blue"><?= $total ?></div><div class="sn">Active in catalogue</div></div>
  <div class="sc"><div class="sl">In Stock</div><div class="sv c-green"><?= $in ?></div><div class="sn">Healthy levels</div></div>
  <div class="sc"><div class="sl">Low Stock</div><div class="sv c-amber"><?= $low ?></div><div class="sn">Need attention</div></div>
  <div class="sc"><div class="sl">Pending Orders</div><div class="sv c-blue"><?= $pend ?></div><div class="sn">Awaiting fulfilment</div></div>
</div>

<!-- Row 2: Active orders + Alerts -->
<div class="g2" style="margin-bottom:16px">

  <!-- Active orders -->
  <div class="card">
    <div class="card-hdr">
      <span class="card-title">Active orders</span>
      <a href="<?= BASE ?>orders/index.php" class="btn btn-outline btn-sm">View more &rarr;</a>
    </div>
    <table class="tbl">
      <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>
      <?php $e=true; while($o=mysqli_fetch_assoc($orders)): $e=false; ?>
      <tr>
        <td class="fw mono" style="font-size:11px"><?= h($o["order_number"]) ?></td>
        <td><?= h($o["customer"]) ?></td>
        <td class="mono">$<?= number_format($o["total"],2) ?></td>
        <td><span class="badge <?= $sc[$o["status"]] ?>"><?= ucfirst($o["status"]) ?></span></td>
      </tr>
      <?php endwhile; if($e): ?>
      <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--t3)">No active orders.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Active alerts -->
  <div class="card">
    <div class="card-hdr">
      <span class="card-title">Active alerts</span>
      <a href="<?= BASE ?>monitoring/index.php" class="btn btn-outline btn-sm">View more &rarr;</a>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
    <?php $any=false; foreach(array_slice($alerts,0,5) as $al): $any=true; ?>
    <div style="display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--bg3);border-radius:8px;border-left:3px solid var(--amber)">
      <div style="flex:1">
        <div style="font-size:12px;font-weight:500"><?= h($al["product_name"]) ?></div>
        <div style="font-size:10px;color:var(--t2)"><?= $al["current_qty"] ?> left &middot; min <?= $al["threshold"] ?></div>
      </div>
      <span class="badge b-red">-<?= max(0,$al["threshold"]-$al["current_qty"]) ?></span>
    </div>
    <?php endforeach; if(!$any): ?>
    <div style="text-align:center;padding:24px;color:var(--t3);font-size:12px">&#10003; No active alerts</div>
    <?php endif; ?>
    </div>
  </div>
</div>

<!-- Row 3: Recent products + Stock levels -->
<div class="g2">

  <!-- Recent products -->
  <div class="card">
    <div class="card-hdr">
      <span class="card-title">Recent products</span>
      <a href="<?= BASE ?>products/index.php" class="btn btn-outline btn-sm">View more &rarr;</a>
    </div>
    <table class="tbl">
      <thead><tr><th>Product</th><th>Category</th><th>Price</th></tr></thead>
      <tbody>
      <?php $e=true; while($p=mysqli_fetch_assoc($products)): $e=false; ?>
      <tr>
        <td class="fw"><?= h($p["name"]) ?></td>
        <td><span class="badge b-blue"><?= h($p["category"]) ?></span></td>
        <td class="mono">$<?= number_format($p["price"],2) ?></td>
      </tr>
      <?php endwhile; if($e): ?>
      <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--t3)">No products.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Stock levels -->
  <div class="card">
    <div class="card-hdr">
      <span class="card-title">Stock levels</span>
      <a href="<?= BASE ?>stock/index.php" class="btn btn-outline btn-sm">View more &rarr;</a>
    </div>
    <table class="tbl">
      <thead><tr><th>Product</th><th>Qty</th><th>Status</th></tr></thead>
      <tbody>
      <?php $e=true; while($p=mysqli_fetch_assoc($stock)): $e=false;
        $st = $p["quantity"]==0?"Out of Stock":($p["quantity"]<=$p["min_qty"]?"Low Stock":"In Stock");
        $bl = ["In Stock"=>"b-green","Low Stock"=>"b-amber","Out of Stock"=>"b-red"];
        $ql = ["In Stock"=>"c-green","Low Stock"=>"c-amber","Out of Stock"=>"c-red"];
      ?>
      <tr>
        <td class="fw"><?= h($p["name"]) ?></td>
        <td class="mono fw <?= $ql[$st] ?>"><?= $p["quantity"] ?></td>
        <td><span class="badge <?= $bl[$st] ?>"><?= $st ?></span></td>
      </tr>
      <?php endwhile; if($e): ?>
      <tr><td colspan="3" style="text-align:center;padding:20px;color:var(--t3)">No stock data.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
<?php require_once "../includes/footer.php"; ?>
