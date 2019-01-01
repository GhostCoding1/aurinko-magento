<?php
namespace Piimega\CheckoutFinland\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Exception;
use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
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
    protected $fileName = '/var/log/cf.log';

    /**
     * @var Exception
     */
    protected $exceptionHandler;

    /**
     * @var Handler\Info
     */
    protected $infoHandler;

    /**
     * @param DriverInterface $filesystem
     * @param Exception $exceptionHandler
     * @param Handler\Info $infoHandler
     * @param string $filePath
     */
    public function __construct(
        DriverInterface $filesystem,
        Exception $exceptionHandler,
        Handler\Info $infoHandler,
        $filePath = null
    ) {
        $this->exceptionHandler = $exceptionHandler;
        $this->infoHandler = $infoHandler;
        parent::__construct($filesystem, $filePath);
    }

    /**
     * @{inheritDoc}
     *
     * @param $record array
     * @return void
     */
    public function write(array $record){
        $isException = isset($record['context']['is_exception']) && $record['context']['is_exception'];
        unset($record['context']['is_exception']);

        if($isException){
            $this->exceptionHandler->handle($record);
        }

        if($record['level'] == Logger::INFO){
            $this->infoHandler->handle($record);
        } else {
            $record['formatted'] = $this->getFormatter()->format($record);
            parent::write($record);
        }
    }
}