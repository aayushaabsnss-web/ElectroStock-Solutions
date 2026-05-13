<?php
/**
 * products/search.php — Product Search (Presentation Layer)
 * Dedicated search page using Product::search() method.
 * Calls stored procedure sp_searchProducts via Product class.
 * Access: All authenticated users.
 */
$t = 'Search Products'; $a = 'products';
require_once '../includes/header.php';
require_once '../classes/Product.php';

$productObj = new Product($conn); // Middle layer
$q = trim($_GET['q'] ?? ''); $cat = $_GET['cat'] ?? ''; $status = $_GET['status'] ?? '';
$res = $productObj->search($q ?: null, $cat ?: null, $status ?: null);
$n   = mysqli_num_rows($res);
?>
<div class="page-hdr"><h1>Search Products</h1></div>
<div class="card" style="margin-bottom:16px">
  <div class="card-body">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
    <input type="text" name="q" class="fc" placeholder="Search name or SKU…" value="<?= h($q) ?>" style="flex:1;min-width:180px">
    <select name="cat" class="fc" style="width:150px"><option value="">All categories</option>
      <?php foreach(['iPhone','Mac','iPad','Watch','Accessory'] as $c): ?><option <?= $cat===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?>
    </select>
    <select name="status" class="fc" style="width:150px"><option value="">All statuses</option>
      <option value="in" <?= $status==='in'?'selected':'' ?>>In Stock</option>
      <option value="low" <?= $status==='low'?'selected':'' ?>>Low Stock</option>
      <option value="out" <?= $status==='out'?'selected':'' ?>>Out of Stock</option>
    </select>
    <button class="btn btn-primary">Search</button>
    <?php if($q||$cat||$status): ?><a href="search.php" class="btn btn-outline">Clear</a><?php endif; ?>
  </form>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title"><?= $n ?> result<?= $n!==1?'s':'' ?><?= $q ? " for &ldquo;".h($q)."&rdquo;" : '' ?></span></div>
  <table class="tbl">
    <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead>
    <tbody>
    <?php $e=true; foreach($res as $p): $e=false;
      $st=$p['quantity']==0?'out':($p['quantity']<=$p['min_qty']?'low':'in');
      $bl=['in'=>'b-green','low'=>'b-amber','out'=>'b-red'];
      $ll=['in'=>'In Stock','low'=>'Low Stock','out'=>'Out of Stock'];
    ?>
    <tr><td class="fw"><?= h($p['name']) ?></td><td class="mono muted"><?= h($p['sku']) ?></td>
    <td><span class="badge b-blue"><?= h($p['category']) ?></span></td>
    <td class="mono">$<?= number_format($p['price'],2) ?></td><td class="mono"><?= $p['quantity'] ?></td>
    <td><span class="badge <?= $bl[$st] ?>"><?= $ll[$st] ?></span></td></tr>
    <?php endforeach; if($e): ?><tr><td colspan="6" style="text-align:center;padding:30px;color:var(--t3)"><?= $q?"No results for &ldquo;".h($q)."&rdquo;" :"Enter a search term above." ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once '../includes/footer.php'; ?>
