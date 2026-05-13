<?php
/**
 * Order Class — Middle Layer
 * Manages customer orders and order line items.
 * On completion, triggers stock deduction via the Stock class.
 *
 * Usage:
 *   $order = new Order($conn);
 *   $all   = $order->getAll();
 *   $id    = $order->create($customer, $notes, $items, $userId);
 */
class Order {

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
     * Returns all orders, optionally filtered by status
     * @param string|null $status Optional status filter
     * @return mysqli_result
     */
    public function getAll(?string $status = null): mysqli_result {
        // Build WHERE clause based on optional status filter
        $where = $status ? "WHERE o.status = '" . mysqli_real_escape_string($this->conn, $status) . "'" : '';
        return mysqli_query($this->conn,
            "SELECT o.*, u.full_name AS created_by_name,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
             FROM orders o
             JOIN users u ON u.id = o.created_by
             $where
             ORDER BY o.created_at DESC");
    }

    /**
     * Returns a single order with its line items
     * @param int $id Order ID
     * @return array|null ['order' => [...], 'items' => [...]]
     */
    public function getById(int $id): ?array {
        // Fetch the order header
        $r = mysqli_query($this->conn,
            "SELECT o.*, u.full_name AS created_by_name
             FROM orders o JOIN users u ON u.id = o.created_by
             WHERE o.id = $id");
        $order = mysqli_fetch_assoc($r);
        if (!$order) return null;

        // Fetch all line items for this order
        $ri = mysqli_query($this->conn,
            "SELECT oi.*, p.name AS product_name, p.sku
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = $id");
        $items = [];
        while ($row = mysqli_fetch_assoc($ri)) $items[] = $row;

        return ['order' => $order, 'items' => $items];
    }

    /**
     * Creates a new order with its line items
     * Calls stored procedure: sp_createOrder
     * Does NOT deduct stock — that happens on completion via updateStatus()
     *
     * @param string $customer Customer name
     * @param string $notes    Optional notes
     * @param array  $items    [['product_id'=>1,'quantity'=>2,'price'=>999.00], ...]
     * @param int    $userId   ID of the creating user
     * @return int|null New order ID, or null on failure
     */
    public function create(string $customer, string $notes, array $items, int $userId): ?int {
        // Generate a unique order number using date + random suffix
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // Calculate the order total from line items
        $total = array_sum(array_map(fn($i) => $i['quantity'] * $i['price'], $items));

        // Call stored procedure to create the order header
        $stmt = mysqli_prepare($this->conn, "CALL sp_createOrder(?,?,?,?,@order_id)");
       
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Retrieve the new order ID from the OUT parameter
        $r = mysqli_query($this->conn, "SELECT @order_id AS id");
        $orderId = (int)(mysqli_fetch_assoc($r)['id'] ?? 0);
        if (!$orderId) return null;

        // Insert each line item
        foreach ($items as $item) {
            $stmt2 = mysqli_prepare($this->conn,
                "INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                 VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, 'iiid',
                $orderId, $item['product_id'], $item['quantity'], $item['price']);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);
        }

        // Update the total on the order header
        mysqli_query($this->conn, "UPDATE orders SET total = $total WHERE id = $orderId");

        return $orderId;
    }

    /**
     * Updates the status of an order
     * When status becomes 'completed', deducts stock for each line item
     * Calls stored procedure: sp_updateOrderStatus
     *
     * @param int    $orderId  Order to update
     * @param string $status   New status value
     * @param int    $userId   User making the change (for stock audit trail)
     * @return bool True on success
     */
    public function updateStatus(int $orderId, string $status, int $userId): bool {
        // Validate the status is a permitted value
        $allowed = ['pending', 'processing', 'completed', 'cancelled'];
        if (!in_array($status, $allowed)) return false;

        // When completing an order, deduct stock for each item
        if ($status === 'completed') {
            $stock = new Stock($this->conn);
            $orderNum = mysqli_fetch_assoc(
                mysqli_query($this->conn, "SELECT order_number FROM orders WHERE id = $orderId")
            )['order_number'];

            $items = mysqli_query($this->conn,
                "SELECT * FROM order_items WHERE order_id = $orderId");
            while ($item = mysqli_fetch_assoc($items)) {
                // Record an OUT stock movement for each line item
                $stock->addMovement(
                    $item['product_id'], 'OUT', $item['quantity'],
                    $userId, "Order $orderNum fulfilment"
                );
            }
        }

        // Update the order status via stored procedure
        $stmt = mysqli_prepare($this->conn, "CALL sp_updateOrderStatus(?,?)");
        mysqli_stmt_bind_param($stmt, 'is', $orderId, $status);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /**
     * Searches orders by order number or customer name
     * @param string|null $query  Search term
     * @param string|null $status Status filter
     * @return mysqli_result
     */
    public function search(?string $query, ?string $status): mysqli_result {
        $where = ["1=1"];
        $params = []; $types = '';

        if ($query) {
            $where[] = "(o.order_number LIKE ? OR o.customer LIKE ?)";
            $params[] = "%$query%"; $params[] = "%$query%"; $types .= 'ss';
        }
        if ($status) { $where[] = "o.status = ?"; $params[] = $status; $types .= 's'; }

        $sql = "SELECT o.*, u.full_name AS created_by_name
                FROM orders o JOIN users u ON u.id = o.created_by
                WHERE " . implode(' AND ', $where) . " ORDER BY o.created_at DESC";

        if ($params) {
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);
            return $result;
        }
        return mysqli_query($this->conn, $sql);
    }

    /**
     * Validates order input before creating
     * @param string $customer Customer name
     * @param array  $items    Line items array
     * @return array List of error messages (empty = valid)
     */
    public function validate(string $customer, array $items): array {
        $errors = [];
        // Customer name is required
        if (empty(trim($customer)))  $errors[] = 'Customer name is required.';
        // At least one product must be added
        if (empty($items))           $errors[] = 'Please add at least one product.';
        // Each item must have a valid quantity
        foreach ($items as $i => $item) {
            if ((int)($item['quantity'] ?? 0) <= 0)
                $errors[] = "Row " . ($i + 1) . ": quantity must be greater than 0.";
        }
        return $errors;
    }
}
