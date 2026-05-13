<?php
/**
 * products/search.php — Product Search (Presentation Layer)
 * Searches and filters the product catalogue by name, SKU and category.
 * Product info only — no stock data shown here.
 */
$t = "Search Products"; $a = "products";
require_once "../includes/header.php";
require_once "../classes/Product.php";

$productObj = new Product($conn);
$q   = trim($_GET["q"]   ?? "");
$cat = trim($_GET["cat"] ?? "");

$res = $productObj->search($q ?: null, $cat ?: null, null);
$n   = count($res);
?>
<div class="page-hdr"><h1>Search Products</h1></div>
<div class="card" style="margin-bottom:16px">
  <div class="card-body">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
    <input type="text" name="q" class="fc" placeholder="Search name or SKU..." value="<?= h($q) ?>" style="flex:1;min-width:200px">
    <select name="cat" class="fc" style="width:160px">
      <option value="">All categories</option>
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
  <div class="card-hdr"><span class="card-title"><?= $n ?> product<?= $n!==1?"s":"" ?> found<?= $q?" for &ldquo;".h($q)."&rdquo;":"" ?></span></div>
  <table class="tbl">
    <thead><tr><th>Product name</th><th>SKU</th><th>Category</th><th>Price</th><th>Supplier</th></tr></thead>
    <tbody>
    <?php $e=true; foreach($res as $p): $e=false; ?>
    <tr>
      <td class="fw"><?= h($p["name"]) ?></td>
      <td class="mono muted"><?= h($p["sku"]) ?></td>
      <td><span class="badge b-blue"><?= h($p["category"]) ?></span></td>
      <td class="mono">$<?= number_format($p["price"],2) ?></td>
      <td class="muted"><?= h($p["supplier"] ?? "Apple Inc.") ?></td>
    </tr>
    <?php endforeach; if($e): ?>
    <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--t3)"><?= $q?"No results for &ldquo;".h($q)."&rdquo;":"Enter a search term above." ?></td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
