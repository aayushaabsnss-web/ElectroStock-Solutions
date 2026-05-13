<?php
/**
 * products/index.php — Product Catalogue (Presentation Layer)
 * Lists all active Apple products with product details only.
 * Stock levels are managed in the Stock module.
 */
$t = "Products"; $a = "products";
require_once "../includes/header.php";
require_once "../classes/Product.php";
include  "../includes/flash.php";

$productObj = new Product($conn);
$q = trim($_GET["q"] ?? ""); $cat = $_GET["cat"] ?? ""; 

// Search by name/SKU and category only (no stock status filter here)
$res   = $productObj->search($q ?: null, $cat ?: null, null);
$total = count($res);
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
    <?php $empty=true; foreach($res as $p): $empty=false; ?>
    <tr>
      <td class="fw"><?= h($p["name"]) ?></td>
      <td class="mono muted"><?= h($p["sku"]) ?></td>
      <td><span class="badge b-blue"><?= h($p["category"]) ?></span></td>
      <td class="mono">$<?= number_format($p["price"],2) ?></td>
      <td class="muted"><?= h($p["supplier"] ?? "Apple Inc.") ?></td>
      <td class="muted" style="font-size:11px"><?= date("d M Y", strtotime($p["created_at"])) ?></td>
      <?php if(isOwner()): ?>
      <td><div style="display:flex;gap:5px">
        <a href="edit.php?id=<?= $p["id"] ?>" class="icon-btn" title="Edit">&#9998;</a>
        <a href="delete.php?id=<?= $p["id"] ?>" class="icon-btn del"
           onclick="return confirm('Remove product?')">&#128465;</a>
      </div></td>
      <?php endif; ?>
    </tr>
    <?php endforeach; if($empty): ?>
    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--t3)">No products found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
