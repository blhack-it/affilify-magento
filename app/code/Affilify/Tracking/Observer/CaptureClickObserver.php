<?php
/**
 * Affilify Tracking Module - Capture Click Observer
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Observer;

use Affilify\Tracking\Api\Data\ClickMessageInterface;
use Affilify\Tracking\Api\Data\ClickMessageInterfaceFactory;
use Affilify\Tracking\Helper\Config;
use Affilify\Tracking\Helper\CookieHelper;
use Affilify\Tracking\Logger\Logger;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Header;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\MessageQueue\PublisherInterface;

class CaptureClickObserver implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var CookieHelper
     */
    private CookieHelper $cookieHelper;

    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    /**
     * @var ClickMessageInterfaceFactory
     */
    private ClickMessageInterfaceFactory $clickMessageFactory;

    /**
     * @var Header
     */
    private Header $httpHeader;

    /**
     * @var RemoteAddress
     */
    private RemoteAddress $remoteAddress;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * CaptureClickObserver constructor.
     *
     * @param RequestInterface $request
     * @param Config $config
     * @param CookieHelper $cookieHelper
     * @param PublisherInterface $publisher
     * @param ClickMessageInterfaceFactory $clickMessageFactory
     * @param Header $httpHeader
     * @param RemoteAddress $remoteAddress
     * @param Logger $logger
     */
    public function __construct(
        RequestInterface $request,
        Config $config,
        CookieHelper $cookieHelper,
        PublisherInterface $publisher,
        ClickMessageInterfaceFactory $clickMessageFactory,
        Header $httpHeader,
        RemoteAddress $remoteAddress,
        Logger $logger
    ) {
        $this->request = $request;
        $this->config = $config;
        $this->cookieHelper = $cookieHelper;
        $this->publisher = $publisher;
        $this->clickMessageFactory = $clickMessageFactory;
        $this->httpHeader = $httpHeader;
        $this->remoteAddress = $remoteAddress;
        $this->logger = $logger;
    }

    /**
     * Capture URL parameter and set tracking cookie
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $this->logger->info('CaptureClickObserver: execute() called');
        
        // Check if tracking is enabled
        if (!$this->config->isEnabled()) {
            $this->logger->info('CaptureClickObserver: tracking is disabled');
            return;
        }

        // Get the configured parameter name
        $parameterName = $this->config->getParameterName();
        if (empty($parameterName)) {
            $this->logger->info('CaptureClickObserver: parameter name is empty');
            return;
        }

        // Check if the tracking parameter is present in the URL
        $affilfyId = $this->request->getParam($parameterName);
        $this->logger->info('CaptureClickObserver: looking for param "' . $parameterName . '", found: "' . ($affilfyId ?: 'null') . '"');
        
        if (empty($affilfyId)) {
            return;
        }

        // Get tracking domain
        $trackingDomain = $this->config->getTrackingDomain();
        if (empty($trackingDomain)) {
            $this->logger->info('CaptureClickObserver: tracking domain is empty');
            return;
        }

        $this->logger->info('CaptureClickObserver: capturing click for affilify_id=' . $affilfyId);

        // Set/overwrite the tracking cookie (last-touch attribution)
        $this->cookieHelper->setTrackingCookie($affilfyId);

        // Publish click message to queue
        /** @var ClickMessageInterface $clickMessage */
        $clickMessage = $this->clickMessageFactory->create();
        $clickMessage->setAffilfyId($affilfyId)
            ->setReferer($this->httpHeader->getHttpReferer() ?: '-')
            ->setIp($this->remoteAddress->getRemoteAddress() ?: '-')
            ->setUserAgent($this->httpHeader->getHttpUserAgent() ?: '-')
            ->setTimestamp(date('Y-m-d H:i:s'))
            ->setTrackingDomain($trackingDomain);

        $this->publisher->publish('affilify.tracking.click', $clickMessage);
        $this->logger->info('CaptureClickObserver: click message published to queue');
    }
}
