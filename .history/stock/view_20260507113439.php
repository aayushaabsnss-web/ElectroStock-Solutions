<?php
/**
 * stock/view.php — View Single Transaction Detail (Presentation Layer)
 * Shows full details of one stock movement record.
 * Access: All authenticated users.
 */
$t = "Transaction Detail"; $a = "stock";
require_once "../includes/header.php";

$id = (int)($_GET["id"] ?? 0);
$tx = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT sm.*,p.name pname,p.sku,p.category,u.full_name dby
     FROM stock_movements sm
     JOIN products p ON p.id=sm.product_id
     JOIN users u ON u.id=sm.moved_by
     WHERE sm.id=$id"));
if (!$tx) { flash("error","Transaction not found."); header("Location: search.php"); exit; }
$tc = ["IN"=>"b-green","OUT"=>"b-red","ADJUSTMENT"=>"b-amber"];
$sign = $tx["quantity"] > 0 ? "+" : "";
?>
<div class="page-hdr">
  <a href="search.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1>Transaction #<?= $id ?></h1>
  <?php if(isOwner()): ?>
  <a href="edit.php?id=<?= $id ?>" class="btn btn-outline">Edit notes</a>
  <a href="delete.php?id=<?= $id ?>" class="btn btn-danger"
     onclick="return confirm('Delete this transaction? The stock quantity will be reversed.')">Delete</a>
  <?php endif; ?>
</div>
<div class="card" style="max-width:560px">
  <div class="card-hdr">
    <span class="card-title">Transaction details</span>
    <span class="badge <?= $tc[$tx["type"]] ?>"><?= $tx["type"] ?></span>
  </div>
  <div class="card-body">
  <table style="width:100%;border-collapse:collapse;font-size:12px">
    <?php foreach([
      "Transaction ID" => "#".$id,
      "Product"        => $tx["pname"]." (".$tx["sku"].")",
      "Category"       => $tx["category"],
      "Type"           => $tx["type"],
      "Quantity change"=> $sign.$tx["quantity"]." units",
      "Date & time"    => date("d M Y H:i:s",strtotime($tx["created_at"])),
      "Recorded by"    => $tx["dby"],
      "Notes"          => ($tx["notes"] ?? "—"),
    ] as $k=>$v): ?>
    <tr>
      <td style="padding:9px 0;color:var(--t2);width:40%;border-bottom:0.5px solid var(--b)"><?= $k ?></td>
      <td style="padding:9px 0;font-weight:500;border-bottom:0.5px solid var(--b)"><?= h($v) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
</div>
<?php require_once "../includes/footer.php"; ?>