<?php
/**
 * Affilify Tracking Module - Track Conversion Observer
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Observer;

use Affilify\Tracking\Api\Data\ConversionMessageInterface;
use Affilify\Tracking\Api\Data\ConversionMessageInterfaceFactory;
use Affilify\Tracking\Helper\Config;
use Affilify\Tracking\Helper\CookieHelper;
use Affilify\Tracking\Logger\Logger;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Header;
use Magento\Framework\MessageQueue\PublisherInterface;

class TrackConversionObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var CookieHelper
     */
    private CookieHelper $cookieHelper;

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    /**
     * @var ConversionMessageInterfaceFactory
     */
    private ConversionMessageInterfaceFactory $conversionMessageFactory;

    /**
     * @var Header
     */
    private Header $httpHeader;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * TrackConversionObserver constructor.
     *
     * @param Config $config
     * @param CookieHelper $cookieHelper
     * @param CheckoutSession $checkoutSession
     * @param PublisherInterface $publisher
     * @param ConversionMessageInterfaceFactory $conversionMessageFactory
     * @param Header $httpHeader
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        CookieHelper $cookieHelper,
        CheckoutSession $checkoutSession,
        PublisherInterface $publisher,
        ConversionMessageInterfaceFactory $conversionMessageFactory,
        Header $httpHeader,
        Logger $logger
    ) {
        $this->config = $config;
        $this->cookieHelper = $cookieHelper;
        $this->checkoutSession = $checkoutSession;
        $this->publisher = $publisher;
        $this->conversionMessageFactory = $conversionMessageFactory;
        $this->httpHeader = $httpHeader;
        $this->logger = $logger;
    }

    /**
     * Track conversion on successful checkout
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $this->logger->info('TrackConversionObserver: execute() called');
        
        // Check if tracking is enabled
        if (!$this->config->isEnabled()) {
            $this->logger->info('TrackConversionObserver: module disabled');
            return;
        }

        // Check if we have a tracking cookie
        $affilfyId = $this->cookieHelper->getTrackingCookie();
        $this->logger->info('TrackConversionObserver: cookie value = ' . ($affilfyId ?: 'null'));
        
        if (empty($affilfyId)) {
            $this->logger->info('TrackConversionObserver: no tracking cookie, skipping');
            return;
        }

        // Get tracking domain
        $trackingDomain = $this->config->getTrackingDomain();
        if (empty($trackingDomain)) {
            $this->logger->info('TrackConversionObserver: no tracking domain configured');
            return;
        }

        // Get the order from checkout session
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order || !$order->getId()) {
            $this->logger->info('TrackConversionObserver: no order found');
            return;
        }

        $this->logger->info('TrackConversionObserver: tracking conversion for order ' . $order->getIncrementId() . ', total: ' . $order->getGrandTotal());

        // Publish conversion message to queue
        /** @var ConversionMessageInterface $conversionMessage */
        $conversionMessage = $this->conversionMessageFactory->create();
        $conversionMessage->setAffilfyId($affilfyId)
            ->setOrderId($order->getIncrementId())
            ->setCheckoutTotal((string) $order->getGrandTotal())
            ->setReferer($this->httpHeader->getHttpReferer() ?: '-')
            ->setTimestamp(date('Y-m-d H:i:s'))
            ->setTrackingDomain($trackingDomain);

        $this->publisher->publish('affilify.tracking.conversion', $conversionMessage);
        $this->logger->info('TrackConversionObserver: conversion message published to queue');

        // Delete the tracking cookie after successful conversion
        $this->cookieHelper->deleteTrackingCookie();
        $this->logger->info('TrackConversionObserver: tracking cookie deleted');
    }
}
