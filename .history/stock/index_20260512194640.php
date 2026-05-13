<?php
/**
 * stock/index.php — Stock Levels (Presentation Layer)
 * Shows all products with their current stock levels.
 * Click "Update stock" on any row to go to the stock update form.
 */
$t = "Stock Management"; $a = "stock";
require_once "../includes/header.php";
include  "../includes/flash.php";

// Fetch all products with stock info
$products = mysqli_query($conn,
    "SELECT id, name, sku, category, quantity, min_qty,
            CASE
              WHEN quantity = 0        THEN 'Out of Stock'
              WHEN quantity <= min_qty THEN 'Low Stock'
              ELSE                          'In Stock'
            END AS stock_status
     FROM products
     WHERE is_active = 1
     ORDER BY name");
?>
<div class="page-hdr">
  <h1>Stock Management</h1>
  <a href="add.php" class="btn btn-primary">+ Add Stock Transaction</a>
  <a href="search.php" class="btn btn-outline">Full history &rarr;</a>
</div>
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
        <a href="add.php?pid=<?= $p["id"] ?>" class="btn btn-outline btn-sm">Update stock</a>
      </td>
    </tr>
    <?php endwhile; if($e): ?>
    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--t3)">No products found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
