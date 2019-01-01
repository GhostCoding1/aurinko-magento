<?php
namespace Piimega\CheckoutFinland\Logger\Handler;

use Piimega\CheckoutFinland\Logger\Logger;
use Magento\Framework\Logger\Handler\Base;

class Info extends Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/cf_info.log';
}