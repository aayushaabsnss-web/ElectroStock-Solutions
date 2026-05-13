<?php
/**
 * stock/delete_tx.php — Delete a stock transaction (Presentation Layer)
 * Removes the transaction and reverses the quantity change on the product.
 */
require_once "../config/db.php";
require_once "../auth/session.php";
requireOwner();

$id  = (int)($_GET["id"]  ?? 0);
$pid = (int)($_GET["pid"] ?? 0);
if($id){
    $tx = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM stock_movements WHERE id=$id"));
    if($tx){
        $reverse = -$tx["quantity"];
        mysqli_query($conn,"UPDATE products SET quantity=quantity+($reverse) WHERE id={$tx["product_id"]}");
        mysqli_query($conn,"DELETE FROM stock_movements WHERE id=$id");
        flash("success","Transaction deleted and stock quantity reversed.");
    }
}
header("Location: view.php?id=$pid"); exit;
