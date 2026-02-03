<?php
/**
 * Affilify Tracking Module - Click Message Model
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Model;

use Affilify\Tracking\Api\Data\ClickMessageInterface;

class ClickMessage implements ClickMessageInterface
{
    /**
     * @var string
     */
    private string $affilifyId = '';

    /**
     * @var string
     */
    private string $referer = '';

    /**
     * @var string
     */
    private string $ip = '';

    /**
     * @var string
     */
    private string $userAgent = '';

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
    public function getAffilifyId(): string
    {
        return $this->affilifyId;
    }

    /**
     * @inheritDoc
     */
    public function setAffilifyId(string $affilifyId): ClickMessageInterface
    {
        $this->affilifyId = $affilifyId;
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
    public function setReferer(string $referer): ClickMessageInterface
    {
        $this->referer = $referer;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @inheritDoc
     */
    public function setIp(string $ip): ClickMessageInterface
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @inheritDoc
     */
    public function setUserAgent(string $userAgent): ClickMessageInterface
    {
        $this->userAgent = $userAgent;
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
    public function setTimestamp(string $timestamp): ClickMessageInterface
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
    public function setTrackingDomain(string $trackingDomain): ClickMessageInterface
    {
        $this->trackingDomain = $trackingDomain;
        return $this;
    }
}
