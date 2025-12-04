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
    private const XML_PATH_TRACKING_DOMAIN = 'affilify_tracking/general/tracking_domain';
    private const XML_PATH_PARAMETER_NAME = 'affilify_tracking/general/parameter_name';
    private const XML_PATH_COOKIE_DURATION = 'affilify_tracking/general/cookie_duration';

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
     * Get the tracking domain
     *
     * @param int|null $storeId
     * @return string
     */
    public function getTrackingDomain(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_TRACKING_DOMAIN,
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
     * Get the full API URL for click tracking
     *
     * @param int|null $storeId
     * @return string
     */
    public function getClickApiUrl(?int $storeId = null): string
    {
        $domain = $this->getTrackingDomain($storeId);
        return $domain ? 'https://' . rtrim($domain, '/') . '/m/click' : '';
    }

    /**
     * Get the full API URL for conversion tracking
     *
     * @param int|null $storeId
     * @return string
     */
    public function getConversionApiUrl(?int $storeId = null): string
    {
        $domain = $this->getTrackingDomain($storeId);
        return $domain ? 'https://' . rtrim($domain, '/') . '/m/conv' : '';
    }
}
