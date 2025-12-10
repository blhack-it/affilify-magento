<?php
/**
 * Affilify Tracking Module - Constants
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Api;

/**
 * Module constants
 */
class Constants
{
    /**
     * Cookie name for tracking
     */
    public const COOKIE_NAME = 'affilify_tracking';

    /**
     * Queue topic for click tracking
     */
    public const QUEUE_TOPIC_CLICK = 'affilify.tracking.click';

    /**
     * Queue topic for conversion tracking
     */
    public const QUEUE_TOPIC_CONVERSION = 'affilify.tracking.conversion';

    /**
     * Maximum retry attempts for API calls
     */
    public const MAX_RETRIES = 3;

    /**
     * Base delay in seconds for exponential backoff
     */
    public const RETRY_DELAY_BASE = 2;

    /**
     * Affiliate ID validation pattern (alphanumeric, dash, underscore, max 100 chars)
     */
    public const AFFILIATE_ID_PATTERN = '/^[a-zA-Z0-9_-]{1,100}$/';
}
