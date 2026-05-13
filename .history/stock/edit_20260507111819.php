<?php
/**
 * stock/edit.php — Edit Stock Transaction Notes (Presentation Layer)
 * Allows updating the notes field on an existing stock transaction.
 * The quantity and type are immutable for audit integrity.
 * Access: Store Owner only.
 */
$t = "Edit Transaction"; $a = "stock";
require_once "../includes/header.php";
requireOwner();

$id = (int)($_GET["id"] ?? 0);
$tx = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT sm.*,p.name pname,u.full_name dby FROM stock_movements sm
     JOIN products p ON p.id=sm.product_id
     JOIN users u ON u.id=sm.moved_by
     WHERE sm.id=$id"));
if (!$tx) { flash("error","Transaction not found."); header("Location: search.php"); exit; }

$err = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $notes = trim($_POST["notes"] ?? "");
    // Only notes can be updated — quantity and type are locked for audit integrity
    $stmt = mysqli_prepare($conn, "UPDATE stock_movements SET notes=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "si", $notes, $id);
    mysqli_stmt_execute($stmt);
    flash("success","Transaction notes updated.");
    header("Location: search.php"); exit;
}
$tc = ["IN"=>"b-green","OUT"=>"b-red","ADJUSTMENT"=>"b-amber"];
$sign = $tx["quantity"] > 0 ? "+" : "";
?>
<?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
<div class="page-hdr">
  <a href="search.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1>Edit Transaction</h1>
</div>
<div class="card" style="max-width:560px">
  <div class="card-hdr"><span class="card-title">Transaction #<?= $id ?></span></div>
  <div class="card-body">
    <!-- Read-only transaction details -->
    <div style="background:var(--bg3);border-radius:8px;padding:12px 14px;margin-bottom:16px;display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:12px">
      <div><span style="color:var(--t2)">Product</span><div class="fw" style="margin-top:2px"><?= h($tx["pname"]) ?></div></div>
      <div><span style="color:var(--t2)">Type</span><div style="margin-top:2px"><span class="badge <?= $tc[$tx["type"]] ?>"><?= $tx["type"] ?></span></div></div>
      <div><span style="color:var(--t2)">Quantity</span><div class="mono fw" style="margin-top:2px"><?= $sign.$tx["quantity"] ?></div></div>
      <div><span style="color:var(--t2)">Date</span><div style="margin-top:2px;color:var(--t2)"><?= date("d M Y H:i",strtotime($tx["created_at"])) ?></div></div>
      <div><span style="color:var(--t2)">Recorded by</span><div style="margin-top:2px"><?= h($tx["dby"]) ?></div></div>
    </div>
    <div class="alert alert-info" style="font-size:11px;margin-bottom:14px">&#8505; Quantity and type are locked for audit integrity. Only notes can be edited.</div>
    <form method="POST">
      <div class="fg"><label>Notes</label>
        <input type="text" name="notes" class="fc" value="<?= h($_POST["notes"]??$tx["notes"]) ?>" placeholder="e.g. Delivery batch #42 — corrected">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <a href="search.php" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary">Save notes &rarr;</button>
      </div>
    </form>
  </div>
</div>
<?php require_once "../includes/footer.php"; ?>