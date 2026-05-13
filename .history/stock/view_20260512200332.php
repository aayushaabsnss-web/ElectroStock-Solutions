<?php
/**
 * stock/view.php — View Product Stock Detail (Presentation Layer)
 * Shows stock info for a product and its full transaction history.
 */
$t = "Stock Detail"; $a = "stock";
require_once "../includes/header.php";

$id = (int)($_GET["id"] ?? 0);
$p  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM products WHERE id=$id AND is_active=1"));
if(!$p){ flash("error","Product not found."); header("Location: index.php"); exit; }

$txns = mysqli_query($conn,
    "SELECT sm.*, u.full_name dby FROM stock_movements sm
     JOIN users u ON u.id=sm.moved_by
     WHERE sm.product_id=$id
     ORDER BY sm.created_at DESC LIMIT 20");

$st = $p["quantity"]==0?"Out of Stock":($p["quantity"]<=$p["min_qty"]?"Low Stock":"In Stock");
$bl = ["In Stock"=>"b-green","Low Stock"=>"b-amber","Out of Stock"=>"b-red"];
?>
<div class="page-hdr">
  <a href="index.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1><?= h($p["name"]) ?></h1>
  <a href="add.php?pid=<?= $id ?>" class="btn btn-primary">+ Add Transaction</a>
  <?php if(isOwner()): ?>
  <a href="edit.php?id=<?= $id ?>" class="btn btn-outline">Edit stock</a>
  <?php endif; ?>
</div>
<div class="g2" style="margin-bottom:16px">
  <div class="card">
    <div class="card-hdr"><span class="card-title">Stock information</span></div>
    <div class="card-body">
    <table style="width:100%;border-collapse:collapse;font-size:12px">
      <?php foreach([
        "Product"        => $p["name"],
        "SKU"            => $p["sku"],
        "Category"       => $p["category"],
        "Current qty"    => $p["quantity"]." units",
        "Min stock level"=> $p["min_qty"]." units",
        "Status"         => $st,
      ] as $k=>$v): ?>
      <tr>
        <td style="padding:8px 0;color:var(--t2);width:45%;border-bottom:0.5px solid var(--b)"><?= $k ?></td>
        <td style="padding:8px 0;font-weight:500;border-bottom:0.5px solid var(--b)"><?= h($v) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>
  </div>
  <div class="card">
    <div class="card-hdr"><span class="card-title">Stock level</span></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
      <div style="padding:14px;background:var(--bg3);border-radius:8px;text-align:center">
        <div style="font-size:42px;font-weight:700;font-family:var(--mono)" class="<?= ["In Stock"=>"c-green","Low Stock"=>"c-amber","Out of Stock"=>"c-red"][$st] ?>"><?= $p["quantity"] ?></div>
        <div style="font-size:12px;color:var(--t2);margin-top:4px">units in stock</div>
      </div>
      <span class="badge <?= $bl[$st] ?>" style="font-size:13px;padding:6px 14px;text-align:center"><?= $st ?></span>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title">Recent transactions</span></div>
  <table class="tbl">
    <thead><tr><th>Type</th><th>Qty change</th><th>Date</th><th>By</th><th>Notes</th><?php if(isOwner()): ?><th>Actions</th><?php endif; ?></tr></thead>
    <tbody>
    <?php $e=true; while($tx=mysqli_fetch_assoc($txns)): $e=false;
      $tc=["IN"=>"b-green","OUT"=>"b-red","ADJUSTMENT"=>"b-amber"];
      $sign=$tx["quantity"]>0?"+":"";
    ?>
    <tr>
      <td><span class="badge <?= $tc[$tx["type"]] ?>"><?= $tx["type"] ?></span></td>
      <td class="mono fw"><?= $sign.$tx["quantity"] ?></td>
      <td class="muted"><?= date("d M Y H:i",strtotime($tx["created_at"])) ?></td>
      <td><?= h($tx["dby"]) ?></td>
      <td class="muted"><?= h($tx["notes"]??"—") ?></td>
      <?php if(isOwner()): ?>
      <td><div style="display:flex;gap:5px">
        <a href="edit_tx.php?id=<?= $tx["id"] ?>&pid=<?= $id ?>" class="icon-btn" title="Edit">&#9998;</a>
        <a href="delete_tx.php?id=<?= $tx["id"] ?>&pid=<?= $id ?>" class="icon-btn del" title="Delete"
           onclick="return confirm('Delete this transaction?')">&#128465;</a>
      </div></td>
      <?php endif; ?>
    </tr>
    <?php endwhile; if($e): ?>
    <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--t3)">No transactions yet.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
