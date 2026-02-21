<?php
require_once __DIR__ . '/../config/database.php';

class WalletHelper {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get user's current wallet balance
     */
    public function getBalance($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT wallet FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? floatval($result['wallet']) : 0.00;
        } catch (PDOException $e) {
            error_log("Error getting wallet balance: " . $e->getMessage());
            return 0.00;
        }
    }

    /**
     * Get service price from pricing table
     */
    public function getServicePrice($service_name) {
        try {
            // First check database
            $stmt = $this->db->prepare("SELECT price FROM pricing WHERE service_name = ? AND status = 'active'");
            $stmt->execute([$service_name]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return floatval($result['price']);
            }

            // Fallback to constants if not found in DB (backward compatibility)
            $constantName = strtoupper($service_name) . '_COST';
            if (defined($constantName)) {
                return constant($constantName);
            }

            return 0.00;
        } catch (PDOException $e) {
            error_log("Error getting service price: " . $e->getMessage());
            return 0.00;
        }
    }

    /**
     * Check if user has sufficient balance for service
     */
    public function hasSufficientBalance($user_id, $service_name) {
        $balance = $this->getBalance($user_id);
        $price = $this->getServicePrice($service_name);

        return $balance >= $price;
    }

    /**
     * Deduct amount from user's wallet
     */
    public function deductAmount($user_id, $amount, $details = '', $reference = null) {
        try {
            $this->db->beginTransaction();

            // Get current balance
            $balStmt = $this->db->prepare("SELECT wallet FROM users WHERE id = ? FOR UPDATE");
            $balStmt->execute([$user_id]);
            $currentBalance = (float)$balStmt->fetchColumn();
            $newBalance = $currentBalance - $amount;

            // Update wallet balance
            $stmt = $this->db->prepare("UPDATE users SET wallet = ? WHERE id = ?");
            $stmt->execute([$newBalance, $user_id]);

            // Record transaction
            $this->addTransaction($user_id, $amount, 'debit', $details, $reference, $currentBalance, $newBalance);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error deducting wallet amount: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add amount to user's wallet
     */
    public function addAmount($user_id, $amount, $details = '', $reference = null) {
        try {
            $this->db->beginTransaction();

            // Get current balance
            $balStmt = $this->db->prepare("SELECT wallet FROM users WHERE id = ? FOR UPDATE");
            $balStmt->execute([$user_id]);
            $currentBalance = (float)$balStmt->fetchColumn();
            $newBalance = $currentBalance + $amount;

            // Update wallet balance
            $stmt = $this->db->prepare("UPDATE users SET wallet = ? WHERE id = ?");
            if (!$stmt->execute([$newBalance, $user_id])) {
                throw new Exception("Failed to update wallet balance in database");
            }

            // Record transaction
            if (!$this->addTransaction($user_id, $amount, 'credit', $details, $reference, $currentBalance, $newBalance)) {
                throw new Exception("Failed to record wallet transaction record");
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error adding wallet amount: " . $e->getMessage() . " | SQL Error: " . json_encode($this->db->errorInfo()));
            return false;
        }
    }

    /**
     * Add wallet transaction record
     */
    public function addTransaction($user_id, $amount, $type, $details = '', $reference = null, $previousBalance = null, $new_balance = null, $admin_id = null) {
        try {
            // Check if admin_id column exists to handle legacy schemas
            $checkStmt = $this->db->prepare("SHOW COLUMNS FROM wallet_transactions LIKE 'admin_id'");
            $checkStmt->execute();
            $hasAdminId = $checkStmt->fetch();

            if ($hasAdminId) {
                $stmt = $this->db->prepare("INSERT INTO wallet_transactions 
                    (user_id, admin_id, amount, transaction_type, description, reference, previous_balance, new_balance, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed')");
                $params = [$user_id, $admin_id, $amount, $type, $details, $reference, $previousBalance, $new_balance];
            } else {
                $stmt = $this->db->prepare("INSERT INTO wallet_transactions 
                    (user_id, amount, transaction_type, description, reference, previous_balance, new_balance, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')");
                $params = [$user_id, $amount, $type, $details, $reference, $previousBalance, $new_balance];
            }
            
            if (!$stmt->execute($params)) {
                $errorInfo = $stmt->errorInfo();
                error_log("Database Error in addTransaction: " . $errorInfo[2]);
                return false;
            }
            return true;
        } catch (PDOException $e) {
            error_log("PDO Exception in addTransaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process payment for service
     */
    public function processPayment($user_id, $service_name, $details = '') {
        $price = $this->getServicePrice($service_name);

        if ($price <= 0) {
            return ['success' => false, 'message' => 'Service price not found'];
        }

        if (!$this->hasSufficientBalance($user_id, $service_name)) {
            $balance = $this->getBalance($user_id);
            return [
                'success' => false,
                'message' => 'Insufficient wallet balance. Required: ₦' . number_format($price, 2) . ', Available: ₦' . number_format($balance, 2)
            ];
        }

        if ($this->deductAmount($user_id, $price, $details)) {
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'amount_deducted' => $price
            ];
        } else {
            return ['success' => false, 'message' => 'Payment processing failed'];
        }
    }
}
?>
