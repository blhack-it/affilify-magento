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
}
