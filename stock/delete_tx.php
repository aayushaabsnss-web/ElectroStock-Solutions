<?php
/**
 * stock/delete_tx.php — Delete a stock transaction (Presentation Layer)
 * Removes the transaction and reverses the quantity change on the product.
 * Fixed: now uses Stock::deleteById() instead of raw SQL (three-layer architecture)
 */
require_once "../config/db.php";
require_once "../auth/session.php";
require_once "../classes/Stock.php"; // Fixed: added Stock class include
requireOwner();

$id  = (int)($_GET["id"]  ?? 0);
$pid = (int)($_GET["pid"] ?? 0);
if($id){
    // Fixed: was raw SQL — now uses Stock class method
    if(Stock::deleteById($conn, $id)){
        flash("success","Transaction deleted and stock quantity reversed.");
    }
}
header("Location: view.php?id=$pid"); exit;