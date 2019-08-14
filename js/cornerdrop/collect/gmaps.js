if (!window.CornerDrop) window.CornerDrop = {};
if (!window.CornerDrop.Collect) window.CornerDrop.Collect = {};

(function() {
    'use strict';

    /* global Class */

    window.CornerDrop.Collect.Gmaps = Class.create({
        initialize: function( options ) {
            var self = this;

            self.options = Object.extend({
                mapOptions: {
                    center: {
                        lat: 54.144529,
                        lng: -3.8476843
                    },
                    zoom: 5
                },
                mapSelector: '#cdc-map-canvas',
                mapClass: typeof google !== "undefined" && typeof google.maps !== "undefined" ? google.maps : null,
                baseUrl: null,
                markerImage: null
            }, options || {});

            self.markers = [];
            self.bounds = [];

            self._createLocationObject();
        },

        createMap: function() {
            var self = this;

            if (self.options.mapClass) {
                self.mapElement = $$(self.options.mapSelector).first();
                self.mapObject = new self.options.mapClass.Map(self.mapElement, self.locationObject);
                self.infoWindow = new self.options.mapClass.InfoWindow();
            } else {
                throw '[CornerDrop Collect] Google Maps object not found';
            }
        },

        resizeMap: function() {
            var self = this;
            self.options.mapClass.event.trigger(self.mapElement, 'resize');
            self._setMapBounds();
        },

        createMarkers: function( data, markerCallback ) {
            var self = this;

            if (self.options.mapClass) {
                self._clearMapBounds();

                if (data.length) {
                    data.each(function (item) {
                        self._createMarker(item, markerCallback);
                    });
                } else {
                    self._createMarker(data, markerCallback);
                }

                self._setMapBounds();
            } else {
                throw '[CornerDrop Collect] Google Maps object not found';
            }
        },

        deleteMarkers: function() {
            var self = this;

            self._clearMarkers();
            self.markers = [];
        },

        triggerMarker: function(marker) {
            var self = this;

            if (self.options.mapClass) {
                if (marker.getAnimation() != null) {
                    marker.setAnimation(null);
                } else {
                    marker.setAnimation(self.options.mapClass.Animation.BOUNCE);
                    window.setTimeout(function () {
                        marker.setAnimation(null);
                    }, 1400);
                }

                var infoWindowContent = '<strong>' + marker.title + ' (' + marker.distance + ' miles)</strong>';

                if (marker.cdcAddress) {
                    infoWindowContent += '<br />' + marker.cdcAddress;
                }

                if (marker.cdcOpeningHours) {
                    infoWindowContent += '<br />' + marker.cdcOpeningHours;
                }

                self.infoWindow.setContent(infoWindowContent);
                self.infoWindow.open(self.mapObject, marker);
            } else {
                throw '[CornerDrop Collect] Google Maps object not found';
            }
        },

        _createMarker: function(item, markerCallback) {
            var self = this;
            var bounds = new self.options.mapClass.LatLng(item.location.latitude, item.location.longitude);
            var marker = new self.options.mapClass.Marker({
                position: bounds,
                map: self.mapObject,
                title: item.name,
                id: item.id,
                cdcAddress: item.addressHtml,
                cdcOpeningHours: item.openingHoursHtml,
                distance: item.location.distance.toFixed(1),
                icon: self.options.markerImage
            });

            self.markers.push(marker);
            self.bounds.push(bounds);

            self.options.mapClass.event.addListener(marker, 'click', function() {
                markerCallback(item.id);
            });
        },

        setCenter: function (lat, lng) {
            var self = this;

            self.mapObject.setCenter(new google.maps.LatLng(
                lat,
                lng
            ));

            self.mapObject.setZoom(12);
        },

        _createLocationObject: function() {
            var self = this;

            self.locationObject = {
                center: new google.maps.LatLng(
                    self.options.mapOptions.center.lat,
                    self.options.mapOptions.center.lng
                ),
                zoom: self.options.mapOptions.zoom
            }
        },

        _clearMarkers: function() {
            var self = this;

            for (var i = 0; i < self.markers.length; i++) {
                self.markers[i].setMap(null);
            }
        },

        _clearMapBounds: function() {
            var self = this;

            if (self.mapBounds) {
                self.mapBounds = null;
                self.bounds = [];
            }
        },

        _setMapBounds: function() {
            var self = this;

            if (self.bounds.length > 0) {
                // Ensure all of the markers are shown inside the bounds
                // of the map object
                self.mapBounds = new self.options.mapClass.LatLngBounds(self.bounds[0]);
                self.bounds.each(function(item) {
                    self.mapBounds.extend(item);
                });
                self.mapObject.fitBounds(self.mapBounds);
            } else {
                self.mapObject.setCenter(new google.maps.LatLng(
                    self.options.mapOptions.center.lat,
                    self.options.mapOptions.center.lng
                ));

                self.mapObject.setZoom(self.options.mapOptions.zoom);
            }
        }

    });
})();
