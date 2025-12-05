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
}
