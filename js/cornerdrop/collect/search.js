if (!window.CornerDrop) window.CornerDrop = {};
if (!window.CornerDrop.Collect) window.CornerDrop.Collect = {};

(function () {
    'use strict';

    /* global Class, Ajax, $$, $H, shipping, shippingRegionUpdater */

    window.CornerDrop.Collect.Search = Class.create({

        initialize: function (options) {
            var self = this;

            self.options = Object.extend({
                endpoint: null,
                form: "#co-shipping-form",
                storeIdField: "shipping[cornerdrop_store_id]",
                prepareFormCallback: function () {},
                regionUpdateCallback: function () {}
            }, options || {});

            if (!self.options.endpoint) {
               throw "[CornerDrop Collect] Search endpoint missing!";
            }

            self.results = [];
            self.query   = null;
            self.lat     = null;
            self.lng     = null;

            self._injectMarkup();
            self._applyObservers();
        },

        search: function (query, lat, long, callback) {
            var self = this;

            self.clear();

            self.query = query;
            self.lat   = lat;
            self.lng   = long;

            new Ajax.Request(self.options.endpoint, {
                method: "GET",
                parameters: {
                    q: query,
                    lat: lat,
                    long: long
                },
                evalJSON: true,
                onSuccess: function (response) {
                    self.results = response.responseJSON;
                },
                onFailure: function (response) {
                    if (response.responseJSON) {
                        self.error = response.responseJSON.message;
                    } else {
                        self.error = true;
                    }
                },
                onComplete: function (response) {
                    if (callback) {
                        callback(self);
                    }
                }
            });
        },

        getResultById: function(id) {
            var self = this;
            var foundResult = null;

            self.results.each(function (result) {
                if (result.id == id) {
                    foundResult = result;
                }
            });

            return foundResult;
        },

        select: function (id) {
            var self = this;

            self.selected = self.getResultById(id);

            if (self.selected) {
                this._fillForm(self.selected);
            }

            return !!self.selected;
        },

        deselect: function () {
            var self = this;
            self.resetStoreId();
            self.selected = null;
        },

        clear: function () {
            var self = this;

            self.results = [];
            self.error = null;
        },

        /**
         * Return the currently selected store id, blank if no store is selected.
         *
         * @returns {*}
         */
        getStoreId: function () {
            var element = $("shipping:cornerdrop_store_id");
            if (element) {
                return element.value;
            }
        },

        resetStoreId: function() {
            var element = $("shipping:cornerdrop_store_id");
            if (element) {
                element.value = null;
            }
        },

        fillForm: function() {
            var self = this;

            if (self.selected) {
                self._fillForm(self.selected);
            }

            return false;
        },

        _getForm: function () {
            var self = this;

            if (!self.form) {
                self.form = $$(self.options.form).first();

                if (!self.form) {
                    throw "[CornerDrop Collect] Shipping Address form not found!";
                }
            }

            return self.form;
        },

        _getFormElement: function (form, name, first) {
            first = typeof first !== 'undefined' ? first : true;

            var elements = form.select('[name *= "[' + name + ']"]');

            return first ? elements.first() : elements;
        },

        _injectMarkup: function () {
            var self = this;

            self._getForm().appendChild(new Element("input", {
                type: "hidden",
                name: self.options.storeIdField,
                value: "",
                id: "shipping:cornerdrop_store_id"
            }));
        },

        _applyObservers: function () {
            var self = this;

            Event.observe(document, "cornerdrop_collect:disabled", self.resetStoreId.bind(this));
            Event.observe(document, "cornerdrop_collect:enabled", self.fillForm.bind(self));
        },

        _fillForm: function (data) {
            var self = this;

            var element;
            var form = self._getForm();

            if (self.options.prepareFormCallback) {
                self.options.prepareFormCallback(true);
            }

            $H(data.address).each(function (item) {
                if (item.key == "street") {
                    var idx = 0;
                    self._getFormElement(form, "street", false).each(function (field) {
                        field.value = item.value[idx++];
                    });
                } else {
                    element = self._getFormElement(form, item.key);
                    if (element) {
                        element.value = item.value;
                    }
                }
            });

            if (self.options.regionUpdateCallback) {
                self.options.regionUpdateCallback();
            }

            ["region_id", "region"].each(function (field) {
                element = self._getFormElement(form, field);
                if (element) {
                    element.value = data.address[field];
                }
            });

            element = self._getFormElement(form, "cornerdrop_store_id");
            if (element) {
                element.value = data.id;
            }
        }
    });
})();
