<?php
/**
 * Affilify Tracking Module - Conversion Message Interface
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Api\Data;

interface ConversionMessageInterface
{
    /**
     * Get affilify ID
     *
     * @return string
     */
    public function getAffilfyId(): string;

    /**
     * Set affilify ID
     *
     * @param string $affilfyId
     * @return self
     */
    public function setAffilfyId(string $affilfyId): self;

    /**
     * Get order ID
     *
     * @return string
     */
    public function getOrderId(): string;

    /**
     * Set order ID
     *
     * @param string $orderId
     * @return self
     */
    public function setOrderId(string $orderId): self;

    /**
     * Get checkout total
     *
     * @return string
     */
    public function getCheckoutTotal(): string;

    /**
     * Set checkout total
     *
     * @param string $checkoutTotal
     * @return self
     */
    public function setCheckoutTotal(string $checkoutTotal): self;

    /**
     * Get currency code
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Set currency code
     *
     * @param string $currency
     * @return self
     */
    public function setCurrency(string $currency): self;

    /**
     * Get referer URL
     *
     * @return string
     */
    public function getReferer(): string;

    /**
     * Set referer URL
     *
     * @param string $referer
     * @return self
     */
    public function setReferer(string $referer): self;

    /**
     * Get timestamp
     *
     * @return string
     */
    public function getTimestamp(): string;

    /**
     * Set timestamp
     *
     * @param string $timestamp
     * @return self
     */
    public function setTimestamp(string $timestamp): self;

    /**
     * Get tracking domain
     *
     * @return string
     */
    public function getTrackingDomain(): string;

    /**
     * Set tracking domain
     *
     * @param string $trackingDomain
     * @return self
     */
    public function setTrackingDomain(string $trackingDomain): self;
}
