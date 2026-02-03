<?php
/**
 * Affilify Tracking Module - TrackConversionObserver Test
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Test\Unit\Observer;

use Affilify\Tracking\Api\Data\ConversionMessageInterface;
use Affilify\Tracking\Api\Data\ConversionMessageInterfaceFactory;
use Affilify\Tracking\Helper\Config;
use Affilify\Tracking\Helper\CookieHelper;
use Affilify\Tracking\Logger\Logger;
use Affilify\Tracking\Observer\TrackConversionObserver;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TrackConversionObserverTest extends TestCase
{
    /**
     * @var TrackConversionObserver
     */
    private TrackConversionObserver $observer;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var CookieHelper|MockObject
     */
    private $cookieHelperMock;

    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var PublisherInterface|MockObject
     */
    private $publisherMock;

    /**
     * @var ConversionMessageInterfaceFactory|MockObject
     */
    private $conversionMessageFactoryMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Observer|MockObject
     */
    private $observerMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->cookieHelperMock = $this->createMock(CookieHelper::class);
        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->conversionMessageFactoryMock = $this->createMock(ConversionMessageInterfaceFactory::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->observerMock = $this->createMock(Observer::class);

        $this->observer = new TrackConversionObserver(
            $this->configMock,
            $this->cookieHelperMock,
            $this->checkoutSessionMock,
            $this->publisherMock,
            $this->conversionMessageFactoryMock,
            $this->loggerMock
        );
    }

    /**
     * Test execute does nothing when tracking is disabled
     */
    public function testExecuteDoesNothingWhenDisabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(false);

        $this->publisherMock->expects($this->never())
            ->method('publish');

        $this->observer->execute($this->observerMock);
    }

    /**
     * Test execute does nothing when no tracking cookie
     */
    public function testExecuteDoesNothingWhenNoCookie(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->cookieHelperMock->method('getTrackingCookie')->willReturn(null);

        $this->publisherMock->expects($this->never())
            ->method('publish');

        $this->observer->execute($this->observerMock);
    }

    /**
     * Test execute does nothing when empty tracking cookie
     */
    public function testExecuteDoesNothingWhenEmptyCookie(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->cookieHelperMock->method('getTrackingCookie')->willReturn('');

        $this->publisherMock->expects($this->never())
            ->method('publish');

        $this->observer->execute($this->observerMock);
    }

    /**
     * Test execute does nothing when no order
     */
    public function testExecuteDoesNothingWhenNoOrder(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->cookieHelperMock->method('getTrackingCookie')->willReturn('test-123');
        $this->checkoutSessionMock->method('getLastRealOrder')->willReturn(null);

        $this->publisherMock->expects($this->never())
            ->method('publish');

        $this->observer->execute($this->observerMock);
    }

    /**
     * Test successful conversion tracking
     */
    public function testExecuteTracksConversionSuccessfully(): void
    {
        $affiliateId = 'test-affiliate-123';
        $orderId = '000000001';
        $grandTotal = 199.99;
        $currency = 'USD';

        $this->configMock->method('isEnabled')->willReturn(true);
        $this->cookieHelperMock->method('getTrackingCookie')->willReturn($affiliateId);

        // Order mock
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getId')->willReturn(1);
        $orderMock->method('getIncrementId')->willReturn($orderId);
        $orderMock->method('getGrandTotal')->willReturn($grandTotal);
        $orderMock->method('getOrderCurrencyCode')->willReturn($currency);

        $this->checkoutSessionMock->method('getLastRealOrder')->willReturn($orderMock);

        // Conversion message mock
        $conversionMessageMock = $this->createMock(ConversionMessageInterface::class);
        $conversionMessageMock->method('setAffilifyId')->willReturnSelf();
        $conversionMessageMock->method('setOrderId')->willReturnSelf();
        $conversionMessageMock->method('setCheckoutTotal')->willReturnSelf();
        $conversionMessageMock->method('setCurrency')->willReturnSelf();

        $this->conversionMessageFactoryMock->method('create')
            ->willReturn($conversionMessageMock);

        // Expect message to be published
        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with(
                'affilify.tracking.conversion',
                $conversionMessageMock
            );

        // Expect cookie to be deleted
        $this->cookieHelperMock->expects($this->once())
            ->method('deleteTrackingCookie');

        $this->observer->execute($this->observerMock);
    }

    /**
     * Test conversion message receives correct data
     */
    public function testConversionMessageReceivesCorrectData(): void
    {
        $affiliateId = 'test-affiliate-123';
        $orderId = '000000001';
        $grandTotal = 199.99;
        $currency = 'EUR';

        $this->configMock->method('isEnabled')->willReturn(true);
        $this->cookieHelperMock->method('getTrackingCookie')->willReturn($affiliateId);

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getId')->willReturn(1);
        $orderMock->method('getIncrementId')->willReturn($orderId);
        $orderMock->method('getGrandTotal')->willReturn($grandTotal);
        $orderMock->method('getOrderCurrencyCode')->willReturn($currency);

        $this->checkoutSessionMock->method('getLastRealOrder')->willReturn($orderMock);

        // Create a real-ish message mock to verify calls
        $conversionMessageMock = $this->createMock(ConversionMessageInterface::class);

        $conversionMessageMock->expects($this->once())
            ->method('setAffilifyId')
            ->with($affiliateId)
            ->willReturnSelf();

        $conversionMessageMock->expects($this->once())
            ->method('setOrderId')
            ->with($orderId)
            ->willReturnSelf();

        $conversionMessageMock->expects($this->once())
            ->method('setCheckoutTotal')
            ->with((string) $grandTotal)
            ->willReturnSelf();

        $conversionMessageMock->expects($this->once())
            ->method('setCurrency')
            ->with($currency)
            ->willReturnSelf();

        $this->conversionMessageFactoryMock->method('create')
            ->willReturn($conversionMessageMock);

        $this->observer->execute($this->observerMock);
    }

    /**
     * Test uses default currency (EUR) when order currency is null
     */
    public function testUsesDefaultCurrencyWhenNull(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->cookieHelperMock->method('getTrackingCookie')->willReturn('test-123');

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getId')->willReturn(1);
        $orderMock->method('getIncrementId')->willReturn('000000001');
        $orderMock->method('getGrandTotal')->willReturn(100.00);
        $orderMock->method('getOrderCurrencyCode')->willReturn(null);

        $this->checkoutSessionMock->method('getLastRealOrder')->willReturn($orderMock);

        $conversionMessageMock = $this->createMock(ConversionMessageInterface::class);
        $conversionMessageMock->method('setAffilifyId')->willReturnSelf();
        $conversionMessageMock->method('setOrderId')->willReturnSelf();
        $conversionMessageMock->method('setCheckoutTotal')->willReturnSelf();

        // Expect EUR as default (for European market)
        $conversionMessageMock->expects($this->once())
            ->method('setCurrency')
            ->with('EUR')
            ->willReturnSelf();

        $this->conversionMessageFactoryMock->method('create')
            ->willReturn($conversionMessageMock);

        $this->observer->execute($this->observerMock);
    }
}
