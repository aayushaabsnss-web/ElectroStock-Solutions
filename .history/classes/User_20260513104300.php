<?php
/**
 * User Class — Middle Layer (OOP)
 * Represents a system user with private properties and public getters.
 * Static methods fetch from DB and return User objects.
 */
class User {

    // ── Private properties ───────────────────────────────────
    private int    $id;
    private string $full_name;
    private string $email;
    private string $role;
    private int    $is_active;
    private string $created_at;

    /**
     * Constructor — maps a DB row into a User object.
     */
    public function __construct(array $row) {
        $this->id         = (int)($row['id']         ?? 0);
        $this->full_name  = $row['full_name']         ?? '';
        $this->email      = $row['email']             ?? '';
        $this->role       = $row['role']              ?? 'employee';
        $this->is_active  = (int)($row['is_active']  ?? 1);
        $this->created_at = $row['created_at']        ?? '';
    }

    // ── Getters ──────────────────────────────────────────────
    public function getId(): int          { return $this->id; }
    public function getName(): string     { return $this->full_name; }
    public function getEmail(): string    { return $this->email; }
    public function getRole(): string     { return $this->role; }
    public function isActive(): bool      { return $this->is_active === 1; }
    public function getCreatedAt(): string{ return $this->created_at; }

    // ── Business logic methods ────────────────────────────────
    public function getRoleLabel(): string {
        return $this->role === 'store_owner' ? 'Owner' : 'Employee';
    }

    public function getRoleBadge(): string {
        return $this->role === 'store_owner' ? 'b-blue' : 'b-gray';
    }

    public function getStatusLabel(): string {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getStatusBadge(): string {
        return $this->is_active ? 'b-green' : 'b-red';
    }

    public function getFormattedDate(): string {
        return $this->created_at ? date('d M Y', strtotime($this->created_at)) : '—';
    }

    public function getInitials(): string {
        $parts = array_filter(explode(' ', $this->full_name));
        return strtoupper(implode('', array_map(fn($p) => $p[0], array_slice($parts, 0, 2))));
    }

    // ── Private DB helper ─────────────────────────────────────
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
        if ($result) while ($row = mysqli_fetch_assoc($result)) $objects[] = new self($row);
        return $objects;
    }

    // ── Static factory methods ────────────────────────────────
    /** Returns all users as User objects with optional filters */
    public static function getAll(mysqli $conn, ?string $q=null, ?string $role=null, ?string $status=null): array {
        $where = ["1=1"]; $params = []; $types = '';
        if ($q) { $where[] = "(full_name LIKE ? OR email LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; $types.='ss'; }
        if ($role)           { $where[] = "role=?";       $params[]=$role;   $types.='s'; }
        if ($status==='active')   $where[] = "is_active=1";
        if ($status==='inactive') $where[] = "is_active=0";
        $sql = "SELECT * FROM users WHERE ".implode(' AND ',$where)." ORDER BY created_at DESC";
        return self::fromDB($conn, $sql, $types, $params);
    }

    /** Returns a single User object by ID */
    public static function getById(mysqli $conn, int $id): ?self {
        $objects = self::fromDB($conn, "SELECT * FROM users WHERE id=$id");
        return $objects[0] ?? null;
    }

    /** Returns activity stats for a user */
    public static function getStats(mysqli $conn, int $userId): array {
        $txCount  = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM stock_movements WHERE moved_by=$userId"))['c'] ?? 0);
        $ordCount = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders WHERE created_by=$userId"))['c'] ?? 0);
        return ['transactions' => $txCount, 'orders' => $ordCount];
    }

    /** Creates a new user account */
    public static function create(mysqli $conn, string $name, string $email, string $password, string $role): bool {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users(full_name,email,password_hash,role)VALUES(?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $hash, $role);
        return mysqli_stmt_execute($stmt);
    }

    /** Updates a user's details */
    public static function update(mysqli $conn, int $id, string $name, string $email, string $role, ?string $password=null): bool {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET full_name=?,email=?,role=?,password_hash=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssi', $name, $email, $role, $hash, $id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET full_name=?,email=?,role=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $role, $id);
        }
        return mysqli_stmt_execute($stmt);
    }

    /** Deactivates a user account (soft delete) */
    public static function deactivate(mysqli $conn, int $id): bool {
        return mysqli_query($conn, "UPDATE users SET is_active=0 WHERE id=$id") !== false;
    }

    /** Checks if email already exists */
    public static function emailExists(mysqli $conn): bool {
        return mysqli_errno($conn) === 1062;
    }

    /** Validates user form input */
    public static function validate(string $name, string $email, string $password, bool $isNew=true): array {
        $e = [];
        if (empty(trim($name)))  $e[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $e[] = 'Valid email address is required.';
        if ($isNew && strlen($password) < 6) $e[] = 'Password must be at least 6 characters.';
        if (!$isNew && !empty($password) && strlen($password) < 6) $e[] = 'New password must be at least 6 characters.';
        return $e;
    }
}
