<?php
/**
 * Affilify Tracking Module - Conversion Message Model
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Model;

use Affilify\Tracking\Api\Data\ConversionMessageInterface;

class ConversionMessage implements ConversionMessageInterface
{
    /**
     * @var string
     */
    private string $affilfyId = '';

    /**
     * @var string
     */
    private string $orderId = '';

    /**
     * @var string
     */
    private string $checkoutTotal = '';

    /**
     * @var string
     */
    private string $currency = '';

    /**
     * @var string
     */
    private string $referer = '';

    /**
     * @var string
     */
    private string $timestamp = '';

    /**
     * @var string
     */
    private string $trackingDomain = '';

    /**
     * @inheritDoc
     */
    public function getAffilfyId(): string
    {
        return $this->affilfyId;
    }

    /**
     * @inheritDoc
     */
    public function setAffilfyId(string $affilfyId): ConversionMessageInterface
    {
        $this->affilfyId = $affilfyId;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @inheritDoc
     */
    public function setOrderId(string $orderId): ConversionMessageInterface
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCheckoutTotal(): string
    {
        return $this->checkoutTotal;
    }

    /**
     * @inheritDoc
     */
    public function setCheckoutTotal(string $checkoutTotal): ConversionMessageInterface
    {
        $this->checkoutTotal = $checkoutTotal;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @inheritDoc
     */
    public function setCurrency(string $currency): ConversionMessageInterface
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getReferer(): string
    {
        return $this->referer;
    }

    /**
     * @inheritDoc
     */
    public function setReferer(string $referer): ConversionMessageInterface
    {
        $this->referer = $referer;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * @inheritDoc
     */
    public function setTimestamp(string $timestamp): ConversionMessageInterface
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTrackingDomain(): string
    {
        return $this->trackingDomain;
    }

    /**
     * @inheritDoc
     */
    public function setTrackingDomain(string $trackingDomain): ConversionMessageInterface
    {
        $this->trackingDomain = $trackingDomain;
        return $this;
    }
}
