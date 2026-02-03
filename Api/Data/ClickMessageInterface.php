<?php
/**
 * Affilify Tracking Module - Click Message Interface
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Api\Data;

interface ClickMessageInterface
{
    /**
     * Get affilify ID
     *
     * @return string
     */
    public function getAffilifyId(): string;

    /**
     * Set affilify ID
     *
     * @param string $affilifyId
     * @return self
     */
    public function setAffilifyId(string $affilifyId): self;

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
     * Get visitor IP address
     *
     * @return string
     */
    public function getIp(): string;

    /**
     * Set visitor IP address
     *
     * @param string $ip
     * @return self
     */
    public function setIp(string $ip): self;

    /**
     * Get user agent
     *
     * @return string
     */
    public function getUserAgent(): string;

    /**
     * Set user agent
     *
     * @param string $userAgent
     * @return self
     */
    public function setUserAgent(string $userAgent): self;

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
