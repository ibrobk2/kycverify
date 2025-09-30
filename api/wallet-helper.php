<?php
require_once '../config/database.php';

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
            $stmt = $this->db->prepare("SELECT price FROM pricing WHERE service_name = ? AND status = 'active'");
            $stmt->execute([$service_name]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? floatval($result['price']) : 0.00;
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
    public function deductAmount($user_id, $amount, $details = '') {
        try {
            $this->db->beginTransaction();

            // Update wallet balance
            $stmt = $this->db->prepare("UPDATE users SET wallet = wallet - ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);

            // Record transaction
            $this->addTransaction($user_id, $amount, 'debit', $details);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error deducting wallet amount: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add wallet transaction record
     */
    public function addTransaction($user_id, $amount, $type, $details = '') {
        try {
            $stmt = $this->db->prepare("INSERT INTO wallet_transactions (user_id, amount, transaction_type, details) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $amount, $type, $details]);
            return true;
        } catch (PDOException $e) {
            error_log("Error adding wallet transaction: " . $e->getMessage());
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
