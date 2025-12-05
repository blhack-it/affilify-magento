<?php
/**
 * Affilify Tracking Module - Constants Test
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Test\Unit\Api;

use Affilify\Tracking\Api\Constants;
use PHPUnit\Framework\TestCase;

class ConstantsTest extends TestCase
{
    /**
     * Test that cookie name constant is defined
     */
    public function testCookieNameConstant(): void
    {
        $this->assertEquals('affilify_tracking', Constants::COOKIE_NAME);
    }

    /**
     * Test that queue topic constants are defined
     */
    public function testQueueTopicConstants(): void
    {
        $this->assertEquals('affilify.tracking.click', Constants::QUEUE_TOPIC_CLICK);
        $this->assertEquals('affilify.tracking.conversion', Constants::QUEUE_TOPIC_CONVERSION);
    }

    /**
     * Test that retry constants are defined
     */
    public function testRetryConstants(): void
    {
        $this->assertEquals(3, Constants::MAX_RETRIES);
        $this->assertEquals(2, Constants::RETRY_DELAY_BASE);
    }

    /**
     * Test affiliate ID pattern validation
     */
    public function testAffiliateIdPatternValid(): void
    {
        // Valid patterns
        $validIds = [
            'abc123',
            'ABC-123',
            'test_affiliate_id',
            'a',
            str_repeat('a', 100), // max length
            '123',
            'a-b_c',
        ];

        foreach ($validIds as $id) {
            $this->assertMatchesRegularExpression(
                Constants::AFFILIATE_ID_PATTERN,
                $id,
                "Expected '$id' to be a valid affiliate ID"
            );
        }
    }

    /**
     * Test affiliate ID pattern rejects invalid input
     */
    public function testAffiliateIdPatternInvalid(): void
    {
        // Invalid patterns
        $invalidIds = [
            '', // empty
            'abc@123', // special char
            'abc 123', // space
            'abc.123', // dot
            str_repeat('a', 101), // too long
            '<script>alert(1)</script>', // XSS attempt
            "'; DROP TABLE users;--", // SQL injection attempt
        ];

        foreach ($invalidIds as $id) {
            $this->assertDoesNotMatchRegularExpression(
                Constants::AFFILIATE_ID_PATTERN,
                $id,
                "Expected '$id' to be rejected as invalid affiliate ID"
            );
        }
    }
}
