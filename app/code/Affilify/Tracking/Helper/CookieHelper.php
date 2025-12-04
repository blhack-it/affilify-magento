<?php
/**
 * Affilify Tracking Module - Cookie Helper
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Helper;

use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;

class CookieHelper
{
    private const COOKIE_NAME = 'affilify_tracking';

    /**
     * @var CookieManagerInterface
     */
    private CookieManagerInterface $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private CookieMetadataFactory $cookieMetadataFactory;

    /**
     * @var SessionManagerInterface
     */
    private SessionManagerInterface $sessionManager;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * CookieHelper constructor.
     *
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManager
     * @param Config $config
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        Config $config
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->config = $config;
    }

    /**
     * Set the tracking cookie (overwrites existing - last-touch attribution)
     *
     * @param string $value
     * @return void
     */
    public function setTrackingCookie(string $value): void
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($this->config->getCookieDurationSeconds())
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain())
            ->setHttpOnly(false)
            ->setSecure(true)
            ->setSameSite('Lax');

        $this->cookieManager->setPublicCookie(
            self::COOKIE_NAME,
            $value,
            $metadata
        );
    }

    /**
     * Get the tracking cookie value
     *
     * @return string|null
     */
    public function getTrackingCookie(): ?string
    {
        return $this->cookieManager->getCookie(self::COOKIE_NAME);
    }

    /**
     * Delete the tracking cookie
     *
     * @return void
     */
    public function deleteTrackingCookie(): void
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain());

        $this->cookieManager->deleteCookie(self::COOKIE_NAME, $metadata);
    }

    /**
     * Check if tracking cookie exists
     *
     * @return bool
     */
    public function hasTrackingCookie(): bool
    {
        return $this->getTrackingCookie() !== null;
    }
}
