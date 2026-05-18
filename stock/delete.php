<?php
/**
 * stock/delete.php — Delete all stock records for a product (Presentation Layer)
 * Removes all stock_movements for the product and resets quantity to 0.
 * Access: Store Owner only.
 */
require_once "../config/db.php";
require_once "../auth/session.php";
require_once "../classes/Stock.php";
requireOwner();

$id = (int)($_GET["id"] ?? 0);
if($id){
    Stock::deleteAllByProduct($conn, $id);
    flash("success","All stock records cleared. Quantity reset to 0.");
}
header("Location: index.php"); exit;