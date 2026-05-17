<?php
/**
 * stock/add.php — Add Stock Transaction (Presentation Layer)
 * Uses Stock::validate() and Stock::add() static methods.
 */
$t = "Record Stock"; $a = "stock";
require_once "../includes/header.php";
require_once "../classes/Stock.php"; 
include  "../includes/flash.php";

$preselect = (int)($_GET["pid"] ?? 0);

if($_SERVER["REQUEST_METHOD"]==="POST"){
    $errors = Stock::validate($_POST, isOwner()); 
    if(!$errors){
        [$ok, $err, $newQty] = Stock::add($conn, 
            (int)$_POST["product_id"], $_POST["type"],
            (int)$_POST["quantity"], $_SESSION["uid"],
            trim($_POST["notes"] ?? ""));
        if($ok){ flash("success","Stock updated. New quantity: $newQty units."); header("Location: index.php"); exit; }
        else    flash("error", $err);
    } else { flash("error", implode(" ", $errors)); }
}

$productList = Stock::getProductList($conn);
?>
<div class="page-hdr">
  <a href="index.php" class="btn btn-outline btn-sm">&larr; Back to stock</a>
  <h1>Record Stock</h1>
</div>
<div class="card" style="max-width:620px">
  <div class="card-hdr"><span class="card-title">Record stock</span></div>
  <div class="card-body">
  <form method="POST">
    <div class="fg"><label>Product *</label>
      <select name="product_id" class="fc" required>
        <option value="">Select a product...</option>
        <?php foreach($productList as $p): ?>
        <option value="<?= $p["id"] ?>" <?= $preselect===$p["id"]?"selected":"" ?>>
          <?= h($p["name"]) ?> — <?= $p["quantity"] ?> in stock
        </option>
        <?php endforeach; ?>
      </select></div>
    <div class="form2">
      <div class="fg"><label>Type *</label>
        <select name="type" class="fc" required>
          <option value="IN">IN — Delivery received</option>
          <option value="OUT">OUT — Item sold / removed</option>
          <?php if(isOwner()): ?>
          <option value="ADJUSTMENT">ADJUSTMENT — Manual correction</option>
          <?php endif; ?>
        </select></div>
      <div class="fg"><label>Quantity *</label>
        <input type="number" name="quantity" min="1" class="fc" placeholder="Enter quantity" required></div>
    </div>
    <div class="fg"><label>Notes (optional)</label>
      <input type="text" name="notes" class="fc" placeholder="e.g. Delivery batch #42"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
      <a href="index.php" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn btn-primary">Add &rarr;</button>
    </div>
  </form>
  </div>
</div>
<?php require_once "../includes/footer.php"; ?>