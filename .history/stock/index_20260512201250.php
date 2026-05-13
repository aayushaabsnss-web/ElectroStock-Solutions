<?php
/**
 * stock/index.php — Stock Levels (Presentation Layer)
 * Shows all products with stock info. Search, filter by category and status.
 */
$t = "Stock Management"; $a = "stock";
require_once "../includes/header.php";
include  "../includes/flash.php";

$q      = trim($_GET["q"]      ?? "");
$cat    = $_GET["cat"]         ?? "";
$status = $_GET["status"]      ?? "";

$where = ["is_active=1"];
if($q)      $where[] = "(name LIKE '%".mysqli_real_escape_string($conn,$q)."%' OR sku LIKE '%".mysqli_real_escape_string($conn,$q)."%')";
if($cat)    $where[] = "category='".mysqli_real_escape_string($conn,$cat)."'";
if($status === "in")  $where[] = "quantity > min_qty";
if($status === "low") $where[] = "quantity > 0 AND quantity <= min_qty";
if($status === "out") $where[] = "quantity = 0";

$products = mysqli_query($conn,
    "SELECT id, name, sku, category, quantity, min_qty,
            CASE
              WHEN quantity = 0        THEN 'Out of Stock'
              WHEN quantity <= min_qty THEN 'Low Stock'
              ELSE                          'In Stock'
            END AS stock_status
     FROM products WHERE ".implode(" AND ",$where)." ORDER BY name");

$total = mysqli_num_rows($products);
?>
<div class="page-hdr">
  <h1>Stock Management</h1>
  <a href="add.php" class="btn btn-primary">+ Add Stock Transaction</a>
</div>
<div class="card">
  <form method="GET" class="filter-bar">
    <input type="text" name="q" class="fc" placeholder="Search name or SKU..." value="<?= h($q) ?>" style="width:200px">
    <select name="cat" class="fc">
      <option value="">All categories</option>
      <?php foreach(["iPhone","Mac","iPad","Watch","Accessory"] as $c): ?>
      <option <?= $cat===$c?"selected":"" ?>><?= $c ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="fc">
      <option value="">All statuses</option>
      <option value="in"  <?= $status==="in" ?"selected":"" ?>>In Stock</option>
      <option value="low" <?= $status==="low"?"selected":"" ?>>Low Stock</option>
      <option value="out" <?= $status==="out"?"selected":"" ?>>Out of Stock</option>
    </select>
    <button class="btn btn-outline btn-sm">Filter</button>
    <?php if($q||$cat||$status): ?>
    <a href="index.php" class="btn btn-outline btn-sm">Clear</a>
    <?php endif; ?>
    <span style="margin-left:auto;font-size:12px;color:var(--t2)"><?= $total ?> product<?= $total!==1?"s":"" ?></span>
  </form>
  <table class="tbl">
    <thead>
      <tr>
        <th>Product</th>
        <th>SKU</th>
        <th>Category</th>
        <th>Quantity</th>
        <th>Min level</th>
        <th>Status</th>
        <th>Actions</th>
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
        <div style="display:flex;gap:5px">
          <a href="view.php?id=<?= $p["id"] ?>"  class="icon-btn" title="View">&#128065;</a>
          <a href="edit.php?id=<?= $p["id"] ?>"  class="icon-btn" title="Edit">&#9998;</a>
          <?php if(isOwner()): ?>
          <a href="delete.php?id=<?= $p["id"] ?>" class="icon-btn del" title="Delete"
             onclick="return confirm('Delete all stock records for <?= h(addslashes($p["name"])) ?>?')">&#128465;</a>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endwhile; if($e): ?>
    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--t3)">No products found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>