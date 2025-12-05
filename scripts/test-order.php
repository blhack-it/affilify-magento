<?php
/**
 * Test script to create an order programmatically and test conversion tracking
 */

use Magento\Framework\App\Bootstrap;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Model\Order;

require '/var/www/html/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
} catch (\Exception $e) {
    // Area code already set
}

echo "🛒 Creating test order for conversion tracking...\n\n";

// Set the affiliate cookie in the cookie manager
$cookieManager = $objectManager->get(\Magento\Framework\Stdlib\CookieManagerInterface::class);
$cookieMetadataFactory = $objectManager->get(\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class);

// We'll simulate the cookie by setting it directly in superglobal
$_COOKIE['affilify_tracking'] = 'NEWTEST123';
echo "📍 Affiliate ID: " . $_COOKIE['affilify_tracking'] . "\n";

try {
    // Get store
    $storeManager = $objectManager->get(StoreManagerInterface::class);
    $store = $storeManager->getStore();
    $websiteId = $store->getWebsiteId();

    echo "🏪 Store: " . $store->getName() . " (ID: " . $store->getId() . ")\n";

    // Get a simple product
    $productRepository = $objectManager->get(ProductRepositoryInterface::class);
    $searchCriteriaBuilder = $objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
    $searchCriteria = $searchCriteriaBuilder
        ->addFilter('type_id', 'simple')
        ->addFilter('status', 1) // enabled
        ->setPageSize(1)
        ->create();

    $products = $productRepository->getList($searchCriteria)->getItems();
    $product = reset($products);

    if (!$product) {
        echo "❌ No simple products found!\n";
        exit(1);
    }

    echo "📦 Product: " . $product->getName() . " (SKU: " . $product->getSku() . ", Price: €" . $product->getPrice() . ")\n";

    // Create quote
    $quoteFactory = $objectManager->get(QuoteFactory::class);
    $quote = $quoteFactory->create();
    $quote->setStore($store);
    $quote->setCurrency();
    $quote->setCustomerIsGuest(true);
    $quote->setCustomerEmail('test-order-' . time() . '@affilify.test');

    // Add product to quote
    $quote->addProduct($product, 1);

    // Set billing address
    $billingAddress = [
        'firstname' => 'Test',
        'lastname' => 'Customer',
        'street' => 'Via Test 123',
        'city' => 'Milano',
        'country_id' => 'IT',
        'region' => 'MI',
        'postcode' => '20100',
        'telephone' => '0212345678',
        'email' => $quote->getCustomerEmail()
    ];

    $quote->getBillingAddress()->addData($billingAddress);
    $quote->getShippingAddress()->addData($billingAddress);

    // Set shipping method
    $shippingAddress = $quote->getShippingAddress();
    $shippingAddress->setCollectShippingRates(true)->collectShippingRates();

    // Get available shipping methods
    $shippingRates = $shippingAddress->getAllShippingRates();
    if (empty($shippingRates)) {
        // Try flatrate
        $shippingAddress->setShippingMethod('flatrate_flatrate');
    } else {
        $firstRate = reset($shippingRates);
        $shippingAddress->setShippingMethod($firstRate->getCode());
    }

    echo "🚚 Shipping: " . $shippingAddress->getShippingMethod() . "\n";

    // Collect totals first
    $quote->collectTotals();

    // Save quote first to get an ID
    $cartRepository = $objectManager->get(CartRepositoryInterface::class);
    $cartRepository->save($quote);

    // Set payment method using the cart payment management
    $paymentMethod = $objectManager->get(\Magento\Quote\Api\PaymentMethodManagementInterface::class);
    $paymentData = $objectManager->create(\Magento\Quote\Api\Data\PaymentInterface::class);
    $paymentData->setMethod('checkmo');
    $paymentMethod->set($quote->getId(), $paymentData);

    echo "💳 Payment: Check/Money Order\n";

    // Reload quote after payment
    $quote = $cartRepository->get($quote->getId());

    echo "💰 Quote Total: €" . number_format($quote->getGrandTotal(), 2) . "\n";

    // Create order from quote
    $cartManagement = $objectManager->get(CartManagementInterface::class);
    $orderId = $cartManagement->placeOrder($quote->getId());

    // Get order
    $orderRepository = $objectManager->get(\Magento\Sales\Api\OrderRepositoryInterface::class);
    $order = $orderRepository->get($orderId);

    echo "\n✅ Order created successfully!\n";
    echo "📋 Order ID: " . $order->getIncrementId() . "\n";
    echo "💵 Grand Total: €" . number_format($order->getGrandTotal(), 2) . "\n";
    echo "📧 Customer Email: " . $order->getCustomerEmail() . "\n";

    // Now we need to dispatch the checkout_onepage_controller_success_action event
    // to trigger the conversion tracking
    echo "\n🎯 Triggering conversion tracking event...\n";

    $eventManager = $objectManager->get(\Magento\Framework\Event\ManagerInterface::class);
    $eventManager->dispatch('checkout_onepage_controller_success_action', [
        'order_ids' => [$orderId]
    ]);

    echo "✅ Event dispatched!\n";
    echo "\n⏳ Waiting for queue consumer to process...\n";
    sleep(3);

    echo "\n📊 Check conversion at: curl -sk https://localhost:3000/events | jq '.conversions'\n";
    echo "📜 Check logs: docker-compose exec php tail -20 var/log/affilify_tracking.log\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
