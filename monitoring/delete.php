<?php
require_once "../config/db.php";
require_once "../auth/session.php";
require_once "../classes/Alert.php";
include "../includes/flash.php";
requireOwner();

$id = (int)($_GET["id"] ?? 0);
if ($id) {
    $ok = Alert::deleteById($conn, $id);
    if ($ok) {
        flash("success","Alert record deleted.");
    } else {
        flash("error","Only resolved alerts can be deleted. Please resolve it first.");
    }
}
header("Location: index.php"); exit;