<?php
namespace Markup\Smartship\Controller\Adminhtml\Agent\Update;

/**
 * Interceptor class for @see \Markup\Smartship\Controller\Adminhtml\Agent\Update
 */
class Interceptor extends \Markup\Smartship\Controller\Adminhtml\Agent\Update implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Sales\Api\OrderRepositoryInterface $orderRepository, \Magento\Framework\App\Request\Http $request)
    {
        $this->___init();
        parent::__construct($context, $orderRepository, $request);
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
