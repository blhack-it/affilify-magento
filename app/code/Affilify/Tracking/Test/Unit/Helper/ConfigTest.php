<?php
/**
 * Affilify Tracking Module - Config Helper Test
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Test\Unit\Helper;

use Affilify\Tracking\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $contextMock = $this->createMock(Context::class);
        $contextMock->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);

        $this->config = new Config($contextMock);
    }

    /**
     * Test isEnabled returns true when enabled
     */
    public function testIsEnabledReturnsTrue(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with(
                'affilify_tracking/general/enabled',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn(true);

        $this->assertTrue($this->config->isEnabled());
    }

    /**
     * Test isEnabled returns false when disabled
     */
    public function testIsEnabledReturnsFalse(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with(
                'affilify_tracking/general/enabled',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn(false);

        $this->assertFalse($this->config->isEnabled());
    }

    /**
     * Test isEnabled with specific store ID
     */
    public function testIsEnabledWithStoreId(): void
    {
        $storeId = 2;

        $this->scopeConfigMock->method('isSetFlag')
            ->with(
                'affilify_tracking/general/enabled',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn(true);

        $this->assertTrue($this->config->isEnabled($storeId));
    }

    /**
     * Test getApiKey returns configured key
     */
    public function testGetApiKey(): void
    {
        $apiKey = 'test-api-key-123';

        $this->scopeConfigMock->method('getValue')
            ->with(
                'affilify_tracking/general/api_key',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($apiKey);

        $this->assertEquals($apiKey, $this->config->getApiKey());
    }

    /**
     * Test getApiKey returns empty string when not configured
     */
    public function testGetApiKeyReturnsEmptyWhenNull(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn(null);

        $this->assertEquals('', $this->config->getApiKey());
    }

    /**
     * Test getApiUrl returns configured URL
     */
    public function testGetApiUrl(): void
    {
        $apiUrl = 'https://custom.api.example.com/track';

        $this->scopeConfigMock->method('getValue')
            ->with(
                'affilify_tracking/general/api_url',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($apiUrl);

        $this->assertEquals($apiUrl, $this->config->getApiUrl());
    }

    /**
     * Test getApiUrl returns default when not configured
     */
    public function testGetApiUrlReturnsDefaultWhenEmpty(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn('');

        $this->assertEquals('https://dashboard.affilify.it/api/track', $this->config->getApiUrl());
    }

    /**
     * Test getParameterName returns configured parameter
     */
    public function testGetParameterName(): void
    {
        $paramName = 'ref';

        $this->scopeConfigMock->method('getValue')
            ->with(
                'affilify_tracking/general/parameter_name',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($paramName);

        $this->assertEquals($paramName, $this->config->getParameterName());
    }

    /**
     * Test getCookieDuration returns configured days
     */
    public function testGetCookieDuration(): void
    {
        $days = 30;

        $this->scopeConfigMock->method('getValue')
            ->with(
                'affilify_tracking/general/cookie_duration',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($days);

        $this->assertEquals($days, $this->config->getCookieDuration());
    }

    /**
     * Test getCookieDuration returns 0 when not configured
     */
    public function testGetCookieDurationReturnsZeroWhenNull(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn(null);

        $this->assertEquals(0, $this->config->getCookieDuration());
    }

    /**
     * Test getCookieDurationSeconds calculates correctly
     */
    public function testGetCookieDurationSeconds(): void
    {
        $days = 30;
        $expectedSeconds = 30 * 24 * 60 * 60; // 2592000

        $this->scopeConfigMock->method('getValue')
            ->willReturn($days);

        $this->assertEquals($expectedSeconds, $this->config->getCookieDurationSeconds());
    }

    /**
     * Test getClickApiUrl builds correct URL
     */
    public function testGetClickApiUrl(): void
    {
        $apiUrl = 'https://dashboard.affilify.it/api/track';

        $this->scopeConfigMock->method('getValue')
            ->willReturn($apiUrl);

        $this->assertEquals('https://dashboard.affilify.it/api/track/click', $this->config->getClickApiUrl());
    }

    /**
     * Test getClickApiUrl with custom URL
     */
    public function testGetClickApiUrlWithCustomUrl(): void
    {
        $apiUrl = 'https://custom.example.com/api/track';

        $this->scopeConfigMock->method('getValue')
            ->willReturn($apiUrl);

        $this->assertEquals('https://custom.example.com/api/track/click', $this->config->getClickApiUrl());
    }

    /**
     * Test getClickApiUrl returns default URL when not configured
     */
    public function testGetClickApiUrlReturnsDefaultWhenEmpty(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn('');

        $this->assertEquals('https://dashboard.affilify.it/api/track/click', $this->config->getClickApiUrl());
    }

    /**
     * Test getConversionApiUrl builds correct URL
     */
    public function testGetConversionApiUrl(): void
    {
        $apiUrl = 'https://dashboard.affilify.it/api/track';

        $this->scopeConfigMock->method('getValue')
            ->willReturn($apiUrl);

        $this->assertEquals('https://dashboard.affilify.it/api/track/conversion', $this->config->getConversionApiUrl());
    }

    /**
     * Test getConversionApiUrl with custom URL
     */
    public function testGetConversionApiUrlWithCustomUrl(): void
    {
        $apiUrl = 'https://custom.example.com/api/track';

        $this->scopeConfigMock->method('getValue')
            ->willReturn($apiUrl);

        $this->assertEquals('https://custom.example.com/api/track/conversion', $this->config->getConversionApiUrl());
    }

    /**
     * Test isDebugMode returns true when enabled
     */
    public function testIsDebugModeReturnsTrue(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with(
                'affilify_tracking/general/debug_mode',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn(true);

        $this->assertTrue($this->config->isDebugMode());
    }

    /**
     * Test isDebugMode returns false when disabled
     */
    public function testIsDebugModeReturnsFalse(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with(
                'affilify_tracking/general/debug_mode',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn(false);

        $this->assertFalse($this->config->isDebugMode());
    }
}
