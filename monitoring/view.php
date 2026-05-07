<?php
/**
 * monitoring/view.php — View Single Alert Detail (Presentation Layer)
 * Shows full details of one alert record including product and resolution info.
 * Access: All authenticated users.
 */
$t = "Alert Detail"; $a = "monitoring";
require_once "../includes/header.php";

$id = (int)($_GET["id"] ?? 0);
$al = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT m.*,p.name pname,p.sku,p.category,p.quantity,p.price,
            u.full_name rby
     FROM monitoring m
     JOIN products p ON p.id=m.product_id
     LEFT JOIN users u ON u.id=m.resolved_by
     WHERE m.id=$id"));
if (!$al) { flash("error","Alert not found."); header("Location: index.php"); exit; }
$shortfall = max(0,$al["threshold"] - $al["quantity"]);
?>
<div class="page-hdr">
  <a href="index.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1>Alert Detail</h1>
  <?php if(isOwner()): ?>
    <?php if($al["alert_status"]==="active"): ?>
    <form method="POST" action="index.php" style="display:inline">
      <input type="hidden" name="resolve_id" value="<?= $al["id"] ?>">
      <button class="btn btn-success">&#10003; Resolve alert</button>
    </form>
    <?php else: ?>
    <a href="delete.php?id=<?= $id ?>" class="btn btn-danger"
       onclick="return confirm('Delete this resolved alert?')">Delete record</a>
    <?php endif; ?>
  <?php endif; ?>
</div>
<div class="g2">
  <div class="card">
    <div class="card-hdr">
      <span class="card-title">Alert information</span>
      <span class="badge <?= $al["alert_status"]==="active"?"b-red":"b-green" ?>"><?= ucfirst($al["alert_status"]) ?></span>
    </div>
    <div class="card-body">
    <table style="width:100%;border-collapse:collapse;font-size:12px">
      <?php foreach([
        "Alert ID"       => "#".$id,
        "Product"        => $al["pname"]." (".$al["sku"].")",
        "Category"       => $al["category"],
        "Current qty"    => $al["quantity"]." units",
        "Min threshold"  => $al["threshold"]." units",
        "Shortfall"      => "-".$shortfall." units",
        "Triggered"      => date("d M Y H:i",strtotime($al["alerted_at"])),
        "Resolved"       => $al["resolved_at"]?date("d M Y H:i",strtotime($al["resolved_at"])):"Not yet resolved",
        "Resolved by"    => ($al["rby"] ?? "—"),
      ] as $k=>$v): ?>
      <tr>
        <td style="padding:8px 0;color:var(--t2);width:40%;border-bottom:0.5px solid var(--b)"><?= $k ?></td>
        <td style="padding:8px 0;font-weight:500;border-bottom:0.5px solid var(--b)"><?= h($v) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>
  </div>
  <div class="card">
    <div class="card-hdr"><span class="card-title">Product snapshot</span></div>
    <div class="card-body">
      <div style="display:flex;flex-direction:column;gap:10px">
        <div style="padding:12px 14px;background:var(--bg3);border-radius:8px">
          <div style="font-size:11px;color:var(--t2);margin-bottom:4px">Product name</div>
          <div class="fw"><?= h($al["pname"]) ?></div>
        </div>
        <div style="padding:12px 14px;background:var(--bg3);border-radius:8px">
          <div style="font-size:11px;color:var(--t2);margin-bottom:4px">Current stock</div>
          <div class="mono fw <?= $al["quantity"]==0?"c-red":"c-amber" ?>"><?= $al["quantity"] ?> units</div>
        </div>
        <div style="padding:12px 14px;background:var(--bg3);border-radius:8px">
          <div style="font-size:11px;color:var(--t2);margin-bottom:4px">Unit price</div>
          <div class="mono fw">$<?= number_format($al["price"],2) ?></div>
        </div>
        <div style="padding:12px 14px;background:var(--rbg);border-radius:8px;border-left:3px solid var(--red)">
          <div style="font-size:11px;color:var(--t2);margin-bottom:4px">Shortfall to reach minimum</div>
          <div class="mono fw c-red">-<?= $shortfall ?> units needed</div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once "../includes/footer.php"; ?>