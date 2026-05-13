<?php
/**
 * orders/index.php — Orders (Presentation Layer)
 * Uses Order objects — data accessed via getter methods.
 */
require_once "../config/db.php";
require_once "../auth/session.php";
require_once "../classes/Order.php";
require_once "../classes/StockMovement.php";

// POST before HTML
if($_SERVER["REQUEST_METHOD"]==="POST" && isset($_POST["order_id"], $_POST["new_status"])){
    Order::updateStatus($conn, (int)$_POST["order_id"], $_POST["new_status"], $_SESSION["uid"]);
    flash("success","Order updated."); header("Location: index.php"); exit;
}
if($_SERVER["REQUEST_METHOD"]==="POST" && isset($_POST["delete_id"])){
    requireOwner();
    Order::updateStatus($conn, (int)$_POST["delete_id"], "cancelled", $_SESSION["uid"]);
    flash("success","Order cancelled."); header("Location: index.php"); exit;
}

$t = "Orders"; $a = "orders";
require_once "../includes/header.php";
include  "../includes/flash.php";

$q      = trim($_GET["q"]      ?? "");
$status = trim($_GET["status"] ?? "");

// Returns array of Order objects
$orders = Order::search($conn, $q ?: null, $status ?: null);
$total  = count($orders);
?>
<div class="page-hdr">
  <h1>Orders <span style="font-size:14px;color:var(--t2);font-weight:400">(<?= $total ?>)</span></h1>
  <a href="add.php" class="btn btn-primary">+ New order</a>
</div>
<div class="card">
  <form method="GET" class="filter-bar">
    <input type="text" name="q" class="fc" placeholder="Search order # or customer..." value="<?= h($q) ?>" style="width:220px">
    <select name="status" class="fc">
      <option value="">All statuses</option>
      <?php foreach(["pending","processing","completed","cancelled"] as $s): ?>
      <option value="<?= $s ?>" <?= $status===$s?"selected":"" ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline btn-sm">Filter</button>
    <?php if($q||$status): ?><a href="index.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    <span style="margin-left:auto;font-size:12px;color:var(--t2)"><?= $total ?> order<?= $total!==1?"s":"" ?></span>
  </form>
  <table class="tbl">
    <thead><tr><th>Order #</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if(empty($orders)): ?>
    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--t3)">No orders found.</td></tr>
    <?php else: ?>
    <?php foreach($orders as $order): // Each $order is an Order object ?>
    <tr>
      <td class="fw mono" style="font-size:11px"><?= h($order->getOrderNumber()) ?></td>
      <td><?= h($order->getCustomer()) ?></td>
      <td class="mono"><?= $order->getItemCount() ?> item<?= $order->getItemCount()!=1?"s":"" ?></td>
      <td class="mono"><?= $order->getFormattedTotal() ?></td>
      <td>
        <?php if($order->isEditable()): ?>
        <form method="POST" style="margin:0">
          <input type="hidden" name="order_id" value="<?= $order->getId() ?>">
          <select name="new_status" class="fc" style="height:28px;padding:2px 8px;font-size:11px;width:120px"
                  onchange="if(confirm('Update status?')) this.form.submit()">
            <?php foreach(["pending","processing","completed","cancelled"] as $s): ?>
            <option value="<?= $s ?>" <?= $order->getStatus()===$s?"selected":"" ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <?php else: ?>
        <span class="badge <?= $order->getStatusBadge() ?>"><?= ucfirst($order->getStatus()) ?></span>
        <?php endif; ?>
      </td>
      <td class="muted" style="font-size:11px"><?= $order->getFormattedDate() ?></td>
      <td><div style="display:flex;gap:5px">
        <a href="view.php?id=<?= $order->getId() ?>" class="icon-btn" title="View">&#128065;</a>
        <?php if($order->isEditable()): ?>
        <a href="edit.php?id=<?= $order->getId() ?>" class="icon-btn" title="Edit">&#9998;</a>
        <?php endif; ?>
        <?php if(isOwner() && $order->isCancellable()): ?>
        <form method="POST" style="display:inline">
          <input type="hidden" name="delete_id" value="<?= $order->getId() ?>">
          <button class="icon-btn del" onclick="return confirm('Cancel this order?')" title="Cancel">&#128465;</button>
        </form>
        <?php endif; ?>
      </div></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
