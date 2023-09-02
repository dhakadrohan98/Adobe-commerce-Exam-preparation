/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'mage/translate',
    'uiComponent',
    'Magento_Ui/js/modal/alert',
    'domReady!'
], function ($, _, $t, Component, alert) {
    'use strict';

    return Component.extend({
        defaults: {
            generalErrorMessage: $t('An error occurred. Refresh the page and try again.'),
            invalidCreditCardErrorMessage: $t('An error occurred. Please validate the credit card information.'),
            orderFormSelector: '#edit_form',
            containerSelector: '#payment_form_quick_checkout',
            additionalDataSelector: '#quick-checkout-additional-data',
            creditCardFormSelector: '#bolt-credit-card-form',
            quickCheckoutMethodRadioSelector: 'input[value="quick_checkout"]',
            paymentField: null,
            publishableKey: null,
            locale: '',
            creditCardFormConfig: [],
            hostedCreditCard: null
        },

        /** @inheritdoc */
        initialize: function (config, element) {
            var boltEmbedded = window.Bolt(config.publishableKey, {language: config.locale});

            this.element = element;
            this.publishableKey = config.publishableKey;
            this.locale = config.locale;
            this.creditCardFormConfig = config.creditCardFormConfig;

            _.bindAll(
                this,
                'submitForm',
                'getPaymentData',
                'onError',
                'onChangePaymentMethod'
            );

            this._super();
            this.paymentField = boltEmbedded.create('payment_component', this.creditCardFormConfig);
            this.orderForm = $(this.orderFormSelector);
            this.initFormListeners();
            this.reinitializeFormAfterReloads();
            return this;
        },

        /**
         * Initialize form submit listeners.
         */
        initFormListeners: function () {
            this.orderForm.off('changePaymentMethod.' + this.code);
            this.orderForm.on('changePaymentMethod.' + this.code, this.onChangePaymentMethod);
        },

        /**
         * Initialize cc form
         */
        mountCreditCardForm: function () {
            $('body').trigger('processStart');
            this.paymentField.unmount();
            this.paymentField.mount(this.creditCardFormSelector);
            $('body').trigger('processStop');
            this.paymentField.on('inputSubmitRequest', function () {
                this.orderForm.trigger('submitOrder');
            }.bind(this));
        },

        /**
         * Reinitialize submitOrder event
         */
        reinitializeFormAfterReloads: function () {
            if ($(this.quickCheckoutMethodRadioSelector).is(':checked')) {
                this.orderForm.off('beforeSubmitOrder.' + this.code);
                this.orderForm.on('beforeSubmitOrder.' + this.code, this.submitForm);
                this.disableDefaultSubmitOrderListener();
                this.orderForm.off('submitOrder.' + this.code);
                this.orderForm.on('submitOrder.' + this.code, this.submitForm);
                this.mountCreditCardForm();
            } else {
                this.enableDefaultSubmitOrderListener();
            }
        },

        /**
         * Reinitialize submitOrder event on delegate.
         *
         * @param {Object} event
         * @param {String} method
         */
        onChangePaymentMethod: function (event, method) {
            this.orderForm.off('beforeSubmitOrder.' + this.code);
            this.orderForm.off('submitOrder.' + this.code);
            if (method === this.code) {
                this.orderForm.on('beforeSubmitOrder.' + this.code, this.submitForm);
                this.disableDefaultSubmitOrderListener();
                this.orderForm.on('submitOrder.' + this.code, this.submitForm);
                this.mountCreditCardForm();
            } else {
                this.enableDefaultSubmitOrderListener();
            }
        },

        /**
         * Form submit handler
         *
         * @param {Object} e
         */
        submitForm: function (e) {
            if (this.orderForm.valid()) {
                this.getPaymentData();
            } else {
                $('body').trigger('processStop');
            }
            e.stopImmediatePropagation();
            return false;
        },

        /**
         * @inheritdoc
         */
        getPaymentData: function () {
            this.paymentField.tokenize().then(function (data) {
                if (typeof data !== 'object' || typeof data.stack === 'undefined') {
                    $(this.additionalDataSelector).val(JSON.stringify(data));
                    this.orderForm.trigger('realOrder');
                } else {
                    $('body').trigger('processStop');
                    alert({
                        content: this.invalidCreditCardErrorMessage
                    });
                }
            }.bind(this)).catch(this.onError);
        },

        /**
         * Error callback
         */
        onError: function () {
            var message = this.generalErrorMessage;

            $('body').trigger('processStop');
            alert({
                content: message
            });
        },

        /**
         * Disable submit form listeners
         */
        disableDefaultSubmitOrderListener: function () {
            this.orderForm.off('submitOrder');
        },

        /**
         * Disable submit form listeners
         */
        enableDefaultSubmitOrderListener: function () {
            this.orderForm.on('submitOrder', function () {
                $(this).trigger('realOrder');
            });
        }
    });
});
