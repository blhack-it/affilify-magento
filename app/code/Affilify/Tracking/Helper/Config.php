<?php
/**
 * Affilify Tracking Module - Configuration Helper
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    private const XML_PATH_ENABLED = 'affilify_tracking/general/enabled';
    private const XML_PATH_API_KEY = 'affilify_tracking/general/api_key';
    private const XML_PATH_API_URL = 'affilify_tracking/general/api_url';
    private const XML_PATH_PARAMETER_NAME = 'affilify_tracking/general/parameter_name';
    private const XML_PATH_COOKIE_DURATION = 'affilify_tracking/general/cookie_duration';
    private const XML_PATH_DEBUG_MODE = 'affilify_tracking/general/debug_mode';

    /**
     * Default Affilify API base URL
     */
    private const DEFAULT_API_URL = 'https://dashboard.affilify.it/api/track';

    /**
     * Check if tracking is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the API key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiKey(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the URL parameter name to capture
     *
     * @param int|null $storeId
     * @return string
     */
    public function getParameterName(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PARAMETER_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get cookie duration in days
     *
     * @param int|null $storeId
     * @return int
     */
    public function getCookieDuration(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_COOKIE_DURATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get cookie duration in seconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getCookieDurationSeconds(?int $storeId = null): int
    {
        return $this->getCookieDuration($storeId) * 24 * 60 * 60;
    }

    /**
     * Get the API base URL
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiUrl(?int $storeId = null): string
    {
        $url = $this->scopeConfig->getValue(
            self::XML_PATH_API_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $url ?: self::DEFAULT_API_URL;
    }

    /**
     * Get the full API URL for click tracking
     *
     * @return string
     */
    public function getClickApiUrl(): string
    {
        return $this->getApiUrl() . '/click';
    }

    /**
     * Get the full API URL for conversion tracking
     *
     * @return string
     */
    public function getConversionApiUrl(): string
    {
        return $this->getApiUrl() . '/conversion';
    }

    /**
     * Check if debug mode is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDebugMode(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEBUG_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
