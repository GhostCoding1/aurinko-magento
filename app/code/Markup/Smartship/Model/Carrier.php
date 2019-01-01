<?php
namespace Markup\Smartship\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Config;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Psr\Log\LoggerInterface;
use Magento\Framework\Xml\Security;
use Magento\Shipping\Helper\Carrier as CarrierHelper;
use Magento\Shipping\Model\Shipping\LabelGenerator;

/**
 * Class Carrier Smartship carrier model
 */
class Carrier extends AbstractCarrierOnline implements CarrierInterface
{
    protected $_code = 'smartship';

    protected $_isFixed = true;
    protected $_logger;
    protected $_carrierHelper;
    protected $_httpClientFactory;
    protected $_labelGenerator;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
      \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
      \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
      \Psr\Log\LoggerInterface $logger,
      Security $xmlSecurity,
      \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
      \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
      \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
      \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
      \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
      \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
      \Magento\Directory\Model\RegionFactory $regionFactory,
      \Magento\Directory\Model\CountryFactory $countryFactory,
      \Magento\Directory\Model\CurrencyFactory $currencyFactory,
      \Magento\Directory\Helper\Data $directoryData,
      \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
      CarrierHelper $carrierHelper,
      \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
      LabelGenerator $labelGenerator,
      array $data = []
    ) {
      $this->_carrierHelper = $carrierHelper;
      $this->_httpClientFactory = $httpClientFactory;
      $this->_labelGenerator = $labelGenerator;
      $this->_logger = $logger;
      parent::__construct(
        $scopeConfig,
        $rateErrorFactory,
        $logger,
        $xmlSecurity,
        $xmlElFactory,
        $rateFactory,
        $rateMethodFactory,
        $trackFactory,
        $trackErrorFactory,
        $trackStatusFactory,
        $regionFactory,
        $countryFactory,
        $currencyFactory,
        $directoryData,
        $stockRegistry,
        $data
      );
    }

    /**
     * Generates list of carrier's all shipping methods
     *
     * @return array
     */
    public function getMethods() {
      return array(
        'PO2103' => $this->getConfigData('title_PO2103'),
        'PO2103S' => $this->getConfigData('title_PO2103S'),
        'PO2104' => $this->getConfigData('title_PO2104'),
        'PO2102' => $this->getConfigData('title_PO2102'),
        'PO2461' => $this->getConfigData('title_PO2461'),
        'PO2711' => $this->getConfigData('title_PO2711'),
        'ITPR' => $this->getConfigData('title_ITPR'),
        'PO2017' => $this->getConfigData('title_PO2017'),
      );
    }

    /**
     * Generates list of allowed carrier`s shipping methods
     * Displays on cart price rules page
     *
     * @return array
     * @api
     */
    public function getAllowedMethods()
    {
      $methods = $this->getMethods();
      $allowed_methods = array();

      foreach ($methods as $key => $method_title) {
        if ($this->getConfigData("active_{$key}")) {
          $allowed_methods[$key] = $method_title;
        }
      }

      return $allowed_methods;
    }

    /**
     * Get mode for Postipaketti
     */
    public function getPostipakettiMode() {
      return $this->getConfigData('mode_PO2103');
    }

    /**
     * Collect and get rates for storefront
     *
     * @param RateRequest $request
     * @return DataObject|bool|null
     * @api
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->isActive()) {
            return false;
        }

        $this->setRawRequest($request);

        $result = $this->_rateFactory->create();

        $allowed_methods = $this->getAllowedMethods();
        foreach ($allowed_methods as $method_key => $method_title) {
          $price = $this->findRate($request, $method_key);

          // Package weight exceeds the highest possible
          if ($price === FALSE) {
            continue;
          }

          $destCountry = strtoupper($request->getDestCountryId());

          // Check country for Finnish methods
          if (in_array($method_key, array('PO2103', 'PO2103S', 'PO2104', 'PO2102', 'PO2461'))) {
            if ($destCountry != 'FI') {
              continue;
            }
          }

          // Check Parcel Connect, EMS and Priority Parcel countries
          if (in_array($method_key, array('PO2711', 'ITPR', 'PO2017'))) {
            if ( ! in_array($destCountry, $this->allowedCountriesForRate($request, $method_key))) {
              continue;
            }
          }

          $method = $this->_rateMethodFactory->create();
          $method->setCarrier($this->_code);
          $method->setCarrierTitle('Posti');

          $method->setMethod($method_key);
          $method->setMethodTitle($method_title);
          $method->setCost($this->getMethodPrice($price, $method_key));
          $method->setPrice($this->getMethodPrice($price, $method_key));
          $result->append($method);
        }

        return $result;
    }

    /**
     * Calculate price considering free shipping
     *
     * @param string $cost
     * @param string $method
     * @return float|string
     * @api
     */
    public function getMethodPrice($cost, $method = '') {
      $free_shipping_subtotal = $this->getConfigData('free_shipping_subtotal_' . $method);
      $subtotal = $this->_rawRequest->getBaseSubtotalInclTax();

      if ($free_shipping_subtotal > 0 && $free_shipping_subtotal <= $subtotal) {
        return '0.00';
      }

      return $cost;
    }

    public function findRate(RateRequest $request, $methodKey) {
      $prices = $this->_getPriceData($methodKey);

      $packageWeight = $request->getPackageWeight();
      $destCountry = strtoupper($request->getDestCountryId());

      foreach ($prices as $price) {
        // Check country (international shipments)
        if (isset($price['country']) && ! empty($price['country'])) {
          $code = $price['country'];

          if ($this->_isPaymentZoneCode($code)) {
            if ($this->_findPaymentZone($destCountry, $methodKey) != $code) {
              continue;
            }
          } else {
            if ($destCountry != $price['country']) {
              continue;
            }
          }
        }

        if ($packageWeight <= $price['max_weight']) {
          return $price['price'];
        }
      }

      return FALSE;
    }

    /**
     * Get price data for a shipping method
     */
    protected function _getPriceData($methodKey)
    {
      // Magento 2.1 used PHP serializer while 2.2 uses JSON
      // We need to check which one to use
      $prices = $this->getConfigData("price_{$methodKey}");

      if ($this->_validJson($prices)) {
        return json_decode($prices, TRUE);
      }

      return unserialize($prices);
    }

    /**
     * Validates JSON
     */
    private function _validJson($string)
    {
      json_decode($string);
      return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Get a list of allowed countries for a rate.
     *
     * Currently only used for Parcel Connect
     */
    public function allowedCountriesForRate(RateRequest $request, $methodKey) {
      $prices = $this->_getPriceData($methodKey);

      $countries = array();
      foreach ($prices as $price) {
        $code = trim(strval($price['country']));

        // Code may be either payment zone or country code
        // Country codes are two letter while payment zones
        // are one digit from 1 to 4 or UP1-8 for Parcel Connect
        if ($this->_isPaymentZoneCode($code)) {
          // Parcel Connect
          if ($methodKey == 'PO2711') {
            $zones = $this->_paymentZonesParcelConnect();
          } else {
            $zones = $this->_paymentZones();
          }

          foreach ($zones as $countryCode => $zone) {
            if (strval($zone) === strval($code)) {
              $countries[$countryCode] = TRUE;
            }
          }
        } elseif (strlen($code) == 2) {
          // Country code
          $countryCode = strtoupper($price['country']);
          $countries[$countryCode] = TRUE;
        }
      }

      return array_keys($countries);
    }

    public function proccessAdditionalValidation(\Magento\Framework\DataObject $request)
    {
      return TRUE;
    }

    /**
     * Do request to shipment
     *
     * @param \Magento\Shipping\Model\Shipment\Request $request
     * @return array|\Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function requestToShipment($request) {
        $packages = $request->getPackages();
        if (!is_array($packages) || !$packages) {
          throw new \Magento\Framework\Exception\LocalizedException(__('No packages for request'));
        }

        $result = $this->_doShipmentRequest($request);

        $data = [];

        if ( ! $result->hasErrors()) {
          $data[] = [
            'tracking_number' => $result->getTrackingNumber(),
            'label_content' => $result->getShippingLabelContent(),
          ];

          $request->setMasterTrackingId($result->getTrackingNumber());
        }

        $response = new \Magento\Framework\DataObject(['info' => $data]);
        if ($result->hasErrors()) {
          $response->setErrors($result->getErrors());
        }

        return $response;
    }

    /**
     * Do shipment request to carrier web service, obtain Print Shipping Labels and process errors in response
     *
     * @param \Magento\Framework\DataObject $request
     * @return \Magento\Framework\DataObject
     */
    protected function _doShipmentRequest(\Magento\Framework\DataObject $request) {
      $this->_prepareShipmentRequest($request);

      // Calculate total package weight
      $packageWeight = 0;
      foreach ($request->getPackages() as $piece) {
        $packageWeight += $this->_convertWeight($piece['params']['weight'], $piece['params']['weight_units']);
      }
      $request->setPackageWeight($packageWeight);

      $result = new \Magento\Framework\DataObject();

      $requestJson = $this->_formShipmentRequest($request);

      $client = $this->_httpClientFactory->create();
      $client->setUri('https://api.unifaun.com/rs-extapi/v1/shipments?inlinePdf=true');
      $client->setConfig(['maxredirects' => 5, 'timeout' => 30]);
      $client->setMethod(\Zend_Http_Client::POST);
      $client->setHeaders(\Zend_Http_Client::CONTENT_TYPE, 'application/json');
      $client->setHeaders('Authorization', 'Bearer ' . $this->getConfigData('api_key'));
      $client->setRawData($requestJson, 'application/json');

      $response = $client->request();

      if ($response != false) {
        $body = json_decode($response->getBody());

        $statusCode = $response->getStatus();

        switch ($statusCode) {
          case '201':
            // Handle normal shipment
            $normalShipment = reset($body);
            if ($normalShipment->status == 'PRINTED') {
              $parcel = reset($normalShipment->parcels);
              $trackingNumber = $parcel->parcelNo;

              $result->setTrackingNumber($trackingNumber);
            } else {
              foreach ($body->statuses as $key => $status) {
                $result->setErrors("{$status->field}: {$status->message}");
              }
            }

            // Combine all shipping labels (normal + return labels)
            $labelsContent = [];
            foreach ($body as $shipment) {
      				if ($shipment->status == 'PRINTED') {
                foreach ($shipment->pdfs as $pdf) {
                  $labelsContent[] = base64_decode($pdf->pdf);
                }
      				}
            }

            if ( ! empty($labelsContent)) {
              $combinedLabels = $this->_labelGenerator->combineLabelsPdf($labelsContent);

              $result->setShippingLabelContent($combinedLabels->render());
            }

            break;
          case '400':
            $result->setErrors("400 - Invalid shipment data");
            break;
          case '401':
            $result->setErrors("401 - Invalid or expired API key.");
            break;
          case '403':
            $result->setErrors("403 - The API key is valid but it doesn't grant access to the operation attempted.");
            break;
          case '422':
            $result->setErrors("422 - Invalid shipment data");

            // Handle error messages from Unifaun
    				if (is_array($body)) {
              foreach ( $body as $key => $message ) {
                if ( is_object( $message ) && isset( $message->type ) && 'error' == $message->type ) {
                  $result->setErrors("{$message->field}: {$message->message}");
                }
              }
    				}

            break;
          case '500':
            $result->setErrors("500 - Server error at Unifaun");
            break;
          default:
            $result->setErrors("Unknown error");
        }
      } else {
        $result->setErrors("No connection to Unifaun or server error");
      }

      $result->setGatewayResponse($response);

      return $result;
    }

    protected function _formShipmentRequest(\Magento\Framework\DataObject $request) {
      if ($request->getReferenceData()) {
        $referenceData = $request->getReferenceData();
      } else {
        $referenceData = '#' . $request->getOrderShipment()->getOrder()->getIncrementId();
      }

      $receiverName1 = $request->getRecipientContactCompanyName() ? $request->getRecipientContactCompanyName() : $request->getRecipientContactPersonName();
      $receiverContactName = $request->getRecipientContactCompanyName() ? $request->getRecipientContactPersonName() : '';

      $body = array(
  			"pdfConfig" => array(
  				"target1Media" => "laser-a5",
  				"target1XOffset" => 0,
  				"target1YOffset" => 0,
  			),
  			"shipment" => array(
  				"sender" => array(
  					"name" => $request->getShipperContactCompanyName(),
  					"address1" => $request->getShipperAddressStreet(),
  					"zipcode" => $request->getShipperAddressPostalCode(),
  					"city" => $request->getShipperAddressCity(),
  					"country" => "FI",
  					"phone" => '',
  					"email" => ''
  				),
  				"senderPartners" => array(array(
  					"id" => "POSTI",
  					"custNo" => $this->getConfigData('customer_number')
  				)),
  				"receiver" => array(
  					"name" => $receiverName1,
  					"contact" => $receiverContactName,
  					"address1" => $request->getRecipientAddressStreet(),
  					"address2" => $request->getRecipientAddressStreet2(),
  					"zipcode" => $request->getRecipientAddressPostalCode(),
  					"city" => $request->getRecipientAddressCity(),
  					"country" => $request->getRecipientAddressCountryCode(),
  					"phone" => $request->getRecipientContactPhoneNumber(),
  					"mobile" => $request->getRecipientContactPhoneNumber(),
  					"email" => $request->getOrderShipment()->getOrder()->getCustomerEmail()
  				),
  				"orderNo" => $referenceData,
  				"test" => ! $this->getConfigData('mode'),
  				"senderReference" => $referenceData,
  				"service" => array(
  					"id" => str_replace('PO2103S', 'PO2103', $request->getShippingMethod()),
            "normalShipment" => true,
            "returnShipment" => $this->_shouldPrintReturnLabels($request->getShippingMethod()),
  					"addons" => array(),
  				),
  				"parcels" => array(array(
  					"copies" => count($request->getPackages()),
  					"valuePerParcel" => true,
            "contents" => $this->getConfigData('contents')
  				)),
          "options" => array(),
  			),
  		);

      // Set cash on delivery
  		if ($this->_shouldApplyCod($request)) {
        $payment = $request->getOrderShipment()->getOrder()->getPayment();

  			$body['shipment']['service']['addons'][] = array(
  				'id' => 'COD',
  				'bank' => $payment->getMethodInstance()->getCodBic(),
  				'account' => $payment->getMethodInstance()->getCodIban(),
  				'accounttype' => 'IBAN',
  				'amount' => $request->getOrderShipment()->getOrder()->getGrandTotal(),
  				'unit' => 'EUR',
  				'reference' => $payment->getMethodInstance()->calculateReference($request->getOrderShipment()->getOrder()),
  			);
  		}

      // Set pre-notification
      if ($this->getConfigData('send_enot')) {
        $body['shipment']['options'][] = array(
          'id' => 'ENOT',
          'from' => $this->_getStoreEmail(),
          'to' => $request->getOrderShipment()->getOrder()->getCustomerEmail(),
          'message' => '',
          'languageCode' => $this->_getEnotLanguage($request),
        );
      }

  		if ( strpos($request->getShippingMethod(), 'PO2103') !== FALSE ) {
        // Set electronic notification
  			$body['shipment']['service']['addons'][] = array(
  				'id' => 'NOT',
  				'text3' => $request->getRecipientContactPhoneNumber(),
  				'text4' => $request->getOrderShipment()->getOrder()->getCustomerEmail(),
  			);

        // Set agent
        if ($agent = $this->_findAgentId($request)) {
          $agentData = json_decode($agent);

          $body['shipment']['agent'] = array(
            'quickId' => $agentData->id,
          );
        }
  		}

      // Set weight for EMS and Priority Parcel
      if (in_array($request->getShippingMethod(), array('PO2017', 'ITPR'))) {
        $body['shipment']['parcels'][0]['weight'] = $request->getPackageWeight();
      }

      // Set customs information for EMS and Priority Parcel
      if (in_array($request->getShippingMethod(), array('PO2017', 'ITPR'))) {
        $order = $request->getOrderShipment()->getOrder();

        $body['shipment']['customsDeclaration']['invoiceNo'] = $order->getIncrementId();
        $body['shipment']['customsDeclaration']['printSet'] = ['OTHER']; // Needs to be array
        $body['shipment']['customsDeclaration']['importExportType'] = 'OTHER';
        $body['shipment']['customsDeclaration']['currencyCode'] = $order->getOrderCurrencyCode();
        $body['shipment']['customsDeclaration']['lines'] = [
          [
            'valuesPerItem' => false,
            'value' => $order->getGrandTotal(),
            'copies' => 1,
          ]
        ];
      }

      return json_encode($body);
    }

    /**
     * Convert weight to kilograms
     */
    protected function _convertWeight($weight, $weightUnits) {
      if ($weightUnits != \Zend_Measure_Weight::STANDARD) {
        $weight = $this->_carrierHelper->convertMeasureWeight(
          $weight,
          $weightUnits,
          \Zend_Measure_Weight::STANDARD
        );
      }

      return $weight;
    }

    protected function _findAgentId($request) {
      $order = $request->getOrderShipment()->getOrder();
      $shippingAddress = $order->getShippingAddress();
      return $shippingAddress->getSmartshipAgentId();
    }

    /**
     * Get pre-notification language
     */
    private function _getEnotLanguage($request)
    {
      $localeCode = $request->getOrderShipment()->getOrder()->getStore()->getConfig('general/locale/code');

      switch ($localeCode) {
        case 'fi_FI':
          return 'FI';
      }

      return 'GB';
    }

    /**
     * Get store email
     */
    private function _getStoreEmail()
    {
      return $this->_scopeConfig->getValue(
        'trans_email/ident_sales/email',
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
      );
    }

    /**
     * Whether cash on delivery should be applied to request
     */
    protected function _shouldApplyCod($request) {
      $order = $request->getOrderShipment()->getOrder();
      $payment = $order->getPayment();
      $invoice = $order->getInvoiceCollection()->getFirstItem();

      return ($payment->getMethod() == 'postiennakko' && empty($invoice->getID()));
    }

    /**
     * Checks if given code is payment zone
     */
    private function _isPaymentZoneCode($code) {
      // International shipment payment zone
      if (strlen($code) == 1 && ctype_digit($code) && intval($code) >= 1 && intval($code) <= 4) {
        return TRUE;
      }

      // Parcel Connect payment zone
      if (strlen($code) == 3 && substr($code, 0, 2) == 'UP' && intval(substr($code, 2, 1)) >= 1 && intval(substr($code, 2, 1)) <= 8) {
        return TRUE;
      }

      return FALSE;
    }

    /**
     * Find Posti payment zone
     */
    private function _findPaymentZone($countryCode, $methodKey) {
      if ($methodKey == 'PO2711') {
        $zones = $this->_paymentZonesParcelConnect();
      } else {
        $zones = $this->_paymentZones();
      }

      return isset($zones[$countryCode]) ? $zones[$countryCode] : FALSE;
    }

    /**
     * Checks if return labels should be printed with the shipping method
     */
    private function _shouldPrintReturnLabels($methodKey)
    {
      if ($this->getConfigData('return_labels')) {
        return in_array($methodKey, explode(',', $this->getConfigData('return_label_methods')));
      }

      return FALSE;
    }

    /**
     * Posti payment zones for international shipments
     */
    private function _paymentZones() {
      return array(
        'AC' => '4', 'AD' => '3', 'AE' => '3', 'AF' => '4', 'AG' => '4', 'AI' => '4',
        'AL' => '3', 'AM' => '4', 'AN' => '4', 'AO' => '4', 'AR' => '4', 'AT' => '2',
        'AU' => '4', 'AW' => '4', 'AZ' => '4', 'BA' => '3', 'BB' => '4',
        'BD' => '4', 'BE' => '1', 'BF' => '4', 'BG' => '2', 'BH' => '4', 'BI' => '4',
        'BJ' => '4', 'BM' => '4', 'BN' => '4', 'BO' => '4', 'BQ' => '4', 'BR' => '4',
        'BS' => '4', 'BT' => '4', 'BW' => '4', 'BY' => '3', 'BZ' => '4', 'CA' => '3',
        'CD' => '4', 'CF' => '4', 'CG' => '4', 'CH' => '3', 'CI' => '4', 'CK' => '4',
        'CL' => '4', 'CM' => '4', 'CN' => '4', 'CO' => '4', 'CR' => '4', 'CU' => '4',
        'CV' => '4', 'CW' => '4', 'CY' => '2', 'CZ' => '2', 'DE' => '1', 'DJ' => '4',
        'DK' => '1', 'DM' => '4', 'DO' => '4', 'DZ' => '3', 'EC' => '4', 'EE' => '1',
        'EG' => '3', 'ER' => '4', 'ES' => '2', 'ES' => '3', 'ET' => '4', 'FJ' => '4',
        'FK' => '4', 'FM' => '4', 'FO' => '3', 'FR' => '2', 'GA' => '4', 'GB' => '2',
        'GD' => '4', 'GE' => '4', 'GF' => '4', 'GG' => '3', 'GH' => '4', 'GI' => '3',
        'GL' => '3', 'GM' => '4', 'GN' => '4', 'GP' => '4', 'GQ' => '4', 'GR' => '2',
        'GT' => '4', 'GU' => '4', 'GW' => '4', 'GY' => '4', 'HK' => '4', 'HN' => '4',
        'HR' => '2', 'HT' => '4', 'HU' => '2', 'IC' => '3', 'ID' => '4', 'IE' => '2',
        'IL' => '3', 'IM' => '3', 'IN' => '4', 'IQ' => '3', 'IR' => '3', 'IS' => '3',
        'IT' => '2', 'JE' => '3', 'JM' => '4', 'JO' => '3', 'JP' => '4', 'KE' => '4',
        'KG' => '4', 'KH' => '4', 'KI' => '4', 'KM' => '4', 'KN' => '4', 'KP' => '4',
        'KR' => '4', 'KW' => '3', 'KY' => '4', 'KZ' => '4', 'LA' => '4', 'LB' => '3',
        'LC' => '4', 'LI' => '3', 'LK' => '4', 'LR' => '4', 'LS' => '4', 'LT' => '1',
        'LU' => '1', 'LV' => '1', 'LY' => '3', 'MA' => '3', 'MC' => '2', 'MD' => '3',
        'ME' => '3', 'MG' => '4', 'MH' => '4', 'MK' => '3', 'ML' => '4', 'MM' => '4',
        'MN' => '4', 'MO' => '4', 'MQ' => '4', 'MR' => '4', 'MS' => '4', 'MT' => '2',
        'MU' => '4', 'MV' => '4', 'MW' => '4', 'MX' => '4', 'MY' => '4', 'MZ' => '4',
        'NA' => '4', 'NC' => '4', 'NE' => '4', 'NG' => '4', 'NI' => '4', 'NL' => '1',
        'NO' => '3', 'NP' => '4', 'NR' => '4', 'NU' => '4', 'NZ' => '4', 'OM' => '4',
        'PA' => '4', 'PE' => '4', 'PF' => '4', 'PG' => '4', 'PH' => '4', 'PK' => '4',
        'PL' => '2', 'PM' => '4', 'PN' => '4', 'PR' => '4', 'PT' => '2', 'PW' => '4',
        'PY' => '4', 'QA' => '4', 'RE' => '4', 'RO' => '2', 'RS' => '3', 'RU' => '3',
        'RW' => '4', 'SA' => '3', 'SB' => '4', 'SC' => '4', 'SD' => '4', 'SE' => '1',
        'SG' => '4', 'SH' => '4', 'SH' => '4', 'SI' => '2', 'SK' => '2', 'SL' => '4',
        'SM' => '3', 'SN' => '4', 'SO' => '4', 'SR' => '4', 'SS' => '4', 'ST' => '4',
        'SV' => '4', 'SX' => '4', 'SY' => '3', 'SZ' => '4', 'TC' => '4', 'TD' => '4',
        'TF' => '4', 'TG' => '4', 'TH' => '4', 'TJ' => '4', 'TK' => '4', 'TL' => '4',
        'TM' => '4', 'TN' => '3', 'TO' => '4', 'TR' => '3', 'TT' => '4', 'TV' => '4',
        'TW' => '4', 'TZ' => '4', 'UA' => '3', 'UG' => '4', 'US' => '3', 'UY' => '4',
        'UZ' => '4', 'VA' => '3', 'VC' => '4', 'VE' => '4', 'VG' => '4', 'VI' => '4',
        'VN' => '4', 'VU' => '4', 'WF' => '4', 'WS' => '4', 'XZ' => '3', 'YE' => '4',
        'YT' => '4', 'ZA' => '4', 'ZM' => '4', 'ZW' => '4',
      );
    }

    /**
     * Posti payment zones for Parcel Connect shipments
     */
    private function _paymentZonesParcelConnect() {
      return array(
        'EE' =>	'UP1', 'LV' =>	'UP1', 'LT' =>	'UP1', 'SE' =>	'UP4',
        'NL' =>	'UP7', 'BE' =>	'UP7', 'AT' =>	'UP7', 'PL' =>	'UP7',
        'LU' =>	'UP8', 'DE' =>	'UP3', 'SK' =>	'UP8', 'CZ' =>	'UP7',
        'ES' => 'UP8', 'PT' =>  'UP8', 'BG' =>  'UP8', 'DK' =>  'UP5',
        'SI' => 'UP8', 'HU' =>  'UP8',
      );
    }

    /**
     * Get tracking
     *
     * @param string|string[] $trackings
     * @return Result|null
     */
    public function getTracking($trackings)
    {
      if (!is_array($trackings)) {
        $trackings = [$trackings];
      }

      $result = $this->_trackFactory->create();

      foreach ($trackings as $tracking_number) {
        $tracking = $this->_trackStatusFactory->create();
        $tracking->setCarrier($this->_code);
        $tracking->setCarrierTitle('Posti');
        $tracking->setTracking($tracking_number);
        $tracking->setUrl($this->getTrackingUrl($tracking_number));
        $result->append($tracking);
      }

      return $result;
    }

    /**
     * Get tracking URL
     *
     * @param string $tracking_number
     * @return string
     */
    public function getTrackingUrl($tracking_number) {
      return "http://www.posti.fi/yritysasiakkaat/seuranta/#/lahetys/{$tracking_number}";
    }
}
