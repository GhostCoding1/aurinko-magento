<?php
namespace Piimega\CheckoutFinland\Block\Redirect;

use \Magento\Framework\View\Element\AbstractBlock;
use \Magento\Framework\Data\Form;
use \Magento\Framework\View\Element\Context;

/**
 * Block for rendering form for redirecting either to Checkout Finland site
 * or directly to the bank, depending on the module config
 */
class Checkoutfinland extends AbstractBlock{

    /**
     * HTTP request method used in the form
     */
    const FORM_METHOD = 'POST';

    /**
     * @var string
     */
    protected $_url;

    /**
     * @var array
     */
    protected $_fields = [];

    /**
     * HTML-attribute "id" for the form
     *
     * @var string
     */
    protected $_identifier = 'checkout_finland_redirect_form';

    /**
     * @var  \Magento\Framework\Data\Form
     */
    protected $_form;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Data\Form $form
     * @param \Magento\Framework\View\Element\Context $context
     * @param array $data
     */
    public function __construct(
        Form $form,
        Context $context,
        array $data = []
    ) {
        $this->_form = $form;
        parent::__construct($context, $data);
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url){
        $this->_url = $url;
        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setFields($fields){
        $this->_fields = $fields;
        return $this;
    }

    /**
     * @return string
     */
    protected function _toHtml(){
        $this->_form->setAction($this->_url)
            ->setId($this->_identifier)
            ->setName($this->_identifier)
            ->setMethod(self::FORM_METHOD)
            ->setUseContainer(true);

        foreach ($this->_fields as $key => $value) {
            $this->_form->addField($key, 'text', [
                'name' => $key,

                /*
                 * Some of bank data params returned by Checkout Finland
                 * may have empty array as value
                 */
                'value' => (is_array($value) && empty($value)) ? '' : $value
            ]);
        }
        return $this->_form->toHtml() .$this->_getSubmitScript();
    }

    /**
     * @return string
     */
    protected function _getSubmitScript(){
        return
            '<script type="text/javascript">
                document.getElementById("'.$this->_identifier.'").submit();
            </script>';
    }
}