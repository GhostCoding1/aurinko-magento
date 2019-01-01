<?php
namespace Magento\Catalog\Controller\Adminhtml\Product\AddAttributeToTemplate;

/**
 * Interceptor class for @see \Magento\Catalog\Controller\Adminhtml\Product\AddAttributeToTemplate
 */
class Interceptor extends \Magento\Catalog\Controller\Adminhtml\Product\AddAttributeToTemplate implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Eav\Api\Data\AttributeGroupInterfaceFactory $attributeGroupFactory = null)
    {
        $this->___init();
        parent::__construct($context, $productBuilder, $resultJsonFactory, $attributeGroupFactory);
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
