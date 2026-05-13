<?php
/**
 * orders/view.php — Order Detail (Presentation Layer)
 * Fetches Order and OrderItem objects.
 * HTML accesses data via getter methods.
 */
$t = "Order Detail"; $a = "orders";
require_once "../includes/header.php";
require_once "../classes/Order.php";
require_once "../classes/StockMovement.php";
include  "../includes/flash.php";

$id = (int)($_GET["id"] ?? 0);
// Fetch Order object
$order = Order::getById($conn, $id);
if(!$order){ flash("error","Order not found."); header("Location: index.php"); exit; }

// Fetch OrderItem objects
$items = Order::getItems($conn, $id);
?>
<div class="page-hdr">
  <a href="index.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1><?= h($order->getOrderNumber()) ?></h1>
  <span class="badge <?= $order->getStatusBadge() ?>" style="font-size:12px"><?= ucfirst($order->getStatus()) ?></span>
  <?php if($order->isEditable()): ?><a href="edit.php?id=<?= $id ?>" class="btn btn-outline">Edit order</a><?php endif; ?>
</div>
<div class="g2" style="margin-bottom:16px">
  <div class="card">
    <div class="card-hdr"><span class="card-title">Order information</span></div>
    <div class="card-body">
    <table style="width:100%;border-collapse:collapse;font-size:12px">
      <?php foreach([
        "Order #"    => $order->getOrderNumber(),
        "Customer"   => $order->getCustomer(),
        "Total"      => $order->getFormattedTotal(),
        "Status"     => ucfirst($order->getStatus()),
        "Created by" => $order->getCreatedBy(),
        "Date"       => $order->getFormattedDate(),
        "Notes"      => ($order->getNotes() ?: "—"),
      ] as $k=>$v): ?>
      <tr>
        <td style="padding:8px 0;color:var(--t2);width:40%;border-bottom:0.5px solid var(--b)"><?= $k ?></td>
        <td style="padding:8px 0;font-weight:500;border-bottom:0.5px solid var(--b)"><?= h($v) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>
  </div>
  <div class="card">
    <div class="card-hdr"><span class="card-title">Order total</span></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
      <div style="padding:14px;background:var(--bg3);border-radius:8px;text-align:center">
        <div style="font-size:36px;font-weight:700;font-family:var(--mono);color:var(--blue)"><?= $order->getFormattedTotal() ?></div>
        <div style="font-size:12px;color:var(--t2);margin-top:4px"><?= $order->getItemCount() ?> items</div>
      </div>
      <span class="badge <?= $order->getStatusBadge() ?>" style="font-size:13px;padding:6px 14px;text-align:center"><?= ucfirst($order->getStatus()) ?></span>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title">Order items</span></div>
  <table class="tbl">
    <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit price</th><th>Line total</th></tr></thead>
    <tbody>
    <?php $grand=0; foreach($items as $item): // Each $item is an OrderItem object $grand+=$item->getLineTotal(); ?>
    <tr>
      <td class="fw"><?= h($item->getProductName()) ?></td>
      <td class="mono muted"><?= h($item->getSku()) ?></td>
      <td class="mono"><?= $item->getQuantity() ?></td>
      <td class="mono"><?= $item->getFormattedUnitPrice() ?></td>
      <td class="mono fw"><?= $item->getFormattedLineTotal() ?></td>
    </tr>
    <?php endforeach; ?>
    <tr style="border-top:2px solid var(--b2)">
      <td colspan="4" style="text-align:right;font-weight:600;padding:10px 14px">Total</td>
      <td class="mono fw" style="padding:10px 14px">$<?= number_format($grand,2) ?></td>
    </tr>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
