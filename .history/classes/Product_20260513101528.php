<?php
/**
 * Product Class — Middle Layer
 * Handles all database operations for the products table.
 * All stored procedure calls fetch results into arrays immediately
 * and drain extra result sets to prevent "commands out of sync".
 */
class Product {

    /** @var mysqli $conn */
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /** Helper: call stored procedure, return all rows as array */
    private function callProc(string $sql, string $types = '', array $params = []): array {
        if ($params) {
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = mysqli_query($this->conn, $sql);
        }
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
            mysqli_free_result($result);
        }
        while (mysqli_more_results($this->conn)) mysqli_next_result($this->conn);
        return $rows;
    }

    /** Returns all active products. Calls sp_getAllProducts */
    public function getAll(): array {
        return $this->callProc("CALL sp_getAllProducts()");
    }

    /** Returns single product by ID. Calls sp_getProductById */
    public function getById(int $id): ?array {
        $rows = $this->callProc("CALL sp_getProductById(?)", 'i', [$id]);
        return $rows[0] ?? null;
    }

    /** Searches products. Calls sp_searchProducts */
    public function search(?string $q, ?string $cat, ?string $status): array {
        return $this->callProc("CALL sp_searchProducts(?,?,?)", 'sss', [$q, $cat, $status]);
    }

    /** Adds a product. Calls sp_addProduct */
    public function add(array $d, int $uid): bool {
        $name  = trim($d['name']); $sku = trim($d['sku']); $cat = $d['category'];
        $desc  = trim($d['description'] ?? ''); $price = (float)$d['price'];
        $qty   = (int)($d['quantity'] ?? 0); $min = (int)($d['min_qty'] ?? 5);
        $sup   = trim($d['supplier'] ?? 'Apple Inc.');
        $stmt  = mysqli_prepare($this->conn, "CALL sp_addProduct(?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssssdiiis', $name, $sku, $cat, $desc, $price, $qty, $min, $sup, $uid);
        $ok = mysqli_stmt_execute($stmt);
        // Drain result sets from the stored procedure
        $res = mysqli_stmt_get_result($stmt);
        if ($res) mysqli_free_result($res);
        mysqli_stmt_close($stmt);
        while (mysqli_more_results($this->conn)) mysqli_next_result($this->conn);
        return $ok;
    }

    /** Updates a product. Calls sp_updateProduct */
    public function update(int $id, array $d): bool {
        $name  = trim($d['name']); $sku = trim($d['sku']); $cat = $d['category'];
        $desc  = trim($d['description'] ?? ''); $price = (float)$d['price'];
        $min   = (int)$d['min_qty']; $sup = trim($d['supplier'] ?? 'Apple Inc.');
        $stmt  = mysqli_prepare($this->conn, "CALL sp_updateProduct(?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'issssdis', $id, $name, $sku, $cat, $desc, $price, $min, $sup);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        while (mysqli_more_results($this->conn)) mysqli_next_result($this->conn);
        return $ok;
    }

    /** Soft-deletes a product. Calls sp_deleteProduct */
    public function delete(int $id): bool {
        $stmt = mysqli_prepare($this->conn, "CALL sp_deleteProduct(?)");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        while (mysqli_more_results($this->conn)) mysqli_next_result($this->conn);
        return $ok;
    }

    /** Checks if last error was a duplicate key violation */
    public function isDuplicate(): bool {
        return mysqli_errno($this->conn) === 1062;
    }

    /** Validates product input */
    public function validate(array $d): array {
        $e = [];
        if (empty(trim($d['name'] ?? '')))      $e[] = 'Product name is required.';
        if (empty(trim($d['sku']  ?? '')))       $e[] = 'SKU is required.';
        if (!in_array($d['category'] ?? '', ['iPhone','Mac','iPad','Watch','Accessory'])) $e[] = 'Select a valid category.';
        if (empty($d['price']) || (float)$d['price'] <= 0) $e[] = 'Price must be greater than $0.';
        if (isset($d['quantity']) && (int)$d['quantity'] < 0) $e[] = 'Quantity cannot be negative.';
        if (isset($d['min_qty'])  && (int)$d['min_qty']  < 1) $e[] = 'Min stock level must be at least 1.';
        return $e;
    }
}