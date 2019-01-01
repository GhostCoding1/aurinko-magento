<?php
namespace Piimega\CheckoutFinland\Controller\Payment\Html;

/**
 * Interceptor class for @see \Piimega\CheckoutFinland\Controller\Payment\Html
 */
class Interceptor extends \Piimega\CheckoutFinland\Controller\Payment\Html implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Piimega\CheckoutFinland\Model\Checkoutfinland $checkoutFinland, \Magento\Checkout\Model\Session $checkoutSession, \Magento\Sales\Model\OrderFactory $orderFactory, \Piimega\CheckoutFinland\Logger\Logger $logger, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Magento\Framework\Data\FormFactory $formFactory, \Magento\Framework\App\Action\Context $context)
    {
        $this->___init();
        parent::__construct($checkoutFinland, $checkoutSession, $orderFactory, $logger, $resultJsonFactory, $resultPageFactory, $formFactory, $context);
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
