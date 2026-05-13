<?php
/**
 * products/add.php — Add New Product (Presentation Layer)
 * Product details only — name, SKU, category, price, supplier, description.
 * Stock quantity is managed through the Stock module.
 */
$t = "Add Product"; $a = "products";
require_once "../includes/header.php";
require_once "../classes/Product.php";
requireOwner();

$productObj = new Product($conn);
$err = "";

if($_SERVER["REQUEST_METHOD"]==="POST"){
    // Validate product fields only
    $errors = [];
    if(empty(trim($_POST["name"] ?? "")))     $errors[] = "Product name is required.";
    if(empty(trim($_POST["sku"]  ?? "")))     $errors[] = "SKU is required.";
    if(!in_array($_POST["category"] ?? "", ["iPhone","Mac","iPad","Watch","Accessory"])) $errors[] = "Please select a category.";
    if(empty($_POST["price"]) || (float)$_POST["price"] <= 0) $errors[] = "Price must be greater than \$0.";

    if(!$errors){
        // Add with quantity=0 and default min_qty — stock is managed separately
        $_POST["quantity"] = 0;
        $_POST["min_qty"]  = 5;
        if($productObj->add($_POST, $_SESSION["uid"])){
            flash("success","Product added to catalogue.");
            header("Location: index.php"); exit;
        } else {
            $errors[] = $productObj->isDuplicate() ? "A product with that name or SKU already exists." : "Database error.";
        }
    }
    $err = implode(" ", $errors);
}
?>
<?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
<div class="page-hdr">
  <a href="index.php" class="btn btn-outline btn-sm">&larr; Back</a>
  <h1>Add New Product</h1>
</div>
<div class="card" style="max-width:660px">
  <div class="card-hdr"><span class="card-title">Product details</span></div>
  <div class="card-body">
  <form method="POST">
    <div class="form2">
      <div class="fg">
        <label>Product name *</label>
        <input type="text" name="name" class="fc" value="<?= h($_POST["name"] ?? "") ?>" placeholder="e.g. iPhone 16 Pro Max 256GB" required>
      </div>
      <div class="fg">
        <label>SKU *</label>
        <input type="text" name="sku" class="fc" value="<?= h($_POST["sku"] ?? "") ?>" placeholder="e.g. IP16PM256" required>
      </div>
    </div>
    <div class="form2">
      <div class="fg">
        <label>Category *</label>
        <select name="category" class="fc" required>
          <option value="">Select category...</option>
          <?php foreach(["iPhone","Mac","iPad","Watch","Accessory"] as $c): ?>
          <option <?= ($_POST["category"] ?? "") === $c ? "selected" : "" ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg">
        <label>Price ($) *</label>
        <input type="number" name="price" step="0.01" min="0.01" class="fc" value="<?= h($_POST["price"] ?? "") ?>" required>
      </div>
    </div>
    <div class="fg">
      <label>Supplier</label>
      <input type="text" name="supplier" class="fc" value="<?= h($_POST["supplier"] ?? "Apple Inc.") ?>">
    </div>
    <div class="fg">
      <label>Description</label>
      <textarea name="description" class="fc"><?= h($_POST["description"] ?? "") ?></textarea>
    </div>
    <div class="alert alert-info" style="font-size:11px">&#8505; Stock quantity is managed through the <a href="<?= BASE ?>stock/index.php" style="color:inherit;font-weight:600">Stock module</a>.</div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
      <a href="index.php" class="btn btn-outline">Cancel</a>
      <button class="btn btn-primary">Add product &rarr;</button>
    </div>
  </form>
  </div>
</div>
<?php require_once "../includes/footer.php"; ?>
