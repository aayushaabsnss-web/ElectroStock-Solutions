<?php
/**
 * orders/index.php — Orders (Presentation Layer)
 * POST handling before header to prevent headers already sent error.
 */
require_once "../config/db.php";
require_once "../auth/session.php";
require_once "../classes/Order.php";

// Handle status update via dropdown — before any HTML output
if($_SERVER["REQUEST_METHOD"]==="POST" && isset($_POST["order_id"], $_POST["new_status"])){
    $orderObj = new Order($conn);
    $orderObj->updateStatus((int)$_POST["order_id"], $_POST["new_status"], $_SESSION["uid"]);
    flash("success","Order status updated.");
    header("Location: index.php".(isset($_GET["status"])?"?status=".$_GET["status"]:"")); exit;
}

// Handle delete/cancel
if($_SERVER["REQUEST_METHOD"]==="POST" && isset($_POST["delete_id"])){
    requireOwner();
    $oid = (int)$_POST["delete_id"];
    mysqli_query($conn,"UPDATE orders SET status='cancelled' WHERE id=$oid");
    flash("success","Order cancelled.");
    header("Location: index.php"); exit;
}

$t = "Orders"; $a = "orders";
require_once "../includes/header.php";
include  "../includes/flash.php";

$q      = trim($_GET["q"]      ?? "");
$status = trim($_GET["status"] ?? "");

// Build query with filters
$where = ["1=1"]; $params = []; $types = "";
if($q){ $where[] = "(o.order_number LIKE ? OR o.customer LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; $types.="ss"; }
if($status){ $where[] = "o.status=?"; $params[]=$status; $types.="s"; }

$sql = "SELECT o.*, u.full_name cby,
        (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) items
        FROM orders o JOIN users u ON u.id=o.created_by
        WHERE ".implode(" AND ",$where)." ORDER BY o.created_at DESC";

if($params){ $st=mysqli_prepare($conn,$sql); mysqli_stmt_bind_param($st,$types,...$params); mysqli_stmt_execute($st); $orders=mysqli_stmt_get_result($st); }
else $orders = mysqli_query($conn,$sql);
$total = mysqli_num_rows($orders);

$sc = ["pending"=>"b-amber","processing"=>"b-blue","completed"=>"b-green","cancelled"=>"b-gray"];
$statuses = ["pending","processing","completed","cancelled"];
?>

<div class="page-hdr">
  <h1>Orders <span style="font-size:14px;color:var(--t2);font-weight:400">(<?= $total ?>)</span></h1>
  <a href="add.php" class="btn btn-primary">+ New order</a>
</div>

<div class="card">
  <!-- Filter bar -->
  <form method="GET" class="filter-bar">
    <input type="text" name="q" class="fc" placeholder="Search order # or customer..." value="<?= h($q) ?>" style="width:220px">
    <select name="status" class="fc">
      <option value="">All statuses</option>
      <?php foreach($statuses as $s): ?>
      <option value="<?= $s ?>" <?= $status===$s?"selected":"" ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline btn-sm">Filter</button>
    <?php if($q||$status): ?><a href="index.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    <span style="margin-left:auto;font-size:12px;color:var(--t2)"><?= $total ?> order<?= $total!==1?"s":"" ?></span>
  </form>

  <table class="tbl">
    <thead>
      <tr>
        <th>Order #</th>
        <th>Customer</th>
        <th>Items</th>
        <th>Total</th>
        <th>Status</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php $e=true; while($o=mysqli_fetch_assoc($orders)): $e=false; ?>
    <tr>
      <td class="fw mono"><?= h($o["order_number"]) ?></td>
      <td><?= h($o["customer"]) ?></td>
      <td class="mono"><?= $o["items"] ?> item<?= $o["items"]!=1?"s":"" ?></td>
      <td class="mono">$<?= number_format($o["total"],2) ?></td>
      <td>
        <!-- Status dropdown — submits on change -->
        <?php if($o["status"] !== "completed" && $o["status"] !== "cancelled"): ?>
        <form method="POST" style="margin:0">
          <input type="hidden" name="order_id" value="<?= $o["id"] ?>">
          <select name="new_status" class="fc" style="height:28px;padding:2px 8px;font-size:11px;width:120px"
                  onchange="if(confirm('Update order status?')) this.form.submit()">
            <?php foreach($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $o["status"]===$s?"selected":"" ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <?php else: ?>
        <span class="badge <?= $sc[$o["status"]] ?>"><?= ucfirst($o["status"]) ?></span>
        <?php endif; ?>
      </td>
      <td class="muted" style="font-size:11px"><?= date("d M Y",strtotime($o["created_at"])) ?></td>
      <td>
        <div style="display:flex;gap:5px">
          <a href="view.php?id=<?= $o["id"] ?>" class="icon-btn" title="View">&#128065;</a>
          <?php if($o["status"]!=="completed" && $o["status"]!=="cancelled"): ?>
          <a href="edit.php?id=<?= $o["id"] ?>" class="icon-btn" title="Edit">&#9998;</a>
          <?php endif; ?>
          <?php if(isOwner() && $o["status"]!=="completed"): ?>
          <form method="POST" style="display:inline">
            <input type="hidden" name="delete_id" value="<?= $o["id"] ?>">
            <button class="icon-btn del" title="Cancel order"
                    onclick="return confirm('Cancel this order?')">&#128465;</button>
          </form>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endwhile; if($e): ?>
    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--t3)">No orders found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
