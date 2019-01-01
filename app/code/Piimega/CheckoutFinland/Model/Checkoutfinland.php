<?php
namespace Piimega\CheckoutFinland\Model;

use \Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\DataObject;
use Magento\Sales\Block\Order\Info;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Checkoutfinland
 */
class Checkoutfinland extends AbstractMethod
{
    /**
     * Payment method code
     */
    const PAYMENT_METHOD_CHECKOUT_FINLAND_CODE  = 'checkout_finland';

    /**
     * Checkout Finland request constants
     */
    const PAYMENT_VERSION       = '0001';
    const PAYMENT_TYPE          = 0;
    const PAYMENT_ALGORITHM     = 3;
    const PAYMENT_COUNTRY       = 'FIN';
    const ADULT_CONTENT_CODE    = 2;
    const NORMAL_CONTENT_CODE   = 1;
    const DEVICE_HTML_CODE      = 1;
    const DEVICE_XML_CODE       = 10;

    /**
     * Checkout Finland payment statuses
     */
    const STATUS_SUCCESS    = 'success';
    const STATUS_DELAYED    = 'delayed';
    const STATUS_REJECTED   = 'rejected';
    const STATUS_CANCELED   = 'canceled';
    const STATUS_REDIRECTED = 'redirected';

    /**
     * Status of payment if order was canceled locally in action \Piimega\CheckoutFinland\Controller\Payment\Cancel
     */
    const LOCALLY_CANCELED_STATUS_CODE = -99;

    /**
     * Maximum payment data age in minutes
     */
    const MAX_PAYMENT_DATA_AGE = 15;

    /**
     * Additional data attribute used for saving operator (bank) on checkout stage
     */
    const PRESELECTED_METHOD_KEY = 'cf_method';

    /**
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_CHECKOUT_FINLAND_CODE;

    /**
     * @var string
     */
    protected $_infoBlockType = 'Piimega\CheckoutFinland\Block\Info\Checkoutfinland';

    /**
     * @var string
     */
    protected $_redirectBlockType = 'Piimega\CheckoutFinland\Block\Redirect\Checkoutfinland';

    /**
     * Payment Method feature
     * 
     * @var bool
     */
    protected $_isGateway = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapture = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapturePartial = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefund = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canVoid = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canFetchTransactionInfo = false;

    /**
     * @var string
     */
    protected $_checkoutUrl = 'https://payment.checkout.fi';

    /**
     * @var array
     */
    protected $_allowedCurrencies = ['EUR'];

    /**
     * @var string
     */
    protected $_paymentSuccessRedirectUrl = 'checkout/onepage/success';

    /**
     * @var string
     */
    protected $_paymentFailureRedirectUrl = 'checkout/onepage/failure';

    /**
     * Checkout Finland payment status codes
     * 
     * Code -99 is used for local cancellation (i.e. )
     */
    protected $_statuses = [
        self::STATUS_REDIRECTED => [1],
        self::STATUS_SUCCESS    => [2, 5, 6, 8, 9, 10],
        self::STATUS_DELAYED    => [3, 4, 7],
        self::STATUS_REJECTED   => [-2, -3, -4, -10],
        self::STATUS_CANCELED   => [-1, self::LOCALLY_CANCELED_STATUS_CODE]
    ];

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    protected $_order;

