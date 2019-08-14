if (!window.CornerDrop) window.CornerDrop = {};
if (!window.CornerDrop.Collect) window.CornerDrop.Collect = {};

(function() {
    'use strict';

    /* global Class, $$, $H, google */

    window.CornerDrop.Collect.Adminhtml_UI = Class.create(window.CornerDrop.Collect.UI, {
        disableContinueButton: function($super, state) {
            /* noop */
        }
    });

})();
