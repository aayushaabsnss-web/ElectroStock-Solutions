<?php
/**
 * stock/search.php — Stock History (Presentation Layer)
 * Shows full stock movement history with date and type filters.
 * Uses Stock::getHistory() which calls sp_getStockHistory stored procedure.
 * Access: All authenticated users.
 */
$t = "Stock History"; $a = "stock";
require_once "../includes/header.php";
require_once "../classes/Stock.php"; // Middle layer — Stock class

// Read filter parameters
$pid   = $_GET["pid"]   ?? null;
$type  = $_GET["type"]  ?? null;
$from  = $_GET["from"]  ?? null;
$to    = $_GET["to"]    ?? null;

// Fetch history via Stock static method (calls sp_getStockHistory stored procedure)
$movements = Stock::getHistory($conn, $pid ? (int)$pid : null, $type ?: null, $from ?: null, $to ?: null);
$n         = count($movements);

// Get product list array for the filter dropdown
$prods = Stock::getProductList($conn);
?>
<div class="page-hdr"><h1>Stock History</h1></div>
<div class="card" style="margin-bottom:16px">
  <div class="card-body">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
    <div class="fg" style="margin:0"><label>Product</label>
      <select name="pid" class="fc" style="width:200px"><option value="">All products</option>
        <?php foreach($prods as $p): // Fixed: was mysqli_fetch_assoc loop — getProductList() returns an array ?>
        <option value="<?= $p["id"] ?>" <?= $pid==$p["id"]?"selected":"" ?>><?= h($p["name"]) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="fg" style="margin:0"><label>Type</label>
      <select name="type" class="fc" style="width:140px"><option value="">All types</option>
        <option <?= $type==="IN"?"selected":"" ?>>IN</option>
        <option <?= $type==="OUT"?"selected":"" ?>>OUT</option>
        <option <?= $type==="ADJUSTMENT"?"selected":"" ?>>ADJUSTMENT</option>
      </select></div>
    <div class="fg" style="margin:0"><label>From</label><input type="date" name="from" class="fc" value="<?= h($from??'') ?>" style="width:140px"></div>
    <div class="fg" style="margin:0"><label>To</label><input type="date" name="to" class="fc" value="<?= h($to??'') ?>" style="width:140px"></div>
    <button class="btn btn-outline btn-sm">Filter</button>
    <?php if($pid||$type||$from||$to): ?><a href="search.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
  </form>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title"><?= $n ?> transaction<?= $n!==1?"s":"" ?></span></div>
  <table class="tbl">
    <thead><tr><th>Product</th><th>Type</th><th>Qty change</th><th>Date</th><th>By</th><th>Notes</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if(empty($movements)): ?>
    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--t3)">No transactions found.</td></tr>
    <?php else: ?>
    <?php foreach($movements as $tx): // Each $tx is a Stock object — use getters ?>
    <tr>
      <td class="fw"><?= h($tx->getProductName()) ?></td>
      <td><span class="badge <?= $tx->getTypeBadge() ?>"><?= $tx->getType() ?></span></td>
      <td class="mono"><?= $tx->getSignedQuantity() ?></td>
      <td class="muted"><?= $tx->getFormattedDate() ?></td>
      <td><?= h($tx->getMovedBy()) ?></td>
      <td class="muted"><?= h($tx->getNotes() ?: "—") ?></td>
      <td><div style="display:flex;gap:5px">
        <!-- Fixed: view now links to product page using getProductId(), not transaction id -->
        <a href="view.php?id=<?= $tx->getProductId() ?>" class="icon-btn" title="View">&#128065;</a>
        <?php if(isOwner()): ?>
        <!-- Fixed: edit/delete now correctly link to edit_tx.php and delete_tx.php with both ids -->
        <a href="edit_tx.php?id=<?= $tx->getId() ?>&pid=<?= $tx->getProductId() ?>" class="icon-btn" title="Edit">&#9998;</a>
        <a href="delete_tx.php?id=<?= $tx->getId() ?>&pid=<?= $tx->getProductId() ?>" class="icon-btn del" onclick="return confirm('Delete this transaction and reverse stock?')" title="Delete">&#128465;</a>
        <?php endif; ?>
      </div></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>