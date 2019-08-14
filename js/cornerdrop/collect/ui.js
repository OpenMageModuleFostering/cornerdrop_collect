if (!window.CornerDrop) window.CornerDrop = {};
if (!window.CornerDrop.Collect) window.CornerDrop.Collect = {};

(function() {
    'use strict';

    /* global Class, $$, $H, google */

    window.CornerDrop.Collect.UI = Class.create({
        initialize: function( options ) {
            var self = this;

            self.options = Object.extend({
                geolocationAPIClass: null,
                searchClass: null,
                gmapsClass: null,
                geocoder: typeof google !== "undefined" && typeof google.maps !== "undefined" ? google.maps.Geocoder : null,
                geocoderStatus: typeof google !== "undefined" && typeof google.maps !== "undefined" ? google.maps.GeocoderStatus : null,
                searchTemplate: null,
                shouldCheckTermsAndConditions: true,
                termsAndConditionsLabel: 'By using this service you agree to the <a href="https://cornerdrop.com/legal/consumer-terms-retailer" target="_blank">Terms &amp; Conditions.</a>',
                elements: {
                    cornerdropContainer: '.cdc-panel',
                    injectLocation: '#checkout-step-shipping',
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
                    shippingFormSubmit: '#shipping-buttons-container button',
                    termsAndConditionsCheckbox: '#cornerdrop-collect-tc-checkbox',
                    termsAndConditionsLocation: '#cornerdrop-collect-toggle-button'
                },
                pager: {
                    resultsPerPage: 5
                },
                classes: {
                    hidden: 'cdc-hidden',
                    error: 'cdc-error',
                    loading: 'cdc-loading',
                    active: 'cdc-active',
                    resultItem: 'cdc-result',
                    show: 'cdc-show'
                },
                initialState: false,
                afterRequestCallback: function() {

                }
            }, options || {});

            self.els = {};
            self.observers = [];
            self.delegatedObservers = [];
            self.searchValue = null;
            self.isGeolocationQuery = null;
            self.resultsPage = 1;

            self.injectSearchTemplate();
            self.injectTermsAndConditions();

            self.createElementReferences(self.options.elements);
            self.applyObservers();
            self.addListeners();
            self.toggleCornerdrop(self.options.initialState);
            self.disableContinueButton(true);

            if (self.options.gmapsClass) {
                self.options.gmapsClass.createMap();
                self._hideElement(self.options.gmapsClass.mapElement, true);
            }

            if (self.options.geocoder) {
                self.geocoder = new self.options.geocoder();
            }
        },

        createElementReferences: function(elements) {
            var hash = $H(elements);
            var self = this;

            hash.each(function(item) {
                self.els[item.key] = $$(item.value).first();
            });
        },

        injectSearchTemplate: function() {
            var self = this;
            var injectLocation = $$(self.options.elements.injectLocation).first();

            injectLocation.insert({
                top: self.options.searchTemplate
            });
        },

        /**
         * Build terms and conditions elements.
         */
        injectTermsAndConditions: function() {
            var self = this;

            if (!self.options.shouldCheckTermsAndConditions) {
                return;
            }

            var termsAndConditionsContainer = new Element('div', {
                'class': 'cdc-tc-container'
            });

            var termsAndConditionsCheckbox = new Element('input', {
                'type': 'checkbox',
                'checked': 'checked',
                'id': self.options.elements.termsAndConditionsCheckbox.replace(/#/, '')
            });

            var termsAndConditionsLabel = new Element('label', {
                'class': 'cdc-tc-label',
                'for': self.options.elements.termsAndConditionsCheckbox.replace(/#/, '')
            }).update(self.options.termsAndConditionsLabel);

            termsAndConditionsContainer.insert({
                bottom: termsAndConditionsCheckbox
            }).insert({
                bottom: termsAndConditionsLabel
            });

            var termsAndConditionsInsertLocation = $$(self.options.elements.termsAndConditionsLocation).first();
            if (termsAndConditionsInsertLocation) {
                termsAndConditionsInsertLocation.insert({
                    before: termsAndConditionsContainer
                });
            }
        },

        requestLocation: function(callback, errorCallback) {
            var self = this;

            if ( self.options.geolocationAPIClass ) {
                if ( callback && errorCallback ) {
                    self.options.geolocationAPIClass.getLocation(callback, errorCallback);
                } else {
                    throw '[CornerDrop Collect] Geolocation callback not provided';
                }
            } else {
                throw '[CornerDrop Collect] GeolocationAPI class not defined';
            }
        },

        searchGeolocation: function(event) {
            event.preventDefault();

            var self = this;

            self._resetFormButtons(event);
            self.els.searchButton.addClassName(self.options.classes.loading);
            self.els.geolocationIcon.addClassName(self.options.classes.active);
            self.requestLocation(self.search.bind(self), self.error.bind(self));
            self.isGeolocationQuery = true;
        },

        searchQuery: function(event) {
            event.preventDefault();

            var self = this;
            var location = {
                coords: {
                    latitude: null,
                    longitude: null
                }
            };

            self.isGeolocationQuery = false;
            self.els.searchButton.addClassName(self.options.classes.loading);
            self.searchValue = self.els.searchField.value;
            self.els.searchList.addClassName(self.options.classes.hidden);

            if (self.geocoder) {
                self.geocoder.geocode({
                    'address': self.searchValue,
                    'bounds': self._searchBounds(),
                    'componentRestrictions': {
                        'country': 'GB'
                    }
                }, function geocoderResults(results, status) {
                    if (status == self.options.geocoderStatus.OK) {
                        location.coords.latitude = results[0].geometry.location.lat();
                        location.coords.longitude = results[0].geometry.location.lng();
                        self.search.call(self, location);
                    } else {
                        self.error('Location not found, please refine your search');
                        throw '[CornerDrop Collect] Geocoding failed:' + status;
                    }
                });
            } else {
                self.requestLocation(self.search.bind(self), self.error.bind(self));
            }
        },

        search: function(location) {
            var self = this;
            var latitude = location.coords.latitude;
            var longitude = location.coords.longitude;

            self.resultsPage = 1;

            if (self.options.searchClass) {
                if (latitude && longitude) {
                    self.options.searchClass.search(self.searchValue, latitude, longitude, self.displayResultsList.bind(self));
                } else {
                    self.error('CornerDrop is currently unavailable');
                    throw '[CornerDrop Collect] Location services are unavailable';
                }
            } else {
                throw '[CornerDrop Collect] Search class not found';
            }
        },

        /**
         * The bounds by which the geocoding request should be restricted.
         *
         * @returns {google.maps.LatLngBounds}
         * @private
         */
        _searchBounds: function () {
            // United Kingdom and Northern Ireland
            return new google.maps.LatLngBounds(
                new google.maps.LatLng(49.86, -8.45),
                new google.maps.LatLng(60.86, 1.78)
            );
        },

        _createResult: function(item) {
            var result = new Element('div');
            var distance = item.location.distance.toFixed(1);

            var itemTitle = new Element('span', {
                'class': 'cdc-result-item-title'
            }).update(item.name + ' (' + distance + ' miles)');
            result.appendChild(itemTitle);

            var address = new Element('span', {
                'class': 'cdc-result-item-body'
            }).update(item.addressHtml);
            result.appendChild(address);

            var openingHours = new Element('span', {
                'class': 'cdc-result-item-body'
            }).update(item.openingHoursHtml);
            result.appendChild(openingHours);

            return result;
        },

        displayResultsList: function(classCallback) {
            var self = this;
            var resultsList = new Element('ul');
            var searchList = self.els.searchList;
            var error;

            if (!self.options.searchClass.error) {
                
                if (classCallback.results.length) {
                    searchList.innerHTML = '';

                    classCallback.results.each(function resultItem(item) {
                        var element = new Element('li', {
                            "data-store-id": item.id,
                            'class': self.options.classes.resultItem
                        });

                        var result = self._createResult(item);

                        if (result) {
                            element.appendChild(result);

                            resultsList.insert({
                                bottom: element
                            });
                        }
                    });

                    searchList.insert(resultsList);

                    if (self.options.gmapsClass.options.mapClass) {
                        self.displayResultsOnMap(classCallback.results, self.setStore.bind(self));
                    } else {
                        self.setStore.bind(self);
                    }

                    self.els.searchList.removeClassName(self.options.classes.hidden);

                    if (error = self.els.error) {
                        error.addClassName(self.options.classes.hidden);
                    }

                    self._showResultsPage();
                } else {
                    self.centerMapOnLocation(classCallback.lat, classCallback.lng);
                    self.error('No results were found');
                }
            } else {
                self.error('CornerDrop is currently unavailable');
            }

            self.els.searchButton.removeClassName(self.options.classes.loading);

            if (self.isGeolocationQuery) {
                self.els.geolocationIcon.addClassName('cdc-active');
                self.els.searchField.value = '';
            } else {
                self.els.searchButton.addClassName(self.options.classes.hidden);
                self.els.resetButton.removeClassName(self.options.classes.hidden);
                self.els.geolocationIcon.removeClassName('cdc-active');
            }
        },

        displayResultsOnMap: function(data, callback) {
            var self = this;

            // Setup the markers and ensure the map is displayed
            self._hideElement(self.options.gmapsClass.mapElement, false);
            self.options.gmapsClass.resizeMap();

            self.options.gmapsClass.deleteMarkers();

            if (data) {
                self.options.gmapsClass.createMarkers(data, callback);
            }
        },

        centerMapOnLocation: function (lat, lng) {
            var self = this;

            // Setup the markers and ensure the map is displayed
            self._hideElement(self.options.gmapsClass.mapElement, false);
            self.options.gmapsClass.resizeMap();

            self.options.gmapsClass.deleteMarkers();

            self.options.gmapsClass.setCenter(lat, lng);
        },

        error: function(message) {
            var self = this;
            var errorElement;

            if (!self.els.error) {
                errorElement = new Element('div', {
                    class: self.options.classes.error
                });

                self.els.searchList.insert({
                    before: errorElement
                });

                self.els.error = errorElement;
            }

            self.els.error.innerHTML = message;
            self.els.error.removeClassName(self.options.classes.hidden);
            self.els.searchButton.removeClassName(self.options.classes.loading);
            self.isGeolocationQuery = false;
        },

        selectResult: function(event, element) {
            var self = this;

            self.setStore(element.readAttribute('data-store-id'));
        },

        setStore: function(storeId) {
            var self = this;
            var item = self.options.searchClass.getResultById(storeId);

            if (item) {
                self.els.selectedStore.update(self._createResult(item));
                self.options.searchClass.select(storeId);
                self._hideElement(self.els.undoElement, false);

                self._hideElement(self.els.storeContainer, false);
                self._hideElement(self.els.searchList, true);

                // For the marker with a matching ID to the storeId
                // trigger animation and display of the markers info
                self.options.gmapsClass.markers.each(function (element) {
                    if (element.id == storeId) {
                        self.options.gmapsClass.triggerMarker(element);
                    }
                });

                self.checkCanDisableContinueButton();
            }
        },

        undoSetStore: function(event) {
            event.preventDefault();
            var self = this;

            self.els.selectedStore.innerHTML = "";
            self._hideElement(self.els.storeContainer, true);
            self._hideElement(self.els.searchList, false);

            self.options.searchClass.deselect();
            self.checkCanDisableContinueButton();
        },

        /**
         * Establish whether or not the continue button should be disabled by checking the store selection and the terms
         * and conditions checkbox.
         *
         * If it should be enabled, enabled it.  If it should be disabled, disable it.
         */
        checkCanDisableContinueButton: function () {
            var self = this;

            var isTermsAndConditionsChecked = !self.options.shouldCheckTermsAndConditions || self.els.termsAndConditionsCheckbox.checked;
            var isStoredSelected = !!self.options.searchClass.selected;

            if (isTermsAndConditionsChecked && isStoredSelected) {
                self.disableContinueButton(false);
            } else {
                self.disableContinueButton(true);
            }
        },

        disableContinueButton: function(state) {
            var self = this;

            if (state) {
                self.els.shippingFormSubmit.disabled = 'disabled';
            } else {
                self.els.shippingFormSubmit.disabled = '';
            }
        },

        checkTermsAndConditions: function (event) {
            var self = this;
            self.checkCanDisableContinueButton();
        },

        toggleCornerdrop: function(state, event) {
            if (event) {
                event.preventDefault();
            }

            var self = this;

            self._hideElement(self.els.shippingForm, state);
            self._hideElement(self.els.cornerdropContainer, !state);
            self._hideElement($$('.cdc-tc-container').first(), !state);
            if (state) {
                self.checkCanDisableContinueButton();
            } else {
                self.disableContinueButton(false);
            }
        },

        applyObservers: function() {
            var self = this;

            self._attachObserver(self.els.geolocationIcon, 'click', self.searchGeolocation.bind(self));
            self._attachObserver(self.els.searchForm, 'submit', self.searchQuery.bind(self));
            self._attachObserver(self.els.searchField, 'keyup', self._searchFieldActions.bind(self));
            self._attachObserver(self.els.resetButton, 'click', self._resetFormButtons.bind(self));
            self._attachObserver(self.els.resultsPager, 'click', self._showResultsPage.bind(self));
            self._attachObserver(self.els.undoElement, 'click', self.undoSetStore.bind(self));
            self._attachDelegatedObserver(self.els.searchList, 'click', 'li.cdc-result', self.selectResult.bind(self));

            if (self.options.shouldCheckTermsAndConditions) {
                self._attachObserver(self.els.termsAndConditionsCheckbox, 'click', self.checkTermsAndConditions.bind(self));
            }
        },

        addListeners: function() {
            var self = this;

            Event.observe(document, 'cornerdrop_collect:enabled', self.toggleCornerdrop.bind(self, true));
            Event.observe(document, 'cornerdrop_collect:disabled', self.toggleCornerdrop.bind(self, false));
        },

        destroyObservers: function() {
            var self = this;

            // Destroy event observers
            self.observers.each(function (observer) {
                Event.stopObserving(observer.element, observer.event, observer.handler);
            });

            self.observers = [];

            // Destroy delegated observers
            self.delegatedObservers.each(function (observer) {
                observer.stop();
            });

            self.delegatedObservers = [];
        },

        _showResultsPage: function(event) {
            if ( event ) {
                event.preventDefault();
            }

            var self = this;
            var end = self.resultsPage * self.options.pager.resultsPerPage;
            var start = end - self.options.pager.resultsPerPage;
            var elements = self.els.searchList.select('.' + self.options.classes.resultItem + ':nth-child(n+' + start + '):nth-child(-n+' + end + ')');

            // Show the next page of items
            elements.each(function(item) {
                item.addClassName(self.options.classes.show);
            });

            if (start < self.options.searchClass.results.length) {
                self.resultsPage++;
                self.els.resultsPager.removeClassName(self.options.classes.hidden);
            } else {
                self.els.resultsPager.addClassName(self.options.classes.hidden);
            }

            // Hide pager if max has been met on last pagination
            if (start + self.options.pager.resultsPerPage >= self.options.searchClass.results.length) {
                self.els.resultsPager.addClassName(self.options.classes.hidden);
            }
        },

        _searchFieldActions: function(event) {
            var self = this;
            var searchFieldLength = self.els.searchField.value.length;

            if (searchFieldLength > 0) {
                self.els.searchButton.addClassName(self.options.classes.active);
            } else {
                self.els.searchButton.removeClassName(self.options.classes.active);
            }

            self._resetFormButtons(event);
        },

        _resetFormButtons: function(event) {
            var self = this;

            if (event.keyCode !== 13) {
                self.els.resetButton.addClassName(self.options.classes.hidden);
                self.els.searchButton.removeClassName(self.options.classes.hidden);
            }

            if (event.keyCode === 0) {
                self.els.searchButton.removeClassName(self.options.classes.active);
            }
        },

        _attachObserver: function(element, event, handler) {
            var self = this;

            self.observers.push({
                element: element,
                event: event,
                handler: handler
            });

            Event.observe(element, event, handler);
        },

        _attachDelegatedObserver: function(element, event, delegationElement, handler) {
            var self = this;
            var observer = Event.on(element, event, delegationElement, handler);

            self.delegatedObservers.push(observer);
        },

        _hideElement: function(element, state) {
            var self = this;

            if (element) {
                if (state) {
                    element.addClassName(self.options.classes.hidden);
                } else {
                    element.removeClassName(self.options.classes.hidden);
                }
            }
        }
    });

})();
