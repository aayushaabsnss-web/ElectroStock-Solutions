<?php
/**
 * auth/view.php — View Single User Profile (Presentation Layer)
 * Shows full user details and their recent stock activity.
 * Access: Store Owner only.
 */
$t = "View User"; $a = "users";
require_once "../includes/header.php";
requireOwner();

$id = (int)($_GET["id"] ?? 0);
$u  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$id"));
if (!$u) { flash("error","User not found."); header("Location: register.php"); exit; }

// Count activity stats for this user
$txCount  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM stock_movements WHERE moved_by=$id"))["c"];
$ordCount = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders WHERE created_by=$id"))["c"];
$recent   = mysqli_query($conn,"SELECT sm.*,p.name pname FROM stock_movements sm JOIN products p ON p.id=sm.product_id WHERE sm.moved_by=$id ORDER BY sm.created_at DESC LIMIT 5");
?>
<div class="page-hdr">
  <a href="register.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1>User Profile</h1>
  <a href="edit.php?id=<?= $u["id"] ?>" class="btn btn-primary">Edit user</a>
</div>
<div class="g2">
  <div class="card">
    <div class="card-hdr"><span class="card-title">Account details</span></div>
    <div class="card-body">
    <table style="width:100%;border-collapse:collapse;font-size:12px">
      <?php foreach(["Full name"=>$u["full_name"],"Email"=>$u["email"],"Role"=>ucfirst(str_replace("_"," ",$u["role"])),"Status"=>($u["is_active"]?"Active":"Inactive"),"Member since"=>date("d M Y",strtotime($u["created_at"]))] as $k=>$v): ?>
      <tr><td style="padding:8px 0;color:var(--t2);width:40%"><?= $k ?></td>
          <td style="padding:8px 0;font-weight:500"><?= h($v) ?></td></tr>
      <?php endforeach; ?>
    </table>
    </div>
  </div>
  <div class="card">
    <div class="card-hdr"><span class="card-title">Activity summary</span></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
      <div style="display:flex;justify-content:space-between;padding:10px 14px;background:var(--bg3);border-radius:8px">
        <span style="color:var(--t2)">Stock transactions</span>
        <span class="mono fw"><?= $txCount ?></span>
      </div>
      <div style="display:flex;justify-content:space-between;padding:10px 14px;background:var(--bg3);border-radius:8px">
        <span style="color:var(--t2)">Orders created</span>
        <span class="mono fw"><?= $ordCount ?></span>
      </div>
    </div>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title">Recent stock activity</span></div>
  <table class="tbl">
    <thead><tr><th>Product</th><th>Type</th><th>Qty</th><th>Date</th><th>Notes</th></tr></thead>
    <tbody>
    <?php $e=true; while($tx=mysqli_fetch_assoc($recent)): $e=false;
      $tc=["IN"=>"b-green","OUT"=>"b-red","ADJUSTMENT"=>"b-amber"];
      $sign=$tx["quantity"]>0?"+":"";
    ?>
    <tr>
      <td class="fw"><?= h($tx["pname"]) ?></td>
      <td><span class="badge <?= $tc[$tx["type"]] ?>"><?= $tx["type"] ?></span></td>
      <td class="mono"><?= $sign.$tx["quantity"] ?></td>
      <td class="muted" style="font-size:11px"><?= date("d M Y H:i",strtotime($tx["created_at"])) ?></td>
      <td class="muted"><?= h($tx["notes"]??"—") ?></td>
    </tr>
    <?php endwhile; if($e): ?>
    <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--t3)">No stock activity yet.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once "../includes/footer.php"; ?>