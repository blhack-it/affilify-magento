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
        $apiKey = $this->config->getApiKey();

        if (empty($apiKey)) {
            $this->logger->warning('Click tracking skipped: No API key configured');
            return;
        }

        $apiUrl = $this->config->getClickApiUrl();

        $payload = [
            'affilify_id' => $message->getAffilfyId(),
            'referer' => $message->getReferer(),
            'ip' => $message->getIp(),
            'user_agent' => $message->getUserAgent(),
            'timestamp' => $message->getTimestamp(),
            'platform' => 'magento'
        ];

        $this->executeWithRetry($apiUrl, $payload, $message->getAffilfyId(), $apiKey);
    }

    /**
     * Execute API call with retry logic
     *
     * @param string $apiUrl
     * @param array $payload
     * @param string $affilifyId
     * @param string $apiKey
     * @return void
     */
    private function executeWithRetry(string $apiUrl, array $payload, string $affilifyId, string $apiKey): void
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
                $this->curl->addHeader('X-Affilify-Api-Key', $apiKey);
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
            'api_url' => $apiUrl
        ]);
    }
}
