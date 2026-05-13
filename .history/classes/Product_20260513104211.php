<?php
/**
 * Product Class — Middle Layer (OOP)
 * Represents a single Apple product with private properties and public getters.
 * Static methods fetch from DB via stored procedures and return Product objects.
 * The presentation layer accesses data through object methods, not raw arrays.
 */
class Product {

    // ── Private properties (encapsulation) ──────────────────
    private int    $id;
    private string $name;
    private string $sku;
    private string $category;
    private float  $price;
    private int    $quantity;
    private int    $min_qty;
    private string $supplier;
    private string $description;
    private string $created_at;

    /**
     * Constructor — maps a database row into a Product object.
     * Called internally by the static factory methods below.
     * @param array $row Associative array from MySQLi fetch
     */
    public function __construct(array $row) {
        $this->id          = (int)($row['id']          ?? 0);
        $this->name        = $row['name']               ?? '';
        $this->sku         = $row['sku']                ?? '';
        $this->category    = $row['category']           ?? '';
        $this->price       = (float)($row['price']      ?? 0);
        $this->quantity    = (int)($row['quantity']     ?? 0);
        $this->min_qty     = (int)($row['min_qty']      ?? 5);
        $this->supplier    = $row['supplier']           ?? 'Apple Inc.';
        $this->description = $row['description']        ?? '';
        $this->created_at  = $row['created_at']         ?? '';
    }

    // ── Getters — public access to private properties ────────
    public function getId(): int          { return $this->id; }
    public function getName(): string     { return $this->name; }
    public function getSku(): string      { return $this->sku; }
    public function getCategory(): string { return $this->category; }
    public function getPrice(): float     { return $this->price; }
    public function getQuantity(): int    { return $this->quantity; }
    public function getMinQty(): int      { return $this->min_qty; }
    public function getSupplier(): string { return $this->supplier; }
    public function getDescription(): string { return $this->description; }
    public function getCreatedAt(): string   { return $this->created_at; }

    // ── Business logic methods ────────────────────────────────

    /** Returns the stock status label based on quantity and min level */
    public function getStockStatus(): string {
        if ($this->quantity === 0)              return 'Out of Stock';
        if ($this->quantity <= $this->min_qty)  return 'Low Stock';
        return 'In Stock';
    }

    /** Returns the stock status CSS badge class */
    public function getStockBadge(): string {
        return ['In Stock'=>'b-green','Low Stock'=>'b-amber','Out of Stock'=>'b-red'][$this->getStockStatus()];
    }

    /** Returns the price formatted as $X,XXX.XX */
    public function getFormattedPrice(): string {
        return '$' . number_format($this->price, 2);
    }

    /** Returns the date added formatted as d M Y */
    public function getFormattedDate(): string {
        return $this->created_at ? date('d M Y', strtotime($this->created_at)) : '—';
    }

    // ── Private DB helper — runs stored procedure, returns Product[] ──
    private static function fromDB(mysqli $conn, string $sql, string $types='', array $params=[]): array {
        if ($params) {
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = mysqli_query($conn, $sql);
        }
        $objects = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $objects[] = new self($row); // Create Product object from each row
            }
            mysqli_free_result($result);
        }
        while (mysqli_more_results($conn)) mysqli_next_result($conn);
        return $objects;
    }

    // ── Static factory methods — return Product objects ──────

    /** Returns all active products as an array of Product objects */
    public static function getAll(mysqli $conn): array {
        return self::fromDB($conn, "CALL sp_getAllProducts()");
    }

    /** Returns a single Product object by ID, or null if not found */
    public static function getById(mysqli $conn, int $id): ?self {
        $objects = self::fromDB($conn, "CALL sp_getProductById(?)", 'i', [$id]);
        return $objects[0] ?? null;
    }

    /** Searches products and returns array of Product objects */
    public static function search(mysqli $conn, ?string $q, ?string $cat, ?string $status): array {
        return self::fromDB($conn, "CALL sp_searchProducts(?,?,?)", 'sss', [$q, $cat, $status]);
    }

    // ── Static write methods ──────────────────────────────────

    /** Inserts a new product via stored procedure */
    public static function add(mysqli $conn, array $d, int $uid): bool {
        $name  = trim($d['name']); $sku = trim($d['sku']); $cat = $d['category'];
        $desc  = trim($d['description'] ?? ''); $price = (float)$d['price'];
        $qty   = (int)($d['quantity'] ?? 0); $min = (int)($d['min_qty'] ?? 5);
        $sup   = trim($d['supplier'] ?? 'Apple Inc.');
        $stmt  = mysqli_prepare($conn, "CALL sp_addProduct(?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssssdiiis', $name,$sku,$cat,$desc,$price,$qty,$min,$sup,$uid);
        $ok = mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt); if($res) mysqli_free_result($res);
        mysqli_stmt_close($stmt);
        while (mysqli_more_results($conn)) mysqli_next_result($conn);
        return $ok;
    }

    /** Updates an existing product via stored procedure */
    public static function update(mysqli $conn, int $id, array $d): bool {
        $name = trim($d['name']); $sku = trim($d['sku']); $cat = $d['category'];
        $desc = trim($d['description'] ?? ''); $price = (float)$d['price'];
        $min  = (int)($d['min_qty'] ?? 5); $sup = trim($d['supplier'] ?? 'Apple Inc.');
        $stmt = mysqli_prepare($conn, "CALL sp_updateProduct(?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'issssdis', $id,$name,$sku,$cat,$desc,$price,$min,$sup);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        while (mysqli_more_results($conn)) mysqli_next_result($conn);
        return $ok;
    }

    /** Soft-deletes a product via stored procedure */
    public static function delete(mysqli $conn, int $id): bool {
        $stmt = mysqli_prepare($conn, "CALL sp_deleteProduct(?)");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        while (mysqli_more_results($conn)) mysqli_next_result($conn);
        return $ok;
    }

    /** Checks if last DB error was a duplicate key violation */
    public static function isDuplicate(mysqli $conn): bool {
        return mysqli_errno($conn) === 1062;
    }

    /** Validates product form input. Returns array of error messages. */
    public static function validate(array $d): array {
        $e = [];
        if (empty(trim($d['name'] ?? '')))   $e[] = 'Product name is required.';
        if (empty(trim($d['sku']  ?? '')))   $e[] = 'SKU is required.';
        if (!in_array($d['category'] ?? '', ['iPhone','Mac','iPad','Watch','Accessory']))
                                              $e[] = 'Please select a valid category.';
        if (empty($d['price']) || (float)$d['price'] <= 0)
                                              $e[] = 'Price must be greater than $0.';
        return $e;
    }
}
