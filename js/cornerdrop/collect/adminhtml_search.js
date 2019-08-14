if (!window.CornerDrop) window.CornerDrop = {};
if (!window.CornerDrop.Collect) window.CornerDrop.Collect = {};

(function() {
    'use strict';

    /* global Class, $$, $H, google */

    window.CornerDrop.Collect.Adminhtml_Search = Class.create(window.CornerDrop.Collect.Search, {
        _injectMarkup: function ($super) {
            /* noop, markup is already on the page for admin */
        },
    });

})();
