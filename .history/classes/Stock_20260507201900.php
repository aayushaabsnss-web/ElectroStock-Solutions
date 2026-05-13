<?php
/**
 * Stock Class — Middle Layer
 * Handles all stock movement operations and history queries.
 * Every stock change is recorded as an immutable audit trail.
 * Auto-triggers low-stock alerts via stored procedure.
 *
 * Usage:
 *   $stock = new Stock($conn);
 *   [$ok, $err] = $stock->addMovement($pid, 'OUT', 3, $uid, 'Sale');
 */
class Stock {

    /** @var mysqli $conn Active database connection */
    private mysqli $conn;

    /**
     * Constructor
     * @param mysqli $conn Active MySQLi connection
     */
    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Records a stock movement and updates product quantity
     * Calls stored procedure: sp_addStockMovement
     * Automatically creates a low-stock alert if quantity drops
     * at or below the product's minimum stock level.
     *
     * @param int    $productId  Product to update
     * @param string $type       'IN', 'OUT', or 'ADJUSTMENT'
     * @param int    $qty        Number of units (always positive)
     * @param int    $userId     User making the change
     * @param string $notes      Optional reason or reference note
     * @return array [bool $success, string|null $error, int $newQty]
     */
    public function addMovement(int $productId, string $type, int $qty,
                                 int $userId, string $notes = ''): array {
        // Call the stored procedure with OUT parameters
        $stmt = mysqli_prepare($this->conn,
            "CALL sp_addStockMovement(?,?,?,?,?,@new_qty,@error)");
        mysqli_stmt_bind_param($stmt, 'isiis', $productId, $type, $qty, $userId, $notes);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Retrieve the OUT parameter values
        $r      = mysqli_query($this->conn, "SELECT @new_qty AS new_qty, @error AS error");
        $row    = mysqli_fetch_assoc($r);
        $newQty = (int)$row['new_qty'];
        $error  = $row['error'];

        return [$error === null, $error, $newQty];
    }

    /**
     * Returns full stock movement history with optional filters
     * Calls stored procedure: sp_getStockHistory
     *
     * @param int|null    $productId Filter by product (null = all)
     * @param string|null $type      Filter by type: IN, OUT, ADJUSTMENT
     * @param string|null $dateFrom  Start date (YYYY-MM-DD)
     * @param string|null $dateTo    End date   (YYYY-MM-DD)
     * @return mysqli_result
     */
    public function getHistory(?int $productId, ?string $type,
                                ?string $dateFrom, ?string $dateTo): mysqli_result {
        $stmt = mysqli_prepare($this->conn, "CALL sp_getStockHistory(?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'isss', $productId, $type, $dateFrom, $dateTo);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    /**
     * Returns a list of products for the stock update dropdown
     * @return mysqli_result Products with id, name, quantity, category
     */
    public function getProductList(): mysqli_result {
        return mysqli_query($this->conn,
            "SELECT id, name, quantity, category
             FROM products
             WHERE is_active = 1
             ORDER BY name");
    }

    /**
     * Validates stock movement input before saving
     * @param array $data POST data
     * @param bool  $isOwner Whether the current user is a Store Owner
     * @return array List of error messages (empty = valid)
     */
    public function validate(array $data, bool $isOwner = false): array {
        $errors = [];
        // Product must be selected
        if (empty($data['product_id'])) $errors[] = 'Please select a product.';
        // Transaction type must be valid
        $validTypes = ['IN', 'OUT', 'ADJUSTMENT'];
        if (!in_array($data['type'] ?? '', $validTypes)) $errors[] = 'Please select a transaction type.';
        // Only store owners can make ADJUSTMENT transactions
        if (($data['type'] ?? '') === 'ADJUSTMENT' && !$isOwner)
            $errors[] = 'Only the Store Owner can record ADJUSTMENT transactions.';
        // Quantity must be greater than zero
        if (empty($data['quantity']) || (int)$data['quantity'] <= 0)
            $errors[] = 'Quantity must be greater than 0.';
        return $errors;
    }
}