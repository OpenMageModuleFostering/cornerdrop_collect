if (!window.CornerDrop) window.CornerDrop = {};
if (!window.CornerDrop.Collect) window.CornerDrop.Collect = {};

(function () {
    'use strict';

    window.CornerDrop.Collect.Adminhtml = Class.create({

        initialize: function ( options ) {
            var self = this;

            self.options = Object.extend({
                activateLabel: "Click & Collect from a CornerDrop location",
                editLabel: "Edit",
                dialogTitle: "Select CornerDrop location",
                controlButtonSelector: ".cdc-control-button",
                shippingAddressSelector: "#order-shipping_address_choice",
                sameAsBillingCheckboxSelector: '#order-shipping_same_as_billing',
                uiSearchEndpoint: '',
                clearCornerDropSelectionSelector: ".js-cdc-clear-cornerdrop",
                clearCornerDropShippingAddressFields: 'input[type=text][name^="order[shipping_address]"',
                searchWindowTemplate: 'No template defined'
            }, options || {});

            self.elements = {};
            self.observers = [];

            self.attachObservers();
        },

        attachObservers: function () {
            var self = this;

            var control = self._get(self.options.controlButtonSelector);
            if (control) {
                self._attachObserver(control, "click", self.open.bind(self));
            }

            var sameAsBillingCheckbox = self._get(self.options.sameAsBillingCheckboxSelector);
            if (sameAsBillingCheckbox) {
                self._attachObserver(sameAsBillingCheckbox, "click", self.toggleDisabledState.bind(self));
            }

            var clearCornerDrop = self._get(self.options.clearCornerDropSelectionSelector);
            if (clearCornerDrop) {
                self._attachObserver(clearCornerDrop, "click", self.clearCornerDropSelection.bind(self));
            }
        },

        open: function (event) {
            var self = this;

            if (typeof event !== "undefined" && event.preventDefault) {
                event.preventDefault();
            }

            var control = self._get(self.options.controlButtonSelector);

            // Don't open the window if the button is disabled in some way.
            if (control.hasClassName('disabled') || control.disabled) {
                return;
            }

            self._displayDialog();
        },

        close: function (event) {
            var self = this;

            if (typeof event !== "undefined" && event.preventDefault) {
                event.preventDefault();
            }

            if (self.dialog) {
                self.dialog.close();
            }

            // Trigger the region updater to refresh
            $('order-shipping_address_country_id').simulate('change');

            self.reloadShippingAddress();
        },

        /**
         * Submit the shipping address, and reload the areas of the page that we need to.
         */
        reloadShippingAddress: function () {
            // Reload shipping address
            var data = window.order.serializeData(window.order.shippingAddressContainer).toObject();

            window.order.loadArea(['shipping_method', 'totals', 'billing_method'], true, data);
        },

        _displayDialog: function () {
            var self = this;

            self.dialog = Dialog.info('', {
                id: "cdc-dialog",
                className: "magento",
                windowClassName: "popup-window",
                title: self.options.dialogTitle,
                width: 950,
                height: 450,
                top: 50,
                zIndex: 300,
                recenterAuto: true,
                draggable: true,
                resizable: false,
                closable: true,
                showEffect: Element.show,
                hideEffect: Element.hide,
                onClose: self.close.bind(self),
                onShow: function () {
                    new window.CornerDrop.Collect.Adminhtml_UI({
                        searchClass: new window.CornerDrop.Collect.Adminhtml_Search({
                            form: '#order-shipping_address',
                            endpoint: self.options.uiSearchEndpoint,
                            storeIdField: "order[shipping_address][cornerdrop_store_id]"
                        }),
                        geolocationAPIClass: new window.CornerDrop.Collect.GeolocationAPI(),
                        gmapsClass: new window.CornerDrop.Collect.Gmaps(),
                        searchTemplate: self.options.searchWindowTemplate,
                        initialState: true,
                        shouldCheckTermsAndConditions: false,
                        elements: {
                            injectLocation: '#cdc-dialog_content .magento_message',
                            cornerdropContainer: '.cdc-panel',
                            searchForm: '#cdc-search-form',
                            searchField: '#cdc-search-form input',
                            geolocationIcon: '.js-cdc-geolocation',
                            searchList: '.js-cdc-search-results',
                            storeContainer: '.js-cdc-store',
                            selectedStore: '.js-cdc-store-details',
                            error: '.js-cdc-error',
                            shippingForm: '#co-shipping-form .form-list',
                            searchButton: '.js-cdc-search-button',
                            resetButton: '.js-cdc-reset-button',
                            resultsPager: '.js-cdc-results-pager',
                            undoElement: '.js-cdc-undo',
                            shippingFormSubmit: '#shipping-buttons-container button'
                        }
                    });
                }
            });
        },

        /**
         * Observer: Called when the "Same as billing address" checkbox is changed.
         */
        toggleDisabledState: function () {
            var self = this;

            var sameAsBillingCheckbox = self._get(self.options.sameAsBillingCheckboxSelector);
            var control = self._get(self.options.controlButtonSelector);

            var disabledClassName = 'disabled';

            if (sameAsBillingCheckbox && control) {
                var sameAsBilling = sameAsBillingCheckbox.checked;

                if (sameAsBilling) {
                    control.addClassName(disabledClassName);
                } else {
                    control.removeClassName(disabledClassName);
                }
            }
        },

        /**
         * Observer: Called when the "reset form" link is clicked.  This resets the shipping form, ensuring that we don't
         * communicate this order as a cornerdrop order to the backend processes.
         */
        clearCornerDropSelection: function (event) {
            if (typeof event !== "undefined" && event.preventDefault) {
                event.preventDefault();
            }

            var confirmation = confirm("Are you sure you want to reset the shipping address?");

            if (confirmation) {
                $$(this.options.clearCornerDropShippingAddressFields).each(function (el) {
                    el.value = '';
                });

                this.reloadShippingAddress();
            }
        },

        _get: function(selector) {
            var self = this;

            if (typeof self.elements[selector] == 'undefined') {
                self.elements[selector] = $$(selector).first();
            }

            return self.elements[selector];
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
