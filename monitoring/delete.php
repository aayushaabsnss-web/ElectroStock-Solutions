<?php
/**
 * monitoring/delete.php — Delete Alert Record (Presentation Layer)
 * Permanently removes a resolved alert from the monitoring table.
 * Only resolved alerts can be deleted — active alerts must be resolved first.
 * Access: Store Owner only.
 */
require_once "../config/db.php";
require_once "../auth/session.php";
requireOwner();

$id = (int)($_GET["id"] ?? 0);
if ($id) {
    // Only allow deletion of resolved alerts — active alerts must be resolved first
    $al = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT alert_status FROM monitoring WHERE id=$id"));
    if ($al && $al["alert_status"] === "resolved") {
        mysqli_query($conn, "DELETE FROM monitoring WHERE id=$id");
        flash("success","Alert record deleted.");
    } else {
        flash("error","Only resolved alerts can be deleted. Please resolve it first.");
    }
}
header("Location: index.php"); exit;