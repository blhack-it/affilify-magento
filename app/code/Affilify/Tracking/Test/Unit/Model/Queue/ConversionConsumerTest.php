<?php
/**
 * Affilify Tracking Module - ConversionConsumer Test
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Test\Unit\Model\Queue;

use Affilify\Tracking\Api\Data\ConversionMessageInterface;
use Affilify\Tracking\Helper\Config;
use Affilify\Tracking\Logger\Logger;
use Affilify\Tracking\Model\Queue\ConversionConsumer;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConversionConsumerTest extends TestCase
{
    /**
     * @var ConversionConsumer
     */
    private ConversionConsumer $consumer;

    /**
     * @var Curl|MockObject
     */
    private $curlMock;

    /**
     * @var Json|MockObject
     */
    private $jsonMock;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ConversionMessageInterface|MockObject
     */
    private $messageMock;

    protected function setUp(): void
    {
        $this->curlMock = $this->createMock(Curl::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->configMock = $this->createMock(Config::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->messageMock = $this->createMock(ConversionMessageInterface::class);

        $this->consumer = new ConversionConsumer(
            $this->curlMock,
            $this->jsonMock,
            $this->configMock,
            $this->loggerMock
        );
    }

    /**
     * Test process skips when no tracking domain
     */
    public function testProcessSkipsWhenNoTrackingDomain(): void
    {
        $this->messageMock->method('getTrackingDomain')->willReturn('');

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Conversion tracking skipped: No tracking domain configured');

        $this->curlMock->expects($this->never())
            ->method('post');

        $this->consumer->process($this->messageMock);
    }

    /**
     * Test successful conversion tracking
     */
    public function testProcessSuccessfulTracking(): void
    {
        $this->setupMessageMock();

        $this->jsonMock->method('serialize')
            ->willReturn('{"affilify_id":"test-123","order_id":"000000001"}');

        $this->curlMock->method('getStatus')->willReturn(200);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Conversion tracked successfully', $this->anything());

        $this->consumer->process($this->messageMock);
    }

    /**
     * Test client error (4xx) does not retry
     */
    public function testProcessDoesNotRetryOn4xxError(): void
    {
        $this->setupMessageMock();

        $this->jsonMock->method('serialize')->willReturn('{}');
        $this->curlMock->method('getStatus')->willReturn(400);
        $this->curlMock->method('getBody')->willReturn('Bad Request');

        // Should only call post once (no retry for 4xx)
        $this->curlMock->expects($this->once())
            ->method('post');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Conversion tracking failed with client error', $this->anything());

        $this->consumer->process($this->messageMock);
    }

    /**
     * Test 5xx error triggers retry
     */
    public function testProcessRetriesOn5xxError(): void
    {
        $this->setupMessageMock();

        $this->jsonMock->method('serialize')->willReturn('{}');

        // First call returns 500, second returns 200
        $this->curlMock->method('getStatus')
            ->willReturnOnConsecutiveCalls(500, 200);

        // Should retry
        $this->curlMock->expects($this->exactly(2))
            ->method('post');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Conversion tracked successfully', $this->anything());

        $this->consumer->process($this->messageMock);
    }

    /**
     * Test payload contains currency
     */
    public function testPayloadContainsCurrency(): void
    {
        $this->setupMessageMock();

        $capturedPayload = null;
        $this->jsonMock->method('serialize')
            ->willReturnCallback(function ($data) use (&$capturedPayload) {
                $capturedPayload = $data;
                return json_encode($data);
            });

        $this->curlMock->method('getStatus')->willReturn(200);

        $this->consumer->process($this->messageMock);

        $this->assertArrayHasKey('currency', $capturedPayload);
        $this->assertEquals('USD', $capturedPayload['currency']);
    }

    /**
     * Test payload contains order_id
     */
    public function testPayloadContainsOrderId(): void
    {
        $this->setupMessageMock();

        $capturedPayload = null;
        $this->jsonMock->method('serialize')
            ->willReturnCallback(function ($data) use (&$capturedPayload) {
                $capturedPayload = $data;
                return json_encode($data);
            });

        $this->curlMock->method('getStatus')->willReturn(200);

        $this->consumer->process($this->messageMock);

        $this->assertArrayHasKey('order_id', $capturedPayload);
        $this->assertEquals('000000001', $capturedPayload['order_id']);
    }

    /**
     * Test URL building with HTTPS
     */
    public function testBuildApiUrlUsesHttps(): void
    {
        $this->setupMessageMock();

        $this->jsonMock->method('serialize')->willReturn('{}');
        $this->curlMock->method('getStatus')->willReturn(200);

        // Capture the URL passed to post()
        $this->curlMock->expects($this->once())
            ->method('post')
            ->with(
                $this->stringContains('https://t.example.com/m/conv'),
                $this->anything()
            );

        $this->consumer->process($this->messageMock);
    }

    /**
     * Setup common message mock expectations
     */
    private function setupMessageMock(): void
    {
        $this->messageMock->method('getTrackingDomain')->willReturn('t.example.com');
        $this->messageMock->method('getAffilfyId')->willReturn('test-affiliate-123');
        $this->messageMock->method('getOrderId')->willReturn('000000001');
        $this->messageMock->method('getCheckoutTotal')->willReturn('199.99');
        $this->messageMock->method('getCurrency')->willReturn('USD');
        $this->messageMock->method('getReferer')->willReturn('https://example.com/checkout');
        $this->messageMock->method('getTimestamp')->willReturn('2024-01-15T10:30:00+00:00');
    }
}
