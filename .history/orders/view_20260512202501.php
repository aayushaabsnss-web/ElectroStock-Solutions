<?php
/**
 * orders/view.php — View Order Detail (Presentation Layer)
 */
$t = "Order Detail"; $a = "orders";
require_once "../includes/header.php";
include  "../includes/flash.php";

$id = (int)($_GET["id"] ?? 0);
$o  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT o.*, u.full_name cby FROM orders o
     JOIN users u ON u.id=o.created_by WHERE o.id=$id"));
if(!$o){ flash("error","Order not found."); header("Location: index.php"); exit; }

$items = mysqli_query($conn,
    "SELECT oi.*, p.name pname, p.sku FROM order_items oi
     JOIN products p ON p.id=oi.product_id WHERE oi.order_id=$id");

$sc = ["pending"=>"b-amber","processing"=>"b-blue","completed"=>"b-green","cancelled"=>"b-gray"];
?>
<div class="page-hdr">
  <a href="index.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1>Order <?= h($o["order_number"]) ?></h1>
  <span class="badge <?= $sc[$o["status"]] ?>" style="font-size:12px"><?= ucfirst($o["status"]) ?></span>
  <?php if($o["status"]!=="completed" && $o["status"]!=="cancelled"): ?>
  <a href="edit.php?id=<?= $id ?>" class="btn btn-outline">Edit order</a>
  <?php endif; ?>
</div>
<div class="g2" style="margin-bottom:16px">
  <div class="card">
    <div class="card-hdr"><span class="card-title">Order info</span></div>
    <div class="card-body">
    <table style="width:100%;border-collapse:collapse;font-size:12px">
      <?php foreach([
        "Order #"    => $o["order_number"],
        "Customer"   => $o["customer"],
        "Total"      => "$".number_format($o["total"],2),
        "Status"     => ucfirst($o["status"]),
        "Created by" => $o["cby"],
        "Date"       => date("d M Y H:i",strtotime($o["created_at"])),
        "Notes"      => ($o["notes"] ?? "—"),
      ] as $k=>$v): ?>
      <tr>
        <td style="padding:8px 0;color:var(--t2);width:40%;border-bottom:0.5px solid var(--b)"><?= $k ?></td>
        <td style="padding:8px 0;font-weight:500;border-bottom:0.5px solid var(--b)"><?= h((string)$v) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>
  </div>
  <div class="card">
    <div class="card-hdr"><span class="card-title">Order summary</span></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
      <div style="padding:14px;background:var(--bg3);border-radius:8px;text-align:center">
        <div style="font-size:36px;font-weight:700;font-family:var(--mono);color:var(--blue)">$<?= number_format($o["total"],2) ?></div>
        <div style="font-size:12px;color:var(--t2);margin-top:4px">order total</div>
      </div>
      <span class="badge <?= $sc[$o["status"]] ?>" style="font-size:13px;padding:6px 14px;text-align:center"><?= ucfirst($o["status"]) ?></span>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title">Order items</span></div>
  <table class="tbl">
    <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit price</th><th>Line total</th></tr></thead>
    <tbody>
    <?php $grand=0; while($it=mysqli_fetch_assoc($items)):
      $lt=$it["quantity"]*$it["unit_price"]; $grand+=$lt; ?>
    <tr>
      <td class="fw"><?= h($it["pname"]) ?></td>
      <td class="mono muted"><?= h($it["sku"]) ?></td>
      <td class="mono"><?= $it["quantity"] ?></td>
      <td class="mono">$<?= number_format($it["unit_price"],2) ?></td>
      <td class="mono fw">$<?= number_format($lt,2) ?></td>
    </tr>
    <?php endwhile; ?>
    <tr style="border-top:2px solid var(--b2)">
      <td colspan="4" style="text-align:right;font-weight:600;padding:10px 14px">Total</td>
      <td class="mono fw" style="padding:10px 14px">$<?= number_format($grand,2) ?></td>
    </tr>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
