<?php
namespace Piimega\CheckoutFinland\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Piimega\CheckoutFinland\Model\Config\Source\Payment\Methods;

class ConfigProvider implements ConfigProviderInterface
{
    /*
     * @var \Piimega\CheckoutFinland\Model\Checkoutfinland
     */
    protected $_checkoutFinland;

    /*
     * @var \Piimega\CheckoutFinland\Model\Config\Source\Payment\Methods
     */
    protected $_methodsConfig;

    /**
     * Constructor
     *
     * @param \Piimega\CheckoutFinland\Model\Checkoutfinland $checkoutFinland
     * @param \Piimega\CheckoutFinland\Model\Config\Source\Payment\Methods $methodsConfig
     */
    public function __construct(
        Checkoutfinland $checkoutFinland,
        Methods $methodsConfig
    ){
        $this->_checkoutFinland = $checkoutFinland;
        $this->_methodsConfig = $methodsConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(){

        $useMethodSelect = $this->_checkoutFinland->getUseMethodSelect();

        $data = [
            'requestUrl'        => $this->_checkoutFinland->getPaymentRedirectUrl(),
            'failureUrl'        => $this->_checkoutFinland->getPaymentFailureRedirectUrl(),
            'useAjax'           => $this->_checkoutFinland->getUseAjaxOnRedirect(),
            'useMethodSelect'   => $useMethodSelect
        ];

        if($useMethodSelect){
            $data['availableMethods'] = $this->_checkoutFinland->getAvailableMethodsConfig(true, true);
        }
        
        return [
            'payment' => [
                'checkoutFinland' => $data
            ]
        ];
    }
}
