<?php
/**
 * VTU Service Interface
 * Abstract interface that all VTU providers must implement
 * This ensures consistency across different VTU API providers
 */

interface VTUServiceInterface {
    /**
     * Purchase airtime for a phone number
     * 
     * @param string $network Network code (MTN, GLO, AIRTEL, 9MOBILE)
     * @param string $phone Phone number to credit
     * @param float $amount Amount in Naira
     * @return array Response with success status and data
     */
    public function purchaseAirtime($network, $phone, $amount);
    
    /**
     * Purchase data bundle for a phone number
     * 
     * @param string $network Network code (MTN, GLO, AIRTEL, 9MOBILE)
     * @param string $phone Phone number to credit
     * @param string $planId Provider-specific plan ID
     * @return array Response with success status and data
     */
    public function purchaseData($network, $phone, $planId);
    
    /**
     * Get available data plans for a specific network
     * 
     * @param string $network Network code (MTN, GLO, AIRTEL, 9MOBILE)
     * @return array Response with success status and plans data
     */
    public function getDataPlans($network);
    
    /**
     * Verify transaction status
     * 
     * @param string $reference Transaction reference
     * @return array Response with success status and transaction data
     */
    public function verifyTransaction($reference);
    
    /**
     * Get provider account balance
     * 
     * @return array Response with success status and balance data
     */
    public function getBalance();
    
    /**
     * Test connection to provider API
     * 
     * @return array Response with success status and connection info
     */
    public function testConnection();
}
?>