    /**
     * @var \Piimega\CheckoutFinland\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Piimega\CheckoutFinland\Model\Config\Source\Payment\Methods
     */
    protected $_methodsConfigSource;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Piimega\CheckoutFinland\Helper\Data $helper
     * @param \Piimega\CheckoutFinland\Model\Config\Source\Payment\Methods $methodsConfigSource
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Piimega\CheckoutFinland\Helper\Data $helper,
        \Piimega\CheckoutFinland\Model\Config\Source\Payment\Methods $methodsConfigSource,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_helper = $helper;
        $this->_urlBuilder = $urlBuilder;
        $this->_methodsConfigSource = $methodsConfigSource;
    }

    /**
     * Assign data to info model instance
     *
     * @param array|DataObject $data
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     */
    public function assignData(DataObject $data){
        parent::assignData($data);

        if($this->getUseMethodSelect()){

            /*
             * Magento 2.0.x
             */
            $cfMethod = $data->getData(self::PRESELECTED_METHOD_KEY);

            /*
             * Magento 2.1.x
             */
            if(empty($cfMethod)){
                $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
                if (!is_object($additionalData)) {
                    $additionalData = new DataObject($additionalData ?: []);
                }
                $cfMethod = $additionalData->getData(self::PRESELECTED_METHOD_KEY);
            }

            if(in_array($cfMethod, $this->getAvailableMethodsConfig())){
                $this->getInfoInstance()
                    ->setAdditionalInformation(self::PRESELECTED_METHOD_KEY, $cfMethod);
            }
        }
        return $this;
    }

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @api
     */
    public function initialize($paymentAction, $stateObject){

        $stateObject
            ->setData('state', \Magento\Sales\Model\Order::STATE_NEW)
            ->setData('status', $this->getConfigData('new_order_status'))
            ->setData('is_notified', false);

        $payment = $this->getInfoInstance()->setCfStamp(time());

        $payment->getOrder()
            ->setCanSendNewEmailFlag(false)
            ->setData(OrderInterface::CUSTOMER_NOTE_NOTIFY, false);

        return $this;
    }

    /**
     * Get config payment action url
     * Used to universalize payment actions when processing payment place
     *
     * @return string
     * @api
     */
    public function getConfigPaymentAction(){
        return \Magento\Payment\Model\Method\AbstractMethod::ACTION_ORDER;
    }

    /**
     * Returns operator (bank) selected during checkout
     *
     * @return string
     */
    public function getPreselectedMethod(){
        return $this->getInfoInstance()->getAdditionalInformation(self::PRESELECTED_METHOD_KEY);
    }

    /**
     * @return string|false
     */
    public function getPreselectedMethodLabel(){
        $code = $this->getPreselectedMethod();
        if(!empty($code)){
            $methods = $this->_methodsConfigSource->toOptionArray();
            foreach($methods as $method){
                if($method['value'] == $code){
                    return $method['label'];
                }
            }
        }
        return false;
    }

    /**
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode){
        return in_array($currencyCode, $this->_allowedCurrencies);
    }

    /**
     * @param string $code
     * @return array
     */
    public function getStatusCodes($code = ''){
        if(!empty($code) && isset($this->_statuses[$code])){
            return $this->_statuses[$code];
        }
        return [];
    }

    /**
     * @param string $statusCode
     * @return string
     */
    public function getStatusCodeDescription($statusCode){
        $statusCode = intval($statusCode);
        foreach($this->_statuses as $key => $statusCodes){
            if(in_array($statusCode, $statusCodes)){
                return $key;
            }
        }
        return '';
    }

    /**
     * @param bool $asArray
     * @param bool $appendLabels
     * @return array || string
     */
    public function getAvailableMethodsConfig($asArray = true, $appendLabels = false){
        $methods = $this->getConfigData("methods");
        if($asArray){
            $methods = explode(',', $methods);
            if($appendLabels){
                $methodsConfig = $this->_methodsConfigSource->toOptionArray();
                foreach($methodsConfig as $key => $method){
                    if(!in_array($method['value'], $methods)){
                        unset($methodsConfig[$key]);
                    }
                }
                return $methodsConfig;
            }
        }
        return $methods;
    }

    /**
     * @return string
     */
    public function getCheckoutUrl(){
        return $this->_checkoutUrl;
    }

    /**
     * @return string
     */
    public function getRedirectBlockType(){
        return $this->_redirectBlockType;
    }

    /**
     * @return bool
     */
    public function getUseMethodSelect(){
        return boolval($this->getConfigData("use_bank_select"));
    }

    /**
     * @return bool
     */
    public function getCreateInvoice(){
        return boolval($this->getConfigData('create_invoice'));
    }

    /**
     * @return string
     */
    public function getPassword(){
        return $this->_getPassword();
    }

    /**
     * @return string
     */
    public function getNewPaymentOrderStatus(){
        return $this->getConfigData('new_order_status');
    }

    /**
     * @return string
     */
    public function getCanceledPaymentOrderStatus(){
        return $this->getConfigData('canceled_order_status');
    }

    /**
     * @return string
     */
    public function getRejectedPaymentOrderStatus(){
        return $this->getConfigData('rejected_order_status');
    }

    /**
     * @return string
     */
    public function getSuccessPaymentOrderStatus(){
        return $this->getConfigData('approved_order_status');
    }

    /**
     * @return string
     */
    public function getDelayedPaymentOrderStatus(){
        return $this->getConfigData('delayed_order_status');
    }

    /**
     * @return string
     */
    public function getCanceledOrderRedirectUrl(){
        return $this->getConfigData('canceled_order_redirect_url');
    }

    /**
     * @return bool
     */
    public function logAllRequests(){
        return boolval($this->getConfigData('log_all_requests'));
    }

    /**
     * @return string
     */
    public function getPaymentSuccessRedirectUrl(){
        return $this->_paymentSuccessRedirectUrl;
    }

    /**
     * @return string
     */
    public function getPaymentFailureRedirectUrl(){
        return $this->_paymentFailureRedirectUrl;
    }

    /**
     * @return string
     */
    public function getPaymentRedirectUrl(){
        if($this->getConfigData("use_bank_select")){
            return 'checkoutfinland/payment/direct';
        } else if($this->getConfigData('use_xml_mode')){
            return 'checkoutfinland/payment/methods';
        }
        return 'checkoutfinland/payment/html';
    }

    /**
     * "Direct" and "Html" actions use ajax, action "Methods" does not
     *
     * @return bool
     */
    public function getUseAjaxOnRedirect(){
        return $this->getConfigData("use_bank_select") || !$this->getConfigData('use_xml_mode');
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    public function isValidOrderObject($order){
        return is_object($order) && $order instanceof \Magento\Sales\Model\Order && $order->getId();
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    public function isAllowedToCreateNewPayment($order){
        if($this->isValidOrderObject($order)){
            return $this->_isAllowedToCreateNewPayment($order);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function processPaymentSuccess(){
        try{
            if($this->getCreateInvoice()){
                $payment = $this->getInfoInstance();
                $order = $payment->getOrder();
                if($order->canInvoice()) {
                    $payment->capture();
                }
            }
            return true;
        } catch (\Exception $e){
            $this->_logger->critical($e);
        }
        return false;
    }

    /**
     * @return array
     */
    public function requestPaymentMethodsData(){
        
        /*
         * Try to get payment data from additional info instead of generating new request to Checkout Finland.
         * (This data exists if customer refreshes the page with banks / methods)
         */
        $payment = $this->getInfoInstance();
        $data = $payment->getAdditionalInformation('checkout_data');

        if(!empty($data) && $this->_canUseSavedMethodsData()){
            return unserialize($data);
        }

        $data = $this->_helper->sendPost($this->_checkoutUrl, $this->getCheckoutData());
        if(isset($data['id']) && !empty($data['id'])){
            $payment->setCfTransactionId($data['id']);
        }
        if(isset($data['status']) && !empty($data['status'])){
            $payment->setCfStatusCode($data['status']);
        }
        $data = $this->_helper->prepareMethodsArray($data);
        $payment->setAdditionalInformation('checkout_data', serialize($data))->save();

        return $data;
    }

    /**
     * Prepares data for request to Checkout Finland
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCheckoutData(){
        $order = $this->getInfoInstance()->getOrder();

        if(!$this->isValidOrderObject($order)){
            throw new LocalizedException(__('Invalid order provided to Checkout Finland payment model'));
        }

        if(!$this->_isAllowedToCreateNewPayment($order)){
            throw new LocalizedException(__('Not allowed to create a new Checkout Finland payment for order %1', $order->getId()));
        }

        $address = $order->getBillingAddress();
        if(!is_object($address) || !$address instanceof \Magento\Sales\Model\Order\Address){
            throw new LocalizedException(__('Invalid Billing address for order %1', $order->getId()));
        }

        $data = [];
        $data['VERSION']		= self::PAYMENT_VERSION;
        $data['STAMP']          = $this->_getPaymentTimestamp();
        $data['AMOUNT']			= $this->_getPaymentAmount($order);
        $data['REFERENCE']		= $this->_getPaymentOrderId($order);
        $data['MESSAGE']		= $this->_getPaymentMessage();
        $data['LANGUAGE']		= $this->_getLanguageCode();
        $data['MERCHANT']		= $this->_getMerchantId();
        $data['RETURN']			= $this->_urlBuilder->getUrl('checkoutfinland/result/success');
        $data['CANCEL']			= $this->_urlBuilder->getUrl('checkoutfinland/result/cancel');
        $data['REJECT']			= $this->_urlBuilder->getUrl('checkoutfinland/result/reject');
        $data['DELAYED']		= $this->_urlBuilder->getUrl('checkoutfinland/result/delayed');
        $data['COUNTRY']        = self::PAYMENT_COUNTRY;
        $data['CURRENCY']		= $this->_getPaymentCurrency($order);
        $data['DEVICE']			= $this->_getDeviceCode();
        $data['CONTENT']		= $this->_getContentTypeCode();
        $data['TYPE']			= self::PAYMENT_TYPE;
        $data['ALGORITHM']		= self::PAYMENT_ALGORITHM;
        $data['DELIVERY_DATE']	= $this->_getDeliveryDate($order);
        $data['FIRSTNAME']		= substr($address->getFirstname(), 0, 40);
        $data['FAMILYNAME']		= substr($address->getLastname(), 0, 40);
        $data['ADDRESS']		= substr($this->_implodeAddressStreet($address->getStreet()), 0, 40);
        $data['POSTCODE']		= substr((string)$address->getPostcode(), 0, 14);
        $data['POSTOFFICE']		= substr($address->getCity(), 0, 18);
        $data['MAC']			= strtoupper(md5(implode('+', $data) ."+" .$this->_getPassword()));
        $data['EMAIL']			= $address->getEmail();
        $data['PHONE']			= $address->getTelephone();

        return $data;
    }

    /**
     * Checks age of saved Checkout Data. The data is considered to old if its age in minutes exceeds value in
     * constant self::MAX_PAYMENT_DATA_AGE
     *
     * @return bool
     */
    protected function _canUseSavedMethodsData(){
        $payment = $this->getInfoInstance();

        $stamp = intval($payment->getCfStamp());
        $now = time();
        if($now > $stamp + 60 * self::MAX_PAYMENT_DATA_AGE){
            $payment->setCfStamp($now);
            return false;
        }
        return true;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    protected function _isAllowedToCreateNewPayment($order){
        $payment = $order->getPayment();
        return $payment->getMethod() == self::PAYMENT_METHOD_CHECKOUT_FINLAND_CODE
        && $order->getState() == \Magento\Sales\Model\Order::STATE_NEW
        && (empty($payment->getCfStatusCode())
            || in_array($payment->getCfStatusCode(), $this->_statuses[self::STATUS_REDIRECTED]));
    }

    /**
     * @return string
     */
    protected function _getMerchantId(){
        return $this->getConfigData("merchant_id");
    }

    /**
     * @return string
     */
    protected function _getPassword(){
        return $this->getConfigData("password");
    }

    /**
     * @return int
     */
    protected function _getPaymentTimestamp(){
        return intval($this->getInfoInstance()->getCfStamp());
    }

    /**
     * Amount must be given in cents as integer
     *
     * @return int
     */
    protected function _getPaymentAmount($order){
        return intval(floatval($order->getGrandTotal()) * 100);
    }

    /**
     * @return string
     */
    protected function _getPaymentOrderId($order){
        return $order->getIncrementId();
    }

    /**
     * @return string
     */
    protected function _getPaymentMessage(){
        $storeName = $this->_scopeConfig
            ->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return 'Order ' .$this->getPaymentOrderId() .', ' .$storeName;
    }

    /**
     * @return string
     */
    protected function _getDeliveryDate($order){
        $date = (string)$order->getData('delivery_date');
        if(!empty($date)){
            return date('Ymd', strtotime($date));
        }
        return date('Ymd');
    }

    /**
     * @return string
     */
    protected function _getPaymentCurrency($order){
        return $order->getOrderCurrencyCode();
    }

    /**
     * @return int
     */
    protected function _getContentTypeCode(){
        if($this->getConfigData("may_have_adult_content")){
            return self::ADULT_CONTENT_CODE;
        }
        return self::NORMAL_CONTENT_CODE;
    }

    /**
     * @return string
     */
    protected function _getLanguageCode(){
        $lang = $this->_scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $lang = explode('_', (string)$lang);
        return strtoupper($lang[0]);
    }

    /**
     * @return int
     */
    protected function _getDeviceCode(){
        if($this->getConfigData('use_xml_mode') || $this->getConfigData("use_bank_select")){
            return self::DEVICE_XML_CODE;
        }
        return self::DEVICE_HTML_CODE;
    }

    /**
     * @return string
     */
    protected function _implodeAddressStreet($streetData){
        if (is_array($streetData)) {
            $streetData = trim(implode(' ', $streetData));
        }
        return $streetData;
    }
}
