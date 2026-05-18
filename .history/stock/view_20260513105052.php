<?php
/**
 * stock/view.php — Stock Detail (Presentation Layer)
 * Fetches a Product object and StockMovement objects.
 * HTML accesses data via getter methods.
 */
$t = "Stock Detail"; $a = "stock";
require_once "../includes/header.php";
require_once "../classes/Product.php";
require_once "../classes/StockMovement.php";
include  "../includes/flash.php";

$id = (int)($_GET["id"] ?? 0);
// Fetch Product object by ID
$product = Product::getById($conn, $id);
if(!$product){ flash("error","Product not found."); header("Location: index.php"); exit; }

// Fetch StockMovement objects for this product
$transactions = StockMovement::getByProduct($conn, $id, 20);
?>
<div class="page-hdr">
  <a href="index.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1><?= h($product->getName()) ?></h1>
  <a href="add.php?pid=<?= $id ?>" class="btn btn-primary">+ Add Transaction</a>
  <?php if(isOwner()): ?><a href="edit.php?id=<?= $id ?>" class="btn btn-outline">Edit stock settings</a><?php endif; ?>
</div>
<div class="g2" style="margin-bottom:16px">
  <div class="card">
    <div class="card-hdr"><span class="card-title">Stock information</span></div>
    <div class="card-body">
    <table style="width:100%;border-collapse:collapse;font-size:12px">
      <?php foreach([
        "Product"         => $product->getName(),
        "SKU"             => $product->getSku(),
        "Category"        => $product->getCategory(),
        "Current qty"     => $product->getQuantity()." units",
        "Min stock level" => $product->getMinQty()." units",
        "Status"          => $product->getStockStatus(),
      ] as $k=>$v): ?>
      <tr>
        <td style="padding:8px 0;color:var(--t2);width:45%;border-bottom:0.5px solid var(--b)"><?= $k ?></td>
        <td style="padding:8px 0;font-weight:500;border-bottom:0.5px solid var(--b)"><?= h($v) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>
  </div>
  <div class="card">
    <div class="card-hdr"><span class="card-title">Current stock level</span></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
      <div style="padding:14px;background:var(--bg3);border-radius:8px;text-align:center">
        <div style="font-size:42px;font-weight:700;font-family:var(--mono)"><?= $product->getQuantity() ?></div>
        <div style="font-size:12px;color:var(--t2);margin-top:4px">units in stock</div>
      </div>
      <span class="badge <?= $product->getStockBadge() ?>" style="font-size:13px;padding:6px 14px;text-align:center"><?= $product->getStockStatus() ?></span>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title">Transaction history</span></div>
  <table class="tbl">
    <thead><tr><th>Type</th><th>Qty change</th><th>Date</th><th>By</th><th>Notes</th><?php if(isOwner()): ?><th>Actions</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if(empty($transactions)): ?>
    <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--t3)">No transactions yet.</td></tr>
    <?php else: ?>
    <?php foreach($transactions as $tx): // Each $tx is a StockMovement object ?>
    <tr>
      <td><span class="badge <?= $tx->getTypeBadge() ?>"><?= $tx->getType() ?></span></td>
      <td class="mono fw"><?= $tx->getSignedQuantity() ?></td>
      <td class="muted"><?= $tx->getFormattedDate() ?></td>
      <td><?= h($tx->getMovedBy()) ?></td>
      <td class="muted"><?= h($tx->getNotes() ?: "—") ?></td>
      <?php if(isOwner()): ?>
      <td><div style="display:flex;gap:5px">
        <a href="edit_tx.php?id=<?= $tx->getId() ?>&pid=<?= $id ?>" class="icon-btn" title="Edit">&#9998;</a>
        <a href="delete_tx.php?id=<?= $tx->getId() ?>&pid=<?= $id ?>" class="icon-btn del" title="Delete"
           onclick="return confirm('Delete this transaction?')">&#128465;</a>
      </div></td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
