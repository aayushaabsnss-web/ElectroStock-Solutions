<?php
require_once '../config/db.php';
require_once '../auth/session.php';
requireOwner();
$t='Manage Users'; $a='users';
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['reg'])) {
    $n=trim($_POST['full_name']??''); $e=trim($_POST['email']??'');
    $p=$_POST['password']??''; $r=$_POST['role']??'employee';
    $errs=[];
    if(!$n) $errs[]='Name required.';
    if(!filter_var($e,FILTER_VALIDATE_EMAIL)) $errs[]='Valid email required.';
    if(strlen($p)<6) $errs[]='Password min 6 chars.';
    if($errs) { flash('error',implode(' ',$errs)); }
    else {
        $h=password_hash($p,PASSWORD_DEFAULT);
        $s=mysqli_prepare($conn,"INSERT INTO users(full_name,email,password_hash,role)VALUES(?,?,?,?)");
        mysqli_stmt_bind_param($s,'ssss',$n,$e,$h,$r);
        mysqli_stmt_execute($s)?flash('success',"Account created for $n."):flash('error',(mysqli_errno($conn)===1062)?'Email already used.':'DB error.');
    }
    header('Location: register.php'); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['deactivate'])) {
    $uid=(int)$_POST['uid'];
    if($uid && $uid!==$_SESSION['uid']) mysqli_query($conn,"UPDATE users SET is_active=0 WHERE id=$uid");
    flash('success','Account deactivated.'); header('Location: register.php'); exit;
}
$users=mysqli_query($conn,"SELECT * FROM users ORDER BY created_at DESC");
?>
<?php include '../includes/flash.php'; ?>
<div class="page-hdr"><h1>Manage Users</h1><a href="search.php" class="btn btn-outline btn-sm">Search users</a></div>
<div class="g2">
<div class="card">
  <div class="card-hdr"><span class="card-title">Register account</span></div>
  <div class="card-body">
  <form method="POST">
    <div class="fg"><label>Full name</label><input type="text" name="full_name" class="fc" required></div>
    <div class="fg"><label>Email</label><input type="email" name="email" class="fc" required></div>
    <div class="fg"><label>Password</label><input type="password" name="password" class="fc" required></div>
    <div class="fg"><label>Role</label>
      <select name="role" class="fc">
        <option value="employee">Employee</option>
        <option value="store_owner">Store Owner</option>
      </select></div>
    <button name="reg" class="btn btn-primary w100">Create account</button>
  </form>
  </div>
</div>
<div class="card">
  <div class="card-hdr"><span class="card-title">All users</span></div>
  <table class="tbl">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php while($u=mysqli_fetch_assoc($users)): ?>
    <tr>
      <td class="fw"><?= h($u['full_name']) ?></td>
      <td class="mono muted"><?= h($u['email']) ?></td>
      <td><span class="badge <?= $u['role']==='store_owner'?'b-blue':'b-gray' ?>"><?= $u['role']==='store_owner'?'Owner':'Employee' ?></span></td>
      <td><span class="badge <?= $u['is_active']?'b-green':'b-red' ?>"><?= $u['is_active']?'Active':'Inactive' ?></span></td>
      <td><div style="display:flex;gap:5px"><a href="view.php?id=<?= $u['id'] ?>" class="icon-btn" title="View">&#128065;</a><a href="edit.php?id=<?= $u['id'] ?>" class="icon-btn" title="Edit">&#9998;</a><?php if($u['is_active']&&$u['id']!=$_SESSION['uid']): ?>
        <form method="POST"><input type="hidden" name="uid" value="<?= $u['id'] ?>">
        <button name="deactivate" class="btn btn-sm btn-danger" onclick="return confirm('Deactivate?')">Deactivate</button></form><?php endif; ?></div></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
</div>
<?php require_once '../includes/footer.php'; ?>