<?php
namespace Markup\Smartship\Model;

use Markup\Smartship\Api\AgentsInterface;

class Agents implements AgentsInterface
{
    protected $_httpClientFactory;
    protected $_request;
    protected $_carrier;
    protected $_scopeConfig;

    /**
     * Constructor
     */
    public function __construct(
      \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
      \Magento\Framework\App\Request\Http $request,
      \Markup\Smartship\Model\Carrier $carrier,
      \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
      $this->_httpClientFactory = $httpClientFactory;
      $this->_request = $request;
      $this->_carrier = $carrier;
      $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Returns Posti agents by postcode
     *
     * @api
     * @return string agents in JSON
     */
    public function agents() {
      $params = $this->_request->getParams();
      $postcode = isset($params['postcode']) ? $params['postcode'] : '';
      $methodCode = isset($params['method']) ? $params['method'] : 'PO2103';
      $adminUpdate = (isset($params['adminUpdate']) && $params['adminUpdate']) ? TRUE : FALSE;

      $agents = $this->agentsByPostcode($postcode, $methodCode, $adminUpdate);

      return json_encode($agents);
    }

    /**
     * Get agents by postcode
     */
    private function agentsByPostcode($postcode, $methodCode, $adminUpdate = FALSE) {
      $params = [];

      // Set agent type
      if ($this->_carrier->getPostipakettiMode() == 'combine' || $adminUpdate) {
        $params['types'] = ['SMARTPOST', 'POSTOFFICE', 'PICKUPPOINT'];
      } else {
        if ($methodCode == 'PO2103S') {
          $params['types'] = ['SMARTPOST'];
        } else {
          $params['types'] = ['POSTOFFICE', 'PICKUPPOINT'];
        }
      }

      // Set max results
      $params['top'] = 20;

      // Set search zip code
      $params['locationZipCode'] = $postcode;

      $client = $this->_httpClientFactory->create();
      $client->setUri('https://locationservice.posti.com/location');
      $client->setParameterGet($params);
      $client->setConfig(['maxredirects' => 5, 'timeout' => 10]);
      $client->setMethod(\Zend_Http_Client::GET);
      $client->setHeaders(\Zend_Http_Client::CONTENT_TYPE, 'application/json');

      $response = $client->request();

      $agents = [];

      if ($response != FALSE) {
        $data = json_decode($response->getBody());

        if (isset($data->locations)) {
          foreach ($data->locations as $location) {
            $agents[] = [
              'id' => $location->pupCode,
              'name' => $location->publicName->fi,
              'rsc' => $location->routingServiceCode,
              'address' => sprintf("%s %s", $location->address->fi->streetName, $location->address->fi->streetNumber),
              'city' => $location->address->fi->municipality,
              'postcode' => $location->address->fi->postalCode,
            ];
          }
        }
      }

      return $agents;
    }
}
