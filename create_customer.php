<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

try {
    $customerFactory = $objectManager->get(\Magento\Customer\Model\CustomerFactory::class);
    $storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
    $encryptor = $objectManager->get(\Magento\Framework\Encryption\EncryptorInterface::class);
    
    $websiteId = $storeManager->getWebsite()->getId();
    $store = $storeManager->getStore();
    
    $customer = $customerFactory->create();
    $customer->setWebsiteId($websiteId);
    $customer->setEmail('test@affilify.io');
    $customer->setFirstname('Test');
    $customer->setLastname('User');
    $customer->setPassword('Test123!');
    $customer->save();
    
    echo "✅ Account cliente creato!\n";
    echo "   Email: test@affilify.io\n";
    echo "   Password: Test123!\n";
    
} catch (\Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}
