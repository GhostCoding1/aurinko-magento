/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        '../model/shipping-rates-validator',
        '../model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        shippingProviderShippingRatesValidator,
        shippingProviderShippingRatesValidationRules
    ) {
        "use strict";
        defaultShippingRatesValidator.registerValidator('smartship', shippingProviderShippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('smartship', shippingProviderShippingRatesValidationRules);
        return Component;
    }
);
