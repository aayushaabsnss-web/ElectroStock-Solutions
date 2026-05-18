<?php
require_once "../config/db.php";
require_once "../auth/session.php";
require_once "../classes/Alert.php";
include "../includes/flash.php";

$id = (int)($_GET["id"] ?? 0);
$al = Alert::getById($conn, $id);
if (!$al) { flash("error","Alert not found."); header("Location: index.php"); exit; }

$t = "Alert Detail"; $a = "monitoring";
require_once "../includes/header.php";
?>
<div class="page-hdr">
  <a href="index.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1>Alert Detail</h1>
  <?php if(isOwner()): ?>
    <?php if($al->isActive()): ?>
    <form method="POST" action="index.php" style="display:inline">
      <input type="hidden" name="resolve_id" value="<?= $al->getId() ?>">
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
      <span class="badge <?= $al->isActive()?"b-red":"b-green" ?>"><?= ucfirst($al->getStatus()) ?></span>
    </div>
    <div class="card-body">
    <table style="width:100%;border-collapse:collapse;font-size:12px">
      <?php foreach([
        "Alert ID"      => "#".$al->getId(),
        "Product"       => $al->getProductName()." (".$al->getSku().")",
        "Current qty"   => $al->getCurrentQty()." units",
        "Min threshold" => $al->getThreshold()." units",
        "Shortfall"     => $al->getShortfall()." units",
        "Triggered"     => $al->getFormattedAlertedAt(),
        "Resolved"      => $al->getFormattedResolvedAt(),
        "Resolved by"   => ($al->getResolvedBy() ?: "—"),
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
          <div class="fw"><?= h($al->getProductName()) ?></div>
        </div>
        <div style="padding:12px 14px;background:var(--bg3);border-radius:8px">
          <div style="font-size:11px;color:var(--t2);margin-bottom:4px">Current stock</div>
          <div class="mono fw <?= $al->getQtyColor() ?>"><?= $al->getCurrentQty() ?> units</div>
        </div>
        <div style="padding:12px 14px;background:var(--rbg);border-radius:8px;border-left:3px solid var(--red)">
          <div style="font-size:11px;color:var(--t2);margin-bottom:4px">Shortfall to reach minimum</div>
          <div class="mono fw c-red"><?= $al->getShortfall() ?> units needed</div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once "../includes/footer.php"; ?>