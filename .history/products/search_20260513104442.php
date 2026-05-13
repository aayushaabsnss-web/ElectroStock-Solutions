<?php
/**
 * products/search.php — Product Search (Presentation Layer)
 * Returns an array of Product objects from Product::search().
 * HTML accesses each product via getter methods.
 */
$t = "Search Products"; $a = "products";
require_once "../includes/header.php";
require_once "../classes/Product.php";

$q   = trim($_GET["q"]   ?? "");
$cat = trim($_GET["cat"] ?? "");

// Returns array of Product objects
$products = Product::search($conn, $q ?: null, $cat ?: null, null);
$n        = count($products);
?>
<div class="page-hdr"><h1>Search Products</h1></div>
<div class="card" style="margin-bottom:16px">
  <div class="card-body">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
    <input type="text" name="q" class="fc" placeholder="Search name or SKU..." value="<?= h($q) ?>" style="flex:1;min-width:200px">
    <select name="cat" class="fc" style="width:160px"><option value="">All categories</option>
      <?php foreach(["iPhone","Mac","iPad","Watch","Accessory"] as $c): ?>
      <option <?= $cat===$c?"selected":"" ?>><?= $c ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Search</button>
    <?php if($q||$cat): ?><a href="search.php" class="btn btn-outline">Clear</a><?php endif; ?>
  </form>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title"><?= $n ?> product<?= $n!==1?"s":"" ?> found</span></div>
  <table class="tbl">
    <thead><tr><th>Product name</th><th>SKU</th><th>Category</th><th>Price</th><th>Supplier</th></tr></thead>
    <tbody>
    <?php if(empty($products)): ?>
    <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--t3)"><?= $q?"No results for &ldquo;".h($q)."&rdquo;":"Enter a search term above." ?></td></tr>
    <?php else: ?>
    <?php foreach($products as $product): // Each $product is a Product object ?>
    <tr>
      <td class="fw"><?= h($product->getName()) ?></td>
      <td class="mono muted"><?= h($product->getSku()) ?></td>
      <td><span class="badge b-blue"><?= h($product->getCategory()) ?></span></td>
      <td class="mono"><?= $product->getFormattedPrice() ?></td>
      <td class="muted"><?= h($product->getSupplier()) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
