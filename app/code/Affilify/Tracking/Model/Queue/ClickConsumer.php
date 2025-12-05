<?php
/**
 * Affilify Tracking Module - Click Consumer
 *
 * Processes click tracking messages from the queue with retry logic.
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Model\Queue;

use Affilify\Tracking\Api\Constants;
use Affilify\Tracking\Api\Data\ClickMessageInterface;
use Affilify\Tracking\Helper\Config;
use Affilify\Tracking\Logger\Logger;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Consumer for processing click tracking messages
 */
class ClickConsumer
{
    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * ClickConsumer constructor.
     *
     * @param Curl $curl
     * @param Json $json
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        Curl $curl,
        Json $json,
        Config $config,
        Logger $logger
    ) {
        $this->curl = $curl;
        $this->json = $json;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Process click tracking message with retry logic
     *
     * @param ClickMessageInterface $message
     * @return void
     */
    public function process(ClickMessageInterface $message): void
    {
        $trackingDomain = $message->getTrackingDomain();

        if (empty($trackingDomain)) {
            $this->logger->warning('Click tracking skipped: No tracking domain configured');
            return;
        }

        $apiUrl = $this->buildApiUrl($trackingDomain, '/m/click');

        $payload = [
            'affilify_id' => $message->getAffilfyId(),
            'referer' => $message->getReferer(),
            'ip' => $message->getIp(),
            'user_agent' => $message->getUserAgent(),
            'timestamp' => $message->getTimestamp(),
            'platform' => 'magento'
        ];

        $this->executeWithRetry($apiUrl, $payload, $message->getAffilfyId(), $trackingDomain);
    }

    /**
     * Execute API call with retry logic
     *
     * @param string $apiUrl
     * @param array $payload
     * @param string $affilifyId
     * @param string $trackingDomain
     * @return void
     */
    private function executeWithRetry(string $apiUrl, array $payload, string $affilifyId, string $trackingDomain): void
    {
        $attempt = 0;
        $lastException = null;
        $lastStatusCode = null;

        while ($attempt < Constants::MAX_RETRIES) {
            $attempt++;

            try {
                // Reset curl for each attempt
                $this->curl->setOptions([
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]);
                $this->curl->addHeader('Content-Type', 'application/json');
                $this->curl->addHeader('Accept', 'application/json');
                $this->curl->setTimeout(10);
                $this->curl->post($apiUrl, $this->json->serialize($payload));

                $statusCode = $this->curl->getStatus();

                if ($statusCode >= 200 && $statusCode < 300) {
                    $this->logger->info('Click tracked successfully', [
                        'attempt' => $attempt
                    ]);
                    return;
                }

                // Non-retryable client errors (4xx except 429)
                if ($statusCode >= 400 && $statusCode < 500 && $statusCode !== 429) {
                    $this->logger->error('Click tracking failed with client error', [
                        'status_code' => $statusCode,
                        'response' => substr($this->curl->getBody(), 0, 200)
                    ]);
                    return;
                }

                $lastStatusCode = $statusCode;

            } catch (\Exception $e) {
                $lastException = $e;
                $this->logger->debug('Click tracking attempt failed', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
            }

            // Wait before retry with exponential backoff
            if ($attempt < Constants::MAX_RETRIES) {
                $delay = (int) pow(Constants::RETRY_DELAY_BASE, $attempt);
                sleep($delay);
            }
        }

        // All retries exhausted
        $this->logger->error('Click tracking failed after all retries', [
            'attempts' => Constants::MAX_RETRIES,
            'last_status_code' => $lastStatusCode,
            'last_error' => $lastException ? $lastException->getMessage() : null,
            'tracking_domain' => $trackingDomain
        ]);
    }

    /**
     * Build API URL with HTTPS scheme
     *
     * @param string $trackingDomain
     * @param string $path
     * @return string
     */
    private function buildApiUrl(string $trackingDomain, string $path): string
    {
        // Check if domain already has scheme
        if (preg_match('/^https?:\/\//', $trackingDomain)) {
            return rtrim($trackingDomain, '/') . $path;
        }

        // Always use HTTPS
        return 'https://' . rtrim($trackingDomain, '/') . $path;
    }
}
