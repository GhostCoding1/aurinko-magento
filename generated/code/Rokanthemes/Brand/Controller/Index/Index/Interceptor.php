<?php
namespace Rokanthemes\Brand\Controller\Index\Index;

/**
 * Interceptor class for @see \Rokanthemes\Brand\Controller\Index\Index
 */
class Interceptor extends \Rokanthemes\Brand\Controller\Index\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Store\Model\StoreManager $storeManager, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Rokanthemes\Brand\Helper\Data $brandHelper, \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory)
    {
        $this->___init();
        parent::__construct($context, $storeManager, $resultPageFactory, $brandHelper, $resultForwardFactory);
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