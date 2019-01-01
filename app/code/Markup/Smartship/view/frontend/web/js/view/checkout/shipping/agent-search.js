define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function ($, Component, ko, checkoutData, shippingService, quote, storage, customerData, $t) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'Markup_Smartship/checkout/shipping/agent-search',
        },
        agents: ko.observableArray([]),
        selectedAgentId: ko.observable(false),
        agentsLoading: ko.observable(false),
        searchPostcode: ko.observable(''),
        validSearchPostcode: ko.observable(false),
        maxResults: ko.observable(5),
        prevPostcode: null,
        shippingAddressInitDone: false,

        initialize: function () {
          this._super();

          var self = this;

          quote.shippingAddress.subscribe(function () {
            self.updateSearchPostcode();
            self.shippingAddressInitDone = true;
          });

          quote.shippingAddress.subscribe( function(oldShippingAddress) {
            if (oldShippingAddress && typeof oldShippingAddress.postcode != 'undefined') {
              self.prevPostcode = oldShippingAddress.postcode;
            }
          }, null, 'beforeChange');

          self.searchPostcode.subscribe(function() {
            self.validSearchPostcode(self.validatePostcode(self.searchPostcode()));

            self.maxResults(5);
            self.updateAgents();

            // Save to cookies
            $.cookie('smartshipSearchPostcode', self.searchPostcode());
          });

          quote.shippingMethod.subscribe(function () {
            self.updateAgents();
          });

          // Save max results to cookies
          self.maxResults.subscribe(function(maxResults) {
            $.cookie('smartshipMaxResults', maxResults);
          });

          // Set default agent
          self.agents.subscribe(function() {
            if (self.agents().length > 0 && ! self.setAgentFromCookies() && self.agentNotSet()) {
              self.selectedAgentId(self.agents()[0].id);
            }
          });

          self.selectedAgentId.subscribe(function(agentId) {
            var agent = self.findAgentById(agentId);

            if (agent != false) {
              quote.smartshipAgent = agent;

              // Save to cookies
              var shippingMethod = quote.shippingMethod();
              if (shippingMethod != null && typeof shippingMethod != 'undefined') {
                var cookieKey = 'smartshipAgentId-' + shippingMethod.method_code.replace('.', '-');
                $.cookie(cookieKey, agentId);
              }
            }
          });
        },

        displayAgents: function () {
          var agents = this.agents();
          return agents.slice(0, this.maxResults());
        },

        displayMore: function () {
          this.maxResults(this.maxResults() + 5);
        },

        findAgentById: function (id) {
          for (var i = 0; i < this.agents().length; i++) {
            if (this.agents()[i].id == id) {
              return this.agents()[i];
            }
          }

          return false;
        },

        setAgentFromCookies: function() {
          var shippingMethod = quote.shippingMethod();
          if (shippingMethod != null && typeof shippingMethod != 'undefined') {
            var cookieKey = 'smartshipAgentId-' + shippingMethod.method_code.replace('.', '-');
            var agentId = $.cookie(cookieKey);
            var cookieMaxResults = $.cookie('smartshipMaxResults');

            if (typeof agentId != 'undefined') {
              var i = 0;

              // If max results is over 5 and agent is there, set it to new max here
              if (typeof cookieMaxResults != 'undefined' && cookieMaxResults != null && cookieMaxResults > 5) {
                for (i = 0; i < this.agents().length; i++) {
                  if (this.agents()[i].id == agentId) {
                    this.maxResults(cookieMaxResults);
                    break;
                  }
                }
              }

              // Check if agent is in the current list and set if so
              // There may be more agents than visible on the frontend, so we need to limit by max results
              var end = (this.maxResults() < this.agents().length) ? this.maxResults() : this.agents().length;
              for (i = 0; i < end; i++) {
                if (this.agents()[i].id == agentId) {
                  this.selectedAgentId(agentId);
                  return true;
                }
              }
            }
          }

          return false;
        },

        agentNotSet: function () {
          if (typeof this.selectedAgentId() == 'undefined') {
            return true;
          }

          if (this.selectedAgentId() == null || this.selectedAgentId() == false) {
            return true;
          }

          var end = (this.maxResults() < this.agents().length) ? this.maxResults() : this.agents().length;
          for (var i = 0; i < end; i++) {
            if (this.agents()[i].id == this.selectedAgentId()) {
              return false;
            }
          }

          return true;
        },

        /**
         * Checks whether agents are available
         */
        agentsAvailable: function() {
          var shippingMethod = quote.shippingMethod();
          if (shippingMethod == null || typeof shippingMethod == 'undefined' || typeof shippingMethod.method_code == 'undefined') {
            return false;
          }

          var agentMethods = ['PO2103', 'PO2103S'];
          return shippingMethod.carrier_code == 'smartship' && agentMethods.indexOf(shippingMethod.method_code) > -1;
        },

        /**
         * Change search postcode when changing shipping address postcode
         */
        updateSearchPostcode: function() {
          var address = quote.shippingAddress();

          // Check if some postcode have been saved to cookies before using one
          // from the shipping address
          var cookiePostcode = $.cookie('smartshipSearchPostcode');
          if ( ! this.shippingAddressInitDone && typeof cookiePostcode != 'undefined' && this.validatePostcode(cookiePostcode)) {
            this.searchPostcode(cookiePostcode);
            return true;
          }
          // Otherwise we will use the postcode from the shipping address
          else if (address && typeof address.postcode != 'undefined' && address.postcode && this.prevPostcode != address.postcode) {
            this.searchPostcode(address.postcode);
            return true;
          }

          return false;
        },

        /**
         * Validate postcode
         */
        validatePostcode: function(postcode) {
          var cleanedPostcode = String(postcode).trim();

          return cleanedPostcode && cleanedPostcode.length == 5 && /^\d+$/.test(cleanedPostcode);
        },

        /**
         * Callback on changing address
         */
        updateAgents: function () {
          var shippingMethod = quote.shippingMethod();
          if (shippingMethod == null || typeof shippingMethod == 'undefined') {
            this.agents([]);
            return;
          }

          if (!this.agentsAvailable()) {
            this.agents([]);
            return;
          }

          var address = quote.shippingAddress();
          var postcode = String(this.searchPostcode()).trim();
          if (address && this.validatePostcode(postcode)) {
            this.getAgents(postcode, shippingMethod.method_code);
          } else {
            this.agents([]);
          }
        },

        getAgents: function (postcode, method_code) {
          var self = this;

          // Skip fetching if postcode is empty
          if (postcode.length < 1) {
            self.agents([]);
            return;
          }

          // Get cache
          var cacheKey = 'cachedSmartshipAgents-' + encodeURIComponent(postcode) + '-' + encodeURIComponent(method_code).replace('.', '-') + '-' + encodeURIComponent(window.checkoutConfig.smartship.postipaketti_mode);
          var cachedAgents = customerData.get(cacheKey)();

          if (typeof cachedAgents != 'undefined' && cachedAgents.length > 0) {
            self.agents(cachedAgents);
            return;
          }

          var serviceUrl = 'rest/V1/smartship/agents?postcode=' + encodeURIComponent(postcode) + '&method=' + encodeURIComponent(method_code);

          self.agentsLoading(true);

          storage.get(
              serviceUrl, '', false
          ).done(
              function (result) {
                self.agents(JSON.parse(result));
              }
          ).fail(
              function (response) {
                self.agents([]);
              }
          ).always(
              function () {
                // Set cache
                customerData.set(cacheKey, self.agents());
                self.agentsLoading(false);
              }
          );
        }
    });
});
