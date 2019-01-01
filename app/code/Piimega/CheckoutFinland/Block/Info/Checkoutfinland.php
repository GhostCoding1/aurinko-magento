<?php
namespace Piimega\CheckoutFinland\Block\Info;

use \Magento\Payment\Block\Info;

/**
 * Block for Checkout Finland payment method info
 */
class Checkoutfinland extends Info
{
    /**
     * @var string
     */
    protected $_template = 'Piimega_CheckoutFinland::info/checkoutfinland.phtml';

    /*
     * @var \Piimega\CheckoutFinland\Logger\Logger
     */
    protected $_logger;

    /**
     * Constructor
     *
     * @param \Piimega\CheckoutFinland\Logger\Logger $context
     * @param \Magento\Framework\View\Context $context
     * @param array $data
     */
    public function __construct(
        \Piimega\CheckoutFinland\Logger\Logger $logger,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ){
        parent::__construct($context, $data);
        $this->_logger = $logger;
    }

    /**
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Piimega_CheckoutFinland::info/pdf/checkoutfinland.phtml');
        return $this->toHtml();
    }

    /**
     * @return string
     */
    public function getPaymentOperator(){
        try{
            if($operator = $this->getMethod()->getPreselectedMethodLabel()){
                return $operator;
            }
        } catch (\Exception $e){
            $this->_logger->critical($e);
        }
        return '';
    }

    /**
     * @return string
     */
    public function getCfTransactionId(){
        try{
            if($transactionId = $this->getInfo()->getCfTransactionId()){
                return $transactionId;
            }
        } catch (\Exception $e){
            $this->_logger->critical($e);
        }
        return '';
    }

    /**
     * @return array
     */
    public function getCfStatusCode(){
        try{
            $payment = $this->getInfo();
            if($statusCode = $payment->getCfStatusCode()){
                return [
                    'code' => $statusCode,
                    'description' => $this->getMethod()->getStatusCodeDescription($statusCode)
                ];
            }
        } catch (\Exception $e){
            $this->_logger->critical($e);
        }
        return [];
    }
}
