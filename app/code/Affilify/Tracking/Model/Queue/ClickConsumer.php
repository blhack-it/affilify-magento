<?php
/**
 * Affilify Tracking Module - Click Consumer
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Model\Queue;

use Affilify\Tracking\Api\Data\ClickMessageInterface;
use Affilify\Tracking\Logger\Logger;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

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
     * @var Logger
     */
    private Logger $logger;

    /**
     * ClickConsumer constructor.
     *
     * @param Curl $curl
     * @param Json $json
     * @param Logger $logger
     */
    public function __construct(
        Curl $curl,
        Json $json,
        Logger $logger
    ) {
        $this->curl = $curl;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * Process click tracking message
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

        $apiUrl = 'https://' . rtrim($trackingDomain, '/') . '/m/click';

        $payload = [
            'affilify_id' => $message->getAffilfyId(),
            'referer' => $message->getReferer(),
            'ip' => $message->getIp(),
            'user_agent' => $message->getUserAgent(),
            'timestamp' => $message->getTimestamp(),
            'platform' => 'magento'
        ];

        try {
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Accept', 'application/json');
            $this->curl->setTimeout(10);
            $this->curl->post($apiUrl, $this->json->serialize($payload));

            $statusCode = $this->curl->getStatus();

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('Click tracked successfully', [
                    'affilify_id' => $message->getAffilfyId(),
                    'tracking_domain' => $trackingDomain
                ]);
            } else {
                $this->logger->error('Click tracking API returned error', [
                    'status_code' => $statusCode,
                    'affilify_id' => $message->getAffilfyId(),
                    'tracking_domain' => $trackingDomain,
                    'response' => $this->curl->getBody()
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Click tracking failed', [
                'error' => $e->getMessage(),
                'affilify_id' => $message->getAffilfyId(),
                'tracking_domain' => $trackingDomain
            ]);
            // Silent fail - do not re-throw to prevent blocking
        }
    }
}
