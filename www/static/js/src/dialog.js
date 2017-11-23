/**
 * This file allows us to close dialogs and clear any affected input elements.
 */
define(function(require) {
    console.log('dialog.js loaded');

    var $ = require('jquery');

    $(document).ready(function() {
        $(".dialog_close").on("click", function () {
            var type = $(this).parent("div").data('type');
            $(this).parent("div").fadeOut();
            $("input.error").removeClass(type);
        });
    });
});
