<?php
/**
 * products/delete.php — Soft delete via static Product::delete()
 */
require_once "../config/db.php";
require_once "../auth/session.php";
require_once "../classes/Product.php";
requireOwner();
$id = (int)($_GET["id"] ?? 0);
if($id) Product::delete($conn, $id);
flash("success","Product removed from catalogue.");
header("Location: index.php"); exit;
