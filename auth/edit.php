<?php
/**
 * auth/edit.php — Edit User Account (Presentation Layer)
 * Allows Store Owner to update a user's name, email and role.
 * Access: Store Owner only.
 */
$t = "Edit User"; $a = "users";
require_once "../includes/header.php";
requireOwner();

$id = (int)($_GET["id"] ?? 0);
if (!$id) { flash("error","User not found."); header("Location: register.php"); exit; }

// Fetch user to edit
$u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$id"));
if (!$u) { flash("error","User not found."); header("Location: register.php"); exit; }

$err = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name  = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $role  = $_POST["role"] ?? "employee";
    $errs  = [];
    if (!$name)  $errs[] = "Full name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = "Valid email required.";
    if (!in_array($role, ["store_owner","employee"])) $errs[] = "Invalid role.";
    if (!$errs) {
        // Update password if provided
        if (!empty($_POST["password"])) {
            if (strlen($_POST["password"]) < 6) {
                $errs[] = "Password must be at least 6 characters.";
            } else {
                $hash = password_hash($_POST["password"], PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE users SET full_name=?,email=?,role=?,password_hash=? WHERE id=?");
                mysqli_stmt_bind_param($stmt,"ssssi",$name,$email,$role,$hash,$id);
                mysqli_stmt_execute($stmt);
            }
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET full_name=?,email=?,role=? WHERE id=?");
            mysqli_stmt_bind_param($stmt,"sssi",$name,$email,$role,$id);
            mysqli_stmt_execute($stmt);
        }
        if (!$errs) { flash("success","User updated successfully."); header("Location: register.php"); exit; }
    }
    $err = implode(" ", $errs);
}
?>
<?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
<div class="page-hdr">
  <a href="register.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1>Edit User</h1>
</div>
<div class="card" style="max-width:500px">
  <div class="card-hdr"><span class="card-title"><?= h($u["full_name"]) ?></span></div>
  <div class="card-body">
  <form method="POST">
    <div class="fg"><label>Full name *</label>
      <input type="text" name="full_name" class="fc" value="<?= h($_POST["full_name"]??$u["full_name"]) ?>" required></div>
    <div class="fg"><label>Email address *</label>
      <input type="email" name="email" class="fc" value="<?= h($_POST["email"]??$u["email"]) ?>" required></div>
    <div class="fg"><label>Role *</label>
      <select name="role" class="fc">
        <option value="employee" <?= $u["role"]==="employee"?"selected":"" ?>>Employee</option>
        <option value="store_owner" <?= $u["role"]==="store_owner"?"selected":"" ?>>Store Owner</option>
      </select></div>
    <div class="fg"><label>New password <span style="color:var(--t2);font-weight:400">(leave blank to keep current)</span></label>
      <input type="password" name="password" class="fc" placeholder="Min 6 characters"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:6px">
      <a href="register.php" class="btn btn-outline">Cancel</a>
      <button class="btn btn-primary">Save changes &rarr;</button>
    </div>
  </form>
  </div>
</div>
<?php require_once "../includes/footer.php"; ?>