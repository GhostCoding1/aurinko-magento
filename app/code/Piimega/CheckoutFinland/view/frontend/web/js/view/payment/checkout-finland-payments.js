define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'checkout_finland',
                component: 'Piimega_CheckoutFinland/js/view/payment/method-renderer/checkoutfinland-method'
            }
        );
        return Component.extend({});
    }
);