<?php
namespace Rokanthemes\Brand\Controller\Adminhtml\Import\Save;

/**
 * Interceptor class for @see \Rokanthemes\Brand\Controller\Adminhtml\Import\Save
 */
class Interceptor extends \Rokanthemes\Brand\Controller\Adminhtml\Import\Save implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Magento\Framework\Filesystem $filesystem, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\App\ResourceConnection $resource, \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource, \Magento\Catalog\Model\Product\Media\Config $mediaConfig, \Magento\Framework\File\Csv $csvProcessor, \Magento\Catalog\Model\ProductFactory $productFactory)
    {
        $this->___init();
        parent::__construct($context, $resultPageFactory, $filesystem, $storeManager, $scopeConfig, $resource, $configResource, $mediaConfig, $csvProcessor, $productFactory);
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
