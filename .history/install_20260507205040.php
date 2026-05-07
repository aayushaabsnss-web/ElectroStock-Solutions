<?php
/**
 * install.php — ElectroStock Solutions One-Click Installer
 * ─────────────────────────────────────────────────────────
 * Run this file ONCE in your browser after copying ESS to htdocs.
 * It creates the database, all tables, stored procedures,
 * seed data and user accounts with freshly hashed passwords.
 *
 * URL: http://localhost/ESS/install.php
 *
 * DELETE THIS FILE after installation is complete.
 */

// ── CONFIGURATION ─────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // change if your MySQL username is different
define('DB_PASS', '');           // change if you have a MySQL password set
define('DB_NAME', 'inventory_db');
define('BASE',    '/ESS/');

$errors   = [];
$messages = [];

// ── CONNECT (without selecting a database yet) ────────
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
if (!$conn) {
    die('<div style="font-family:Arial;padding:40px;background:#1a1a2e;color:#ff6b6b;text-align:center">
        <h2>Cannot connect to MySQL</h2>
        <p>' . mysqli_connect_error() . '</p>
        <p>Edit DB_USER and DB_PASS at the top of install.php to match your XAMPP settings.</p>
    </div>');
}

// ── CREATE DATABASE ───────────────────────────────────
if (mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `inventory_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    $messages[] = "✓ Database 'inventory_db' created (or already exists).";
} else {
    $errors[] = "✗ Could not create database: " . mysqli_error($conn);
}

mysqli_select_db($conn, DB_NAME);
mysqli_set_charset($conn, 'utf8mb4');

// ── CREATE TABLES ─────────────────────────────────────
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        full_name     VARCHAR(100) NOT NULL,
        email         VARCHAR(150) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role          ENUM('store_owner','employee') NOT NULL DEFAULT 'employee',
        is_active     TINYINT(1) NOT NULL DEFAULT 1,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "products" => "CREATE TABLE IF NOT EXISTS products (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(150) NOT NULL UNIQUE,
        sku         VARCHAR(40)  NOT NULL UNIQUE,
        category    ENUM('iPhone','Mac','iPad','Watch','Accessory') NOT NULL,
        description TEXT,
        price       DECIMAL(10,2) NOT NULL,
        quantity    INT NOT NULL DEFAULT 0,
        min_qty     INT NOT NULL DEFAULT 5,
        supplier    VARCHAR(100) DEFAULT 'Apple Inc.',
        is_active   TINYINT(1) NOT NULL DEFAULT 1,
        added_by    INT,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL
    )",
    "stock_movements" => "CREATE TABLE IF NOT EXISTS stock_movements (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        type       ENUM('IN','OUT','ADJUSTMENT') NOT NULL,
        quantity   INT NOT NULL,
        notes      VARCHAR(255),
        moved_by   INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (moved_by)   REFERENCES users(id)    ON DELETE CASCADE
    )",
    "monitoring" => "CREATE TABLE IF NOT EXISTS monitoring (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        product_id   INT NOT NULL UNIQUE,
        threshold    INT NOT NULL DEFAULT 5,
        alert_status ENUM('active','resolved') NOT NULL DEFAULT 'active',
        alerted_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at  TIMESTAMP NULL,
        resolved_by  INT,
        FOREIGN KEY (product_id)  REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (resolved_by) REFERENCES users(id)    ON DELETE SET NULL
    )",
    "orders" => "CREATE TABLE IF NOT EXISTS orders (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(20)  NOT NULL UNIQUE,
        customer     VARCHAR(120) NOT NULL,
        status       ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
        total        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        notes        TEXT,
        created_by   INT NOT NULL,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )",
    "order_items" => "CREATE TABLE IF NOT EXISTS order_items (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        order_id   INT NOT NULL,
        product_id INT NOT NULL,
        quantity   INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )",
];

foreach ($tables as $name => $sql) {
    if (mysqli_query($conn, $sql)) {
        $messages[] = "✓ Table '$name' created.";
    } else {
        $errors[] = "✗ Table '$name' failed: " . mysqli_error($conn);
    }
}

// ── DROP AND RECREATE STORED PROCEDURES ───────────────
$procedures = [
    "sp_getAllProducts",
    "sp_getProductById",
    "sp_searchProducts",
    "sp_addProduct",
    "sp_updateProduct",
    "sp_deleteProduct",
    "sp_addStockMovement",
    "sp_getStockHistory",
    "sp_getActiveAlerts",
    "sp_resolveAlert",
    "sp_createOrder",
    "sp_updateOrderStatus",
    "sp_getDashboardStats",
    "sp_getLowStockReport",
    "sp_getStockValueReport",
    "sp_getOrderSummaryReport",
];
foreach ($procedures as $p) {
    mysqli_query($conn, "DROP PROCEDURE IF EXISTS $p");
}

$procs = [
"sp_getAllProducts" => "CREATE PROCEDURE sp_getAllProducts()
BEGIN
  SELECT p.*, u.full_name AS added_by_name,
    CASE WHEN p.quantity=0 THEN 'Out of Stock' WHEN p.quantity<=p.min_qty THEN 'Low Stock' ELSE 'In Stock' END AS stock_status
  FROM products p LEFT JOIN users u ON u.id=p.added_by WHERE p.is_active=1 ORDER BY p.created_at DESC;
END",

"sp_getProductById" => "CREATE PROCEDURE sp_getProductById(IN p_id INT)
BEGIN
  SELECT p.*, u.full_name AS added_by_name FROM products p LEFT JOIN users u ON u.id=p.added_by WHERE p.id=p_id AND p.is_active=1;
END",

"sp_searchProducts" => "CREATE PROCEDURE sp_searchProducts(IN p_query VARCHAR(150),IN p_category VARCHAR(50),IN p_status VARCHAR(20))
BEGIN
  SELECT p.*, u.full_name AS added_by_name,
    CASE WHEN p.quantity=0 THEN 'Out of Stock' WHEN p.quantity<=p.min_qty THEN 'Low Stock' ELSE 'In Stock' END AS stock_status
  FROM products p LEFT JOIN users u ON u.id=p.added_by
  WHERE p.is_active=1
    AND (p_query IS NULL OR p.name LIKE CONCAT('%',p_query,'%') OR p.sku LIKE CONCAT('%',p_query,'%'))
    AND (p_category IS NULL OR p.category=p_category)
    AND (p_status IS NULL
      OR (p_status='in'  AND p.quantity>p.min_qty)
      OR (p_status='low' AND p.quantity>0 AND p.quantity<=p.min_qty)
      OR (p_status='out' AND p.quantity=0))
  ORDER BY p.name;
END",

"sp_addProduct" => "CREATE PROCEDURE sp_addProduct(IN p_name VARCHAR(150),IN p_sku VARCHAR(40),IN p_category VARCHAR(20),IN p_desc TEXT,IN p_price DECIMAL(10,2),IN p_quantity INT,IN p_min_qty INT,IN p_supplier VARCHAR(100),IN p_added_by INT)
BEGIN
  DECLARE v_new_id INT;
  INSERT INTO products(name,sku,category,description,price,quantity,min_qty,supplier,added_by)
  VALUES(p_name,p_sku,p_category,p_desc,p_price,p_quantity,p_min_qty,p_supplier,p_added_by);
  SET v_new_id=LAST_INSERT_ID();
  IF p_quantity>0 THEN INSERT INTO stock_movements(product_id,type,quantity,moved_by,notes) VALUES(v_new_id,'IN',p_quantity,p_added_by,'Initial stock'); END IF;
  IF p_quantity<=p_min_qty THEN INSERT IGNORE INTO monitoring(product_id,threshold) VALUES(v_new_id,p_min_qty); END IF;
  SELECT v_new_id AS new_id;
END",

"sp_updateProduct" => "CREATE PROCEDURE sp_updateProduct(IN p_id INT,IN p_name VARCHAR(150),IN p_sku VARCHAR(40),IN p_category VARCHAR(20),IN p_desc TEXT,IN p_price DECIMAL(10,2),IN p_min_qty INT,IN p_supplier VARCHAR(100))
BEGIN
  UPDATE products SET name=p_name,sku=p_sku,category=p_category,description=p_desc,price=p_price,min_qty=p_min_qty,supplier=p_supplier WHERE id=p_id AND is_active=1;
END",

"sp_deleteProduct" => "CREATE PROCEDURE sp_deleteProduct(IN p_id INT)
BEGIN UPDATE products SET is_active=0 WHERE id=p_id; END",

"sp_addStockMovement" => "CREATE PROCEDURE sp_addStockMovement(IN p_product_id INT,IN p_type VARCHAR(20),IN p_quantity INT,IN p_moved_by INT,IN p_notes VARCHAR(255),OUT p_new_qty INT,OUT p_error VARCHAR(255))
BEGIN
  DECLARE v_current INT; DECLARE v_min INT; DECLARE v_change INT;
  SELECT quantity,min_qty INTO v_current,v_min FROM products WHERE id=p_product_id AND is_active=1;
  SET v_change=IF(p_type='OUT',-p_quantity,p_quantity);
  SET p_new_qty=v_current+v_change;
  IF p_new_qty<0 THEN SET p_error=CONCAT('Cannot reduce below zero. Current stock: ',v_current);
  ELSE
    INSERT INTO stock_movements(product_id,type,quantity,moved_by,notes) VALUES(p_product_id,p_type,v_change,p_moved_by,p_notes);
    UPDATE products SET quantity=p_new_qty WHERE id=p_product_id;
    IF p_new_qty<=v_min THEN
      INSERT IGNORE INTO monitoring(product_id,threshold) VALUES(p_product_id,v_min);
      UPDATE monitoring SET alert_status='active',alerted_at=NOW(),resolved_at=NULL,resolved_by=NULL WHERE product_id=p_product_id AND alert_status='resolved';
    END IF;
    SET p_error=NULL;
  END IF;
END",

"sp_getStockHistory" => "CREATE PROCEDURE sp_getStockHistory(IN p_product_id INT,IN p_type VARCHAR(20),IN p_date_from DATE,IN p_date_to DATE)
BEGIN
  SELECT sm.*,p.name AS product_name,p.sku,u.full_name AS moved_by_name
  FROM stock_movements sm JOIN products p ON p.id=sm.product_id JOIN users u ON u.id=sm.moved_by
  WHERE (p_product_id IS NULL OR sm.product_id=p_product_id)
    AND (p_type IS NULL OR sm.type=p_type)
    AND (p_date_from IS NULL OR DATE(sm.created_at)>=p_date_from)
    AND (p_date_to IS NULL OR DATE(sm.created_at)<=p_date_to)
  ORDER BY sm.created_at DESC;
END",

"sp_getActiveAlerts" => "CREATE PROCEDURE sp_getActiveAlerts()
BEGIN
  SELECT m.*,p.name AS product_name,p.sku,p.quantity AS current_qty,(m.threshold-p.quantity) AS shortfall
  FROM monitoring m JOIN products p ON p.id=m.product_id WHERE m.alert_status='active' ORDER BY m.alerted_at DESC;
END",

"sp_resolveAlert" => "CREATE PROCEDURE sp_resolveAlert(IN p_alert_id INT,IN p_user_id INT)
BEGIN UPDATE monitoring SET alert_status='resolved',resolved_at=NOW(),resolved_by=p_user_id WHERE id=p_alert_id AND alert_status='active'; END",

"sp_createOrder" => "CREATE PROCEDURE sp_createOrder(IN p_number VARCHAR(20),IN p_customer VARCHAR(120),IN p_notes TEXT,IN p_by INT,OUT p_order_id INT)
BEGIN INSERT INTO orders(order_number,customer,notes,created_by) VALUES(p_number,p_customer,p_notes,p_by); SET p_order_id=LAST_INSERT_ID(); END",

"sp_updateOrderStatus" => "CREATE PROCEDURE sp_updateOrderStatus(IN p_id INT,IN p_status VARCHAR(20))
BEGIN UPDATE orders SET status=p_status WHERE id=p_id; END",

"sp_getDashboardStats" => "CREATE PROCEDURE sp_getDashboardStats()
BEGIN
  SELECT
    (SELECT COUNT(*) FROM products WHERE is_active=1) AS total_products,
    (SELECT COUNT(*) FROM products WHERE is_active=1 AND quantity>min_qty) AS in_stock,
    (SELECT COUNT(*) FROM products WHERE is_active=1 AND quantity>0 AND quantity<=min_qty) AS low_stock,
    (SELECT COUNT(*) FROM products WHERE is_active=1 AND quantity=0) AS out_of_stock,
    (SELECT COUNT(*) FROM orders WHERE status='pending') AS pending_orders,
    (SELECT COUNT(*) FROM monitoring WHERE alert_status='active') AS active_alerts,
    (SELECT COALESCE(SUM(price*quantity),0) FROM products WHERE is_active=1) AS total_inventory_value;
END",

"sp_getLowStockReport" => "CREATE PROCEDURE sp_getLowStockReport()
BEGIN SELECT p.name,p.sku,p.category,p.quantity,p.min_qty,(p.min_qty-p.quantity) AS shortfall,p.supplier FROM products p WHERE p.is_active=1 AND p.quantity<=p.min_qty ORDER BY shortfall DESC; END",

"sp_getStockValueReport" => "CREATE PROCEDURE sp_getStockValueReport()
BEGIN SELECT p.name,p.sku,p.category,p.price,p.quantity,(p.price*p.quantity) AS total_value FROM products p WHERE p.is_active=1 ORDER BY total_value DESC; END",

"sp_getOrderSummaryReport" => "CREATE PROCEDURE sp_getOrderSummaryReport()
BEGIN SELECT status,COUNT(*) AS order_count,SUM(total) AS total_revenue FROM orders GROUP BY status ORDER BY FIELD(status,'completed','processing','pending','cancelled'); END",
];

foreach ($procs as $name => $sql) {
    if (mysqli_query($conn, $sql)) {
        $messages[] = "✓ Procedure '$name' created.";
    } else {
        $errors[] = "✗ Procedure '$name' failed: " . mysqli_error($conn);
    }
}

// ── CREATE USER ACCOUNTS ──────────────────────────────
// Hash is generated fresh on THIS machine — guaranteed to work
$ownerHash = password_hash('Admin@123', PASSWORD_DEFAULT);
$staffHash = password_hash('Admin@123', PASSWORD_DEFAULT);

$users = [
    ['Store Owner', 'owner@electrostock.com', $ownerHash, 'store_owner'],
    ['Alex Johnson', 'staff@electrostock.com', $staffHash, 'employee'],
];
foreach ($users as [$name, $email, $hash, $role]) {
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO users(full_name,email,password_hash,role) VALUES(?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $hash, $role);
    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        $messages[] = "✓ User '$email' created.";
    } else {
        // User already exists — update the hash
        $s2 = mysqli_prepare($conn, "UPDATE users SET password_hash=? WHERE email=?");
        mysqli_stmt_bind_param($s2, 'ss', $hash, $email);
        mysqli_stmt_execute($s2);
        $messages[] = "✓ User '$email' password updated.";
    }
}

// ── SEED PRODUCTS ─────────────────────────────────────
$existing = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products"))['c']);
if ($existing === 0) {
    $products = [
        ['iPhone 16 Pro Max 256GB','IP16PM256','iPhone',1199.00,24,8],
        ['iPhone 16 Pro 128GB','IP16P128','iPhone',999.00,18,8],
        ['iPhone 15 128GB','IP15128','iPhone',799.00,35,10],
        ['iPhone 14 128GB','IP14128','iPhone',699.00,4,8],
        ['MacBook Pro 16 M4 Pro','MBP16M4P','Mac',2499.00,6,3],
        ['MacBook Pro 14 M4','MBP14M4','Mac',1999.00,11,3],
        ['MacBook Air 15 M3','MBA15M3','Mac',1299.00,20,5],
        ['Mac mini M4','MM4','Mac',599.00,9,5],
        ['iPad Pro 13 M4','IPADP13M4','iPad',1299.00,5,3],
        ['iPad Air 11 M2','IPADA11M2','iPad',749.00,14,5],
        ['iPad 10th Gen 64GB','IPAD10G64','iPad',449.00,22,8],
        ['iPad mini 7th Gen','IPADMINI7','iPad',499.00,3,5],
        ['Apple Watch Series 10','AWS10GPS','Watch',399.00,28,10],
        ['Apple Watch Ultra 2','AWULTRA2','Watch',799.00,6,3],
        ['Apple Watch SE 2','AWSE2','Watch',249.00,19,8],
        ['AirPods Pro 2nd Gen','APP2','Accessory',249.00,2,8],
        ['AirPods 4','AP4','Accessory',129.00,31,10],
        ['Apple Pencil Pro','APENCILP','Accessory',129.00,12,5],
        ['MagSafe Charger 15W','MAGSAFE','Accessory',39.00,45,15],
        ['USB-C Cable Woven 1m','USBC1M','Accessory',29.00,4,15],
    ];
    $uid = 1;
    $s = mysqli_prepare($conn,
        "INSERT IGNORE INTO products(name,sku,category,price,quantity,min_qty,added_by) VALUES(?,?,?,?,?,?,?)");
    foreach ($products as [$n,$sku,$cat,$p,$q,$m]) {
        mysqli_stmt_bind_param($s,'sssdiib',$n,$sku,$cat,$p,$q,$m,$uid);
        // fix type
        mysqli_stmt_bind_param($s,'sssdiii',$n,$sku,$cat,$p,$q,$m,$uid);
        mysqli_stmt_execute($s);
    }
    $messages[] = "✓ 20 Apple products seeded.";

    // Seed alerts for low-stock products
    mysqli_query($conn,"INSERT IGNORE INTO monitoring(product_id,threshold) SELECT id,min_qty FROM products WHERE quantity<=min_qty AND is_active=1");
    $messages[] = "✓ Low-stock alerts seeded.";
} else {
    $messages[] = "ℹ Products already exist — skipping seed.";
}

// ── DONE ──────────────────────────────────────────────
$success = empty($errors);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ESS Installer</title>
<style>
*{box-sizing:border-box}
body{font-family:Arial,sans-serif;background:#0D1117;color:#F0F6FC;margin:0;padding:40px 20px;min-height:100vh}
.card{max-width:680px;margin:0 auto;background:#161B26;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:32px;box-shadow:0 24px 60px rgba(0,0,0,.4)}
h1{font-size:22px;margin-bottom:4px;color:#F0F6FC}
.sub{font-size:13px;color:#8B949E;margin-bottom:28px}
.msg{display:flex;gap:10px;padding:8px 12px;border-radius:8px;font-size:12px;margin-bottom:6px;border:1px solid}
.ok{background:rgba(63,185,80,.1);color:#7ee787;border-color:rgba(63,185,80,.28)}
.err{background:rgba(248,81,73,.1);color:#ffa198;border-color:rgba(248,81,73,.28)}
.info{background:rgba(26,110,224,.1);color:#79c0ff;border-color:rgba(26,110,224,.28)}
.login-box{background:rgba(63,185,80,.08);border:1px solid rgba(63,185,80,.28);border-radius:10px;padding:20px;margin:24px 0}
.login-box h2{font-size:15px;color:#7ee787;margin-bottom:14px}
table{width:100%;border-collapse:collapse;font-size:13px}
td{padding:8px 12px;border-bottom:1px solid rgba(255,255,255,.07)}
td:first-child{color:#8B949E;width:40%}
td:last-child{font-weight:500;font-family:monospace}
.btn{display:inline-block;background:#1A6EE0;color:#fff;padding:11px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:500;margin-top:20px}
.warn{background:rgba(248,81,73,.08);border:1px solid rgba(248,81,73,.28);border-radius:10px;padding:14px;margin-top:16px;font-size:12px;color:#ffa198}
</style>
</head>
<body>
<div class="card">
  <h1>&#9889; ElectroStock Solutions — Installer</h1>
  <p class="sub">CTEC2713 Agile Development · Apple Inventory System</p>

  <?php foreach ($messages as $m): ?>
  <div class="msg ok"><?= htmlspecialchars($m) ?></div>
  <?php endforeach; ?>

  <?php foreach ($errors as $e): ?>
  <div class="msg err"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <?php if ($success): ?>
  <div class="login-box">
    <h2>&#10003; Installation complete — login details</h2>
    <table>
      <tr><td>Store Owner email</td><td>owner@electrostock.com</td></tr>
      <tr><td>Store Owner password</td><td>Admin@123</td></tr>
      <tr><td>Employee email</td><td>staff@electrostock.com</td></tr>
      <tr><td>Employee password</td><td>Admin@123</td></tr>
      <tr><td>URL</td><td>http://localhost/ESS/</td></tr>
    </table>
    <a href="auth/login.php" class="btn">Go to login page &rarr;</a>
  </div>
  <div class="warn">
    &#9888; <strong>Delete install.php now.</strong>
    This file should not be accessible in a production environment.
    Delete it from your ESS folder after logging in successfully.
  </div>
  <?php else: ?>
  <div class="msg err">&#9888; Some steps failed. Check the errors above and fix config/db.php then refresh this page.</div>
  <?php endif; ?>
</div>
</body>
</html>
