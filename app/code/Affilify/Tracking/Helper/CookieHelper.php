<?php
/**
 * Affilify Tracking Module - Cookie Helper
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Helper;

use Affilify\Tracking\Logger\Logger;
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
     * @var Logger
     */
    private Logger $logger;

    /**
     * CookieHelper constructor.
     *
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManager
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        Config $config,
        Logger $logger
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Set the tracking cookie (overwrites existing - last-touch attribution)
     *
     * @param string $value
     * @return void
     */
    public function setTrackingCookie(string $value): void
    {
        $this->logger->info('CookieHelper: setTrackingCookie called with value: ' . $value);
        
        try {
            $duration = $this->config->getCookieDurationSeconds();
            $this->logger->info('CookieHelper: cookie duration = ' . $duration);
            
            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDuration($duration)
                ->setPath('/')
                ->setHttpOnly(false)
                ->setSameSite('Lax');

            $this->logger->info('CookieHelper: setting cookie ' . self::COOKIE_NAME);
            
            $this->cookieManager->setPublicCookie(
                self::COOKIE_NAME,
                $value,
                $metadata
            );
            
            $this->logger->info('CookieHelper: cookie set successfully');
        } catch (\Exception $e) {
            $this->logger->error('CookieHelper: error setting cookie - ' . $e->getMessage());
        }
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
