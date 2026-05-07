<?php
/**
 * orders/index.php — Orders List (Presentation Layer)
 * Lists all orders with status filter tabs.
 * Uses Order class for data access and status updates.
 * Completing an order triggers stock deduction via Stock class.
 * Access: All authenticated users.
 */
$t = "Orders"; $a = "orders";
require_once "../includes/header.php";
require_once "../classes/Order.php";
include "../includes/flash.php";

$orderObj = new Order($conn); // Middle layer: Order class

// Handle status update form submission
if($_SERVER["REQUEST_METHOD"]==="POST" && isset($_POST["order_id"])){
    $oid = (int)$_POST["order_id"];
    $ns  = $_POST["new_status"] ?? "";
    // updateStatus handles stock deduction when completing an order
    $orderObj->updateStatus($oid, $ns, $_SESSION["uid"]);
    flash("success", "Order updated.");
    header("Location: index.php"); exit;
}

$status = $_GET["status"] ?? "";
$orders = $orderObj->getAll($status ?: null); // Fetch all or filtered orders
$n      = mysqli_num_rows($orders);
$sc     = ["pending"=>"b-amber","processing"=>"b-blue","completed"=>"b-green","cancelled"=>"b-gray"];
?>
<div class="page-hdr">
  <h1>Orders <span style="font-size:14px;color:var(--t2);font-weight:400">(<?= $n ?>)</span></h1>
  <a href="add.php" class="btn btn-primary">+ New order</a>
</div>
<div class="card">
  <!-- Status filter tabs -->
  <div class="filter-bar">
    <?php foreach(["" =>"All","pending"=>"Pending","processing"=>"Processing","completed"=>"Completed","cancelled"=>"Cancelled"] as $k=>$v): ?>
    <a href="index.php<?= $k?"?status=$k":"" ?>" class="btn <?= $status===$k?"btn-primary":"btn-outline" ?> btn-sm"><?= $v ?></a>
    <?php endforeach; ?>
  </div>
  <table class="tbl">
    <thead><tr><th>Order #</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php $e=true; while($o=mysqli_fetch_assoc($orders)): $e=false; ?>
    <tr>
      <td class="fw mono"><?= h($o["order_number"]) ?></td>
      <td><?= h($o["customer"]) ?></td>
      <td class="mono"><?= $o["item_count"] ?> items</td>
      <td class="mono">$<?= number_format($o["total"],2) ?></td>
      <td><span class="badge <?= $sc[$o["status"]] ?>"><?= ucfirst($o["status"]) ?></span></td>
      <td class="muted" style="font-size:11px"><?= date("d M Y",strtotime($o["created_at"])) ?></td>
      <td><div style="display:flex;gap:5px;align-items:center">
        <a href="edit.php?id=<?= $o["id"] ?>" class="icon-btn" title="View/Edit">&#9998;</a>
        <?php if($o["status"]==="pending"): ?>
        <form method="POST" style="display:inline"><input type="hidden" name="order_id" value="<?= $o["id"] ?>"><input type="hidden" name="new_status" value="processing"><button class="btn btn-outline btn-sm">Process</button></form>
        <?php elseif($o["status"]==="processing"): ?>
        <form method="POST" style="display:inline"><input type="hidden" name="order_id" value="<?= $o["id"] ?>"><input type="hidden" name="new_status" value="completed"><button class="btn btn-success btn-sm" onclick="return confirm('Complete order? Stock will be deducted.')">Complete</button></form>
        <?php endif; ?>
      </div></td>
    </tr>
    <?php endwhile; if($e): ?><tr><td colspan="7" style="text-align:center;padding:30px;color:var(--t3)">No orders found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
