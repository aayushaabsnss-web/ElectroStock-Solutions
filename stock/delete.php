 <?php
/**
 * stock/delete.php — Delete Stock Transaction (Presentation Layer)
 * Permanently removes a stock transaction record.
 * Also reverses the quantity change on the product.
 * Access: Store Owner only.
 */
require_once "../config/db.php";
require_once "../auth/session.php";
requireOwner();

$id = (int)($_GET["id"] ?? 0);
if ($id) {
    // Get the transaction before deleting so we can reverse the quantity
    $tx = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM stock_movements WHERE id=$id"));
    if ($tx) {
        // Reverse the quantity change on the product
        $reverse = -$tx["quantity"];
        mysqli_query($conn, "UPDATE products SET quantity=quantity+($reverse) WHERE id={$tx["product_id"]}");
        // Delete the transaction record
        mysqli_query($conn, "DELETE FROM stock_movements WHERE id=$id");
        flash("success","Transaction deleted and stock quantity reversed.");
    }
}
header("Location: search.php"); exit;