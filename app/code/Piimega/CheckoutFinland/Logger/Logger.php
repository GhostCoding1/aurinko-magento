<?php
namespace Piimega\CheckoutFinland\Logger;

use Magento\Framework\Logger\Monolog;
use Monolog\Handler\HandlerInterface;

class Logger extends Monolog{

    /**
     * Reorders handler supplied to parent constructor (custom handlers first)
     * 
     * @param string $name   
     * @param HandlerInterface[] $handlers
     * @param HandlerInterface[] $customHandlers
     * @param callable[] $processors
     */
    public function __construct($name, array $handlers = array(), $customHandlers = array(), $processors = array()){
        $customHandlers = array_merge($customHandlers, $handlers);
        parent::__construct($name, $customHandlers, $processors);
    }
}