<?php
/**
 * products/edit.php — Edit Product (Presentation Layer)
 * Fetches a single Product object via Product::getById().
 * Accesses existing values via getter methods to pre-fill the form.
 */
$t = "Edit Product"; $a = "products";
require_once "../includes/header.php";
require_once "../classes/Product.php";
requireOwner();

$id = (int)($_GET["id"] ?? 0);
// Returns a Product object (or null)
$product = Product::getById($conn, $id);
if(!$product){ flash("error","Product not found."); header("Location: index.php"); exit; }

$err = "";
if($_SERVER["REQUEST_METHOD"]==="POST"){
    $errors = [];
    if(empty(trim($_POST["name"]??"")))  $errors[] = "Name required.";
    if(empty(trim($_POST["sku"] ??"")))  $errors[] = "SKU required.";
    if((float)($_POST["price"]??0) <= 0) $errors[] = "Price must be > \$0.";
    if(!$errors){
        $_POST["min_qty"] = $product->getMinQty(); // Preserve existing min_qty
        Product::update($conn, $id, $_POST);
        flash("success","Product updated.");
        header("Location: index.php"); exit;
    }
    $err = implode(" ", $errors);
}
?>
<?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
<div class="page-hdr">
  <a href="index.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1>Edit Product</h1>
</div>
<div class="card" style="max-width:660px">
  <!-- Access product name via getter method -->
  <div class="card-hdr"><span class="card-title"><?= h($product->getName()) ?></span></div>
  <div class="card-body">
  <form method="POST">
    <div class="form2">
      <div class="fg"><label>Product name *</label>
        <!-- Pre-fill form using getter methods on the Product object -->
        <input type="text" name="name" class="fc" value="<?= h($_POST["name"] ?? $product->getName()) ?>" required></div>
      <div class="fg"><label>SKU *</label>
        <input type="text" name="sku" class="fc" value="<?= h($_POST["sku"] ?? $product->getSku()) ?>" required></div>
    </div>
    <div class="form2">
      <div class="fg"><label>Category *</label>
        <select name="category" class="fc">
          <?php foreach(["iPhone","Mac","iPad","Watch","Accessory"] as $c): ?>
          <!-- Compare with getter method -->
          <option <?= $product->getCategory()===$c?"selected":"" ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="fg"><label>Price ($) *</label>
        <input type="number" name="price" step="0.01" min="0.01" class="fc"
               value="<?= h($_POST["price"] ?? $product->getPrice()) ?>" required></div>
    </div>
    <div class="fg"><label>Supplier</label>
      <input type="text" name="supplier" class="fc" value="<?= h($_POST["supplier"] ?? $product->getSupplier()) ?>"></div>
    <div class="fg"><label>Description</label>
      <textarea name="description" class="fc"><?= h($_POST["description"] ?? $product->getDescription()) ?></textarea></div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <a href="index.php" class="btn btn-outline">Cancel</a>
      <button class="btn btn-primary">Save changes &rarr;</button>
    </div>
  </form>
  </div>
</div>
<?php require_once "../includes/footer.php"; ?>
