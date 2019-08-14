if (!window.CornerDrop) window.CornerDrop = {};
if (!window.CornerDrop.Collect) window.CornerDrop.Collect = {};

(function() {
    'use strict';

    /* global Class */

    window.CornerDrop.Collect.GeolocationAPI = Class.create({
        initialize: function( options ) {
            var self = this;

            self.options = Object.extend({
                enableHighAccuracy: false,
                timeout: 5000,
                maximumAge: 0,
                watcher: false,
                afterRequestCallback: function() {

                }
            }, options || {});

            self.geoOptions = {
                enableHighAccuracy: self.options.enableHighAccuracy,
                timeout: self.options.timeout,
                maximumAge: self.options.maximumAge
            }

            self.position = {
                latitude: null,
                longitude: null,
                error: ''
            };

            if (self.options.watcher) {
                this.watcherID = navigator.geolocation.watchPosition(self.onSuccess.bind(this), self.onError.bind(this), self.geoOptions);
            }
        },

        getLocation: function(callback, errorCallback) {
            var self = this;

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(self.onSuccess.bind(this, callback), self.onError.bind(this, errorCallback), self.geoOptions);
                self.options.afterRequestCallback();
            } else {
                self.options.afterRequestCallback();
                return false;
            }
        },

        onSuccess: function(callback, location) {
            var self = this;

            self.position.latitude = location.coords.latitude;
            self.position.longitude = location.coords.longitude;

            if (callback) {
                callback(location);
            }

            return;
        },

        onError: function(callback, error) {
            var self = this;

            switch(error.code) {
                case error.PERMISSION_DENIED:
                    self.position.error = "User denied the request for Geolocation.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    self.position.error = "Location information is unavailable.";
                    break;
                case error.TIMEOUT:
                    self.position.error = "The request to get user location timed out.";
                    break;
                case error.UNKNOWN_ERROR:
                    self.position.error = "An unknown error occurred.";
                    break;
            }

            if (callback) {
                callback(self.position.error);
            }
        }

    });
})();