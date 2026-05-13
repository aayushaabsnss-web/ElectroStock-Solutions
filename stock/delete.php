<?php
/**
 * stock/delete.php — Delete all stock records for a product (Presentation Layer)
 * Removes all stock_movements for the product and resets quantity to 0.
 * Access: Store Owner only.
 */
require_once "../config/db.php";
require_once "../auth/session.php";
requireOwner();

$id = (int)($_GET["id"] ?? 0);
if($id){
    mysqli_query($conn,"DELETE FROM stock_movements WHERE product_id=$id");
    mysqli_query($conn,"UPDATE products SET quantity=0 WHERE id=$id");
    flash("success","All stock records cleared. Quantity reset to 0.");
}
header("Location: index.php"); exit;
