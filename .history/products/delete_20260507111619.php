<?php
/**
 * products/delete.php — Soft Delete Product
 * Sets is_active = 0 via Product::delete() (calls sp_deleteProduct).
 * Record is kept in database for audit purposes — never hard deleted.
 * Access: Store Owner only.
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../classes/Product.php';
requireOwner();

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $productObj = new Product($conn);
    $productObj->delete($id); // Soft delete via stored procedure sp_deleteProduct
    flash('success', 'Product removed from catalogue.');
}
header('Location: index.php'); exit;