<?php
/**
 * reports/index.php — Inventory Reports (Bonus — Presentation Layer)
 * Generates three business intelligence reports:
 *   1. Low Stock Report  — calls sp_getLowStockReport
 *   2. Stock Value Report — calls sp_getStockValueReport
 *   3. Order Summary     — calls sp_getOrderSummaryReport
 * Uses the Report class from the middle layer.
 * Access: Store Owner only.
 */
$t = 'Reports'; $a = 'reports';
require_once '../includes/header.php';
require_once '../classes/Report.php';
requireOwner(); // Reports are restricted to Store Owner

$reportObj = new Report($conn); // Middle layer: Report class

// Determine which report to show (defaults to low_stock)
$type = $_GET['type'] ?? 'low_stock';
?>

<?php include '../includes/flash.php'; ?>

<div class="page-hdr"><h1>Inventory Reports</h1></div>

<!-- Report type selector tabs -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
  <?php
  $tabs = [
    'low_stock'     => 'Low Stock Report',
    'stock_value'   => 'Stock Value Report',
    'order_summary' => 'Order Summary',
  ];
  foreach ($tabs as $k => $v):
  ?>
  <a href="index.php?type=<?= $k ?>"
     class="btn <?= $type === $k ? 'btn-primary' : 'btn-outline' ?>">
    <?= $v ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($type === 'low_stock'): ?>
<!-- ── LOW STOCK REPORT ──────────────────────────── -->
<div class="card">
  <div class="card-hdr">
    <span class="card-title">Low Stock Report</span>
    <span class="muted" style="font-size:11px">Products at or below minimum stock threshold</span>
  </div>
  <table class="tbl">
    <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Current qty</th><th>Min level</th><th>Shortfall</th><th>Supplier</th></tr></thead>
    <tbody>
    <?php
    // Fetch low stock data via Report class (calls sp_getLowStockReport)
    $res = $reportObj->getLowStock();
    $e = true;
    foreach ($res as $row):
      $e = false;
    ?>
    <tr>
      <td class="fw"><?= h($row['name']) ?></td>
      <td class="mono muted"><?= h($row['sku']) ?></td>
      <td><span class="badge b-blue"><?= h($row['category']) ?></span></td>
      <td class="mono c-amber"><?= $row['quantity'] ?></td>
      <td class="mono"><?= $row['min_qty'] ?></td>
      <td><span class="badge b-red">-<?= $row['shortfall'] ?></span></td>
      <td class="muted"><?= h($row['supplier']) ?></td>
    </tr>
    <?php endforeach; if ($e): ?>
    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--t3)">&#10003; All products are above minimum stock levels.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php elseif ($type === 'stock_value'): ?>
<!-- ── STOCK VALUE REPORT ─────────────────────────── -->
<?php
// Fetch value data via Report class (calls sp_getStockValueReport)
$res   = $reportObj->getStockValue();
$grand = 0;
$rows  = [];
while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; $grand += $row['total_value']; }
?>
<div class="card" style="margin-bottom:16px">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:16px">
    <div class="sc"><div class="sl">Total inventory value</div><div class="sv c-blue">$<?= number_format($grand, 2) ?></div></div>
    <div class="sc"><div class="sl">Total product lines</div><div class="sv c-green"><?= count($rows) ?></div></div>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title">Stock Value by Product</span></div>
  <table class="tbl">
    <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Unit price</th><th>Quantity</th><th>Total value</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
    <tr>
      <td class="fw"><?= h($row['name']) ?></td>
      <td class="mono muted"><?= h($row['sku']) ?></td>
      <td><span class="badge b-blue"><?= h($row['category']) ?></span></td>
      <td class="mono">$<?= number_format($row['price'], 2) ?></td>
      <td class="mono"><?= $row['quantity'] ?></td>
      <td class="mono fw">$<?= number_format($row['total_value'], 2) ?></td>
    </tr>
    <?php endforeach; if (empty($rows)): ?>
    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--t3)">No products found.</td></tr>
    <?php endif; ?>
    <tr style="border-top:2px solid var(--b2);background:var(--bg3)">
      <td colspan="5" style="padding:10px 14px;font-weight:600;text-align:right">Grand total</td>
      <td class="mono fw" style="padding:10px 14px">$<?= number_format($grand, 2) ?></td>
    </tr>
    </tbody>
  </table>
</div>

<?php elseif ($type === 'order_summary'): ?>
<!-- ── ORDER SUMMARY REPORT ───────────────────────── -->
<div class="card">
  <div class="card-hdr"><span class="card-title">Order Summary by Status</span></div>
  <table class="tbl">
    <thead><tr><th>Status</th><th>Order count</th><th>Total revenue</th></tr></thead>
    <tbody>
    <?php
    // Fetch order summary via Report class (calls sp_getOrderSummaryReport)
    $res   = $reportObj->getOrderSummary();
    $sc    = ['pending'=>'b-amber','processing'=>'b-blue','completed'=>'b-green','cancelled'=>'b-gray'];
    $e = true;
    foreach ($res as $row):
      $e = false;
    ?>
    <tr>
      <td><span class="badge <?= $sc[$row['status']] ?>"><?= ucfirst($row['status']) ?></span></td>
      <td class="mono"><?= $row['order_count'] ?></td>
      <td class="mono fw">$<?= number_format($row['total_revenue'], 2) ?></td>
    </tr>
    <?php endforeach; if ($e): ?>
    <tr><td colspan="3" style="text-align:center;padding:30px;color:var(--t3)">No orders found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
