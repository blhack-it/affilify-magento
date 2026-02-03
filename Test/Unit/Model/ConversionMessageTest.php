<?php
/**
 * Affilify Tracking Module - Conversion Message Test
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Test\Unit\Model;

use Affilify\Tracking\Model\ConversionMessage;
use PHPUnit\Framework\TestCase;

class ConversionMessageTest extends TestCase
{
    /**
     * @var ConversionMessage
     */
    private ConversionMessage $message;

    protected function setUp(): void
    {
        $this->message = new ConversionMessage();
    }

    /**
     * Test setters and getters for affiliate ID
     */
    public function testAffilifyId(): void
    {
        $result = $this->message->setAffilifyId('test-affiliate-123');

        $this->assertSame($this->message, $result, 'Setter should return self for fluent interface');
        $this->assertEquals('test-affiliate-123', $this->message->getAffilifyId());
    }

    /**
     * Test setters and getters for order ID
     */
    public function testOrderId(): void
    {
        $result = $this->message->setOrderId('ORDER-001');

        $this->assertSame($this->message, $result);
        $this->assertEquals('ORDER-001', $this->message->getOrderId());
    }

    /**
     * Test setters and getters for checkout total
     */
    public function testCheckoutTotal(): void
    {
        $result = $this->message->setCheckoutTotal('199.99');

        $this->assertSame($this->message, $result);
        $this->assertEquals('199.99', $this->message->getCheckoutTotal());
    }

    /**
     * Test setters and getters for currency
     */
    public function testCurrency(): void
    {
        $result = $this->message->setCurrency('EUR');

        $this->assertSame($this->message, $result);
        $this->assertEquals('EUR', $this->message->getCurrency());
    }

    /**
     * Test setters and getters for referer
     */
    public function testReferer(): void
    {
        $result = $this->message->setReferer('https://example.com/page');

        $this->assertSame($this->message, $result);
        $this->assertEquals('https://example.com/page', $this->message->getReferer());
    }

    /**
     * Test setters and getters for timestamp
     */
    public function testTimestamp(): void
    {
        $timestamp = '2024-01-15T10:30:00+00:00';
        $result = $this->message->setTimestamp($timestamp);

        $this->assertSame($this->message, $result);
        $this->assertEquals($timestamp, $this->message->getTimestamp());
    }

    /**
     * Test setters and getters for tracking domain
     */
    public function testTrackingDomain(): void
    {
        $result = $this->message->setTrackingDomain('t.example.com');

        $this->assertSame($this->message, $result);
        $this->assertEquals('t.example.com', $this->message->getTrackingDomain());
    }

    /**
     * Test fluent interface chaining
     */
    public function testFluentInterface(): void
    {
        $this->message
            ->setAffilifyId('test-123')
            ->setOrderId('ORDER-001')
            ->setCheckoutTotal('100.00')
            ->setCurrency('USD')
            ->setReferer('https://google.com')
            ->setTimestamp('2024-01-15T10:00:00Z')
            ->setTrackingDomain('t.affilify.it');

        $this->assertEquals('test-123', $this->message->getAffilifyId());
        $this->assertEquals('ORDER-001', $this->message->getOrderId());
        $this->assertEquals('100.00', $this->message->getCheckoutTotal());
        $this->assertEquals('USD', $this->message->getCurrency());
        $this->assertEquals('https://google.com', $this->message->getReferer());
        $this->assertEquals('2024-01-15T10:00:00Z', $this->message->getTimestamp());
        $this->assertEquals('t.affilify.it', $this->message->getTrackingDomain());
    }

    /**
     * Test default values are empty strings
     */
    public function testDefaultValues(): void
    {
        $newMessage = new ConversionMessage();

        $this->assertEquals('', $newMessage->getAffilifyId());
        $this->assertEquals('', $newMessage->getOrderId());
        $this->assertEquals('', $newMessage->getCheckoutTotal());
        $this->assertEquals('', $newMessage->getCurrency());
        $this->assertEquals('', $newMessage->getReferer());
        $this->assertEquals('', $newMessage->getTimestamp());
        $this->assertEquals('', $newMessage->getTrackingDomain());
    }
}
