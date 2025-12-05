<?php
/**
 * Affilify Tracking Module - Capture Click Plugin
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Plugin;

use Affilify\Tracking\Api\Constants;
use Affilify\Tracking\Api\Data\ClickMessageInterfaceFactory;
use Affilify\Tracking\Helper\Config;
use Affilify\Tracking\Logger\Logger;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Plugin to capture affiliate click tracking from URL parameters
 */
class CaptureClickPlugin
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var CookieManagerInterface
     */
    private CookieManagerInterface $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private CookieMetadataFactory $cookieMetadataFactory;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    /**
     * @var ClickMessageInterfaceFactory
     */
    private ClickMessageInterfaceFactory $clickMessageFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * CaptureClickPlugin constructor.
     *
     * @param RequestInterface $request
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param Config $config
     * @param PublisherInterface $publisher
     * @param ClickMessageInterfaceFactory $clickMessageFactory
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        RequestInterface $request,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        Config $config,
        PublisherInterface $publisher,
        ClickMessageInterfaceFactory $clickMessageFactory,
        StoreManagerInterface $storeManager,
        Logger $logger
    ) {
        $this->request = $request;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->config = $config;
        $this->publisher = $publisher;
        $this->clickMessageFactory = $clickMessageFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Capture affiliate ID before sending response
     *
     * @param ResponseInterface $subject
     * @return void
     */
    public function beforeSendResponse(ResponseInterface $subject): void
    {
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
        } catch (\Exception $e) {
            $storeId = null;
        }

        if (!$this->config->isEnabled($storeId)) {
            return;
        }

        $paramName = $this->config->getParameterName($storeId);
        $affiliateId = $this->request->getParam($paramName);

        // Validate and sanitize affiliate ID
        $affiliateId = $this->sanitizeAffiliateId($affiliateId);
        if ($affiliateId === null) {
            return;
        }

        $this->logger->debug('Capturing click', [
            'param' => $paramName,
            'affiliate_id' => $this->maskId($affiliateId)
        ]);

        try {
            // Set cookie
            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDuration($this->config->getCookieDurationSeconds($storeId))
                ->setPath('/')
                ->setHttpOnly(false)
                ->setSameSite('Lax');

            $this->cookieManager->setPublicCookie(
                Constants::COOKIE_NAME,
                $affiliateId,
                $metadata
            );

            // Publish to queue for async processing
            $clickMessage = $this->clickMessageFactory->create();
            $clickMessage->setAffilfyId($affiliateId)
                ->setIp($this->maskIp((string)($this->request->getClientIp() ?: '')))
                ->setUserAgent((string)($this->request->getHeader('User-Agent') ?: ''))
                ->setReferer((string)($this->request->getHeader('Referer') ?: ''))
                ->setTimestamp((new \DateTime())->format(\DateTime::ATOM));

            $this->publisher->publish(Constants::QUEUE_TOPIC_CLICK, $clickMessage);
            $this->logger->debug('Click queued for processing');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->error('Magento error capturing click', ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->error('Error capturing click', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Validate and sanitize affiliate ID
     *
     * @param mixed $affiliateId
     * @return string|null
     */
    private function sanitizeAffiliateId($affiliateId): ?string
    {
        if ($affiliateId === null || $affiliateId === '' || !is_string($affiliateId)) {
            return null;
        }

        // Validate format: alphanumeric, dash, underscore, max 100 chars
        if (!preg_match(Constants::AFFILIATE_ID_PATTERN, $affiliateId)) {
            $this->logger->warning('Invalid affiliate ID format rejected', [
                'affiliate_id_preview' => substr($affiliateId, 0, 20) . '...'
            ]);
            return null;
        }

        return $affiliateId;
    }

    /**
     * Mask IP address for privacy (keep first two octets)
     *
     * @param string $ip
     * @return string
     */
    private function maskIp(string $ip): string
    {
        if (empty($ip)) {
            return '';
        }

        // IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return $parts[0] . '.' . $parts[1] . '.x.x';
        }

        // IPv6 - mask last 64 bits
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . '::xxxx';
        }

        return 'x.x.x.x';
    }

    /**
     * Mask affiliate ID for logging
     *
     * @param string $id
     * @return string
     */
    private function maskId(string $id): string
    {
        if (strlen($id) <= 4) {
            return '****';
        }
        return substr($id, 0, 2) . '****' . substr($id, -2);
    }
}
