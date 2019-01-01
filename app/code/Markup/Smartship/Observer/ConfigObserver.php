<?php
namespace Markup\Smartship\Observer;

use Markup\Smartship\Model\Carrier;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class ConfigObserver implements ObserverInterface
{
    protected $carrier;
    protected $_httpClientFactory;
    protected $_storeManager;

    /**
     *
     */
    public function __construct(
      Carrier $carrier,
      \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
      \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
      $this->carrier = $carrier;
      $this->_httpClientFactory = $httpClientFactory;
      $this->_storeManager = $storeManager;
    }

    /**
     * Send license request to the vendor
     */
    public function execute(EventObserver $observer)
    {
      $domain = $this->_storeManager->getStore()->getBaseUrl();

      $url = 'http://markup.fi/licenses/request';

      $client = $this->_httpClientFactory->create();
      $client->setUri($url);
      $client->setConfig(['maxredirects' => 5, 'timeout' => 30]);
      $client->setMethod(\Zend_Http_Client::POST);
      $client->setHeaders(\Zend_Http_Client::CONTENT_TYPE, 'text/plain');
      $client->setParameterPost('license_key', '');
      $client->setParameterPost('domain', $domain);
      $client->setParameterPost('product', 'Magento 2 SmartShip');

      $client->request();
    }
}
