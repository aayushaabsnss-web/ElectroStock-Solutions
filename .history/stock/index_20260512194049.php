<?php
/**
 * stock/index.php — Stock Management (Presentation Layer)
 * Shows all products with their current stock levels.
 * The "Add Stock" form is hidden by default and appears on button click.
 */
$t = "Stock Management"; $a = "stock";
require_once "../includes/header.php";
require_once "../classes/Stock.php";
include  "../includes/flash.php";

$stockObj = new Stock($conn);

// Handle stock transaction POST
if($_SERVER["REQUEST_METHOD"]==="POST"){
    $errors = $stockObj->validate($_POST, isOwner());
    if(!$errors){
        [$ok, $err, $newQty] = $stockObj->addMovement(
            (int)$_POST["product_id"],
            $_POST["type"],
            (int)$_POST["quantity"],
            $_SESSION["uid"],
            trim($_POST["notes"] ?? "")
        );
        if($ok) flash("success","Stock updated. New quantity: $newQty units.");
        else    flash("error", $err);
    } else {
        flash("error", implode(" ", $errors));
    }
    header("Location: index.php"); exit;
}

// Fetch all products with their stock info
$products = mysqli_query($conn,
    "SELECT id, name, sku, category, quantity, min_qty,
            CASE
              WHEN quantity = 0          THEN 'Out of Stock'
              WHEN quantity <= min_qty   THEN 'Low Stock'
              ELSE                            'In Stock'
            END AS stock_status
     FROM products
     WHERE is_active = 1
     ORDER BY name");

// Product list for the form dropdown
$prodList = $stockObj->getProductList();
?>

<div class="page-hdr">
  <h1>Stock Management</h1>
  <button class="btn btn-primary" onclick="toggleForm()">+ Add Stock Transaction</button>
  <a href="search.php" class="btn btn-outline">Full history &rarr;</a>
</div>

<!-- Add Stock Form (hidden by default) -->
<div id="stock-form-wrap" style="display:none;margin-bottom:16px">
  <div class="card">
    <div class="card-hdr">
      <span class="card-title">Record stock transaction</span>
      <button class="btn btn-outline btn-sm" onclick="toggleForm()">&#10005; Close</button>
    </div>
    <div class="card-body">
    <form method="POST">
      <div class="form3">
        <div class="fg">
          <label>Product *</label>
          <select name="product_id" id="product_id" class="fc" required>
            <option value="">Select product...</option>
            <?php
            $tmp = [];
            while($p = mysqli_fetch_assoc($prodList)) $tmp[] = $p;
            foreach($tmp as $p): ?>
            <option value="<?= $p["id"] ?>"><?= h($p["name"]) ?> (<?= $p["quantity"] ?> in stock)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label>Transaction type *</label>
          <select name="type" class="fc" required>
            <option value="IN">IN — Delivery received</option>
            <option value="OUT">OUT — Item sold / removed</option>
            <?php if(isOwner()): ?>
            <option value="ADJUSTMENT">ADJUSTMENT — Manual correction</option>
            <?php endif; ?>
          </select>
        </div>
        <div class="fg">
          <label>Quantity *</label>
          <input type="number" name="quantity" min="1" class="fc" placeholder="0" required>
        </div>
      </div>
      <div class="fg">
        <label>Notes (optional)</label>
        <input type="text" name="notes" class="fc" placeholder="e.g. Delivery batch #42, Store sale">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn btn-outline" onclick="toggleForm()">Cancel</button>
        <button type="submit" class="btn btn-primary">Log transaction &rarr;</button>
      </div>
    </form>
    </div>
  </div>
</div>

<!-- Product Stock Table -->
<div class="card">
  <div class="card-hdr"><span class="card-title">Current stock levels</span></div>
  <table class="tbl">
    <thead>
      <tr>
        <th>Product</th>
        <th>SKU</th>
        <th>Category</th>
        <th>Quantity</th>
        <th>Min level</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php $e=true; while($p=mysqli_fetch_assoc($products)): $e=false;
      $bl=["In Stock"=>"b-green","Low Stock"=>"b-amber","Out of Stock"=>"b-red"];
      $ql=["In Stock"=>"c-green","Low Stock"=>"c-amber","Out of Stock"=>"c-red"];
    ?>
    <tr>
      <td class="fw"><?= h($p["name"]) ?></td>
      <td class="mono muted"><?= h($p["sku"]) ?></td>
      <td><span class="badge b-blue"><?= h($p["category"]) ?></span></td>
      <td class="mono fw <?= $ql[$p["stock_status"]] ?>"><?= $p["quantity"] ?></td>
      <td class="mono"><?= $p["min_qty"] ?></td>
      <td><span class="badge <?= $bl[$p["stock_status"]] ?>"><?= $p["stock_status"] ?></span></td>
      <td>
        <button class="btn btn-outline btn-sm"
          onclick="openForm(<?= $p['id'] ?>)">
          Update stock
        </button>
      </td>
    </tr>
    <?php endwhile; if($e): ?>
    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--t3)">No products found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
// Show/hide the stock form
function toggleForm() {
  const wrap = document.getElementById("stock-form-wrap");
  wrap.style.display = wrap.style.display === "none" ? "block" : "none";
  if(wrap.style.display === "block") wrap.scrollIntoView({ behavior:"smooth" });
}

// Open form and pre-select a specific product
function openForm(productId) {
  const wrap = document.getElementById("stock-form-wrap");
  wrap.style.display = "block";
  document.getElementById("product_id").value = productId;
  wrap.scrollIntoView({ behavior:"smooth" });
}
</script>
<?php require_once "../includes/footer.php"; ?>