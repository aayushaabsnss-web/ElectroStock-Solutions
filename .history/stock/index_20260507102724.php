<?php
/**
 * stock/index.php — Stock Management (Presentation Layer)
 * Handles stock IN/OUT/ADJUSTMENT transactions.
 * Uses the Stock class which calls sp_addStockMovement stored procedure.
 * Auto-triggers low-stock alerts when quantity drops below threshold.
 * Access: All users for IN/OUT. Store Owner only for ADJUSTMENT.
 */
$t = "Stock Management"; $a = "stock";
require_once "../includes/header.php";
require_once "../classes/Stock.php";
include "../includes/flash.php";

$stockObj = new Stock($conn); // Middle layer: Stock class

if($_SERVER["REQUEST_METHOD"]==="POST"){
    // Validate input using Stock class validator
    $errors = $stockObj->validate($_POST, isOwner());
    if(!$errors){
        // Record movement via Stock class (calls sp_addStockMovement)
        [$ok, $err, $newQty] = $stockObj->addMovement(
            (int)$_POST["product_id"],
            $_POST["type"],
            (int)$_POST["quantity"],
            $_SESSION["uid"],
            trim($_POST["notes"] ?? "")
        );
        if($ok) {
            flash("success", "Stock updated. New quantity: $newQty units.");
        } else {
            flash("error", $err);
        }
    } else {
        flash("error", implode(" ", $errors));
    }
    header("Location: index.php"); exit;
}

// Get product list for the form dropdown
$products = $stockObj->getProductList();

// Get recent transactions for the activity panel
$txns = mysqli_query($conn,
    "SELECT sm.*,p.name pn,u.full_name dby
     FROM stock_movements sm
     JOIN products p ON p.id=sm.product_id
     JOIN users u ON u.id=sm.moved_by
     ORDER BY sm.created_at DESC LIMIT 20");
?>
<div class="page-hdr"><h1>Stock Management</h1><a href="search.php" class="btn btn-outline btn-sm">Full history &rarr;</a></div>
<div class="g2">
  <!-- Stock update form -->
  <div class="card">
    <div class="card-hdr"><span class="card-title">Record transaction</span></div>
    <div class="card-body">
    <form method="POST">
      <div class="fg"><label>Product *</label>
        <select name="product_id" class="fc" required>
          <option value="">Select product…</option>
          <?php while($p=mysqli_fetch_assoc($products)): ?>
          <option value="<?= $p["id"] ?>"><?= h($p["name"]) ?> (<?= $p["quantity"] ?> in stock)</option>
          <?php endwhile; ?>
        </select></div>
      <div class="form2">
        <div class="fg"><label>Type *</label>
          <select name="type" class="fc" required>
            <option value="IN">IN — Delivery received</option>
            <option value="OUT">OUT — Item sold</option>
            <?php if(isOwner()): // ADJUSTMENT restricted to Store Owner ?>
            <option value="ADJUSTMENT">ADJUSTMENT — Manual correction</option>
            <?php endif; ?>
          </select></div>
        <div class="fg"><label>Quantity *</label>
          <input type="number" name="quantity" min="1" class="fc" placeholder="0" required></div>
      </div>
      <div class="fg"><label>Notes (optional)</label>
        <input type="text" name="notes" class="fc" placeholder="e.g. Delivery batch #42"></div>
      <button class="btn btn-primary w100">Log transaction &rarr;</button>
    </form>
    </div>
  </div>
  <!-- Recent transactions panel -->
  <div class="card">
    <div class="card-hdr"><span class="card-title">Recent transactions</span></div>
    <table class="tbl">
      <thead><tr><th>Product</th><th>Type</th><th>Qty</th><th>Date</th><th>By</th></tr></thead>
      <tbody>
      <?php while($tx=mysqli_fetch_assoc($txns)):
        $tc=["IN"=>"b-green","OUT"=>"b-red","ADJUSTMENT"=>"b-amber"];
        $sign=$tx["quantity"]>0?"+":"";
      ?>
      <tr>
        <td class="fw" style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($tx["pn"]) ?></td>
        <td><span class="badge <?= $tc[$tx["type"]] ?>"><?= $tx["type"] ?></span></td>
        <td class="mono"><?= $sign.$tx["quantity"] ?></td>
        <td class="muted" style="font-size:11px"><?= date("d M H:i",strtotime($tx["created_at"])) ?></td>
        <td class="muted"><?= h($tx["dby"]) ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once "../includes/footer.php"; ?>