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
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        CookieHelper $cookieHelper,
        CheckoutSession $checkoutSession,
        PublisherInterface $publisher,
        ConversionMessageInterfaceFactory $conversionMessageFactory,
        Logger $logger
    ) {
        $this->config = $config;
        $this->cookieHelper = $cookieHelper;
        $this->checkoutSession = $checkoutSession;
        $this->publisher = $publisher;
        $this->conversionMessageFactory = $conversionMessageFactory;
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
        $this->logger->debug('TrackConversionObserver: execute() called');

        // Check if tracking is enabled
        if (!$this->config->isEnabled()) {
            $this->logger->debug('TrackConversionObserver: module disabled');
            return;
        }

        // Check if we have a tracking cookie
        $affilifyId = $this->cookieHelper->getTrackingCookie();
        $this->logger->debug('TrackConversionObserver: cookie present = ' . ($affilifyId ? 'yes' : 'no'));

        if (empty($affilifyId)) {
            $this->logger->debug('TrackConversionObserver: no tracking cookie, skipping');
            return;
        }

        // Get the order from checkout session
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order || !$order->getId()) {
            $this->logger->debug('TrackConversionObserver: no order found');
            return;
        }

        $this->logger->info('TrackConversionObserver: tracking conversion for order ' . $order->getIncrementId());

        // Publish conversion message to queue
        /** @var ConversionMessageInterface $conversionMessage */
        $conversionMessage = $this->conversionMessageFactory->create();
        $conversionMessage->setAffilifyId($affilifyId)
            ->setOrderId($order->getIncrementId())
            ->setCheckoutTotal((string) $order->getGrandTotal())
            ->setCurrency($order->getOrderCurrencyCode() ?: 'EUR');

        $this->publisher->publish('affilify.tracking.conversion', $conversionMessage);
        $this->logger->debug('TrackConversionObserver: conversion message published to queue');

        // Delete the tracking cookie after successful conversion
        // Wrapped in try-catch because cookie deletion fails in CLI/testing environments
        try {
            $this->cookieHelper->deleteTrackingCookie();
            $this->logger->debug('TrackConversionObserver: tracking cookie deleted');
        } catch (\Exception $e) {
            $this->logger->debug('TrackConversionObserver: could not delete cookie (expected in CLI): ' . $e->getMessage());
        }
    }
}
