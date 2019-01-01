<?php
namespace Piimega\CheckoutFinland\Controller\Result\Success;

/**
 * Interceptor class for @see \Piimega\CheckoutFinland\Controller\Result\Success
 */
class Interceptor extends \Piimega\CheckoutFinland\Controller\Result\Success implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Piimega\CheckoutFinland\Model\Checkoutfinland $checkoutFinland, \Magento\Checkout\Model\Session $checkoutSession, \Magento\Sales\Model\OrderFactory $orderFactory, \Piimega\CheckoutFinland\Logger\Logger $logger, \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender, \Magento\Framework\App\Action\Context $context)
    {
        $this->___init();
        parent::__construct($checkoutFinland, $checkoutSession, $orderFactory, $logger, $orderSender, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'dispatch');
        if (!$pluginInfo) {
            return parent::dispatch($request);
        } else {
            return $this->___callPlugins('dispatch', func_get_args(), $pluginInfo);
        }
    }
}
