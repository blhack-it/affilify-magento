<?php
/**
 * Affilify Tracking Module - Click Message Test
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Test\Unit\Model;

use Affilify\Tracking\Model\ClickMessage;
use PHPUnit\Framework\TestCase;

class ClickMessageTest extends TestCase
{
    /**
     * @var ClickMessage
     */
    private ClickMessage $message;

    protected function setUp(): void
    {
        $this->message = new ClickMessage();
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
     * Test setters and getters for IP
     */
    public function testIp(): void
    {
        $result = $this->message->setIp('192.168.x.x');

        $this->assertSame($this->message, $result);
        $this->assertEquals('192.168.x.x', $this->message->getIp());
    }

    /**
     * Test setters and getters for user agent
     */
    public function testUserAgent(): void
    {
        $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)';
        $result = $this->message->setUserAgent($ua);

        $this->assertSame($this->message, $result);
        $this->assertEquals($ua, $this->message->getUserAgent());
    }

    /**
     * Test setters and getters for referer
     */
    public function testReferer(): void
    {
        $result = $this->message->setReferer('https://google.com/search');

        $this->assertSame($this->message, $result);
        $this->assertEquals('https://google.com/search', $this->message->getReferer());
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
            ->setIp('10.0.x.x')
            ->setUserAgent('TestBot/1.0')
            ->setReferer('https://example.com')
            ->setTimestamp('2024-01-15T10:00:00Z')
            ->setTrackingDomain('t.affilify.it');

        $this->assertEquals('test-123', $this->message->getAffilifyId());
        $this->assertEquals('10.0.x.x', $this->message->getIp());
        $this->assertEquals('TestBot/1.0', $this->message->getUserAgent());
        $this->assertEquals('https://example.com', $this->message->getReferer());
        $this->assertEquals('2024-01-15T10:00:00Z', $this->message->getTimestamp());
        $this->assertEquals('t.affilify.it', $this->message->getTrackingDomain());
    }

    /**
     * Test default values are empty strings
     */
    public function testDefaultValues(): void
    {
        $newMessage = new ClickMessage();

        $this->assertEquals('', $newMessage->getAffilifyId());
        $this->assertEquals('', $newMessage->getIp());
        $this->assertEquals('', $newMessage->getUserAgent());
        $this->assertEquals('', $newMessage->getReferer());
        $this->assertEquals('', $newMessage->getTimestamp());
        $this->assertEquals('', $newMessage->getTrackingDomain());
    }
}
