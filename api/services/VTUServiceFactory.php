<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * VTU Service Factory
 * Factory class to instantiate the correct VTU provider based on configuration
 */
class VTUServiceFactory {
    private static $instance = null;
    
    /**
     * Get the active VTU provider instance
     * 
     * @return VTUServiceInterface|null Active provider instance or null
     * @throws Exception if no active provider is configured
     */
    public static function getActiveProvider() {
        try {
            $db = (new Database())->getConnection();
            
            // Get active provider ID from settings
            $stmt = $db->prepare("SELECT setting_value FROM vtu_api_settings WHERE setting_key = 'active_provider_id'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || !$result['setting_value']) {
                throw new Exception('No active VTU provider configured');
            }
            
            $providerId = $result['setting_value'];
            
            // Get provider details
            $stmt = $db->prepare("SELECT * FROM vtu_providers WHERE id = ? AND is_enabled = 1");
            $stmt->execute([$providerId]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$provider) {
                throw new Exception('Active VTU provider not found or disabled');
            }
            
            // Instantiate the correct provider class
            return self::createProvider($provider);
            
        } catch (Exception $e) {
            error_log("VTU Factory Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create provider instance based on provider code
     * 
     * @param array $providerConfig Provider configuration from database
     * @return VTUServiceInterface Provider instance
     * @throws Exception if provider class not found
     */
    private static function createProvider($providerConfig) {
        $providerCode = strtolower($providerConfig['provider_code']);
        
        // Map provider codes to class names
        $providerClasses = [
            'ufardata' => 'UfardataService',
            'vtpass' => 'VTPassService',
            'clubkonnect' => 'ClubkonnectService',
            // Add more providers as needed
        ];
        
        if (!isset($providerClasses[$providerCode])) {
            throw new Exception("Unsupported VTU provider: {$providerCode}");
        }
        
        $className = $providerClasses[$providerCode];
        $classFile = __DIR__ . "/{$className}.php";
        
        if (!file_exists($classFile)) {
            throw new Exception("Provider class file not found: {$className}");
        }
        
        require_once $classFile;
        
        if (!class_exists($className)) {
            throw new Exception("Provider class not found: {$className}");
        }
        
        return new $className($providerConfig);
    }
    
    /**
     * Get provider instance by ID
     * 
     * @param int $providerId Provider ID
     * @return VTUServiceInterface|null Provider instance or null
     */
    public static function getProviderById($providerId) {
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT * FROM vtu_providers WHERE id = ? AND is_enabled = 1");
            $stmt->execute([$providerId]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$provider) {
                return null;
            }
            
            return self::createProvider($provider);
            
        } catch (Exception $e) {
            error_log("VTU Factory Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all enabled providers
     * 
     * @return array List of enabled providers
     */
    public static function getAllProviders() {
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT * FROM vtu_providers WHERE is_enabled = 1 ORDER BY provider_name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("VTU Factory Error: " . $e->getMessage());
            return [];
        }
    }
}
?>
