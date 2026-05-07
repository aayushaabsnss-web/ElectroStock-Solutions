<?php
/**
 * products/edit.php — Edit Product (Presentation Layer)
 * Fetches product via Product::getById(), saves via Product::update().
 * Calls stored procedure sp_updateProduct via Product class.
 * Access: Store Owner only.
 */
$t = 'Edit Product'; $a = 'products';
require_once '../includes/header.php';
require_once '../classes/Product.php';
requireOwner();

$productObj = new Product($conn); // Middle layer
$id = (int)($_GET['id'] ?? 0);

// Fetch the product to edit — redirect if not found
$p = $productObj->getById($id);
if (!$p) { flash('error','Product not found.'); header('Location: index.php'); exit; }

$err = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $errors = [];
    if(empty(trim($_POST['name']??''))) $errors[] = 'Name required.';
    if(empty($_POST['sku'])) $errors[] = 'SKU required.';
    if((float)($_POST['price']??0) <= 0) $errors[] = 'Price must be > 0.';
    if(!$errors){
        // Update via Product class (calls sp_updateProduct)
        $productObj->update($id, $_POST);
        flash('success','Product updated.'); header('Location: index.php'); exit;
    }
    $err = implode(' ',$errors);
}
?>
<?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
<div class="page-hdr"><a href="index.php" class="btn btn-outline btn-sm">&larr; Back</a><h1>Edit Product</h1></div>
<div class="card" style="max-width:700px">
  <div class="card-hdr"><span class="card-title"><?= h($p['name']) ?></span></div>
  <div class="card-body">
  <form method="POST">
    <div class="form2">
      <div class="fg"><label>Product name *</label><input type="text" name="name" class="fc" value="<?= h($_POST['name']??$p['name']) ?>" required></div>
      <div class="fg"><label>SKU *</label><input type="text" name="sku" class="fc" value="<?= h($_POST['sku']??$p['sku']) ?>" required></div>
    </div>
    <div class="form2">
      <div class="fg"><label>Category *</label>
        <select name="category" class="fc">
          <?php foreach(['iPhone','Mac','iPad','Watch','Accessory'] as $c): ?>
          <option <?= $p['category']===$c?'selected':'' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="fg"><label>Supplier</label><input type="text" name="supplier" class="fc" value="<?= h($_POST['supplier']??$p['supplier']) ?>"></div>
    </div>
    <div class="form2">
      <div class="fg"><label>Price ($) *</label><input type="number" name="price" step="0.01" min="0.01" class="fc" value="<?= h($_POST['price']??$p['price']) ?>" required></div>
      <div class="fg"><label>Min stock level</label><input type="number" name="min_qty" min="1" class="fc" value="<?= h($_POST['min_qty']??$p['min_qty']) ?>"></div>
    </div>
    <div class="fg"><label>Description</label><textarea name="description" class="fc"><?= h($_POST['description']??$p['description']) ?></textarea></div>
    <div class="alert alert-info" style="font-size:11px">&#8505; Current stock: <strong><?= $p['quantity'] ?> units</strong>. Use the Stock module to update quantity.</div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <a href="index.php" class="btn btn-outline">Cancel</a>
      <button class="btn btn-primary">Save changes &rarr;</button>
    </div>
  </form>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>