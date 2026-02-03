<?php
/**
 * Affilify Tracking Module - CaptureClickPlugin Test
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Test\Unit\Plugin;

use Affilify\Tracking\Api\Constants;
use Affilify\Tracking\Api\Data\ClickMessageInterface;
use Affilify\Tracking\Api\Data\ClickMessageInterfaceFactory;
use Affilify\Tracking\Helper\Config;
use Affilify\Tracking\Logger\Logger;
use Affilify\Tracking\Plugin\CaptureClickPlugin;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CaptureClickPluginTest extends TestCase
{
    /**
     * @var CaptureClickPlugin
     */
    private CaptureClickPlugin $plugin;

    /**
     * @var HttpRequest|MockObject
     */
    private $requestMock;

    /**
     * @var CookieManagerInterface|MockObject
     */
    private $cookieManagerMock;

    /**
     * @var CookieMetadataFactory|MockObject
     */
    private $cookieMetadataFactoryMock;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var PublisherInterface|MockObject
     */
    private $publisherMock;

    /**
     * @var ClickMessageInterfaceFactory|MockObject
     */
    private $clickMessageFactoryMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ResponseInterface|MockObject
     */
    private $responseMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(HttpRequest::class);
        $this->cookieManagerMock = $this->createMock(CookieManagerInterface::class);
        $this->cookieMetadataFactoryMock = $this->createMock(CookieMetadataFactory::class);
        $this->configMock = $this->createMock(Config::class);
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->clickMessageFactoryMock = $this->createMock(ClickMessageInterfaceFactory::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);

        // Setup default store mock
        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(1);
        $this->storeManagerMock->method('getStore')->willReturn($storeMock);

        $this->plugin = new CaptureClickPlugin(
            $this->requestMock,
            $this->cookieManagerMock,
            $this->cookieMetadataFactoryMock,
            $this->configMock,
            $this->publisherMock,
            $this->clickMessageFactoryMock,
            $this->storeManagerMock,
            $this->loggerMock
        );
    }

    /**
     * Test that nothing happens when tracking is disabled
     */
    public function testBeforeSendResponseDoesNothingWhenDisabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(false);

        // Cookie should never be set
        $this->cookieManagerMock->expects($this->never())
            ->method('setPublicCookie');

        // Publisher should never be called
        $this->publisherMock->expects($this->never())
            ->method('publish');

        $this->plugin->beforeSendResponse($this->responseMock);
    }

    /**
     * Test that nothing happens when no affiliate ID in URL
     */
    public function testBeforeSendResponseDoesNothingWhenNoAffiliateId(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->configMock->method('getParameterName')->willReturn('affilify_id');
        $this->requestMock->method('getParam')->with('affilify_id')->willReturn(null);

        $this->cookieManagerMock->expects($this->never())
            ->method('setPublicCookie');

        $this->publisherMock->expects($this->never())
            ->method('publish');

        $this->plugin->beforeSendResponse($this->responseMock);
    }

    /**
     * Test that empty affiliate ID is rejected
     */
    public function testBeforeSendResponseRejectsEmptyAffiliateId(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->configMock->method('getParameterName')->willReturn('affilify_id');
        $this->requestMock->method('getParam')->with('affilify_id')->willReturn('');

        $this->cookieManagerMock->expects($this->never())
            ->method('setPublicCookie');

        $this->plugin->beforeSendResponse($this->responseMock);
    }

    /**
     * Test that invalid affiliate ID format is rejected
     *
     * @dataProvider invalidAffiliateIdProvider
     */
    public function testBeforeSendResponseRejectsInvalidAffiliateId(string $invalidId): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->configMock->method('getParameterName')->willReturn('affilify_id');
        $this->requestMock->method('getParam')->with('affilify_id')->willReturn($invalidId);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'Invalid affiliate ID format rejected',
                $this->anything()
            );

        $this->cookieManagerMock->expects($this->never())
            ->method('setPublicCookie');

        $this->plugin->beforeSendResponse($this->responseMock);
    }

    /**
     * Data provider for invalid affiliate IDs
     */
    public static function invalidAffiliateIdProvider(): array
    {
        return [
            'contains space' => ['abc 123'],
            'contains special char' => ['abc@123'],
            'contains dot' => ['abc.123'],
            'XSS attempt' => ['<script>alert(1)</script>'],
            'SQL injection' => ["'; DROP TABLE users;--"],
            'too long' => [str_repeat('a', 101)],
        ];
    }

    /**
     * Test that valid affiliate ID sets cookie and publishes message
     */
    public function testBeforeSendResponseSetsCoookieAndPublishesMessage(): void
    {
        $affiliateId = 'valid-affiliate-123';
        $cookieDuration = 2592000;

        $this->configMock->method('isEnabled')->willReturn(true);
        $this->configMock->method('getParameterName')->willReturn('affilify_id');
        $this->configMock->method('getCookieDurationSeconds')->willReturn($cookieDuration);

        $this->requestMock->method('getParam')->with('affilify_id')->willReturn($affiliateId);
        $this->requestMock->method('getClientIp')->willReturn('192.168.1.100');
        $this->requestMock->method('getHeader')
            ->willReturnMap([
                ['User-Agent', 'Mozilla/5.0'],
                ['Referer', 'https://google.com'],
            ]);

        // Cookie metadata setup
        $cookieMetadataMock = $this->createMock(PublicCookieMetadata::class);
        $cookieMetadataMock->method('setDuration')->willReturnSelf();
        $cookieMetadataMock->method('setPath')->willReturnSelf();
        $cookieMetadataMock->method('setHttpOnly')->willReturnSelf();
        $cookieMetadataMock->method('setSameSite')->willReturnSelf();

        $this->cookieMetadataFactoryMock->method('createPublicCookieMetadata')
            ->willReturn($cookieMetadataMock);

        // Expect cookie to be set
        $this->cookieManagerMock->expects($this->once())
            ->method('setPublicCookie')
            ->with(
                Constants::COOKIE_NAME,
                $affiliateId,
                $cookieMetadataMock
            );

        // Click message setup
        $clickMessageMock = $this->createMock(ClickMessageInterface::class);
        $clickMessageMock->method('setAffilifyId')->willReturnSelf();
        $clickMessageMock->method('setIp')->willReturnSelf();
        $clickMessageMock->method('setUserAgent')->willReturnSelf();
        $clickMessageMock->method('setReferer')->willReturnSelf();
        $clickMessageMock->method('setTimestamp')->willReturnSelf();
        $clickMessageMock->method('setTrackingDomain')->willReturnSelf();

        $this->clickMessageFactoryMock->method('create')
            ->willReturn($clickMessageMock);

        // Expect message to be published
        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with(
                Constants::QUEUE_TOPIC_CLICK,
                $clickMessageMock
            );

        $this->plugin->beforeSendResponse($this->responseMock);
    }

    /**
     * Test valid affiliate IDs are accepted
     *
     * @dataProvider validAffiliateIdProvider
     */
    public function testBeforeSendResponseAcceptsValidAffiliateId(string $validId): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->configMock->method('getParameterName')->willReturn('affilify_id');
        $this->configMock->method('getCookieDurationSeconds')->willReturn(2592000);

        $this->requestMock->method('getParam')->with('affilify_id')->willReturn($validId);
        $this->requestMock->method('getClientIp')->willReturn('127.0.0.1');
        $this->requestMock->method('getHeader')->willReturn('');

        // Cookie metadata setup
        $cookieMetadataMock = $this->createMock(PublicCookieMetadata::class);
        $cookieMetadataMock->method('setDuration')->willReturnSelf();
        $cookieMetadataMock->method('setPath')->willReturnSelf();
        $cookieMetadataMock->method('setHttpOnly')->willReturnSelf();
        $cookieMetadataMock->method('setSameSite')->willReturnSelf();

        $this->cookieMetadataFactoryMock->method('createPublicCookieMetadata')
            ->willReturn($cookieMetadataMock);

        // Click message setup
        $clickMessageMock = $this->createMock(ClickMessageInterface::class);
        $clickMessageMock->method('setAffilifyId')->willReturnSelf();
        $clickMessageMock->method('setIp')->willReturnSelf();
        $clickMessageMock->method('setUserAgent')->willReturnSelf();
        $clickMessageMock->method('setReferer')->willReturnSelf();
        $clickMessageMock->method('setTimestamp')->willReturnSelf();
        $clickMessageMock->method('setTrackingDomain')->willReturnSelf();

        $this->clickMessageFactoryMock->method('create')
            ->willReturn($clickMessageMock);

        // Expect cookie to be set with valid ID
        $this->cookieManagerMock->expects($this->once())
            ->method('setPublicCookie')
            ->with(
                Constants::COOKIE_NAME,
                $validId,
                $this->anything()
            );

        $this->plugin->beforeSendResponse($this->responseMock);
    }

    /**
     * Data provider for valid affiliate IDs
     */
    public static function validAffiliateIdProvider(): array
    {
        return [
            'simple alphanumeric' => ['abc123'],
            'with dash' => ['test-affiliate-123'],
            'with underscore' => ['test_affiliate_123'],
            'uppercase' => ['ABC123'],
            'mixed case' => ['AbC-123_XyZ'],
            'single char' => ['a'],
            'max length' => [str_repeat('a', 100)],
            'numbers only' => ['123456789'],
        ];
    }

    /**
     * Test exception handling
     */
    public function testBeforeSendResponseHandlesException(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);
        $this->configMock->method('getParameterName')->willReturn('affilify_id');
        $this->configMock->method('getCookieDurationSeconds')->willReturn(2592000);

        $this->requestMock->method('getParam')->willReturn('valid-id');
        $this->requestMock->method('getClientIp')->willReturn('127.0.0.1');
        $this->requestMock->method('getHeader')->willReturn('');

        // Cookie metadata setup
        $cookieMetadataMock = $this->createMock(PublicCookieMetadata::class);
        $cookieMetadataMock->method('setDuration')->willReturnSelf();
        $cookieMetadataMock->method('setPath')->willReturnSelf();
        $cookieMetadataMock->method('setHttpOnly')->willReturnSelf();
        $cookieMetadataMock->method('setSameSite')->willReturnSelf();

        $this->cookieMetadataFactoryMock->method('createPublicCookieMetadata')
            ->willReturn($cookieMetadataMock);

        // Simulate exception when setting cookie
        $this->cookieManagerMock->method('setPublicCookie')
            ->willThrowException(new \Exception('Cookie error'));

        // Expect error to be logged
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Error capturing click',
                $this->anything()
            );

        // Should not throw exception
        $this->plugin->beforeSendResponse($this->responseMock);
    }
}
