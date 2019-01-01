<?php
namespace Markup\Smartship\Controller\Adminhtml\Agent;

class Update extends \Magento\Backend\App\Action
{
	protected $request;
	protected $orderRepository;
	protected $urlBuilder;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
		\Magento\Framework\App\Request\Http $request
	) {
		$this->request = $request;
		$this->orderRepository = $orderRepository;
		$this->urlBuilder = $context->getUrl();

		return parent::__construct($context);
	}

	public function execute()
	{
		$params = $this->getRequest()->getPostValue();

		if (isset($params['smartship_agent']) && isset($params['order_id'])) {
			$order = $this->orderRepository->get($params['order_id']);

			$shippingAddress = $order->getShippingAddress();
			if ($shippingAddress) {
				$shippingAddress->setSmartshipAgentId(json_encode($params['smartship_agent']));
				$shippingAddress->save();
			}
		}

		$this->messageManager->addSuccess(__('Pickup location updated.'));
		$orderUrl = $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $params['order_id']]);
		$this->_redirect($orderUrl);
	}
}
