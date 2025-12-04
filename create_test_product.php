<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

try {
    $productFactory = $objectManager->get(\Magento\Catalog\Model\ProductFactory::class);
    $productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    $stockRegistry = $objectManager->get(\Magento\CatalogInventory\Api\StockRegistryInterface::class);
    
    // Crea prodotto
    $product = $productFactory->create();
    $product->setSku('test-affilify-001');
    $product->setName('Prodotto Test Affilify');
    $product->setAttributeSetId(4); // Default attribute set
    $product->setStatus(1); // Enabled
    $product->setVisibility(4); // Catalog, Search
    $product->setTypeId('simple');
    $product->setPrice(49.99);
    $product->setWebsiteIds([1]);
    $product->setStockData([
        'qty' => 100,
        'is_in_stock' => 1,
        'manage_stock' => 1
    ]);
    
    $savedProduct = $productRepository->save($product);
    
    echo "✅ Prodotto creato con successo!\n";
    echo "   SKU: " . $savedProduct->getSku() . "\n";
    echo "   Nome: " . $savedProduct->getName() . "\n";
    echo "   Prezzo: €" . $savedProduct->getPrice() . "\n";
    echo "   URL: http://localhost/catalog/product/view/id/" . $savedProduct->getId() . "\n";
    
} catch (\Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}
