<?php
/**
 * Affilify Tracking Module - ClickConsumer Test
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Test\Unit\Model\Queue;

use Affilify\Tracking\Api\Constants;
use Affilify\Tracking\Api\Data\ClickMessageInterface;
use Affilify\Tracking\Helper\Config;
use Affilify\Tracking\Logger\Logger;
use Affilify\Tracking\Model\Queue\ClickConsumer;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClickConsumerTest extends TestCase
{
    /**
     * @var ClickConsumer
     */
    private ClickConsumer $consumer;

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
     * @var ClickMessageInterface|MockObject
     */
    private $messageMock;

    protected function setUp(): void
    {
        $this->curlMock = $this->createMock(Curl::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->configMock = $this->createMock(Config::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->messageMock = $this->createMock(ClickMessageInterface::class);

        $this->consumer = new ClickConsumer(
            $this->curlMock,
            $this->jsonMock,
            $this->configMock,
            $this->loggerMock
        );
    }

    /**
     * Test process skips when no API key configured
     */
    public function testProcessSkipsWhenNoApiKey(): void
    {
        $this->configMock->method('getApiKey')->willReturn('');

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Click tracking skipped: No API key configured');

        $this->curlMock->expects($this->never())
            ->method('post');

        $this->consumer->process($this->messageMock);
    }

    /**
     * Test successful click tracking
     */
    public function testProcessSuccessfulTracking(): void
    {
        $this->setupConfigMock();
        $this->setupMessageMock();

        $this->jsonMock->method('serialize')
            ->willReturn('{"affilify_id":"test-123","platform":"magento"}');

        $this->curlMock->method('getStatus')->willReturn(200);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Click tracked successfully', $this->anything());

        $this->consumer->process($this->messageMock);
    }

    /**
     * Test client error (4xx) does not retry
     */
    public function testProcessDoesNotRetryOn4xxError(): void
    {
        $this->setupConfigMock();
        $this->setupMessageMock();

        $this->jsonMock->method('serialize')->willReturn('{}');
        $this->curlMock->method('getStatus')->willReturn(400);
        $this->curlMock->method('getBody')->willReturn('Bad Request');

        // Should only call post once (no retry for 4xx)
        $this->curlMock->expects($this->once())
            ->method('post');

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Click tracking failed with client error', $this->anything());

        $this->consumer->process($this->messageMock);
    }

    /**
     * Test 429 (rate limit) triggers retry
     */
    public function testProcessRetriesOn429Error(): void
    {
        $this->setupConfigMock();
        $this->setupMessageMock();

        $this->jsonMock->method('serialize')->willReturn('{}');

        // First call returns 429, second returns 200
        $this->curlMock->method('getStatus')
            ->willReturnOnConsecutiveCalls(429, 200);

        // Should retry
        $this->curlMock->expects($this->exactly(2))
            ->method('post');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Click tracked successfully', $this->anything());

        $this->consumer->process($this->messageMock);
    }

    /**
     * Test correct API URL is used
     */
    public function testUsesCorrectApiUrl(): void
    {
        $this->setupConfigMock();
        $this->setupMessageMock();

        $this->jsonMock->method('serialize')->willReturn('{}');
        $this->curlMock->method('getStatus')->willReturn(200);

        $this->curlMock->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('https://dashboard.affilify.it/api/track/click'),
                $this->anything()
            );

        $this->consumer->process($this->messageMock);
    }

    /**
     * Test custom API URL is used when configured
     */
    public function testUsesCustomApiUrl(): void
    {
        $this->configMock->method('getApiKey')->willReturn('test-api-key');
        $this->configMock->method('getClickApiUrl')
            ->willReturn('https://custom.example.com/api/track/click');
        $this->setupMessageMock();

        $this->jsonMock->method('serialize')->willReturn('{}');
        $this->curlMock->method('getStatus')->willReturn(200);

        $this->curlMock->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('https://custom.example.com/api/track/click'),
                $this->anything()
            );

        $this->consumer->process($this->messageMock);
    }

    /**
     * Test API key header is set
     */
    public function testSetsApiKeyHeader(): void
    {
        $this->setupConfigMock();
        $this->setupMessageMock();

        $this->jsonMock->method('serialize')->willReturn('{}');
        $this->curlMock->method('getStatus')->willReturn(200);

        $this->curlMock->expects($this->exactly(3))
            ->method('addHeader')
            ->withConsecutive(
                ['Content-Type', 'application/json'],
                ['Accept', 'application/json'],
                ['X-Affilify-Api-Key', 'test-api-key']
            );

        $this->consumer->process($this->messageMock);
    }

    /**
     * Setup config mock with default values
     */
    private function setupConfigMock(): void
    {
        $this->configMock->method('getApiKey')->willReturn('test-api-key');
        $this->configMock->method('getClickApiUrl')
            ->willReturn('https://dashboard.affilify.it/api/track/click');
    }

    /**
     * Setup common message mock expectations
     */
    private function setupMessageMock(): void
    {
        $this->messageMock->method('getAffilfyId')->willReturn('test-affiliate-123');
        $this->messageMock->method('getReferer')->willReturn('https://google.com');
        $this->messageMock->method('getIp')->willReturn('192.168.x.x');
        $this->messageMock->method('getUserAgent')->willReturn('Mozilla/5.0');
        $this->messageMock->method('getTimestamp')->willReturn('2024-01-15T10:30:00+00:00');
    }
}
