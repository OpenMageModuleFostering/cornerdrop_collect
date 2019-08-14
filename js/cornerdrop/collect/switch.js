if (!window.CornerDrop) window.CornerDrop = {};
if (!window.CornerDrop.Collect) window.CornerDrop.Collect = {};

(function () {
    'use strict';

    /* global Class, $, $$ */

    window.CornerDrop.Collect.Switch = Class.create({

        initialize: function ( options ) {
            var self = this;

            self.options = Object.extend({
                labels: {
                    checkbox: "Ship to a CornerDrop location",
                    deactivateButton: 'Switch to home delivery',
                    activateButton: 'Use CornerDrop'
                },
                value: false,
                elements: {
                    toggleButton: '#cornerdrop-collect-toggle-button',
                    toggleButtonLocation: '#shipping-buttons-container',
                    billingFormList: '#co-billing-form ul.form-list',
                    shippingFormList: '#co-shipping-form ul.form-list'
                }
            }, options || {});

            self.observers = [];

            self.build();
        },

        build: function () {
            var self = this;

            // Create control element in the billing form

            var control = new Element("li", {
                id: "billing:cornerdrop_control",
                class: "control"
            });

            control.appendChild(new Element("input", {
                type: "hidden",
                name: "billing[ship_to_cornerdrop]",
                value: self.options.value ? "1" : "0",
                id: "billing:ship_to_cornerdrop"
            }));

            control.appendChild(new Element("input", {
                type: "radio",
                name: "billing[use_for_shipping]",
                value: "0",
                checked: !!self.options.value,
                title: self.options.labels.checkbox,
                id: "billing:use_for_shipping_cornerdrop",
                'class': "radio"
            }));

            control.appendChild(new Element("label", {
                for: "billing:use_for_shipping_cornerdrop"
            }).update(self.options.labels.checkbox));

            var billingFormList = $$(self.options.elements.billingFormList).first();
            if (!billingFormList) {
                throw "[CornerDrop Collect] Could not find the billing form list!";
            }
            billingFormList.appendChild(control);

            // Create switch field in the shipping form

            var cornerdropField = new Element("input", {
                type: "hidden",
                name: "shipping[is_cornerdrop_collect]",
                value: self.options.value ? "1" : "0",
                id: "shipping:is_cornerdrop_collect"
            });

            var shippingFormList = $$(self.options.elements.shippingFormList).first();
            if (!shippingFormList) {
                throw "[CornerDrop Collect] Could not find the shipping form list!";
            }
            shippingFormList.appendChild(cornerdropField);

            // Create activate switch in shipping address form

            var toggleLinkLabel = self.options.value ? self.options.labels.deactivateButton : self.options.labels.activateButton;
            var toggleLink = new Element('a', {
                'id': self.options.elements.toggleButton.replace(/#/, ''),
                'href': '',
                'class': 'cdc-toggle-button'
            }).update(toggleLinkLabel);

            var insertLocation = $$(self.options.elements.toggleButtonLocation).first();
            if (!insertLocation) {
                throw "[CornerDrop Collect] Could not find the insert location";
            }

            insertLocation.insert({
                before: toggleLink
            });

            // Attach observers

            self._attachObserver($("billing:cornerdrop_control"), "click", self._enableCornerdrop.bind(self));
            self._attachObserver($("billing:use_for_shipping_yes"), "click", self._disableCornerdrop.bind(self));
            self._attachObserver($("billing:use_for_shipping_no"), "click", self._disableCornerdrop.bind(self));
            self._attachObserver($$(self.options.elements.toggleButton).first(), "click", self._toggleCornerDrop.bind(self));
        },

        destroy: function () {
            var self = this;

            self._destroyObservers();

            $("billing:cornerdrop_control").remove();
        },

        _toggleCornerDrop: function() {
            event.preventDefault();
            var self = this;

            if (self.options.value) {
                self._disableCornerdrop();
            } else {
                self._enableCornerdrop();
            }
        },

        _enableCornerdrop: function () {
            var self = this;

            self.options.value = true;
            $("shipping:same_as_billing").checked = false;
            $("billing:ship_to_cornerdrop").value = "1";
            $("shipping:is_cornerdrop_collect").value = "1";
            $("billing:use_for_shipping_cornerdrop").checked = true;
            $$(self.options.elements.toggleButton).first().update(self.options.labels.deactivateButton);
            Event.fire(document, 'cornerdrop_collect:enabled');
        },

        _disableCornerdrop: function () {
            var self = this;

            var no_checked = $("billing:use_for_shipping_no").checked;
            var yes_checked = $("billing:use_for_shipping_yes").checked;
            var something_checked = no_checked || yes_checked;

            self.options.value = false;
            $("billing:ship_to_cornerdrop").value = "0";
            $("shipping:is_cornerdrop_collect").value = "0";

            if (!something_checked) {
                $("billing:use_for_shipping_no").checked = true;
            }

            $$(self.options.elements.toggleButton).first().update(self.options.labels.activateButton);
            Event.fire(document, 'cornerdrop_collect:disabled');
        },

        _attachObserver: function (element, event, handler) {
            var self = this;

            self.observers.push({
                element: element,
                event: event,
                handler: handler
            });

            element.observe(event, handler);
        },

        _destroyObservers: function () {
            var self = this;

            self.observers.each(function (observer) {
                observer.element.stopObserving(observer.event, observer.handler);
            });

            self.observers = [];
        }

    });
})();
