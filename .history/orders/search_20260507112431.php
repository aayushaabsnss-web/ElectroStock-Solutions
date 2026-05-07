<?php
/**
 * orders/search.php — Order Search (Presentation Layer)
 * Searches orders by order number or customer name.
 * Uses Order::search() from the middle layer.
 * Access: All authenticated users.
 */
$t = "Search Orders"; $a = "orders";
require_once "../includes/header.php";
require_once "../classes/Order.php";

$orderObj = new Order($conn); // Middle layer
$q      = trim($_GET["q"]      ?? "");
$status = trim($_GET["status"] ?? "");
$res    = $orderObj->search($q ?: null, $status ?: null);
$n      = mysqli_num_rows($res);
$sc     = ["pending"=>"b-amber","processing"=>"b-blue","completed"=>"b-green","cancelled"=>"b-gray"];
?>
<div class="page-hdr"><h1>Search Orders</h1></div>
<div class="card" style="margin-bottom:16px">
  <div class="card-body">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
    <input type="text" name="q" class="fc" placeholder="Order # or customer name…" value="<?= h($q) ?>" style="flex:1;min-width:180px">
    <select name="status" class="fc" style="width:160px"><option value="">All statuses</option>
      <?php foreach(["pending","processing","completed","cancelled"] as $s): ?>
      <option <?= $status===$s?"selected":"" ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Search</button>
    <?php if($q||$status): ?><a href="search.php" class="btn btn-outline">Clear</a><?php endif; ?>
  </form>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title"><?= $n ?> order<?= $n!==1?"s":"" ?> found</span></div>
  <table class="tbl">
    <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php $e=true; while($o=mysqli_fetch_assoc($res)): $e=false; ?>
    <tr><td class="fw mono"><?= h($o["order_number"]) ?></td><td><?= h($o["customer"]) ?></td>
    <td class="mono">$<?= number_format($o["total"],2) ?></td>
    <td><span class="badge <?= $sc[$o["status"]] ?>"><?= ucfirst($o["status"]) ?></span></td>
    <td class="muted" style="font-size:11px"><?= date("d M Y",strtotime($o["created_at"])) ?></td>
    <td><a href="edit.php?id=<?= $o["id"] ?>" class="btn btn-outline btn-sm">View</a></td></tr>
    <?php endwhile; if($e): ?><tr><td colspan="6" style="text-align:center;padding:30px;color:var(--t3)">No orders found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>
