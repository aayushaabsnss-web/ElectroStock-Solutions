<?php
/**
 * auth/create.php — Create New User (Presentation Layer)
 * Separate page for the user registration form.
 * Access: Store Owner only.
 */
require_once "../config/db.php";
require_once "../auth/session.php";

// Handle form submission before HTML output
if($_SERVER["REQUEST_METHOD"]==="POST"){
    requireOwner();
    $name  = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"]     ?? "");
    $pass  = $_POST["password"]       ?? "";
    $role  = $_POST["role"]           ?? "employee";
    $errs  = [];
    if(!$name)  $errs[] = "Full name is required.";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = "Valid email is required.";
    if(strlen($pass) < 6) $errs[] = "Password must be at least 6 characters.";
    if(!$errs){
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $st   = mysqli_prepare($conn,"INSERT INTO users(full_name,email,password_hash,role)VALUES(?,?,?,?)");
        mysqli_stmt_bind_param($st,"ssss",$name,$email,$hash,$role);
        if(mysqli_stmt_execute($st)){
            flash("success","Account created for $name.");
            header("Location: register.php"); exit;
        } else {
            $errs[] = mysqli_errno($conn)===1062 ? "That email is already registered." : "Database error.";
        }
    }
    $err = implode(" ", $errs);
}

$t = "Create User"; $a = "users";
require_once "../includes/header.php";
include  "../includes/flash.php";
$err = $err ?? "";
?>

<?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>

<div class="page-hdr">
  <a href="register.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1>Create New User</h1>
</div>

<div class="card" style="max-width:500px">
  <div class="card-hdr"><span class="card-title">Account details</span></div>
  <div class="card-body">
  <form method="POST">
    <div class="fg">
      <label>Full name *</label>
      <input type="text" name="full_name" class="fc" value="<?= h($_POST["full_name"] ?? "") ?>" placeholder="e.g. John Smith" required>
    </div>
    <div class="fg">
      <label>Email address *</label>
      <input type="email" name="email" class="fc" value="<?= h($_POST["email"] ?? "") ?>" placeholder="e.g. john@electrostock.com" required>
    </div>
    <div class="fg">
      <label>Password *</label>
      <input type="password" name="password" class="fc" placeholder="Minimum 6 characters" required>
    </div>
    <div class="fg">
      <label>Role *</label>
      <select name="role" class="fc">
        <option value="employee">Employee</option>
        <option value="store_owner">Store Owner</option>
      </select>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
      <a href="register.php" class="btn btn-outline">Cancel</a>
      <button class="btn btn-primary">Create account &rarr;</button>
    </div>
  </form>
  </div>
</div>
<?php require_once "../includes/footer.php"; ?>
