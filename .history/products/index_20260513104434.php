<?php
/**
 * products/index.php — Product Catalogue (Presentation Layer)
 * Fetches Product objects from the middle layer.
 * HTML table accesses data via object getter methods.
 */
$t = "Products"; $a = "products";
require_once "../includes/header.php";
require_once "../classes/Product.php";
include  "../includes/flash.php";

$q   = trim($_GET["q"]   ?? "");
$cat = trim($_GET["cat"] ?? "");

// Returns array of Product objects via static method
$products = Product::search($conn, $q ?: null, $cat ?: null, null);
$total    = count($products);
?>
<div class="page-hdr">
  <h1>Products <span style="font-size:14px;color:var(--t2);font-weight:400">(<?= $total ?>)</span></h1>
  <?php if(isOwner()): ?><a href="add.php" class="btn btn-primary">+ Add product</a><?php endif; ?>
</div>
<div class="card">
  <form method="GET" class="filter-bar">
    <input type="text" name="q" class="fc" placeholder="Search name or SKU..." value="<?= h($q) ?>" style="width:220px">
    <select name="cat" class="fc">
      <option value="">All categories</option>
      <?php foreach(["iPhone","Mac","iPad","Watch","Accessory"] as $c): ?>
      <option <?= $cat===$c?"selected":"" ?>><?= $c ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline btn-sm">Filter</button>
    <?php if($q||$cat): ?><a href="index.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
  </form>
  <table class="tbl">
    <thead>
      <tr>
        <th>Product name</th>
        <th>SKU</th>
        <th>Category</th>
        <th>Price</th>
        <th>Supplier</th>
        <th>Added</th>
        <?php if(isOwner()): ?><th>Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
    <?php if(empty($products)): ?>
      <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--t3)">No products found.</td></tr>
    <?php else: ?>
    <?php foreach($products as $product): // Loop over Product objects ?>
    <tr>
      <!-- Access data via getter methods on the Product object -->
      <td class="fw"><?= h($product->getName()) ?></td>
      <td class="mono muted"><?= h($product->getSku()) ?></td>
      <td><span class="badge b-blue"><?= h($product->getCategory()) ?></span></td>
      <td class="mono"><?= $product->getFormattedPrice() ?></td>
      <td class="muted"><?= h($product->getSupplier()) ?></td>
      <td class="muted" style="font-size:11px"><?= $product->getFormattedDate() ?></td>
      <?php if(isOwner()): ?>
      <td><div style="display:flex;gap:5px">
        <a href="edit.php?id=<?= $product->getId() ?>" class="icon-btn" title="Edit">&#9998;</a>
        <a href="delete.php?id=<?= $product->getId() ?>" class="icon-btn del"
           onclick="return confirm('Remove <?= h(addslashes($product->getName())) ?>?')">&#128465;</a>
      </div></td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
