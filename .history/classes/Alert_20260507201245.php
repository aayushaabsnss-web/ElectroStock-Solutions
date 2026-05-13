<?php
/**
 * Alert Class — Middle Layer
 * Manages inventory monitoring alerts and threshold configuration.
 * IMPORTANT: getActive() fetches all rows immediately and clears
 * stored procedure result sets to prevent "commands out of sync".
 */
class Alert {

    /** @var mysqli $conn Active database connection */
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Returns all active alerts as a plain PHP array.
     * Fetches and frees all stored procedure result sets immediately
     * to prevent mysqli "commands out of sync" on the next query.
     * @return array
     */
    public function getActive(): array {
        $result = mysqli_query($this->conn, "CALL sp_getActiveAlerts()");
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
            mysqli_free_result($result);
        }
        // Drain any extra result sets returned by the stored procedure
        while (mysqli_more_results($this->conn)) mysqli_next_result($this->conn);
        return $rows;
    }

    /**
     * Returns recently resolved alerts as a plain PHP array.
     * Uses a plain SELECT (not a stored proc) — no sync issue.
     * @param int $limit Max rows
     * @return array
     */
    public function getResolved(int $limit = 10): array {
        $result = mysqli_query($this->conn,
            "SELECT m.*, p.name AS product_name, u.full_name AS resolved_by_name
             FROM monitoring m
             JOIN products p ON p.id = m.product_id
             LEFT JOIN users u ON u.id = m.resolved_by
             WHERE m.alert_status = 'resolved'
             ORDER BY m.resolved_at DESC
             LIMIT $limit");
        $rows = [];
        if ($result) while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
        return $rows;
    }

    /**
     * Marks an alert as resolved. Calls sp_resolveAlert.
     */
    public function resolve(int $alertId, int $userId): bool {
        $stmt = mysqli_prepare($this->conn, "CALL sp_resolveAlert(?,?)");
        mysqli_stmt_bind_param($stmt, 'ii', $alertId, $userId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        while (mysqli_more_results($this->conn)) mysqli_next_result($this->conn);
        return $ok;
    }

    /**
     * Sets or updates the alert threshold for a product.
     */
    public function setThreshold(int $productId, int $threshold): bool {
        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO monitoring (product_id, threshold)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE threshold = ?");
        mysqli_stmt_bind_param($stmt, 'iii', $productId, $threshold, $threshold);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /**
     * Returns count of active alerts for the nav badge.
     */
    public function countActive(): int {
        $r = mysqli_query($this->conn,
            "SELECT COUNT(*) AS c FROM monitoring WHERE alert_status = 'active'");
        return (int)(mysqli_fetch_assoc($r)['c'] ?? 0);
    }
}
?>