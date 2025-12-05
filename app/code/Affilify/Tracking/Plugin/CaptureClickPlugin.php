<?php
/**
 * Affilify Tracking Module - Capture Click Plugin
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Plugin;

use Affilify\Tracking\Helper\Config;
use Affilify\Tracking\Helper\CookieHelper;
use Affilify\Tracking\Logger\Logger;
use Affilify\Tracking\Model\ClickMessage;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class CaptureClickPlugin
{
    private const COOKIE_NAME = 'affilify_tracking';
    private const QUEUE_TOPIC = 'affilify.tracking.click';

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
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param RequestInterface $request
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param Config $config
     * @param PublisherInterface $publisher
     * @param Logger $logger
     */
    public function __construct(
        RequestInterface $request,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        Config $config,
        PublisherInterface $publisher,
        Logger $logger
    ) {
        $this->request = $request;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->config = $config;
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * After send response, set cookie
     *
     * @param ResponseInterface $subject
     * @return void
     */
    public function beforeSendResponse(ResponseInterface $subject): void
    {
        $this->logger->info('CaptureClickPlugin: beforeSendResponse called');

        if (!$this->config->isEnabled()) {
            $this->logger->info('CaptureClickPlugin: module disabled');
            return;
        }

        $paramName = $this->config->getParameterName();
        $affiliateId = $this->request->getParam($paramName);

        $this->logger->info('CaptureClickPlugin: looking for param "' . $paramName . '", found: "' . ($affiliateId ?? 'null') . '"');

        if (empty($affiliateId)) {
            return;
        }

        $this->logger->info('CaptureClickPlugin: capturing click for ' . $paramName . '=' . $affiliateId);

        try {
            // Set cookie directly
            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDuration($this->config->getCookieDurationSeconds())
                ->setPath('/')
                ->setHttpOnly(false)
                ->setSameSite('Lax');

            $this->cookieManager->setPublicCookie(
                self::COOKIE_NAME,
                $affiliateId,
                $metadata
            );

            $this->logger->info('CaptureClickPlugin: cookie set successfully');

            // Publish to queue for async processing
            $clickMessage = new ClickMessage();
            $clickMessage->setAffilfyId($affiliateId);
            $clickMessage->setIp((string)($this->request->getClientIp() ?: ''));
            $clickMessage->setUserAgent((string)($this->request->getHeader('User-Agent') ?: ''));
            $clickMessage->setReferer((string)($this->request->getHeader('Referer') ?: ''));
            $clickMessage->setTimestamp((string)time());
            $clickMessage->setTrackingDomain($this->config->getTrackingDomain());

            $this->publisher->publish(self::QUEUE_TOPIC, $clickMessage);
            $this->logger->info('CaptureClickPlugin: click message published to queue with tracking domain: ' . $this->config->getTrackingDomain());
        } catch (\Exception $e) {
            $this->logger->error('CaptureClickPlugin: error - ' . $e->getMessage());
        }
    }
}
