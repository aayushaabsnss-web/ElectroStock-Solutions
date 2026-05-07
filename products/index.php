<?php
/**
 * products/index.php — Product List (Presentation Layer)
 * Lists all active Apple products with search and filter.
 * Uses the Product class from the middle layer.
 * Access: All authenticated users (read). Store Owner for edit/delete.
 */
$t = 'Products'; $a = 'products';
require_once '../includes/header.php';
require_once '../classes/Product.php';
include  '../includes/flash.php';

$productObj = new Product($conn); // Middle layer: Product class

// Read filter parameters from GET request
$q = trim($_GET['q'] ?? ''); $cat = $_GET['cat'] ?? ''; $status = $_GET['status'] ?? '';

// Use Product class search method (calls sp_searchProducts stored procedure)
$res   = $productObj->search($q ?: null, $cat ?: null, $status ?: null);
$total = count($res);
?>
<div class="page-hdr">
  <h1>Products <span style="font-size:14px;color:var(--t2);font-weight:400">(<?= $total ?>)</span></h1>
  <?php if(isOwner()): ?><a href="add.php" class="btn btn-primary">+ Add product</a><?php endif; ?>
</div>
<div class="card">
  <!-- Filter bar — submits GET params to same page -->
  <form method="GET" class="filter-bar">
    <input type="text" name="q" class="fc" placeholder="Name or SKU…" value="<?= h($q) ?>" style="width:200px">
    <select name="cat" class="fc">
      <option value="">All categories</option>
      <?php foreach(['iPhone','Mac','iPad','Watch','Accessory'] as $c): ?>
      <option <?= $cat===$c?'selected':'' ?>><?= $c ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="fc">
      <option value="">All statuses</option>
      <option value="in"  <?= $status==='in'?'selected':''  ?>>In Stock</option>
      <option value="low" <?= $status==='low'?'selected':'' ?>>Low Stock</option>
      <option value="out" <?= $status==='out'?'selected':'' ?>>Out of Stock</option>
    </select>
    <button class="btn btn-outline btn-sm">Filter</button>
    <?php if($q||$cat||$status): ?><a href="index.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
  </form>
  <table class="tbl">
    <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock</th><th>Min</th><th>Status</th><?php if(isOwner()): ?><th>Actions</th><?php endif; ?></tr></thead>
    <tbody>
    <?php $empty = true; foreach($res as $p): $empty = false;
      $st = $p['quantity']==0?'out':($p['quantity']<=$p['min_qty']?'low':'in');
      $bl = ['in'=>'b-green','low'=>'b-amber','out'=>'b-red'];
      $ll = ['in'=>'In Stock','low'=>'Low Stock','out'=>'Out of Stock'];
      $fc = ['in'=>'var(--green)','low'=>'var(--amber)','out'=>'var(--red)'];
      $pct = min(100,$p['min_qty']>0?round($p['quantity']/($p['min_qty']*2)*100):100);
    ?>
    <tr>
      <td class="fw"><?= h($p['name']) ?></td>
      <td class="mono muted"><?= h($p['sku']) ?></td>
      <td><span class="badge b-blue"><?= h($p['category']) ?></span></td>
      <td class="mono">$<?= number_format($p['price'],2) ?></td>
      <td><div class="sbar"><div class="sbg"><div class="sbf" style="width:<?= $pct ?>%;background:<?= $fc[$st] ?>"></div></div><span class="mono"><?= $p['quantity'] ?></span></div></td>
      <td class="mono"><?= $p['min_qty'] ?></td>
      <td><span class="badge <?= $bl[$st] ?>"><?= $ll[$st] ?></span></td>
      <?php if(isOwner()): ?>
      <td><div style="display:flex;gap:5px">
        <a href="edit.php?id=<?= $p['id'] ?>" class="icon-btn" title="Edit">&#9998;</a>
        <a href="delete.php?id=<?= $p['id'] ?>" class="icon-btn del"
           onclick="return confirm('Remove product?')">&#128465;</a>
      </div></td>
      <?php endif; ?>
    </tr>
    <?php endforeach; if($empty): ?>
    <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--t3)">No products found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once '../includes/footer.php'; ?>