<?php
/**
 * products/add.php — Add Product Form (Presentation Layer)
 * Validates input and delegates saving to Product class.
 * Calls stored procedure sp_addProduct via Product::add().
 * Access: Store Owner only.
 */
$t = 'Add Product'; $a = 'products';
require_once '../includes/header.php';
require_once '../classes/Product.php';
requireOwner();

$productObj = new Product($conn); // Middle layer
$err = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $errors = $productObj->validate($_POST); // Validate via class method
    if(!$errors){
        if($productObj->add($_POST, $_SESSION['uid'])){ // Save via stored procedure
            flash('success','Product added.'); header('Location: index.php'); exit;
        } else {
            $errors[] = $productObj->isDuplicate() ? 'Name or SKU already exists.' : 'DB error.';
        }
    }
    $err = implode(' ',$errors);
}
?>
<?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
<div class="page-hdr"><a href="index.php" class="btn btn-outline btn-sm">&larr; Back</a><h1>Add New Product</h1></div>
<div class="card" style="max-width:700px">
  <div class="card-hdr"><span class="card-title">Product details</span></div>
  <div class="card-body">
  <form method="POST">
    <div class="form2">
      <div class="fg"><label>Product name *</label><input type="text" name="name" class="fc" value="<?= h($_POST['name']??'') ?>" placeholder="e.g. iPhone 16 Pro Max 256GB" required></div>
      <div class="fg"><label>SKU *</label><input type="text" name="sku" class="fc" value="<?= h($_POST['sku']??'') ?>" placeholder="e.g. IP16PM256" required></div>
    </div>
    <div class="form2">
      <div class="fg"><label>Category *</label>
        <select name="category" class="fc" required><option value="">Select…</option>
          <?php foreach(['iPhone','Mac','iPad','Watch','Accessory'] as $c): ?>
          <option <?= ($_POST['category']??'')===$c?'selected':'' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="fg"><label>Supplier</label><input type="text" name="supplier" class="fc" value="<?= h($_POST['supplier']??'Apple Inc.') ?>"></div>
    </div>
    <div class="form3">
      <div class="fg"><label>Price ($) *</label><input type="number" name="price" step="0.01" min="0.01" class="fc" value="<?= h($_POST['price']??'') ?>" required></div>
      <div class="fg"><label>Initial quantity</label><input type="number" name="quantity" min="0" class="fc" value="<?= h($_POST['quantity']??'0') ?>"></div>
      <div class="fg"><label>Min stock level</label><input type="number" name="min_qty" min="1" class="fc" value="<?= h($_POST['min_qty']??'5') ?>"></div>
    </div>
    <div class="fg"><label>Description</label><textarea name="description" class="fc"><?= h($_POST['description']??'') ?></textarea></div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <a href="index.php" class="btn btn-outline">Cancel</a>
      <button class="btn btn-primary">Add product &rarr;</button>
    </div>
  </form>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>