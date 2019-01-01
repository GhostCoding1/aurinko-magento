define(
    [   'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function (ko, $, Component, url) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: false,
            preselectedMethod: ko.observable(),
            
            defaults: {
                template: 'Piimega_CheckoutFinland/payment/checkoutfinland'
            },
            
            getUseAjax: function(){
                return window.checkoutConfig.payment.checkoutFinland.useAjax;
            },
            
            getUseMethodSelect: function(){
                return window.checkoutConfig.payment.checkoutFinland.useMethodSelect;
            },
            
            getAvailableMethods: function(){
                return window.checkoutConfig.payment.checkoutFinland.availableMethods;
            },
            
            getPaymentRequestUrl: function(){
                return window.checkoutConfig.payment.checkoutFinland.requestUrl;
            },
            
            getPaymentFailureUrl: function(){
                return window.checkoutConfig.payment.checkoutFinland.failureUrl;
            },
            
            validate: function () {
                if(!this.getUseMethodSelect()){
                    return true;
                }
                var form = 'form[data-role=checkout-finland-form]';
                if($(form).validation() && $(form).validation('isValid')){
                    var method = this.preselectedMethod();
                    return ($.type(method) !== 'undefined' && method.length > 0 && (function(method){
                        var methods = this.getAvailableMethods();
                        var l = methods.length;
                        for(var i = 0; i < l; i++){
                            if(methods[i].value && methods[i].value == method){
                                return true;
                            }
                        }
                        return false;
                    }));
                }
            },

            getData: function () {
                var parent = this._super(),
                    additionalData = {};
                if(this.getUseMethodSelect()){
                    additionalData['cf_method'] = this.preselectedMethod();
                }
                return $.extend(true, parent, {
                    'additional_data': additionalData
                });
            },

            afterPlaceOrder: function () {
                if(!this.getUseAjax()){
                    $.mage.redirect(url.build(this.getPaymentRequestUrl()));
                    return false;
                }

                var self = this;

                $.ajax({
                    url: url.build(self.getPaymentRequestUrl()),
                    type: 'post',
                    context: this,
                    data: { 'is_ajax': true }
                }).done(function(response){
                    if ($.type(response) === 'object' && !$.isEmptyObject(response)) {
                        if(response.success == true && $.type(response.data) != 'undefined' && response.data.length > 0){
                            $('#checkout_finland_form_wrap').append(response.data);
                            return false;
                        }
                        if($.type(response.redirect) === 'string'){
                            $.mage.redirect(url.build(response.redirect));
                            return false;
                        }
                    }
                    $.mage.redirect(url.build(self.getPaymentFailureUrl()));
                }).fail(function(){
                    $.mage.redirect(url.build(self.getPaymentFailureUrl()));
                });
            }
        });
    }
);
