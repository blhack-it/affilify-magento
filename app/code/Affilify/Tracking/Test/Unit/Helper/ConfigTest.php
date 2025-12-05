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
     * Test getTrackingDomain returns configured domain
     */
    public function testGetTrackingDomain(): void
    {
        $domain = 't.example.com';

        $this->scopeConfigMock->method('getValue')
            ->with(
                'affilify_tracking/general/tracking_domain',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($domain);

        $this->assertEquals($domain, $this->config->getTrackingDomain());
    }

    /**
     * Test getTrackingDomain returns empty string when not configured
     */
    public function testGetTrackingDomainReturnsEmptyWhenNull(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn(null);

        $this->assertEquals('', $this->config->getTrackingDomain());
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
        $domain = 't.example.com';

        $this->scopeConfigMock->method('getValue')
            ->willReturn($domain);

        $this->assertEquals('https://t.example.com/m/click', $this->config->getClickApiUrl());
    }

    /**
     * Test getClickApiUrl handles trailing slash
     */
    public function testGetClickApiUrlHandlesTrailingSlash(): void
    {
        $domain = 't.example.com/';

        $this->scopeConfigMock->method('getValue')
            ->willReturn($domain);

        $this->assertEquals('https://t.example.com/m/click', $this->config->getClickApiUrl());
    }

    /**
     * Test getClickApiUrl returns empty when no domain
     */
    public function testGetClickApiUrlReturnsEmptyWhenNoDomain(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->willReturn('');

        $this->assertEquals('', $this->config->getClickApiUrl());
    }

    /**
     * Test getConversionApiUrl builds correct URL
     */
    public function testGetConversionApiUrl(): void
    {
        $domain = 't.example.com';

        $this->scopeConfigMock->method('getValue')
            ->willReturn($domain);

        $this->assertEquals('https://t.example.com/m/conv', $this->config->getConversionApiUrl());
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
