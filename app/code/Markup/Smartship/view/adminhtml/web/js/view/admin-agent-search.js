define([
  'jquery',
  'uiComponent',
  'ko',
  'mage/storage',
  'mage/translate'
], function ($, Component, ko, storage, $t) {
  'use strict';
  return function(config) {
    return Component.extend({
      defaults: {
        template: 'Markup_Smartship/admin-agent-search',
      },
      agents: ko.observableArray([]),
      selectedAgent: ko.observable(null),
      agentsLoading: ko.observable(false),
      searchPostcode: ko.observable(''),
      validSearchPostcode: ko.observable(false),
      formAction: config.formAction,
      formKey: config.formKey,
      orderId: config.orderId,

      initialize: function () {
        this._super();

        var self = this;

        self.searchPostcode.subscribe(function() {
          self.validSearchPostcode(self.validatePostcode(self.searchPostcode()));
          self.updateAgents();
        });
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
        var postcode = String(this.searchPostcode()).trim();
        if (this.validatePostcode(postcode)) {
          this.getAgents(postcode, 'PO2103');
        } else {
          this.agents([]);
        }
      },

      getAgents: function (postcode, methodCode) {
        var self = this;

        var serviceUrl = '/rest/V1/smartship/agents?postcode=' + encodeURIComponent(postcode) + '&method=' + encodeURIComponent(methodCode) + '&adminUpdate=1';

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
            self.agentsLoading(false);
          }
        );
      }
    });
  };

});
