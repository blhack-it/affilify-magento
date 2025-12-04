<?php
/**
 * Affilify Tracking Module - Logger Handler
 *
 * @copyright Copyright (c) 2024 Affilify
 * @license   MIT License
 */

declare(strict_types=1);

namespace Affilify\Tracking\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger as MonologLogger;

class Handler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = MonologLogger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/affilify_tracking.log';
}
