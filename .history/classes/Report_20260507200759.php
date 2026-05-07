<?php
/**
 * Report Class — Middle Layer (Bonus)
 * Generates inventory reports using stored procedures.
 * All methods fetch results into arrays and drain extra result sets
 * immediately to prevent mysqli "commands out of sync" errors.
 */
class Report {

    /** @var mysqli $conn */
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Helper: run a stored procedure, return all rows as array, free results.
     */
    private function callProc(string $sql): array {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
            mysqli_free_result($result);
        }
        while (mysqli_more_results($this->conn)) mysqli_next_result($this->conn);
        return $rows;
    }

    /** Returns dashboard stats from sp_getDashboardStats */
    public function getDashboardStats(): array {
        $rows = $this->callProc("CALL sp_getDashboardStats()");
        return $rows[0] ?? [];
    }

    /** Returns low stock rows from sp_getLowStockReport */
    public function getLowStock(): array {
        return $this->callProc("CALL sp_getLowStockReport()");
    }

    /** Returns stock value rows from sp_getStockValueReport */
    public function getStockValue(): array {
        return $this->callProc("CALL sp_getStockValueReport()");
    }

    /** Returns order summary rows from sp_getOrderSummaryReport */
    public function getOrderSummary(): array {
        return $this->callProc("CALL sp_getOrderSummaryReport()");
    }
}
